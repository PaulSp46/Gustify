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
    $conn->beginTransaction();
    
    // Verifica che il prodotto appartenga all'utente corrente prima di eliminarlo
    $checkSql = "SELECT frigo.*, prodotto.idprodotto, pp.id as personalizzato_id 
                FROM frigo 
                JOIN prodotto ON frigo.prodotto_idprodotto = prodotto.idprodotto
                LEFT JOIN prodotto_personalizzato pp ON pp.prodotto_idprodotto = prodotto.idprodotto 
                                              AND pp.utente_idutente = frigo.utente_idutente
                WHERE frigo.idrelation = :id AND frigo.utente_idutente = :user_id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(":id", $productId);
    $checkStmt->bindParam(":user_id", $_SESSION["user_id"]);
    $checkStmt->execute();
    $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        // Il prodotto non esiste o non appartiene all'utente
        $conn->rollBack();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Prodotto non trovato o non autorizzato']);
        exit;
    }
    
    // Se esiste una personalizzazione per questo prodotto, eliminala
    if (!empty($product['personalizzato_id'])) {
        $deletePersonalSql = "DELETE FROM prodotto_personalizzato WHERE id = :personalizzato_id";
        $deletePersonalStmt = $conn->prepare($deletePersonalSql);
        $deletePersonalStmt->bindParam(":personalizzato_id", $product['personalizzato_id']);
        $deletePersonalStmt->execute();
    }
    
    // Elimina il prodotto dal frigo dell'utente
    $deleteSql = "DELETE FROM frigo WHERE idrelation = :id AND utente_idutente = :user_id";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bindParam(":id", $productId);
    $deleteStmt->bindParam(":user_id", $_SESSION["user_id"]);
    $deleteStmt->execute();
    
    // Verifica se ci sono altri utenti che hanno questo prodotto nel frigo
    $checkOtherUsersSql = "SELECT COUNT(*) as count FROM frigo WHERE prodotto_idprodotto = :prodotto_id";
    $checkOtherUsersStmt = $conn->prepare($checkOtherUsersSql);
    $checkOtherUsersStmt->bindParam(":prodotto_id", $product['prodotto_idprodotto']);
    $checkOtherUsersStmt->execute();
    $count = $checkOtherUsersStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Se questo era l'unico utente ad avere questo prodotto nel frigo, e il prodotto è stato creato 
    // manualmente dallo stesso utente (non verificato), lo eliminiamo dalla tabella prodotto
    if ($count == 0) {
        $checkProductSql = "SELECT created_by, verified FROM prodotto WHERE idprodotto = :prodotto_id";
        $checkProductStmt = $conn->prepare($checkProductSql);
        $checkProductStmt->bindParam(":prodotto_id", $product['prodotto_idprodotto']);
        $checkProductStmt->execute();
        $productInfo = $checkProductStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($productInfo && $productInfo['created_by'] == $_SESSION["user_id"] && !$productInfo['verified']) {
            $deleteProductSql = "DELETE FROM prodotto WHERE idprodotto = :prodotto_id";
            $deleteProductStmt = $conn->prepare($deleteProductSql);
            $deleteProductStmt->bindParam(":prodotto_id", $product['prodotto_idprodotto']);
            $deleteProductStmt->execute();
        }
    }
    
    $conn->commit();
    
    // Risponde con successo
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Prodotto eliminato con successo']);
    
} catch (PDOException $e) {
    // Rollback in caso di errore
    $conn->rollBack();
    
    // Gestisce eventuali errori del database
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Errore del database: ' . $e->getMessage()]);
}
?>