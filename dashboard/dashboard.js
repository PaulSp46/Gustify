// Sostituisci completamente il contenuto del file dashboard.js con questo

// Definisci tutte le funzioni globali FUORI dal DOMContentLoaded
// Così saranno disponibili immediatamente per gli attributi onclick

// Variabile globale per l'ID del prodotto corrente
window.currentProductId = null;

// Funzione per il menu contestuale
window.showProductActions = function(productId, e) {
    // Cattura l'evento
    var event = e || window.event;
    
    // Memorizza l'ID prodotto
    window.currentProductId = productId;
    
    // Ottieni l'elemento cliccato
    var icon = event.target || event.srcElement;
    var rect = icon.getBoundingClientRect();
    
    // Trova e posiziona il popup
    var actionsPopup = document.getElementById('product-actions-popup');
    actionsPopup.style.display = 'block';
    actionsPopup.style.top = (rect.bottom + window.scrollY) + 'px';
    actionsPopup.style.left = (rect.left - 120) + 'px';
    
    // Aggiungi handler per chiusura
    setTimeout(function() {
        document.addEventListener('click', function closeMenu(evt) {
            if (!actionsPopup.contains(evt.target) && !evt.target.classList.contains('fa-ellipsis-v')) {
                actionsPopup.style.display = 'none';
                document.removeEventListener('click', closeMenu);
            }
        });
    }, 10);
    
    // Previeni la propagazione dell'evento
    if (event.stopPropagation) event.stopPropagation();
    else event.cancelBubble = true;
    
    return false;
};

// Altre funzioni globali necessarie
window.consumeProduct = function(productId) {
    var actionsPopup = document.getElementById('product-actions-popup');
    if (actionsPopup) {
        actionsPopup.style.display = 'none';
    }
    
    // AJAX request to consume the product
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../CRUDfun/consumeProduct.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                var response = JSON.parse(this.responseText);
                if (response.success) {
                    showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.');
                    
                    // Trova e rimuovi il prodotto dalla lista
                    var productElements = document.querySelectorAll('.product-item');
                    for (var i = 0; i < productElements.length; i++) {
                        var product = productElements[i];
                        var actionElement = product.querySelector('.product-actions');
                        if (actionElement && actionElement.getAttribute('onclick').indexOf(productId) > -1) {
                            product.style.opacity = '0';
                            setTimeout(function(elem) {
                                return function() {
                                    elem.remove();
                                    checkEmptyList();
                                };
                            }(product), 300);
                            break;
                        }
                    }
                } else {
                    showToast('Errore', response.message || 'Si è verificato un errore durante il consumo del prodotto.', 'error');
                }
            } catch (e) {
                showToast('Errore', 'Si è verificato un errore durante l\'elaborazione della risposta.', 'error');
            }
        } else {
            showToast('Errore', 'Si è verificato un errore durante il consumo del prodotto.', 'error');
        }
    };
    xhr.onerror = function() {
        showToast('Errore', 'Si è verificato un errore di rete durante il consumo del prodotto.', 'error');
    };
    xhr.send('relation_id=' + productId);
};

window.editProduct = function(productId) {
    var actionsPopup = document.getElementById('product-actions-popup');
    actionsPopup.style.display = 'none';
    location.href = "../CRUDfun/frigoEdit.php?id=" + productId;
};

window.deleteProduct = function(productId) {
    var actionsPopup = document.getElementById('product-actions-popup');
    actionsPopup.style.display = 'none';
    
    // Store the product ID for the modal
    window.productToDelete = productId;
    
    // Show the delete confirmation modal
    var deleteModal = document.getElementById('delete-confirmation-modal');
    deleteModal.classList.add('show');
};

// Funzione utility per i toast
// Funzione utility per i toast
function showToast(title, message, type) {
    var toast = document.getElementById('toast');
    if (!toast) return;
    
    var toastTitle = toast.querySelector('.toast-title');
    var toastMessage = toast.querySelector('.toast-message');
    var toastIcon = toast.querySelector('.toast-icon i');
    
    if (toastTitle) toastTitle.textContent = title;
    if (toastMessage) toastMessage.textContent = message;
    
    // Update icon based on type
    if (toastIcon) {
        toastIcon.className = type === 'error' 
            ? 'fas fa-exclamation-circle' 
            : 'fas fa-check-circle';
    }
    
    // Add appropriate class for styling
    toast.className = 'toast show';
    if (type === 'error') {
        toast.style.borderLeftColor = 'var(--error-color)';
        toastIcon.style.color = 'var(--error-color)';
    } else {
        toast.style.borderLeftColor = 'var(--success-color)';
        toastIcon.style.color = 'var(--success-color)';
    }
    
    setTimeout(function() {
        toast.classList.remove('show');
    }, 3000);
}

// Funzione per verificare se la lista è vuota
function checkEmptyList() {
    var productList = document.querySelector('.product-list');
    if (productList && productList.children.length === 0) {
        var emptyMessage = document.createElement('li');
        emptyMessage.className = 'empty-message';
        emptyMessage.textContent = 'Nessun prodotto nel tuo frigo.';
        productList.appendChild(emptyMessage);
    }
}

// Ora metti il resto del codice che dipende dal DOM nell'event listener
document.addEventListener('DOMContentLoaded', function() {
    const scanBtn = document.querySelector('.scan-btn');
    const qrReader = document.getElementById('qr-reader');
    const overlay = document.getElementById('overlay');
    const qrCloseBtn = document.getElementById('qr-close-btn');
    const successMessage = document.getElementById('success-message');
    const successCloseBtn = document.getElementById('success-close-btn');
    const scannedProductsList = document.getElementById('scanned-products');
    const actionsPopup = document.getElementById('product-actions-popup');
    const toastCloseBtn = document.querySelector('.toast-close');
    
    // Delete confirmation modal elements
    const deleteModal = document.getElementById('delete-confirmation-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

    // Handler for cancel delete button
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.remove('show');
            window.productToDelete = null;
        });
    }
    
    // Handler for confirm delete button
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (window.productToDelete) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', '../CRUDfun/frigoDelete.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        try {
                            var response = JSON.parse(this.responseText);
                            if (response.success) {
                                location.reload();
                            } else {
                                showToast('Errore', response.message || 'Si è verificato un errore durante l\'eliminazione del prodotto.');
                            }
                        } catch (e) {
                            showToast('Errore', 'Si è verificato un errore durante l\'elaborazione della risposta.');
                        }
                    } else {
                        showToast('Errore', 'Si è verificato un errore durante l\'eliminazione del prodotto.');
                    }
                };
                xhr.onerror = function() {
                    showToast('Errore', 'Si è verificato un errore di rete durante l\'eliminazione del prodotto.');
                };
                xhr.send('relation_id=' + window.productToDelete);
                
                // Hide the modal
                deleteModal.classList.remove('show');
                window.productToDelete = null;
            }
        });
    }
    
    // Close modal when clicking outside
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.classList.remove('show');
                window.productToDelete = null;
            }
        });
    }

    let html5QrCode = null;

    // ——— NOTIFICHE SCADENZE ———

    // 1. Chiedo permesso alle notifiche
    if ('Notification' in window) {
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    // 2. Funzione che interroga notifications.php e mostra le notifiche
    function checkExpiryNotifications() {
        if (Notification.permission !== 'granted') return;
      
        const xhr = new XMLHttpRequest();
        xhr.open('GET', '../utility/notification.php', true);
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    let data;
                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (e) {
                        return console.error('Risposta JSON non valida', e);
                    }
                    data.forEach(item => {
                        const title = `Prodotto in scadenza`;
                        const body  = `${item.name} scade tra ${item.daysLeft} giorno${item.daysLeft > 1 ? 'i' : ''}.`;
                        const key = `notified_expiry_${item.id}`;
                        if (!localStorage.getItem(key)) {
                            new Notification(title, { body, icon: '/path/to/icon.png' });
                            localStorage.setItem(key, '1');
                        }
                    });
                } else if (xhr.status === 401) {
                    console.error('Non autenticato per le notifiche.');
                } else {
                    console.error('Errore server notifiche:', xhr.status);
                }
            }
        };
        xhr.onerror = function() {
            console.error('Errore di rete durante richiesta notifiche.');
        };
        xhr.send();
    }
    
    // 3. Lancio subito al caricamento e poi ogni 6 ore
    checkExpiryNotifications();
    setInterval(checkExpiryNotifications, 6 * 60 * 60 * 1000);

    function isMobileDevice() {
        return (
            typeof window.orientation !== "undefined" ||
            /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
        );
    }

    function handleScanButton() {
        if (!isMobileDevice()) {
            alert('La scansione del QR code è disponibile solo su dispositivi mobili.');
            return;
        }

        overlay.style.display = 'block';
        qrReader.style.display = 'block';

        html5QrCode = new Html5Qrcode("qr-reader__camera");
        const qrConfig = { fps: 10, qrbox: { width: 250, height: 250 } };

        Html5Qrcode.getCameras()
            .then(devices => {
                if (!devices.length) {
                    document.getElementById('qr-reader__status').innerText = 'Nessuna fotocamera rilevata';
                    return;
                }
                const cameraSelection = document.getElementById('qr-reader__camera-selection');
                cameraSelection.innerHTML = '';
                devices.forEach(device => {
                    const opt = document.createElement('option');
                    opt.value = device.id;
                    opt.text = device.label || `Camera ${cameraSelection.length + 1}`;
                    cameraSelection.appendChild(opt);
                });
                const defaultCam = devices.find(d => /back/i.test(d.label)) || devices[0];
                startScanner(defaultCam.id);

                cameraSelection.addEventListener('change', function() {
                    html5QrCode.stop().then(() => startScanner(this.value));
                });
            })
            .catch(err => {
                document.getElementById('qr-reader__status').innerText = `Errore: ${err}`;
            });

        function startScanner(cameraId) {
            document.getElementById('qr-reader__status').innerText = 'Scanner attivo…';
            html5QrCode.start(cameraId, qrConfig, onScanSuccess, onScanFailure);
        }

        function onScanSuccess(decodedText) {
            html5QrCode.stop().then(() => {
                let qrData;
                try {
                    qrData = JSON.parse(decodedText);
                } catch {
                    return alert('Formato QR code non riconosciuto.');
                }
                if (!qrData.userId || !Array.isArray(qrData.products)) {
                    return alert('QR code non valido per Gustify.');
                }
        
                // Popolo la lista di prodotti scansionati
                scannedProductsList.innerHTML = '';
                qrData.products.forEach(p => {
                    const li = document.createElement('li');
                    li.className = 'product-item';
                    li.innerHTML = `
                        <div class="product-info">
                            <div class="product-icon">
                                <i class="fas ${getProductIcon(p.category)}"></i>
                            </div>
                            <div>
                                <div class="product-name">${p.name}</div>
                                <div class="product-expiry">Scade il ${formatDate(p.expiryDate)}</div>
                            </div>
                        </div>`;
                    scannedProductsList.appendChild(li);
                });
        
                // Chiudo scanner e mostro overlay
                qrReader.style.display = 'none';
                successMessage.style.display = 'block';
        
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../utility/updateDB_qr.php', true);
                xhr.setRequestHeader('Content-Type', 'application/json');
        
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            let resp;
                            try {
                                resp = JSON.parse(xhr.responseText);
                            } catch {
                                return alert('Risposta non valida dal server.');
                            }
                            if (resp.success) {
                                console.log('Prodotti aggiunti:', resp.added);
                                // qui puoi aggiornare dinamicamente la UI o ricaricare la pagina
                            } else {
                                alert('Errore: ' + (resp.error || 'Errore sconosciuto'));
                            }
                        } else if (xhr.status === 401) {
                            alert('Devi effettuare il login per aggiungere prodotti.');
                            window.location.href = '../login/login.php';
                        } else {
                            alert('Errore di server (' + xhr.status + '). Riprova più tardi.');
                        }
                    }
                };
        
                xhr.onerror = function() {
                    alert('Errore di rete durante l\'invio dei dati.');
                };
        
                // Preparo il payload: solo l'array products
                const payload = JSON.stringify({ products: qrData.products });
                xhr.send(payload);
            });
        }

        function onScanFailure(error) {
            // ignoro, continuo a scansionare
        }
    }

    if (scanBtn) {
        scanBtn.addEventListener('click', handleScanButton);
    }

    if (qrCloseBtn) {
        qrCloseBtn.addEventListener('click', function() {
            if (html5QrCode) {
                html5QrCode.stop().catch(() => {});
            }
            qrReader.style.display = 'none';
            overlay.style.display = 'none';
        });
    }

    if (successCloseBtn) {
        successCloseBtn.addEventListener('click', function() {
            successMessage.style.display = 'none';
            overlay.style.display = 'none';
        });
    }

    if (toastCloseBtn) {
        toastCloseBtn.addEventListener('click', function() {
            document.getElementById('toast').classList.remove('show');
        });
    }

    // ——— HELPERS ———
    function getProductIcon(category) {
        const icons = {
            dairy: 'fa-cheese',
            fruit: 'fa-apple-alt',
            vegetable: 'fa-carrot',
            meat: 'fa-drumstick-bite',
            fish: 'fa-fish',
            bakery: 'fa-bread-slice',
            beverage: 'fa-wine-bottle',
            snack: 'fa-cookie'
        };
        return icons[category] || 'fa-shopping-basket';
    }

    function formatDate(dateString) {
        const d = new Date(dateString);
        return d.toLocaleDateString('it-IT', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    }
});