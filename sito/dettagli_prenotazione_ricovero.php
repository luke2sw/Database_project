<?php
session_start(); // Inizializza la sessione

include 'connection_db.php'; // Include il file di connessione

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Controlla se i parametri sono passati via URL
if (isset($_GET['dataoraesame']) && isset($_GET['dataprenotazione'])) {
    $_SESSION['dataoraesame'] = $_GET['dataoraesame'];
    $_SESSION['dataprenotazione'] = $_GET['dataprenotazione'];
    $_SESSION['paziente'] = $_GET['paziente'];

    $dataoraesame = $_SESSION['dataoraesame'];
    $dataprenotazione = $_SESSION['dataprenotazione'];
    $paziente = $_SESSION['paziente'];

    try {
        $queryRicovero = "
            SELECT DISTINCT *
        FROM RICOVERO
        JOIN PRENOTAZIONE ON RICOVERO.paziente = PRENOTAZIONE.paziente
        LEFT JOIN RICOVEROPATOLOGIA ON RICOVERO.DataInizio = RICOVEROPATOLOGIA.Ricovero
            AND RICOVERO.paziente = RICOVEROPATOLOGIA.Paziente
        WHERE PRENOTAZIONE.dataoraesame = :dataoraesame
          AND PRENOTAZIONE.dataprenotazione = :dataprenotazione
            AND RICOVERO.paziente = :paziente
    ";



        $stmt = $conn->prepare($queryRicovero);
        $stmt->execute([':dataoraesame' => $dataoraesame, ':dataprenotazione' => $dataprenotazione, ':paziente' => $paziente]);
        $ricoveri = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }
} elseif (isset($_SESSION['dataoraesame']) && isset($_SESSION['dataprenotazione'])) {
    // Recupera i parametri dalla sessione
    $dataoraesame = $_SESSION['dataoraesame'];
    $dataprenotazione = $_SESSION['dataprenotazione'];
    $paziente = $_SESSION['paziente'];

    try {
        $queryRicovero = "
            SELECT DISTINCT *
        FROM RICOVERO
        JOIN PRENOTAZIONE ON RICOVERO.paziente = PRENOTAZIONE.paziente
        LEFT JOIN RICOVEROPATOLOGIA ON RICOVERO.DataInizio = RICOVEROPATOLOGIA.Ricovero
            AND RICOVERO.paziente = RICOVEROPATOLOGIA.Paziente
        WHERE PRENOTAZIONE.dataoraesame = :dataoraesame
          AND PRENOTAZIONE.dataprenotazione = :dataprenotazione
          AND RICOVERO.paziente = :paziente
    ";


        $stmt = $conn->prepare($queryRicovero);
        $stmt->execute([':dataoraesame' => $dataoraesame, ':dataprenotazione' => $dataprenotazione, ':paziente' => $paziente]);
        $ricoveri = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }
} else {
    echo "Codice esame non specificato.";
    exit;
}

// Gestione del modulo di aggiornamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifica'])) {
    $letto = $_POST['letto'] ?? ''; // Può essere vuoto se non fornito
    $datadimissione = $_POST['datadimissione'] ?? ''; // Può essere vuoto se non fornito

    $dataoraesame = $_SESSION['dataoraesame'];
    $dataprenotazione = $_SESSION['dataprenotazione'];

    try {
        // Recupera l'attuale Letto e DataDimissione se non forniti
        if (empty($letto) || empty($datadimissione)) {
            $queryGetLettoDimissione = "
                SELECT Letto, DataDimissione
                FROM RICOVERO
                WHERE DATE(DataInizio) = DATE(:dataoraesame) AND Paziente = (
                    SELECT Paziente 
                    FROM PRENOTAZIONE
                    WHERE DATE(DataOraEsame) = DATE(:dataoraesame) AND DataPrenotazione = :dataprenotazione
                )
            ";
            $stmt = $conn->prepare($queryGetLettoDimissione);
            $stmt->execute([':dataoraesame' => $dataoraesame, ':dataprenotazione' => $dataprenotazione]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Mantieni il valore attuale se non fornito dall'utente
            if (empty($letto)) {
                $letto = $row['Letto'];
            }
            if (empty($datadimissione)) {
                $datadimissione = $row['DataDimissione'];
            }
        }

        // Verifica se la data di dimissione inserita è valida
        if (!empty($datadimissione)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $datadimissione);
            if ($d && $d->format($dateFormat) !== $datadimissione) {
                die('Errore: Formato della data di dimissione non valido.');
            }
        }

        // Creazione della query di aggiornamento in base alla presenza di Letto e DataDimissione
        $updateQuery = "
            UPDATE RICOVERO
            SET " .
            (!empty($letto) ? "Letto = :letto " : "") .
            (!empty($letto) && !empty($datadimissione) ? "," : "") .
            (!empty($datadimissione) ? "DataDimissione = :datadimissione " : "") . "
            WHERE DATE(DataInizio) = DATE(:dataoraesame)
        ";

        // Prepara e esegue la query
        $stmt = $conn->prepare($updateQuery);

        // Esegui la query con i parametri corretti
        $params = [
            ':dataoraesame' => $dataoraesame,
        ];

        if (!empty($letto)) {
            $params[':letto'] = $letto;
        }
        if (!empty($datadimissione)) {
            $params[':datadimissione'] = $datadimissione;
        }

        $stmt->execute($params);

        // Debugging: stampa i valori delle variabili
        echo 'Letto: ' . $letto . '<br>';
        echo 'Data Dimissione: ' . $datadimissione . '<br>';
        echo 'Data Ora Esame: ' . $dataoraesame . '<br>';

        echo "Informazioni aggiornate con successo.";

        // Reindirizzamento per ripulire il form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento
    } catch (PDOException $e) {
        $error_message = 'Errore durante l\'aggiornamento delle informazioni: ' . $e->getMessage();
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <title>Il tuo Esame</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <script>
        console.log("Data Ora Esame: <?php echo $_SESSION['dataoraesame']; ?>");
        console.log("Data Prenotazione: <?php echo $_SESSION['dataprenotazione']; ?>");
    </script>
</head>

<body>
    <main>
        <section>
            <div class="container title" style="margin-top:2%">
                <!-- Flex container per il titolo e il pulsante di ritorno -->
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="title">Dettagli Ricovero</h1>
                    <div class="return-button">
                        <a href="gestione_prenotazioni.php" class="btn btn-secondary return-button">Torna alla pagina
                            delle Prenotazioni</a>
                    </div>
                </div>
            </div>

            <div class="container" style="margin-top:2%">
                <div class="row row_space" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body d-flex align-items-start" style="padding:0">
                                <table class="table table-bordered" style="margin-bottom:0">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th scope="col">Urgenza</th>
                                            <th scope="col">Data e Ora Esame</th>
                                            <th scope="col">Data Dimissione</th>
                                            <th scope="col">Letto</th>
                                            <th scope="col">Numero Stanza </th>
                                            <th scope="col">Reparto</th>
                                            <th scope="col">Data Prenotazione</th>
                                            <th scope="col">Ambulatorio Esterno</th>
                                            <th scope="col">Codice Esame</th>
                                            <th scope="col">Patologia</th>
                                            <th scope="col">Prescrizione</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ricoveri as $index => $ricovero): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($ricovero['urgenza'], ENT_QUOTES, 'UTF-8'); ?>
                                                <td><?php echo htmlspecialchars($ricovero['dataoraesame'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['datadimissione'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['letto'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['numerostanza'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['reparto'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>

                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['dataprenotazione'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['ambulatorioesterno'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['esame'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($ricovero['patologia'], ENT_QUOTES, 'UTF-8'); ?>
                                                <td><?php echo htmlspecialchars($ricovero['prescrizione'], ENT_QUOTES, 'UTF-8'); ?>
                                                </td>


                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

                <div id="modificaSection">
                    <div class="row" style="margin-top:1%; margin-bottom:3%">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">


                                        <div class="mb-3">
                                            <label for="letto" class="form-label">Numero Letto</label>
                                            <input type="text" class="form-control" id="letto" name="letto"
                                                value="<?php echo isset($_POST['letto']) ? htmlspecialchars($_POST['letto']) : ''; ?>">
                                        </div>

                                        <div class="mb-3">
                                            <label for="datadimissione" class="form-label">Data Dimissione</label>
                                            <input type="date" class="form-control" id="datadimissione"
                                                name="datadimissione"
                                                value="<?php echo isset($_POST['datadimissione']) ? htmlspecialchars($_POST['datadimissione']) : ''; ?>">
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


        </section>
    </main>
</body>

</html>