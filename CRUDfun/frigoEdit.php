<?php 
    session_start();
    if(isset($_SESSION["email"])){
        include "../utility/database.php";
        
        // Verifica se è stato passato un ID di prodotto valido
        if(isset($_GET['id']) && is_numeric($_GET['id'])) {
            $relation_id = $_GET['id'];
            
            // Recupera i dati del prodotto
            $sql = "SELECT frigo.idrelation as id, frigo.quantita as quantity, frigo.scadenza as expiry_date
                    FROM frigo
                    WHERE frigo.idrelation = :id AND frigo.utente_idutente = :user_id";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":id", $relation_id);
            $stmt->bindParam(":user_id", $_SESSION["user_id"]);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$product) {
                // Se il prodotto non esiste o non appartiene all'utente, reindirizza alla lista prodotti
                header("Location: productsList.php");
                exit;
            }
            
            // Gestione del form di modifica
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $quantity = $_POST['quantity'];
                $expiry_date = $_POST['expiry_date'];
                
                // Aggiornamento del prodotto nella tabella prodotto
                $updateProductSql = "UPDATE prodotto SET des = :description, marca = :brand, categoria = :category 
                                     WHERE idprodotto = :product_id";
                $updateProductStmt = $conn->prepare($updateProductSql);
                $updateProductStmt->bindParam(":description", $description);
                $updateProductStmt->bindParam(":brand", $brand);
                $updateProductStmt->bindParam(":category", $category);
                $updateProductStmt->bindParam(":product_id", $product['product_id']);
                $productUpdateSuccess = $updateProductStmt->execute();
                
                // Aggiornamento della relazione nella tabella frigo
                $updateFrigoSql = "UPDATE frigo SET quantita = :quantity, scadenza = :expiry_date 
                                   WHERE idrelation = :id AND utente_idutente = :user_id";
                $updateFrigoStmt = $conn->prepare($updateFrigoSql);
                $updateFrigoStmt->bindParam(":quantity", $quantity);
                $updateFrigoStmt->bindParam(":expiry_date", $expiry_date);
                $updateFrigoStmt->bindParam(":id", $relation_id);
                $updateFrigoStmt->bindParam(":user_id", $_SESSION["user_id"]);
                $frigoUpdateSuccess = $updateFrigoStmt->execute();
                
                if ($productUpdateSuccess && $frigoUpdateSuccess) {
                    // Reindirizza alla lista prodotti con messaggio di successo
                    header("Location: productsList.php?message=updated");
                    exit;
                }
            }
        } else {
            // Se non è stato passato un ID valido, reindirizza alla lista prodotti
            header("Location: ../productsList/productsList.php");
            exit;
        }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Prodotto - Gustify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="frigoEdit.css">
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
                <li><a href="../productsList/productsList.php" class="active"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href="../profile/profile.php"><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <section class="hero fade-in">
            <h1>Modifica Prodotto</h1>
            <p>Aggiorna i dettagli del prodotto nel tuo inventario</p>
        </section>
        
        <div class="form-container fade-in">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="quantity">Quantità (confezioni)</label>
                    <div class="number-input-wrapper">
                        <input type="number" id="quantity" name="quantity" class="form-control" 
                               value="<?php echo htmlspecialchars($product['quantity']); ?>" min="1" required>
                        <div class="number-control">
                            <button type="button" class="number-btn increase-btn" aria-label="Aumenta quantità">
                                <i class="fas fa-chevron-up"></i>
                            </button>
                            <button type="button" class="number-btn decrease-btn" aria-label="Diminuisci quantità">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="expiry_date">Data di scadenza</label>
                    <input type="date" id="expiry_date" name="expiry_date" class="form-control" 
                           value="<?php echo htmlspecialchars($product['expiry_date']); ?>" required>
                </div>
                
                <div class="form-buttons">
                    <a href="../productsList/productsList.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Annulla
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salva modifiche
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Toast Notification (hidden by default) -->
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">Operazione completata</div>
            <div class="toast-message">Le modifiche sono state salvate con successo.</div>
        </div>
        <div class="toast-close">
            <i class="fas fa-times"></i>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione menu mobile
            const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenuIcon && navLinks) {
                mobileMenuIcon.addEventListener('click', function() {
                    navLinks.classList.toggle('show');
                });
            }
            
            // Controlli per input numerico
            const quantityInput = document.getElementById('quantity');
            const increaseBtn = document.querySelector('.increase-btn');
            const decreaseBtn = document.querySelector('.decrease-btn');
            
            if (quantityInput && increaseBtn && decreaseBtn) {
                increaseBtn.addEventListener('click', function() {
                    quantityInput.value = parseInt(quantityInput.value) + 1;
                });
                
                decreaseBtn.addEventListener('click', function() {
                    if (parseInt(quantityInput.value) > 1) {
                        quantityInput.value = parseInt(quantityInput.value) - 1;
                    }
                });
            }
            
            // Gestione toast notification
            function showToast(type, title, message) {
                const toast = document.getElementById('toast');
                const toastIcon = toast.querySelector('.toast-icon i');
                const toastTitle = toast.querySelector('.toast-title');
                const toastMessage = toast.querySelector('.toast-message');
                
                // Imposta icona in base al tipo
                if (type === 'success') {
                    toast.className = 'toast success';
                    toastIcon.className = 'fas fa-check-circle';
                } else if (type === 'error') {
                    toast.className = 'toast error';
                    toastIcon.className = 'fas fa-exclamation-circle';
                }
                
                // Imposta contenuto
                toastTitle.textContent = title;
                toastMessage.textContent = message;
                
                // Mostra toast
                toast.classList.add('show');
                
                // Nascondi dopo 3 secondi
                setTimeout(function() {
                    toast.classList.remove('show');
                }, 3000);
            }
            
            // Chiusura toast
            const toastClose = document.querySelector('.toast-close');
            if (toastClose) {
                toastClose.addEventListener('click', function() {
                    document.getElementById('toast').classList.remove('show');
                });
            }
            
            // Controlla se c'è un parametro message nell'URL (per mostrare toast dopo redirect)
            const urlParams = new URLSearchParams(window.location.search);
            const messageParam = urlParams.get('message');
            
            if (messageParam === 'updated') {
                showToast('success', 'Modifiche Salvate', 'Le modifiche sono state salvate con successo.');
            }
            
            // Validazione form prima dell'invio
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Validazione quantità
                    if (parseInt(quantityInput.value) < 1) {
                        isValid = false;
                        quantityInput.style.borderColor = 'var(--error-color)';
                        showToast('error', 'Errore di Validazione', 'La quantità deve essere maggiore di zero.');
                    } else {
                        quantityInput.style.borderColor = 'var(--border-color)';
                    }
                    
                    // Validazione data di scadenza
                    const expiryDate = document.getElementById('expiry_date');
                    if (!expiryDate.value) {
                        isValid = false;
                        expiryDate.style.borderColor = 'var(--error-color)';
                        showToast('error', 'Errore di Validazione', 'La data di scadenza è obbligatoria.');
                    } else {
                        expiryDate.style.borderColor = 'var(--border-color)';
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
    } else {
        header("Location: /login.html");
    }
?>