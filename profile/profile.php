<?php 

// Initialize session and check authentication
session_start(); 
if (!isset($_SESSION["email"])) {
    header("Location: ../login/login.html");
    exit;
}

// Database connection
include "../utility/database.php";

// Helper function to get category icon
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

// Helper function to get category name
function getCategoryName($category) {
    $categories = [
        'dairy' => 'Latticini',
        'fruit' => 'Frutta',
        'vegetable' => 'Verdura',
        'meat' => 'Carne',
        'fish' => 'Pesce',
        'bakery' => 'Prodotti da forno',
        'beverage' => 'Bevande',
        'snack' => 'Snack',
        'cereal' => 'Cereali',
        'other' => 'Altro'
    ];
    
    return isset($categories[$category]) ? $categories[$category] : 'Altro';
}

// SECTION: Data Retrieval
try {
    // 1. Retrieve user data
    $sql = "SELECT nome, cognome, email FROM utente WHERE idutente = :idutente";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":idutente", $_SESSION["user_id"]);
    $stmt->execute();
    $user = $stmt->fetch();

    // 2. Retrieve product statistics
    $sql_stats = "SELECT
                    COUNT(*) as total_products,
                    SUM(CASE WHEN CURRENT_DATE > scadenza THEN 1 ELSE 0 END) as expired_products,
                    SUM(CASE WHEN DATEDIFF(scadenza, CURRENT_DATE) BETWEEN 0 AND 3 THEN 1 ELSE 0 END) as expiring_soon
                  FROM frigo
                  WHERE utente_idutente = :idutente";
    $stmt_stats = $conn->prepare($sql_stats);
    $stmt_stats->bindParam(":idutente", $_SESSION["user_id"]);
    $stmt_stats->execute();
    $stats = $stmt_stats->fetch();
    
    // 3. Consumed products query (commented out - in development)
    $sql_consumed = "SELECT 
                        p.des as nome, 
                        p.categoria, 
                        c.data_consumo,
                        c.quantita,
                        c.note 
                    FROM consumo c
                    JOIN prodotto p ON c.prodotto_idprodotto = p.idprodotto
                    WHERE c.utente_idutente = :idutente
                    ORDER BY c.data_consumo DESC
                    LIMIT 5";
    $stmt_consumed = $conn->prepare($sql_consumed);
    $stmt_consumed->bindParam(":idutente", $_SESSION["user_id"]);
    $stmt_consumed->execute();
    $consumed_products = $stmt_consumed->fetchAll();
} catch (PDOException $e) {
    // Handle database errors
    $_SESSION["error_message"] = "Errore di database: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gustify - Il tuo profilo</title>
    
    <!-- External CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="profile.css">
    <link rel="icon" href="../tablogo.png">

    <style>
    /* Menu mobile overlay */
    .menu-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99;
        animation: fadeIn 0.3s ease;
        pointer-events: all;
    }

    .menu-overlay.show {
        display: block;
    }

    @media (max-width: 768px) {
        header {
            z-index: 100;
        }
        
        .nav-links {
            position: fixed;
            top: 60px;
            left: 0;
            width: 100%;
            flex-direction: column;
            gap: 0;
            background-color: var(--bg-white);
            box-shadow: var(--shadow-md);
            padding: 0;
            z-index: 101;
            border-top: 1px solid var(--border-color);
            transform: translateY(-100%);
            transition: transform 0.3s ease, opacity 0.3s ease;
            display: flex;
            opacity: 0;
        }
        
        .mobile-menu-icon {
            display: block;
            z-index: 102;
        }
        
        body.menu-open {
            overflow: hidden;
            position: relative;
            height: 100%;
        }
        
        body.menu-open .container * {
            pointer-events: none;
        }
        
        body.menu-open .nav-links *,
        body.menu-open .mobile-menu-icon {
            pointer-events: auto;
        }
    }
    </style>
</head>
<body>
    <!-- Header & Navigation -->
    <header>
        <nav>
            <a href="../index.php" class="logo">
                <i class="fas fa-leaf"></i>
                Gustify
            </a>
            <ul class="nav-links">
                <li><a href="../dashboard/dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                <li><a href="../productsList/productsList.php"><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href="../consumedList/consumedList.php"><i class="fas fa-utensils"></i> Consumati</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <!-- Div overlay per il menu mobile -->
    <div class="menu-overlay" id="menu-overlay"></div>
    
    <!-- Main Content Container -->
    <div class="container">
        <!-- Page Title -->
        <section class="hero">
            <h1>Il tuo profilo</h1>
            <p>Gestisci le tue informazioni personali e monitora le tue statistiche di utilizzo</p>
        </section>
        
        <!-- Alert Messages -->
        <?php if(isset($_SESSION["success_message"])): ?>
        <div class="alert alert-success">
            <div><?php echo htmlspecialchars($_SESSION["success_message"]); ?></div>
            <button class="alert-close"><i class="fas fa-times"></i></button>
        </div>
        <?php unset($_SESSION["success_message"]); ?>
        <?php endif; ?>
        
        <?php if(isset($_SESSION["error_message"])): ?>
        <div class="alert alert-error">
            <div><?php echo htmlspecialchars($_SESSION["error_message"]); ?></div>
            <button class="alert-close"><i class="fas fa-times"></i></button>
        </div>
        <?php unset($_SESSION["error_message"]); ?>
        <?php endif; ?>
        
        <!-- Profile Layout -->
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($user['nome'] . ' ' . $user['cognome']); ?></h2>
                <div class="profile-email"><?php echo htmlspecialchars($user['email']); ?></div>
                
                <!-- Profile Actions -->
                <div class="profile-actions">
                    <button class="profile-btn" id="edit-profile-btn">
                        <i class="fas fa-edit"></i>
                        Modifica Profilo
                    </button>
                    <button class="profile-btn secondary" id="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </button>
                </div>
                
                <!-- Profile Statistics Summary -->
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-label">Prodotti totali</div>
                        <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">In scadenza</div>
                        <div class="stat-value"><?php echo $stats['expiring_soon']; ?></div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Scaduti</div>
                        <div class="stat-value"><?php echo $stats['expired_products']; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content Area -->
            <div class="profile-content">
                <!-- Personal Information Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Informazioni Personali</h2>
                    </div>
                    
                    <!-- Information Display (Default View) -->
                    <div class="profile-info" id="profile-info">
                        <div class="stat-item">
                            <div class="stat-label">Nome</div>
                            <div class="stat-value"><?php echo htmlspecialchars($user['nome']); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Cognome</div>
                            <div class="stat-value"><?php echo htmlspecialchars($user['cognome']); ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Email</div>
                            <div class="stat-value"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                    </div>
                    
                    <!-- Edit Profile Form (Hidden by Default) -->
                    <div class="edit-form" id="edit-form">
                        <form id="update-profile-form" method="post" action="updateProfile.php">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($user['nome']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="cognome">Cognome</label>
                                <input type="text" id="cognome" name="cognome" value="<?php echo htmlspecialchars($user['cognome']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Nuova password (lasciare vuoto per non modificare)</label>
                                <input type="password" id="password" name="password">
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Conferma nuova password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="profile-btn secondary" id="cancel-edit-btn">Annulla</button>
                                <button type="submit" class="profile-btn">Salva modifiche</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Consumption History Section (In Development) -->
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Ultimi prodotti consumati</h2>
                        <a href="../consumedList/consumedList.php" class="view-all">Vedi tutti</a>
                    </div>
                    
                    <ul class="product-list consumption-history">
                        <?php if (count($consumed_products) > 0): ?>
                            <?php foreach ($consumed_products as $product): ?>
                                <li class="product-item">
                                    <div class="product-info">
                                        <div class="product-icon">
                                            <i class="fas <?php echo getCategoryIcon($product['categoria']); ?>"></i>
                                        </div>
                                        <div>
                                            <div class="product-name"><?php echo htmlspecialchars($product['nome']); ?></div>
                                            <div class="date-info">Consumato il <?php echo (new DateTime($product['data_consumo']))->format('d/m/Y H:i'); ?></div>
                                            <?php if (!empty($product['note'])): ?>
                                                <div class="product-note">Note: <?php echo htmlspecialchars($product['note']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="category-tag">
                                        <?php echo getCategoryName($product['categoria']); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="empty-message">Nessun prodotto consumato recentemente.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Preferences Section
                <div class="profile-section">
                    <div class="section-header">
                        <h2>Preferenze</h2>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-label">Notifiche Email</div>
                        <div class="stat-value">
                            <label class="switch">
                                <input type="checkbox" id="email-notifications" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Avvisi prodotti in scadenza</div>
                        <div class="stat-value">
                            <label class="switch">
                                <input type="checkbox" id="expiry-alerts" checked>
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Tema scuro</div>
                        <div class="stat-value">
                            <label class="switch">
                                <input type="checkbox" id="dark-theme">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </div>
                </div>-->
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logout-modal">
        <div class="modal-container">
            <div class="modal-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <div class="modal-title">Conferma Logout</div>
            <div class="modal-message">Sei sicuro di voler effettuare il logout dall'applicazione?</div>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" id="cancel-logout-btn">
                    <i class="fas fa-times"></i> Annulla
                </button>
                <button class="modal-btn modal-btn-primary" id="confirm-logout-btn">
                    <i class="fas fa-check"></i> Conferma
                </button>
            </div>
        </div>
    </div>
    
    <!-- JavaScript for Interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestione menu mobile - VERSIONE CORRETTA
            const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
            const navLinks = document.querySelector('.nav-links');

            // Crea l'elemento overlay se non esiste già
            let menuOverlay = document.querySelector('.menu-overlay');
            if (!menuOverlay) {
                menuOverlay = document.createElement('div');
                menuOverlay.classList.add('menu-overlay');
                document.body.appendChild(menuOverlay);
            }

            // Funzione per aprire il menu
            function openMobileMenu() {
                navLinks.classList.add('show');
                menuOverlay.classList.add('show');
                document.body.classList.add('menu-open'); // Aggiungi questa classe al body
                document.body.style.overflow = 'hidden';
            }

            // Funzione per chiudere il menu
            function closeMobileMenu() {
                navLinks.classList.remove('show');
                menuOverlay.classList.remove('show');
                document.body.classList.remove('menu-open'); // Rimuovi questa classe dal body
                document.body.style.overflow = '';
            }

            if (mobileMenuIcon && navLinks) {
                // Gestisci il toggle del menu
                mobileMenuIcon.addEventListener('click', function(event) {
                    // Previeni la propagazione dell'evento
                    event.stopPropagation();
                    
                    // Controlla se il menu è già aperto
                    if (navLinks.classList.contains('show')) {
                        closeMobileMenu();
                    } else {
                        openMobileMenu();
                    }
                });
                
                // Chiudi il menu quando si clicca sull'overlay
                menuOverlay.addEventListener('click', function(event) {
                    // Previeni la propagazione dell'evento
                    event.stopPropagation();
                    closeMobileMenu();
                });
                
                // Chiudi il menu quando si clicca su un link
                const navLinksItems = document.querySelectorAll('.nav-links a');
                navLinksItems.forEach(item => {
                    item.addEventListener('click', function(event) {
                        // Previeni la propagazione dell'evento
                        event.stopPropagation();
                        closeMobileMenu();
                    });
                });
                
                // Chiudi il menu se si preme ESC
                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && navLinks.classList.contains('show')) {
                        closeMobileMenu();
                    }
                });
            }

            // Mantieni il resto dello script originale qui sotto
            // DOM Elements
            const editProfileBtn = document.getElementById('edit-profile-btn');
            const cancelEditBtn = document.getElementById('cancel-edit-btn');
            const profileInfo = document.getElementById('profile-info');
            const editForm = document.getElementById('edit-form');
            const logoutBtn = document.getElementById('logout-btn');
            const updateProfileForm = document.getElementById('update-profile-form');
            const alertCloseButtons = document.querySelectorAll('.alert-close');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // Logout Modal Elements
            const logoutModal = document.getElementById('logout-modal');
            const cancelLogoutBtn = document.getElementById('cancel-logout-btn');
            const confirmLogoutBtn = document.getElementById('confirm-logout-btn');
            
            // Alert Close Functionality
            alertCloseButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    this.parentElement.style.display = 'none';
                });
            });
            
            // Profile Edit Form Toggle
            editProfileBtn.addEventListener('click', function() {
                profileInfo.style.display = 'none';
                editForm.style.display = 'block';
            });
            
            cancelEditBtn.addEventListener('click', function() {
                profileInfo.style.display = 'block';
                editForm.style.display = 'none';
            });
            
            // Form Validation
            updateProfileForm.addEventListener('submit', function(e) {
                if (passwordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    alert('Le password non corrispondono!');
                }
            });
            
            // Logout Confirmation with Modal
            logoutBtn.addEventListener('click', function() {
                // Show the logout confirmation modal
                logoutModal.classList.add('show');
            });
            
            // Cancel Logout
            cancelLogoutBtn.addEventListener('click', function() {
                logoutModal.classList.remove('show');
            });
            
            // Confirm Logout
            confirmLogoutBtn.addEventListener('click', function() {
                window.location.href = '../login/logout.php';
            });
            
            // Close modal if clicking outside
            logoutModal.addEventListener('click', function(e) {
                if (e.target === logoutModal) {
                    logoutModal.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>