# Rechnungs-API

Die Rechnungs-API bietet Endpunkte für die Verwaltung von Rechnungen, Rechnungspositionen und zugehörigen Firmen-/Personendaten in der K-Systems Plattform.

## Basis-URL

```
/backend/invoice/
```

## Authentifizierung

Alle Endpunkte erfordern ein gültiges JWT Token, das als Cookie übergeben wird. Das Token muss gültige `userId`, `authority` und `authority_id` Claims enthalten.

### Erforderliche Berechtigungen

- **READ_INVOICE**: Rechnungen und Rechnungspositionen anzeigen
- **WRITE_INVOICE**: Rechnungen, Positionen erstellen und bearbeiten sowie auf zugehörige Firmen-/Personendaten zugreifen
- **DELETE_INVOICE**: Rechnungen und Rechnungspositionen löschen

## Endpunkte

### Rechnungen abrufen

**GET** `/backend/invoice/?action=getInvoices`

Ruft alle Rechnungen für die Authority des authentifizierten Benutzers ab.

**Erforderliche Berechtigung:** `READ_INVOICE`

**Antwort (200):**
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

### Rechnungspositionen abrufen

**GET** `/backend/invoice/?action=getInvoiceEntries&invoice_id={id}`

Ruft alle Positionen (Einzelposten) für eine bestimmte Rechnung ab.

**Erforderliche Berechtigung:** `READ_INVOICE`

**Query-Parameter:**
- `invoice_id` (erforderlich): ID der Rechnung

**Antwort (200):**
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

### Rechnung hinzufügen

**POST** `/backend/invoice/?action=addInvoice`

Erstellt eine neue Rechnung.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

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

**Antwort (200):**
```json
{
  "success": true,
  "message": "Invoice created successfully",
  "invoice_id": 2
}
```

### Rechnung bearbeiten

**POST** `/backend/invoice/?action=editInvoice`

Aktualisiert eine bestehende Rechnung.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

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

**Antwort (200):**
```json
{
  "success": true,
  "message": "Invoice updated successfully"
}
```

### Rechnung löschen

**POST** `/backend/invoice/?action=deleteInvoice`

Löscht eine Rechnung und alle ihre Positionen.

**Erforderliche Berechtigung:** `DELETE_INVOICE`

**Request Body:**
```json
{
  "id": 2
}
```

**Antwort (200):**
```json
{
  "success": true,
  "message": "Invoice deleted successfully"
}
```

### Rechnungsposition hinzufügen

**POST** `/backend/invoice/?action=addInvoiceEntry`

Fügt einer Rechnung einen neuen Einzelposten hinzu.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

**Request Body:**
```json
{
  "invoice_id": 1,
  "description": "Additional Services",
  "quantity": 10,
  "unit_price": 50.00
}
```

**Antwort (200):**
```json
{
  "success": true,
  "message": "Invoice entry created successfully",
  "entry_id": 3
}
```

### Rechnungsposition bearbeiten

**POST** `/backend/invoice/?action=editInvoiceEntry`

Aktualisiert eine bestehende Rechnungsposition.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

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

**Antwort (200):**
```json
{
  "success": true,
  "message": "Invoice entry updated successfully"
}
```

### Rechnungsposition löschen

**POST** `/backend/invoice/?action=deleteInvoiceEntry`

Löscht eine Rechnungsposition.

**Erforderliche Berechtigung:** `DELETE_INVOICE`

**Request Body:**
```json
{
  "id": 3
}
```

**Antwort (200):**
```json
{
  "success": true,
  "message": "Invoice entry deleted successfully"
}
```

### Firmen abrufen

**GET** `/backend/invoice/?action=getCompanies`

Ruft alle für die Rechnungsstellung verfügbaren Firmen ab.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

**Antwort (200):**
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

### Firmendaten abrufen

**GET** `/backend/invoice/?action=getCompanyData&company_id={id}`

Ruft detaillierte Daten für eine bestimmte Firma ab.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

**Query-Parameter:**
- `company_id` (erforderlich): ID der Firma

**Antwort (200):**
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
    "email": "billing@acme.com",
    "website": "www.acme.com"
  }
}
```

### Personen abrufen

**GET** `/backend/invoice/?action=getPersons`

Ruft alle für die Rechnungsstellung verfügbaren Personen ab.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

**Antwort (200):**
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

### Personendaten abrufen

**GET** `/backend/invoice/?action=getPersonData&person_id={id}`

Ruft detaillierte Daten für eine bestimmte Person ab.

**Erforderliche Berechtigung:** `WRITE_INVOICE`

**Query-Parameter:**
- `person_id` (erforderlich): ID der Person

**Antwort (200):**
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

## Rechnungsstatus-Werte

Das Feld `status` unterstützt die folgenden Werte:
- `unpaid`: Rechnung wurde nicht bezahlt
- `partial`: Rechnung wurde teilweise bezahlt
- `paid`: Rechnung wurde vollständig bezahlt
- `overdue`: Rechnung ist überfällig
- `cancelled`: Rechnung wurde storniert

## Fehlercodes

| Code | Beschreibung |
|------|-------------|
| 400 | Bad request - Fehlende erforderliche Parameter oder ungültige Daten |
| 403 | Forbidden - Unzureichende Berechtigungen oder ungültige Authority |
| 404 | Not found - Rechnung oder Position existiert nicht |
| 405 | Method not allowed - Falsche HTTP-Methode verwendet |
| 500 | Internal server error - Datenbank- oder Systemfehler |

## Dateianhänge

Rechnungen unterstützen Dateianhänge, die im authority-spezifischen Upload-Verzeichnis gespeichert werden:

**Upload-Pfad:** `uploads/{authority}/invoices/`

Anhänge können enthalten:
- PDF-Rechnungen
- Begleitdokumente
- Zahlungsbelege
- Verträge

## Mandanten-Isolation

Alle Rechnungsoperationen werden automatisch auf die Authority des authentifizierten Benutzers beschränkt. Benutzer können nur auf Rechnungen zugreifen, die zu ihrer eigenen Authority gehören.

## Beispiele

### JavaScript-Beispiel

```javascript
// Alle Rechnungen abrufen
async function getInvoices() {
  const response = await fetch('/backend/invoice/?action=getInvoices', {
    method: 'GET',
    credentials: 'include', // Cookies einbeziehen
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

// Neue Rechnung erstellen
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

// Position zu Rechnung hinzufügen
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

### cURL-Beispiele

```bash
# Alle Rechnungen abrufen
curl -X GET \
  'http://localhost:8080/backend/invoice/?action=getInvoices' \
  -H 'Cookie: token=YOUR_JWT_TOKEN'

# Neue Rechnung erstellen
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

# Rechnungsposition hinzufügen
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

# Rechnung löschen
curl -X POST \
  'http://localhost:8080/backend/invoice/?action=deleteInvoice' \
  -H 'Cookie: token=YOUR_JWT_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{"id": 2}'
```

## Best Practices

1. **Rechnungsnummerierung**: Verwenden Sie ein konsistentes Nummerierungsschema (z.B. INV-YYYY-XXX)
2. **Fälligkeitsdaten**: Setzen Sie immer realistische Zahlungsziele
3. **Status-Updates**: Halten Sie den Rechnungsstatus aktuell, wenn Zahlungen eingehen
4. **Positionsdetails**: Geben Sie klare Beschreibungen für alle Rechnungspositionen an
5. **Firma vs. Person**: Wählen Sie den geeigneten Empfängertyp basierend auf dem Geschäftskontext
6. **Summenberechnung**: Summen werden aus Positionen berechnet (Menge × Einzelpreis)
7. **Prüfpfad**: Alle Rechnungsänderungen werden für Compliance-Zwecke protokolliert
8. **Anhänge**: Speichern Sie Begleitdokumente im dafür vorgesehenen Upload-Verzeichnis

## Berechnungslogik

Rechnungssummen werden wie folgt berechnet:

1. **Positionssumme** = `quantity × unit_price`
2. **Rechnungssumme** = Summe aller Positionssummen
3. **Restbetrag** = `total_amount - paid_amount`

## Datenbanktabellen

Die Rechnungs-API interagiert mit den folgenden Tabellen:

- `invoices`: Hauptrechnungsdatensätze
- `invoice_entries`: Einzelne Rechnungspositionen
- `companies`: Firmeninformationen für B2B-Rechnungen
- `persons`: Individuelle Kundeninformationen für B2C-Rechnungen

Alle Tabellen enthalten `authority_id` für Mandanten-Datenisolation.
