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

        // Recupero prodotti consumati dall'utente con dati personalizzati
        $sql = "SELECT 
                consumo.idconsumo as id, 
                COALESCE(pp.des, prodotto.des) as des, 
                COALESCE(pp.marca, prodotto.marca) as marca, 
                COALESCE(pp.categoria, prodotto.categoria) as categoria, 
                consumo.quantita as quantita, 
                consumo.data_consumo as data_consumo, 
                consumo.note as note,
                prodotto.verified as verified
                FROM consumo
                JOIN prodotto ON consumo.prodotto_idprodotto = prodotto.idprodotto
                LEFT JOIN prodotto_personalizzato pp ON pp.prodotto_idprodotto = prodotto.idprodotto 
                                              AND pp.utente_idutente = consumo.utente_idutente
                WHERE consumo.utente_idutente = :idutente
                ORDER BY consumo.data_consumo DESC";
        
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
    <title>Prodotti Consumati - Gustify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="consumedList.css">
    <link rel="stylesheet" href="consumedList.css">
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
                <li><a href="../productsList/productsList.php"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href="consumedList.php" class="active"><i class="fas fa-utensils"></i> Consumati</a></li>
                <li><a href="../profile/profile.php"><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <section class="hero">
            <h1>Prodotti Consumati</h1>
            <p>Visualizza la cronologia dei prodotti che hai consumato, analizza le tue abitudini alimentari e mantieni traccia dei tuoi consumi</p>
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
                
                <select class="filter-select" id="time-filter">
                    <option value="all">Tutti i periodi</option>
                    <option value="today">Oggi</option>
                    <option value="week">Ultima settimana</option>
                    <option value="month">Ultimo mese</option>
                    <option value="year">Ultimo anno</option>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="text" class="search-box" id="search-product" placeholder="Cerca prodotto...">
            </div>

            <?php if (count($products) > 0): ?>
            <button id="delete-all-btn" class="delete-all-btn">
                <i class="fas fa-trash-alt"></i> Elimina Tutto
            </button>
            <?php endif; ?>
        </div>

        <?php if (count($products) > 0): ?>
        <div class="table-container">
            <table class="consumed-table">
                <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th>Categoria</th>
                        <th>Marca</th>
                        <th>Quantità</th>
                        <th>Data consumo</th>
                        <th>Note</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody id="consumed-table-body">
                    <?php 
                        // Funzione per formattare la data
                        function formatDate($dateString) {
                            $date = new DateTime($dateString);
                            return $date->format('d/m/Y H:i');
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
                        
                        // Calcola il timestamp per i filtri di data
                        $todayStart = strtotime('today');
                        $weekStart = strtotime('-7 days');
                        $monthStart = strtotime('-30 days');
                        $yearStart = strtotime('-365 days');
                        
                        // Mostra i prodotti consumati
                        foreach ($products as $product):
                            $categoryIcon = getCategoryIcon($product['categoria']);
                            $verifiedClass = isset($product['verified']) && $product['verified'] ? 'verified-product' : '';
                            $consumedDate = strtotime($product['data_consumo']);
                            $dataClass = '';
                            
                            if ($consumedDate >= $todayStart) {
                                $dataClass = 'data-today';
                            } else if ($consumedDate >= $weekStart) {
                                $dataClass = 'data-week';
                            } else if ($consumedDate >= $monthStart) {
                                $dataClass = 'data-month';
                            } else if ($consumedDate >= $yearStart) {
                                $dataClass = 'data-year';
                            }
                    ?>
                    <tr data-category="<?php echo htmlspecialchars($product['categoria']); ?>" data-time="<?php echo $dataClass; ?>" class="<?php echo $verifiedClass; ?>">
                        <td class="product-name">
                            <div class="product-info">
                                <div class="product-icon">
                                    <i class="fas <?php echo $categoryIcon; ?>"></i>
                                    <?php if (isset($product['verified']) && $product['verified']): ?>
                                        <span class="verified-badge" title="Prodotto verificato"><i class="fas fa-check-circle"></i></span>
                                    <?php endif; ?>
                                </div>
                                <span><?php echo htmlspecialchars($product['des']); ?></span>
                            </div>
                        </td>
                        <td><?php echo ucfirst(htmlspecialchars($product['categoria'])); ?></td>
                        <td><?php echo htmlspecialchars($product['marca'] ?? 'N/D'); ?></td>
                        <td><?php echo htmlspecialchars($product['quantita']); ?></td>
                        <td><?php echo formatDate($product['data_consumo']); ?></td>
                        <td class="note-cell">
                            <?php 
                                if (!empty($product["note"])) {
                                    $notes = htmlspecialchars($product['note']);
                                    $maxLength = 50;
                                    
                                    if (strlen($notes) > $maxLength) {
                                        $shortNotes = substr($notes, 0, $maxLength) . '...';
                                        echo "<div class='note-container'>
                                                <span class='note-content truncated'>" . $shortNotes . "</span>
                                                <span class='note-toggle' onclick='toggleNote(this)'>Leggi tutto</span>
                                                <span class='note-full' style='display:none'>" . $notes . "</span>
                                            </div>";
                                    } else {
                                        echo $notes;
                                    }
                                } else {
                                    echo "-";
                                }
                            ?>
                        </td>
                        <td class="action-cell">
                            <button class="delete-btn" title="Rimuovi dalla cronologia" onclick="deleteConsumedProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-consumed">
            <i class="fas fa-utensils"></i>
            <h3>Non hai ancora consumato prodotti!</h3>
            <p>Quando consumi i prodotti dal tuo frigo, questi verranno registrati qui per tenere traccia delle tue abitudini alimentari.</p>
            <button class="back-btn" onclick="location.href='../productsList/productsList.php'">
                <i class="fas fa-arrow-left"></i>
                Torna ai prodotti
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
            <div class="toast-message">Prodotto rimosso con successo.</div>
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
            <div class="modal-message">Sei sicuro di voler rimuovere questo prodotto dalla cronologia dei consumi? Questa azione non può essere annullata.</div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" id="cancel-delete-btn">
                    <i class="fas fa-times"></i> Annulla
                </button>
                <button class="modal-btn modal-btn-danger" id="confirm-delete-btn" onclick="Reload()">
                    <i class="fas fa-trash-alt"></i> Elimina
                </button>
            </div>
        </div>
    </div>`

    <!-- Delete All Confirmation Modal -->
    <div class="modal-overlay" id="delete-all-confirmation-modal">
        <div class="modal-container">
            <div class="modal-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="modal-title">Conferma eliminazione completa</div>
            <div class="modal-message">Sei sicuro di voler eliminare <strong>tutta la cronologia</strong> dei prodotti consumati? Questa azione non può essere annullata.</div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" id="cancel-delete-all-btn">
                    <i class="fas fa-times"></i> Annulla
                </button>
                <button class="modal-btn modal-btn-danger" id="confirm-delete-all-btn">
                    <i class="fas fa-trash-alt"></i> Elimina Tutto
                </button>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <script src="consumedList.js"></script>
</body>
</html>
<?php
    } else {
        header("Location: ../login/login.html");
    }
?>