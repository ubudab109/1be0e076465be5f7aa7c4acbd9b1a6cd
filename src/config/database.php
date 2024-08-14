<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

/**
 * The function `getPgConnection` establishes a connection to a PostgreSQL database using environment
 * variables for host, port, database name, username, and password.
 * 
 * @return PDO The function `getPgConnection` is returning a PDO (PHP Data Objects) database connection
 * object.
 */
function getPgConnection(): PDO
{
    $host = $_ENV['POSTGRES_HOST'];
    $port = $_ENV['POSTGRES_PORT'];
    $dbname = $_ENV['POSTGRES_DB'];
    $user = $_ENV['POSTGRES_USER'];
    $password = $_ENV['POSTGRES_PASSWORD'];

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    try {
        $pdo = new PDO($dsn, $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception('Database connection failed: ' . $e->getMessage());
    }
}