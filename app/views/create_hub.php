<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/Business%20project/assets/css/app.css">
    <title>Create Documents - Hub</title>
    <style>
        /* Document Type Grid Styles */
        .document-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            max-width: 1200px;
        }

        .document-card {
            background: white;
            border-radius: 12px;
            padding: 32px 24px;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .document-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.15);
            transform: translateY(-4px);
        }

        .document-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .document-card:hover::before {
            transform: scaleX(1);
        }

        .document-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
        }

        .document-card h3 {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .document-card p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .select-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .select-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }

        .page-header p {
            font-size: 16px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <h2>Create New Document</h2>
            <p>Choose the type of document you want to create</p>
        </div>

        <div class="document-type-grid">
            <!-- Quotation Card -->
            <div class="document-card" onclick="navigateToCreate('quotation')">
                <div class="document-icon">📋</div>
                <h3>Quotation</h3>
                <p>Create a price quotation or estimate for your clients</p>
                <button class="select-btn" type="button">Create Quotation</button>
            </div>

            <!-- Bill without GST Card -->
            <div class="document-card" onclick="navigateToCreate('bill-no-gst')">
                <div class="document-icon">🧾</div>
                <h3>Bill (No GST)</h3>
                <p>Create a simple bill without GST calculations</p>
                <button class="select-btn" type="button">Create Bill</button>
            </div>

            <!-- Invoice with GST Card -->
            <div class="document-card" onclick="navigateToCreate('invoice')">
                <div class="document-icon">📄</div>
                <h3>Invoice (With GST)</h3>
                <p>Create a tax invoice with GST calculations</p>
                <button class="select-btn" type="button">Create Invoice</button>
            </div>

            <!-- Transport Challan Card -->
            <div class="document-card" onclick="navigateToCreate('challan')">
                <div class="document-icon">🚚</div>
                <h3>Transport Challan</h3>
                <p>Create a delivery challan for goods transport</p>
                <button class="select-btn" type="button">Create Challan</button>
            </div>
        </div>
    </main>

    <script>
        function navigateToCreate(type) {
            window.location.href = `/Business%20project/public/index.php?page=create-document&type=${type}`;
        }
    </script>
</body>
</html>