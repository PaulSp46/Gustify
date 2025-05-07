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
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Gustify
            </div>
            <ul class="nav-links">
                <li><a href="../index.html"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="productsList.php" class="active"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href=""><i class="fas fa-chart-pie"></i> Alimentazione</a></li>
                <li><a href=""><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <section class="hero">
            <h1>Modifica Prodotto</h1>
            <p>Aggiorna i dettagli del prodotto nel tuo frigorifero</p>
        </section>
        
        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="quantity">Quantità (confezioni)</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" 
                           value="<?php echo htmlspecialchars($product['quantity']); ?>" min="1" required>
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
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione menu mobile (se necessario)
            const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
            const navLinks = document.querySelector('.nav-links');
            
            if (mobileMenuIcon && navLinks) {
                mobileMenuIcon.addEventListener('click', function() {
                    navLinks.classList.toggle('show');
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