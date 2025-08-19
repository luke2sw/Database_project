<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Inizializza la variabile $codicefiscale con una stringa vuota
$codicefiscale = '';


// Verifica se è stato inserito un codice esame nella ricerca
if (isset($_GET['search_esame'])) {
    $search_esame = trim($_GET['search_esame']);
}

try {
    // Query per ottenere le informazioni dell'esame, filtrata per codice se è stata eseguita una ricerca
    if (!empty($search_esame)) {
        // Se è presente un codice da cercare, esegui la query filtrata
        $queryDba_Gestione_Esami = "SELECT * FROM ESAME 
                                    LEFT JOIN ESAMEHAAVVERTENZA on ESAME.codice = ESAMEHAAVVERTENZA.esame 
                                    WHERE ESAME.codice LIKE :search_esame";
        $stmt = $conn->prepare($queryDba_Gestione_Esami);
        $stmt->execute([':search_esame' => '%' . $search_esame . '%']);
    } else {
        // Altrimenti esegui la query senza filtri
        $queryDba_Gestione_Esami = "SELECT * FROM ESAME 
                                    LEFT JOIN ESAMEHAAVVERTENZA on ESAME.codice = ESAMEHAAVVERTENZA.esame
                                    ORDER BY ESAME.codice ASC";
        $stmt = $conn->prepare($queryDba_Gestione_Esami);
        $stmt->execute();
    }
    // Ottieni tutti i risultati come array associativo
    $Esami = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
}



function checkEsameEsistente($conn, $codice)
{
    // Query per controllare l'esistenza dell'esame con quel codice 
    $query = "SELECT COUNT(*) FROM ESAME WHERE codice = :codice";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':codice' => $codice]);

        // Ottieni il conteggio dell'eame con quel codice 
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno un esame con quel codice, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo della prenotazione: ' . $e->getMessage());
    }
}

//aggiungi esame
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungi'])) {
    $codice = $_POST['codice'];

    if (!checkEsameEsistente($conn, $codice)) {
        $descrizione = isset($_POST['descrizione']) ? trim($_POST['descrizione']) : '';
        $costopubblico = isset($_POST['costopubblico']) ? trim($_POST['costopubblico']) : null;
        $costoprivato = isset($_POST['costoprivato']) ? trim($_POST['costoprivato']) : null;
        $specializzazione = isset($_POST['specializzazione']) ? trim($_POST['specializzazione']) : null;
        $avvertenza = isset($_POST['avvertenza']) ? trim($_POST['avvertenza']) : null;

        try {
            // Inserisci il nuovo esame nella tabella ESAME
            $queryInsertEsame = "
                INSERT INTO ESAME (codice, descrizione, costopubblico, costoprivato, specializzazione)
                VALUES (:codice, :descrizione, :costopubblico, :costoprivato, :specializzazione)
            ";
            $stmtInsertEsame = $conn->prepare($queryInsertEsame);
            $stmtInsertEsame->execute([
                ':codice' => $codice,
                ':descrizione' => $descrizione,
                ':costopubblico' => $costopubblico,
                ':costoprivato' => $costoprivato,
                ':specializzazione' => $specializzazione,
            ]);



            // Se c'è un'avvertenza, inseriscila nella tabella AVVERTENZE e ESAMEHAAVVERTENZA
            if (!empty($avvertenza)) {

                $queryInsertAvvertenza = "INSERT INTO AVVERTENZE (Descrizione) VALUES (:avvertenza)";
                $stmtInsertAvvertenza = $conn->prepare($queryInsertAvvertenza);
                $stmtInsertAvvertenza->execute([':avvertenza' => $avvertenza]);


                // Controlla se l'avvertenza è già associata all'esame
                $queryCheckAssociation = "SELECT COUNT(*) FROM ESAMEHAAVVERTENZA WHERE esame = :codice AND avvertenza = :avvertenza";
                $stmtCheckAssociation = $conn->prepare($queryCheckAssociation);
                $stmtCheckAssociation->execute([
                    ':codice' => $codice,
                    ':avvertenza' => $avvertenza
                ]);
                $associationExists = $stmtCheckAssociation->fetchColumn();

                if ($associationExists == 0) {
                    // Inserisci la nuova associazione
                    $queryInsertAssociation = "
                        INSERT INTO ESAMEHAAVVERTENZA (esame, avvertenza)
                        VALUES (:codice, :lastAvvertenzaId)
                    ";
                    $stmtInsertAssociation = $conn->prepare($queryInsertAssociation);
                    $stmtInsertAssociation->execute([
                        ':codice' => $codice,
                        ':lastAvvertenzaId' => $avvertenza
                    ]);
                }
            }

            // Reindirizzamento per ripulire il form
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;

        } catch (PDOException $e) {
            die('Errore durante l\'inserimento dell\'esame: ' . $e->getMessage());
        }
    } else {
        echo 'Esame già presente.';
    }
}





//modifica Esame
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifica'])) {
    $codice = $_POST['codice'];



    // Recupera l'esame esistente
    $querySelectEsame = "SELECT descrizione, costopubblico, costoprivato, specializzazione, avvertenza FROM ESAME LEFT JOIN ESAMEHAAVVERTENZA on ESAME.codice = ESAMEHAAVVERTENZA.esame WHERE codice = :codice";
    $stmtSelectEsame = $conn->prepare($querySelectEsame);
    $stmtSelectEsame->execute([':codice' => $codice]);
    $esame = $stmtSelectEsame->fetch(PDO::FETCH_ASSOC);

    if ($esame === false) {
        die('Errore: Esame non trovato.');
    }

    // Valori di data attuali
    $currentDescrizione = $esame['descrizione'];
    $currentCostoPubblico = $esame['costopubblico'];
    $currentCostoPrivato = $esame['costoprivato'];
    $currentSpecializzazione = $esame['specializzazione'];
    $currentAvvertenza = $esame['avvertenza'];

    // Ottieni i valori dei campi
    $descrizione = isset($_POST['descrizione']) ? trim($_POST['descrizione']) : $currentDescrizione;
    $costopubblico = isset($_POST['costopubblico']) ? trim($_POST['costopubblico']) : $currentCostoPubblico;
    $costoprivato = isset($_POST['costoprivato']) ? trim($_POST['costoprivato']) : $currentCostoPrivato;
    $specializzazione = isset($_POST['specializzazione']) ? trim($_POST['specializzazione']) : $currentSpecializzazione;
    $avvertenza = isset($_POST['avvertenza']) ? trim($_POST['avvertenza']) : $currentAvvertenza;


    // Controllo della descrizione
    if (empty($descrizione)) {
        $descrizione = $currentDescrizione; // Mantieni il valore corrente se non fornito
    }

    // Controllo del costo pubblico
    if (empty($costopubblico)) {
        $costopubblico = $currentCostoPubblico; // Mantieni il valore corrente se non fornito
    }

    // Controllo del costo privato
    if (empty($costoprivato)) {
        $costoprivato = $currentCostoPrivato; // Mantieni il valore corrente se non fornito
    }

    // Controllo della specializzazione
    if (empty($specializzazione)) {
        $specializzazione = $currentSpecializzazione; // Mantieni il valore corrente se non fornito
    }

    // Controllo dell'avvertenza
    if (empty($avvertenza)) {
        $avvertenza = $currentAvvertenza; // Mantieni il valore corrente se non fornito
    }





    try {
        // Aggiorna i dati dell'esame nella tabella ESAME
        $queryUpdateEsame = "
            UPDATE ESAME
            SET descrizione = :descrizione, 
                costopubblico = :costopubblico, 
                costoprivato = :costoprivato, 
                specializzazione = :specializzazione
            WHERE codice = :codice
        ";
        $stmtUpdateEsame = $conn->prepare($queryUpdateEsame);
        $stmtUpdateEsame->execute([
            ':codice' => $codice,
            ':descrizione' => $descrizione,
            ':costopubblico' => $costopubblico,
            ':costoprivato' => $costoprivato,
            ':specializzazione' => $specializzazione,
        ]);

        // Gestione dell'avvertenza
        if (!empty($avvertenza)) {
            // Verifica se l'avvertenza esiste nella tabella AVVERTENZE
            $queryCheckAvvertenza = "SELECT COUNT(*) FROM AVVERTENZE WHERE Descrizione = :avvertenza";
            $stmtCheckAvvertenza = $conn->prepare($queryCheckAvvertenza);
            $stmtCheckAvvertenza->execute([':avvertenza' => $avvertenza]);
            $avvertenzaExists = $stmtCheckAvvertenza->fetchColumn();

            if ($avvertenzaExists == 0) {
                // Inserisci l'avvertenza nella tabella AVVERTENZE se non esiste
                $queryInsertAvvertenza = "INSERT INTO AVVERTENZE (Descrizione) VALUES (:avvertenza)";
                $stmtInsertAvvertenza = $conn->prepare($queryInsertAvvertenza);
                $stmtInsertAvvertenza->execute([':avvertenza' => $avvertenza]);
            }

            // Inserisci o aggiorna la relazione nella tabella ESAMEHAAVVERTENZA
            $queryInsertOrUpdateEsameAvvertenza = "
                INSERT INTO ESAMEHAAVVERTENZA (Esame, Avvertenza)
                VALUES (:codice, :avvertenza)
                ON CONFLICT (Esame, Avvertenza) 
                DO UPDATE SET Avvertenza = EXCLUDED.Avvertenza
            ";
            $stmtInsertOrUpdateEsameAvvertenza = $conn->prepare($queryInsertOrUpdateEsameAvvertenza);
            $stmtInsertOrUpdateEsameAvvertenza->execute([
                ':codice' => $codice,
                ':avvertenza' => $avvertenza,
            ]);
        }


        // Reindirizzamento per ripulire il form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (PDOException $e) {
        die('Errore durante l\'aggiornamento dell\'esame: ' . $e->getMessage());
    }
}

$queryAvvertenze = "SELECT * FROM AVVERTENZE";
$stmtAvvertenza = $conn->prepare($queryAvvertenze);
$stmtAvvertenza->execute();
$avvertenze = $stmtAvvertenza->fetchAll(PDO::FETCH_ASSOC);



//rimuovi esame
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi_esame'])) {
    $esameCodice = $_POST['esame_codice'];

    if (empty($esameCodice)) {
        die('Errore: Codice esame non fornito.');
    }

    try {
        // Inizia una transazione
        $conn->beginTransaction();

        $queryGetAvvertenza = "SELECT Avvertenza FROM ESAMEHAAVVERTENZA WHERE Esame = :esameCodice";
        $stmtGetAvvertenza = $conn->prepare($queryGetAvvertenza);
        $stmtGetAvvertenza->execute([':esameCodice' => $esameCodice]);
        $avvertenza = $stmtGetAvvertenza->fetchColumn();

        // Controlla se l'avvertenza è associata a più di un esame
        $queryCountEsame = "SELECT COUNT(*) FROM ESAMEHAAVVERTENZA WHERE esame = :esameCodice";
        $stmtCountEsame = $conn->prepare($queryCountEsame);
        $stmtCountEsame->execute([':esameCodice' => $esameCodice]);
        $countExam = $stmtCountEsame->fetchColumn();
        if ($countExam > 1) {
            // Rimuovi le avvertenze associate all'esame
            $queryDeleteAvvertenze = "DELETE FROM ESAMEHAAVVERTENZA WHERE Esame = :esame and Avvertenza = :avvertenza";
            $stmtDeleteAvvertenze = $conn->prepare($queryDeleteAvvertenze);
            $stmtDeleteAvvertenze->execute([':esame' => $esameCodice, ':avvertenza' => $avvertenza]);

            // Rimuovi l'avvertenza
            $queryDeleteAvvertenzaDesc = "DELETE FROM AVVERTENZE WHERE descrizione = :avvertenza";
            $stmtDeleteAvvertenzaDesc = $conn->prepare($queryDeleteAvvertenzaDesc);
            $stmtDeleteAvvertenzaDesc->execute([':avvertenza' => $avvertenza]);


        } else {
            // Rimuovi le avvertenze associate all'esame
            $queryDeleteAvvertenze = "DELETE FROM ESAMEHAAVVERTENZA WHERE Esame = :esame";
            $stmtDeleteAvvertenze = $conn->prepare($queryDeleteAvvertenze);
            $stmtDeleteAvvertenze->execute([':esame' => $esameCodice]);

            // Rimuovi l'avvertenza
            $queryDeleteAvvertenzaDesc = "DELETE FROM AVVERTENZE WHERE descrizione = :avvertenza";
            $stmtDeleteAvvertenzaDesc = $conn->prepare($queryDeleteAvvertenzaDesc);
            $stmtDeleteAvvertenzaDesc->execute([':avvertenza' => $avvertenza]);

            // Rimuovi l'esame
            $queryDeleteEsame = "DELETE FROM ESAME WHERE Codice = :esame";
            $stmtDeleteEsame = $conn->prepare($queryDeleteEsame);
            $stmtDeleteEsame->execute([':esame' => $esameCodice]);

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
    <title>Gestione Esami</title>
    
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



    <script>
        function getAvvertenzeByEsame($codiceEsame) {
            global $conn;
            $query = "SELECT Avvertenza FROM ESAMEHAAVVERTENZA WHERE Esame = :esame";
            $stmt = $conn -> prepare($query);
            $stmt -> execute([':esame' => $codiceEsame]);
            return $stmt -> fetchAll(PDO:: FETCH_COLUMN);
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
                <h1 class="title">Esami dell'Ospedale</h1>
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
                        <h2 class="card-title" style="width: 30%">Elenco Esami</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la tabella -->
                            <button id="toggleButton" class="btn btn-primary" onclick="toggleTable()">Comprimi</button>
                        </div>
                    </div>
                </div>

                <!-- Campo di ricerca per il codice dell'esame -->
                <div class="container" style="margin-top:2%">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" name="search_esame" placeholder="Cerca per Codice"
                                value="<?php echo isset($_GET['search_esame']) ? htmlspecialchars($_GET['search_esame']) : ''; ?>">
                            <button class="btn btn-primary" type="submit" style="width:7.5%">Cerca</button>
                        </div>
                    </form>
                </div>

                <!-- Tabella per visualizzare l'elenco degli esami -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($Esami)): ?>
                                    <table id="pazienteTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Codice</th>
                                                <th scope="col">Descrizione</th>
                                                <th scope="col">Costo Pubblico</th>
                                                <th scope="col">Costo Privato</th>
                                                <th scope="col">Specializzazione</th>
                                                <th scope="col">Avvertenza</th>
                                                <th scope="col">Rimuovi</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($Esami as $index => $esame): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($esame['codice']); ?></td>
                                                    <td><?php echo htmlspecialchars($esame['descrizione']); ?></td>
                                                    <td><?php echo htmlspecialchars($esame['costopubblico']); ?></td>
                                                    <td><?php echo htmlspecialchars($esame['costoprivato']); ?></td>
                                                    <td><?php echo htmlspecialchars($esame['specializzazione']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($esame['avvertenza']); ?>
                                                    </td>
                                                    <td>
                                                        <form method="POST"
                                                            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <input type="hidden" name="esame_codice"
                                                                value="<?php echo htmlspecialchars($esame['codice']); ?>">
                                                            <button type="submit" name="rimuovi_esame"
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

                <!--Modifica Personale-->
                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Modifica Esame</h2>
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
                                            <!--Codice Esame (per identificare l'esame da modificare)-->
                                            <div class="mb-3">
                                                <label for="codice" class="form-label">Codice Esame</label>
                                                <input type="text" class="form-control" id="codice" name="codice"
                                                    value="<?php echo isset($_POST['codice']) ? htmlspecialchars($_POST['codice']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Descrizione-->
                                            <div class="mb-3">
                                                <label for="descrizione" class="form-label">Descrizione</label>
                                                <input type="text" class="form-control" id="descrizione"
                                                    name="descrizione"
                                                    value="<?php echo isset($_POST['descrizione']) ? htmlspecialchars($_POST['descrizione']) : ''; ?>">
                                            </div>
                                            <!--Costo Pubblico-->
                                            <div class="mb-3">
                                                <label for="costopubblico" class="form-label"> Costo Pubblico
                                                </label>
                                                <input type="text" class="form-control" id="costopubblico"
                                                    name="costopubblico"
                                                    value="<?php echo isset($_POST['costopubblico']) ? htmlspecialchars($_POST['costopubblico']) : ''; ?>">
                                            </div>
                                            <!--Costo Privato-->
                                            <div class="mb-3">
                                                <label for="costoprivato" class="form-label"> Costo Privato </label>
                                                <input type="text" class="form-control" id="costoprivato"
                                                    name="costoprivato"
                                                    value="<?php echo isset($_POST['costoprivato']) ? htmlspecialchars($_POST['costoprivato']) : ''; ?>">
                                            </div>
                                            <!--Specializzazione -->
                                            <div class="mb-3">
                                                <label for="specializzazione" class="form-label"> Specializzazione
                                                </label>
                                                <input type="text" class="form-control" id="specializzazione"
                                                    name="specializzazione"
                                                    value="<?php echo isset($_POST['specializzazione']) ? htmlspecialchars($_POST['specializzazione']) : ''; ?>">
                                            </div>
                                            <!--Avvertenza-->
                                            <div class="mb-3">
                                                <label for="avvertenza" class="form-label"> Aggiungi Avvertenza </label>
                                                <input type="text" class="form-control" id="avvertenza"
                                                    name="avvertenza"
                                                    value="<?php echo isset($_POST['avvertenza']) ? htmlspecialchars($_POST['avvertenza']) : ''; ?>">
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
                <div class="d-flex justify-content-between align-items-center" style="margin-top:2%">
                    <h2 class="card-title">Aggiungi Esame</h2>
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
                                        <!--Codice Esame (per identificare l'esame da modificare)-->
                                        <div class="mb-3">
                                            <label for="codice" class="form-label">Codice Esame</label>
                                            <input type="text" class="form-control" id="codice" name="codice"
                                                value="<?php echo isset($_POST['codice']) ? htmlspecialchars($_POST['codice']) : ''; ?>"
                                                required>
                                        </div>
                                        <!--Descrizione-->
                                        <div class="mb-3">
                                            <label for="descrizione" class="form-label">Descrizione</label>
                                            <input type="text" class="form-control" id="descrizione" name="descrizione"
                                                value="<?php echo isset($_POST['descrizione']) ? htmlspecialchars($_POST['descrizione']) : ''; ?>">
                                        </div>
                                        <!--Costo Pubblico-->
                                        <div class="mb-3">
                                            <label for="costopubblico" class="form-label"> Costo Pubblico
                                            </label>
                                            <input type="text" class="form-control" id="costopubblico"
                                                name="costopubblico"
                                                value="<?php echo isset($_POST['costopubblico']) ? htmlspecialchars($_POST['costopubblico']) : ''; ?>">
                                        </div>
                                        <!--Costo Privato-->
                                        <div class="mb-3">
                                            <label for="costoprivato" class="form-label"> Costo Privato </label>
                                            <input type="text" class="form-control" id="costoprivato"
                                                name="costoprivato"
                                                value="<?php echo isset($_POST['costoprivato']) ? htmlspecialchars($_POST['costoprivato']) : ''; ?>">
                                        </div>
                                        <!--Specializzazione -->
                                        <div class="mb-3">
                                            <label for="specializzazione" class="form-label"> Specializzazione
                                            </label>
                                            <input type="text" class="form-control" id="specializzazione"
                                                name="specializzazione"
                                                value="<?php echo isset($_POST['specializzazione']) ? htmlspecialchars($_POST['specializzazione']) : ''; ?>">
                                        </div>
                                        <!--Avvertenza-->
                                        <div class="mb-3">
                                            <label for="avvertenza" class="form-label"> Avvertenza </label>
                                            <input type="text" class="form-control" id="avvertenza" name="avvertenza"
                                                value="<?php echo isset($_POST['avvertenza']) ? htmlspecialchars($_POST['avvertenza']) : ''; ?>">
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