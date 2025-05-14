<?php
session_start();
if(!isset($_SESSION["email"])) {
    header("HTTP/1.1 401 Unauthorized");
    echo json_encode(["success" => false, "message" => "Non autorizzato"]);
    exit();
}

include "../utility/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["consumed_id"])) {
    $consumed_id = $_POST["consumed_id"];
    $user_id = $_SESSION["user_id"];
    
    try {
        // Verifica che il record esista e appartenga all'utente corrente
        $verify_sql = "SELECT idconsumo FROM consumo WHERE idconsumo = :id AND utente_idutente = :user_id";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bindParam(":id", $consumed_id);
        $verify_stmt->bindParam(":user_id", $user_id);
        $verify_stmt->execute();
        
        if ($verify_stmt->rowCount() === 0) {
            header("HTTP/1.1 403 Forbidden");
            echo json_encode(["success" => false, "message" => "Non hai il permesso di eliminare questo record"]);
            exit();
        }
        
        // Elimina il record
        $delete_sql = "DELETE FROM consumo WHERE idconsumo = :id";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bindParam(":id", $consumed_id);
        $delete_stmt->execute();
        
        echo json_encode(["success" => true, "message" => "Record eliminato con successo"]);
    } catch (PDOException $e) {
        header("HTTP/1.1 500 Internal Server Error");
        echo json_encode(["success" => false, "message" => "Errore durante l'eliminazione: " . $e->getMessage()]);
    }
} else {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(["success" => false, "message" => "Parametri mancanti o richiesta non valida"]);
}
?>