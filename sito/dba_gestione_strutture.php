<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Inizializza la variabile $codice dell'ospedale con una stringa vuota
$codice = '';



try {
    // Query per ottenere le informazioni dell'ospedale, filtrata per codice se è stata eseguita una ricerca
    $search_codiceospedale = isset($_GET['codice']) ? $_GET['codice'] : '';
    // Se è presente un codice da cercare, esegui la query filtrata
    $queryDba_Gestione_Strutture_Ospedale = "SELECT * FROM OSPEDALE WHERE codice LIKE :search_codiceospedale";
    $stmt = $conn->prepare($queryDba_Gestione_Strutture_Ospedale);

    // Bind del parametro, se il parametro di ricerca è vuoto usa il carattere jolly '%' per ottenere tutti i reparti
    $stmt->bindValue(':search_codiceospedale', $search_codiceospedale === '' ? '%' : "%$search_codiceospedale%", PDO::PARAM_STR);

    // Esegui la dichiarazione
    $stmt->execute();

    // Ottieni tutti i risultati come array associativo
    $Ospedali = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Errore durante il recupero delle informazioni: ' . $e->getMessage());
}


// Recupera le opzioni esistenti per i campi
$telefoniEsistenti = [];
$nomiEsistenti = [];
$ospedaliEsistenti = [];
$direttoriEsistenti = [];


//OSPEDALE
function checkOspedaleEsistente($conn, $codice)
{
    // Query per controllare l'esistenza dell'ospedale con quel codice 
    $query = "SELECT COUNT(*) FROM OSPEDALE WHERE codice = :codice";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':codice' => $codice]);

        // Ottieni il conteggio dell'ospedale con quel codice 
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno un ospedale con quel codice, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo dell\'ospedale: ' . $e->getMessage());
    }
}

//aggiungi ospedale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungi'])) {

    $codice = $_POST['codice'];


    if (!checkOspedaleEsistente($conn, $codice)) {
        echo 'Reparto non presente';
        // Dati ospedale
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $indirizzo = isset($_POST['indirizzo']) ? trim($_POST['indirizzo']) : null;



        try {
            // Inserisci il nuovo ospedale
            $queryInsertOspedale = "
                INSERT INTO OSPEDALE (codice, nome, indirizzo) 
                VALUES (:codice, :nome, :indirizzo)
            ";
            $stmtInsertOspedale = $conn->prepare($queryInsertOspedale);
            $stmtInsertOspedale->execute([
                ':codice' => $codice,
                ':nome' => $nome,
                ':indirizzo' => $indirizzo,
            ]);

            // Reindirizzamento per ripulire il form
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

        } catch (PDOException $e) {
            die('Errore durante l\'inserimento dell\'ospedale: ' . $e->getMessage());
        }
    } else {
        // L'ospedale esiste già
        //echo 'Ospedale già presente.';
    }

}

//modifica ospedale
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modifica'])) {
    $codice = $_POST['codice'];



    // Recupera l'ospedale esistente
    $querySelectOspedale = "SELECT nome, indirizzo FROM OSPEDALE WHERE codice = :codice";
    $stmtSelectOspedale = $conn->prepare($querySelectOspedale);
    $stmtSelectOspedale->execute([':codice' => $codice]);
    $ospedale = $stmtSelectOspedale->fetch(PDO::FETCH_ASSOC);

    if ($ospedale === false) {
        die('Errore: ospedale non trovato.');
    }

    // Valori di data attuali
    $currentNome = $ospedale['nome'];
    $currentIndirizzo = $ospedale['indirizzo'];

    $codice = $_POST['codice'];

    // Ottieni i valori dei campi
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : $currentNome;
    $indirizzo = isset($_POST['indirizzo']) ? trim($_POST['indirizzo']) : $currentIndirizzo;

    // Controllo del nome
    if (empty($nome)) {
        $nome = $currentNome; // Mantieni il valore corrente se non fornito
    }

    // Controllo dell'indirizzo
    if (empty($indirizzo)) {
        $indirizzo = $currentIndirizzo; // Mantieni il valore corrente se non fornito
    }


    try {
        // Aggiorna l'ospedale esistente
        $queryUpdateOspedale = "
            UPDATE OSPEDALE 
            SET nome = :nome, 
                indirizzo = :indirizzo 
            WHERE codice = :codice
        ";
        $stmtUpdateOspedale = $conn->prepare($queryUpdateOspedale);
        $stmtUpdateOspedale->execute([
            ':codice' => $codice,
            ':nome' => $nome,
            ':indirizzo' => $indirizzo,
        ]);

        // Reindirizzamento per ripulire il form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento

    } catch (PDOException $e) {
        die('Errore durante l\'aggiornamento dell\'ospedale: ' . $e->getMessage());
    }
}




?>


<!DOCTYPE html>
<html lang="it">




<head>
    <title>Gestione Strutture</title>
    <?php if (!empty($error_message)): ?>
        <div class="error-message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!--SCRIPT OSPEDALE-->
    <script>
        // Funzione per comprimere/espandere la tabella
        function toggleTable() {
            var table = document.getElementById("OspedaleTable");
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

        // Funzione per comprimere/espandere la sezione Modifica Ospedale
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

        // Funzione per comprimere/espandere la sezione Aggiungi Ospeale
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
        .collapse {
            display: none;
        }

        .show {
            display: table-row;
        }

        .details-container {
            width: 100%;
        }

        .details-container table {
            width: 100%;
            margin: 0;
        }

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
                <h1 class="title">Strutture Ospedaliere</h1>
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
                        <h2 class="card-title" style="width: 20%">Elenco Ospedali</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la tabella -->
                            <button id="toggleButton" class="btn btn-primary" onclick="toggleTable()">Comprimi</button>
                        </div>
                    </div>
                </div>


                <!-- Modulo di ricerca -->
                <div class="container" style="margin-top:2%">
                    <form id="searchForm" method="get">
                        <div class="form-group input-group mb-3">
                            <input type="text" id="codice" name="codice" class="form-control"
                                placeholder="Cerca per Codice " />
                            <button type="submit" class="btn btn-primary" style="width:7.5%">Cerca</button>
                        </div>
                    </form>
                </div>

                <!-- Tabella per visualizzare i dati degli ospedali -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">

                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($Ospedali)): ?>
                                    <table id="OspedaleTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Codice</th>
                                                <th scope="col">Nome</th>
                                                <th scope="col">Indirizzo</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($Ospedali as $index => $Ospedale): ?>
                                                <tr>
                                                    <th scope="row"><?php echo $index + 1; ?></th>
                                                    <td><?php echo htmlspecialchars($Ospedale['codice']); ?></td>
                                                    <td><?php echo htmlspecialchars($Ospedale['nome']); ?></td>
                                                    <td><?php echo htmlspecialchars($Ospedale['indirizzo']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Nessun ospedale trovato.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!--Modifica Ospedale-->
                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Modifica Ospedale</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Modifica Ospedale -->
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
                                            <!--Codice (per identificare l'ospedale da modificare)-->
                                            <div class="mb-3">
                                                <label for="codice" class="form-label">Codice</label>
                                                <input type="text" class="form-control" id="codice" name="codice"
                                                    value="<?php echo isset($_POST['codice']) ? htmlspecialchars($_POST['codice']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Nome-->
                                            <div class="mb-3">
                                                <label for="nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="nome" name="nome"
                                                    value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                                            </div>
                                            <!--Indirizzo-->
                                            <div class="mb-3">
                                                <label for="indirizzo" class="form-label">Indirizzo</label>
                                                <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                                    value="<?php echo isset($_POST['indirizzo']) ? htmlspecialchars($_POST['indirizzo']) : ''; ?>">
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



                <!--Aggiungi Ospedale-->
                <!-- Flex container per il titolo e il pulsante di comprimi -->
                <div class="d-flex justify-content-between align-items-center" style="margin-top:2%">
                    <h2 class="card-title">Aggiungi Ospedale</h2>
                    <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                        <!-- Bottone per comprimere/espandere la sezione Aggiungi Ospedale -->
                        <button id="toggleAggiungi" class="btn btn-primary" onclick="toggleAggiungi()">Comprimi</button>
                    </div>
                </div>
                <div id="aggiungiSection">
                    <div class="row" style="margin-top:1%; margin-bottom:3%">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                        <!--Codice (per identificare l'ospedale da aggiungere)-->
                                        <div class="mb-3">
                                            <label for="codice" class="form-label">Codice</label>
                                            <input type="text" class="form-control" id="codice" name="codice"
                                                value="<?php echo isset($_POST['codice']) ? htmlspecialchars($_POST['codice']) : ''; ?>"
                                                required>
                                        </div>
                                        <!--Nome-->
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome</label>
                                            <input type="text" class="form-control" id="nome" name="nome"
                                                value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                                required>
                                        </div>
                                        <!--Indirizzo-->
                                        <div class="mb-3">
                                            <label for="indirizzo" class="form-label">Indirizzo</label>
                                            <input type="text" class="form-control" id="indirizzo" name="indirizzo"
                                                value="<?php echo isset($_POST['indirizzo']) ? htmlspecialchars($_POST['indirizzo']) : ''; ?>"
                                                required>
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