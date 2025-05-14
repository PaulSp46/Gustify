// Sostituisci completamente il contenuto del file dashboard.js con questo

// Variabile globale per l'ID del prodotto corrente
window.currentProductId = null;
window.productToConsume = null;

// Funzione per il menu contestuale
window.showProductActions = function(productId, e) {
    console.log("showProductActions chiamato per productId:", productId);
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
    console.log("---- INIZIO PROCESSO CONSUMAZIONE ----");
    console.log("Consumazione prodotto ID:", productId);
    
    var actionsPopup = document.getElementById('product-actions-popup');
    if (actionsPopup) {
        actionsPopup.style.display = 'none';
    }
    
    // Find the product list item
    let productItem = null;
    const productItems = document.querySelectorAll('.product-item');
    
    console.log("Numero elementi prodotto trovati:", productItems.length);
    
    for (let item of productItems) {
        const actionElement = item.querySelector('.product-actions');
        if (actionElement) {
            // Estrarre l'ID del prodotto dall'attributo onclick
            const onclickAttr = actionElement.getAttribute('onclick');
            console.log("Attributo onclick:", onclickAttr);
            
            if (onclickAttr) {
                // Cercare un pattern come "showProductActions(52, event)"
                const match = onclickAttr.match(/showProductActions\((\d+)/);
                if (match && match[1] == productId) {
                    productItem = item;
                    console.log("Elemento prodotto trovato per ID:", productId);
                    break;
                }
            }
        }
    }
    
    if (!productItem) {
        showToast('Errore', 'Prodotto non trovato.', 'error');
        console.error("Elemento prodotto non trovato per ID:", productId);
        return;
    }
    
    // Get the product name for display in the modal
    const productName = productItem.querySelector('.product-name').textContent;
    console.log("Nome prodotto:", productName);
    
    // Show the consumption modal
    const consumeModal = document.getElementById('consume-modal');
    const quantityInput = document.getElementById('consume-quantity');
    const productNameElement = document.getElementById('consume-product-name');
    
    // Update modal content
    productNameElement.textContent = productName;
    quantityInput.value = "1";
    
    // Store the product ID for the confirmation
    window.productToConsume = productId;
    
    // Show the modal
    consumeModal.classList.add('show');
};

window.confirmConsumption = function() {
    console.log("confirmConsumption chiamato - ID prodotto:", window.productToConsume);
    
    const consumeModal = document.getElementById('consume-modal');
    const quantityInput = document.getElementById('consume-quantity');
    const consumeQuantity = parseInt(quantityInput.value);
    
    console.log("Quantità da consumare:", consumeQuantity);
    
    if (isNaN(consumeQuantity) || consumeQuantity <= 0) {
        showToast('Errore', 'Quantità non valida.', 'error');
        console.error("Errore: quantità non valida");
        return;
    }
    
    // AJAX request to consume the product
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '../CRUDfun/consumeProduct.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        console.log("Risposta ricevuta dal server - Status:", this.status);
        console.log("Risposta completa:", this.responseText);
        
        if (this.status === 200) {
            try {
                var response = JSON.parse(this.responseText);
                console.log("Risposta parsata:", response);
                console.log("Success flag:", response.success);
                console.log("Consumed all flag:", response.consumed_all);
                
                if (response.success) {
                    showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.');
                    
                    // Cerchiamo il prodotto nella UI
                    var productId = window.productToConsume;
                    var productElements = document.querySelectorAll('.product-item');
                    console.log("Cerco prodotto ID:", productId, "tra", productElements.length, "elementi");
                    
                    var trovato = false;
                    for (var i = 0; i < productElements.length; i++) {
                        var product = productElements[i];
                        var actionElement = product.querySelector('.product-actions');
                        
                        if (actionElement) {
                            const onclickAttr = actionElement.getAttribute('onclick');
                            const match = onclickAttr.match(/showProductActions\((\d+)/);
                            console.log("Controllo elemento", i, "- onclick:", onclickAttr);
                            
                            if (match && match[1] == productId) {
                                console.log("Prodotto trovato in posizione", i);
                                trovato = true;
                                
                                if (response.consumed_all) {
                                    console.log("Rimuovo completamente il prodotto con animazione");
                                    
                                    // Prepariamo l'elemento per l'animazione
                                    product.style.transition = "all 0.5s ease-out";
                                    product.style.height = product.offsetHeight + "px";
                                    product.style.overflow = "hidden";
                                    
                                    // Prima fase dell'animazione - fade out e slide
                                    product.style.opacity = "0";
                                    product.style.transform = "translateX(-30px)";
                                    
                                    // Seconda fase dell'animazione - riduzione altezza
                                    setTimeout(function() {
                                        product.style.height = "0";
                                        product.style.marginTop = "0";
                                        product.style.marginBottom = "0";
                                        product.style.paddingTop = "0";
                                        product.style.paddingBottom = "0";
                                        
                                        // Rimozione finale dell'elemento
                                        setTimeout(function() {
                                            product.remove();
                                            console.log("Elemento rimosso dal DOM");
                                            checkEmptyList();
                                        }, 300);
                                    }, 300);
                                } else {
                                    console.log("Aggiorno la quantità parziale");
                                    console.log("Quantità rimanente:", response.remaining_quantity);
                                    
                                    // Trova l'elemento info dentro product-info
                                    var productInfo = product.querySelector('.product-info');
                                    var infoDiv = productInfo ? productInfo.querySelector('div:nth-child(2)') : null;
                                    
                                    if (infoDiv) {
                                        console.log("Elemento info trovato");
                                        
                                        // Cerca un elemento quantità esistente o creane uno nuovo
                                        var quantityElem = infoDiv.querySelector('.product-quantity');
                                        if (!quantityElem) {
                                            console.log("Creo nuovo elemento quantità");
                                            quantityElem = document.createElement('div');
                                            quantityElem.className = 'product-quantity';
                                            infoDiv.appendChild(quantityElem);
                                        }
                                        
                                        // Aggiorna il testo della quantità
                                        quantityElem.textContent = 'Quantità: ' + response.remaining_quantity;
                                        console.log("Testo quantità aggiornato:", quantityElem.textContent);
                                        
                                        // Evidenzia l'aggiornamento con un effetto visivo pulsante
                                        quantityElem.style.animation = "pulseHighlight 1s ease";
                                        
                                        // Evidenzia anche l'intero prodotto
                                        product.style.transition = "background-color 0.5s ease";
                                        const originalColor = product.style.backgroundColor;
                                        product.style.backgroundColor = "rgba(76, 175, 80, 0.1)";
                                        
                                        setTimeout(function() {
                                            product.style.backgroundColor = originalColor;
                                            // Rimuovi l'animazione per consentire di ripeterla in futuro
                                            quantityElem.style.animation = "none";
                                        }, 1000);
                                    } else {
                                        console.error("Elemento info non trovato");
                                        // Se non riusciamo a trovare l'elemento, ricarichiamo la pagina
                                        setTimeout(function() {
                                            location.reload();
                                        }, 1000);
                                    }
                                }
                                break;
                            }
                        }
                    }
                    
                    if (!trovato) {
                        console.error("ERRORE: Prodotto non trovato nella UI dopo la risposta!");
                        // Se non troviamo il prodotto, ricarichiamo la pagina
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                } else {
                    console.error("Errore restituito dal server:", response.message);
                    showToast('Errore', response.message || 'Si è verificato un errore durante il consumo del prodotto.', 'error');
                }
            } catch (e) {
                console.error("ERRORE nel parsing JSON:", e, "Risposta del server:", this.responseText);
                showToast('Errore', 'Si è verificato un errore durante l\'elaborazione della risposta.', 'error');
            }
        } else {
            console.error("Errore HTTP:", this.status);
            showToast('Errore', 'Si è verificato un errore durante il consumo del prodotto.', 'error');
        }
        
        console.log("---- FINE PROCESSO CONSUMAZIONE ----");
    };
    xhr.onerror = function() {
        console.error("Errore di rete nella richiesta");
        showToast('Errore', 'Si è verificato un errore di rete durante il consumo del prodotto.', 'error');
    };
    
    const payload = 'relation_id=' + window.productToConsume + '&consume_quantity=' + consumeQuantity;
    console.log("Invio payload:", payload);
    xhr.send(payload);
    
    // Hide the modal
    consumeModal.classList.remove('show');
    
    // Non resettare immediatamente le variabili per evitare problemi con le risposte asincrone
    setTimeout(function() {
        window.productToConsume = null;
    }, 2000);
};

window.cancelConsumption = function() {
    const consumeModal = document.getElementById('consume-modal');
    consumeModal.classList.remove('show');
    window.productToConsume = null;
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
    
    // Aggiorniamo lo stile prima di mostrare il toast
    toast.className = 'toast';
    if (type === 'error') {
        toast.style.borderLeftColor = 'var(--error-color)';
        toastIcon.style.color = 'var(--error-color)';
    } else {
        toast.style.borderLeftColor = 'var(--success-color)';
        toastIcon.style.color = 'var(--success-color)';
    }
    
    // Reset delle animazioni
    toast.style.animation = 'none';
    // Forza reflow
    void toast.offsetWidth;
    
    // Aggiungi classe show con animazione
    toast.classList.add('show');
    toast.style.animation = 'toastSlideIn 0.3s ease forwards';
    
    setTimeout(function() {
        // Animazione in uscita
        toast.style.animation = 'toastSlideOut 0.3s ease forwards';
        setTimeout(function() {
            toast.classList.remove('show');
        }, 300);
    }, 3000);
}

// Funzione per verificare se la lista è vuota
function checkEmptyList() {
    var productList = document.querySelector('.product-list');
    if (productList && productList.children.length === 0) {
        var emptyMessage = document.createElement('li');
        emptyMessage.className = 'empty-message';
        emptyMessage.textContent = 'Nessun prodotto nel tuo frigo.';
        emptyMessage.style.opacity = '0';
        productList.appendChild(emptyMessage);
        
        // Animazione fade-in
        setTimeout(function() {
            emptyMessage.style.transition = 'opacity 0.5s ease';
            emptyMessage.style.opacity = '1';
        }, 10);
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
                                // Troviamo l'elemento e lo rimuoviamo invece di ricaricare
                                var productElements = document.querySelectorAll('.product-item');
                                var found = false;
                                
                                for (var i = 0; i < productElements.length; i++) {
                                    var product = productElements[i];
                                    var actionElement = product.querySelector('.product-actions');
                                    
                                    if (actionElement) {
                                        const onclickAttr = actionElement.getAttribute('onclick');
                                        const match = onclickAttr.match(/showProductActions\((\d+)/);
                                        
                                        if (match && match[1] == window.productToDelete) {
                                            found = true;
                                            
                                            // Animazione di rimozione
                                            product.style.transition = "all 0.5s ease-out";
                                            product.style.height = product.offsetHeight + "px";
                                            product.style.overflow = "hidden";
                                            
                                            // Prima fase dell'animazione - fade out e slide
                                            product.style.opacity = "0";
                                            product.style.transform = "translateX(-30px)";
                                            
                                            // Seconda fase dell'animazione - riduzione altezza
                                            setTimeout(function() {
                                                product.style.height = "0";
                                                product.style.marginTop = "0";
                                                product.style.marginBottom = "0";
                                                product.style.paddingTop = "0";
                                                product.style.paddingBottom = "0";
                                                
                                                // Rimozione finale dell'elemento
                                                setTimeout(function() {
                                                    product.remove();
                                                    checkEmptyList();
                                                    showToast('Successo', 'Prodotto eliminato con successo.');
                                                }, 300);
                                            }, 300);
                                            break;
                                        }
                                    }
                                }
                                
                                if (!found) {
                                    location.reload();
                                }
                            } else {
                                showToast('Errore', response.message || 'Si è verificato un errore durante l\'eliminazione del prodotto.', 'error');
                            }
                        } catch (e) {
                            showToast('Errore', 'Si è verificato un errore durante l\'elaborazione della risposta.', 'error');
                        }
                    } else {
                        showToast('Errore', 'Si è verificato un errore durante l\'eliminazione del prodotto.', 'error');
                    }
                };
                xhr.onerror = function() {
                    showToast('Errore', 'Si è verificato un errore di rete durante l\'eliminazione del prodotto.', 'error');
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
    
    // Aggiungiamo uno style per le animazioni
    if (!document.getElementById('dashboard-animations')) {
        const style = document.createElement('style');
        style.id = 'dashboard-animations';
        style.textContent = `
            @keyframes pulseHighlight {
                0% { background-color: transparent; }
                50% { background-color: rgba(76, 175, 80, 0.3); }
                100% { background-color: transparent; }
            }
            
            @keyframes toastSlideIn {
                0% { transform: translateX(150%); opacity: 0; }
                100% { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes toastSlideOut {
                0% { transform: translateX(0); opacity: 1; }
                100% { transform: translateX(150%); opacity: 0; }
            }
            
            .product-item {
                transition: all 0.3s ease;
            }
            
            .toast {
                transition: all 0.3s ease;
            }
            
            .empty-message {
                transition: opacity 0.5s ease;
            }
        `;
        document.head.appendChild(style);
    }
});