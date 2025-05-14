<?php
session_start();
header('Content-Type: application/json');

// Verifica che l'utente sia autenticato
if (!isset($_SESSION["email"]) || !isset($_SESSION["user_id"])) {
    echo json_encode(["success" => false, "message" => "Utente non autenticato"]);
    exit;
}

// Connessione al database
include "../utility/database.php";

try {
    // Prepara ed esegui la query per eliminare tutti i prodotti consumati dall'utente
    $sql = "DELETE FROM consumo WHERE utente_idutente = :idutente";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idutente", $_SESSION["user_id"]);
    $result = $stmt->execute();
    
    // Verifica se l'operazione è riuscita
    if ($result) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Errore durante l'eliminazione dei dati"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Errore del database: " . $e->getMessage()]);
}
?>