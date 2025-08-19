<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();


if (isset($_COOKIE['username']) && isset($_COOKIE['codice_utente']) && isset($_COOKIE['ruolo'])) {
    $username = $_COOKIE['username'];
    $codice_utente = $_COOKIE['codice_utente'];
    $ruolo = $_COOKIE['ruolo'];

    if ($ruolo !== 'PersonaleAmministrativo') {
        header('Location: login.php');
        exit;
    }

    // Verifica se è stato inserito un numtesserasanitaria nella ricerca
    if (isset($_GET['search_paziente'])) {
        $search_paziente = trim($_GET['search_paziente']);
    }

    try {
        // Query per ottenere le informazioni della prenotazione, filtrata per numtesserasanitaria se è stata eseguita una ricerca
        if (!empty($search_paziente)) {
            // Se è presente un numtesserasanitaria da cercare, esegui la query filtrata
            $queryGestione_Prenotazioni = "SELECT * FROM PRENOTAZIONE WHERE paziente LIKE :search_paziente";
            $stmt = $conn->prepare($queryGestione_Prenotazioni);
            $stmt->execute([':search_paziente' => '%' . $search_paziente . '%']);
        } else {
            // Altrimenti esegui la query senza filtri
            $queryGestione_Prenotazioni = "SELECT * FROM PRENOTAZIONE";
            $stmt = $conn->prepare($queryGestione_Prenotazioni);
            $stmt->execute();


        }
        // Ottieni tutti i risultati come array associativo
        $Prenotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }

} else {
    header('Location: login.php');
    exit;
}



function checkPrenotazioneEsistente($conn, $paziente, $prescrizione)
{
    // Query per controllare l'esistenza della prenotazione con quel codice
    $query = "SELECT COUNT(*) FROM PRENOTAZIONE WHERE paziente = :paziente AND prescrizione = :prescrizione";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':paziente' => $paziente, ':prescrizione' => $prescrizione]);

        // Ottieni il conteggio della prenotazione con quel codice
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno una prenotazione con quel codice, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo della prenotazione: ' . $e->getMessage());
    }
}

//aggiungi prenotazione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungi'])) {
    $paziente = $_POST['numtesserasanitaria'];
    $prescrizione = $_POST['prescrizione'];


    if (!checkPrenotazioneEsistente($conn, $paziente, $prescrizione)) {
        echo 'Prenotazione non presente';
        // Dati prenotazione
        $urgenza = isset($_POST['urgenza']) ? trim($_POST['urgenza']) : '';
        $ambulatorioesterno = isset($_POST['ambulatorioesterno']) ? trim($_POST['ambulatorioesterno']) : null;
        $dataprenotazione = isset($_POST['dataprenotazione']) ? trim($_POST['dataprenotazione']) : null;
        $dataoraesame = isset($_POST['dataoraesame']) ? trim($_POST['dataoraesame']) : null;
        $numerostanza = isset($_POST['numerostanza']) ? trim($_POST['numerostanza']) : null;
        $reparto = isset($_POST['reparto']) ? trim($_POST['reparto']) : null;
        $esame = isset($_POST['esame']) ? trim($_POST['esame']) : null;


        // Controllo della data di prenotazione
        if (!empty($dataprenotazione)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $dataprenotazione);
            if ($d && $d->format($dateFormat) === $dataprenotazione) {
                // Data valida -> $dataprenotazione = $dataprenotazione;
            } else {
                die('Errore: Formato della data non valido.');
            }
        } else {
            $dataprenotazione = null; // Imposta a null se vuoto
        }

        // Controllo della data e ora esame
        if (!empty($dataoraesame)) {
            $dateTimeFormat = 'Y-m-d\TH:i'; //controllo anche l'ora
            $d = DateTime::createFromFormat($dateTimeFormat, $dataoraesame);
            if ($d && $d->format($dateTimeFormat) === $dataoraesame) {
                // Data e ora validi
            } else {
                die('Errore: Formato della data e ora dell\'esame non valido.');
            }
        } else {
            $dataoraesame = null; // Mantieni il valore corrente se non fornito
        }

        // Controllo dell'urgenza
        if (empty($urgenza)) {
            echo 'Urgenza non presente';
            $urgenza = null;
        }

        // Controllo dell'esame
        if (empty($esame)) {
            echo 'Esame non presente';
            $esame = null;
        }

        // Controllo dell'ambulatorio esterno
        if (empty($ambulatorioesterno)) {
            echo 'Ambulatorio Esterno non presente';
            $ambulatorioesterno = null;
        }

        // Controllo del reparto
        if (empty($reparto)) {
            echo 'Reparto non presente';
            $reparto = null;
        }

        // Controllo del numero stanza
        if (empty($numerostanza)) {
            echo 'Numero Stanza non presente';
            $numerostanza = null;
        }



        try {
            // Inserisci la nuova prenotazione
            $queryInsertPrenotazione = "
                INSERT INTO PRENOTAZIONE (urgenza, dataprenotazione, dataoraesame, paziente, ambulatorioesterno, numerostanza, reparto, esame, prescrizione) 
                VALUES (:urgenza, :dataprenotazione, :dataoraesame, :paziente, :ambulatorioesterno, :numerostanza, :reparto, :esame, :prescrizione)
            ";
            $stmtInsertPrenotazione = $conn->prepare($queryInsertPrenotazione);
            $stmtInsertPrenotazione->execute([
                ':paziente' => $paziente,
                ':urgenza' => $urgenza,
                ':dataprenotazione' => $dataprenotazione,
                ':dataoraesame' => $dataoraesame,
                ':ambulatorioesterno' => $ambulatorioesterno,
                ':numerostanza' => $numerostanza,
                ':reparto' => $reparto,
                ':esame' => $esame,
                ':prescrizione' => $prescrizione
            ]);

            if ($ambulatorioesterno == null) {
                $queryDetailsStanza = "SELECT tipologia FROM STANZA WHERE numerostanza = :numerostanza and reparto = :reparto";
                $stmtDetailsStanza = $conn->prepare($queryDetailsStanza);
                $stmtDetailsStanza->execute([':numerostanza' => $numerostanza, ':reparto' => $reparto]);
                $Detailstanza = $stmtDetailsStanza->fetch(PDO::FETCH_ASSOC);


                if ($Detailstanza['tipologia'] == 'Ricovero') {


                    $queryInsertRicovero =
                        "INSERT INTO RICOVERO (datainizio, datadimissione, paziente, letto, numerostanza, reparto)
                    VALUES (:datainizio, :datadimissione, :paziente, :letto, :numerostanza, :reparto)";

                    $stmtInsertRicovero = $conn->prepare($queryInsertRicovero);
                    $stmtInsertRicovero->execute([
                        ':datainizio' => date('Y-m-d', strtotime($dataoraesame)),
                        ':datadimissione' => date('Y-m-d', strtotime($dataoraesame)),
                        ':paziente' => $paziente,
                        ':letto' => null,
                        ':numerostanza' => $numerostanza,
                        ':reparto' => $reparto
                    ]);

                    //query per trovare patologia paziente
                    $queryPatologiaPaziente = "SELECT patologia FROM PAZIENTEHAPATOLOGIA WHERE numtesserasanitaria = :paziente";
                    $stmtPatologiaPaziente = $conn->prepare($queryPatologiaPaziente);
                    $stmtPatologiaPaziente->execute([':paziente' => $paziente]);
                    $patologiaPaziente = $stmtPatologiaPaziente->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($patologiaPaziente as $patologia) {
                        $queryInsertRicoveroPatologia = "
                            INSERT INTO RICOVEROPATOLOGIA (ricovero, patologia, paziente)
                            VALUES (:ricovero, :patologia, :paziente)
                        ";
                        $stmtInsertRicoveroPatologia = $conn->prepare($queryInsertRicoveroPatologia);
                        $stmtInsertRicoveroPatologia->execute([
                            ':ricovero' => date('Y-m-d', strtotime($dataoraesame)),
                            ':patologia' => $patologia['patologia'],
                            ':paziente' => $paziente
                        ]);
                    }
                }
            }

            // Reindirizzamento per ripulire il form
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

        } catch (PDOException $e) {
            die('Errore durante l\'inserimento della prenotazione: ' . $e->getMessage());
        }
    } else {
        // La prenotazione esiste già
        echo 'Prenotazione già presente.';
    }

}




//modifica Prenotazione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifica'])) {
    $paziente = $_POST['numtesserasanitaria'];



    // Recupera la Prenotazione esistente
    $querySelectPrenotazione = "SELECT urgenza, dataprenotazione, dataoraesame, ambulatorioesterno, numerostanza, reparto, esame, prescrizione FROM PRENOTAZIONE WHERE paziente = :paziente and prescrizione = :prescrizione";
    $stmtSelectPrenotazione = $conn->prepare($querySelectPrenotazione);
    $stmtSelectPrenotazione->execute([':paziente' => $paziente, ':prescrizione' => $_POST['prescrizione']]);
    $prenotazione = $stmtSelectPrenotazione->fetch(PDO::FETCH_ASSOC);

    if ($prenotazione === false) {
        die('Errore: Prenotazione non trovato.');
    }

    // Valori di data attuali
    $currentUrgenza = $prenotazione['urgenza'];
    $currentDataPrenotazione = $prenotazione['dataprenotazione'];
    $currentDataOraEsame = $prenotazione['dataoraesame'];
    $currentAmbulatorioEsterno = $prenotazione['ambulatorioesterno'];
    $currentNumeroStanza = $prenotazione['numerostanza'];
    $currentReparto = $prenotazione['reparto'];
    $currentEsame = $prenotazione['esame'];
    $currentPrescrizione = $prenotazione['prescrizione'];


    // Ottieni i valori dei campi
    $urgenza = isset($_POST['urgenza']) ? trim($_POST['urgenza']) : $currentUrgenza;
    $dataprenotazione = isset($_POST['dataprenotazione']) ? trim($_POST['dataprenotazione']) : $currentDataPrenotazione;
    $dataoraesame = isset($_POST['dataoraesame']) ? trim($_POST['dataoraesame']) : $currentDataOraEsame;
    $ambulatorioesterno = isset($_POST['ambulatorioesterno']) ? trim($_POST['ambulatorioesterno']) : $currentAmbulatorioEsterno;
    $numerostanza = isset($_POST['numerostanza']) ? trim($_POST['numerostanza']) : $currentNumeroStanza;
    $reparto = isset($_POST['reparto']) ? trim($_POST['reparto']) : $currentReparto;
    $esame = isset($_POST['esame']) ? trim($_POST['esame']) : $currentEsame;
    $prescrizione = isset($_POST['prescrizione']) ? trim($_POST['prescrizione']) : $currentPrescrizione;


    // Controllo della data di prenotazione
    if (!empty($dataprenotazione)) {
        $dateFormat = 'Y-m-d';
        $d = DateTime::createFromFormat($dateFormat, $dataprenotazione);
        if ($d && $d->format($dateFormat) === $dataprenotazione) {
            // Data valida
        } else {
            die('Errore: Formato della data di assunzione non valido.');
        }
    } else {
        $dataprenotazione = $currentDataPrenotazione; // Mantieni il valore corrente se non fornito
    }

    // Controllo della data e ora esame
    if (!empty($dataoraesame)) {
        $dateTimeFormat = 'Y-m-d\TH:i';
        $d = DateTime::createFromFormat($dateTimeFormat, $dataoraesame);
        if ($d && $d->format($dateTimeFormat) === $dataoraesame) {
            // Data e ora validi
        } else {
            die('Errore: Formato della data e ora dell\'esame non valido.');
        }
    } else {
        $dataoraesame = $currentDataOraEsame; // Mantieni il valore corrente se non fornito
    }




    // Controllo dell'urgenza
    if (empty($urgenza)) {
        $urgenza = $currentUrgenza; // Mantieni il valore corrente se non fornito
    }

    // Controllo dell'ambulatorio esterno
    if (empty($ambulatorioesterno)) {
        $ambulatorioesterno = $currentAmbulatorioEsterno; // Mantieni il valore corrente se non fornito
    }

    // Controllo del numero stanza
    if (empty($numerostanza)) {
        $numerostanza = $currentNumeroStanza; // Mantieni il valore corrente se non fornito
    }

    // Controllo del reparto
    if (empty($reparto)) {
        $reparto = $currentReparto; // Mantieni il valore corrente se non fornito
    }

    // Controllo dell'esame
    if (empty($esame)) {
        $esame = $currentEsame; // Mantieni il valore corrente se non fornito
    }

    // Controllo della prescrizione
    if (empty($prescrizione)) {
        $prescrizione = $currentPrescrizione; // Mantieni il valore corrente se non fornito
    }

    try {
        // Aggiorna la prenotaione esistente
        $queryUpdatePrenotazione = "
            UPDATE PRENOTAZIONE 
            SET urgenza = :urgenza, 
                dataprenotazione = :dataprenotazione, 
                dataoraesame = :dataoraesame, 
                ambulatorioesterno = :ambulatorioesterno, 
                numerostanza = :numerostanza,
                reparto = :reparto, 
                esame = :esame, 
                prescrizione = :prescrizione 
            WHERE paziente = :paziente AND prescrizione = :prescrizione
        ";
        $stmtUpdatePrenotazione = $conn->prepare($queryUpdatePrenotazione);
        $stmtUpdatePrenotazione->execute([
            ':paziente' => $paziente,
            ':urgenza' => $urgenza,
            ':dataprenotazione' => $dataprenotazione,
            ':dataoraesame' => $dataoraesame,
            ':ambulatorioesterno' => $ambulatorioesterno,
            ':numerostanza' => $numerostanza,
            ':reparto' => $reparto,
            ':esame' => $esame,
            ':prescrizione' => $prescrizione
        ]);

        // Reindirizzamento per ripulire il form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

    } catch (PDOException $e) {
        die('Errore durante l\'aggiornamento della prenotazione: ' . $e->getMessage());
    }
}


//rimuoivi prenotazione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi_prenotazione'])) {
    $prescrizioneCodice = $_POST['prescrizione_codice'];

    if (empty($prescrizioneCodice)) {
        die('Errore: Codice prescrizione non fornito.');
    }

    try {
        // Inizia una transazione
        $conn->beginTransaction();

        // Rimuovi la prenotazione
        $queryDeletePrenotazione = "DELETE FROM PRENOTAZIONE WHERE prescrizione = :prescrizioneCodice";
        $stmtDeletePrenotazione = $conn->prepare($queryDeletePrenotazione);
        $stmtDeletePrenotazione->execute([':prescrizioneCodice' => $prescrizioneCodice]);


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
    <title>Gestione Personale</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        // Funzione per comprimere/espandere la tabella
        function toggleTable() {
            var table = document.getElementById("pazienteTable");
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

        // Funzione per comprimere/espandere la sezione Modifica Prenotazione
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

        // Funzione per comprimere/espandere la sezione Aggiungi Prenotazione
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
                <h1 class="title">Prenotazioni dell'Ospedale</h1>
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
                        <h2 class="card-title" style="width: 30%">Elenco Prenotazioni</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la tabella -->
                            <button id="toggleButton" class="btn btn-primary" onclick="toggleTable()">Comprimi</button>
                        </div>
                    </div>
                </div>

                <!-- Campo di ricerca per il numtesserasanitaria -->
                <div class="container" style="margin-top:2%">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="search_paziente"
                                placeholder="Cerca per Codice Fiscale"
                                value="<?php echo isset($_GET['search_paziente']) ? htmlspecialchars($_GET['search_paziente']) : ''; ?>">
                            <button class="btn btn-primary" type="submit" style="width:7.5%">Cerca</button>
                        </div>
                    </form>
                </div>

                <!-- Tabella per visualizzare i dati della prenotaione -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($Prenotazioni)): ?>
                                    <table id="pazienteTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Urgenza</th>
                                                <th scope="col">Data Prenotazione</th>
                                                <th scope="col">Paziente</th>
                                                <th scope="col">Data e Ora Esame</th>
                                                <th scope="col">Ambulatorio Esterno</th>
                                                <th scope="col">Numero Stanza</th>
                                                <th scope="col">Tipologia Stanza</th>
                                                <th scope="col">Reparto</th>
                                                <th scope="col">Esame</th>
                                                <th scope="col">Prescrizione</th>
                                                <th scope="col">Rimuovi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($Prenotazioni as $index => $Prenotazione): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($Prenotazione['urgenza']); ?></td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['dataprenotazione']); ?></td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['paziente']); ?></td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['dataoraesame']); ?></td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['ambulatorioesterno']); ?></td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['numerostanza']); ?>
                                                    </td>
                                                    <?php
                                                    $numerostanza = $Prenotazione['numerostanza'];
                                                    $reparto = $Prenotazione['reparto'];
                                                    
                                                    // Recupera la tipologia della stanza dal database in base al numero stanza e reparto
                                                    $queryDetailsStanza = "SELECT tipologia FROM STANZA WHERE numerostanza = :numerostanza and reparto = :reparto";
                                                    $stmtDetailsStanza = $conn->prepare($queryDetailsStanza);
                                                    $stmtDetailsStanza->execute([':numerostanza' => $numerostanza, ':reparto' => $reparto]);
                                                    $Detailstanza = $stmtDetailsStanza->fetch(PDO::FETCH_ASSOC);

                                                    // Controlla se la stanza è stata trovata
                                                    if ($Detailstanza) {
                                                        $tipologia = htmlspecialchars($Detailstanza['tipologia']);
                                                    } else {
                                                        $tipologia = ''; // In caso non esista una tipologia
                                                    }
                                                    ?>

                                                    <td>
                                                        <?php if ($tipologia == 'Ricovero'): ?>
                                                            <!-- Crea il link alla pagina di dettaglio della prenotazione -->
                                                            <a href="dettagli_prenotazione_ricovero.php?dataoraesame=<?php echo urlencode($Prenotazione['dataoraesame']); ?>&dataprenotazione=<?php echo urlencode($Prenotazione['dataprenotazione']); ?>&paziente=<?php echo urlencode($Prenotazione['paziente']); ?>">
                                                                <?php echo $tipologia; ?>
                                                            </a>


                                                        <?php else: ?>
                                                            <?php echo $tipologia; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    
                                                    <td><?php echo htmlspecialchars($Prenotazione['reparto']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['esame']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($Prenotazione['prescrizione']); ?></td>
                                                    <td>
                                                        <form method="POST"
                                                            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <input type="hidden" name="prescrizione_codice"
                                                                value="<?php echo htmlspecialchars($Prenotazione['prescrizione']); ?>">
                                                            <button type="submit" name="rimuovi_prenotazione"
                                                                class="btn btn-danger btn-sm">Rimuovi</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Nessuna prenotazione trovata.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Modifica Prenotazione-->
                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Modifica Prenotazione</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Modifica Prenotazione -->
                            <button id="toggleModifica" class="btn btn-primary"
                                onclick="toggleModifica()">Comprimi</button>
                        </div>
                    </div>

                    <div id="modificaSection">
                        <div class="row" style="margin-top:1%; margin-bottom:3%">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                                            method="post">
                                            <!--Numero Tessera Sanitaria(per identificare la prenotazione da modificare)-->
                                            <div class="mb-3">
                                                <label for="numtesserasanitaria" class="form-label">Numero Tessera
                                                    Sanitaria*</label>
                                                <input type="text" class="form-control" id="numtesserasanitaria"
                                                    name="numtesserasanitaria"
                                                    value="<?php echo isset($_POST['codicefiscale']) ? htmlspecialchars($_POST['codicefiscale']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--urgenza-->
                                            <div class="mb-3">
                                                <label for="urgenza" class="form-label">Urgenza</label>
                                                <input type="text" class="form-control" id="urgenza" name="urgenza"
                                                    value="<?php echo isset($_POST['urgenza']) ? htmlspecialchars($_POST['urgenza']) : ''; ?>">
                                            </div>
                                            <!--Data di Prenotazione-->
                                            <div class="mb-3">
                                                <label for="dataprenotazione" class="form-label">Data di
                                                    Prenotazione</label>
                                                <input type="date" class="form-control" id="dataprenotazione"
                                                    name="dataprenotazione"
                                                    value="<?php echo isset($_POST['dataprenotazione']) ? htmlspecialchars($_POST['dataprenotazione']) : ''; ?>">

                                            </div>
                                            <!--Data e ORa esame-->
                                            <div class="mb-3">
                                                <label for="dataoraesame" class="form-label">Data e Ora dell'Esame</label>
                                                <input type="datetime-local" class="form-control" id="dataoraesame"
                                                    name="dataoraesame"
                                                    value="<?php echo isset($_POST['dataoraesame']) ? htmlspecialchars($_POST['dataoraesame']) : ''; ?>">

                                            </div>
                                            <!--Ambulatorio Esterno-->
                                            <div class="mb-3">
                                                <label for="ambulatorioesterno" class="form-label">Ambulatorio Esterno
                                                </label>
                                                <input type="text" class="form-control" id="ambulatorioesterno"
                                                    name="ambulatorioesterno"
                                                    value="<?php echo isset($_POST['ambulatorioesterno']) ? htmlspecialchars($_POST['ambulatorioesterno']) : ''; ?>">

                                            </div>
                                            <!--Numero della stanza-->
                                            <div class="mb-3">
                                                <label for="numerostanza" class="form-label">Numero della stanza

                                                </label>
                                                <input type="text" class="form-control" id="numerostanza"
                                                    name="numerostanza"
                                                    value="<?php echo isset($_POST['numerostanza']) ? htmlspecialchars($_POST['numerostanza']) : ''; ?>">

                                            </div>
                                            <!--Reparto-->
                                            <div class="mb-3">
                                                <label for="reparto" class="form-label">Reparto

                                                </label>
                                                <input type="text" class="form-control" id="reparto" name="reparto"
                                                    value="<?php echo isset($_POST['reparto']) ? htmlspecialchars($_POST['reparto']) : ''; ?>">

                                            </div>
                                            <!--Esame-->
                                            <div class="mb-3">
                                                <label for="esame" class="form-label">Esame

                                                </label>
                                                <input type="text" class="form-control" id="esame" name="esame"
                                                    value="<?php echo isset($_POST['esame']) ? htmlspecialchars($_POST['esame']) : ''; ?>">

                                            </div>
                                            <!--Prescrizione-->
                                            <div class="mb-3">
                                                <label for="prescrizione" class="form-label">Prescrizione*

                                                </label>
                                                <input type="text" class="form-control" id="prescrizione"
                                                    name="prescrizione"
                                                    value="<?php echo isset($_POST['prescrizione']) ? htmlspecialchars($_POST['prescrizione']) : ''; ?>"
                                                    required>

                                            </div>

                                            <button type="submit" name="modifica" class="btn btn-success"
                                                style="float:right">Modifica</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!--Aggiungi Prenotazione-->
                <!-- Flex container per il titolo e il pulsante di comprimi -->
                <div class="d-flex justify-content-between align-items-center" style="margin-top:2%">
                    <h2 class="card-title">Aggiungi Prenotazione</h2>
                    <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                        <!-- Bottone per comprimere/espandere la sezione Aggiungi Prenotazione -->
                        <button id="toggleAggiungi" class="btn btn-primary" onclick="toggleAggiungi()">Comprimi</button>
                    </div>
                </div>
                <div id="aggiungiSection">
                    <div class="row" style="margin-top:1%; margin-bottom:3%">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                        <!--Numero Tessera Sanitaria(per identificare la prenotazione da modificare)-->
                                        <div class="mb-3">
                                            <label for="numtesserasanitaria" class="form-label">Numero Tessera
                                                Sanitaria</label>
                                            <input type="text" class="form-control" id="numtesserasanitaria"
                                                name="numtesserasanitaria"
                                                value="<?php echo isset($_POST['codicefiscale']) ? htmlspecialchars($_POST['codicefiscale']) : ''; ?>"
                                                required>
                                        </div>
                                        <!--urgenza-->
                                        <div class="mb-3">
                                            <label for="urgenza" class="form-label">Urgenza</label>
                                            <input type="text" class="form-control" id="urgenza" name="urgenza"
                                                value="<?php echo isset($_POST['urgenza']) ? htmlspecialchars($_POST['urgenza']) : ''; ?>">
                                        </div>
                                        <!--Data di prenotaione-->
                                        <div class="mb-3">
                                            <label for="dataprenotazione" class="form-label">Data di
                                                Prenotazione</label>
                                            <input type="date" class="form-control" id="dataprenotazione"
                                                name="dataprenotazione"
                                                value="<?php echo isset($_POST['dataprenotazione']) ? htmlspecialchars($_POST['dataprenotazione']) : ''; ?>">

                                        </div>
                                        <!--Data e Ora esame-->
                                        <div class="mb-3">
                                            <label for="dataoraesame" class="form-label">Data e Ora
                                                dell'Esame</label>
                                            <input type="datetime-local" class="form-control" id="dataoraesame"
                                                name="dataoraesame"
                                                value="<?php echo isset($_POST['dataoraesame']) ? htmlspecialchars($_POST['dataoraesame']) : ''; ?>">

                                        </div>
                                        <!--Ambulatorio Esterno-->
                                        <div class="mb-3">
                                            <label for="ambulatorioesterno" class="form-label">Ambulatorio Esterno
                                            </label>
                                            <input type="text" class="form-control" id="ambulatorioesterno"
                                                name="ambulatorioesterno"
                                                value="<?php echo isset($_POST['ambulatorioesterno']) ? htmlspecialchars($_POST['ambulatorioesterno']) : ''; ?>">

                                        </div>
                                        <!--Numero della stanza-->
                                        <div class="mb-3">
                                            <label for="numerostanza" class="form-label">Numero della stanza

                                            </label>
                                            <input type="text" class="form-control" id="numerostanza"
                                                name="numerostanza"
                                                value="<?php echo isset($_POST['numerostanza']) ? htmlspecialchars($_POST['numerostanza']) : ''; ?>">

                                        </div>
                                        <!--Reparto-->
                                        <div class="mb-3">
                                            <label for="reparto" class="form-label">Reparto

                                            </label>
                                            <input type="text" class="form-control" id="reparto" name="reparto"
                                                value="<?php echo isset($_POST['reparto']) ? htmlspecialchars($_POST['reparto']) : ''; ?>">

                                        </div>
                                        <!--Esame-->
                                        <div class="mb-3">
                                            <label for="esame" class="form-label">Esame

                                            </label>
                                            <input type="text" class="form-control" id="esame" name="esame"
                                                value="<?php echo isset($_POST['esame']) ? htmlspecialchars($_POST['esame']) : ''; ?>">

                                        </div>
                                        <!--Prescrizione-->
                                        <div class="mb-3">
                                            <label for="prescrizione" class="form-label">Prescrizione

                                            </label>
                                            <input type="text" class="form-control" id="prescrizione"
                                                name="prescrizione"
                                                value="<?php echo isset($_POST['prescrizione']) ? htmlspecialchars($_POST['prescrizione']) : ''; ?>">

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