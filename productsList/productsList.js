document.addEventListener('DOMContentLoaded', function() {
    const scanBtn = document.querySelector('.scan-btn');
    const qrReader = document.getElementById('qr-reader');
    const overlay = document.getElementById('overlay');
    const qrCloseBtn = document.getElementById('qr-close-btn');
    const successMessage = document.getElementById('success-message');
    const successCloseBtn = document.getElementById('success-close-btn');
    const scannedProductsList = document.getElementById('scanned-products');
    const actionsPopup = document.getElementById('product-actions-popup');

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
    


    let html5QrCode = null;
    window.currentProductId = null;

    // Funzione per mostrare/nascondere il menu delle azioni sui prodotti
    window.showProductActions = function(productId) {
        window.currentProductId = productId;
        
        const icon = event.target;
        const rect = icon.getBoundingClientRect();
        
        actionsPopup.style.display = 'block';
        actionsPopup.style.top = rect.bottom + 'px';
        actionsPopup.style.left = rect.left + 'px';
        
        // Aggiungi un evento per chiudere il popup quando si clicca altrove
        document.addEventListener('click', closeActionsPopup);
        
        // Previeni la propagazione dell'evento click per evitare che il popup si chiuda immediatamente
        event.stopPropagation();
    };
    
    function closeActionsPopup(event) {
        if (!actionsPopup.contains(event.target) && event.target.className !== 'fas fa-ellipsis-v') {
            actionsPopup.style.display = 'none';
            document.removeEventListener('click', closeActionsPopup);
        }
    }
    
    // Funzioni per le azioni sui prodotti
    window.consumeProduct = function(productId) {
        // Qui dovresti implementare la logica per marcare il prodotto come consumato
        // Per ora simuliamo un successo
        document.getElementById('product-actions-popup').style.display = 'none';
        showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.');
        
        // Rimuovi l'elemento dalla lista (simulazione)
        const productElements = document.querySelectorAll('.product-item');
        for (let product of productElements) {
            if (product.querySelector('.product-actions i').getAttribute('onclick').includes(productId)) {
                product.style.opacity = '0';
                setTimeout(() => {
                    product.remove();
                    checkEmptyList();
                }, 300);
                break;
            }
        }
    }
    
    window.editProduct = function(productId) {
        actionsPopup.style.display = 'none';
        location.href = `../CRUDfun/frigoEdit.php?id=${productId}`;
    };
    
    window.deleteProduct = function(productId) {
        actionsPopup.style.display = 'none';
        if (confirm('Sei sicuro di voler eliminare questo prodotto?')) {
            // Invia una richiesta AJAX per eliminare il prodotto
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../CRUDfun/frigoDelete.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                } else {
                    alert('Si è verificato un errore durante l\'eliminazione del prodotto.');
                }
            };
            xhr.onerror = function() {
                alert('Si è verificato un errore di rete durante l\'eliminazione del prodotto.');
            };
            xhr.send('relation_id=' + productId);
        }
    };

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
        
                // Preparo il payload: solo l’array products
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

document.addEventListener('DOMContentLoaded', function() {
    // Filtro per categoria
    const categoryFilter = document.getElementById('category-filter');
    const expiryFilter = document.getElementById('expiry-filter');
    const searchBox = document.getElementById('search-product');
    const productGrid = document.getElementById('product-grid');
    
    if (categoryFilter && expiryFilter && searchBox && productGrid) {
        const productCards = document.querySelectorAll('.product-card');
        
        function filterProducts() {
            const categoryValue = categoryFilter.value;
            const expiryValue = expiryFilter.value;
            const searchValue = searchBox.value.toLowerCase();
            
            productCards.forEach(card => {
                const category = card.dataset.category;
                const expiryElement = card.querySelector('.product-expiry');
                const productName = card.querySelector('.product-title').textContent.toLowerCase();
                
                // Filtro per categoria
                let showByCategory = categoryValue === 'all' || category === categoryValue;
                
                // Filtro per scadenza
                let showByExpiry = true;
                if (expiryValue !== 'all') {
                    showByExpiry = expiryElement.classList.contains(`expiry-${expiryValue}`);
                }
                
                // Filtro per ricerca
                let showBySearch = searchValue === '' || productName.includes(searchValue);
                
                // Mostra o nascondi la card
                card.style.display = (showByCategory && showByExpiry && showBySearch) ? 'block' : 'none';
            });
        }
        
        categoryFilter.addEventListener('change', filterProducts);
        expiryFilter.addEventListener('change', filterProducts);
        searchBox.addEventListener('input', filterProducts);
    }
    
    // Funzioni per le azioni sui prodotti
    window.consumeProduct = function(productId) {
        if (confirm('Vuoi segnare questo prodotto come consumato?')) {
            // TODO: Implementare la logica AJAX per segnare un prodotto come consumato
            console.log(`Prodotto ${productId} consumato`);
        }
    };
    
    window.editProduct = function(productId) {
        // Modifica questa riga:
        // location.href = `../manualAdd/manualAdd.php?edit=${productId}`;
        // A:
        location.href = `../CRUDfun/frigoEdit.php?id=${productId}`;
    };
    
    window.deleteProduct = function(relationId) {
        if (confirm('Sei sicuro di voler eliminare questo prodotto?')) {
            // Invia una richiesta AJAX per eliminare il prodotto
            const xhr = new XMLHttpRequest();
            xhr.open('POST', '../CRUDfun/frigoDelete.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        // Se l'eliminazione è avvenuta con successo, rimuovi la card del prodotto
                        const productCard = document.querySelector(`.product-card[data-id="${relationId}"]`);
                        if (productCard) {
                            productCard.remove();
                        }
                        
                        // Se non ci sono più prodotti, mostra il messaggio "frigo vuoto"
                        const remainingProducts = document.querySelectorAll('.product-card');
                        if (remainingProducts.length === 0) {
                            const productGrid = document.getElementById('product-grid');
                            if (productGrid) {
                                productGrid.innerHTML = `
                                    <div class="empty-fridge">
                                        <i class="fas fa-box-open"></i>
                                        <h3>Il tuo frigo è vuoto!</h3>
                                        <p>Aggiungi i tuoi prodotti alimentari per iniziare a monitorare le scadenze e ottimizzare i consumi.</p>
                                        <button class="manual-btn" onclick="location.href='../manualAdd/manualAdd.php'">
                                            <i class="fa fa-plus"></i>
                                            Aggiungi prodotti
                                        </button>
                                    </div>
                                `;
                            }
                        }

                        location.reload();
                    } else {
                        // Mostra un messaggio di errore
                        alert(response.message);
                    }
                } else {
                    alert('Si è verificato un errore durante l\'eliminazione del prodotto.');
                }
            };
            xhr.onerror = function() {
                alert('Si è verificato un errore di rete durante l\'eliminazione del prodotto.');
            };
            xhr.send('relation_id=' + relationId);
        }
    };
});

function toggleNote(element) {
    const noteContainer = element.parentElement;
    const truncatedNote = noteContainer.querySelector('.note-content');
    const fullNote = noteContainer.querySelector('.note-full');
    
    if (fullNote.style.display === 'none') {
        // Espandi la nota
        truncatedNote.style.display = 'none';
        fullNote.style.display = 'inline';
        element.textContent = 'Mostra meno';
    } else {
        // Contrai la nota
        truncatedNote.style.display = 'inline';
        fullNote.style.display = 'none';
        element.textContent = 'Leggi tutto';
    }
}