<?php
include 'connection_db.php';

$error_message = ''; // Initialize error message variable

// Database connection
$conn = getConnectionDB();

if (isset($_COOKIE['username']) && isset($_COOKIE['codice_utente']) && isset($_COOKIE['ruolo'])) {
    $username = $_COOKIE['username'];
    $codice_utente = $_COOKIE['codice_utente'];
    $ruolo = $_COOKIE['ruolo'];

    if ($ruolo !== 'Infermiere') {
        header('Location: login.php');
        exit;
    }

    try {
        // Query per ottenere informazioni sull'infermiere
        $queryInfermiere = "SELECT * FROM PERSONALE WHERE CodiceFiscale = :username";
        $stmt = $conn->prepare($queryInfermiere);
        $stmt->execute([':username' => $username]);
        $infermiere = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($infermiere) {
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
                orarioinizio, orariofine, Giorno AS Giorno_Data, 'Pronto Soccorso' AS TipoTurno
            FROM 
                TurnoProntoSoccorso 
            WHERE 
                Personale = :username";
            $stmtTurnoProntoSoccorso = $conn->prepare($queryTurnoProntoSoccorso);
            $stmtTurnoProntoSoccorso->execute([':username' => $username]);
            $TurnoProntoSoccorso = $stmtTurnoProntoSoccorso->fetchAll(PDO::FETCH_ASSOC);

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
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <title>Il tuo Profilo</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        .container {
                padding-right: 0;
                padding-left: 0;
            }
    </style>
</head>

<body>
    <main>
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
            <div class="container" style="margin-top:2%">
                <h2 class="card-title">I tuoi turni nel reparto</h2>

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
                                                    <td><?php echo htmlspecialchars($turno['orarioinizio']); ?></td>
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
            </div>

        </section>
    </main>
</body>

</html>