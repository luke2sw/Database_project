<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Inizializza la variabile $codicefiscale con una stringa vuota
$codicefiscale = '';

    // Query per ottenere l'elenco dei reparti
    $queryReparti = "SELECT Telefono, Nome, Ospedale FROM Reparto";
    $stmtReparti = $conn->prepare($queryReparti);
    $stmtReparti->execute();
    $reparti = $stmtReparti->fetchAll(PDO::FETCH_ASSOC);

    // Se l'elenco dei reparti non è vuoto, seleziona automaticamente il primo reparto
    if (!empty($reparti)) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seleziona_reparto'])) {
            // Se è stato inviato un reparto tramite il form, usalo
            $repartoSelezionato = $_POST['reparto'];
        } else {
            // Altrimenti, pre-seleziona il primo reparto della lista
            $repartoSelezionato = $reparti[0]['telefono']; // Si presume che 'Telefono' sia il campo chiave per il reparto
        }

        // Query per ottenere il personale del reparto selezionato
        $queryPersonale = "SELECT p.CodiceFiscale, p.Ruolo, p.DataAssunzione, p.DataPromozionePrimario, p.DataPromozioneVicePrimario, p.Reparto, r.Ospedale
                           FROM Reparto r 
                           JOIN Personale p ON r.Telefono = p.Reparto
                           WHERE p.Reparto = :repartoSelezionato";
        $stmtPersonale = $conn->prepare($queryPersonale);
        $stmtPersonale->execute([':repartoSelezionato' => $repartoSelezionato]);
        $personale = $stmtPersonale->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $personale = []; // Nessun reparto trovato
    }




function checkPersonaleEsistente($conn, $codicefiscale)
{
    // Query per controllare l'esistenza del personale con quel codice fiscale
    $query = "SELECT COUNT(*) FROM PERSONALE WHERE codicefiscale = :codicefiscale";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':codicefiscale' => $codicefiscale]);

        // Ottieni il conteggio del personale con quel codice fiscale
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno una persona con quel codice fiscale, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo del paziente: ' . $e->getMessage());
    }
}

//aggiungi personale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungi'])) {
    $codicefiscale = $_POST['codicefiscale'];


    if (!checkPersonaleEsistente($conn, $codicefiscale)) {
        echo 'Personale non presente';
        // Dati personale
        $ruolo = isset($_POST['ruolo']) ? trim($_POST['ruolo']) : '';
        $reparto = isset($_POST['reparto']) ? trim($_POST['reparto']) : null;
        $dataassunzione = isset($_POST['dataassunzione']) ? trim($_POST['dataassunzione']) : null;
        $datapromozioneprimario = isset($_POST['datapromozioneprimario']) ? trim($_POST['datapromozioneprimario']) : null;
        $datapromozioneviceprimario = isset($_POST['datapromozioneviceprimario']) ? trim($_POST['datapromozioneviceprimario']) : null;

        // Controllo della data di assunzione
        if (!empty($dataassunzione)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $dataassunzione);
            if ($d && $d->format($dateFormat) === $dataassunzione) {
                // Data valida -> $dataassunzione = $dataassunzione;
            } else {
                die('Errore: Formato della data non valido.');
            }
        } else {
            $dataassunzione = null; // Imposta a null se vuoto
        }

        // Controllo della data di promozione a primario
        if (!empty($datapromozioneprimario)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $datapromozioneprimario);
            if ($d && $d->format($dateFormat) === $datapromozioneprimario) {
                // Data valida -> $datapromozioneprimario = $datapromozioneprimario;
            } else {
                die('Errore: Formato della data non valido.');
            }
        } else {
            $datapromozioneprimario = null; // Imposta a null se vuoto
        }

        // Controllo della data di promozione a vice primario
        if (!empty($datapromozioneviceprimario)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $datapromozioneviceprimario);
            if ($d && $d->format($dateFormat) === $datapromozioneviceprimario) {
                // Data valida -> $datapromozioneviceprimario = $datapromozioneviceprimario;
            } else {
                die('Errore: Formato della data non valido.');
            }
        } else {
            $datapromozioneviceprimario = null; // Imposta a null se vuoto
        }


        try {
            // Inserisci il nuovo personale
            $queryInsertPersonale = "
                INSERT INTO PERSONALE (codicefiscale, ruolo, reparto, dataassunzione, datapromozioneprimario, datapromozioneviceprimario) 
                VALUES (:codicefiscale, :ruolo, :reparto, :dataassunzione, :datapromozioneprimario, :datapromozioneviceprimario)
            ";
            $stmtInsertPersonale = $conn->prepare($queryInsertPersonale);
            $stmtInsertPersonale->execute([
                ':codicefiscale' => $codicefiscale,
                ':ruolo' => $ruolo,
                ':reparto' => $reparto,
                ':dataassunzione' => $dataassunzione,
                ':datapromozioneprimario' => $datapromozioneprimario,
                ':datapromozioneviceprimario' => $datapromozioneviceprimario
            ]);

            // Reindirizzamento per ripulire il form
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

        } catch (PDOException $e) {
            die('Errore durante l\'inserimento del personale: ' . $e->getMessage());
        }
    } else {
        // Il paziente esiste già
        //echo 'Personale già presente.';
    }

}




//modifica personale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifica'])) {
    $codicefiscale = $_POST['codicefiscale'];



    // Recupera il personale esistente
    $querySelectPersonale = "SELECT dataassunzione, datapromozioneprimario, datapromozioneviceprimario, reparto, ruolo FROM PERSONALE WHERE codicefiscale = :codicefiscale";
    $stmtSelectPersonale = $conn->prepare($querySelectPersonale);
    $stmtSelectPersonale->execute([':codicefiscale' => $codicefiscale]);
    $personale = $stmtSelectPersonale->fetch(PDO::FETCH_ASSOC);

    if ($personale === false) {
        die('Errore: Personale non trovato.');
    }

    // Valori di data attuali
    $currentRuolo = $personale['ruolo'];
    $currentReparto = $personale['reparto'];
    $currentDataAssunzione = $personale['dataassunzione'];
    $currentDataPromozionePrimario = $personale['datapromozioneprimario'];
    $currentDataPromozioneVicePrimario = $personale['datapromozioneviceprimario'];

    $codicefiscale = $_POST['codicefiscale'];

    // Ottieni i valori dei campi
    $ruolo = isset($_POST['ruolo']) ? trim($_POST['ruolo']) : $currentRuolo;
    $reparto = isset($_POST['reparto']) ? trim($_POST['reparto']) : $currentReparto;
    $dataassunzione = isset($_POST['dataassunzione']) ? trim($_POST['dataassunzione']) : $currentDataAssunzione;
    $datapromozioneprimario = isset($_POST['datapromozioneprimario']) ? trim($_POST['datapromozioneprimario']) : $currentDataPromozionePrimario;
    $datapromozioneviceprimario = isset($_POST['datapromozioneviceprimario']) ? trim($_POST['datapromozioneviceprimario']) : $currentDataPromozioneVicePrimario;

    // Controllo della data di assunzione
    if (!empty($dataassunzione)) {
        $dateFormat = 'Y-m-d';
        $d = DateTime::createFromFormat($dateFormat, $dataassunzione);
        if ($d && $d->format($dateFormat) === $dataassunzione) {
            // Data valida
        } else {
            die('Errore: Formato della data di assunzione non valido.');
        }
    } else {
        $dataassunzione = $currentDataAssunzione; // Mantieni il valore corrente se non fornito
    }



    // Controllo della data di promozione a primario
    if (!empty($datapromozioneprimario)) {
        $dateFormat = 'Y-m-d';
        $d = DateTime::createFromFormat($dateFormat, $datapromozioneprimario);
        if ($d && $d->format($dateFormat) === $datapromozioneprimario) {
            // Data valida
        } else {
            die('Errore: Formato della data di promozione a primario non valido.');
        }
    } else {
        $datapromozioneprimario = $currentDataPromozionePrimario; // Mantieni il valore corrente se non fornito
    }

    if (!empty($datapromozioneviceprimario)) {
        $dateFormat = 'Y-m-d';
        $d = DateTime::createFromFormat($dateFormat, $datapromozioneviceprimario);
        if ($d && $d->format($dateFormat) === $datapromozioneviceprimario) {
            // Data valida
        } else {
            die('Errore: Formato della data di promozione a vice primario non valido.');
        }
    } else {
        $datapromozioneviceprimario = $currentDataPromozioneVicePrimario; // Mantieni il valore corrente se non fornito
    }


        // Controllo del reparto
        if (empty($reparto)) {
            $reparto = $currentReparto; // Mantieni il valore corrente se non fornito
        }

    // Controllo del ruolo
    if (empty($ruolo)) {
        $ruolo = $currentRuolo; // Mantieni il valore corrente se non fornito
    }

    if($ruolo !== 'Medico'){
        $datapromozioneviceprimario = null;
        $datapromozioneprimario =   null;
    }
    



    try {
        // Aggiorna il personale esistente
        $queryUpdatePersonale = "
            UPDATE PERSONALE 
            SET ruolo = :ruolo, 
                reparto = :reparto,  -- Numero di telefono del reparto
                dataassunzione = :dataassunzione, 
                datapromozioneprimario = :datapromozioneprimario, 
                datapromozioneviceprimario = :datapromozioneviceprimario 
            WHERE codicefiscale = :codicefiscale
        ";
        $stmtUpdatePersonale = $conn->prepare($queryUpdatePersonale);
        $stmtUpdatePersonale->execute([
            ':codicefiscale' => $codicefiscale,
            ':ruolo' => $ruolo,
            ':reparto' => $reparto,  // Usa il numero di telefono
            ':dataassunzione' => $dataassunzione,
            ':datapromozioneprimario' => $datapromozioneprimario,
            ':datapromozioneviceprimario' => $datapromozioneviceprimario
        ]);
    
        // Reindirizzamento per ripulire il form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento
    
    } catch (PDOException $e) {
        die('Errore durante l\'aggiornamento del personale: ' . $e->getMessage());
    }
}

// Rimozione personale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi'])) {
    $codicefiscale = $_POST['codicefiscale'];

    // Controlla se il codice fiscale è valido
    if (empty($codicefiscale)) {
        die('Errore: Codice Fiscale non fornito.');
    }

    try {
        // Verifica che il personale esista
        $querySelectPersonale = "SELECT codicefiscale FROM PERSONALE WHERE codicefiscale = :codicefiscale";
        $stmtSelectPersonale = $conn->prepare($querySelectPersonale);
        $stmtSelectPersonale->execute([':codicefiscale' => $codicefiscale]);
        $personale = $stmtSelectPersonale->fetch(PDO::FETCH_ASSOC);

        if ($personale === false) {
            die('Errore: Personale non trovato.');
        }

        // Esegui la query di rimozione
        $queryDeletePersonale = "DELETE FROM PERSONALE WHERE codicefiscale = :codicefiscale";
        $stmtDeletePersonale = $conn->prepare($queryDeletePersonale);
        $stmtDeletePersonale->execute([':codicefiscale' => $codicefiscale]);

        // Reindirizzamento per ripulire il form e confermare l'eliminazione
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (PDOException $e) {
        die('Errore durante la rimozione del personale: ' . $e->getMessage());
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
            var table = document.getElementById("personaleTable");
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

        // Funzione per comprimere/espandere la sezione Modifica Personale
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

        // Funzione per comprimere/espandere la sezione Aggiungi Personale
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
        .container{
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
                <h1 class="title">Personale Ospedaliero</h1>
                <div class="return-button">
                    <a href="dba.php" class="btn btn-secondary">Torna alla pagina di Gestione DBA</a>
                </div>
            </div>
        </div>

        <section>
            <div class="container" style="margin-top:2%">
                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title" style="width: 20%">Elenco Personale</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la tabella -->
                            <button id="toggleButton" class="btn btn-primary" onclick="toggleTable()">Comprimi</button>
                        </div>
                    </div>
                </div>


                <!-- Campo di ricerca per reparto (scegli la lista di personale da far vedere in base al loro reparto) -->
                <div class="container" style="margin-top:2%">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline d-flex justify-content-between align-items-center">
                        <div class="form-group" style="width:15%">
                            <label for="reparto" class="mr-2">Seleziona Reparto:</label>
                            <select name="reparto" id="reparto" class="form-control">
                                <option value="">-- Seleziona Reparto --</option>
                                <?php foreach ($reparti as $reparto): ?>
                                    <option value="<?php echo htmlspecialchars($reparto['telefono']); ?>" 
                                            <?php echo ($repartoSelezionato == $reparto['telefono']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($reparto['nome'] . " - " . $reparto['ospedale']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="seleziona_reparto" class="btn btn-primary ml-auto">Mostra Personale</button>
                    </form>
                </div>


                <!-- Tabella per visualizzare i dati del personale -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($personale)): ?>
                                    <table id="personaleTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Codice Fiscale</th>
                                                <th scope="col">Ruolo</th>
                                                <th scope="col">Data Assunzione</th>
                                                <th scope="col">Data Promozione Primario</th>
                                                <th scope="col">Data Promozione Vice-Primario</th>
                                                <th scope="col">Numero Telefono Reparto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($personale as $index => $Persona): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($Persona['codicefiscale']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['ruolo']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['dataassunzione']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['datapromozioneprimario']); ?></td>
                                                    <td><?php echo htmlspecialchars($Persona['datapromozioneviceprimario']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($Persona['reparto']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Nessun personale trovato.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <!--Modifica Personale-->
                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Modifica Personale</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Modifica Personale -->
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
                                            <!--Codice Fiscale (per identificare il personale da modificare)-->
                                            <div class="mb-3">
                                                <label for="codicefiscale" class="form-label">Codice Fiscale</label>
                                                <input type="text" class="form-control" id="codicefiscale"
                                                    name="codicefiscale"
                                                    value="<?php echo isset($_POST['codicefiscale']) ? htmlspecialchars($_POST['codicefiscale']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Ruolo-->
                                            <div class="mb-3">
                                                <label for="ruolo" class="form-label">Ruolo</label>
                                                <input type="text" class="form-control" id="ruolo" name="ruolo"
                                                    value="<?php echo isset($_POST['ruolo']) ? htmlspecialchars($_POST['ruolo']) : ''; ?>">
                                            </div>
                                            <!-- Reparto (dropdown) -->
                                            <div class="mb-3">
                                                <label for="reparto" class="form-label">Reparto - Numero di Telefono</label>
                                                <select class="form-control" id="reparto" name="reparto">
                                                    <option value="">Seleziona un reparto</option>
                                                    <?php foreach ($reparti as $reparto): ?>
                                                        <option value="<?php echo htmlspecialchars($reparto['telefono']); ?>"
                                                            <?php echo (isset($_POST['reparto']) && $_POST['reparto'] == $reparto['telefono']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($reparto['nome']); ?>
                                                            <?php echo '-'; ?>
                                                            <?php echo htmlspecialchars($reparto['telefono']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <!--Data Assunzione-->
                                            <div class="mb-3">
                                                <label for="dataassunzione" class="form-label">Data Assunzione</label>
                                                <input type="date" class="form-control" id="dataassunzione"
                                                    name="dataassunzione"
                                                    value="<?php echo isset($_POST['dataassunzione']) ? htmlspecialchars($_POST['dataassunzione']) : ''; ?>">

                                            </div>
                                            <!--Data Promozione Primario-->
                                            <div class="mb-3">
                                                <label for="datapromozioneprimario" class="form-label">Data Promozione
                                                    Primario</label>
                                                <input type="date" class="form-control" id="datapromozioneprimario"
                                                    name="datapromozioneprimario"
                                                    value="<?php echo isset($_POST['datapromozioneprimario']) ? htmlspecialchars($_POST['datapromozioneprimario']) : ''; ?>">
                                            </div>
                                            <!--Data Promozione Vice-Primario-->
                                            <div class="mb-3">
                                                <label for="datapromozioneviceprimario" class="form-label">Data
                                                    Promozione
                                                    Vice-Primario</label>
                                                <input type="date" class="form-control" id="datapromozioneviceprimario"
                                                    name="datapromozioneviceprimario"
                                                    value="<?php echo isset($_POST['datapromozioneviceprimario']) ? htmlspecialchars($_POST['datapromozioneviceprimario']) : ''; ?>">
                                            </div>

                                            <button type="submit" name="modifica" class="btn btn-success"
                                                style="float:right">Modifica</button>
                                            <button type="submit" name="rimuovi" class="btn btn-danger" style="float:right; margin-right: 10px;">Rimuovi</button>

                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                

                    <!--Aggiungi Personale-->
                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center" style="margin-top:2%">
                        <h2 class="card-title">Aggiungi Personale</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Aggiungi Personale -->
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
                                            <!--Codice Fiscale-->
                                            <div class="mb-3">
                                                <label for="codicefiscale" class="form-label">Codice Fiscale</label>
                                                <input type="text" class="form-control" id="codicefiscale"
                                                    name="codicefiscale"
                                                    value="<?php echo htmlspecialchars(isset($_POST['codicefiscale']) ? $_POST['codicefiscale'] : $codicefiscale); ?>"
                                                    required>
                                            </div>
                                            <!--Ruolo-->
                                            <div class="mb-3">
                                                <label for="ruolo" class="form-label">Ruolo</label>
                                                <select class="form-control" id="ruolo" name="ruolo" required>
                                                    <option value="">Seleziona un ruolo</option>
                                                    <option value="Medico" <?php echo (isset($_POST['ruolo']) && $_POST['ruolo'] == 'Medico') ? 'selected' : ''; ?>>Medico</option>
                                                    <option value="Infermiere" <?php echo (isset($_POST['ruolo']) && $_POST['ruolo'] == 'Infermiere') ? 'selected' : ''; ?>>Infermiere</option>
                                                    <option value="Amministrativo" <?php echo (isset($_POST['ruolo']) && $_POST['ruolo'] == 'Amministrativo') ? 'selected' : ''; ?>>Amministrativo</option>
                                                </select>
                                            </div>
                                            
                                            <!-- Reparto (dropdown) -->
                                            <div class="mb-3">
                                                <label for="reparto" class="form-label">Reparto - Numero di Telefono</label>
                                                <select class="form-control" id="reparto" name="reparto">
                                                    <option value="">Seleziona un reparto</option>
                                                    <!-- Opzione vuota -->
                                                    <?php foreach ($reparti as $reparto): ?>
                                                        <option value="<?php echo htmlspecialchars($reparto['telefono']); ?>"
                                                            <?php echo (isset($_POST['reparto']) && $_POST['reparto'] == $reparto['telefono']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($reparto['nome']); ?>
                                                            <?php echo '-'; ?>
                                                            <?php echo htmlspecialchars($reparto['telefono']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <!--Data Assunzione-->
                                            <div class="mb-3">
                                                <label for="dataassunzione" class="form-label">Data Assunzione</label>
                                                <input type="date" class="form-control" id="dataassunzione"
                                                    name="dataassunzione"
                                                    value="<?php echo isset($_POST['dataassunzione']) ? htmlspecialchars($_POST['dataassunzione']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Data Promozione Primario-->
                                            <div class="mb-3">
                                                <label for="datapromozioneprimario" class="form-label">Data Promozione
                                                    Primario</label>
                                                <input type="date" class="form-control" id="datapromozioneprimario"
                                                    name="datapromozioneprimario"
                                                    value="<?php echo isset($_POST['datapromozioneprimario']) ? htmlspecialchars($_POST['datapromozioneprimario']) : ''; ?>"
                                                    >
                                            </div>
                                            <!--Data Promozione Vice-Primario-->
                                            <div class="mb-3">
                                                <label for="datapromozioneviceprimario" class="form-label">Data
                                                    Promozione
                                                    Vice-Primario</label>
                                                <input type="date" class="form-control" id="datapromozioneviceprimario"
                                                    name="datapromozioneviceprimario"
                                                    value="<?php echo isset($_POST['datapromozioneviceprimario']) ? htmlspecialchars($_POST['datapromozioneviceprimario']) : ''; ?>">
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