<?php
// Verbindung zur Datenbank herstellen
include 'db/connect.php';

// SQL-Befehl zum Ändern der ENUM-Werte
$sql = "ALTER TABLE kdd_authority_fields 
        MODIFY COLUMN field_type 
        ENUM('text','number','date','boolean','select','textarea','multiselect') 
        NOT NULL";

try {
    // SQL-Befehl ausführen
    $pdo->exec($sql);
    echo "ENUM-Werte erfolgreich aktualisiert!\n";
} catch (PDOException $e) {
    echo "Fehler beim Aktualisieren der ENUM-Werte: " . $e->getMessage() . "\n";
} 