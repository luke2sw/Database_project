<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Inizializza la variabile $codicefiscale con una stringa vuota
$codicefiscale = '';

if (isset($_COOKIE['username']) && isset($_COOKIE['codice_utente']) && isset($_COOKIE['ruolo'])) {
    $username = $_COOKIE['username'];
    $codice_utente = $_COOKIE['codice_utente'];
    $ruolo = $_COOKIE['ruolo'];

    if ($ruolo !== 'PersonaleAmministrativo') {
        header('Location: login.php');
        exit;
    }

    // Verifica se è stato inserito un codice fiscale nella ricerca
    if (isset($_GET['search_codicefiscale'])) {
        $search_codicefiscale = trim($_GET['search_codicefiscale']);
    }

    try {
        // Query per ottenere le informazioni del personale, filtrata per codice fiscale se è stata eseguita una ricerca
        if (!empty($search_codicefiscale)) {
            // Se è presente un codice fiscale da cercare, esegui la query filtrata
            $queryGestione_Personale = "SELECT * FROM PERSONALE WHERE codicefiscale LIKE :search_codicefiscale";
            $stmt = $conn->prepare($queryGestione_Personale);
            $stmt->execute([':search_codicefiscale' => '%' . $search_codicefiscale . '%']);
        } else {
            // Altrimenti esegui la query senza filtri
            $queryGestione_Personale = "SELECT * FROM PERSONALE";
            $stmt = $conn->prepare($queryGestione_Personale);
            $stmt->execute();
        }
        // Ottieni tutti i risultati come array associativo
        $Personale = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }

    try {
        // Query per ottenere tutti i reparti dal database
        $queryReparti = "SELECT * FROM REPARTO"; 
        $stmtReparti = $conn->prepare($queryReparti);
        $stmtReparti->execute();
        $reparti = $stmtReparti->fetchAll(PDO::FETCH_ASSOC); // Ottiene tutti i reparti come array associativo
    } catch (PDOException $e) {
        die('Errore durante il recupero dei reparti: ' . $e->getMessage());
    }


} else {
    header('Location: login.php');
    exit;
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

        if (empty($datapromozioneviceprimario)) {
            $datapromozioneprimario = null;
        }



        if ($ruolo !== 'Medico') {
            $datapromozioneviceprimario = null;
            $datapromozioneprimario = null;
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
        // Il personale esiste già
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


    // Ottieni i valori dei campi
    $ruolo = isset($_POST['ruolo']) ? trim($_POST['ruolo']) : $currentRuolo;
    $reparto = isset($_POST['reparto']) ? trim($_POST['reparto']) : $currentReparto;
    $dataassunzione = isset($_POST['dataassunzione']) ? trim($_POST['dataassunzione']) : $currentDataAssunzione;
    $datapromozioneprimario = isset($_POST['datapromozioneprimario']) && !empty($_POST['datapromozioneprimario']) ? trim($_POST['datapromozioneprimario']) : null;
    $datapromozioneviceprimario = isset($_POST['datapromozioneviceprimario']) && !empty($_POST['datapromozioneviceprimario']) ? trim($_POST['datapromozioneviceprimario']) : null;
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

    // Mantieni il ruolo attuale se non viene specificato un nuovo valore
    if (empty($ruolo)) {
        $ruolo = $currentRuolo;
    }


    // Mantieni il ruolo attuale se non viene specificato un nuovo valore
    if (empty($reparto)) {
        $reparto = $currentReparto;

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

    //controlla che ci sia una data di promozione a vice primario se si vuole inserire una data di promozione a primario
    if (empty($datapromozioneviceprimario)) {
        $datapromozioneprimario = null;
    }

    //controlla che solo un medico possa essere promosso a primario/vice primario
    if ($ruolo !== 'Medico') {
        $datapromozioneviceprimario = null;
        $datapromozioneprimario = null;
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
                <h1 class="title">Personale Ospedaliero</h1>
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
                        <h2 class="card-title" style="width: 20%">Elenco Personale</h2>
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
                            <input type="text" class="form-control" name="search_codicefiscale"
                                placeholder="Cerca per Codice Fiscale"
                                value="<?php echo isset($_GET['search_codicefiscale']) ? htmlspecialchars($_GET['search_codicefiscale']) : ''; ?>">
                            <button class="btn btn-primary" type="submit" style="width:7.5%">Cerca</button>
                        </div>
                    </form>
                </div>

                <!-- Tabella per visualizzare i dati del personale -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($Personale)): ?>
                                    <table id="personaleTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Codice Fiscale</th>
                                                <th scope="col">Ruolo</th>
                                                <th scope="col">Data Assunzione</th>
                                                <th scope="col">Data Promozione Primario</th>
                                                <th scope="col">Data Promozione Vice-Primario</th>
                                                <th scope="col">Reparto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($Personale as $index => $Persona): ?>
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
                                                <label for="reparto" class="form-label">Reparto</label>
                                                <select class="form-control" id="reparto" name="reparto">
                                                    <option value="">Seleziona un reparto</option>
                                                    <?php foreach ($reparti as $reparto): ?>
                                                        <option
                                                            value="<?php echo htmlspecialchars($reparto['telefono']); ?>"
                                                            <?php echo (isset($_POST['reparto']) && $_POST['reparto'] == $reparto['telefono']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($reparto['nome']); ?> -
                                                            <?php echo htmlspecialchars($reparto['ospedale']); ?>
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
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!--Aggiungi Personale-->
                <!-- Flex container per il titolo e il pulsante di comprimi -->
                <div class="d-flex justify-content-between align-items-center" style="margin-top:1%">
                    <h2 class="card-title">Aggiungi Personale</h2>
                    <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                        <!-- Bottone per comprimere/espandere la sezione Aggiungi Personale -->
                        <button id="toggleAggiungi" class="btn btn-primary" onclick="toggleAggiungi()">Comprimi</button>
                    </div>
                </div>
                <div id="aggiungiSection">
                    <div class="row" style="margin-top:1%; margin-bottom:3%">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
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
                                            <input type="text" class="form-control" id="ruolo" name="ruolo"
                                                value="<?php echo isset($_POST['ruolo']) ? htmlspecialchars($_POST['ruolo']) : ''; ?>"
                                                required>
                                        </div>
                                        <!-- Reparto (dropdown) -->
                                        <div class="mb-3">
                                            <label for="reparto" class="form-label">Reparto</label>
                                            <select class="form-control" id="reparto" name="reparto">
                                                <option value="">Seleziona un reparto</option>
                                                <?php foreach ($reparti as $reparto): ?>
                                                    <option value="<?php echo htmlspecialchars($reparto['telefono']); ?>"
                                                        <?php echo (isset($_POST['reparto']) && $_POST['reparto'] == $reparto['telefono']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($reparto['nome']); ?> -
                                                        <?php echo htmlspecialchars($reparto['ospedale']); ?>
                                                    </option required>
                                                <?php endforeach; ?>
                                            </select >
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
                                                value="<?php echo isset($_POST['datapromozioneprimario']) ? htmlspecialchars($_POST['datapromozioneprimario']) : ''; ?>">
                                        </div>
                                        <!--Data Promozione Vice-Primario-->
                                        <div class="mb-3">
                                            <label for="datapromozioneviceprimario" class="form-label">Data Promozione Vice-Primario</label>
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