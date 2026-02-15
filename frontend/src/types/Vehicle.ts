export interface Vehicle {
  id: number;
  active: boolean;
  title: string;
  numberplate: string;
  rank?: string | null;
  damage: boolean;
  damage_officer?: string | null;
  damage_time: string;
  dispatch_id?: number | null;
  sort_order?: number | null;
  created_at: string;
  updated_at: string;
}

// Datei: src/types/Vehicle.ts

export interface VehicleFile {
  id: number;
  owner?: number; // Optional, da es sich um eine Referenz zu einer anderen Person handelt
  driver?: number; // Optional, da es sich um eine Referenz zu einer anderen Person handelt
  owners?: number[]; // Mehrere Besitzer erlaubt
  drivers?: number[]; // Mehrere Fahrer erlaubt
  brand: string;
  model: string;
  numberplate: string;
  color: string;
  stolen: boolean;
  wanted: boolean;
  registered?: string; // Datum, optional
  text: string;
  is_deleted: boolean;
  role?: string; // Hinzugefügt für die Rollenanzeige (z.B. "Besitzer" oder "Fahrer")
}
