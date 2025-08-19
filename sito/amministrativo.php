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

} else {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Amministrazione</title>
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
            <div class="d-flex justify-content-between align-items-center">
                <h1>Scegli quali informazioni visualizzare:   
                </h1>
                <div class="return-button">
                    <a href="login.php" class="btn btn-secondary">Torna alla pagina di Login</a>
                </div>
            </div>


        </div>

        <section class="container" style="margin-top:2%">
                <!--Card Prenotazioni-->
            <div class="card" style="width: 100%; margin-top:2%">
                <div class="card-body">
                    <h5 class="card-title">Gestione Prenotazioni</h5>
                    <p class="card-text">Gestisci le prenotazioni dei pazienti.</p>
                    <a href="gestione_prenotazioni.php" class="btn btn-primary" style="width: 10%; float: right">Gestisci</a>
                </div>
            </div>
                <!--Card Personale-->
            <div class="card" style="width: 100%; margin-top:2%">
                <div class="card-body">
                    <h5 class="card-title">Gestione Personale</h5>
                    <p class="card-text">Visualizza e gestisci l'elenco del personale.</p>
                    <a href="gestione_personale.php" class="btn btn-primary" style="width: 10%; float: right">Gestisci</a>
                </div>
            </div>
                <!--Card Pazienti-->
            <div class="card" style="width: 100%; margin-top:2%">
                <div class="card-body">
                    <h5 class="card-title">Gestione Pazienti</h5>
                    <p class="card-text">Visualizza e gestisci l'elenco del personale.</p>
                    <a href="gestione_pazienti.php" class="btn btn-primary" style="width: 10%; float: right">Gestisci</a>
                </div>
            </div>
        </section>

    </main>
</body>

</html>