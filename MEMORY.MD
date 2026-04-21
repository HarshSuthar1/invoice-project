# Invoice Management System - Project Memory

## Project Overview
**Business Name:** GS Metal Concept  
**Industry:** Metal Fabrication Work  
**Owner:** Your Dad  
**Developer:** You (Harsh Suthar)  
**Tech Stack:** PHP, MySQL/MariaDB, JavaScript (ES6 Modules), HTML5, CSS3  
**Server:** XAMPP (localhost)  
**Project Path:** `C:\xampp\htdocs\Business project\`

---

## Business Context
- **Current Process:** Manual Excel-based quotations and invoicing
- **Pain Point:** Time-consuming, error-prone manual calculations
- **Goal:** Automate document creation with proper GST compliance
- **Key Requirement:** Support both GST and non-GST invoices

---

## System Architecture

### Database Schema
**Database Name:** `invoice_system`  
**MariaDB Version:** 10.4.32

#### Core Tables:
1. **users** - User authentication and management
2. **clients** - Customer information with GST details
3. **company_profile** - Business profile (GST: 24BIDPS5550H1Z7)
4. **invoices** - Tax invoices with GST
5. **invoice_items** - Line items for invoices
6. **quotations** - Price estimates (Prefix: QT-)
7. **quotation_items** - Line items for quotations
8. **bills** - Bills without GST (Prefix: BL-)
9. **bill_items** - Line items for bills
10. **challans** - Transport/delivery challans (Prefix: CH-)
11. **challan_items** - Line items for challans
12. **hsn_codes** - HSN codes with GST rates (25+ metal fabrication codes)

### Document Prefixes:
- **QT-XXXX** - Quotations
- **BL-XXXX** - Bills (No GST)
- **INV-XX-XXXX** - Tax Invoices (Financial Year Based: INV-25-0001)
- **CH-XXXX** - Transport Challans

---

## Project Structure

```
Business project/
├── api/
│   ├── auth/
│   │   ├── login_user.php
│   │   ├── register_user.php
│   │   └── logout.php
│   ├── clients/
│   │   ├── get_clients.php
│   │   └── save_clients.php
│   ├── dashboard/
│   │   └── get_dashboard_data.php
│   ├── documents/
│   │   ├── get_documents.php
│   │   ├── get_next_number.php
│   │   ├── get_document_details.php
│   │   └── save_document.php
│   ├── invoices/
│   │   ├── get_invoices.php
│   │   ├── get_invoice_details.php
│   │   ├── save_invoice.php
│   │   └── update_invoice.php
│   ├── profile/
│   │   ├── get_profile.php
│   │   └── save_profile.php
│   └── reports/
│       └── get_reports_data.php
├── app/
│   ├── core/
│   │   ├── Auth.php
│   │   ├── ApiAuth.php
│   │   └── ApiResponse.php
│   ├── routes/
│   │   └── web.php
│   ├── views/
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   └── signin.php
│   │   ├── create-hub.php        ← NEW
│   │   ├── create-document.php   ← NEW
│   │   ├── dashboard.php
│   │   ├── clients.php
│   │   ├── manage_invoice.php
│   │   ├── reports.php
│   │   └── profile.php
│   └── bootstrap.php
├── assets/
│   ├── css/
│   │   ├── app.css (main import file)
│   │   ├── base/
│   │   │   └── base_styling.css
│   │   ├── components/
│   │   │   ├── buttons.css
│   │   │   ├── forms.css
│   │   │   ├── modal.css
│   │   │   ├── toasts.css
│   │   │   └── utilities.css
│   │   └── pages/
│   │       ├── dashboard.css
│   │       ├── clients.css
│   │       ├── invoice.css
│   │       ├── manage_invoice.css
│   │       ├── profile.css
│   │       ├── reports.css
│   │       ├── login.css
│   │       ├── signin.css
│   │       ├── create-hub.css     ← NEW
│   │       └── create-document.css ← NEW
│   └── js/
│       ├── main.js
│       ├── core/
│       │   ├── api.js (apiFetch wrapper)
│       │   ├── dom.js (qs, qsa, on)
│       │   ├── ui.js (modals, toasts)
│       │   ├── utils.js (formatCurrency, debounce)
│       │   ├── validators.js (required, isEmail, isGST)
│       │   └── data/
│       │       ├── clients.js
│       │       ├── invoice.js
│       │       ├── dashboard.js
│       │       └── reports.js
│       └── pages/
│           ├── dashboard.js
│           ├── clients.js
│           ├── invoice.js
│           ├── manage_documents_cards.js
│           ├── profile.js
│           ├── reports.js
│           ├── login.js
│           ├── signin.js
├── includes/
│   ├── config.php
│   └── sidebar.php
└── public/
    └── index.php
```

---

## Key Features Implemented

### ✅ Authentication System
- User registration with password hashing
- Session-based login/logout
- Protected routes with Auth::requireLogin()

### ✅ Client Management
- CRUD operations for clients
- GST number validation
- Search and filter functionality

### ✅ Multi-Document System (NEW)
- **Create Hub:** Central place to select document type
- **Unified Creation:** One form handles all document types
- **Document Types:**
  1. Quotation (estimates for clients)
  2. Bill without GST (simple bills)
  3. Invoice with GST (tax invoices)
  4. Transport Challan (delivery notes)

### ✅ Invoice Management
- Financial year-based numbering (INV-25-0001)
- Dynamic item rows with auto-calculation
- GST breakdown (CGST/SGST/IGST)
- Status tracking (Paid/Unpaid/Partially Paid)
- Import from quotations/bills

### ✅ Dashboard
- Total outstanding amount
- Total received amount
- Unpaid invoices count
- Recent invoices table
- Payment status chart (Chart.js)

### ✅ Reports
- Total revenue invoiced
- Total amount received
- Collection rate percentage
- Days sales outstanding
- Active clients count
- Monthly revenue trend
- Top paying clients
- Payment performance by client
- Outstanding by client
- Aging report (0-30, 31-60, 61-90, 90+ days)

### ✅ Company Profile
- GST registration details
- Bank account information
- Invoice preferences (prefix, due days)
- Terms & conditions templates
- Payment instructions
- Real-time preview

---

## Technical Patterns

### API Response Format
```json
{
  "success": true/false,
  "message": "Optional message",
  "data": { ... }
}
```

### Error Handling
- Try-catch blocks in all async operations
- User-friendly error messages via showError()
- API errors logged to console
- Database transactions for multi-step operations

### Data Caching
- Client-side caching for frequently accessed data
- Cache invalidation on mutations
- Reduces unnecessary API calls

### Code Organization
- **Separation of Concerns:** Core utilities, data layer, UI layer
- **ES6 Modules:** Import/export for modularity
- **Reusable Components:** Shared UI functions (modals, toasts)
- **Data Normalization:** Consistent API response handling

---

## Recent Changes (Latest Session)

### 1. Document Creation Hub
**Created:** Unified document creation system
- Landing page with 4 document type cards
- Conditional form fields based on document type
- Tax columns show/hide automatically
- Import functionality for invoices

### 2. New Files Created
- `app/views/create-hub.php`
- `app/views/create-document.php`
- `assets/css/pages/create-hub.css`
- `assets/css/pages/create-document.css`

### 3. Database Migration
- Created 8 new tables for document management
- MariaDB-compatible SQL (no syntax errors)
- Foreign key constraints properly configured

### 4. API Endpoints
- `/api/documents/get_documents.php`
- `/api/documents/get_next_number.php`
- `/api/documents/get_document_details.php`
- `/api/documents/save_document.php`

### 5. Routes Updated
- Added `create-hub` route
- Added `create-document` route
- Redirect old `invoice` route to `create-hub`
- Updated sidebar navigation

---

## Business Logic

### Invoice Numbering
- **Format:** INV-[YY]-[0001]
- **Example:** INV-25-0001 (for FY 2025-26)
- **Logic:** Financial year starts April 1st
  - Jan-Mar: Use previous year (Jan 2026 = FY 2025)
  - Apr-Dec: Use current year (Apr 2025 = FY 2025)

### GST Calculation
- **Same State (Gujarat = 24):** CGST 9% + SGST 9% = 18%
- **Other State:** IGST 18%
- **HSN Codes:** Pre-populated with metal fabrication codes
- **Tax Rate:** Auto-filled from HSN code selection

### Document Workflow
1. **Quotation** → Sent to client → Approved/Rejected
2. **Approved Quotation** → Convert to Invoice (with GST) or Bill (no GST)
3. **Invoice** → Payment tracking → Status updates
4. **Challan** → Delivery tracking → Vehicle/driver details

---

## Environment Configuration

### Database Connection
```php
$host = 'localhost';
$dbname = 'invoice_system';
$username = 'root';
$password = '';
```

### Base Path
```javascript
const BASE_PATH = '/Business%20project';
```

### Session Configuration
- Session started in `bootstrap.php`
- User ID stored in `$_SESSION['user_id']`
- Auth check: `Auth::check()`

---

## Testing Checklist

### ✅ Completed Tests
- [x] User registration and login
- [x] Client CRUD operations
- [x] Invoice creation with GST
- [x] Dashboard data loading
- [x] Reports generation
- [x] Profile management
- [x] Database migration (MariaDB compatible)

### 🔄 In Progress
- [ ] Create hub navigation
- [ ] Document creation (all types)
- [ ] Import functionality
- [ ] PDF generation

### 📋 Pending Tests
- [ ] Quotation to invoice conversion
- [ ] Multi-document management
- [ ] Email/WhatsApp integration
- [ ] Production deployment

---

## Next Steps

### Immediate Tasks
1. ✅ Fix create-hub routing issue
2. ⏳ Test document creation for all types
3. ⏳ Implement PDF templates
4. ⏳ Add import functionality

### Short-term Goals (Week 1-2)
- Complete document management system
- Test with real client data
- Train your dad on the system
- Add WhatsApp sharing feature

### Long-term Goals (Month 1-3)
- Email automation
- Quotation follow-up reminders
- Client portal (view invoices online)
- Mobile app version
- Backup automation

---

## Important Notes

### Security
- All API endpoints protected with `ApiAuth::requireLogin()`
- SQL injection prevention with prepared statements
- Password hashing with `password_hash()`
- XSS prevention with `htmlspecialchars()`

### Performance
- Client-side caching reduces API calls
- Database indexes on frequently queried columns
- Lazy loading for large datasets
- Debounced search inputs

### Browser Compatibility
- Modern browsers (Chrome, Firefox, Edge, Safari)
- ES6 modules support required
- LocalStorage for user preferences
- SessionStorage for temporary data

---

## Contact & Support
- **Developer:** Harsh Suthar
- **Email:** harshsuthar17086@gmail.com
- **Project Type:** Business Management System
- **Started:** January 2025
- **Current Status:** Active Development

---

## Version History

### v1.0 (Initial Release)
- Basic invoice creation
- Client management
- Dashboard with stats

### v1.1 (Current)
- Multi-document system (quotations, bills, challans)
- Import functionality
- Enhanced reports
- Company profile management
- Financial year-based invoice numbering

---

## Quick Commands

### Start Development Server
```bash
# XAMPP Control Panel
# Start Apache
# Start MySQL
```

### Access Application
```
http://localhost/Business%20project/public/index.php
```

### Database Management
```
http://localhost/phpmyadmin
```

### Clear Cache
```
Ctrl + F5 (Hard refresh)
```

---

*Last Updated: January 26, 2025*
