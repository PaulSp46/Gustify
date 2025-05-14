// Variabili globali
let currentProductId = null;
        
// Funzioni per le azioni sui prodotti
function editProduct(productId) {
    window.location.href = `../CRUDfun/frigoEdit.php?id=${productId}`;
}

function consumeProduct(productId) {
    if (confirm('Sei sicuro di voler segnare questo prodotto come consumato?')) {
        // In un'implementazione reale, qui ci sarebbe una chiamata AJAX
        // Per ora simuliamo il successo
        showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.', 'success');
        
        // Rimuovi visivamente il prodotto
        const productCards = document.querySelectorAll('.product-card');
        for (let card of productCards) {
            if (card.querySelector('.consume-btn').getAttribute('onclick').includes(productId)) {
                card.style.opacity = '0';
                setTimeout(() => {
                    card.remove();
                    checkEmptyState();
                }, 300);
                break;
            }
        }
    }
}

// Verifica se tutti i prodotti sono stati rimossi
function checkEmptyState() {
    const productGrid = document.getElementById('product-grid');
    if (!productGrid) return;
    
    if (productGrid.children.length === 0) {
        productGrid.parentNode.innerHTML = `
        <div class="empty-fridge">
            <i class="fas fa-box-open"></i>
            <h3>Il tuo frigo è vuoto!</h3>
            <p>Aggiungi i tuoi prodotti alimentari per iniziare a monitorare le scadenze e ottimizzare i consumi.</p>
            <button class="manual-btn" onclick="location.href='../manualAdd/manualAdd.php'">
                <i class="fas fa-plus"></i>
                Aggiungi prodotti
            </button>
        </div>
        `;
    }
}

// Funzione per espandere le note
function toggleNote(element) {
    const noteContent = element.previousElementSibling;
    const noteFull = element.nextElementSibling;
    
    if (noteContent.classList.contains('truncated')) {
        noteContent.textContent = noteFull.textContent;
        noteContent.classList.remove('truncated');
        element.textContent = 'Leggi meno';
    } else {
        const shortText = noteFull.textContent.substring(0, 70) + '...';
        noteContent.textContent = shortText;
        noteContent.classList.add('truncated');
        element.textContent = 'Leggi tutto';
    }
}

// Toast notification
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

// Filtraggio prodotti
function filterProducts() {
    const categoryFilter = document.getElementById('category-filter');
    const expiryFilter = document.getElementById('expiry-filter');
    const searchInput = document.getElementById('search-product');
    
    const category = categoryFilter.value;
    const expiry = expiryFilter.value;
    const searchTerm = searchInput.value.toLowerCase();
    
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        const cardExpiry = card.getAttribute('data-expiry');
        const cardTitle = card.querySelector('.product-title').textContent.toLowerCase();
        const cardBrand = card.querySelector('.product-brand') ? 
                          card.querySelector('.product-brand').textContent.toLowerCase() : '';
        
        let showCard = true;
        
        // Filtro categoria
        if (category !== 'all' && cardCategory !== category) {
            showCard = false;
        }
        
        // Filtro scadenza
        if (expiry !== 'all') {
            if (expiry === 'critical' && cardExpiry !== 'expiry-critical') {
                showCard = false;
            } else if (expiry === 'soon' && cardExpiry !== 'expiry-soon' && cardExpiry !== 'expiry-critical') {
                showCard = false;
            }
        }
        
        // Filtro ricerca
        if (searchTerm && !cardTitle.includes(searchTerm) && !cardBrand.includes(searchTerm)) {
            showCard = false;
        }
        
        card.style.display = showCard ? 'block' : 'none';
    });
}

// QR Scanner setup
function setupQRScanner() {
    const scanBtn = document.querySelector('.scan-btn');
    const qrReader = document.getElementById('qr-reader');
    const overlay = document.getElementById('overlay');
    const closeBtn = document.getElementById('qr-close-btn');
    
    if (scanBtn && qrReader && overlay && closeBtn) {
        scanBtn.addEventListener('click', function() {
            qrReader.style.display = 'block';
            overlay.style.display = 'block';
            
            // Inizializza lo scanner QR (simulato)
            document.getElementById('qr-reader__status').textContent = 'Scanner pronto. Inquadra un codice QR.';
        });
        
        closeBtn.addEventListener('click', function() {
            qrReader.style.display = 'none';
            overlay.style.display = 'none';
        });
        
        overlay.addEventListener('click', function() {
            qrReader.style.display = 'none';
            overlay.style.display = 'none';
        });
    }
}

// Inizializzazione all'avvio
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuIcon && navLinks) {
        mobileMenuIcon.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }
    
    // Inizializza QR Scanner
    setupQRScanner();
    
    // Listener per i filtri
    const categoryFilter = document.getElementById('category-filter');
    const expiryFilter = document.getElementById('expiry-filter');
    const searchInput = document.getElementById('search-product');
    
    if (categoryFilter && expiryFilter && searchInput) {
        categoryFilter.addEventListener('change', filterProducts);
        expiryFilter.addEventListener('change', filterProducts);
        searchInput.addEventListener('input', filterProducts);
    }
    
    // Verifica parametri URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('message')) {
        const message = urlParams.get('message');
        
        switch (message) {
            case 'updated':
                showToast('Prodotto aggiornato', 'Le modifiche sono state salvate con successo.', 'success');
                break;
            case 'deleted':
                showToast('Prodotto eliminato', 'Il prodotto è stato eliminato con successo.', 'success');
                break;
            case 'consumed':
                showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.', 'success');
                break;
        }
    }
});