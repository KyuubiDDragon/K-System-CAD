# Invoice API

The Invoice API provides endpoints for managing invoices, invoice entries, and related company/person data in the K-Systems platform.

## Base URL

```
/backend/invoice/
```

## Authentication

All endpoints require a valid JWT token passed as a cookie. The token must contain valid `userId`, `authority`, and `authority_id` claims.

### Required Permissions

- **READ_INVOICE**: View invoices and invoice entries
- **WRITE_INVOICE**: Create and edit invoices, entries, and access related company/person data
- **DELETE_INVOICE**: Delete invoices and invoice entries

## Endpoints

### Get Invoices

**GET** `/backend/invoice/?action=getInvoices`

Retrieves all invoices for the authenticated user's authority.

**Required Permission:** `READ_INVOICE`

**Response (200):**
```json
{
  "invoices": [
    {
      "id": 1,
      "invoice_number": "INV-2025-001",
      "invoice_date": "2025-01-15",
      "due_date": "2025-02-15",
      "company_id": 5,
      "company_name": "Acme Corporation",
      "person_id": null,
      "person_name": null,
      "total_amount": 1250.00,
      "paid_amount": 500.00,
      "status": "partial",
      "notes": "Payment plan agreed",
      "created_at": "2025-01-15 10:30:00",
      "updated_at": "2025-01-20 14:22:00"
    }
  ]
}
```

### Get Invoice Entries

**GET** `/backend/invoice/?action=getInvoiceEntries&invoice_id={id}`

Retrieves all entries (line items) for a specific invoice.

**Required Permission:** `READ_INVOICE`

**Query Parameters:**
- `invoice_id` (required): ID of the invoice

**Response (200):**
```json
{
  "entries": [
    {
      "id": 1,
      "invoice_id": 1,
      "description": "Professional Services - January 2025",
      "quantity": 40,
      "unit_price": 25.00,
      "total": 1000.00,
      "created_at": "2025-01-15 10:30:00"
    },
    {
      "id": 2,
      "invoice_id": 1,
      "description": "Materials and Supplies",
      "quantity": 1,
      "unit_price": 250.00,
      "total": 250.00,
      "created_at": "2025-01-15 10:31:00"
    }
  ]
}
```

### Add Invoice

**POST** `/backend/invoice/?action=addInvoice`

Creates a new invoice.

**Required Permission:** `WRITE_INVOICE`

**Request Body:**
```json
{
  "invoice_number": "INV-2025-002",
  "invoice_date": "2025-01-20",
  "due_date": "2025-02-20",
  "company_id": 5,
  "person_id": null,
  "status": "unpaid",
  "notes": "Net 30 payment terms"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice created successfully",
  "invoice_id": 2
}
```

### Edit Invoice

**POST** `/backend/invoice/?action=editInvoice`

Updates an existing invoice.

**Required Permission:** `WRITE_INVOICE`

**Request Body:**
```json
{
  "id": 2,
  "invoice_number": "INV-2025-002",
  "invoice_date": "2025-01-20",
  "due_date": "2025-02-25",
  "company_id": 5,
  "person_id": null,
  "status": "paid",
  "notes": "Payment received on 2025-02-10"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice updated successfully"
}
```

### Delete Invoice

**POST** `/backend/invoice/?action=deleteInvoice`

Deletes an invoice and all its entries.

**Required Permission:** `DELETE_INVOICE`

**Request Body:**
```json
{
  "id": 2
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice deleted successfully"
}
```

### Add Invoice Entry

**POST** `/backend/invoice/?action=addInvoiceEntry`

Adds a new line item to an invoice.

**Required Permission:** `WRITE_INVOICE`

**Request Body:**
```json
{
  "invoice_id": 1,
  "description": "Additional Services",
  "quantity": 10,
  "unit_price": 50.00
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice entry created successfully",
  "entry_id": 3
}
```

### Edit Invoice Entry

**POST** `/backend/invoice/?action=editInvoiceEntry`

Updates an existing invoice entry.

**Required Permission:** `WRITE_INVOICE`

**Request Body:**
```json
{
  "id": 3,
  "invoice_id": 1,
  "description": "Additional Services - Updated",
  "quantity": 12,
  "unit_price": 45.00
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice entry updated successfully"
}
```

### Delete Invoice Entry

**POST** `/backend/invoice/?action=deleteInvoiceEntry`

Deletes an invoice entry.

**Required Permission:** `DELETE_INVOICE`

**Request Body:**
```json
{
  "id": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Invoice entry deleted successfully"
}
```

### Get Companies

**GET** `/backend/invoice/?action=getCompanies`

Retrieves all companies available for invoicing.

**Required Permission:** `WRITE_INVOICE`

**Response (200):**
```json
{
  "companies": [
    {
      "id": 5,
      "name": "Acme Corporation",
      "address": "123 Business St",
      "city": "New York",
      "postal_code": "10001",
      "country": "USA",
      "tax_id": "12-3456789"
    }
  ]
}
```

### Get Company Data

**GET** `/backend/invoice/?action=getCompanyData&company_id={id}`

Retrieves detailed data for a specific company.

**Required Permission:** `WRITE_INVOICE`

**Query Parameters:**
- `company_id` (required): ID of the company

**Response (200):**
```json
{
  "company": {
    "id": 5,
    "name": "Acme Corporation",
    "address": "123 Business St",
    "city": "New York",
    "state": "NY",
    "postal_code": "10001",
    "country": "USA",
    "tax_id": "12-3456789",
    "phone": "+1-212-555-0100",
    "email": "billing@example.com",
    "website": "www.example.com"
  }
}
```

### Get Persons

**GET** `/backend/invoice/?action=getPersons`

Retrieves all persons available for invoicing.

**Required Permission:** `WRITE_INVOICE`

**Response (200):**
```json
{
  "persons": [
    {
      "id": 10,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john.doe@example.com",
      "phone": "+1-555-0123",
      "address": "456 Main St",
      "city": "Boston",
      "postal_code": "02101"
    }
  ]
}
```

### Get Person Data

**GET** `/backend/invoice/?action=getPersonData&person_id={id}`

Retrieves detailed data for a specific person.

**Required Permission:** `WRITE_INVOICE`

**Query Parameters:**
- `person_id` (required): ID of the person

**Response (200):**
```json
{
  "person": {
    "id": 10,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@example.com",
    "phone": "+1-555-0123",
    "mobile": "+1-555-0124",
    "address": "456 Main St",
    "city": "Boston",
    "state": "MA",
    "postal_code": "02101",
    "country": "USA"
  }
}
```

## Invoice Status Values

The `status` field supports the following values:
- `unpaid`: Invoice has not been paid
- `partial`: Invoice has been partially paid
- `paid`: Invoice has been fully paid
- `overdue`: Invoice is past due date
- `cancelled`: Invoice has been cancelled

## Error Codes

| Code | Description |
|------|-------------|
| 400 | Bad request - Missing required parameters or invalid data |
| 403 | Forbidden - Insufficient permissions or invalid authority |
| 404 | Not found - Invoice or entry does not exist |
| 405 | Method not allowed - Wrong HTTP method used |
| 500 | Internal server error - Database or system error |

## File Attachments

Invoices support file attachments stored in the authority-specific upload directory:

**Upload Path:** `uploads/{authority}/invoices/`

Attachments can include:
- PDF invoices
- Supporting documents
- Payment receipts
- Contracts

## Multi-Tenant Isolation

All invoice operations are automatically scoped to the authenticated user's authority. Users can only access invoices belonging to their own authority.

## Examples

### JavaScript Example

```javascript
// Fetch all invoices
async function getInvoices() {
  const response = await fetch('/backend/invoice/?action=getInvoices', {
    method: 'GET',
    credentials: 'include', // Include cookies
    headers: {
      'Content-Type': 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }

  const data = await response.json();
  return data.invoices;
}

// Create a new invoice
async function createInvoice(invoiceData) {
  const response = await fetch('/backend/invoice/?action=addInvoice', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(invoiceData)
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to create invoice');
  }

  return data;
}

// Add entry to invoice
async function addInvoiceEntry(entryData) {
  const response = await fetch('/backend/invoice/?action=addInvoiceEntry', {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(entryData)
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || 'Failed to add invoice entry');
  }

  return data;
}
```

### cURL Examples

```bash
# Get all invoices
curl -X GET \
  'http://localhost:8080/backend/invoice/?action=getInvoices' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Create new invoice
curl -X POST \
  'http://localhost:8080/backend/invoice/?action=addInvoice' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "invoice_number": "INV-2025-003",
    "invoice_date": "2025-01-25",
    "due_date": "2025-02-25",
    "company_id": 5,
    "status": "unpaid",
    "notes": "Monthly service invoice"
  }'

# Add invoice entry
curl -X POST \
  'http://localhost:8080/backend/invoice/?action=addInvoiceEntry' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "invoice_id": 1,
    "description": "Consulting Services",
    "quantity": 20,
    "unit_price": 75.00
  }'

# Delete invoice
curl -X POST \
  'http://localhost:8080/backend/invoice/?action=deleteInvoice' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"id": 2}'
```

## Best Practices

1. **Invoice Numbering**: Use a consistent numbering scheme (e.g., INV-YYYY-XXX)
2. **Due Dates**: Always set realistic payment due dates
3. **Status Updates**: Keep invoice status up to date as payments are received
4. **Entry Details**: Provide clear descriptions for all invoice entries
5. **Company vs Person**: Choose appropriate recipient type based on business context
6. **Total Calculation**: Totals are calculated from entries (quantity × unit_price)
7. **Audit Trail**: All invoice changes are logged for compliance
8. **Attachments**: Store supporting documents in the designated upload directory

## Calculation Logic

Invoice totals are calculated as follows:

1. **Entry Total** = `quantity × unit_price`
2. **Invoice Total** = Sum of all entry totals
3. **Balance Due** = `total_amount - paid_amount`

## Database Tables

The Invoice API interacts with the following tables:

- `invoices`: Main invoice records
- `invoice_entries`: Individual line items
- `companies`: Company information for B2B invoices
- `persons`: Individual customer information for B2C invoices

All tables include `authority_id` for multi-tenant data isolation.
