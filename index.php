<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gustify - La tua app per la gestione alimentare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <div class="header-container">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Gustify
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="#features">Funzionalità</a></li>
                    <li><a href="#app">App</a></li>
                    <li><a href="#contatti">Contatti</a></li>
                    <li><a href="manual/manual.html">Manuale d'uso</a></li>
                    <li id="login-container" style="display: none;"><a href="login/login.html" class="btn btn-secondary" id="login-button">Accedi</a></li>
                </ul>
            </nav>
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Gestisci la tua alimentazione in modo intelligente</h1>
            <p>Monitora la qualità e la scadenza dei tuoi prodotti alimentari, riduci gli sprechi e migliora le tue abitudini alimentari con Gustify.</p>
            <div class="action-buttons">
                <a href="./dashboard/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-globe"></i>
                    Accedi all'App Web
                </a>
                <a href="#" class="btn btn-secondary" id="download-apk">
                    <i class="fab fa-android"></i>
                    Scarica per Android
                </a>
            </div>
        </div>
        <img src="logo.png" alt="Gustify App" class="hero-image">
    </section>

    <section class="features-section" id="features">
        <div class="section-title">
            <h2>Funzionalità di Gustify</h2>
            <p>Scopri come Gustify può rendere la tua vita più semplice e aiutarti a ridurre gli sprechi alimentari.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Monitoraggio scadenze</h3>
                <p>Tieni sotto controllo le date di scadenza dei tuoi prodotti e ricevi notifiche quando si avvicinano.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Analisi alimentare</h3>
                <p>Ottieni informazioni dettagliate sulla qualità della tua alimentazione e suggerimenti per migliorarla.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h3>Gestione acquisti</h3>
                <p>Importa automaticamente i tuoi acquisti dai supermercati partner tramite QR code sullo scontrino.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <h3>Scansione QR Code</h3>
                <p>Basta uno scan per aggiungere prodotti alla tua dispensa e sincronizzare i tuoi acquisti.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Notifiche intelligenti</h3>
                <p>Ricevi avvisi personalizzati sui prodotti in scadenza e suggerimenti per utilizzarli.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-apple-alt"></i>
                </div>
                <h3>Consigli nutrizionali</h3>
                <p>Ottieni consigli personalizzati per una dieta equilibrata in base ai prodotti che consumi.</p>
            </div>
        </div>
    </section>

    <!--
    <section class="app-section" id="app">
        <div class="app-container">
            <div class="app-image">
                <img src="/api/placeholder/300/600" alt="Gustify App">
            </div>
            <div class="app-content">
                <h2>Scarica l'App Gustify</h2>
                <p>Accedi a tutte le funzionalità di Gustify ovunque tu sia con la nostra app mobile. Disponibile per dispositivi Android.</p>
                <div class="action-buttons">
                    <a href="#" class="btn btn-primary" id="download-apk-2">
                        <i class="fab fa-android"></i>
                        Scarica APK
                    </a>
                    <a href="index.html" class="btn btn-secondary">
                        <i class="fas fa-globe"></i>
                        Usa Versione Web
                    </a>
                </div>
            </div>
        </div>
    </section>
    -->
    <section class="cta-section">
        <div class="cta-container">
            <h2>Inizia a usare Gustify oggi stesso</h2>
            <p>Accedi a tutte le funzionalità di Gustify ovunque tu sia con la nostra app mobile. Disponibile per dispositivi Android.</p>
            <div class="action-buttons" style="justify-content: center;">
                <a href="./dashboard/dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-globe"></i>
                    Accedi all'App Web
                </a>
                <a href="#" class="btn btn-primary" id="download-apk-3">
                    <i class="fab fa-android"></i>
                    Scarica per Android
                </a>
            </div>
        </div>
    </section>

    <footer id="contatti">
        <div class="footer-container">
            <div class="footer-info">
                <div class="footer-logo">
                    <i class="fas fa-leaf"></i>
                    Gustify
                </div>
                <p>Gestisci la tua alimentazione in modo intelligente e riduci gli sprechi alimentari.</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h3>Link Utili</h3>
                <ul>
                    <li><a href="#features">Funzionalità</a></li>
                    <li><a href="#app">App</a></li>
                    <li><a href="#">Privacy Policy</a></li>
                    <li><a href="#">Termini di Servizio</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h3>Supporto</h3>
                <ul>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Centro Assistenza</a></li>
                    <li><a href="#">Contattaci</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h3>Contatti</h3>
                <ul>
                    <li><i class="fas fa-envelope"></i> info@gustify.it</li>
                    <li><i class="fas fa-phone"></i> +39 123 456 7890</li>
                    <li><i class="fas fa-map-marker-alt"></i> Via Roma 123, Milano</li>
                </ul>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
            </div>
        </div>
    </footer>

    <!-- Download Modal -->
    <div class="modal" id="download-modal">
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <h2 style="color: var(--primary-color); margin-bottom: 1rem;">Scarica Gustify per Android</h2>
            <p style="margin-bottom: 1.5rem;">Scegli uno dei seguenti metodi per scaricare l'app Gustify:</p>
            
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <a href="gustify.apk" download class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    Download Diretto APK
                </a>
            </div>
            
            <p style="margin-top: 1.5rem; font-size: 0.9rem; color: #666;">
                Nota: Per installare l'APK, potrebbe essere necessario abilitare l'installazione da fonti sconosciute nelle impostazioni del tuo dispositivo Android.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if email exists in session
            function checkSession() {
                // Simulate PHP session check with JavaScript
                // In a real application, you would use PHP to check the session
                // This is just for demonstration purposes
                
                // Check if email is in session storage (for demo purposes)
                const emailInSession = sessionStorage.getItem('email');
                const loginContainer = document.getElementById('login-container');
                
                if (emailInSession) {
                    loginContainer.style.display = 'block';
                } else {
                    loginContainer.style.display = 'none';
                }
            }
            
            // Call the check session function when page loads
            checkSession();
            
            // Mobile menu toggle with improved animation
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mainNav = document.querySelector('.main-nav');
            
            mobileMenuToggle.addEventListener('click', function() {
                if (mainNav.style.display === 'block') {
                    mainNav.style.opacity = '0';
                    setTimeout(() => {
                        mainNav.style.display = 'none';
                    }, 300);
                } else {
                    mainNav.style.display = 'block';
                    setTimeout(() => {
                        mainNav.style.opacity = '1';
                    }, 10);
                }
            });
            
            // Download buttons
            const downloadButtons = document.querySelectorAll('#download-apk, #download-apk-2, #download-apk-3');
            const downloadModal = document.getElementById('download-modal');
            const closeModal = document.querySelector('.close-modal');
            
            downloadButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    downloadModal.style.display = 'flex';
                    setTimeout(() => {
                        downloadModal.querySelector('.modal-content').style.transform = 'scale(1)';
                        downloadModal.querySelector('.modal-content').style.opacity = '1';
                    }, 10);
                });
            });
            
            closeModal.addEventListener('click', function() {
                downloadModal.querySelector('.modal-content').style.transform = 'scale(0.9)';
                downloadModal.querySelector('.modal-content').style.opacity = '0';
                setTimeout(() => {
                    downloadModal.style.display = 'none';
                }, 300);
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === downloadModal) {
                    downloadModal.querySelector('.modal-content').style.transform = 'scale(0.9)';
                    downloadModal.querySelector('.modal-content').style.opacity = '0';
                    setTimeout(() => {
                        downloadModal.style.display = 'none';
                    }, 300);
                }
            });
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    if (this.getAttribute('href') !== '#') {
                        e.preventDefault();
                        const targetElement = document.querySelector(this.getAttribute('href'));
                        const headerOffset = 80;
                        const elementPosition = targetElement.getBoundingClientRect().top;
                        const offsetPosition = elementPosition - headerOffset;

                        window.scrollBy({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Add scroll animations
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.feature-card, .section-title, .app-image, .app-content');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;
                    
                    if (elementPosition < windowHeight - 100) {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }
                });
            };
            
            // Add initial styles for animation
            document.querySelectorAll('.feature-card, .section-title, .app-image, .app-content').forEach(element => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            });
            
            // Run once on page load
            setTimeout(animateOnScroll, 300);
            
            // Add event listener for scroll
            window.addEventListener('scroll', animateOnScroll);
        });
    </script>
</body>
</html>