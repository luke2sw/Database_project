<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Inizializza la variabile $codicefiscale con una stringa vuota
$numtesserasanitaria = '';

if (isset($_COOKIE['username']) && isset($_COOKIE['codice_utente']) && isset($_COOKIE['ruolo'])) {
    $username = $_COOKIE['username'];
    $codice_utente = $_COOKIE['codice_utente'];
    $ruolo = $_COOKIE['ruolo'];

    if ($ruolo !== 'PersonaleAmministrativo') {
        header('Location: login.php');
        exit;
    }

    // Verifica se è stato inserito un numero di tessera sanitaria nella ricerca
    if (isset($_GET['search_numtesserasanitaria'])) {
        $search_numtesserasanitaria = trim($_GET['search_numtesserasanitaria']);
    }

    try {
        // Query per ottenere le informazioni del paziente, filtrata per numero tessera sanitaria se è stata eseguita una ricerca
        if (!empty($search_numtesserasanitaria)) {
            // Se è presente un numero di tessera sanitaria da cercare, esegui la query filtrata
            $queryGestione_Pazienti = "SELECT * FROM PAZIENTE WHERE numtesserasanitaria LIKE :search_numtesserasanitaria";
            $stmt = $conn->prepare($queryGestione_Pazienti);
            $stmt->execute([':search_numtesserasanitaria' => '%' . $search_numtesserasanitaria . '%']);
        } else {
            // Altrimenti esegui la query senza filtri
            $queryGestione_Pazienti = "SELECT * FROM PAZIENTE";
            $stmt = $conn->prepare($queryGestione_Pazienti);
            $stmt->execute();
        }
        // Ottieni tutti i risultati come array associativo
        $Pazienti = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }

} else {
    header('Location: login.php');
    exit;
}



function checkPazienteEsistente($conn, $numtesserasanitaria)
{
    // Query per controllare l'esistenza del paziente con quel codice fiscale
    $query = "SELECT COUNT(*) FROM PAZIENTE WHERE numtesserasanitaria = :numtesserasanitaria";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':numtesserasanitaria' => $numtesserasanitaria]);

        // Ottieni il conteggio del paziente con quel codice fiscale
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno una persona con quel codice fiscale, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo del paziente: ' . $e->getMessage());
    }
}

//aggiungi paziente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungi'])) {
    $numtesserasanitaria = $_POST['numtesserasanitaria'];


    if (!checkPazienteEsistente($conn, $numtesserasanitaria)) {
        echo 'Paziente non presente';
        // Dati paziente
        $indirizzo = isset($_POST['indirizzo']) ? trim($_POST['indirizzo']) : '';
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : null;
        $cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : null;
        $datanascita = isset($_POST['datanascita']) ? trim($_POST['datanascita']) : null;


        // Controllo della data di nascita
        if (!empty($datanascita)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $datanascita);
            if ($d && $d->format($dateFormat) === $datanascita) {
                // Data valida -> $datanascita = $datanascita;
            } else {
                die('Errore: Formato della data non valido.');
            }
        } else {
            $datanascita = null; // Imposta a null se vuoto
        }


        try {
            // Inserisci il nuovo paziente
            $queryInsertPaziente = "
                INSERT INTO PAZIENTE (numtesserasanitaria, indirizzo, telefono, nome, cognome, datanascita) 
                VALUES (:numtesserasanitaria, :indirizzo, :telefono, :nome, :cognome, :datanascita)
            ";
            $stmtInsertPaziente = $conn->prepare($queryInsertPaziente);
            $stmtInsertPaziente->execute([
                ':numtesserasanitaria' => $numtesserasanitaria,
                ':indirizzo' => $indirizzo,
                ':telefono' => $telefono,
                ':nome' => $nome,
                ':cognome' => $cognome,
                ':datanascita' => $datanascita
            ]);

            // Reindirizzamento per ripulire il form
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

        } catch (PDOException $e) {
            die('Errore durante l\'inserimento del paziente: ' . $e->getMessage());
        }
    } else {
        // Il paziente esiste già
    }

}




//modifica paziente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifica'])) {
    $numtesserasanitaria = $_POST['numtesserasanitaria'];



    // Recupera il paziente esistente
    $querySelectPaziente = "SELECT indirizzo, telefono, nome, cognome, datanascita FROM PAZIENTE WHERE numtesserasanitaria = :numtesserasanitaria";
    $stmtSelectPaziente = $conn->prepare($querySelectPaziente);
    $stmtSelectPaziente->execute([':numtesserasanitaria' => $numtesserasanitaria]);
    $paziente = $stmtSelectPaziente->fetch(PDO::FETCH_ASSOC);

    if ($paziente === false) {
        die('Errore: Paziente non trovato.');
    }

    // Valori di data attuali
    $currentIndirizzo = $paziente['indirizzo'];
    $currentTelefono = $paziente['telefono'];
    $currentNome = $paziente['nome'];
    $currentCognome = $paziente['cognome'];
    $currentDataNascita = $paziente['datanascita'];


    // Ottieni i valori dei campi
    $indirizzo = isset($_POST['indirizzo']) ? trim($_POST['indirizzo']) : $currentIndirizzo;
    $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : $currentTelefono;
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : $currentNome;
    $cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : $currentCognome;
    $datanascita = isset($_POST['datanascita']) ? trim($_POST['datanascita']) : $currentDataNascita;

    // Controllo della data di nascita
    if (!empty($datanascita)) {
        $dateFormat = 'Y-m-d';
        $d = DateTime::createFromFormat($dateFormat, $datanascita);
        if ($d && $d->format($dateFormat) === $datanascita) {
            // Data valida
        } else {
            die('Errore: Formato della data di assunzione non valido.');
        }
    } else {
        $datanascita = $currentDataNascita; // Mantieni il valore corrente se non fornito
    }


    // Controllo dell'indirizzo
    if (empty($indirizzo)) {
        $indirizzo = $currentIndirizzo; // Mantieni il valore corrente se non fornito
    }

    // Controllo del telefono
    if (empty($telefono)) {
        $telefono = $currentTelefono; // Mantieni il valore corrente se non fornito
    }

    // Controllo del nome
    if (empty($nome)) {
        $nome = $currentNome; // Mantieni il valore corrente se non fornito
    }

    // Controllo del cognome
    if (empty($cognome)) {
        $cognome = $currentCognome; // Mantieni il valore corrente se non fornito
    }





    try {
        // Aggiorna il paziente esistente
        $queryUpdatePaziente = "
            UPDATE PAZIENTE 
            SET indirizzo = :indirizzo, 
                telefono = :telefono, 
                nome = :nome, 
                cognome = :cognome, 
                datanascita = :datanascita 
            WHERE numtesserasanitaria = :numtesserasanitaria
        ";
        $stmtUpdatePaziente = $conn->prepare($queryUpdatePaziente);
        $stmtUpdatePaziente->execute([
            ':numtesserasanitaria' => $numtesserasanitaria,
            ':indirizzo' => $indirizzo,
            ':telefono' => $telefono,
            ':nome' => $nome,
            ':cognome' => $cognome,
            ':datanascita' => $datanascita
        ]);

        // Reindirizzamento per ripulire il form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

    } catch (PDOException $e) {
        die('Errore durante l\'aggiornamento del paziente: ' . $e->getMessage());
    }
}

//rimuovi prescrizione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi_paziente'])) {
    $pazienteCodice = $_POST['paziente_codice'];

    if (empty($pazienteCodice)) {
        die('Errore: Codice paziente non fornito.');
    }

    try {
        // Inizia una transazione
        $conn->beginTransaction();

        // Controlla se il paziente ha delle prescrizioni a suo nome
        $queryFindPrescrzione = "SELECT COUNT(*) FROM PRENOTAZIONE WHERE paziente = :pazienteCodice";
        $stmtFindPrescrzione = $conn->prepare($queryFindPrescrzione);
        $stmtFindPrescrzione->execute([':pazienteCodice' => $pazienteCodice]);
        $countPrescrizione = $stmtFindPrescrzione->fetchColumn();


        // Rimuovi il paziente se non ha prescrizioni a suo nome
        if ($countPrescrizione == 0) {
            // Rimuovi il paziente
            $queryDeletePaziente = "DELETE FROM PAZIENTE WHERE numtesserasanitaria = :pazienteCodice";
            $stmtDeletePaziente = $conn->prepare($queryDeletePaziente);
            $stmtDeletePaziente->execute([':pazienteCodice' => $pazienteCodice]);
        } else {
            die('Errore: Il paziente ha delle prescrizioni a suo nome. Elimina prima le prescrizioni.');
        }

        // Conferma la transazione
        $conn->commit();

        // Reindirizza per ripulire il form e confermare l'eliminazione
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (PDOException $e) {
        // Annulla la transazione in caso di errore
        $conn->rollBack();
        die('Errore durante la rimozione dell\'esame: ' . $e->getMessage());
    }
}




?>


<!DOCTYPE html>
<html lang="it">




<head>
    <title>Gestione Pazienti</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script>
        // Funzione per comprimere/espandere la tabella
        function toggleTable() {
            var table = document.getElementById("PazienteTable");
            var button = document.getElementById("toggleButton");

            // Controlla lo stato della tabella (visibile o nascosta)
            if (table.style.display === "none") {
                table.style.display = "table";  // Mostra la tabella
                button.innerHTML = "Comprimi";  // Cambia il testo del bottone
            } else {
                table.style.display = "none";  // Nascondi la tabella
                button.innerHTML = "Espandi";  // Cambia il testo del bottone
            }
        }

        // Funzione per comprimere/espandere la sezione Modifica Paziente
        function toggleModifica() {
            var section = document.getElementById("modificaSection");
            var button = document.getElementById("toggleModifica");

            // Controlla lo stato della sezione (visibile o nascosta)
            if (section.style.display === "none") {
                section.style.display = "block";  // Mostra la sezione
                button.innerHTML = "Comprimi";  // Cambia il testo del bottone
            } else {
                section.style.display = "none";  // Nascondi la sezione
                button.innerHTML = "Espandi";  // Cambia il testo del bottone
            }
        }

        // Funzione per comprimere/espandere la sezione Aggiungi Paziente
        function toggleAggiungi() {
            var section = document.getElementById("aggiungiSection");
            var button = document.getElementById("toggleAggiungi");

            // Controlla lo stato della sezione (visibile o nascosta)
            if (section.style.display === "none") {
                section.style.display = "block";  // Mostra la sezione
                button.innerHTML = "Comprimi";  // Cambia il testo del bottone
            } else {
                section.style.display = "none";  // Nascondi la sezione
                button.innerHTML = "Espandi";  // Cambia il testo del bottone
            }
        }
    </script>

    <style>
        .section-container {
            display: none;
            /* Nascondi per default */
        }
        .container {

            padding-right: 0;
            padding-left: 0;

        }
    </style>






</head>

<body>
    <main style="margin-bottom:5%">
        <div class="container title" style="margin-top:2%">

            <!-- Flex container per il titolo e il pulsante di ritorno -->
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="title">Pazienti dell'Ospedale</h1>
                <div class="return-button">
                    <a href="amministrativo.php" class="btn btn-secondary">Torna alla pagina di Gestione
                        Amministrativa</a>
                </div>
            </div>
        </div>

        <section>
            <div class="container" style="margin-top:2%">
                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title" style="width: 20%">Elenco Pazienti</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la tabella -->
                            <button id="toggleButton" class="btn btn-primary" onclick="toggleTable()">Comprimi</button>
                        </div>
                    </div>
                </div>

                <!-- Campo di ricerca per il codice fiscale -->
                <div class="container" style="margin-top:2%">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="search_numtesserasanitaria"
                                placeholder="Cerca per Numero Tessera Sanitaria"
                                value="<?php echo isset($_GET['search_numtesserasanitaria']) ? htmlspecialchars($_GET['search_numtesserasanitaria']) : ''; ?>">
                            <button class="btn btn-primary" type="submit" style="width:7.5%">Cerca</button>
                        </div>
                    </form>
                </div>

                <!-- Tabella per visualizzare i dati del paziente -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($Pazienti)): ?>
                                    <table id="PazienteTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Numero Tessera Sanitaria</th>
                                                <th scope="col">Nome</th>
                                                <th scope="col">Cognome</th>
                                                <th scope="col">telefono</th>
                                                <th scope="col">Indirizzo</th>
                                                <th scope="col">Data Nascita</th>
                                                <th scope="col">Rimuovi</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($Pazienti as $index => $Persona): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($Persona['numtesserasanitaria']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['cognome']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['telefono']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['indirizzo']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['datanascita']); ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST"
                                                            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <input type="hidden" name="paziente_codice"
                                                                value="<?php echo htmlspecialchars($Persona['numtesserasanitaria']); ?>">
                                                            <button type="submit" name="rimuovi_paziente"
                                                                class="btn btn-danger btn-sm">Rimuovi</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Nessun paziente trovato.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Modifica Paziente</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Modifica paziente -->
                            <button id="toggleModifica" class="btn btn-primary"
                                onclick="toggleModifica()">Comprimi</button>
                        </div>
                    </div>
                    <!--Modifica paziente-->
                    <div id="modificaSection">
                        <div class="row" style="margin-top:1%; margin-bottom:3%">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                                            method="post">
                                            <!--Numero Tessera Sanitaria (per identificare il paziente da modificare)-->
                                            <div class="mb-3">
                                                <label for="numtesserasanitaria" class="form-label">Numero Tessera
                                                    Sanitaria</label>
                                                <input type="text" class="form-control" id="numtesserasanitaria"
                                                    name="numtesserasanitaria"
                                                    value="<?php echo isset($_POST['numtesserasanitaria']) ? htmlspecialchars($_POST['numtesserasanitaria']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Nome-->
                                            <div class="mb-3">
                                                <label for="nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="nome" name="nome"
                                                    value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                                            </div>
                                            <!--Cognome-->
                                            <div class="mb-3">
                                                <label for="cognome" class="form-label">Cognome</label>
                                                <input type="text" class="form-control" id="cognome" name="cognome"
                                                    value="<?php echo isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : ''; ?>">

                                            </div>
                                            <!--Data Nascita-->
                                            <div class="mb-3">
                                                <label for="datanascita" class="form-label">Data
                                                    Nascita
                                                </label>
                                                <input type="date" class="form-control" id="datanascita"
                                                    name="datanascita"
                                                    value="<?php echo isset($_POST['datanascita']) ? htmlspecialchars($_POST['datanascita']) : ''; ?>">
                                            </div>
                                            <!--Indirizzo-->
                                            <div class="mb-3">
                                                <label for="indirizzo" class="form-label">Indirizzo</label>
                                                <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                                    value="<?php echo isset($_POST['indirizzo']) ? htmlspecialchars($_POST['indirizzo']) : ''; ?>">
                                            </div>

                                            <!--Telefono-->
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Telefono
                                                </label>
                                                <input type="text" class="form-control" id="telefono" name="telefono"
                                                    value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                                            </div>


                                            <button type="submit" name="modifica" class="btn btn-success"
                                                style="float:right">Modifica</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!--Aggiungi Paziente-->
                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center" style="margin-top:1%">
                        <h2 class="card-title">Aggiungi Paziente</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Aggiungi paziente -->
                            <button id="toggleAggiungi" class="btn btn-primary"
                                onclick="toggleAggiungi()">Comprimi</button>
                        </div>
                    </div>
                    <div id="aggiungiSection">
                        <div class="row" style="margin-top:1%; margin-bottom:3%">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                                            method="post">
                                            <!--Numero Tessera Sanitaria (per identificare il paziente da modificare)-->
                                            <div class="mb-3">
                                                <label for="numtesserasanitaria" class="form-label">Numero Tessera
                                                    Sanitaria</label>
                                                <input type="text" class="form-control" id="numtesserasanitaria"
                                                    name="numtesserasanitaria"
                                                    value="<?php echo isset($_POST['numtesserasanitaria']) ? htmlspecialchars($_POST['numtesserasanitaria']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Nome-->
                                            <div class="mb-3">
                                                <label for="nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="nome" name="nome"
                                                    value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Cognome-->
                                            <div class="mb-3">
                                                <label for="cognome" class="form-label">Cognome</label>
                                                <input type="text" class="form-control" id="cognome" name="cognome"
                                                    value="<?php echo isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : ''; ?>"
                                                    required>

                                            </div>
                                            <!--Data Nascita-->
                                            <div class="mb-3">
                                                <label for="datanascita" class="form-label">Data
                                                    Nascita
                                                </label>
                                                <input type="date" class="form-control" id="datanascita"
                                                    name="datanascita"
                                                    value="<?php echo isset($_POST['datanascita']) ? htmlspecialchars($_POST['datanascita']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Indirizzo-->
                                            <div class="mb-3">
                                                <label for="indirizzo" class="form-label">Indirizzo</label>
                                                <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                                    value="<?php echo isset($_POST['indirizzo']) ? htmlspecialchars($_POST['indirizzo']) : ''; ?>"
                                                    required>
                                            </div>

                                            <!--Telefono-->
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Telefono
                                                </label>
                                                <input type="text" class="form-control" id="telefono" name="telefono"
                                                    value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                                                    required>
                                            </div>


                                            <button type="submit" name="aggiungi" class="btn btn-success"
                                                style="float:right">Aggiungi</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </section>
    </main>
</body>

</html>