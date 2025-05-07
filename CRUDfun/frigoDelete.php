<?php
// Inizia la sessione per verificare l'autenticazione dell'utente
session_start();

// Verifica che l'utente sia autenticato
if (!isset($_SESSION["email"]) || !isset($_SESSION["user_id"])) {
    // Risponde con un errore in formato JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

// Verifica che la richiesta sia di tipo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// Verifica che sia stato fornito l'ID del prodotto
if (!isset($_POST["relation_id"]) || empty($_POST["relation_id"])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ID prodotto mancante']);
    exit;
}

// Recupera l'ID del prodotto
$productId = intval($_POST["relation_id"]);

// Connessione al database
include "../utility/database.php";

try {
    // Verifica che il prodotto appartenga all'utente corrente prima di eliminarlo
    $checkSql = "SELECT * FROM frigo WHERE idrelation = :id AND utente_idutente = :user_id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(":id", $productId);
    $checkStmt->bindParam(":user_id", $_SESSION["user_id"]);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        // Il prodotto non esiste o non appartiene all'utente
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Prodotto non trovato o non autorizzato']);
        exit;
    }
    
    // Elimina il prodotto dal frigo dell'utente
    $deleteSql = "DELETE FROM frigo WHERE idrelation = :id AND utente_idutente = :user_id";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bindParam(":id", $productId);
    $deleteStmt->bindParam(":user_id", $_SESSION["user_id"]);
    $deleteStmt->execute();
    
    // Verifica se l'eliminazione è avvenuta con successo
    if ($deleteStmt->rowCount() > 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Prodotto eliminato con successo']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'eliminazione del prodotto']);
    }
    
} catch (PDOException $e) {
    // Gestisce eventuali errori del database
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore del database: ' . $e->getMessage()]);
}
?>