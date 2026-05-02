<?php
/**
 * Database Configuration — AWS RDS / EC2 Local MySQL
 * Auto-detects RDS endpoints and enables SSL.
 */

require_once __DIR__ . '/env.php';

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $host   = getenv('DB_HOST') ?: 'localhost';
    $dbname = getenv('DB_NAME') ?: 'it_helpdesk';
    $user   = getenv('DB_USER') ?: 'root';
    $pass   = getenv('DB_PASS') ?: '';
    $port   = getenv('DB_PORT') ?: '3306';

    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        // Enable SSL for Amazon RDS connections
        if (strpos($host, 'rds.amazonaws.com') !== false) {
            $caPath = '/etc/ssl/certs/global-bundle.pem';
            if (file_exists($caPath)) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $caPath;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
        }

        $pdo = new PDO($dsn, $user, $pass, $options);

        $tz = getenv('APP_TIMEZONE');
        if ($tz) {
            $pdo->exec("SET time_zone = '+08:00'"); // SGT
        }

        return $pdo;
    } catch (PDOException $e) {
        $debug = getenv('APP_DEBUG') === 'true';
        error_log("Database connection failed: " . $e->getMessage());
        if ($debug) {
            die('<pre>DB Error: ' . htmlspecialchars($e->getMessage()) . '</pre>');
        }
        die('<div style="text-align:center;margin-top:100px;font-family:sans-serif;">
            <h2>Service Unavailable</h2>
            <p>Could not connect to the database. Please contact your administrator.</p>
        </div>');
    }
}
