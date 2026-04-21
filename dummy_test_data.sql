-- Dummy seed data for local testing
-- Covers:
-- 1. Unified document flow tables: invoices, invoice_items
-- 2. Supporting master data: company_profile, clients, hsn_codes
--
-- Safe to re-run:
-- - Removes only records created by this seed file using dedicated document numbers.
-- - Reuses dummy clients if they already exist.

START TRANSACTION;

SET @today = CURDATE();

-- ---------------------------------------------------------------------
-- Optional base data
-- ---------------------------------------------------------------------

INSERT INTO company_profile (
    company_name,
    gst_number,
    email,
    phone,
    website,
    pan_number,
    address,
    state,
    state_code,
    bank_name,
    account_number,
    ifsc_code,
    account_holder_name,
    branch_name,
    invoice_prefix,
    default_due_days,
    invoice_terms,
    payment_instructions
)
SELECT
    'GS Metal Concept',
    '24BIDPS5550H1Z7',
    'demo@gsmetal.test',
    '9999990000',
    'https://example.test',
    'ABCDE1234F',
    'Aaryan Pride, Gota, Ahmedabad 382481',
    'Gujarat',
    '24',
    'State Bank of India',
    '12345678901',
    'SBIN0001234',
    'GS Metal Concept',
    'Gota Branch',
    'INV-',
    30,
    'Payment within 30 days. Material once sold will not be taken back.',
    'Please pay by NEFT/IMPS and share the transaction reference.'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM company_profile
);

INSERT INTO hsn_codes (hsn_code, description, gst_rate, category, is_active)
SELECT '7308', 'Steel structures and fabricated assemblies', 18.00, 'Structural Steel', 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM hsn_codes WHERE hsn_code = '7308'
);

INSERT INTO hsn_codes (hsn_code, description, gst_rate, category, is_active)
SELECT '9986', 'Installation and erection services', 18.00, 'Services', 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM hsn_codes WHERE hsn_code = '9986'
);

INSERT INTO hsn_codes (hsn_code, description, gst_rate, category, is_active)
SELECT '9985', 'Surface treatment and coating services', 18.00, 'Services', 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM hsn_codes WHERE hsn_code = '9985'
);

-- ---------------------------------------------------------------------
-- Dummy clients
-- ---------------------------------------------------------------------

INSERT INTO clients (
    company_name,
    contact_person,
    email,
    phone,
    gst_number,
    client_gst_number,
    address,
    state,
    state_code,
    country,
    pincode,
    notes
)
SELECT
    'Dummy Fabrication Works',
    'Rakesh Patel',
    'dummy.fabrication@example.test',
    '9000001001',
    '24AAAAA1111A1Z1',
    '24AAAAA1111A1Z1',
    '12, GIDC Estate, Ahmedabad',
    'Gujarat',
    '24',
    'India',
    '382481',
    'Seeded test client for quotations and GST invoices.'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM clients
    WHERE company_name = 'Dummy Fabrication Works'
      AND phone = '9000001001'
);

INSERT INTO clients (
    company_name,
    contact_person,
    email,
    phone,
    gst_number,
    client_gst_number,
    address,
    state,
    state_code,
    country,
    pincode,
    notes
)
SELECT
    'Dummy Interior Studio',
    'Neha Shah',
    'dummy.interior@example.test',
    '9000001002',
    NULL,
    NULL,
    '44, Satellite Road, Ahmedabad',
    'Gujarat',
    '24',
    'India',
    '380015',
    'Seeded test client for bills without GST.'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM clients
    WHERE company_name = 'Dummy Interior Studio'
      AND phone = '9000001002'
);

INSERT INTO clients (
    company_name,
    contact_person,
    email,
    phone,
    gst_number,
    client_gst_number,
    address,
    state,
    state_code,
    country,
    pincode,
    notes
)
SELECT
    'Dummy Logistics Yard',
    'Imran Sheikh',
    'dummy.logistics@example.test',
    '9000001003',
    '27BBBBB2222B2Z2',
    '27BBBBB2222B2Z2',
    '88, MIDC Service Lane, Pune',
    'Maharashtra',
    '27',
    'India',
    '411019',
    'Seeded test client for challans and mixed status documents.'
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1
    FROM clients
    WHERE company_name = 'Dummy Logistics Yard'
      AND phone = '9000001003'
);

SET @client_fabrication = (
    SELECT id
    FROM clients
    WHERE company_name = 'Dummy Fabrication Works'
      AND phone = '9000001001'
    ORDER BY id DESC
    LIMIT 1
);

SET @client_interior = (
    SELECT id
    FROM clients
    WHERE company_name = 'Dummy Interior Studio'
      AND phone = '9000001002'
    ORDER BY id DESC
    LIMIT 1
);

SET @client_logistics = (
    SELECT id
    FROM clients
    WHERE company_name = 'Dummy Logistics Yard'
      AND phone = '9000001003'
    ORDER BY id DESC
    LIMIT 1
);

-- ---------------------------------------------------------------------
-- Cleanup previous seed run
-- ---------------------------------------------------------------------

DELETE FROM invoice_items
WHERE invoice_id IN (
    SELECT seeded_docs.id
    FROM (
        SELECT id
        FROM invoices
        WHERE invoice_number IN (
            'QT-DUMMY-2026-001',
            'BL-DUMMY-2026-001',
            'INV-DUMMY-2026-001',
            'CH-DUMMY-2026-001'
        )
    ) AS seeded_docs
);

DELETE FROM invoices
WHERE invoice_number IN (
    'QT-DUMMY-2026-001',
    'BL-DUMMY-2026-001',
    'INV-DUMMY-2026-001',
    'CH-DUMMY-2026-001'
);

-- ---------------------------------------------------------------------
-- Unified document flow: quotations, bills-no-gst, invoices, challans
-- These are the records used by the current create/manage document flow.
-- ---------------------------------------------------------------------

INSERT INTO invoices (
    invoice_number,
    document_type,
    client_id,
    source_document_id,
    invoice_date,
    due_date,
    subtotal,
    total_tax,
    cgst_amount,
    sgst_amount,
    igst_amount,
    discount,
    discount_type,
    grand_total,
    status,
    issuer_type,
    place_of_supply,
    state_code,
    is_interstate,
    reverse_charge,
    amount_received,
    notes,
    document_image,
    vehicle_number,
    transport_mode,
    lr_number,
    eway_bill_number
) VALUES
(
    'QT-DUMMY-2026-001',
    'quotation',
    @client_fabrication,
    NULL,
    DATE_SUB(@today, INTERVAL 6 DAY),
    DATE_ADD(@today, INTERVAL 24 DAY),
    33520.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    'flat',
    33520.00,
    'unpaid',
    'company',
    'Gujarat',
    '24',
    0,
    0,
    0.00,
    'Quotation seed for testing grouped line items, extra detail text, and extra billed lines.',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
),
(
    'BL-DUMMY-2026-001',
    'bill-no-gst',
    @client_interior,
    NULL,
    DATE_SUB(@today, INTERVAL 4 DAY),
    DATE_ADD(@today, INTERVAL 26 DAY),
    29000.00,
    0.00,
    0.00,
    0.00,
    0.00,
    1000.00,
    'flat',
    28000.00,
    'partially paid',
    'personal',
    'Gujarat',
    '24',
    0,
    0,
    8000.00,
    'Bill without GST seed for testing personal issuer mode and partial payment.',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
),
(
    'INV-DUMMY-2026-001',
    'invoice',
    @client_fabrication,
    NULL,
    DATE_SUB(@today, INTERVAL 1 DAY),
    DATE_ADD(@today, INTERVAL 29 DAY),
    60000.00,
    10800.00,
    5400.00,
    5400.00,
    0.00,
    3000.00,
    'flat',
    67800.00,
    'partially paid',
    'company',
    'Gujarat',
    '24',
    0,
    0,
    25000.00,
    'GST invoice seed linked to a legacy quotation for conversion testing.',
    NULL,
    NULL,
    NULL,
    NULL,
    NULL
),
(
    'CH-DUMMY-2026-001',
    'challan',
    @client_logistics,
    NULL,
    @today,
    DATE_ADD(@today, INTERVAL 7 DAY),
    16300.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    'flat',
    16300.00,
    'unpaid',
    'company',
    'Maharashtra',
    '27',
    0,
    0,
    0.00,
    'Unified challan seed for testing transport details in the active flow.',
    NULL,
    'GJ01CD9911',
    'Truck',
    'LR-DUMMY-01',
    'EWB-DUMMY-01'
);

SET @doc_quote = (
    SELECT id FROM invoices WHERE invoice_number = 'QT-DUMMY-2026-001' LIMIT 1
);
SET @doc_bill = (
    SELECT id FROM invoices WHERE invoice_number = 'BL-DUMMY-2026-001' LIMIT 1
);
SET @doc_invoice = (
    SELECT id FROM invoices WHERE invoice_number = 'INV-DUMMY-2026-001' LIMIT 1
);
SET @doc_challan = (
    SELECT id FROM invoices WHERE invoice_number = 'CH-DUMMY-2026-001' LIMIT 1
);

INSERT INTO invoice_items (
    invoice_id,
    description,
    hsn_code,
    specifications,
    item_date,
    quantity,
    unit,
    unit_price,
    tax_rate,
    tax_amount,
    cgst_rate,
    cgst_amount,
    sgst_rate,
    sgst_amount,
    igst_rate,
    igst_amount,
    line_total,
    image_url,
    item_order
) VALUES
(
    @doc_quote,
    'MS Laser Cut Panel - 4mm',
    '7308',
    '{"description_extra":"Powder coated and ready for site delivery","sub_items":[{"description":"Gray powder coating","quantity":8,"unit":"Nos","price":180}]}',
    NULL,
    8.00,
    'Nos',
    3200.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    27040.00,
    NULL,
    0
),
(
    @doc_quote,
    'Bracket Set',
    '7308',
    '{"description_extra":"With primer coat and matching hardware"}',
    NULL,
    12.00,
    'Nos',
    540.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    6480.00,
    NULL,
    1
),
(
    @doc_bill,
    'Custom SS Counter Frame',
    NULL,
    '{"description_extra":"Mirror finish with rounded edge corners","sub_items":[{"description":"On-site fitting support","quantity":2,"unit":"Visit","price":750}]}',
    NULL,
    2.00,
    'Nos',
    12500.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    26500.00,
    NULL,
    0
),
(
    @doc_bill,
    'Transport and local unloading',
    NULL,
    '{"description_extra":"Single vehicle trip within city limits"}',
    NULL,
    1.00,
    'Job',
    2500.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    2500.00,
    NULL,
    1
),
(
    @doc_invoice,
    'MS Staircase Fabrication',
    '7308',
    '{"description_extra":"Includes landing platform and railing brackets"}',
    NULL,
    1.00,
    'Job',
    42000.00,
    18.00,
    7560.00,
    9.00,
    3780.00,
    9.00,
    3780.00,
    0.00,
    0.00,
    49560.00,
    NULL,
    0
),
(
    @doc_invoice,
    'Site installation and welding',
    '9986',
    '{"description_extra":"2 technicians, machine setup, and finishing touch-up"}',
    NULL,
    1.00,
    'Job',
    18000.00,
    18.00,
    3240.00,
    9.00,
    1620.00,
    9.00,
    1620.00,
    0.00,
    0.00,
    21240.00,
    NULL,
    1
),
(
    @doc_challan,
    'Mild steel base frames',
    NULL,
    '{"description_extra":"Dispatch as packed bundles","item_date":"2026-04-21"}',
    '2026-04-21',
    3.00,
    'Nos',
    4500.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    13500.00,
    NULL,
    0
),
(
    @doc_challan,
    'Clamp and anchor kit',
    NULL,
    '{"description_extra":"Packed separately with installation markings","item_date":"2026-04-21"}',
    '2026-04-21',
    1.00,
    'Set',
    2800.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    0.00,
    2800.00,
    NULL,
    1
);

UPDATE invoices
SET source_document_id = @doc_quote
WHERE id = @doc_invoice;

COMMIT;

-- Quick check queries after import:
-- SELECT invoice_number, document_type, status, grand_total FROM invoices WHERE invoice_number LIKE '%DUMMY%';
