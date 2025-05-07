<?php 
//  session_start(); 
//  if(!isset($_SESSION["email"])){
//      header("Location: /login.html");
//      exit();
//  }
//  include "database.php";
//  $message = "";
//  $messageType = "";
//  // Gestione del form di aggiunta prodotto
//  if ($_SERVER["REQUEST_METHOD"] == "POST") {
//      // Recupero dati dal form
//      $nome_prodotto = $_POST["nome_prodotto"];
//      $categoria = $_POST["categoria"];
//      $data_scadenza = $_POST["data_scadenza"];
//      $quantita = $_POST["quantita"];
//      $note = isset($_POST["note"]) ? $_POST["note"] : "";
//      // Validazione base
//      if (empty($nome_prodotto) || empty($categoria) || empty($data_scadenza) || empty($quantita)) {
//          $message = "Tutti i campi obbligatori devono essere compilati";
//          $messageType = "error";
//      } else {
//          try {
//              // Query per inserire il prodotto nel database
//              $sql = "INSERT INTO prodotti (email_utente, nome, categoria, data_scadenza, quantita, note) 
//                      VALUES (:email, :nome, :categoria, :data_scadenza, :quantita, :note)";
//              
//              $stmt = $conn->prepare($sql);
//              $stmt->bindParam(":email", $_SESSION["email"]);
//              $stmt->bindParam(":nome", $nome_prodotto);
//              $stmt->bindParam(":categoria", $categoria);
//              $stmt->bindParam(":data_scadenza", $data_scadenza);
//              $stmt->bindParam(":quantita", $quantita);
//              $stmt->bindParam(":note", $note);
//              
//              $stmt->execute();
//              
//              $message = "Prodotto aggiunto con successo!";
//              $messageType = "success";
//          } catch(PDOException $e) {
//              $message = "Errore durante l'aggiunta del prodotto: " . $e->getMessage();
//              $messageType = "error";
//          }
//      }
//  }
//  // Recupero informazioni utente per la visualizzazione
//  $sql = "SELECT nome, cognome FROM utente WHERE email = :email";
//  $stmt = $conn->prepare($sql);
//  $stmt->bindParam(":email", $_SESSION["email"]);
//  $stmt->execute();
//  $data = $stmt->fetchAll();
//  
//  if (count($data) > 0) {
//      $nome = $data[0]["nome"];
//      $cognome = $data[0]["cognome"];
//  } else {
//      $nome = "Utente";
//      $cognome = "";
//  }
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gustify - Aggiungi Prodotto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="dashboard/dashboard.css">
    <style>
        .form-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 2rem;
            box-shadow: var(--shadow);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        }

        .submit-btn {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block;
            margin-right: 10px;
        }

        .submit-btn:hover {
            background-color: #3d8b40;
        }

        .cancel-btn {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            display: inline-block;
        }

        .cancel-btn:hover {
            background-color: #d32f2f;
        }

        .button-group {
            display: flex;
            justify-content: flex-start;
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }

        .alert-error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        .page-title {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <i class="fas fa-leaf"></i>
                Gustify
            </div>
            <ul class="nav-links">
                <li><a href="../index.html"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="dashboard.php"><i class="fa fa-dashboard"></i> Dashboard</a></li>
                <li><a href=""><i class="fas fa-shopping-basket"></i> Prodotti</a></li>
                <li><a href=""><i class="fas fa-chart-pie"></i> Alimentazione</a></li>
                <li><a href=""><i class="fas fa-user"></i> Profilo</a></li>
            </ul>
            <div class="mobile-menu-icon">
                <i class="fas fa-bars"></i>
            </div>
        </nav>
    </header>
    
    <div class="container">
        <section class="hero">
            <h1>Aggiungi un Prodotto</h1>
            <p>Inserisci i dettagli del prodotto che desideri aggiungere al tuo frigo virtuale</p>
        </section>
        
        <div class="form-container">
            <h2 class="page-title">Dettagli Prodotto</h2>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="nome_prodotto">Nome Prodotto *</label>
                    <input type="text" id="nome_prodotto" name="nome_prodotto" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="categoria">Categoria *</label>
                    <select id="categoria" name="categoria" class="form-control" required>
                        <option value="">Seleziona categoria</option>
                        <option value="dairy">Latticini</option>
                        <option value="fruit">Frutta</option>
                        <option value="vegetable">Verdura</option>
                        <option value="meat">Carne</option>
                        <option value="fish">Pesce</option>
                        <option value="bakery">Prodotti da forno</option>
                        <option value="beverage">Bevande</option>
                        <option value="snack">Snack</option>
                        <option value="other">Altro</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="data_scadenza">Data di Scadenza *</label>
                    <input type="date" id="data_scadenza" name="data_scadenza" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="quantita">Quantità *</label>
                    <input type="number" id="quantita" name="quantita" min="1" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="note">Note (opzionale)</label>
                    <textarea id="note" name="note" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="button-group">
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save"></i> Salva Prodotto
                    </button>
                    <a href="dashboard.php" class="cancel-btn">
                        <i class="fas fa-times"></i> Annulla
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; 2025 Gustify - Sviluppato da Paul&Federic Software House</p>
    </footer>
    
    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('data_scadenza').setAttribute('min', today);

            // Preselect date to a week from now as default
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            const nextWeekFormatted = nextWeek.toISOString().split('T')[0];
            document.getElementById('data_scadenza').value = nextWeekFormatted;
        });
    </script>
</body>
</html>