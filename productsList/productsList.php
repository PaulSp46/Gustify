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

        // Recupero prodotti nel frigo dell'utente con dati personalizzati
        $sql = "SELECT 
                frigo.idrelation as id, 
                COALESCE(pp.des, prodotto.des) as des, 
                COALESCE(pp.marca, prodotto.marca) as marca, 
                COALESCE(pp.categoria, prodotto.categoria) as categoria, 
                frigo.quantita as quantita, 
                frigo.scadenza as scadenza, 
                frigo.note as note,
                frigo.data_creazione as data_aggiunta,
                prodotto.verified as verified
                FROM frigo
                JOIN prodotto ON frigo.prodotto_idprodotto = prodotto.idprodotto
                LEFT JOIN prodotto_personalizzato pp ON pp.prodotto_idprodotto = prodotto.idprodotto 
                                              AND pp.utente_idutente = frigo.utente_idutente
                WHERE frigo.utente_idutente = :idutente
                ORDER BY frigo.scadenza ASC";
        
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
    <link rel="icon" href="../tablogo.png">
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
                <li><a href="productsList.php" class="active"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href="../consumedList/consumedList.php"><i class="fas fa-utensils"></i> Consumati</a></li>
                <li><a href="../profile/profile.php"><i class="fas fa-user"></i> Profilo</a></li>
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
                    <i class="fas fa-plus"></i>
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
                        'cereal' => 'fa-wheat-awn',
                        'other' => 'fa-shopping-basket'
                    ];
                    
                    return isset($icons[$category]) ? $icons[$category] : 'fa-shopping-basket';
                }
                
                // Mostra i prodotti
                foreach ($products as $product):
                    $expiryClass = getExpiryClass($product['scadenza']);
                    $categoryIcon = getCategoryIcon($product['categoria']);
                    $verifiedClass = isset($product['verified']) && $product['verified'] ? 'verified-product' : '';
                    $addedDate = isset($product['data_aggiunta']) ? 
                                 (new DateTime($product['data_aggiunta']))->format('d/m/Y') : 'N/D';
            ?>
            <div class="product-card <?php echo $verifiedClass; ?>" data-category="<?php echo htmlspecialchars($product['categoria']); ?>" data-expiry="<?php echo $expiryClass; ?>">
                <div class="product-category">
                    <i class="fas <?php echo $categoryIcon; ?>"></i>
                    <?php if (isset($product['verified']) && $product['verified']): ?>
                        <span class="verified-badge" title="Prodotto verificato"><i class="fas fa-check-circle"></i></span>
                    <?php endif; ?>
                </div>
                <h3 class="product-title"><?php echo htmlspecialchars($product['des']); ?></h3>
                <div class="product-quantity">
                    Confezioni: <?php echo htmlspecialchars($product['quantita']); ?>
                </div>
                <div class="product-added-date">
                    Aggiunto il: <?php echo $addedDate; ?>
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
                    <!--<button class="delete-btn" title="Elimina prodotto" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>-->
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
                <i class="fas fa-plus"></i>
                Aggiungi prodotti
            </button>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="toast-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">Operazione completata</div>
            <div class="toast-message">Prodotto aggiornato con successo.</div>
        </div>
        <div class="toast-close" onclick="closeToast()">
            <i class="fas fa-times"></i>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="delete-confirmation-modal">
        <div class="modal-container">
            <div class="modal-icon">
                <i class="fas fa-trash-alt"></i>
            </div>
            <div class="modal-title">Conferma eliminazione</div>
            <div class="modal-message">Sei sicuro di voler eliminare questo prodotto dal tuo frigo? Questa azione non può essere annullata.</div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" id="cancel-delete-btn">
                    <i class="fas fa-times"></i> Annulla
                </button>
                <button class="modal-btn modal-btn-danger" id="confirm-delete-btn">
                    <i class="fas fa-trash-alt"></i> Elimina
                </button>
            </div>
        </div>
    </div>
    
    <!-- QR Reader Modal -->
    <div class="overlay" id="overlay"></div>
    <div id="qr-reader">
        <button id="qr-close-btn">X</button>
        <div id="qr-reader__status">Attivazione scanner...</div>
        <div id="qr-reader__camera"></div>
        <select id="qr-reader__camera-selection"></select>
    </div>

    <!-- Consume Modal -->
    <div class="modal-overlay" id="consume-modal">
        <div class="modal-container">
            <div class="modal-icon">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="modal-title">Consumo prodotto</div>
            <div class="modal-message">
                Stai per consumare <span id="consume-product-name">questo prodotto</span>. Quante confezioni vuoi consumare?
            </div>
            <div style="margin: 1rem 0; text-align: center;">
                <input type="number" id="consume-quantity" min="1" value="1" style="padding: 0.5rem; border-radius: var(--rounded-md); border: 1px solid var(--border-color); width: 100px; text-align: center;">
            </div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" onclick="cancelConsumption()">
                    <i class="fas fa-times"></i> Annulla
                </button>
                <button class="modal-btn modal-btn-primary" onclick="confirmConsumption()">
                    <i class="fas fa-check"></i> Conferma
                </button>
            </div>
        </div>
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
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
    <script src="productsList.js"></script>
</body>
</html>
<?php
    } else {
        header("Location: /login.html");
    }
?>