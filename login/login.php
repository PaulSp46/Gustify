<?php
    session_start();

    // Connessione al database MySQL
    $servername = "localhost";  // Modifica con il tuo host MySQL
    $username = "root";         // Modifica con il tuo username MySQL
    $password = "";             // Modifica con la tua password MySQL
    $dbname = "gustify";  // Nome del database

    // Crea connessione
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        //TODO: improve failed message
        echo "Connection failed: " . $e->getMessage();
        exit();
    }

    // Controllo se il modulo è stato inviato
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Recupera i dati dal modulo
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Prepara la query per cercare l'utente nel database
        $sql = "SELECT idUtente, email, pwd FROM utente WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $result = $stmt->fetchAll();

        if (count($result)) {
            // L'utente esiste, ora controlliamo la password
            $user = $result[0];

            // Verifica la password con password_hash
            if (password_verify($password, $user['pwd'])) {
                // Password corretta, sessione avviata
                $_SESSION['user_id'] = $user['idUtente'];
                $_SESSION['email'] = $user['email'];
                // Redirect alla home page o alla pagina protetta
                header("Location: ../dashboard/dashboard.php");
                exit();
            } else {
                header("Location: login.html");
            }
        } else {
            echo "Nessun utente trovato con questa email.";
        }
    }
?>
