<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Non autorizzato"]);
    exit;
}

// Include database connection
include "../utility/database.php";

// Check if relation_id is provided
if (!isset($_POST["relation_id"]) || empty($_POST["relation_id"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID relazione mancante"]);
    exit;
}

$relation_id = $_POST["relation_id"];
$user_id = $_SESSION["user_id"];

// Get consume_quantity (if provided), otherwise default to full quantity
$consume_quantity = isset($_POST["consume_quantity"]) ? intval($_POST["consume_quantity"]) : 0;

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Get product information from frigo table
    $sql_get_product = "SELECT prodotto_idprodotto, quantita, note FROM frigo 
                        WHERE idrelation = :relation_id AND utente_idutente = :user_id";
    $stmt_get = $conn->prepare($sql_get_product);
    $stmt_get->bindParam(":relation_id", $relation_id);
    $stmt_get->bindParam(":user_id", $user_id);
    $stmt_get->execute();
    
    if ($stmt_get->rowCount() === 0) {
        throw new Exception("Prodotto non trovato o non appartiene all'utente");
    }
    
    $product_data = $stmt_get->fetch();
    $product_id = $product_data["prodotto_idprodotto"];
    $total_quantity = $product_data["quantita"];
    $notes = $product_data["note"];
    
    // If consume_quantity is 0 or greater than total, consume all
    if ($consume_quantity <= 0 || $consume_quantity > $total_quantity) {
        $consume_quantity = $total_quantity;
    }
    
    // Insert into consumo table
    $sql_insert = "INSERT INTO consumo (utente_idutente, prodotto_idprodotto, quantita, note) 
                   VALUES (:user_id, :product_id, :quantity, :notes)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bindParam(":user_id", $user_id);
    $stmt_insert->bindParam(":product_id", $product_id);
    $stmt_insert->bindParam(":quantity", $consume_quantity);
    $stmt_insert->bindParam(":notes", $notes);
    $stmt_insert->execute();
    
    // Check if consuming all or part of the quantity
    if ($consume_quantity >= $total_quantity) {
        // Consume all: Delete from frigo table
        $sql_delete = "DELETE FROM frigo WHERE idrelation = :relation_id AND utente_idutente = :user_id";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(":relation_id", $relation_id);
        $stmt_delete->bindParam(":user_id", $user_id);
        $stmt_delete->execute();
    } else {
        // Consume part: Update quantity in frigo table
        $new_quantity = $total_quantity - $consume_quantity;
        $sql_update = "UPDATE frigo SET quantita = :new_quantity 
                      WHERE idrelation = :relation_id AND utente_idutente = :user_id";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(":new_quantity", $new_quantity);
        $stmt_update->bindParam(":relation_id", $relation_id);
        $stmt_update->bindParam(":user_id", $user_id);
        $stmt_update->execute();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        "success" => true, 
        "message" => "Prodotto contrassegnato come consumato con successo",
        "consumed_all" => ($consume_quantity >= $total_quantity),
        "remaining_quantity" => ($consume_quantity >= $total_quantity) ? 0 : ($total_quantity - $consume_quantity)
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Errore: " . $e->getMessage()]);
}
?>