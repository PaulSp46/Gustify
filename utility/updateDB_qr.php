<?php
session_start();
if (!isset($_SESSION["email"])) {
    // Ritorna errore 401 se non autenticato
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(["success" => false, "error" => "Non autenticato"]);
    exit();
}

include "database.php";

// Recupera il contenuto inviato come JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Verifica che i dati siano validi
if (!isset($data['products']) || !is_array($data['products'])) {
    echo json_encode(["success" => false, "error" => "Formato dati non valido"]);
    exit();
}

$added = 0;
$errors = [];

// Inizia transazione
$conn->beginTransaction();

try {
    foreach ($data['products'] as $product) {
        // Verifica che tutti i campi richiesti siano presenti
        if (empty($product['bar_code']) || empty($product['name']) || 
            empty($product['category']) || empty($product['quantita']) || 
            empty($product['expiryDate'])) {
            $errors[] = "Prodotto con dati incompleti ignorato";
            continue;
        }

        // 1. Verifica se il prodotto esiste già nella tabella 'prodotto'
        $sql = "SELECT idprodotto FROM prodotto WHERE bar_code = :bar_code";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":bar_code", $product['bar_code']);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $idprodotto = null;
        
        if (!$existing) {
            // 2. Se non esiste, inseriscilo nella tabella 'prodotto' (ora con i nuovi campi)
            $sql = "INSERT INTO prodotto (des, categoria, bar_code, marca, created_by, source, verified) 
                    VALUES (:des, :categoria, :bar_code, :marca, :created_by, 'qr', true)";
            $stmt = $conn->prepare($sql);
            
            $stmt->bindParam(":des", $product['name']);
            $stmt->bindParam(":categoria", $product['category']);
            $stmt->bindParam(":bar_code", $product['bar_code']);
            $marca = isset($product['marca']) ? $product['marca'] : null; // Valore predefinito se non presente
            $stmt->bindParam(":marca", $marca);
            $stmt->bindParam(":created_by", $_SESSION["user_id"]);
            
            $stmt->execute();
            
            // Ottieni l'ID del prodotto appena inserito
            $idprodotto = $conn->lastInsertId();
        } else {
            $idprodotto = $existing['idprodotto'];
        }
        
        // 3. Verifica se il prodotto è già nel frigo dell'utente
        $sql = "SELECT idrelation FROM frigo 
                WHERE utente_idutente = :idutente AND prodotto_idprodotto = :idprodotto";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
        $stmt->bindParam(":idprodotto", $idprodotto);
        $stmt->execute();
        $inFrigo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 4. Aggiorna o inserisci il prodotto nel frigo
        if ($inFrigo) {
            // Se esiste già, aggiorna la quantità
            $sql = "UPDATE frigo SET quantita = quantita + :quantita 
                    WHERE utente_idutente = :idutente AND prodotto_idprodotto = :idprodotto";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":idprodotto", $idprodotto);
            $stmt->bindParam(":idutente", $_SESSION["user_id"]);
            $stmt->bindParam(":quantita", $product['quantita']);
            $stmt->execute();
        } else {
            // Se non esiste, inseriscilo con data_creazione
            $sql = "INSERT INTO frigo (prodotto_idprodotto, utente_idutente, quantita, scadenza, note, data_creazione)
                    VALUES (:idprodotto, :idutente, :quantita, :scadenza, :note, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":idprodotto", $idprodotto);
            $stmt->bindParam(":idutente", $_SESSION["user_id"]);
            $stmt->bindParam(":quantita", $product['quantita']);
            $stmt->bindParam(":scadenza", $product['expiryDate']);
            $note = isset($product['note']) ? $product['note'] : null;
            $stmt->bindParam(":note", $note);
            $stmt->execute();
        }
        
        $added++;
    }
    
    // Conferma transazione
    $conn->commit();
    
    echo json_encode([
        "success" => true, 
        "added" => $added,
        "errors" => $errors
    ]);
    
} catch (PDOException $e) {
    // Rollback in caso di errore
    $conn->rollback();
    echo json_encode([
        "success" => false, 
        "error" => "Errore di database: " . $e->getMessage()
    ]);
}
?>