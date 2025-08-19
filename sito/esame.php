<?php
include 'connection_db.php';// Include il file di connessione

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Verifica se il codice dell'esame è presente nella query string
if (isset($_GET['codice'])) {
    //Un array associativo di variabili passate allo script corrente tramite i parametri dell'URL.
    $codiceEsame = $_GET['codice'];


    try {
        $queryEsame = "
        SELECT
            e.Codice AS codice_esame,
            e.Descrizione AS descrizione_esame,
            e.CostoPubblico AS costopubblico,
            e.CostoPrivato AS costoprivato,
            s.Nome AS specializzazione,
            pr.Prescrizione AS codice_prescrizione,
            a.Descrizione AS avvertenza
        FROM Esame e
        LEFT JOIN PRENOTAZIONE pr ON e.Codice = pr.Esame
        LEFT JOIN Specializzazione s ON e.Specializzazione = s.Nome
        LEFT JOIN Prescrizione p ON e.Codice = p.Codice
        LEFT JOIN esamehaavvertenza ae ON e.Codice = ae.Esame
        LEFT JOIN Avvertenze a ON ae.Avvertenza = a.Descrizione
        WHERE e.Codice = :codiceEsame
    ";

        $stmt = $conn->prepare($queryEsame);
        $stmt->execute([':codiceEsame' => $codiceEsame]);
        $esame = $stmt->fetchAll(PDO::FETCH_ASSOC); // Usa fetchAll per ottenere tutte le righe

        if ($esame) {
            // Dati trovati, li elaboriamo
            $firstRow = $esame[0];
        } else {
            echo 'Informazioni riguardo all\'esame non trovate';
            exit;
        }

    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }
} else {
    echo "Codice esame non specificato.";
    exit;
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
</head>

<body>
    <main>
        <section>
            <div class="container title" style="margin-top:2%">
                <!-- Flex container per il titolo e il pulsante di ritorno -->
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="title">Dettagli Esame</h1>
                    <div class="return-button">
                        <a href="paziente.php" class="btn btn-secondary return-button">Torna alla pagina del Profilo</a>
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
                                            <th scope="col">Esame</th>
                                            <th scope="col">Descrizione Esame</th>
                                            <th scope="col">Costo Pubblico</th>
                                            <th scope="col">Costo Privato</th>
                                            <th scope="col">Specializzazione</th>
                                            <th scope="col">Codice Prescrizione</th>
                                            <th scope="col">Avvertenze</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><?php echo htmlspecialchars($firstRow['codice_esame'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstRow['descrizione_esame'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstRow['costopubblico'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstRow['costoprivato'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstRow['specializzazione'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstRow['codice_prescrizione'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($firstRow['avvertenza'], ENT_QUOTES, 'UTF-8'); ?>
                                            </td>

                                        </tr>

                                    </tbody>
                                </table>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </main>
</body>

</html>