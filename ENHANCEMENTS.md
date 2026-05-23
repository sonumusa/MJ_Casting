# MJ Casting - Enhancement Summary

## New Features Added

### 1. Material Design Theme
- **Dark luxury gold theme** enhanced with Material Design principles
- **Elevation shadows** (5 levels) on cards, buttons, and panels
- **Ripple-style buttons** with hover lift effects
- **Animated page transitions** (slide-up animations)
- **Floating action button (FAB)** styling ready
- **Improved typography** with better hierarchy and spacing
- **Gradient accents** on primary buttons and stat cards
- **Responsive sidebar** with active state indicators

### 2. Three Invoice Types
- **Customer Invoice** (گاہک بل)
- **Dukandar Invoice** (دوکاندار بل)
- **Karigar Invoice** (کاریگر بل)
- Each type is color-coded with chips/badges
- Filterable lists by type
- Same ratti-based conversion formula applies to all

### 3. Gold Received in Invoice (Dynamic Rows)
Inside any casting invoice, you can now add **multiple rows** of gold received from the party:
- **Description** (e.g., Old chain, impure bar)
- **Gross Weight** (کچا وزن)
- **Ratti Impurity** (رتی نقص)
- **Auto-calculated Khalis** (خالص سونا)

Formula applied live:
```
Khalis = Gross - (Gross / 96 × Ratti)
```
Example: 100g with 11 ratti impurity → 88.541g khalis

The total received khalis is automatically **deducted from the customer's balance**.

### 4. Separate Gold Receive Voucher
A brand new module: **Gold Receipts / سونا وصولی رسید**
- Independent list, create, edit, show, print
- Supports all 3 party types (Customer, Dukandar, Karigar)
- Multiple dynamic rows per receipt with the same ratti conversion
- Updates inventory automatically
- Print-friendly A4 receipt with signatures

### 5. Inventory in Pure Khalis Gold
Inventory now tracks only **pure khalis gold**:
- **Opening Balance** (manual)
- **+ Total Received Khalis** (from both invoice receives + separate receipts)
- **- Total Given** (sum of `effective_gold` from active invoices)
- **= Closing Balance** (auto-calculated)

All impure gold is converted to khalis before hitting inventory.

### 6. Party Types for Customers
The customer list now supports 3 party types:
- **Customer** (گاہک) - Blue chip
- **Dukandar** (دوکاندار) - Amber chip
- **Karigar** (کاریگر) - Green chip

You can filter the party list by type.

---

## Files Changed / Created

### Migrations (New)
- `database/migrations/2026_05_23_000001_add_party_type_to_customers_table.php`
- `database/migrations/2026_05_23_000002_add_invoice_type_to_invoices_table.php`
- `database/migrations/2026_05_23_000003_create_invoice_receives_table.php`
- `database/migrations/2026_05_23_000004_create_gold_receipts_table.php`
- `database/migrations/2026_05_23_000005_create_gold_receipt_items_table.php`

### Models
- `app/Models/Customer.php` - Added `party_type`, `goldReceipts()` relation, `getPartyTypeLabelAttribute()`
- `app/Models/Invoice.php` - Added `invoice_type`, `total_received_khalis`, `receives()` relation
- `app/Models/InvoiceReceive.php` - **NEW**
- `app/Models/GoldReceipt.php` - **NEW**
- `app/Models/GoldReceiptItem.php` - **NEW**

### Services
- `app/Services/GoldCalculationService.php` - Added `convertToKhalis()` and updated balance calculation to subtract `totalReceivedKhalis`
- `resources/js/calculations.js` - Added `convertToKhalis()` to client-side calculator, updated `remainingBalance` and `calculateAll`

### Controllers
- `app/Http/Controllers/InvoiceController.php` - Handles invoice receives (create/update/delete), type filtering, updated print/export
- `app/Http/Controllers/GoldReceiptController.php` - **NEW** Full CRUD + print for receipts
- `app/Http/Controllers/CustomerController.php` - Added `party_type` filtering and validation
- `app/Http/Controllers/InventoryController.php` - Now includes `GoldReceipt::sum('total_khalis_weight')` + invoice receives

### Routes
- `routes/web.php` - Added all gold-receipt routes

### Views (Theme + Functionality)
- `resources/views/layouts/app.blade.php` - Material Design theme overhaul
- `resources/views/pages/invoices/create.blade.php` - Dynamic receive rows + type selector + live panel
- `resources/views/pages/invoices/edit.blade.php` - Edit receives + type
- `resources/views/pages/invoices/index.blade.php` - Type filter + received column + summary bar
- `resources/views/pages/invoices/show.blade.php` - Shows receive rows + balance summary
- `resources/views/pages/invoices/print.blade.php` - Updated with receives and type badge
- `resources/views/pages/gold-receipts/create.blade.php` - **NEW**
- `resources/views/pages/gold-receipts/edit.blade.php` - **NEW**
- `resources/views/pages/gold-receipts/index.blade.php` - **NEW**
- `resources/views/pages/gold-receipts/show.blade.php` - **NEW**
- `resources/views/pages/gold-receipts/print.blade.php` - **NEW**
- `resources/views/pages/customers/index.blade.php` - Type filter + chips
- `resources/views/pages/customers/create.blade.php` - Type selector
- `resources/views/pages/customers/edit.blade.php` - Type selector
- `resources/views/pages/inventory/index.blade.php` - Shows receipt totals + formula display

---

## Deployment Steps

1. **Pull / copy** all changed files into your project.

2. **Run migrations** to add new columns and tables:
```bash
php artisan migrate
```

3. **Build assets** if you use Vite (optional, since we used inline styles):
```bash
npm run build
```

4. **Clear caches**:
```bash
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

5. **Set opening inventory** by visiting `/inventory` and entering your actual physical opening balance in khalis pure gold.

---

## How to Use

### Creating an Invoice with Received Gold
1. Go to **Invoices → New Invoice**
2. Select **Invoice Type** (Customer / Dukandar / Karigar)
3. Select the party
4. In the **Gold Received from Party** section, click **"Add Receive Row"**
5. Enter the item description, gross weight, and ratti impurity
6. The **Khalis** column auto-calculates using the formula
7. Fill the **casting calculation** as before
8. The **live panel** on the right shows:
   - Total Received Khalis (deducted from balance)
   - Casting output totals
   - Final remaining balance

### Creating a Separate Gold Receipt
1. Go to **Gold Receipts / سونا وصولی** from the sidebar
2. Click **New Receipt**
3. Select type and party
4. Add multiple rows of impure gold
5. Save and print the voucher

### Tracking Inventory
1. Go to **Inventory / مال**
2. Set your **Opening Balance** in pure khalis grams
3. The system automatically adds all receipts and subtracts all invoice effective gold
4. View your **Closing Balance** in real time

---

## Formula Reference

### Ratti Conversion (used everywhere)
```
Khalis Pure Gold = Gross Weight - (Gross Weight / 96 × Ratti Impurity)

Example:
  Gross = 100.000 g
  Ratti = 11.00
  Khalis = 100 - (100 / 96 × 11) = 100 - 11.458 = 88.542 g
```

### Balance Calculation (per invoice)
```
Remaining = Previous Balance
          + Effective Gold (this invoice output)
          - Wasooli (payment received)
          - Total Received Khalis (gold given by party in this invoice)
```

### Inventory
```
Closing = Opening Balance
        + Sum of all Receipt Khalis
        + Sum of all Invoice Receive Khalis
        - Sum of all Invoice Effective Gold (given out)
```

---

## Notes
- All database numbers remain in **grams** with 3 decimal precision
- The app assumes **1 tola = 96 ratti** as per local market convention
- If a customer gives **100% pure gold**, enter **Ratti = 0** and Khalis will equal Gross
- Receipts and invoices are **soft-deletable** (balance chains recalculate automatically)
- Print views are optimized for **A4 paper** with signature lines
