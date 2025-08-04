<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306', 'root', '');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS JO_data');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS SA_data');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS EG_data');
    $pdo->exec('CREATE DATABASE IF NOT EXISTS PS_data');
    echo "Databases created successfully\n";
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
