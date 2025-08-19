<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore
try {
    // Connessione al database
    $conn = getConnectionDB();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["Username"]) && isset($_POST["Password"]) && isset($_POST["Ruolo"])) {
            $username = $_POST["Username"];
            $password = $_POST["Password"];
            $ruolo = $_POST["Ruolo"];

            // Esegue l'escape dei parametri per prevenire SQL injection
            // Trasforma i caratteri speciali in una forma che non può essere interpretata come codice SQL. 
            // In questo modo, anche se un attaccante inserisce caratteri speciali, verranno trattati come dati letterali e non come parte della query.
            $query = "SELECT * FROM utente WHERE username = :username AND password = :password AND ruolo = :ruolo";
            // Preparazione ed esecuzione della query
            $stmt = $conn->prepare($query);
            $stmt->execute([':username' => $username, ':password' => $password, ':ruolo' => $ruolo]);

            // Ottieni i risultati della query
            $utente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utente) {
                switch ($utente['ruolo']) {
                    case 'Medico Interno':
                        setcookie('username', $username, time() + 3600, '/');
                        setcookie('codice_utente', $utente['username'], time() + 3600, '/');
                        setcookie('ruolo', 'MedicoInterno', time() + 3600, '/');
                        header('Location: medico_interno.php');
                        exit;
                    case 'Medico Esterno':
                        setcookie('username', $username, time() + 3600, '/');
                        setcookie('codice_utente', $utente['username'], time() + 3600, '/');
                        setcookie('ruolo', 'MedicoEsterno', time() + 3600, '/');
                        header('Location: medico_esterno.php');
                        exit;
                    case 'Infermiere':
                        setcookie('username', $username, time() + 3600, '/');
                        setcookie('codice_utente', $utente['username'], time() + 3600, '/');
                        setcookie('ruolo', 'Infermiere', time() + 3600, '/');
                        header('Location: infermiere.php');
                        exit;
                    case 'Amministrativo':
                        setcookie('username', $username, time() + 3600, '/');
                        setcookie('codice_utente', $utente['username'], time() + 3600, '/');
                        setcookie('ruolo', 'PersonaleAmministrativo', time() + 3600, '/');
                        header('Location: amministrativo.php');
                        exit;
                    case 'Paziente':
                        setcookie('username', $username, time() + 3600, '/');
                        setcookie('codice_utente', $utente['username'], time() + 3600, '/');
                        setcookie('ruolo', 'Paziente', time() + 3600, '/');
                        header('Location: paziente.php');
                        exit;
                    default:
                        $error_message = 'Il ruolo dell\'utente non corrisponde alle credenziali fornite.';
                }
            } else {
                $error_message = 'Credenziali non valide';
            }
        } else {
            $error_message = 'Si è verificato un problema con l\'invio dei dati.';
        }
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <style>
        .error-message {
            color: red;
            margin-top: 10px;
            text-align: center;
        }
    </style>
</head>

<body>
    <main style="margin-bottom: 15%">
        <section style="margin:5%; margin-right:25%; margin-left:25%">
            <h1 style="text-align:center">Login</h1>
            <p class="login-subtitle" style="text-align: center; color: rgb(156, 156, 156); margin-top:1.5%">
                Effettua il login per poter accedere ai tuoi dati
            </p>

            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="form-group Username" style="margin-bottom:2.5%">
                    <label for="InputUsername">Username</label>
                    <input type="text" class="form-control" id="InputUsername" name="Username"
                        aria-describedby="usernamelHelp">
                </div>
                <div class="form-group" style="margin-bottom:2.5%">
                    <label for="InputPassword">Password</label>
                    <input type="password" class="form-control" id="InputPassword" name="Password">
                </div>

                <div class="form-group ruolo">
                    <label for="InputRuolo">Seleziona il ruolo</label>
                    <select class="form-control" style="margin-bottom:2.5%" name="Ruolo" id="InputRuolo">
                        <option value="Medico Interno">Medico Interno</option>
                        <option value="Medico Esterno">Medico Esterno</option>
                        <option value="Infermiere">Infermiere</option>
                        <option value="Amministrativo">Personale Amministrativo</option>
                        <option value="Paziente">Paziente</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary" style="float: right">Login</button>
            </form>
        </section>

        <!-- Centered Button for "Accedi come DBA" -->
        <div style="text-align:center; margin-top:20px;">
            <a href="dba.php" class="btn btn-primary" style="width:15%">Accedi come DBA</a>
        </div>
    </main>
</body>

</html>
