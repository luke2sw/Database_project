<?php
include 'connection_db.php';

$error_message = ''; // Inizializza la variabile di messaggio di errore

// Connessione al database
$conn = getConnectionDB();

// Inizializza la variabile $telefono con una stringa vuota
$telefono = '';


try {


    // Query per ottenere le informazioni del personale
    $queryDba_Gestione_Strutture_Reparto = "
    SELECT r.telefono, r.nome, r.ospedale, r.direttore, o.apertura, o.chiusura, o.giorno
    FROM REPARTO r
    LEFT JOIN REPARTOPOSSIEDEORARIO o ON r.telefono = o.Reparto
    ORDER BY r.telefono, 
        CASE 
            WHEN o.giorno = 'Lunedì' THEN 1
            WHEN o.giorno = 'Martedì' THEN 2
            WHEN o.giorno = 'Mercoledì' THEN 3
            WHEN o.giorno = 'Giovedì' THEN 4
            WHEN o.giorno = 'Venerdì' THEN 5
            WHEN o.giorno = 'Sabato' THEN 6
            WHEN o.giorno = 'Domenica' THEN 7
            ELSE 8 -- Aggiunta nel caso ci sia un valore non riconosciuto
        END
    ";

    // Prepara la dichiarazione
    $stmtReparti = $conn->prepare($queryDba_Gestione_Strutture_Reparto);

    // Esegui la dichiarazione
    $stmtReparti->execute();

    // Ottieni i risultati
    $reparti = $stmtReparti->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die('Errore durante il recupero dei reparti: ' . $e->getMessage());
}


// Recupera le opzioni esistenti per i campi
$telefoniEsistenti = [];
$nomiEsistenti = [];
$ospedaliEsistenti = [];
$direttoriEsistenti = [];

// Query per ottenere i dati esistenti
$queryEsistenti = "SELECT DISTINCT telefono FROM REPARTO";
$stmtEsistenti = $conn->prepare($queryEsistenti);
$stmtEsistenti->execute();
$telefoniEsistenti = $stmtEsistenti->fetchAll(PDO::FETCH_COLUMN);

//query per ottenere i nomi 
$queryNomi = "SELECT DISTINCT nome FROM REPARTO";
$stmtNomi = $conn->prepare($queryNomi);
$stmtNomi->execute();
$nomiEsistenti = $stmtNomi->fetchAll(PDO::FETCH_COLUMN);

//query per ottenere gli ospedali
$queryOspedali = "SELECT DISTINCT ospedale FROM REPARTO";
$stmtOspedali = $conn->prepare($queryOspedali);
$stmtOspedali->execute();
$ospedaliEsistenti = $stmtOspedali->fetchAll(PDO::FETCH_COLUMN);

//query per ottenere i direttori
$queryDirettori = "SELECT DISTINCT direttore FROM REPARTO";
$stmtDirettori = $conn->prepare($queryDirettori);
$stmtDirettori->execute();
$direttoriEsistenti = $stmtDirettori->fetchAll(PDO::FETCH_COLUMN);

// Recupera il reparto esistente se ID passato
$telefono = isset($_POST['telefono']) ? trim($_POST['telefono']) : '';
$querySelectReparto = "SELECT * FROM REPARTO WHERE telefono = :telefono";
$stmtSelectReparto = $conn->prepare($querySelectReparto);
$stmtSelectReparto->execute([':telefono' => $telefono]);
$reparto = $stmtSelectReparto->fetch(PDO::FETCH_ASSOC);


//GESTIONE REPARTO
function checkRepartoEsistente($conn, $telefono)
{
    // Query per controllare l'esistenza del reparto con quel numero di telefono 
    $query = "SELECT COUNT(*) FROM REPARTO WHERE telefono = :telefono";

    try {
        // Preparazione e esecuzione della query
        $stmt = $conn->prepare($query);
        $stmt->execute([':telefono' => $telefono]);

        // Ottieni il conteggio del reparto con quel numero di telefono 
        $count = $stmt->fetchColumn();

        // Restituisci true se esiste almeno un reparto con quel numero di telefono, altrimenti false
        return $count > 0;
    } catch (PDOException $e) {
        die('Errore durante il controllo del reparto: ' . $e->getMessage());
    }
}

//aggiungi reparto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['aggiungiReparto'])) {
    $telefono = $_POST['telefono'];


    // Controlla se il reparto esiste già
    if (!checkRepartoEsistente($conn, $telefono)) {
        echo 'Reparto non presente';

        // Dati del reparto
        $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
        $ospedale = isset($_POST['ospedale']) ? trim($_POST['ospedale']) : null;
        $direttore = isset($_POST['direttore']) ? trim($_POST['direttore']) : null;

        // Dati degli orari
        $giorni = isset($_POST['giorno']) ? $_POST['giorno'] : [];
        $aperture = isset($_POST['apertura']) ? $_POST['apertura'] : [];
        $chiusure = isset($_POST['chiusura']) ? $_POST['chiusura'] : [];



        // Verifica che tutti gli array abbiano la stessa lunghezza
        if (count($giorni) == count($aperture) && count($aperture) == count($chiusure)) {

            try {
                // Inserisci il nuovo reparto
                $queryInsertReparto = "
                INSERT INTO REPARTO (telefono, nome, ospedale, direttore) 
                VALUES (:telefono, :nome, :ospedale, :direttore)
            ";
                $stmtInsertReparto = $conn->prepare($queryInsertReparto);
                $stmtInsertReparto->execute([
                    ':telefono' => $telefono,
                    ':nome' => $nome,
                    ':ospedale' => $ospedale,
                    ':direttore' => $direttore,
                ]);

                // Prepariamo l'inserimento degli orari
                $queryInsertOrario = "
                    INSERT INTO ORARIO (Apertura, Chiusura, Giorno) 
                    VALUES (:apertura, :chiusura, :giorno)
                ";
                $stmtInsertOrario = $conn->prepare($queryInsertOrario);

                // Inseriamo gli orari nella tabella ORARIO se non esistono
                foreach ($giorni as $index => $giorno) {
                    $apertura = $aperture[$index];
                    $chiusura = $chiusure[$index];

                    // Verifica se l'orario esiste già
                    $queryCheckOrario = "
                        SELECT 1 
                        FROM ORARIO 
                        WHERE Apertura = :apertura 
                        AND Chiusura = :chiusura 
                        AND Giorno = :giorno
                    ";
                    $stmtCheckOrario = $conn->prepare($queryCheckOrario);
                    $stmtCheckOrario->execute([
                        ':apertura' => $apertura,
                        ':chiusura' => $chiusura,
                        ':giorno' => $giorno
                    ]);

                    if ($stmtCheckOrario->rowCount() == 0) {
                        // Se non esiste, inserisci
                        $stmtInsertOrario->execute([
                            ':apertura' => $apertura,
                            ':chiusura' => $chiusura,
                            ':giorno' => $giorno
                        ]);
                    }
                }

                // Inserisci gli orari collegati al reparto
                $queryInsertRepartoOrario = "
                    INSERT INTO REPARTOPOSSIEDEORARIO (Reparto, Apertura, Chiusura, Giorno) 
                    VALUES (:reparto, :apertura, :chiusura, :giorno)
                ";
                $stmtInsertRepartoOrario = $conn->prepare($queryInsertRepartoOrario);

                foreach ($giorni as $index => $giorno) {
                    $apertura = $aperture[$index];
                    $chiusura = $chiusure[$index];

                    if (!empty($giorno) && !empty($apertura) && !empty($chiusura)) {
                        $stmtInsertRepartoOrario->execute([
                            ':reparto' => $telefono,
                            ':apertura' => $apertura,
                            ':chiusura' => $chiusura,
                            ':giorno' => $giorno
                        ]);
                    }
                }

                // Se tutto va bene, conferma la transazione
                echo "Reparto e orari salvati con successo!";

            } catch (PDOException $e) {
                die('Errore durante l\'inserimento del reparto: ' . $e->getMessage());
            }

            // Reindirizzamento per ripulire il form
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento
        } else {
            echo "Errore: i dati degli orari non sono validi.";
        }
    } else {
        echo 'Reparto già presente.';
    }
}



//modifica reparto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modificaReparto'])) {
    $telefono = $_POST['telefono'];

    // Recupera il reparto esistente
    $querySelectReparto = "SELECT nome, ospedale, apertura, direttore, chiusura, giorno 
                           FROM REPARTO 
                           LEFT JOIN REPARTOPOSSIEDEORARIO 
                           ON REPARTO.telefono = REPARTOPOSSIEDEORARIO.reparto 
                           WHERE telefono = :telefono";
    $stmtSelectReparto = $conn->prepare($querySelectReparto);
    $stmtSelectReparto->execute([':telefono' => $telefono]);
    $reparto = $stmtSelectReparto->fetch(PDO::FETCH_ASSOC);

    if ($reparto === false) {
        die('Errore: reparto non trovato.');
    }

    // Valori di data attuali
    $currentNome = $reparto['nome'];
    $currentOspedale = $reparto['ospedale'];
    $currentDirettore = $reparto['direttore'];
    $currentApertura = $reparto['apertura'];
    $currentChiusura = $reparto['chiusura'];
    $currentGiorno = $reparto['giorno'];

    // Ottieni i valori dei campi
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : $currentNome;
    $ospedale = isset($_POST['ospedale']) ? trim($_POST['ospedale']) : $currentOspedale;
    $direttore = isset($_POST['direttore']) ? trim($_POST['direttore']) : $currentDirettore;

    // Gestione degli orari e dei giorni
    $aperture = isset($_POST['apertura']) ? $_POST['apertura'] : [];
    $chiusure = isset($_POST['chiusura']) ? $_POST['chiusura'] : [];
    $giorni = isset($_POST['giorno']) ? $_POST['giorno'] : [];

    // Controllo del nome
    if (empty($nome)) {
        $nome = $currentNome; // Mantieni il valore corrente se non fornito
    }

    // Controllo dell'ospedale
    if (empty($ospedale)) {
        $ospedale = $currentOspedale; // Mantieni il valore corrente se non fornito
    }

    // Controllo del direttore
    if (empty($direttore)) {
        $direttore = $currentDirettore; // Mantieni il valore corrente se non fornito
    }

    if (empty($apertura)) {
        $apertura = $currentApertura; // Mantieni il valore corrente se non fornito
    }
    if (empty($chiusura)) {
        $chiusura = $currentChiusura; // Mantieni il valore corrente se non fornito
    }
    if (empty($giorno)) {
        $giorno = $currentGiorno; // Mantieni il valore corrente se non fornito
    }

    // Verifica che tutti gli array abbiano la stessa lunghezza
    if (count($giorni) == count($aperture) && count($aperture) == count($chiusure)) {
        try {
            // Aggiorna il reparto
            $queryUpdateReparto = "
                UPDATE REPARTO
                SET Nome = :nome,
                    Ospedale = :ospedale,
                    Direttore = :direttore
                WHERE Telefono = :telefono
                ";
            $stmtUpdateReparto = $conn->prepare($queryUpdateReparto);
            $stmtUpdateReparto->execute([
                ':telefono' => $telefono,
                ':nome' => $nome,
                ':ospedale' => $ospedale,
                ':direttore' => $direttore,
            ]);

            // Prepariamo l'inserimento degli orari nella tabella ORARIO se non esistono
            $queryInsertOrario = "
                INSERT INTO ORARIO (Apertura, Chiusura, Giorno) 
                VALUES (:apertura, :chiusura, :giorno)
            ";
            $stmtInsertOrario = $conn->prepare($queryInsertOrario);

            $queryCheckOrario = "
                SELECT 1 
                FROM ORARIO 
                WHERE Apertura = :apertura 
                AND Chiusura = :chiusura 
                AND Giorno = :giorno
            ";
            $stmtCheckOrario = $conn->prepare($queryCheckOrario);

            $queryInsertRepartoOrario = "
                INSERT INTO REPARTOPOSSIEDEORARIO (Reparto, Apertura, Chiusura, Giorno) 
                VALUES (:reparto, :apertura, :chiusura, :giorno)
            ";
            $stmtInsertRepartoOrario = $conn->prepare($queryInsertRepartoOrario);

            $queryUpdateRepartoOrario = "
                UPDATE REPARTOPOSSIEDEORARIO 
                SET Apertura = :apertura, Chiusura = :chiusura
                WHERE Reparto = :reparto AND Giorno = :giorno
            ";
            $stmtUpdateRepartoOrario = $conn->prepare($queryUpdateRepartoOrario);

            // Ciclo sugli orari e giorni forniti
            foreach ($giorni as $index => $giorno) {
                $apertura = $aperture[$index];
                $chiusura = $chiusure[$index];

                if (!empty($apertura) && !empty($chiusura)) {

                    // Verifica se l'orario esiste nella tabella ORARIO
                    $stmtCheckOrario->execute([
                        ':apertura' => $apertura,
                        ':chiusura' => $chiusura,
                        ':giorno' => $giorno
                    ]);

                    if ($stmtCheckOrario->rowCount() == 0) {
                        // Se l'orario non esiste, inseriscilo nella tabella ORARIO
                        $stmtInsertOrario->execute([
                            ':apertura' => $apertura,
                            ':chiusura' => $chiusura,
                            ':giorno' => $giorno
                        ]);
                    }

                    // Verifica se il giorno esiste già per il reparto
                    $queryCheckRepartoOrario = "
                        SELECT 1 
                        FROM REPARTOPOSSIEDEORARIO 
                        WHERE Reparto = :reparto 
                        AND Giorno = :giorno
                    ";
                    $stmtCheckRepartoOrario = $conn->prepare($queryCheckRepartoOrario);
                    $stmtCheckRepartoOrario->execute([
                        ':reparto' => $telefono,
                        ':giorno' => $giorno
                    ]);

                    if ($stmtCheckRepartoOrario->rowCount() > 0) {
                        // Se esiste già un record per questo giorno, aggiorna gli orari
                        $stmtUpdateRepartoOrario->execute([
                            ':reparto' => $telefono,
                            ':apertura' => $apertura,
                            ':chiusura' => $chiusura,
                            ':giorno' => $giorno
                        ]);
                    } else {
                        // Altrimenti inserisci un nuovo record
                        $stmtInsertRepartoOrario->execute([
                            ':reparto' => $telefono,
                            ':apertura' => $apertura,
                            ':chiusura' => $chiusura,
                            ':giorno' => $giorno
                        ]);
                    }
                }

            }

                // Reindirizzamento per ripulire il form
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit; // Assicurati di interrompere l'esecuzione dello script dopo il reindirizzamento
            
        } catch (PDOException $e) {
            die('Errore durante l\'aggiornamento del reparto: ' . $e->getMessage());
        }
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

    <!--SCRIPT REPARTO-->
    <script>
        // Funzione per comprimere/espandere la tabella
        function toggleTableReparto() {
            var table = document.getElementById("RepartoTable");
            var button = document.getElementById("toggleButtonReparto");

            // Controlla lo stato della tabella (visibile o nascosta)
            if (table.style.display === "none") {
                table.style.display = "table";  // Mostra la tabella
                button.innerHTML = "Comprimi";  // Cambia il testo del bottone
            } else {
                table.style.display = "none";  // Nascondi la tabella
                button.innerHTML = "Espandi";  // Cambia il testo del bottone
            }
        }

        // Funzione per comprimere/espandere la sezione Modifica Reparto
        function toggleModificaReparto() {
            var section = document.getElementById("modificaSectionReparto");
            var button = document.getElementById("toggleModificaReparto");

            // Controlla lo stato della sezione (visibile o nascosta)
            if (section.style.display === "none") {
                section.style.display = "block";  // Mostra la sezione
                button.innerHTML = "Comprimi";  // Cambia il testo del bottone
            } else {
                section.style.display = "none";  // Nascondi la sezione
                button.innerHTML = "Espandi";  // Cambia il testo del bottone
            }
        }

        // Funzione per comprimere/espandere la sezione Aggiungi Reparto
        function toggleAggiungiReparto() {
            var section = document.getElementById("aggiungiSectionReparto");
            var button = document.getElementById("toggleAggiungiReparto");

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
        function toggleDetails(telefono) {
            var details = document.getElementById("details-" + telefono);
            // Toggle the collapse class to show or hide the details
            if (details.classList.contains("collapse")) {
                details.classList.remove("collapse");
                details.classList.add("show");
            } else {
                details.classList.remove("show");
                details.classList.add("collapse");
            }
        }
    </script>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('aggiungiRiga').addEventListener('click', function () {
                let table = document.getElementById('orariTable').getElementsByTagName('tbody')[0];
                let newRow = table.insertRow();

                let cell1 = newRow.insertCell(0);
                let select = document.createElement('select');
                select.className = 'form-select';
                select.name = 'giorno';
                select.innerHTML = `
                <option value="Lunedì">Lunedì</option>
                <option value="Martedì">Martedì</option>
                <option value="Mercoledì">Mercoledì</option>
                <option value="Giovedì">Giovedì</option>
                <option value="Venerdì">Venerdì</option>
                <option value="Sabato">Sabato</option>
                <option value="Domenica">Domenica</option>
            `;
                cell1.appendChild(select);

                /**
                 *inserisce una nuova riga in una tabella con campi di input per gli orari di apertura e chiusura e un pulsante di rimozione.
                 * 
                 * @param {HTMLElement} newRow - Il nuovo elemento di riga da inserire nella tabella.
                 * @param {string} apertura -  Il nome del campo di input per l'orario di apertura.
                 * @param {string} chiusura - Il nome del campo di input per l'orario di chiusura.
                 * @param {HTMLElement} btnRimuovi - L'elemento del pulsante di rimozione.
                 * @returns {void}
                 */
                let cell2 = newRow.insertCell(1);
                let aperturaInput = document.createElement('input');
                aperturaInput.type = 'time';
                aperturaInput.className = 'form-control';
                aperturaInput.name = 'apertura';
                cell2.appendChild(aperturaInput);

                let cell3 = newRow.insertCell(2);
                let chiusuraInput = document.createElement('input');
                chiusuraInput.type = 'time';
                chiusuraInput.className = 'form-control';
                chiusuraInput.name = 'chiusura';
                cell3.appendChild(chiusuraInput);

                let cell4 = newRow.insertCell(3);
                let btnRimuovi = document.createElement('button');
                btnRimuovi.type = 'button';
                btnRimuovi.className = 'btn btn-danger';
                btnRimuovi.innerText = 'Rimuovi';
                btnRimuovi.onclick = function () { rimuoviRiga(this); };
                cell4.appendChild(btnRimuovi);
            });

            function rimuoviRiga(button) {
                let row = button.parentElement.parentElement;
                row.remove();
            }

            /**
             * Questo frammento di codice aggiunge un ascoltatore di eventi all'evento submit di un modulo.
             * Impedisce il comportamento predefinito di invio del modulo.
             * 
             * Recupera i valori dei campi “telefono”, “nome”, “ospedale” e “direttore” del modulo.
             * 
             * @param {Event} e - The submit event object.
             * @returns {void}
             */
            document.querySelector('form').addEventListener('submit', function (e) {
                e.preventDefault();

                let form = e.target;
                let table = document.getElementById('orariTable').getElementsByTagName('tbody')[0];
                let rows = table.getElementsByTagName('tr');

                let telefono = form.telefono.value;
                let nome = form.nome.value;
                let ospedale = form.ospedale.value;
                let direttore = form.direttore.value;

                // Invia una richiesta separata per ogni riga della tabella
                for (let row of rows) {
                    // Ottieni i valori dei campi della riga corrente
                    let giorno = row.cells[0].getElementsByTagName('select')[0].value;
                    let apertura = row.cells[1].getElementsByTagName('input')[0].value;
                    let chiusura = row.cells[2].getElementsByTagName('input')[0].value;

                    // Invia la richiesta al server
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            telefono: telefono,
                            nome: nome,
                            ospedale: ospedale,
                            direttore: direttore,
                            giorno: giorno,
                            apertura: apertura,
                            chiusura: chiusura
                        })
                    })
                        .then(response => response.text())
                        .then(result => {
                            console.log(result); // Gestisci la risposta del server
                        })
                        .catch(error => {
                            console.error('Errore:', error);
                        });
                }
            });
        });
    </script>

    <script>
        /**
         * Aggiunge una nuova riga alla tabella quando si fa clic sul pulsante “aggiungiRigaModifica”.
         * La nuova riga contiene un elemento di selezione per scegliere un giorno della settimana,
         * due elementi di input per specificare gli orari di apertura e chiusura,
         * e un pulsante per rimuovere la riga.
         */
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('aggiungiRigaModifica').addEventListener('click', function () {
                let table = document.getElementById('orariTableModifica').getElementsByTagName('tbody')[0];
                let newRow = table.insertRow();

                let cell1 = newRow.insertCell(0);
                let select = document.createElement('select');
                select.className = 'form-select';
                select.name = 'giorno[]';
                select.innerHTML = `
                <option value="Lunedì">Lunedì</option>
                <option value="Martedì">Martedì</option>
                <option value="Mercoledì">Mercoledì</option>
                <option value="Giovedì">Giovedì</option>
                <option value="Venerdì">Venerdì</option>
                <option value="Sabato">Sabato</option>
                <option value="Domenica">Domenica</option>
            `;
                cell1.appendChild(select);

                let cell2 = newRow.insertCell(1);
                let aperturaInput = document.createElement('input');
                aperturaInput.type = 'time';
                aperturaInput.className = 'form-control';
                aperturaInput.name = 'apertura[]';
                cell2.appendChild(aperturaInput);

                let cell3 = newRow.insertCell(2);
                let chiusuraInput = document.createElement('input');
                chiusuraInput.type = 'time';
                chiusuraInput.className = 'form-control';
                chiusuraInput.name = 'chiusura[]';
                cell3.appendChild(chiusuraInput);

                let cell4 = newRow.insertCell(3);
                let btnRimuovi = document.createElement('button');
                btnRimuovi.type = 'button';
                btnRimuovi.className = 'btn btn-danger';
                btnRimuovi.innerText = 'Rimuovi';
                btnRimuovi.onclick = function () { rimuoviRiga(this); };
                cell4.appendChild(btnRimuovi);
            });

            function rimuoviRiga(button) {
                let row = button.parentElement.parentElement;
                row.remove();
            }

            /**
             * Questo event listener è responsabile della gestione dell'invio del modulo per la modifica di un reparto.
             * Previene il comportamento predefinito di invio del modulo e recupera i dati del modulo necessari.
             * I dati del modulo vengono quindi preparati e inviati come richiesta di aggiornamento delle informazioni sul reparto.
             *
             * @param {Event} e - L'oggetto evento che rappresenta l'invio del modulo.
             */
            document.getElementById('modificaReparto').addEventListener('submit', function (e) {
                e.preventDefault();

                let form = e.target;
                let table = document.getElementById('orariTableModifica').getElementsByTagName('tbody')[0];
                let rows = table.getElementsByTagName('tr');

                let telefono = form.telefono.value;
                let nome = form.nome.value;
                let ospedale = form.ospedale.value;
                let direttore = form.direttore.value;

                let formData = new FormData(form);

                // Prepariamo i dati da inviare
                let data = {
                    telefono: telefono,
                    nome: nome,
                    ospedale: ospedale,
                    direttore: direttore,
                };

                // Aggiungi dati della tabella
                data.orari = Array.from(rows).map(row => {
                    return {
                        giorno: row.cells[0].getElementsByTagName('select')[0].value,
                        apertura: row.cells[1].getElementsByTagName('input')[0].value,
                        chiusura: row.cells[2].getElementsByTagName('input')[0].value
                    };
                });

                /**
                 * Invia una richiesta POST al server con i dati forniti e gestisce la risposta.
                 *
                 * @param {string} form.action - L'URL a cui inviare la richiesta.
                 * @param {object} data - I dati da inviare nel corpo della richiesta come JSON.
                 * @returns {Promise} - A promise that resolves with the response text or rejects with an error.
                 */
                fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                    .then(response => response.text())
                    .then(result => {
                        console.log(result); // Gestisci la risposta del server
                    })
                    .catch(error => {
                        console.error('Errore:', error);
                    });
            });
        });
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
                        <h2 class="card-title" style="width: 20%">Elenco Reparti</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la tabella -->
                            <button id="toggleButtonReparto" class="btn btn-primary"
                                onclick="toggleTableReparto()">Comprimi</button>
                        </div>
                    </div>
                </div>

                <!-- Tabella per visualizzare i dati del reparto -->
                <div class="row" style="margin-top:1%; margin-bottom:3%">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body" style="padding:0">
                                <?php if (!empty($reparti)): ?>
                                    <table id="RepartoTable" class="table table-bordered" style="margin-bottom:0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Telefono</th>
                                                <th scope="col">Nome</th>
                                                <th scope="col">Ospedale</th>
                                                <th scope="col">Direttore</th>
                                                <th scope="col">Orari</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $currentTelefono = '';
                                            $counter = 1; // Contatore per i reparti distinti
                                            foreach ($reparti as $Reparto):
                                                // Raggruppa per telefono
                                                if ($Reparto['telefono'] !== $currentTelefono):
                                                    if ($currentTelefono !== ''): ?>
                                                        <!-- Righe di dettagli -->
                                                        <tr class="collapse"
                                                            id="details-<?php echo htmlspecialchars($currentTelefono); ?>">
                                                            <td colspan="6">
                                                                <div class="details-container">
                                                                    <table class="table table-striped">
                                                                        <thead>
                                                                            <tr>
                                                                                <th scope="col"></th>
                                                                                <th scope="col"></th>
                                                                                <th scope="col">Giorno</th>
                                                                                <th scope="col">Apertura</th>
                                                                                <th scope="col">Chiusura</th>

                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($orari[$currentTelefono] as $orario): ?>
                                                                                <tr>
                                                                                    <td></td>
                                                                                    <td></td>
                                                                                    <td><?php echo htmlspecialchars($orario['giorno']); ?>
                                                                                    </td>
                                                                                    <td><?php echo htmlspecialchars($orario['apertura']); ?>
                                                                                    </td>
                                                                                    <td><?php echo htmlspecialchars($orario['chiusura']); ?>
                                                                                    </td>

                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>

                                                    <!-- Righe principali -->
                                                    <tr>
                                                        <th scope="row"><?php echo $counter++; ?></th>
                                                        <td><?php echo htmlspecialchars($Reparto['telefono']); ?></td>
                                                        <td><?php echo htmlspecialchars($Reparto['nome']); ?></td>
                                                        <td><?php echo htmlspecialchars($Reparto['ospedale']); ?></td>
                                                        <td><?php echo htmlspecialchars($Reparto['direttore']); ?></td>
                                                        <td>
                                                            <button class="btn btn-primary" type="button"
                                                                style="width:80%; margin-left:10%"
                                                                onclick="toggleDetails('<?php echo htmlspecialchars($Reparto['telefono']); ?>')">
                                                                Mostra orari
                                                            </button>
                                                        </td>
                                                    </tr>

                                                    <?php
                                                    $currentTelefono = $Reparto['telefono'];
                                                    $orari[$currentTelefono] = [];
                                                endif;
                                                // Aggiungi gli orari al telefono corrente
                                                if ($Reparto['apertura'] !== null):
                                                    $orari[$currentTelefono][] = [
                                                        'apertura' => $Reparto['apertura'],
                                                        'chiusura' => $Reparto['chiusura'],
                                                        'giorno' => $Reparto['giorno']
                                                    ];
                                                endif;
                                            endforeach;
                                            // Stampa gli orari per l'ultimo telefono
                                            if ($currentTelefono !== ''): ?>
                                                <!-- Righe di dettagli -->
                                                <tr class="collapse"
                                                    id="details-<?php echo htmlspecialchars($currentTelefono); ?>">
                                                    <td colspan="6">
                                                        <div class="details-container">
                                                            <table class="table table-striped">
                                                                <thead>
                                                                    <tr>
                                                                        <th scope="col"></th>
                                                                        <th scope="col"></th>
                                                                        <th scope="col">Giorno</th>
                                                                        <th scope="col">Apertura</th>
                                                                        <th scope="col">Chiusura</th>

                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($orari[$currentTelefono] as $orario): ?>
                                                                        <tr>
                                                                            <td></td>
                                                                            <td></td>
                                                                            <td><?php echo htmlspecialchars($orario['giorno']); ?>
                                                                            </td>
                                                                            <td><?php echo htmlspecialchars($orario['apertura']); ?>
                                                                            </td>
                                                                            <td><?php echo htmlspecialchars($orario['chiusura']); ?>
                                                                            </td>

                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p style="margin-top: 1rem; margin-left:1%">Nessun reparto trovato.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="container title" style="margin-top:2%">

                    <!-- Flex container per il titolo e il pulsante di comprimi -->
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="card-title">Modifica Reparto</h2>
                        <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                            <!-- Bottone per comprimere/espandere la sezione Modifica Personale -->
                            <button id="toggleModificaReparto" class="btn btn-primary"
                                onclick="toggleModificaReparto()">Comprimi</button>
                        </div>
                    </div>

                    <!--Modifica Reparto-->
                    <div id="modificaSectionReparto">
                        <div class="row" style="margin-top:1%; margin-bottom:3%">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-body">
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>"
                                            method="post">
                                            <!--Telefono (per identificare il reparto da modificare)-->
                                            <div class="mb-3">
                                                <label for="telefono" class="form-label">Numero di Telefono</label>
                                                <input type="text" class="form-control" id="telefono" name="telefono"
                                                    value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                                                    required>
                                            </div>
                                            <!--Nome-->
                                            <div class="mb-3">
                                                <label for="nome" class="form-label">Nome</label>
                                                <input type="text" class="form-control" id="nome" name="nome"
                                                    value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">
                                            </div>
                                            <!--Ospedale-->
                                            <div class="mb-3">
                                                <label for="ospedale" class="form-label">Ospedale</label>
                                                <input type="text" class="form-control" id="ospedale" name="ospedale"
                                                    value="<?php echo isset($_POST['ospedale']) ? htmlspecialchars($_POST['ospedale']) : ''; ?>">
                                            </div>

                                            <!--Direttore-->
                                            <div class="mb-3">
                                                <label for="direttore" class="form-label">Direttore</label>
                                                <input type="text" class="form-control" id="direttore" name="direttore"
                                                    value="<?php echo isset($_POST['direttore']) ? htmlspecialchars($_POST['direttore']) : ''; ?>">
                                            </div>
                                            <!-- Sezione Orari di Apertura/Chiusura e Giorni -->
                                            <div class="mb-3">
                                                <label class="form-label">Orari di Apertura/Chiusura e Giorni</label>
                                                <table class="table table-bordered" id="orariTableModifica">
                                                    <thead>
                                                        <tr>
                                                            <th>Giorno</th>
                                                            <th>Orario Apertura</th>
                                                            <th>Orario Chiusura</th>
                                                            <th>Azione</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Riga vuota di default -->
                                                        <tr>
                                                            <td>
                                                                <select class="form-select" name="giorno[]">
                                                                    <option value="Lunedì">Lunedì</option>
                                                                    <option value="Martedì">Martedì</option>
                                                                    <option value="Mercoledì">Mercoledì</option>
                                                                    <option value="Giovedì">Giovedì</option>
                                                                    <option value="Venerdì">Venerdì</option>
                                                                    <option value="Sabato">Sabato</option>
                                                                    <option value="Domenica">Domenica</option>
                                                                </select>
                                                            </td>
                                                            <td><input type="time" class="form-control"
                                                                    name="apertura[]"></td>
                                                            <td><input type="time" class="form-control"
                                                                    name="chiusura[]"></td>
                                                            <td><button type="button" class="btn btn-danger"
                                                                    onclick="rimuoviRiga(this)">Rimuovi</button></td>
                                                        </tr>
                                                        <!-- Righe aggiuntive saranno inserite dinamicamente -->
                                                        <?php if (isset($repartoOrari) && is_array($repartoOrari)): ?>
                                                            <?php foreach ($repartoOrari as $orario): ?>
                                                                <tr>
                                                                    <td>
                                                                        <select class="form-select" name="giorno[]">
                                                                            <option value="Lunedì" <?php echo $orario['giorno'] == 'Lunedì' ? 'selected' : ''; ?>>Lunedì</option>
                                                                            <option value="Martedì" <?php echo $orario['giorno'] == 'Martedì' ? 'selected' : ''; ?>>Martedì</option>
                                                                            <option value="Mercoledì" <?php echo $orario['giorno'] == 'Mercoledì' ? 'selected' : ''; ?>>Mercoledì</option>
                                                                            <option value="Giovedì" <?php echo $orario['giorno'] == 'Giovedì' ? 'selected' : ''; ?>>Giovedì</option>
                                                                            <option value="Venerdì" <?php echo $orario['giorno'] == 'Venerdì' ? 'selected' : ''; ?>>Venerdì</option>
                                                                            <option value="Sabato" <?php echo $orario['giorno'] == 'Sabato' ? 'selected' : ''; ?>>Sabato</option>
                                                                            <option value="Domenica" <?php echo $orario['giorno'] == 'Domenica' ? 'selected' : ''; ?>>Domenica</option>
                                                                        </select>
                                                                    </td>
                                                                    <td><input type="time" class="form-control"
                                                                            name="apertura[]"
                                                                            value="<?php echo htmlspecialchars($orario['apertura']); ?>">
                                                                    </td>
                                                                    <td><input type="time" class="form-control"
                                                                            name="chiusura[]"
                                                                            value="<?php echo htmlspecialchars($orario['chiusura']); ?>">
                                                                    </td>
                                                                    <td><button type="button" class="btn btn-danger"
                                                                            onclick="rimuoviRiga(this)">Rimuovi</button></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                                <button type="button" class="btn btn-secondary"
                                                    id="aggiungiRigaModifica">Aggiungi Riga</button>
                                            </div>

                                            <button type="submit" name="modificaReparto" class="btn btn-success"
                                                style="float:right">Modifica</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!--Aggiungi Reparto-->
                <!-- Flex container per il titolo e il pulsante di ritorno -->
                <div class="d-flex justify-content-between align-items-center" style="margin-top:2%">
                    <h2 class="card-title">Aggiungi Reparto</h2>
                    <div class="d-flex justify-content-end" style="margin-bottom: 10px;">
                        <!-- Bottone per comprimere/espandere la sezione Aggiungi Personale -->
                        <button id="toggleAggiungiReparto" class="btn btn-primary"
                            onclick="toggleAggiungiReparto()">Comprimi</button>
                    </div>
                </div>
                <div id="aggiungiSectionReparto">
                    <div class="row" style="margin-top:1%; margin-bottom:3%">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                                        <!--Numero di Telefono (per identificare il reparto da modificare)-->
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Numero di Telefono</label>
                                            <input type="text" class="form-control" id="telefono" name="telefono"
                                                value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>"
                                                required>
                                        </div>
                                        <!--Nome-->
                                        <div class="mb-3">
                                            <label for="nome" class="form-label">Nome</label>
                                            <input type="text" class="form-control" id="nome" name="nome"
                                                value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>"
                                                required>
                                        </div>
                                        <!--Ospedale-->
                                        <div class="mb-3">
                                            <label for="ospedale" class="form-label">Codice dell'ospedale</label>
                                            <input type="text" class="form-control" id="ospedale" name="ospedale"
                                                value="<?php echo isset($_POST['ospedale']) ? htmlspecialchars($_POST['ospedale']) : ''; ?>"
                                                required>
                                        </div>

                                        <!--Direttore-->
                                        <div class="mb-3">
                                            <label for="direttore" class="form-label">Direttore</label>
                                            <input type="text" class="form-control" id="direttore" name="direttore"
                                                value="<?php echo isset($_POST['direttore']) ? htmlspecialchars($_POST['direttore']) : ''; ?>"
                                                required>
                                        </div>

                                        <!-- Etichetta per la sezione Orari di Apertura/Chiusura e Giorni -->
                                        <div class="mb-3">
                                            <label class="form-label">Orari di Apertura/Chiusura e Giorni</label>
                                            <table class="table table-bordered" id="orariTable">
                                                <thead>
                                                    <tr>
                                                        <th>Giorno</th>
                                                        <th>Orario Apertura</th>
                                                        <th>Orario Chiusura</th>
                                                        <th>Azione</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <select class="form-select" name="giorno[]">
                                                                <option value="Lunedì">Lunedì</option>
                                                                <option value="Martedì">Martedì</option>
                                                                <option value="Mercoledì">Mercoledì</option>
                                                                <option value="Giovedì">Giovedì</option>
                                                                <option value="Venerdì">Venerdì</option>
                                                                <option value="Sabato">Sabato</option>
                                                                <option value="Domenica">Domenica</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="time" class="form-control" name="apertura[]">
                                                        </td>
                                                        <td><input type="time" class="form-control" name="chiusura[]">
                                                        </td>
                                                        <td><button type="button" class="btn btn-danger"
                                                                onclick="rimuoviRiga(this)">Rimuovi</button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <button type="button" class="btn btn-secondary" id="aggiungiRiga">Aggiungi
                                                Riga</button>
                                        </div>


                                        <button type="submit" name="aggiungiReparto" class="btn btn-success"
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