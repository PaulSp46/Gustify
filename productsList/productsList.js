document.addEventListener('DOMContentLoaded', function() {
    const scanBtn = document.querySelector('.scan-btn');
    const qrReader = document.getElementById('qr-reader');
    const overlay = document.getElementById('overlay');
    const qrCloseBtn = document.getElementById('qr-close-btn');
    const successMessage = document.getElementById('success-message');
    const successCloseBtn = document.getElementById('success-close-btn');
    const scannedProductsList = document.getElementById('scanned-products');
    const actionsPopup = document.getElementById('product-actions-popup');
    const deleteModal = document.getElementById('delete-confirmation-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    let productToDelete = null;

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
        
        if (actionsPopup) {
            actionsPopup.style.display = 'block';
            actionsPopup.style.top = rect.bottom + 'px';
            actionsPopup.style.left = rect.left + 'px';
            
            // Aggiungi un evento per chiudere il popup quando si clicca altrove
            document.addEventListener('click', closeActionsPopup);
        }
        
        // Previeni la propagazione dell'evento click per evitare che il popup si chiuda immediatamente
        event.stopPropagation();
    };
    
    function closeActionsPopup(event) {
        if (actionsPopup && !actionsPopup.contains(event.target) && event.target.className !== 'fas fa-ellipsis-v') {
            actionsPopup.style.display = 'none';
            document.removeEventListener('click', closeActionsPopup);
        }
    }
    
    // Funzioni per le azioni sui prodotti
    window.consumeProduct = function(productId) {
        if (actionsPopup) {
            actionsPopup.style.display = 'none';
        }
        
        // AJAX request to consume the product
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../CRUDfun/consumeProduct.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.');
                        
                        // Rimuovi l'elemento dalla lista
                        const productElements = document.querySelectorAll('.product-card');
                        for (let product of productElements) {
                            if (product.querySelector('.consume-btn').getAttribute('onclick').includes(productId)) {
                                product.style.opacity = '0';
                                setTimeout(() => {
                                    product.remove();
                                    // Check if the grid is empty
                                    if (document.querySelectorAll('.product-card').length === 0) {
                                        location.reload(); // Reload to show the empty state
                                    }
                                }, 300);
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
        if (actionsPopup) {
            actionsPopup.style.display = 'none';
        }
        location.href = `../CRUDfun/frigoEdit.php?id=${productId}`;
    };
    
    window.deleteProduct = function(productId) {
        if (actionsPopup) {
            actionsPopup.style.display = 'none';
        }
        
        // Store the product ID for later use
        productToDelete = productId;
        
        // Show the delete confirmation modal
        if (deleteModal) {
            deleteModal.classList.add('show');
        }
    };
    
    // Event listeners for delete modal
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.classList.remove('show');
            productToDelete = null;
        });
    }
    
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (productToDelete !== null) {
                // Send AJAX request to delete the product
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../CRUDfun/frigoDelete.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        if (response.success) {
                            location.reload();
                        } else {
                            showToast('Errore', response.message || 'Si è verificato un errore durante l\'eliminazione del prodotto.');
                        }
                    } else {
                        showToast('Errore', 'Si è verificato un errore durante l\'eliminazione del prodotto.');
                    }
                };
                xhr.onerror = function() {
                    showToast('Errore', 'Si è verificato un errore di rete durante l\'eliminazione del prodotto.');
                };
                xhr.send('relation_id=' + productToDelete);
                
                // Hide the modal
                deleteModal.classList.remove('show');
                productToDelete = null;
            }
        });
    }
    
    // Close modal when clicking outside
    if (deleteModal) {
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.classList.remove('show');
                productToDelete = null;
            }
        });
    }

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

    // Toast notification
    function showToast(title, message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
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
        setTimeout(() => toast.classList.remove('show'), 5000);
    }

    // Function to check if the list is empty
    function checkEmptyList() {
        const productList = document.querySelector('.product-list');
        if (productList && productList.children.length === 0) {
            const emptyMessage = document.createElement('li');
            emptyMessage.className = 'empty-message';
            emptyMessage.textContent = 'Nessun prodotto nel tuo frigo.';
            productList.appendChild(emptyMessage);
        }
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

    // Function to close toast
    window.closeToast = function() {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.classList.remove('show');
        }
    };
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