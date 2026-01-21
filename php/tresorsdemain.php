<?php
/**
 * ============================================================================
 * CONFIGURATION BASE DE DONNÉES - Trésor de Main
 * ============================================================================
 * 
 * Ce fichier contient uniquement la configuration et connexion BDD.
 * Les fonctions d'authentification sont dans auth.php
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'tresordemain');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Fonction de connexion sécurisée à la base de données
 * @return PDO
 */
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        error_log("Erreur de connexion : " . $e->getMessage());
        die("Erreur de base de données. Veuillez réessayer plus tard.");
    }
}

/**
 * Fonction pour sécuriser les données entrantes
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>