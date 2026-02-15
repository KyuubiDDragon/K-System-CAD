# Invoice Management

Basic invoice tracking system for managing incoming and outgoing invoices with customer information and line items.

## Overview

Features:
- Invoice creation with line items
- Customer linking (companies or persons)
- Status tracking (delivered, sent, paid)
- File attachments
- Search functionality
- Authority-specific data isolation

**Required Permission:** `READ_INVOICE` (view), `WRITE_INVOICE` (create/edit), `DELETE_INVOICE` (delete)

## Accessing Invoices

**Route:** `/invoice`

**How to Access:**
- Desktop: Double-click **Invoices** icon
- Menu: Start Menu → Finance → Invoices
- Or navigate directly to `/invoice`

## Interface Layout

### Invoice Table

**Columns:**
- **Type** - Green "Income" chip (outgoing=true) or Red "Expense" chip (outgoing=false)
- **Subject** - Invoice title
- **Customer** - Customer name
- **Delivered** - Delivery date (chip) or "-"
- **Sent** - Sent date (blue chip) or "-"
- **Paid** - Payment date (green chip) or "-"
- **Actions** - View, Edit, Delete buttons

**Toolbar:**
- Search field (filters by customer name or invoice title)
- **New Invoice** button

**Pagination:**
- 25 items per page
- Sorted by ID descending (newest first)

## Creating Invoices

### New Invoice

1. Click **New Invoice** button
2. Form dialog opens with three sections

**Section 1: Customer Selection**
- **Company Autocomplete** - Select from existing companies
- **Person Autocomplete** - Select from existing persons
- **Load Data Button** - Auto-fills customer contact information
- Invoice links to selected company OR person (mutually exclusive)

**Section 2: Status Toggles**
- **Delivered** - Checkbox (marks invoice as delivered)
- **Sent** - Checkbox (marks invoice as sent)
- **Paid** - Checkbox (marks invoice as paid)

**Section 3: Transaction Type**
- **Dropdown** - Select "Income" (outgoing=1) or "Expense" (outgoing=0)

**Section 4: Basic Information**
- **Title*** (required) - Invoice subject
- **Customer** - Customer name (auto-filled or manual)
- **Phone Number** - Customer phone
- **Email** - Customer email
- **Account** - Bank account information
- **Description** - Multi-line text description

**Section 5: Line Items**
- **Item Selector** - Dropdown of predefined items
- **Description** - Item description (auto-filled from selection)
- **Price** - Unit price
- **Quantity** - Quantity (default: 1)
- **Add Item** - Blue "+" button to add more items
- **Remove Item** - Red "X" button on each item

**Section 6: Additional Options**
- **Discount** - Numeric discount value
- **Attachments** - Multiple file upload

3. Fill in required fields (minimum: title)
4. Add line items as needed
5. Click **Save**
6. Invoice appears in table

## Editing Invoices

### Edit Invoice

1. Click **Edit** icon (pencil) on invoice row
2. Edit dialog opens with all current data
3. Modify any fields across all sections
4. Add/remove line items
5. Add new attachments or remove existing ones
6. Click **Save**
7. Changes applied immediately

## Viewing Invoices

### View Invoice

1. Click **View** icon (eye) on invoice row
2. View-only dialog displays all information
3. Shows linked company/person if applicable
4. Displays all line items
5. Shows all attachments
6. Close when done

## Deleting Invoices

### Delete Invoice

1. Click **Delete** icon (trash) on invoice row
2. Confirm deletion in dialog
3. Warning about permanent deletion displayed
4. Invoice removed from database

**Warning:** Deletion is permanent.

**Permission Required:** `DELETE_INVOICE`

## Invoice Status

**Status Indicators:**
- **Delivered** - Invoice has been delivered to customer
- **Sent** - Invoice has been sent
- **Paid** - Invoice has been paid

**Status Display:**
- Date chips show when status was set
- Dates formatted as DD.MM.YYYY
- Empty status shows "-"

**Note:** Status uses boolean toggles, not direct date editing in form.

## Customer Linking

**Company Linking:**
1. Select company from autocomplete
2. Click "Load Data" button
3. Customer name, phone, email auto-fill from company record
4. Invoice linked to company (linked_company field set)

**Person Linking:**
1. Select person from autocomplete
2. Click "Load Data" button
3. Customer information auto-fills from person record
4. Invoice linked to person (linked_person field set)

**Mutual Exclusivity:**
- Invoice can link to company OR person, not both
- Linking helps track invoices per customer

## Line Items

**Item Management:**
1. Click "Add Item" to add new line item
2. Select from predefined invoice items dropdown (optional)
3. Selecting predefined item auto-fills description and price
4. Or manually enter description and price
5. Set quantity (default: 1)
6. Click "X" to remove item

**Item Fields:**
- **Item Selector** - Optional dropdown for predefined items
- **Description** - Item description
- **Price** - Unit price
- **Quantity** - Number of units

**Multiple Items:**
- Add as many line items as needed
- Each item on separate card
- Remove unwanted items before saving

## File Attachments

**Upload Attachments:**
- Multiple file upload support
- Allowed types: jpg, jpeg, png, pdf, docx, doc, txt
- Files stored in authority-specific folder

**Manage Attachments:**
- Add new attachments when editing
- Remove existing attachments
- Attachments submitted with invoice data

## Search and Filter

### Search Functionality

**Search Bar:**
- Located in toolbar
- Filters by customer name or invoice title
- Real-time filtering
- Case-insensitive matching

**Behavior:**
- Type to filter invoice list
- Shows matching invoices
- Clear search to see all

## Data Model

**Invoice Record Fields:**
- `id` - Unique identifier
- `title` - Invoice subject (required)
- `customer` - Customer name
- `phone_number` - Phone number
- `email` - Email address
- `account` - Bank account info
- `description` - Additional description
- `is_sent` - Sent status (boolean)
- `is_paid` - Paid status (boolean)
- `is_delivered` - Delivered status (boolean)
- `is_sent_date` - Date when sent
- `is_paid_date` - Date when paid
- `is_delivered_date` - Date when delivered
- `outgoing` - Income (true) or Expense (false)
- `discount` - Discount value
- `linked_person` - Person ID if linked
- `linked_company` - Company ID if linked
- `items` - Array of line items
- `authority_id` - Organization ID

**Line Item Fields:**
- `id` - Item ID
- `invoice_id` - Parent invoice
- `item_name` - Predefined item name
- `description` - Item description
- `price` - Unit price
- `quantity` - Quantity

## Multi-Tenant Security

**Authority Isolation:**
- All invoices scoped to `authority_id`
- Users only see invoices from their organization
- File uploads in authority-specific directories
- Company/person dropdowns filtered by authority

## Permissions

**Permission Levels:**
- `READ_INVOICE` - View invoice list and details
- `WRITE_INVOICE` - Create and edit invoices
- `DELETE_INVOICE` - Delete invoices
- `ALL` - Full access

## Limitations

**What's NOT Available:**

### Financial Features
- ❌ Tax/VAT calculation
- ❌ Tax configuration
- ❌ Automatic invoice numbering
- ❌ Payment tracking beyond paid/unpaid
- ❌ Partial payment support
- ❌ Payment method tracking
- ❌ Currency selection
- ❌ Multi-currency support

### Document Features
- ❌ PDF generation
- ❌ PDF export
- ❌ Customizable invoice templates
- ❌ Email sending from system
- ❌ Invoice preview before saving

### Advanced Features
- ❌ Recurring invoices
- ❌ Subscription invoices
- ❌ Credit notes
- ❌ Refunds
- ❌ Invoice versioning
- ❌ Revision history
- ❌ Approval workflows
- ❌ Late payment reminders
- ❌ Reporting or analytics
- ❌ Due date tracking
- ❌ Accounting system integration

### UI Limitations
- ❌ Dates not directly editable in form (only toggle boolean)
- ❌ Bulk operations
- ❌ Invoice duplication/cloning
- ❌ Draft saving
- ❌ Cannot reorder line items
- ❌ No invoice total display in table
- ❌ No validation for total calculations
- ❌ Attachments can't be previewed before upload
- ❌ Search limited to customer and title (no date range, amount, status)

### Status Features
- ❌ Invoice status workflow (only boolean toggles)
- ❌ Overdue detection
- ❌ Payment terms
- ❌ Automatic status updates

**Current Reality:**
This is a **simple invoice tracker** with line items and file attachments. It is not a comprehensive invoicing or accounting system.

## Use Cases

**Invoice Tracking:**
- Record outgoing invoices (income)
- Record incoming invoices (expenses)
- Track invoice status

**Customer Management:**
- Link invoices to companies
- Link invoices to persons
- Track invoices per customer

**Line Item Detail:**
- Itemize invoice details
- Track quantities and prices
- Use predefined items for consistency

**Document Storage:**
- Attach invoice PDFs
- Store supporting documents
- Keep invoice-related files together

## Tips & Best Practices

**Creating Invoices:**
1. Always fill in title (required)
2. Link to company or person for better tracking
3. Use "Load Data" to auto-fill customer information
4. Set transaction type (Income/Expense) correctly

**Line Items:**
1. Use predefined items when available
2. Be specific in item descriptions
3. Verify quantities and prices
4. Add all items before saving

**Status Management:**
1. Update delivered status when invoice sent
2. Mark as sent when actually sent
3. Mark as paid when payment received
4. Use date chips to verify when status was set

**Attachments:**
1. Attach invoice PDF if available
2. Include supporting documents
3. Keep file sizes reasonable
4. Use supported file types only

## Troubleshooting

### Can't Create Invoice

**Problem:** New Invoice button doesn't work

**Solutions:**
1. Verify `WRITE_INVOICE` permission
2. Check title field is filled
3. Refresh page
4. Check browser console for errors

### Customer Data Not Loading

**Problem:** Load Data button doesn't fill customer info

**Solutions:**
1. Verify company or person is selected first
2. Check company/person has contact information
3. Try selecting again
4. Verify company/person exists in system

### Line Item Not Saving

**Problem:** Added items disappear

**Solutions:**
1. Verify you filled description and price
2. Check you clicked Save on invoice dialog
3. Ensure no validation errors
4. Try adding item again

### Status Not Updating

**Problem:** Status toggles don't save

**Solutions:**
1. Check you have `WRITE_INVOICE` permission
2. Verify you clicked Save button
3. Check internet connection
4. Look for error messages

### Attachments Not Uploading

**Problem:** Files won't attach

**Solutions:**
1. Check file type is supported (jpg, jpeg, png, pdf, docx, doc, txt)
2. Verify file size is reasonable
3. Check internet connection
4. Try different file
5. Check browser console for errors

### Search Not Working

**Problem:** Search doesn't filter

**Solutions:**
1. Check spelling of customer or title
2. Try partial match
3. Clear search and try again
4. Refresh page

## Related Documentation

- [Getting Started](/guide/getting-started) - K-Systems basics
- [Companies](/guide/companies) - Company management
- [Person Files](/guide/person-files) - Person management

---

**Last Updated:** 2025-10-02
**Version:** 2.0.0 (Corrected to match actual implementation)
