// types/Apartment.ts

// Typ für ein Apartment
export interface Apartment {
    id: number;
    name: string;             // Name des Apartments
    location: string;         // Ort des Apartments
    street: string;           // Straße des Apartments
    housenumber: string;      // Hausnummer des Apartments
    units: number;            // Anzahl der Einheiten
    bought: boolean;          // Gekauft-Status (true/false)
    rented: boolean;          // Vermietet-Status (true/false)
    text?: string;            // Beschreibung des Apartments (optional)
    owners?: number[];        // IDs der Besitzer (optional, mehrere Personen)
    tenants?: number[];       // IDs der Mieter (optional, mehrere Personen)
    landlords?: number[];     // IDs der Vermieter (optional, mehrere Personen)
    subtenants?: number[];    // IDs der Untermieter (optional, mehrere Personen)
    created_at?: string;      // Erstellungsdatum (optional)
    updated_at?: string;      // Aktualisierungsdatum (optional)
}

// Typ für die Beziehung zwischen einer Person und einem Apartment
export interface ApartmentPersonRelation {
    id: number;
    apartment_id: number;             // ID des Apartments
    person_id: number;                // ID der verknüpften Person
    type: 'owner' | 'tenant' | 'landlord' | 'subtenant'; // Beziehungstyp
    created_at?: string;              // Erstellungsdatum (optional)
    updated_at?: string;              // Aktualisierungsdatum (optional)
}

// Kombinationstyp für die Anzeige eines Apartments inklusive der Beziehungen zu Personen
export interface ApartmentWithRelations extends Apartment {
    relations?: ApartmentPersonRelation[];  // Array der Beziehungen (optional)
    type?: 'owner' | 'tenant' | 'landlord' | 'subtenant'; // Beziehungstyp für die aktuelle Anzeige
}
