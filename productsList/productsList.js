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
    
    // Variabile globale per il prodotto da consumare
    window.productToConsume = null;
    window.productTotalQuantity = null;

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
        console.log("showProductActions chiamato per ID:", productId);
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
        console.log("Consumazione prodotto ID:", productId);
        
        if (actionsPopup) {
            actionsPopup.style.display = 'none';
        }
        
        // Find the product card
        let productCard = null;
        const productCards = document.querySelectorAll('.product-card');
        
        console.log("Numero di card prodotto trovate:", productCards.length);
        
        for (let card of productCards) {
            const consumeBtn = card.querySelector('.consume-btn');
            if (consumeBtn) {
                // Estrarre l'ID del prodotto dall'attributo onclick
                const onclickAttr = consumeBtn.getAttribute('onclick');
                console.log("Attributo onclick:", onclickAttr);
                
                if (onclickAttr) {
                    // Cercare un pattern come "consumeProduct(52)"
                    const match = onclickAttr.match(/consumeProduct\((\d+)/);
                    if (match && match[1] == productId) {
                        productCard = card;
                        console.log("Card trovata per il prodotto ID:", productId);
                        break;
                    }
                }
            }
        }
        
        if (!productCard) {
            showToast('Errore', 'Prodotto non trovato.', 'error');
            console.error("Card prodotto non trovata per ID:", productId);
            return;
        }
        
        // Get the total quantity
        const quantityText = productCard.querySelector('.product-quantity').textContent;
        const totalQuantity = parseInt(quantityText.match(/\d+/)[0]) || 1;
        
        console.log("Quantità totale:", totalQuantity);
        
        // Show the consumption modal
        const consumeModal = document.getElementById('consume-modal');
        const quantityInput = document.getElementById('consume-quantity');
        const productNameElement = document.getElementById('consume-product-name');
        
        // Update modal content
        productNameElement.textContent = productCard.querySelector('.product-title').textContent;
        quantityInput.value = "1";
        quantityInput.max = totalQuantity;
        
        // Store the product ID for the confirmation
        window.productToConsume = productId;
        window.productTotalQuantity = totalQuantity;
        
        // Show the modal
        consumeModal.classList.add('show');
    };

    window.confirmConsumption = function() {
        console.log("Conferma consumazione - ID prodotto:", window.productToConsume);
        
        const consumeModal = document.getElementById('consume-modal');
        const quantityInput = document.getElementById('consume-quantity');
        const consumeQuantity = parseInt(quantityInput.value);
        
        console.log("Quantità da consumare:", consumeQuantity);
        console.log("Quantità totale disponibile:", window.productTotalQuantity);
        
        if (isNaN(consumeQuantity) || consumeQuantity <= 0) {
            showToast('Errore', 'Quantità non valida.', 'error');
            return;
        }
        
        // AJAX request to consume the product
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../CRUDfun/consumeProduct.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            console.log("Risposta ricevuta dal server:", this.status);
            console.log("Risposta completa:", this.responseText);
            
            if (this.status === 200) {
                try {
                    const response = JSON.parse(this.responseText);
                    console.log("Risposta parsata:", response);
                    
                    if (response.success) {
                        showToast('Prodotto consumato', 'Il prodotto è stato contrassegnato come consumato.');
                        
                        // Utilizzare l'ID salvato nella variabile globale
                        const prodId = window.productToConsume;
                        console.log("Cercando prodotto ID:", prodId, "per aggiornare l'UI");
                        
                        const productCards = document.querySelectorAll('.product-card');
                        let cardFound = false;
                        
                        for (let card of productCards) {
                            const consumeBtn = card.querySelector('.consume-btn');
                            if (consumeBtn) {
                                const onclickAttr = consumeBtn.getAttribute('onclick');
                                const match = onclickAttr.match(/consumeProduct\((\d+)/);
                                
                                if (match && match[1] == prodId) {
                                    console.log("Card trovata per aggiornamento UI");
                                    cardFound = true;
                                    
                                    if (response.consumed_all) {
                                        console.log("Rimozione completa della card con animazione");
                                        
                                        // Salviamo l'altezza attuale per un'animazione fluida
                                        card.style.height = card.offsetHeight + 'px';
                                        card.style.overflow = 'hidden';
                                        card.style.transition = 'all 0.5s ease-out';
                                        
                                        // Fase 1: fade out e slide
                                        card.style.opacity = '0';
                                        card.style.transform = 'translateX(-30px)';
                                        
                                        // Fase 2: collasso dell'altezza
                                        setTimeout(() => {
                                            card.style.height = '0';
                                            card.style.margin = '0';
                                            card.style.padding = '0';
                                            
                                            // Fase 3: rimozione effettiva
                                            setTimeout(() => {
                                                card.remove();
                                                
                                                // Controlla se rimangono prodotti
                                                if (document.querySelectorAll('.product-card').length === 0) {
                                                    console.log("Nessun prodotto rimasto, mostrando stato vuoto");
                                                    
                                                    // Animazione stato vuoto
                                                    const emptyState = document.createElement('div');
                                                    emptyState.className = 'empty-fridge';
                                                    emptyState.style.opacity = '0';
                                                    emptyState.style.transform = 'translateY(20px)';
                                                    emptyState.style.transition = 'all 0.5s ease';
                                                    emptyState.innerHTML = `
                                                        <i class="fas fa-box-open"></i>
                                                        <h3>Il tuo frigo è vuoto!</h3>
                                                        <p>Aggiungi i tuoi prodotti alimentari per iniziare a monitorare le scadenze e ottimizzare i consumi.</p>
                                                        <button class="manual-btn" onclick="location.href='../manualAdd/manualAdd.php'">
                                                            <i class="fas fa-plus"></i>
                                                            Aggiungi prodotti
                                                        </button>
                                                    `;
                                                    
                                                    const grid = document.getElementById('product-grid');
                                                    if (grid) {
                                                        grid.innerHTML = '';
                                                        grid.appendChild(emptyState);
                                                        
                                                        // Animazione di comparsa
                                                        setTimeout(() => {
                                                            emptyState.style.opacity = '1';
                                                            emptyState.style.transform = 'translateY(0)';
                                                        }, 10);
                                                    }
                                                }
                                            }, 300);
                                        }, 300);
                                    } else {
                                        console.log("Aggiornamento quantità parziale con animazione");
                                        console.log("Quantità rimanente:", response.remaining_quantity);
                                        
                                        // Aggiornamento della quantità parziale
                                        const quantityElement = card.querySelector('.product-quantity');
                                        console.log("Elemento quantità trovato:", quantityElement);
                                        
                                        if (quantityElement && response.remaining_quantity) {
                                            // Evidenzia l'intera card
                                            card.style.transition = 'background-color 0.5s ease';
                                            const originalBg = card.style.backgroundColor;
                                            card.style.backgroundColor = 'rgba(76, 175, 80, 0.1)';
                                            
                                            // Aggiorna il testo della quantità con animazione
                                            quantityElement.style.transition = 'all 0.3s ease';
                                            quantityElement.style.transform = 'scale(1.1)';
                                            quantityElement.style.backgroundColor = 'rgba(76, 175, 80, 0.2)';
                                            
                                            // Aggiorna il testo
                                            quantityElement.textContent = 'Confezioni: ' + response.remaining_quantity;
                                            console.log("Testo quantità aggiornato a:", quantityElement.textContent);
                                            
                                            // Ripristina lo stato normale dopo l'animazione
                                            setTimeout(() => {
                                                quantityElement.style.transform = 'scale(1)';
                                                quantityElement.style.backgroundColor = 'transparent';
                                                card.style.backgroundColor = originalBg;
                                            }, 1000);
                                        } else {
                                            console.log("Elemento quantità non trovato o quantità rimanente non disponibile");
                                            setTimeout(function() {
                                                location.reload();
                                            }, 1000);
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                        
                        // Se non abbiamo trovato la card, ricarichiamo la pagina come ultima risorsa
                        if (!cardFound) {
                            console.log("Card non trovata dopo la consumazione, ricarico la pagina");
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    } else {
                        console.log("Errore restitutito dal server:", response.message);
                        showToast('Errore', response.message || 'Si è verificato un errore durante il consumo del prodotto.', 'error');
                    }
                } catch (e) {
                    console.error("Errore parsing JSON:", e);
                    console.error("Risposta server:", this.responseText);
                    showToast('Errore', 'Si è verificato un errore durante l\'elaborazione della risposta.', 'error');
                }
            } else {
                console.error("Errore HTTP:", this.status);
                showToast('Errore', 'Si è verificato un errore durante il consumo del prodotto.', 'error');
            }
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
        // Non resettare subito le variabili per evitare problemi con risposte asincrone
        setTimeout(() => {
            window.productToConsume = null;
            window.productTotalQuantity = null;
        }, 2000);
    };

    window.cancelConsumption = function() {
        const consumeModal = document.getElementById('consume-modal');
        consumeModal.classList.remove('show');
        window.productToConsume = null;
        window.productTotalQuantity = null;
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
                            // Proviamo a trovare e rimuovere la card prima di ricaricare
                            const cards = document.querySelectorAll('.product-card');
                            let found = false;
                            
                            for (let card of cards) {
                                const deleteBtn = card.querySelector('.delete-btn');
                                if (deleteBtn) {
                                    const onclickAttr = deleteBtn.getAttribute('onclick');
                                    const match = onclickAttr.match(/deleteProduct\((\d+)/);
                                    
                                    if (match && match[1] == productToDelete) {
                                        found = true;
                                        
                                        // Animazione di rimozione
                                        card.style.height = card.offsetHeight + 'px';
                                        card.style.overflow = 'hidden';
                                        card.style.transition = 'all 0.5s ease-out';
                                        
                                        // Prima fase: fade out e slide
                                        card.style.opacity = '0';
                                        card.style.transform = 'translateX(-30px)';
                                        
                                        // Seconda fase: collasso dell'altezza
                                        setTimeout(() => {
                                            card.style.height = '0';
                                            card.style.margin = '0';
                                            card.style.padding = '0';
                                            
                                            // Ultima fase: rimozione dal DOM
                                            setTimeout(() => {
                                                card.remove();
                                                
                                                // Verifichiamo se abbiamo prodotti rimasti
                                                if (document.querySelectorAll('.product-card').length === 0) {
                                                    location.reload(); // Ricarica per mostrare lo stato vuoto
                                                }
                                                
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
                    } else {
                        showToast('Errore', 'Si è verificato un errore durante l\'eliminazione del prodotto.', 'error');
                    }
                };
                xhr.onerror = function() {
                    showToast('Errore', 'Si è verificato un errore di rete durante l\'eliminazione del prodotto.', 'error');
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
        
        // Reset delle animazioni
        toast.style.animation = 'none';
        void toast.offsetWidth; // Forza reflow
        
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        
        if (type === 'success') {
            toastIcon.className = 'fas fa-check-circle';
            toast.style.borderLeftColor = 'var(--success-color)';
            toastIcon.style.color = 'var(--success-color)';
        } else {
            toastIcon.className = 'fas fa-exclamation-circle';
            toast.style.borderLeftColor = 'var(--error-color)';
            toastIcon.style.color = 'var(--error-color)';
        }
        
        toast.classList.add('show');
        toast.style.animation = 'toastSlideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
        
        // Hide toast after 5 seconds with animation
        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s ease forwards';
            setTimeout(() => {
                toast.classList.remove('show');
            }, 300);
        }, 5000);
    }

    // Function to check if the list is empty
    function checkEmptyList() {
        const productList = document.querySelector('.product-list');
        if (productList && productList.children.length === 0) {
            const emptyMessage = document.createElement('li');
            emptyMessage.className = 'empty-message';
            emptyMessage.textContent = 'Nessun prodotto nel tuo frigo.';
            emptyMessage.style.opacity = '0';
            productList.appendChild(emptyMessage);
            
            // Animazione di comparsa
            setTimeout(() => {
                emptyMessage.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                emptyMessage.style.opacity = '1';
                emptyMessage.style.transform = 'translateY(0)';
            }, 10);
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
            toast.style.animation = 'toastSlideOut 0.3s ease forwards';
            setTimeout(() => {
                toast.classList.remove('show');
            }, 300);
        }
    };
    
    // Aggiungiamo stili per le animazioni
    if (!document.getElementById('animation-styles')) {
        const style = document.createElement('style');
        style.id = 'animation-styles';
        style.textContent = `
            @keyframes toastSlideIn {
                0% { transform: translateX(150%); opacity: 0; }
                100% { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes toastSlideOut {
                0% { transform: translateX(0); opacity: 1; }
                100% { transform: translateX(150%); opacity: 0; }
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes pulseScale {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            @keyframes slideOut {
                0% { transform: translateX(0); opacity: 1; }
                100% { transform: translateX(-30px); opacity: 0; }
            }
            
            .product-card, .product-item {
                transition: all 0.5s ease-out;
            }
            
            .empty-message {
                opacity: 0;
                transform: translateY(10px);
            }
            
            .product-quantity {
                transition: all 0.3s ease;
            }
        `;
        document.head.appendChild(style);
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
                if (showByCategory && showByExpiry && showBySearch) {
                    card.style.display = 'block';
                    card.style.animation = 'fadeIn 0.3s ease';
                } else {
                    card.style.display = 'none';
                }
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
        // Espandi la nota con animazione
        truncatedNote.style.display = 'none';
        fullNote.style.opacity = '0';
        fullNote.style.display = 'inline';
        fullNote.style.transition = 'opacity 0.3s ease';
        
        // Forza il reflow
        void fullNote.offsetWidth;
        
        // Avvia l'animazione di fade in
        fullNote.style.opacity = '1';
        element.textContent = 'Mostra meno';
    } else {
        // Contrai la nota con animazione
        fullNote.style.opacity = '0';
        
        setTimeout(() => {
            fullNote.style.display = 'none';
            truncatedNote.style.display = 'inline';
            
            // Anima la ricomparsa del testo troncato
            truncatedNote.style.opacity = '0';
            truncatedNote.style.transition = 'opacity 0.3s ease';
            void truncatedNote.offsetWidth;
            truncatedNote.style.opacity = '1';
            
            element.textContent = 'Leggi tutto';
        }, 300);
    }
}