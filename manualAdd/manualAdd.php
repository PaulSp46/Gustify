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
                    $conn->beginTransaction();
                    
                    // Verifica se esiste già un prodotto con questo barcode
                    $sql = "SELECT idprodotto FROM prodotto WHERE bar_code = :bar_code";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":bar_code", $bar_code);
                    $stmt->execute();
                    $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $idprodotto = null;
                    
                    if ($existingProduct) {
                        // Il prodotto esiste già nel database globale
                        $idprodotto = $existingProduct['idprodotto'];
                        
                        // Verifica se l'utente ha una versione personalizzata
                        $stmt = $conn->prepare("SELECT id FROM prodotto_personalizzato 
                                              WHERE prodotto_idprodotto = :idprodotto AND utente_idutente = :idutente");
                        $stmt->bindParam(":idprodotto", $idprodotto);
                        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                        $stmt->execute();
                        $personalProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($personalProduct) {
                            // Aggiorna la versione personalizzata
                            $stmt = $conn->prepare("UPDATE prodotto_personalizzato 
                                                 SET des = :des, categoria = :categoria, marca = :marca, note = :note 
                                                 WHERE id = :id");
                            $stmt->bindParam(":des", $descrizione);
                            $stmt->bindParam(":categoria", $categoria);
                            $stmt->bindParam(":marca", $marca);
                            $stmt->bindParam(":note", $note);
                            $stmt->bindParam(":id", $personalProduct['id']);
                            $stmt->execute();
                        } else {
                            // Crea versione personalizzata
                            $stmt = $conn->prepare("INSERT INTO prodotto_personalizzato 
                                                  (prodotto_idprodotto, utente_idutente, des, categoria, marca, note) 
                                                  VALUES (:idprodotto, :idutente, :des, :categoria, :marca, :note)");
                            $stmt->bindParam(":idprodotto", $idprodotto);
                            $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                            $stmt->bindParam(":des", $descrizione);
                            $stmt->bindParam(":categoria", $categoria);
                            $stmt->bindParam(":marca", $marca);
                            $stmt->bindParam(":note", $note);
                            $stmt->execute();
                        }
                    } else {
                        // Crea un nuovo prodotto globale ma marcato come 'manual' e non verificato
                        $stmt = $conn->prepare("INSERT INTO prodotto 
                                              (des, categoria, marca, bar_code, created_by, source, verified) 
                                              VALUES (:des, :categoria, :marca, :bar_code, :created_by, 'manual', false)");
                        $stmt->bindParam(":des", $descrizione);
                        $stmt->bindParam(":categoria", $categoria);
                        $stmt->bindParam(":marca", $marca);
                        $stmt->bindParam(":bar_code", $bar_code);
                        $stmt->bindParam(":created_by", $_SESSION["user_id"]);
                        $stmt->execute();
                        
                        $idprodotto = $conn->lastInsertId();
                    }
                    
                    // Aggiungi o aggiorna il prodotto nel frigo dell'utente
                    $stmt = $conn->prepare("SELECT idrelation FROM frigo 
                                          WHERE utente_idutente = :idutente AND prodotto_idprodotto = :idprodotto");
                    $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                    $stmt->bindParam(":idprodotto", $idprodotto);
                    $stmt->execute();
                    $frigoProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($frigoProduct) {
                        // Aggiorna quantità
                        $stmt = $conn->prepare("UPDATE frigo SET quantita = quantita + :quantita 
                                              WHERE idrelation = :idrelation");
                        $stmt->bindParam(":quantita", $quantita);
                        $stmt->bindParam(":idrelation", $frigoProduct['idrelation']);
                        $stmt->execute();
                    } else {
                        // Inserisci nuovo record con data_creazione
                        $stmt = $conn->prepare("INSERT INTO frigo 
                                              (prodotto_idprodotto, utente_idutente, quantita, scadenza, note, data_creazione) 
                                              VALUES (:idprodotto, :idutente, :quantita, :scadenza, :note, CURRENT_TIMESTAMP)");
                        $stmt->bindParam(":idprodotto", $idprodotto);
                        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
                        $stmt->bindParam(":quantita", $quantita);
                        $stmt->bindParam(":scadenza", $data_scadenza);
                        $stmt->bindParam(":note", $note);
                        $stmt->execute();
                    }
                    
                    $conn->commit();
                    $message = "Prodotto aggiunto con successo!";
                    $messageType = "success";
                    
                } catch(PDOException $e) {
                    $conn->rollBack();
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
            <a href="../index.php" class="logo">
                <i class="fas fa-leaf"></i>
                Gustify
            </a>
            <ul class="nav-links">
                <li><a href="../dashboard/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="../productsList/productsList.php"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href="../profile/profile.php"><i class="fas fa-user"></i> Profilo</a></li>
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
        
        <div class="form-container fade-in">
            <h2 class="page-title">Dettagli Prodotto</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php if ($messageType == "success"): ?>
                        <i class="fas fa-check-circle toast-icon"></i>
                    <?php else: ?>
                        <i class="fas fa-exclamation-circle toast-icon"></i>
                    <?php endif; ?>
                    <div><?php echo $message; ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="des">Nome o Descrizione *</label>
                    <input type="text" id="des" name="des" class="form-control" placeholder="Inserisci il nome del prodotto" required>
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
                    <input type="text" id="marca" name="marca" class="form-control" placeholder="Inserisci la marca del prodotto" required>
                </div>

                <div class="form-group">
                    <label for="bar_code">Codice a barre *</label>
                    <input type="text" id="bar_code" name="bar_code" class="form-control" placeholder="Inserisci il codice a barre del prodotto" required>
                </div>
                
                <div class="form-group">
                    <label for="data_scadenza">Data di Scadenza *</label>
                    <input type="date" id="data_scadenza" name="data_scadenza" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="quantita">Quantità *</label>
                    <input type="number" id="quantita" name="quantita" min="1" value="1" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="note">Note (opzionale)</label>
                    <textarea id="note" name="note" class="form-control" rows="3" placeholder="Inserisci eventuali note sul prodotto"></textarea>
                </div>
                
                <div class="button-group">
                    <a href="../dashboard/dashboard.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Annulla
                    </a>
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Salva Prodotto
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">Operazione completata</div>
            <div class="toast-message">Prodotto aggiunto con successo.</div>
        </div>
        <div class="toast-close" onclick="closeToast()">
            <i class="fas fa-times"></i>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <script>
        // Mobile menu
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenuIcon && navLinks) {
                mobileMenuIcon.addEventListener('click', function() {
                    navLinks.classList.toggle('show');
                });
            }
            
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('data_scadenza').setAttribute('min', today);

            // Preselect date to a week from now as default
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            const nextWeekFormatted = nextWeek.toISOString().split('T')[0];
            document.getElementById('data_scadenza').value = nextWeekFormatted;
            
            // Toast notification
            <?php if (!empty($message) && $messageType == "success"): ?>
                showToast('Operazione completata', '<?php echo $message; ?>', 'success');
            <?php elseif (!empty($message) && $messageType == "error"): ?>
                showToast('Errore', '<?php echo $message; ?>', 'error');
            <?php endif; ?>
        });
        
        // Toast functions
        function showToast(title, message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastTitle = toast.querySelector('.toast-title');
            const toastMessage = toast.querySelector('.toast-message');
            const toastIcon = toast.querySelector('.toast-icon i');
            
            toast.className = 'toast ' + type;
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            
            if (type === 'success') {
                toastIcon.className = 'fas fa-check-circle';
            } else {
                toastIcon.className = 'fas fa-exclamation-circle';
            }
            
            toast.classList.add('show');
            
            // Hide toast after 5 seconds
            setTimeout(closeToast, 5000);
        }
        
        function closeToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('show');
        }
    </script>
</body>
</html>