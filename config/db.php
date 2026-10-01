<?php
/**
 * ============================================================
 *  OHANABIZ — Database Connection (PDO)
 *  File   : config/db.php
 * ============================================================
 *
 *  Usage:
 *      require_once __DIR__ . '/../config/db.php';
 *      $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 * ============================================================
 */

if (!defined('DB_HOST'))    define('DB_HOST',    'localhost');
if (!defined('DB_PORT'))    define('DB_PORT',    '3306');
if (!defined('DB_NAME'))    define('DB_NAME',    'ohanabiz');
if (!defined('DB_USER'))    define('DB_USER',    'root');
if (!defined('DB_PASS'))    define('DB_PASS',    '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log('[OHANABIZ DB ERROR] ' . $e->getMessage());
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>OHANABIZ — Database Error</title></head>';
        echo '<body style="font-family:system-ui,sans-serif;background:#f6f3ee;color:#1c482c;padding:3rem;text-align:center;">';
        echo '<h1>Database connection failed</h1>';
        echo '<p>Please confirm that MySQL is running in XAMPP and that the <strong>ohanabiz</strong> schema has been imported from <code>config/ohanabiz_schema.sql</code>.</p>';
        echo '</body></html>';
        exit;
    }
}
