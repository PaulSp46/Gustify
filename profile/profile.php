<?php 

// Initialize session and check authentication
session_start(); 
if (!isset($_SESSION["email"])) {
    header("Location: ../login/login.html");
    exit;
}

// Database connection
include "../utility/database.php";

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
    /* 
    $sql_consumed = "SELECT p.des as nome, p.categoria, c.data_consumo 
                    FROM consumo c
                    JOIN prodotto p ON c.prodotto_idprodotto = p.idprodotto
                    WHERE c.utente_idutente = :idutente
                    ORDER BY c.data_consumo DESC
                    LIMIT 5";
    $stmt_consumed = $conn->prepare($sql_consumed);
    $stmt_consumed->bindParam(":idutente", $_SESSION["user_id"]);
    $stmt_consumed->execute();
    $consumed_products = $stmt_consumed->fetchAll();
    */
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
    
    <!-- Inline CSS for alerts - consider moving to profile.css -->
    <style>
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-close {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: inherit;
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
                <li><a href="profile.php"><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
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
                        <a href="#" class="view-all">Vedi tutti</a>
                    </div>
                    <p>In sviluppo</p>
                    
                    <!-- Commented out functionality - To be implemented -->
                    <!--
                    <ul class="product-list consumption-history">
                        <?php 
                        // Consumption history implementation - currently commented out
                        ?>
                    </ul>
                    -->
                </div>
                
                <!-- Preferences Section -->
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
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <!-- JavaScript for Interactivity -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
            
            // Logout Confirmation
            logoutBtn.addEventListener('click', function() {
                if (confirm('Sei sicuro di voler effettuare il logout?')) {
                    window.location.href = '../login/logout.php';
                }
            });
            
            // Note: Toggle switch styles are defined in CSS
        });
    </script>
</body>
</html>