<?php
    $servername = "localhost";  
    $username = "root";         
    $password = "";             
    $dbname = "gustify";

    // Crea connessione
    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Recupera i dati dal modulo
        $nome = $_POST['name'];
        $cognome = $_POST['surname'];
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Proteggi la password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepara la query in modo sicuro
        $sql = "INSERT INTO utente (email, pwd, nome, cognome) 
                VALUES (:email, :pwd, :nome, :cognome)";
        $stmt = $conn->prepare($sql);

        try {
            $stmt->execute([
                ':email' => $email,
                ':pwd' => $hashedPassword,
                ':nome' => $nome,
                ':cognome' => $cognome
            ]);

            $sql = "SELECT idutente FROM utente WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":email", $email);
            $data = $stmt->fetchAll();

            echo $data["idutente"];

            $_SESSION['email'] = $email;
            $_SESSION['idutente'] = $data["idutente"];
            header("Location: ../dashboard/dashboard.php");
            exit();
        } catch (PDOException $e) {
            echo "Errore: " . $e->getMessage();
        }
    }
?>
