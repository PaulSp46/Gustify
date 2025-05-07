<?php 
    session_start();
    if(isset($_SESSION["email"])){
        include "../utility/database.php";

        // Recupero informazioni utente
        $sql = "SELECT nome, cognome FROM utente WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":email", $_SESSION["email"]);
        $stmt->execute();
        $userData = $stmt->fetchAll();
        
        if (count($userData) > 0) {
            $nome = $userData[0]["nome"];
            $cognome = $userData[0]["cognome"];
        } else {
            $nome = "Utente";
            $cognome = "";
        }

        // Recupero prodotti nel frigo dell'utente
        $sql = "SELECT frigo.idrelation as id, prodotto.des as des, prodotto.marca as marca, prodotto.categoria as categoria, frigo.quantita as quantita, frigo.scadenza as scadenza, frigo.note as note
                FROM prodotto, frigo
                WHERE frigo.utente_idutente = :idutente AND frigo.prodotto_idprodotto = prodotto.idprodotto";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
        $stmt->execute();
        $products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Tuo Frigo - Gustify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="productsList.css">
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
                <li><a href="../dashboard/dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="fridge.php" class="active"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href=""><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <section class="hero">
            <h1>Il Tuo Frigo</h1>
            <p>Gestisci tutti i prodotti nel tuo frigorifero, monitora le scadenze e ottimizza i tuoi consumi alimentari</p>
            <div id="button-container">
                <button class="scan-btn">
                    <i class="fas fa-qrcode"></i>
                    Scansiona QR Code
                </button>
                <button class="manual-btn" onclick="location.href='../manualAdd/manualAdd.php'">
                    <i class="fa fa-plus"></i>
                    Aggiungi manualmente
                </button>
            </div>
        </section>
        
        <div class="filter-container">
            <div class="filter-group">
                <select class="filter-select" id="category-filter">
                    <option value="all">Tutte le categorie</option>
                    <option value="dairy">Latticini</option>
                    <option value="fruit">Frutta</option>
                    <option value="vegetable">Verdura</option>
                    <option value="meat">Carne</option>
                    <option value="fish">Pesce</option>
                    <option value="bakery">Prodotti da forno</option>
                    <option value="beverage">Bevande</option>
                    <option value="snack">Snack</option>
                    <option value="other">Altro</option>
                </select>
                
                <select class="filter-select" id="expiry-filter">
                    <option value="all">Tutte le scadenze</option>
                    <option value="critical">Scade oggi</option>
                    <option value="soon">Scade entro 3 giorni</option>
                    <option value="week">Scade entro 7 giorni</option>
                    <option value="month">Scade entro 30 giorni</option>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="text" class="search-box" id="search-product" placeholder="Cerca prodotto...">
            </div>
        </div>

        <?php if (count($products) > 0): ?>
        <div class="product-grid" id="product-grid">
            <?php 
                // Funzione per determinare lo stato di scadenza
                function getExpiryClass($expiryDate) {
                    $today = new DateTime();
                    $expiry = new DateTime($expiryDate);
                    $diff = $today->diff($expiry)->days;
                    
                    if ($expiry < $today) {
                        return 'expiry-critical';
                    } elseif ($diff <= 3) {
                        return 'expiry-critical';
                    } elseif ($diff <= 7) {
                        return 'expiry-soon';
                    } else {
                        return '';
                    }
                }
                
                // Funzione per formattare la data
                function formatDate($dateString) {
                    $date = new DateTime($dateString);
                    return $date->format('d/m/Y');
                }
                
                // Funzione per ottenere l'icona della categoria
                function getCategoryIcon($category) {
                    $icons = [
                        'dairy' => 'fa-cheese',
                        'fruit' => 'fa-apple-alt',
                        'vegetable' => 'fa-carrot',
                        'meat' => 'fa-drumstick-bite',
                        'fish' => 'fa-fish',
                        'bakery' => 'fa-bread-slice',
                        'beverage' => 'fa-wine-bottle',
                        'snack' => 'fa-cookie',
                        'cereal' => 'fa-solid fa-wheat-awn',
                        'other' => 'fa-shopping-basket'
                    ];
                    
                    return isset($icons[$category]) ? $icons[$category] : 'fa-shopping-basket';
                }
                
                // Mostra i prodotti
                foreach ($products as $product):
                    $expiryClass = getExpiryClass($product['scadenza']);
                    $categoryIcon = getCategoryIcon($product['categoria']);
            ?>
            <div class="product-card" data-category="<?php echo htmlspecialchars($product['categoria']); ?>">
                <div class="product-category">
                    <i class="fas <?php echo $categoryIcon; ?>"></i>
                </div>
                <h3 class="product-title"><?php echo htmlspecialchars($product['des']); ?></h3>
                <div class="product-quantity">
                    Confezioni: <?php echo htmlspecialchars($product['quantita']); ?>
                </div>
                <?php
                    if ($product["marca"] != null) {
                        echo "<div class='product-brand'>
                                Marca: " . htmlspecialchars($product['marca']) . 
                            "</div>";
                    }
                
                    if (!empty($product["note"])) {
                        // Verifica se le note superano una certa lunghezza
                        $notes = htmlspecialchars($product['note']);
                        $maxLength = 70; // Puoi regolare questo valore in base alle tue esigenze
                        
                        if (strlen($notes) > $maxLength) {
                            $shortNotes = substr($notes, 0, $maxLength) . '...';
                            echo "<div class='product-notes' title='" . $notes . "'>
                                    <span>Note: </span>
                                    <span class='note-content truncated'>" . $shortNotes . "</span>
                                    <span class='note-toggle' onclick='toggleNote(this)'>Leggi tutto</span>
                                    <span class='note-full' style='display:none'>" . $notes . "</span>
                                </div>";
                        } else {
                            echo "<div class='product-notes'>
                                    <span>Note: </span>
                                    <span class='note-content'>" . $notes . "</span>
                                </div>";
                        }
                    }
                ?>
                <div class="product-expiry <?php echo $expiryClass; ?>">
                    Scade il <?php echo formatDate($product['scadenza']); ?>
                </div>
                <div class="product-actions">
                    <button class="consume-btn" title="Segna come consumato" onclick="consumeProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-utensils"></i>
                    </button>
                    <button class="edit-btn" title="Modifica prodotto" onclick="editProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="delete-btn" title="Elimina prodotto" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-fridge">
            <i class="fas fa-box-open"></i>
            <h3>Il tuo frigo è vuoto!</h3>
            <p>Aggiungi i tuoi prodotti alimentari per iniziare a monitorare le scadenze e ottimizzare i consumi.</p>
            <button class="manual-btn" onclick="location.href='../manualAdd/manualAdd.php'">
                <i class="fa fa-plus"></i>
                Aggiungi prodotti
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <!-- QR Reader Modal -->
    <div class="overlay" id="overlay"></div>
    <div id="qr-reader">
        <button id="qr-close-btn">X</button>
        <div id="qr-reader__status">Attivazione scanner...</div>
        <div id="qr-reader__camera"></div>
        <select id="qr-reader__camera-selection"></select>
    </div>
    
    <!-- Success Message Modal -->
    <div class="success-message" id="success-message">
        <h3>Acquisto sincronizzato con successo!</h3>
        <p>Prodotti importati:</p>
        <ul class="product-list" id="scanned-products">
            <!-- Prodotti scansionati saranno aggiunti qui -->
        </ul>
        <button id="success-close-btn">Chiudi</button>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
    <script src="productsList.js"></script>
</body>
</html>
<?php
    } else {
        header("Location: /login.html");
    }
?>