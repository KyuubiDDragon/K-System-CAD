// In deiner Typdefinitionsdatei

export interface PersonFile {
  // Basis-Felder (wie in deinem Person Interface)
  id: number;
  name?: string;
  fullname?: string;
  firstname: string;
  lastname: string;
  gender: string;
  title: string;
  birthplace: string;
  birthday: string;
  phonenumber: string;
  address: string;
  idcard: string;
  bankaccount: string;
  mail: string;
  entry: string;
  licenses?: string;
  wanted?: boolean;
  text: string;
  is_deleted: boolean;
  custom_fields?: Record<string, any>;

  // Alle spezifischen Felder als optional hinzufügen
  // Fire
  fireRelatedTraining?: string;
  fireDepartment?: string;
  // Police & Justice (ggf. zusammenfassen oder spezifischer machen, wenn sie sich unterscheiden)
  lastKnownLocation?: string;
  gangAffiliation?: string; // Beispiel für Justice/Police
  // Medical
  allergies?: string;
  emergencyContact?: string;
  insured?: string; // Beispiel
  bloodType?: string; // Beispiel
  firstAid?: string; // Beispiel
  medicalHistory?: string; // Aus deiner Komponente
  // Justice specific (falls abweichend)
  caseHistory?: string; // Aus deiner Komponente
  legalStatus?: string; // Aus deiner Komponente
  lawyerName?: string; // Aus deiner Komponente
  // StatePark
  parkEntryStatus?: string; // Aus deiner Komponente
  violations?: string; // Aus deiner Komponente
  rangerNotes?: string; // Aus deiner Komponente
  license?: string; // Aus deiner StateParkPerson Def. - prüfen ob noch benötigt
  // Casa
  propertyStatus?: string; // Aus deiner Komponente
  rentalHistory?: string;  // Aus deiner Komponente
  landlordName?: string;   // Aus deiner Komponente
  job?: string;            // Aus deiner CasaPerson Def. - prüfen ob noch benötigt
  freelance?: string;      // Aus deiner CasaPerson Def. - prüfen ob noch benötigt
}

// Die spezifischen Interfaces (FirePerson, PolicePerson etc.) und der Union Type werden dann nicht mehr unbedingt für *diese* Komponente benötigt,
// es sei denn, du verwendest sie an anderer Stelle. Für das `person` ref in dieser Komponente ist die flache Struktur oben besser.