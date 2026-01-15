<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once '../../app/bootstrap.php';

// Protect API
ApiAuth::requireLogin();

try {
    // 1. KPI CALCULATIONS
    
    // Total Revenue Invoiced (sum of all grand_totals)
    $sql = "SELECT SUM(grand_total) as total_invoiced FROM invoices";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_invoiced = (float) ($row['total_invoiced'] ?? 0);
    
    // Total Amount Received
    $sql = "SELECT SUM(amount_received) as total_received FROM invoices";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_received = (float) ($row['total_received'] ?? 0);
    
    // Total Outstanding (invoiced - received)
    $total_outstanding = $total_invoiced - $total_received;
    
    // Collection Rate Percentage
    $collection_rate = $total_invoiced > 0 ? round(($total_received / $total_invoiced) * 100, 1) : 0;
    
    // Days Sales Outstanding (simplified calculation)
    $sql = "
        SELECT 
            AVG(DATEDIFF(CURRENT_DATE, invoice_date)) as avg_days
        FROM invoices 
        WHERE status != 'Paid'
    ";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $days_outstanding = (int) ($row['avg_days'] ?? 0);
    
    // Active Clients (clients with at least one invoice)
    $sql = "
        SELECT COUNT(DISTINCT client_id) as active_clients 
        FROM invoices
    ";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $active_clients = (int) ($row['active_clients'] ?? 0);
    
    // Total Invoices Count
    $sql = "SELECT COUNT(*) as total_invoices FROM invoices";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $total_invoices = (int) ($row['total_invoices'] ?? 0);
    
    
    // 2. MONTHLY REVENUE TREND (last 6 months)
    $sql = "
        SELECT 
            DATE_FORMAT(invoice_date, '%b') as month,
            SUM(grand_total) as revenue
        FROM invoices
        WHERE invoice_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(invoice_date, '%Y-%m'), DATE_FORMAT(invoice_date, '%b')
        ORDER BY DATE_FORMAT(invoice_date, '%Y-%m')
    ";
    $result = $conn->query($sql);
    $revenue_trend = [];
    while ($row = $result->fetch_assoc()) {
        $revenue_trend[] = [
            'month' => $row['month'],
            'revenue' => (float) $row['revenue']
        ];
    }
    
    
    // 3. TOP PAYING CLIENTS (by total amount received)
    $sql = "
        SELECT 
            c.company_name,
            c.id,
            SUM(i.amount_received) as total_paid,
            COUNT(i.id) as invoice_count
        FROM clients c
        INNER JOIN invoices i ON c.id = i.client_id
        GROUP BY c.id, c.company_name
        ORDER BY total_paid DESC
        LIMIT 5
    ";
    $result = $conn->query($sql);
    $top_clients = [];
    while ($row = $result->fetch_assoc()) {
        $top_clients[] = [
            'client_name' => $row['company_name'],
            'total_paid' => (float) $row['total_paid'],
            'invoice_count' => (int) $row['invoice_count']
        ];
    }
    
    
    // 4. CLIENT PAYMENT PERFORMANCE
    $sql = "
        SELECT 
            c.company_name as client_name,
            COUNT(i.id) as total_invoices,
            AVG(DATEDIFF(
                CASE 
                    WHEN i.status = 'Paid' THEN i.updated_at 
                    ELSE CURRENT_DATE 
                END, 
                i.invoice_date
            )) as avg_days_to_pay,
            SUM(CASE WHEN i.status = 'Paid' THEN 1 ELSE 0 END) / COUNT(i.id) * 100 as on_time_rate
        FROM clients c
        INNER JOIN invoices i ON c.id = i.client_id
        GROUP BY c.id, c.company_name
        ORDER BY total_invoices DESC
        LIMIT 10
    ";
    $result = $conn->query($sql);
    $payment_performance = [];
    while ($row = $result->fetch_assoc()) {
        $payment_performance[] = [
            'client_name' => $row['client_name'],
            'total_invoices' => (int) $row['total_invoices'],
            'avg_days_to_pay' => round((float) ($row['avg_days_to_pay'] ?? 0), 1),
            'on_time_rate' => round((float) ($row['on_time_rate'] ?? 0), 1)
        ];
    }
    
    
    // 5. OUTSTANDING BY CLIENT
    $sql = "
        SELECT 
            c.company_name as client_name,
            SUM(i.grand_total - i.amount_received) as outstanding
        FROM clients c
        INNER JOIN invoices i ON c.id = i.client_id
        WHERE i.status != 'Paid'
        GROUP BY c.id, c.company_name
        HAVING outstanding > 0
        ORDER BY outstanding DESC
        LIMIT 5
    ";
    $result = $conn->query($sql);
    $outstanding_by_client = [];
    while ($row = $result->fetch_assoc()) {
        $outstanding_by_client[] = [
            'client_name' => $row['client_name'],
            'outstanding' => (float) $row['outstanding']
        ];
    }
    
    
    // 6. AGING REPORT
    $sql = "
        SELECT 
            SUM(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) <= 30 
                THEN (grand_total - amount_received) 
                ELSE 0 
            END) as current_0_30,
            COUNT(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) <= 30 
                AND status != 'Paid'
                THEN 1 
            END) as current_0_30_count,
            
            SUM(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) BETWEEN 31 AND 60 
                THEN (grand_total - amount_received) 
                ELSE 0 
            END) as days_31_60,
            COUNT(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) BETWEEN 31 AND 60 
                AND status != 'Paid'
                THEN 1 
            END) as days_31_60_count,
            
            SUM(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) BETWEEN 61 AND 90 
                THEN (grand_total - amount_received) 
                ELSE 0 
            END) as days_61_90,
            COUNT(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) BETWEEN 61 AND 90 
                AND status != 'Paid'
                THEN 1 
            END) as days_61_90_count,
            
            SUM(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) > 90 
                THEN (grand_total - amount_received) 
                ELSE 0 
            END) as days_90_plus,
            COUNT(CASE 
                WHEN DATEDIFF(CURRENT_DATE, invoice_date) > 90 
                AND status != 'Paid'
                THEN 1 
            END) as days_90_plus_count
        FROM invoices
        WHERE status != 'Paid'
    ";
    $result = $conn->query($sql);
    $aging = $result->fetch_assoc();
    
    $aging_report = [
        'current' => [
            'amount' => (float) ($aging['current_0_30'] ?? 0),
            'count' => (int) ($aging['current_0_30_count'] ?? 0)
        ],
        'days_30' => [
            'amount' => (float) ($aging['days_31_60'] ?? 0),
            'count' => (int) ($aging['days_31_60_count'] ?? 0)
        ],
        'days_60' => [
            'amount' => (float) ($aging['days_61_90'] ?? 0),
            'count' => (int) ($aging['days_61_90_count'] ?? 0)
        ],
        'days_90' => [
            'amount' => (float) ($aging['days_90_plus'] ?? 0),
            'count' => (int) ($aging['days_90_plus_count'] ?? 0)
        ]
    ];
    
    
    // Build comprehensive response
    ApiResponse::success([
        'kpis' => [
            'total_invoiced' => $total_invoiced,
            'total_received' => $total_received,
            'total_outstanding' => $total_outstanding,
            'collection_rate' => $collection_rate,
            'days_outstanding' => $days_outstanding,
            'active_clients' => $active_clients,
            'total_invoices' => $total_invoices
        ],
        'revenue_trend' => $revenue_trend,
        'top_clients' => $top_clients,
        'payment_performance' => $payment_performance,
        'outstanding_by_client' => $outstanding_by_client,
        'aging_report' => $aging_report
    ]);

} catch (Throwable $e) {
    error_log("Reports API Error: " . $e->getMessage());
    ApiResponse::error(
        'Failed to generate reports: ' . $e->getMessage(),
        500
    );
}