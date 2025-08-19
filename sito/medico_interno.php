<?php
include 'connection_db.php';

$error_message = ''; // Initialize error message variable

// Database connection
$conn = getConnectionDB();

if (isset($_COOKIE['username']) && isset($_COOKIE['codice_utente']) && isset($_COOKIE['ruolo'])) {
    $username = $_COOKIE['username'];
    $codice_utente = $_COOKIE['codice_utente'];
    $ruolo = $_COOKIE['ruolo'];

    if ($ruolo !== 'MedicoInterno') {
        header('Location: login.php');
        exit;
    }

    $esami = [];
    $NumTesseraSanitaria = ''; // Initialize variable for storing the user input

    try {
        // Query to get nurse information
        $queryMedicoInterno = "SELECT * FROM PERSONALE WHERE CodiceFiscale = :username";
        $stmt = $conn->prepare($queryMedicoInterno);
        $stmt->execute([':username' => $username]);
        $MedicoInterno = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($MedicoInterno) {
            // Query per ottenere i turni di Reparto
            $queryOrarioReparto = "
            SELECT 
                RPO.Apertura AS OrarioInizio, 
                RPO.Chiusura AS OrarioFine, 
                RPO.Giorno AS Giorno_Data,
                'Reparto' AS TipoTurno
            FROM 
                PERSONALE P
            JOIN 
                repartopossiedeorario RPO ON P.Reparto = RPO.reparto
            WHERE 
                P.CodiceFiscale = :username";
            $stmtOrarioReparto = $conn->prepare($queryOrarioReparto);
            $stmtOrarioReparto->execute([':username' => $username]);
            $OrarioReparto = $stmtOrarioReparto->fetchAll(PDO::FETCH_ASSOC);

            // Query per ottenere i turni di Pronto Soccorso
            $queryTurnoProntoSoccorso = "
            SELECT 
                OrarioInizio, OrarioFine, Giorno AS Giorno_Data, 'Pronto Soccorso' AS TipoTurno
            FROM 
                TURNOPRONTOSOCCORSO
            WHERE 
                Personale = :username";
            $stmtTurnoProntoSoccorso = $conn->prepare($queryTurnoProntoSoccorso);
            $stmtTurnoProntoSoccorso->execute([':username' => $username]);
            $TurnoProntoSoccorso = $stmtTurnoProntoSoccorso->fetchAll(PDO::FETCH_ASSOC);

            // Query to get Prescrizioni
            $queryPrescrizioni = "
            SELECT 
                p.Codice AS codice,
                e.Descrizione AS descresame,
                paz.Nome AS nome,
                paz.Cognome AS cognome,
                p.Paziente AS paziente
            FROM Prescrizione p
            LEFT JOIN Prenotazione pre ON p.Codice = pre.Prescrizione
            LEFT JOIN Esame e ON pre.Esame = e.Codice
            LEFT JOIN Paziente paz ON p.Paziente = paz.NumTesseraSanitaria
            WHERE p.MedicoInterno = :codice_utente
            ";

            $stmtPrescrizioni = $conn->prepare($queryPrescrizioni);
            $stmtPrescrizioni->execute([':codice_utente' => $codice_utente]);
            $Prescrizioni = $stmtPrescrizioni->fetchAll(PDO::FETCH_ASSOC);

            // Query per ottenere l'elenco degli esami
            $queryEsami = "SELECT codice, descrizione FROM Esame ORDER BY descrizione";
            $stmtEsami = $conn->query($queryEsami);
            $esami = $stmtEsami->fetchAll(PDO::FETCH_ASSOC);

 
        } else {
            echo 'Informazioni non trovate';
        }

    } catch (PDOException $e) {
        die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
    }

} else {
    header('Location: login.php');
    exit;
}


function checkPazienteEsistente($conn, $numTesseraSanitaria)
{
    // Query per controllare l'esistenza del paziente
    $query = "SELECT COUNT(*) FROM Paziente WHERE NumTesseraSanitaria = :numTesseraSanitaria";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':numTesseraSanitaria' => $numTesseraSanitaria]);

        // Ottieni il conteggio dei pazienti con quel numero tessera
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno un paziente con quel numero tessera, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo del paziente: ' . $e->getMessage());
    }
}

//prescrivi (aggiungi prescrizione)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['prescrivi'])) {
    $NumTesseraSanitaria = $_POST['NumTesseraSanitaria'];
    $esame = $_POST['esame'];

    $medico_interno = $codice_utente;
    $medico_esterno = $null;

    if (!checkPazienteEsistente($conn, $NumTesseraSanitaria)) {
        echo 'Paziente non trovato. Inserimento del nuovo paziente...';
        // Dati paziente
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $cognome = isset($_POST['cognome']) ? trim($_POST['cognome']) : null;
        $telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : null;
        $indirizzo = isset($_POST['indirizzo']) ? trim($_POST['indirizzo']) : null;
        $dataNascita = isset($_POST['dataNascita']) ? trim($_POST['dataNascita']) : null;

        // Controllo della data di nascita
        if (!empty($dataNascita)) {
            $dateFormat = 'Y-m-d';
            $d = DateTime::createFromFormat($dateFormat, $dataNascita);
            if ($d && $d->format($dateFormat) === $dataNascita) {
                // Data valida -> $dataNascita = $dataNascita;
            } else {
                die('Errore: Formato della data non valido.');
            }
        } else {
            $dataNascita = null; // Imposta a null se vuoto
        }

        try {
            // Inserisci il nuovo paziente
            $queryInsertPaziente = "
                INSERT INTO Paziente (NumTesseraSanitaria, Indirizzo, Telefono, Nome, Cognome, DataNascita) 
                VALUES (:NumTesseraSanitaria, :Indirizzo, :telefono, :NomePaziente, :CognomePaziente, :dataNascita)
            ";
            $stmtInsertPaziente = $conn->prepare($queryInsertPaziente);
            $stmtInsertPaziente->execute([
                ':NumTesseraSanitaria' => $NumTesseraSanitaria,
                ':Indirizzo' => $indirizzo,
                ':telefono' => $telefono,
                ':NomePaziente' => $nome,
                ':CognomePaziente' => $cognome,
                ':dataNascita' => $dataNascita
            ]);
        } catch (PDOException $e) {
            die('Errore durante l\'inserimento del paziente: ' . $e->getMessage());
        }
    } else {
        // Il paziente esiste già
        echo 'Paziente già presente.';
    }

    try {
        // Genera un codice di prescrizione univoco di 20 caratteri
        $codice_prescrizione = generatePrescriptionCode();

        // Inserimento della nuova prescrizione nel database
        $query = "
            INSERT INTO Prescrizione (Codice, Paziente, MedicoInterno, MedicoEsterno)
            VALUES (:codice, :paziente, :medico_interno, :medico_esterno)
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':codice' => $codice_prescrizione,
            ':paziente' => $NumTesseraSanitaria,
            ':medico_esterno' => $medico_esterno,
            ':medico_interno' => $medico_interno
        ]);

        // Dopo l'inserimento, reindirizza alla pagina corrente per evitare di reinserire dati in caso di refresh
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        die('Errore durante l\'inserimento della prescrizione: ' . $e->getMessage());
    }
}

// Funzione per generare un codice di prescrizione di 20 caratteri
function generatePrescriptionCode()
{
    $prefix = 'PRESC';
    $random_part = bin2hex(random_bytes(8)); // Genera 16 caratteri casuali
    $code = $prefix . $random_part; // Lunghezza totale 20 caratteri
    return substr($code, 0, 20); // Assicura che il codice sia esattamente di 20
}

//rimuovi prescrizione
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rimuovi_prescrizione'])) {
    $prescrizioneCodice = $_POST['prescrizione_codice'];

    if (empty($prescrizioneCodice)) {
        die('Errore: Codice prescrizione non fornito.');
    }

    try {
        // Inizia una transazione
        $conn->beginTransaction();

        // Controlla se la prescrizione è associata ad una prenotazione
        $queryFindPrescrzioneInPrenotazione = "SELECT COUNT(*) FROM PRENOTAZIONE WHERE Prescrizione = :prescrizioneCodice";
        $stmtFindPrescrzioneInPrenotazione = $conn->prepare($queryFindPrescrzioneInPrenotazione);
        $stmtFindPrescrzioneInPrenotazione->execute([':prescrizioneCodice' => $prescrizioneCodice]);
        $countPrescrizioneInPrenotazione = $stmtFindPrescrzioneInPrenotazione->fetchColumn();
        if ($countPrescrizioneInPrenotazione == 0) {
            // Rimuovi la prescrizione
            $queryDeletePrescrizione = "DELETE FROM PRESCRIZIONE WHERE Codice = :prescrizioneCodice";
            $stmtDeletePrescrizione = $conn->prepare($queryDeletePrescrizione);
            $stmtDeletePrescrizione->execute([':prescrizioneCodice' => $prescrizioneCodice]);
        } else {
            
            die('Errore: La prescrizione è associata ad una prenotazione. Rimuovi prima la prenotazione.');
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
    <title>Il tuo Profilo</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <main style="margin-bottom:5%">
        <div class="container title" style="margin-top:2%">

            <!-- Flex container per il titolo e il pulsante di ritorno -->
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="title">Il tuo Profilo</h1>
                <div class="return-button">
                    <a href="login.php" class="btn btn-secondary">Torna alla pagina di Login</a>
                </div>
            </div>
        </div>

        <section>
            <!-- Turni nel reparto -->
            <div class="container" style="margin-top:2%">
                <h2 class="card-title">I tuoi turni nel reparto
                </h2>

                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($OrarioReparto)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Giorno</th>
                                                <th scope="col">Orario Inizio</th>
                                                <th scope="col">Orario Fine</th>
                                                
                                            </tr>
                                        </thead>
                                        <tbody>

                                            <?php foreach ($OrarioReparto as $index => $ora): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($ora['giorno_data']); ?></td>
                                                    <td><?php echo htmlspecialchars($ora['orarioinizio']); ?></td>
                                                    <td><?php echo htmlspecialchars($ora['orariofine']); ?></td>


                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Non hai turni in reparto al momento.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Turni al pronto soccorso -->
                <h2 class="card-title">I tuoi turni al pronto soccorso</h2>
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($TurnoProntoSoccorso)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Giorno</th>
                                                <th scope="col">Orario Inizio</th>
                                                <th scope="col">Orario Fine</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($TurnoProntoSoccorso as $index => $turno): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($turno['giorno_data']); ?></td>
                                                    <td><?php echo htmlspecialchars($turno['orarioinizio']); ?></td>
                                                    <td><?php echo htmlspecialchars($turno['orariofine']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Non hai turni al pronto soccorso al momento.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Prescrizioni Effettuate-->
                <h2 class="card-title">Prescrizioni effettuate</h2>
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($Prescrizioni)): ?>
                                    <table class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Cognome</th>
                                                <th scope="col">Nome</th>
                                                <th scope="col">Numero Tessera Sanitaria Paziente</th>
                                                <th scope="col">Codice prescrizione</th>
                                                <th scope="col">Esame Prenotato</th>
                                                <th scope="col">Rimuovi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($Prescrizioni as $index => $prescrizione): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($prescrizione['cognome']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescrizione['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescrizione['paziente']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescrizione['codice']); ?></td>
                                                    <td><?php echo htmlspecialchars($prescrizione['descresame']); ?></td>
                                                    <td>
                                                        <form method="POST"
                                                            action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                                            <input type="hidden" name="prescrizione_codice"
                                                                value="<?php echo htmlspecialchars($prescrizione['codice']); ?>">
                                                            <button type="submit" name="rimuovi_prescrizione"
                                                                class="btn btn-danger btn-sm">Rimuovi</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Nessuna prescrizione trovata.
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Nuova Prescrizione -->
                <h2 class="card-title">Nuova Prescrizione</h2>
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                    <div class="mb-3">
                                        <label for="NumTesseraSanitaria" class="form-label">Numero Tessera
                                            Sanitaria</label>
                                        <input type="text" class="form-control" id="NumTesseraSanitaria"
                                            name="NumTesseraSanitaria"
                                            value="<?php echo htmlspecialchars(isset($_POST['NumTesseraSanitaria']) ? $_POST['NumTesseraSanitaria'] : $NumTesseraSanitaria); ?>"
                                            required>
                                    </div>
                                    <!-- Bottone per verificare se il paziente è già salvato -->
                                    <button type="submit" name="check_paziente" class="btn btn-primary"
                                        style="margin-bottom:2%">Verifica se il paziente è già salvato</button>

                                    <?php if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_paziente'])): ?>
                                        <?php
                                        // Assicurati che il valore di numTesseraSanitaria sia definito
                                        $numTesseraSanitaria = isset($_POST['NumTesseraSanitaria']) ? $_POST['NumTesseraSanitaria'] : '';

                                        if (!checkPazienteEsistente($conn, $numTesseraSanitaria)): ?>
                                            <!-- Campi aggiuntivi mostrati solo se il paziente non esiste -->
                                            <div id="extra-fields">
                                                <div class="mb-3">
                                                    <label for="nome" class="form-label">Nome Paziente</label>
                                                    <input type="text" class="form-control" id="nome" name="nome"
                                                        value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                                        required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="cognome" class="form-label">Cognome Paziente</label>
                                                    <input type="text" class="form-control" id="cognome" name="cognome"
                                                        value="<?php echo isset($_POST['cognome']) ? htmlspecialchars($_POST['cognome']) : ''; ?>"
                                                        required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="telefono" class="form-label">Telefono</label>
                                                    <input type="text" class="form-control" id="telefono" name="telefono"
                                                        value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                                                        required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="indirizzo" class="form-label">Indirizzo</label>
                                                    <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                                        value="<?php echo isset($_POST['indirizzo']) ? htmlspecialchars($_POST['indirizzo']) : ''; ?>"
                                                        required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="dataNascita" class="form-label">Data di Nascita</label>
                                                    <input type="date" class="form-control" id="dataNascita" name="dataNascita"
                                                        value="<?php echo isset($_POST['dataNascita']) ? htmlspecialchars($_POST['dataNascita']) : ''; ?>"
                                                        required>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- Se il paziente esiste -->
                                            <p class="text-success">Paziente trovato!</p>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <div class="mb-3">
                                        <label for="esame" class="form-label">Tipo Esame</label>
                                        <select class="form-select" id="esame" name="esame" required>
                                            <?php foreach ($esami as $esame): ?>
                                                <option value="<?php echo htmlspecialchars($esame['codice']); ?>">
                                                    <?php echo htmlspecialchars($esame['descrizione']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <button type="submit" name="prescrivi" class="btn btn-success">Prescrivi</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>

</html>