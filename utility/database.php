<?php
    $conn = null;
    $servername = "localhost";
    $username = "root"; //xampp user: root; altervista user: castiglionepaolo 
    $password = ""; // xampp pass: ""; altervista pass: ""
    $dbname = "gustify"; // altervista dbname: "my_castiglionepaolo"; xampp: "<nome creato>"

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password); //PDO = PHP Data Object --> manipolazione dei dati dei database in php
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit();
    }

    if ($conn == null){
        echo "Connessione al database non riuscita";
    }
?>