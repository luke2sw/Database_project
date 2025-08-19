<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

if (isset($_COOKIE['username']) && isset($_COOKIE['codice_utente']) && isset($_COOKIE['ruolo'])) {
    $username = $_COOKIE['username'];
    $codice_utente = $_COOKIE['codice_utente'];
    $ruolo = $_COOKIE['ruolo'];

    if ($ruolo !== 'Paziente') {
        header('Location: login.php');
        exit;
    }

    $paziente = [];
    $prenotazioni = [];
    $prescrizioni = [];
    $ricoveri = [];

    try {
        // Query per ottenere le informazioni del paziente
        $queryPaziente = "SELECT * FROM PAZIENTE WHERE NumTesseraSanitaria = :username";
        $stmt = $conn->prepare($queryPaziente);
        $stmt->execute([':username' => $username]);
        $paziente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paziente) {
            // Query per ottenere le prenotazioni del paziente
            $queryPrenotazioni = "SELECT * FROM Prenotazione WHERE Paziente = :username";
            $stmtPrenotazioni = $conn->prepare($queryPrenotazioni);
            $stmtPrenotazioni->execute([':username' => $username]);
            $prenotazioni = $stmtPrenotazioni->fetchAll(PDO::FETCH_ASSOC); // Fetch all prenotazioni

            // Query per ottenere le prescrizioni del paziente
            $queryPrescrizioni = "SELECT * FROM Prescrizione WHERE Paziente = :username";
            $stmtPrescrizioni = $conn->prepare($queryPrescrizioni);
            $stmtPrescrizioni->execute([':username' => $username]);
            $prescrizioni = $stmtPrescrizioni->fetchAll(PDO::FETCH_ASSOC); // Fetch all prenotazioni

            // Query per ottenere i ricoveri del paziente
            $queryRicoveri = "SELECT * FROM Ricovero WHERE Paziente = :username";
            $stmtRicoveri = $conn->prepare($queryRicoveri);
            $stmtRicoveri->execute([':username' => $username]);
            $ricoveri = $stmtRicoveri->fetchAll(PDO::FETCH_ASSOC); // Fetch all prenotazioni

            // Query per ottenere le patologie del paziente
            $queryPatologie = "SELECT * FROM PAZIENTEHAPATOLOGIA WHERE NumTesseraSanitaria = :username";
            $stmtPatologie = $conn->prepare($queryPatologie);
            $stmtPatologie->execute([':username' => $username]);
            $patologie = $stmtPatologie->fetchAll(PDO::FETCH_ASSOC); // Fetch all prenotazioni
        } else {
            echo 'Informazioni paziente non trovate';
        }

    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }
} else {
    header('Location: login.php');
    exit;
}


function checkPatologiaEsistente($conn, $patologia)
{
    // Query per controllare l'esistenza di una patologia con quel nome
    $query = "SELECT COUNT(*) FROM PATOLOGIA WHERE nome = :patologia";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':patologia' => $patologia]);

        // Ottieni il conteggio della patologia con quel nome
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno una patologia con quel nome, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo del paziente: ' . $e->getMessage());
    }
}



//aggiungi patologia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungi_patologia'])) {
    $patologia = $_POST['patologia'];

    if (!checkPatologiaEsistente($conn, $patologia)) {
        try {
            $queryInsertPatologia = "INSERT INTO PATOLOGIA (nome) VALUES (:patologia)";
            $stmtInsertPatologia = $conn->prepare($queryInsertPatologia);
            $stmtInsertPatologia->execute([':patologia' => $patologia]);

            $queryInsertPatologiaPaziente =
                "INSERT INTO PAZIENTEHAPATOLOGIA (numtesserasanitaria, patologia) 
             VALUES (:numtesserasanitaria, :patologia)";
            $stmtInsertPatologiaPaziente = $conn->prepare($queryInsertPatologiaPaziente);
            $stmtInsertPatologiaPaziente->execute([
                ':numtesserasanitaria' => $paziente['numtesserasanitaria'],
                ':patologia' => $patologia
            ]);

        } catch (PDOException $e) {

            die('Errore durante l\'inserimento del paziente: ' . $e->getMessage());
        }

    } else {
        try {
            $queryInsertPatologiaPaziente =
                "INSERT INTO PAZIENTEHAPATOLOGIA (numtesserasanitaria, patologia) 
             VALUES (:numtesserasanitaria, :patologia)";
            $stmtInsertPatologiaPaziente = $conn->prepare($queryInsertPatologiaPaziente);
            $stmtInsertPatologiaPaziente->execute([
                ':numtesserasanitaria' => $paziente['numtesserasanitaria'],
                ':patologia' => $patologia
            ]);

        } catch (PDOException $e) {
            echo $patologia;
            die('Errore durante l\'inserimento del paziente: ' . $e->getMessage());
        }

    }
    // Reindirizzamento per ripulire il form
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento
}

//rimuovi patologia
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi_patologia'])) {
    $patologia = $_POST['patologia_codice'];

    if (empty($patologia)) {
        die('Errore: Codice patologia non fornito.');
    }

    try {
        // Inizia una transazione
        $conn->beginTransaction();

        $queryFindPatologia = "SELECT COUNT(*) FROM PAZIENTEHAPATOLOGIA WHERE patologia = :patologia";
        $stmtFindPatologia = $conn->prepare($queryFindPatologia);
        $stmtFindPatologia->execute([':patologia' => $patologia]);
        $countPatologia = $stmtFindPatologia->fetchColumn();




        if ($countPatologia == 1) {
            // Rimuovi la patologia dal paziente
            $queryDeletePatologiaPaziente = "DELETE FROM PAZIENTEHAPATOLOGIA WHERE numtesserasanitaria = :username and patologia = :patologia";
            $stmtDeletePazientePaziente = $conn->prepare($queryDeletePatologiaPaziente);
            $stmtDeletePazientePaziente->execute([':username' => $username, ':patologia' => $patologia]);

            //rimuovi la patologia
            $queryDeletePatologia = "DELETE FROM PATOLOGIA WHERE nome = :patologia";
            $stmtDeletePatologia = $conn->prepare($queryDeletePatologia);
            $stmtDeletePatologia->execute([':patologia' => $patologia]);


        } else {
            // Rimuovi la patologia dal paziente
            $queryDeletePatologiaPaziente = "DELETE FROM PAZIENTEHAPATOLOGIA WHERE numtesserasanitaria = :username and patologia = :patologia";
            $stmtDeletePazientePaziente = $conn->prepare($queryDeletePatologiaPaziente);
            $stmtDeletePazientePaziente->execute([':username' => $username, ':patologia' => $patologia]);
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
<html lang="en">

<head>
    <title>Il tuo Profilo</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
</head>

<body>
    <main>
        <div class="container title" style="margin-top:2%">
            <!-- Flex container per il titolo e il pulsante di ritorno -->
            <div class="d-flex justify-content-between align-items-center">
                <h1>Il tuo Profilo:
                    <?php echo htmlspecialchars($paziente['nome']); ?>
                    <?php echo htmlspecialchars($paziente['cognome']); ?>
                </h1>
                <div class="return-button">
                    <a href="login.php" class="btn btn-secondary">Torna alla pagina di Login</a>
                </div>
            </div>


        </div>

        <section>
            <div class="container" style="margin-top:2%">
                <!--Elenco Prenotazioni-->
                <h2 class="card-title">Prenotazioni</h2>
                <div class="row row_space" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body d-flex align-items-start" style="padding:0">
                                <?php if (!empty($prenotazioni)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Esame</th>
                                                <th scope="col">Data e Ora Esame</th>
                                                <th scope="col">Data Prenotazione</th>
                                                <th scope="col">Numero Stanza Interno</th>
                                                <th scope="col">Reparto Interno</th>
                                                <th scope="col">Ambulatorio Esterno</th>
                                                <th scope="col">Dettagli Esame</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prenotazioni as $index => $prenotazione): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($prenotazione['esame']); ?></td>
                                                    <td><?php echo htmlspecialchars($prenotazione['dataoraesame']); ?></td>
                                                    <td><?php echo htmlspecialchars($prenotazione['dataprenotazione']); ?></td>
                                                    <td><?php echo htmlspecialchars($prenotazione['numerostanza']); ?></td>
                                                    <td><?php echo htmlspecialchars($prenotazione['reparto']); ?></td>
                                                    <td><?php echo htmlspecialchars($prenotazione['ambulatorioesterno']); ?>
                                                    </td>
                                                    <td><a
                                                            href="esame.php?codice=<?php echo urlencode($prenotazione['esame']); ?>">Dettagli</a>
                                                    </td>
                                                </tr>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Non hai prenotazioni al momento.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Elenco Prescrizioni-->
                <h2 class="card-title">Prescrizioni</h2>
                <div class="row row_space" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body d-flex align-items-start" style="padding:0">
                                <?php if (!empty($prescrizioni)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Codice</th>
                                                <th scope="col">Medico Interno</th>
                                                <th scope="col">Medico Esterno</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($prescrizioni as $index => $prescrizione): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($prescrizione['codice']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescrizione['medicointerno']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescrizione['medicoesterno']); ?></td>
                                                </tr>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Non hai prescrizioni al momento.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Elenco Ricoveri-->
                <h2 class="card-title">Ricoveri</h2>
                <div class="row row_space" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body d-flex align-items-start" style="padding:0">
                                <?php if (!empty($ricoveri)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Data di Inizio</th>
                                                <th scope="col">Data di Dimissione</th>
                                                <th scope="col">Reparto</th>
                                                <th scope="col">Numero Stanza</th>
                                                <th scope="col">Letto</th>

                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($ricoveri as $index => $ricovero): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($ricovero['datainizio']); ?></td>
                                                    <td><?php echo htmlspecialchars($ricovero['datadimissione']); ?></td>
                                                    <td><?php echo htmlspecialchars($ricovero['reparto']); ?></td>
                                                    <td><?php echo htmlspecialchars($ricovero['numerostanza']); ?></td>
                                                    <td><?php echo htmlspecialchars($ricovero['letto']); ?></td>
                                                </tr>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Non hai ricoveri al momento.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <!--Elenco Patologie-->
                <h2 class="card-title">Patologie</h2>
                <div class="row row_space" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body d-flex align-items-start" style="padding:0">
                                <?php if (!empty($patologie)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th scope="col" style="width: 100%">Patologia</th>
                                                <th scope="col">Rimuovi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($patologie as $index => $patologia): ?>
                                                <tr>
                                                    <td style="width: 100%">
                                                        <?php echo htmlspecialchars($patologia['patologia']); ?>
                                                    </td>
                                                    <td style="float:right">
                                                        <form method="POST"
                                                            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <input type="hidden" name="patologia_codice"
                                                                value="<?php echo htmlspecialchars($patologia['patologia']); ?>">
                                                            <button type="submit" name="rimuovi_patologia"
                                                                class="btn btn-danger btn-sm">Rimuovi</button>
                                                        </form>
                                                    </td>
                                                </tr>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Non hai patologie.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Nuova Patologia-->
                <h2 class="card-title">Nuova Patologia</h2>
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <!--Nome Patologia-->
                                    <div class="mb-3">
                                        <label for="patologia" class="form-label">Nome Patologia</label>
                                        <input type="text" class="form-control" id="patologia" name="patologia"
                                            value="<?php echo isset($_POST['patologia']) ? htmlspecialchars($_POST['patologia']) : ''; ?>">

                                    </div>
                                    <button type="submit" name="aggiungi_patologia" class="btn btn-success"
                                        style="float:right">Aggiungi</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>
    </main>
</body>

</html>