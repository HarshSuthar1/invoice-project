# Metal Fabrication Business - System Design Document

## Business Context
**Company:** GS Metal Concept
**Type:** Metal Fabrication Work
**Current Process:** Manual Excel-based quotations and invoicing
**Owner:** Your Dad

---

## Current Workflow Analysis

### Your Manual Process:
1. Client requests work → You create **Quotation** in Excel
2. Client approves → Convert to either:
   - **Bill WITHOUT GST** (simple format)
   - **Bill WITH GST** (detailed format with HSN, bank details, etc.)

### Key Documents Needed:
1. **Quotation/Estimate** - Initial price estimate
2. **Proforma Invoice** - Formal quotation before work
3. **Tax Invoice (With GST)** - Full GST bill with all details
4. **Bill of Supply (Without GST)** - Simple bill for non-GST customers
5. **Delivery Challan** - For material delivery tracking

---

## Recommended System Architecture

### Option 1: Document Type System (RECOMMENDED)
**Best for your business because:**
- Clean separation of quotations vs invoices
- Easy to convert quotation → invoice
- Proper GST compliance
- Industry-standard approach

### Option 2: Single Document with Stages
Less recommended because it gets messy with GST compliance.

---

## Database Schema Design

### New Tables to Add:

```sql
-- 1. QUOTATIONS TABLE (New)
CREATE TABLE quotations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  quotation_number VARCHAR(50) UNIQUE NOT NULL,
  client_id INT NOT NULL,
  quotation_date DATE NOT NULL,
  valid_until DATE NOT NULL,
  subtotal DECIMAL(10,2) DEFAULT 0,
  total_tax DECIMAL(10,2) DEFAULT 0,
  grand_total DECIMAL(10,2) DEFAULT 0,
  status ENUM('Draft', 'Sent', 'Approved', 'Rejected', 'Converted') DEFAULT 'Draft',
  notes TEXT,
  terms_conditions TEXT,
  converted_to_invoice_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (converted_to_invoice_id) REFERENCES invoices(id)
);

-- 2. QUOTATION ITEMS TABLE (New)
CREATE TABLE quotation_items (
  id INT PRIMARY KEY AUTO_INCREMENT,
  quotation_id INT NOT NULL,
  description TEXT NOT NULL,
  hsn_code VARCHAR(20),
  quantity DECIMAL(10,2) DEFAULT 1,
  unit VARCHAR(50) DEFAULT 'Nos',
  unit_price DECIMAL(10,2) DEFAULT 0,
  tax_rate DECIMAL(5,2) DEFAULT 0,
  line_total DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE
);

-- 3. UPDATE INVOICES TABLE (Modify existing)
ALTER TABLE invoices 
  ADD COLUMN document_type ENUM('Tax Invoice', 'Bill of Supply', 'Proforma Invoice') DEFAULT 'Tax Invoice',
  ADD COLUMN created_from_quotation_id INT DEFAULT NULL,
  ADD COLUMN place_of_supply VARCHAR(100),
  ADD COLUMN reverse_charge BOOLEAN DEFAULT FALSE,
  ADD COLUMN notes TEXT,
  ADD FOREIGN KEY (created_from_quotation_id) REFERENCES quotations(id);

-- 4. UPDATE INVOICE ITEMS TABLE (Modify existing)
ALTER TABLE invoice_items
  ADD COLUMN hsn_code VARCHAR(20),
  MODIFY COLUMN unit VARCHAR(50) DEFAULT 'Nos';

-- 5. HSN CODES MASTER TABLE (For metal fabrication)
CREATE TABLE hsn_codes (
  id INT PRIMARY KEY AUTO_INCREMENT,
  hsn_code VARCHAR(20) UNIQUE NOT NULL,
  description TEXT NOT NULL,
  gst_rate DECIMAL(5,2) NOT NULL,
  category VARCHAR(100),
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pre-populate common metal fabrication HSN codes
INSERT INTO hsn_codes (hsn_code, description, gst_rate, category) VALUES
('7308', 'Structures and parts of structures of iron or steel', 18.00, 'Structural Steel'),
('7326', 'Other articles of iron or steel', 18.00, 'Fabricated Metal'),
('7306', 'Tubes, pipes and hollow profiles of iron or steel', 18.00, 'Pipes & Tubes'),
('7216', 'Angles, shapes and sections of iron or steel', 18.00, 'Sections'),
('9987', 'Welding & Fabrication Services', 18.00, 'Services'),
('9988', 'Metal Cutting & Bending Services', 18.00, 'Services');
```

---

## User Interface Design

### New Pages to Add:

#### 1. **Quotations Page** (new)
```
/quotations
- List all quotations
- Create new quotation
- Edit quotation
- Send quotation to client (PDF)
- Convert quotation → Invoice
- Mark as Approved/Rejected
```

#### 2. **Enhanced Invoice Page** (modify existing)
```
/invoice
- Document Type selector (Tax Invoice / Bill of Supply)
- HSN Code dropdown for each item
- Auto-fill GST rates from HSN
- Show/hide GST details based on document type
- Option to create from existing quotation
```

#### 3. **Templates Management** (new)
```
/templates
- Manage PDF templates for:
  * Quotations
  * Tax Invoice (with GST)
  * Bill of Supply (without GST)
  * Delivery Challan
```

---

## Workflow Implementation

### Workflow 1: Quotation → Tax Invoice (WITH GST)

```
1. Client calls for price
   ↓
2. Create Quotation
   - Select client
   - Add items with HSN codes
   - System auto-calculates GST
   - Generate PDF quotation
   ↓
3. Send to client (email/WhatsApp)
   ↓
4. Client approves
   ↓
5. Convert to Tax Invoice
   - One-click conversion
   - All items copied
   - HSN codes included
   - Bank details shown
   - GST breakdown shown
   ↓
6. Generate GST Invoice PDF
   - Company GST number
   - Client GST number
   - HSN codes
   - Tax breakdown (CGST/SGST or IGST)
   - Bank details
   - Total in words
```

### Workflow 2: Direct Bill of Supply (NO GST)

```
1. Client doesn't need GST
   ↓
2. Create Invoice directly
   - Select "Bill of Supply" type
   - Add items (HSN optional)
   - No GST calculation
   - Simple format
   ↓
3. Generate Simple Bill PDF
   - No GST details
   - No tax breakdown
   - Basic bank details
   - Clean simple format
```

### Workflow 3: Quotation → Bill of Supply

```
1. Create Quotation (with prices)
   ↓
2. Client approves but wants no GST
   ↓
3. Convert to Bill of Supply
   - Copy items
   - Remove GST
   - Adjust prices if needed
   - Generate simple bill
```

---

## PDF Template Designs

### Tax Invoice Template (WITH GST)
```
┌─────────────────────────────────────────────────┐
│  GS METAL CONCEPT                    TAX INVOICE│
│  Address, GST No: 24BIDPS5550H1Z7               │
├─────────────────────────────────────────────────┤
│  Invoice No: INV-001      Date: 15-Jan-2026     │
│  Client: ABC Industries                         │
│  Client GST: 29ABCDE1234F1Z5                   │
│  Place of Supply: Gujarat (24)                  │
├─────────────────────────────────────────────────┤
│ Item Description    HSN  Qty Unit Rate    Amt   │
│ Steel Fabrication  7308   10  Kg  500   5,000   │
│                                   CGST 9%   450  │
│                                   SGST 9%   450  │
├─────────────────────────────────────────────────┤
│ Subtotal:                               5,000   │
│ CGST @ 9%:                                450   │
│ SGST @ 9%:                                450   │
│ Total:                                  5,900   │
│ (Amount in Words: Five Thousand Nine Hundred)  │
├─────────────────────────────────────────────────┤
│ BANK DETAILS:                                   │
│ Bank: SBI, Account: 1234567890                 │
│ IFSC: IBKL0000123                              │
├─────────────────────────────────────────────────┤
│ Terms & Conditions...                           │
│                                                 │
│                           Authorized Signature  │
└─────────────────────────────────────────────────┘
```

### Bill of Supply Template (NO GST)
```
┌─────────────────────────────────────────────────┐
│  GS METAL CONCEPT                  BILL OF SUPPLY│
│  Address                                         │
├─────────────────────────────────────────────────┤
│  Bill No: BILL-001        Date: 15-Jan-2026     │
│  Client: XYZ Construction                        │
│  Address: ...                                    │
├─────────────────────────────────────────────────┤
│ Item Description         Qty  Unit  Rate   Amt  │
│ Welding Work             10   Hrs   500   5,000 │
│ Material Supply          1    Lot  2,000  2,000 │
├─────────────────────────────────────────────────┤
│ Total Amount:                           7,000   │
│ (Amount in Words: Seven Thousand Only)          │
├─────────────────────────────────────────────────┤
│ Payment Details: ...                            │
│                                                 │
│                           Authorized Signature  │
└─────────────────────────────────────────────────┘
```

### Quotation Template
```
┌─────────────────────────────────────────────────┐
│  GS METAL CONCEPT                      QUOTATION │
│  Valid Until: 31-Jan-2026                        │
├─────────────────────────────────────────────────┤
│  Quotation No: QUO-001    Date: 15-Jan-2026     │
│  To: ABC Industries                              │
├─────────────────────────────────────────────────┤
│ Item Description         Qty  Unit  Rate   Amt  │
│ Steel Gate Fabrication   1    Set  15,000 15,000│
│ Installation Charges     1    Lot  2,000  2,000 │
│                                   GST@18%  3,060 │
├─────────────────────────────────────────────────┤
│ Total (Including GST):                  20,060  │
├─────────────────────────────────────────────────┤
│ Note: This is an estimate only.                 │
│ Final bill will be issued upon completion.      │
│                                                 │
│                           For GS Metal Concept  │
└─────────────────────────────────────────────────┘
```

---

## Implementation Priority

### Phase 1: Core Foundation (Week 1-2)
1. ✅ Update database schema
2. ✅ Add HSN codes master data
3. ✅ Modify invoice table to support document types
4. ✅ Update invoice items with HSN codes

### Phase 2: Quotation System (Week 3-4)
1. ✅ Create quotations page (list/create/edit)
2. ✅ Add quotation PDF generation
3. ✅ Implement quotation → invoice conversion
4. ✅ Email/WhatsApp integration for sending quotations

### Phase 3: Enhanced Invoicing (Week 5-6)
1. ✅ Update invoice creation page
2. ✅ Document type selection (Tax Invoice vs Bill of Supply)
3. ✅ HSN code integration
4. ✅ Conditional GST display logic
5. ✅ Enhanced PDF templates

### Phase 4: Reports & Analytics (Week 7-8)
1. ✅ Quotation conversion rate reports
2. ✅ GST vs Non-GST revenue split
3. ✅ Client-wise quotation history
4. ✅ Product/service wise analysis by HSN

---

## Key Features to Implement

### 1. Smart HSN Selection
```javascript
// Autocomplete HSN codes with descriptions
// Auto-fill GST rate based on HSN
// Recent HSN codes quick access
```

### 2. One-Click Conversion
```javascript
// Convert quotation to invoice in one click
// Preserve all item details
// Update quotation status automatically
```

### 3. Conditional Templates
```javascript
// IF document_type = 'Tax Invoice'
//   SHOW: GST breakdown, HSN codes, tax details
// ELSE IF document_type = 'Bill of Supply'
//   SHOW: Simple format, no GST
```

### 4. Client GST Validation
```javascript
// Auto-detect if client has GST number
// Suggest document type accordingly
// Validate GST number format
```

### 5. Place of Supply Logic
```javascript
// Auto-detect from client address
// CGST+SGST for same state
// IGST for other state
```

---

## Benefits for Your Dad's Business

✅ **Faster Quotations** - Create in minutes, not hours
✅ **Professional Look** - Consistent branded documents
✅ **Error Reduction** - Auto-calculations, no manual mistakes
✅ **Easy Conversion** - Quotation → Invoice with one click
✅ **GST Compliance** - All required details automatically included
✅ **Better Tracking** - Know which quotations converted to sales
✅ **Client History** - See all past quotations and invoices per client
✅ **Reports** - Understand business trends and top products

---

## Next Steps

1. **Review this design** with your dad
2. **Confirm workflows** match his actual process
3. **Prioritize features** - what's most urgent?
4. **Start Phase 1** - Database updates first

Would you like me to:
- Create the database migration SQL?
- Build the quotations page?
- Design the PDF templates?
- Start with a specific feature?

Let me know what you'd like to tackle first!