<?php
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION["email"])) {
    header("Location: ../login/login.html");
    exit();
}

// Includi il file di connessione al database
include "../utility/database.php";

// Recupera i dati inviati dal form
$nome = trim($_POST["nome"]);
$cognome = trim($_POST["cognome"]);
$email = trim($_POST["email"]);
$password = $_POST["password"]; // Può essere vuoto se l'utente non vuole cambiare la password

// Validazione base dei dati
if (empty($nome) || empty($cognome) || empty($email)) {
    $_SESSION["error_message"] = "Tutti i campi obbligatori devono essere compilati";
    header("Location: ../profile/profile.php");
    exit();
}

// Verifica che l'email sia valida
//if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//    $_SESSION["error_message"] = "L'indirizzo email non è valido";
//    header("Location: ../profile/profile.php");
//    exit();
//}

try {
    // Inizia una transazione
    $conn->beginTransaction();

    // Controlla se l'email è già utilizzata da un altro utente
    if ($email !== $_SESSION["email"]) {
        $check_email = "SELECT COUNT(*) FROM utente WHERE email = :email AND idutente != :idutente";
        $stmt_check = $conn->prepare($check_email);
        $stmt_check->bindParam(":email", $email);
        $stmt_check->bindParam(":idutente", $_SESSION["user_id"]);
        $stmt_check->execute();
        
        if ($stmt_check->fetchColumn() > 0) {
            $_SESSION["error_message"] = "L'indirizzo email è già utilizzato da un altro utente";
            header("Location: ../profile/profile.php");
            exit();
        }
    }

    // Aggiorna i dati dell'utente
    if (!empty($password)) {
        // Se è stata fornita una nuova password, aggiorna anche quella
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE utente SET nome = :nome, cognome = :cognome, email = :email, password = :password WHERE idutente = :idutente";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":password", $hashed_password);
    } else {
        // Altrimenti aggiorna solo nome, cognome ed email
        $sql = "UPDATE utente SET nome = :nome, cognome = :cognome, email = :email WHERE idutente = :idutente";
        $stmt = $conn->prepare($sql);
    }

    $stmt->bindParam(":nome", $nome);
    $stmt->bindParam(":cognome", $cognome);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":idutente", $_SESSION["user_id"]);
    $stmt->execute();

    // Commit della transazione
    $conn->commit();

    // Aggiorna anche le informazioni di sessione
    $_SESSION["email"] = $email;
    $_SESSION["success_message"] = "Profilo aggiornato con successo!";
    
    // Reindirizza alla pagina del profilo
    header("Location: ../profile/profile.php");
    exit();
    
} catch(PDOException $e) {
    // In caso di errore, esegui il rollback
    $conn->rollBack();
    $_SESSION["error_message"] = "Errore durante l'aggiornamento del profilo: " . $e->getMessage();
    header("Location: ../profile/profile.php");
    exit();
}
?>