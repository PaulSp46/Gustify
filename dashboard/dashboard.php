<?php 
    session_start(); 
    if(!isset($_SESSION["email"])){
        header("Location: ../login/login.html");
    } else{
        include "../utility/database.php";

        // Recupero prodotti nel frigo dell'utente, ordinati per data di scadenza
        $sql = "SELECT prodotto.des as des, prodotto.categoria as categoria, frigo.quantita as quantita, 
                frigo.scadenza as scadenza, frigo.idrelation as id
                FROM prodotto, frigo
                WHERE frigo.utente_idutente = :idutente AND frigo.prodotto_idprodotto = prodotto.idprodotto
                ORDER BY frigo.scadenza ASC";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":idutente", $_SESSION["user_id"]);
        $stmt->execute();
        $products = $stmt->fetchAll();
    }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gustify - Gestisci la tua alimentazione</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
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
                <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
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
            <h1>Benvenuto su Gustify</h1>
            <p>Monitora la qualità e la scadenza dei tuoi prodotti alimentari, riduci gli sprechi e migliora le tue abitudini alimentari con un semplice scan!</p>
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
        
        <section class="features">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Monitoraggio scadenze</h3>
                <p>Tieni sotto controllo le date di scadenza dei tuoi prodotti e ricevi notifiche quando si avvicinano.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Gestione acquisti</h3>
                <p>Importa automaticamente i tuoi acquisti dai supermercati partner tramite QR code sullo scontrino.</p>
            </div>
        </section>
        
        <section class="products">
            <div class="section-header">
                <h2>I tuoi prodotti in scadenza</h2>
                <a href="../productsList/productsList.php" class="view-all">Vedi tutti</a>
            </div>
            
            <ul class="product-list">
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
                    $today = new DateTime();
                    $diff = $today->diff($date)->days;
                    
                    if ($diff == 0) {
                        return "Scade oggi";
                    } elseif ($diff == 1) {
                        return "Scade domani";
                    } elseif ($diff <= 7) {
                        return "Scade tra $diff giorni";
                    } else {
                        return "Scade il " . $date->format('d/m/Y');
                    }
                }
                
                // Funzione per ottenere l'icona della categoria
                function getProductIcon($category) {
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
                
                // Mostra solo i primi 5 prodotti (quelli che scadono prima)
                $maxProductsToShow = 5;
                $count = 0;
                
                if (count($products) > 0) {
                    foreach ($products as $product) {
                        if ($count >= $maxProductsToShow) break;
                        
                        $expiryClass = getExpiryClass($product['scadenza']);
                        $categoryIcon = getProductIcon($product['categoria']);
                        ?>
                        <li class="product-item">
                            <div class="product-info">
                                <div class="product-icon">
                                    <i class="fas <?php echo $categoryIcon; ?>"></i>
                                </div>
                                <div>
                                    <div class="product-name"><?php echo htmlspecialchars($product['des']); ?></div>
                                    <div class="product-expiry <?php echo $expiryClass; ?>">
                                        <?php echo formatDate($product['scadenza']); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="product-actions">
                                <i class="fas fa-ellipsis-v" onclick="showProductActions(<?php echo $product['id']; ?>)"></i>
                            </div>
                        </li>
                        <?php
                        $count++;
                    }
                } else {
                    echo '<li class="empty-message">Nessun prodotto nel tuo frigo. <a href="../manualAdd/manualAdd.php">Aggiungi prodotti</a></li>';
                }
                ?>
            </ul>
        </section>
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
    
    <!-- Product Actions Popup -->
    <div class="product-actions-popup" id="product-actions-popup">
        <button onclick="editProduct(currentProductId)"><i class="fas fa-edit"></i> Modifica</button>
        <button onclick="consumeProduct(currentProductId)"><i class="fas fa-utensils"></i> Consumato</button>
        <button onclick="deleteProduct(currentProductId)"><i class="fas fa-trash"></i> Elimina</button>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
    <script src="dashboard.js"></script>
</body>
</html>