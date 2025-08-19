<?php

function getConnectionDB()
{
    $host = 'localhost';
    $port = '5432';
    $dbname = 'progetto';
    $user = 'postgres';
    $password = 'luca_db';



    try {
        $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        throw new Exception('Errore di connessione al database: ' . $e->getMessage());
    }
}


