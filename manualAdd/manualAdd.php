<?php
    session_start(); 
    if(!isset($_SESSION["email"])){
        header("Location: /login.html");
        exit();
    } else{
        include "../utility/database.php";

        $message = "";
        $messageType = "";
        // Gestione del form di aggiunta prodotto
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Recupero dati dal form
            $descrizione = $_POST["des"];
            $categoria = $_POST["categoria"];
            $marca = $_POST["marca"]; 
            $bar_code = $_POST["bar_code"];
            $data_scadenza = $_POST["data_scadenza"];
            $quantita = $_POST["quantita"];
            $note = isset($_POST["note"]) ? $_POST["note"] : null;

            // Validazione base
            if (empty($descrizione) || empty($categoria) || empty($data_scadenza) || empty($quantita)) {
                $message = "Tutti i campi obbligatori devono essere compilati";
                $messageType = "error";
            } else {
                try {
                    $sql = "SELECT idprodotto FROM prodotto WHERE bar_code = :bar_code";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":bar_code", $bar_code);
                    $stmt->execute();
                    $data = $stmt->fetchAll();
                    
                    if (empty($data)) {
                        $sql = "INSERT INTO prodotto (des, categoria, marca, bar_code)
                                VALUES (:des, :categoria, :marca, :bar_code)";
                        $stmt = $conn->prepare($sql);

                        $stmt->bindParam(":des", $descrizione);
                        $stmt->bindParam(":categoria", $categoria);
                        $stmt->bindParam(":marca", $marca);
                        $stmt->bindParam(":bar_code", $bar_code);

                        $stmt->execute();

                        $sql = "SELECT idprodotto FROM prodotto WHERE bar_code = :bar_code";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":bar_code", $bar_code);
                        $stmt->execute();
                        $data = $stmt->fetchAll();
                    }

                    $idprodotto = $data[0]["idprodotto"];

                    $sql = "SELECT * FROM frigo WHERE utente_idutente = :idutente AND prodotto_idprodotto = :idprodotto";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                    $stmt->bindParam(":idprodotto", $idprodotto);
                    
                    $stmt->execute();
                    $data = $stmt->fetchAll();

                    if (empty($data)) {
                        $sql = "INSERT INTO frigo (prodotto_idprodotto, utente_idutente, quantita, scadenza, note)
                            VALUES (:idprodotto, :idutente, :quantita, :scadenza, :note)";

                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":idprodotto", $idprodotto); 
                        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                        $stmt->bindParam(":quantita", $quantita);
                        $stmt->bindParam(":scadenza", $data_scadenza);
                        $stmt->bindParam(":note", $note);

                        $stmt->execute();
                    } else{
                        $sql = "UPDATE frigo SET quantita = quantita + :quantita 
                                WHERE utente_idutente = :idutente AND prodotto_idprodotto = :idprodotto";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":idprodotto", $idprodotto); 
                        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                        $stmt->bindParam(":quantita", $quantita);

                        $stmt->execute();
                    }

                    $message = "Prodotto aggiunto con successo!";
                    $messageType = "success";
                } catch(PDOException $e) {
                    $message = "Errore durante l'aggiunta del prodotto: " . $e->getMessage();
                    $messageType = "error";
                }
            }
        }

        // Recupero informazioni utente per la visualizzazione
        $sql = "SELECT nome, cognome FROM utente WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":email", $_SESSION["email"]);
        $stmt->execute();
        $data = $stmt->fetchAll();

        if (count($data) > 0) {
            $nome = $data[0]["nome"];
            $cognome = $data[0]["cognome"];
        } else {
            $nome = "Utente";
            $cognome = "";
        }
    }

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gustify - Aggiungi Prodotto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="manualAdd.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Gustify
            </div>
            <ul class="nav-links">
                <li><a href="../index.html"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="../dashboard/dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                <li><a href="../productsList/productsList.php"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href=""><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <section class="hero">
            <h1>Aggiungi un Prodotto</h1>
            <p>Inserisci i dettagli del prodotto che desideri aggiungere al tuo frigo virtuale</p>
        </section>
        
        <div class="form-container">
            <h2 class="page-title">Dettagli Prodotto</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="des">Nome o Descrizione *</label>
                    <input type="text" id="des" name="des" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoria *</label>
                    <select id="categoria" name="categoria" class="form-control" required>
                        <option value="">Seleziona categoria</option>
                        <option value="dairy">Latticini</option>
                        <option value="fruit">Frutta</option>
                        <option value="vegetable">Verdura</option>
                        <option value="meat">Carne</option>
                        <option value="fish">Pesce</option>
                        <option value="bakery">Prodotti da forno</option>
                        <option value="beverage">Bevande</option>
                        <option value="snack">Snack</option>
                        <option value="cereal">Cereali</option>
                        <option value="other">Altro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="marca">Marca *</label>
                    <input type="text" id="marca" name="marca" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="bar_code">Codice a barre *</label>
                    <input type="text" id="bar_code" name="bar_code" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="data_scadenza">Data di Scadenza *</label>
                    <input type="date" id="data_scadenza" name="data_scadenza" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="quantita">Quantità *</label>
                    <input type="number" id="quantita" name="quantita" min="1" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="note">Note (opzionale)</label>
                    <textarea id="note" name="note" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Salva Prodotto
                    </button>
                    <a href="../dashboard/dashboard.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('data_scadenza').setAttribute('min', today);

            // Preselect date to a week from now as default
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            const nextWeekFormatted = nextWeek.toISOString().split('T')[0];
            document.getElementById('data_scadenza').value = nextWeekFormatted;
        });
    </script>
</body>
</html>