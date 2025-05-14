document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuIcon = document.querySelector('.mobile-menu-icon');
    const navLinks = document.querySelector('.nav-links');
    
    if (mobileMenuIcon) {
        mobileMenuIcon.addEventListener('click', function() {
            navLinks.classList.toggle('show');
        });
    }

    // Filtri per la tabella dei prodotti consumati
    const categoryFilter = document.getElementById('category-filter');
    const timeFilter = document.getElementById('time-filter');
    const searchBox = document.getElementById('search-product');
    const tableRows = document.querySelectorAll('#consumed-table-body tr');
    
    if (categoryFilter && timeFilter && searchBox && tableRows.length > 0) {
        function filterTable() {
            const categoryValue = categoryFilter.value;
            const timeValue = timeFilter.value;
            const searchValue = searchBox.value.toLowerCase();
            
            tableRows.forEach(row => {
                const category = row.dataset.category;
                const timeClass = row.dataset.time;
                const productName = row.querySelector('.product-name').textContent.toLowerCase();
                
                // Filtro per categoria
                let showByCategory = categoryValue === 'all' || category === categoryValue;
                
                // Filtro per tempo
                let showByTime = timeValue === 'all' || (timeValue === 'today' && timeClass === 'data-today') || 
                                 (timeValue === 'week' && (timeClass === 'data-today' || timeClass === 'data-week')) ||
                                 (timeValue === 'month' && (timeClass === 'data-today' || timeClass === 'data-week' || timeClass === 'data-month')) ||
                                 (timeValue === 'year' && (timeClass === 'data-today' || timeClass === 'data-week' || timeClass === 'data-month' || timeClass === 'data-year'));
                
                // Filtro per ricerca
                let showBySearch = searchValue === '' || productName.includes(searchValue);
                
                // Mostra o nascondi la riga
                row.style.display = (showByCategory && showByTime && showBySearch) ? '' : 'none';
            });
            
            // Verifica se ci sono righe visibili
            const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
            if (visibleRows.length === 0) {
                // Nessun risultato trovato
                const tableBody = document.getElementById('consumed-table-body');
                
                // Rimuovi la riga "Nessun risultato" se già presente
                const noResultRow = document.getElementById('no-result-row');
                if (noResultRow) {
                    noResultRow.remove();
                }
                
                // Aggiungi riga "Nessun risultato"
                const newRow = document.createElement('tr');
                newRow.id = 'no-result-row';
                newRow.innerHTML = `<td colspan="7" style="text-align: center; padding: 2rem;">Nessun prodotto trovato con i filtri selezionati</td>`;
                tableBody.appendChild(newRow);
            } else {
                // Rimuovi la riga "Nessun risultato" se presente
                const noResultRow = document.getElementById('no-result-row');
                if (noResultRow) {
                    noResultRow.remove();
                }
            }
        }
        
        categoryFilter.addEventListener('change', filterTable);
        timeFilter.addEventListener('change', filterTable);
        searchBox.addEventListener('input', filterTable);
    }
    
    // Gestione del modal di conferma eliminazione
    const deleteModal = document.getElementById('delete-confirmation-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    let productToDelete = null;
    
    // Funzione per eliminare un prodotto consumato
    window.deleteConsumedProduct = function(consumedId) {
        // Store the product ID for later use
        productToDelete = consumedId;
        
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
                // Send AJAX request to delete the consumed product record
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '../CRUDfun/deleteConsumed.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        try {
                            const response = JSON.parse(this.responseText);
                            if (response.success) {
                                // Show a brief message that the product was removed
                                showToast('Prodotto rimosso', 'Il prodotto è stato rimosso dalla cronologia.');
                                
                                // Force page reload immediately
                                setTimeout(function() {
                                    window.location.reload(true); // true forces a reload from server, not cache
                                }, 500); // Short delay to allow toast to be visible
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
                xhr.send('consumed_id=' + productToDelete);
                
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
    
    // Function to close toast
    window.closeToast = function() {
        const toast = document.getElementById('toast');
        if (toast) {
            toast.classList.remove('show');
        }
    };
});

// Funzione per espandere/contrarre le note
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