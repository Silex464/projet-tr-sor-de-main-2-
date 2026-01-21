<?php
/**
 * Configuration globale - Trésor de Main
 * Détecte automatiquement l'environnement (local ou production)
 */

// Détection automatique de l'environnement
$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) 
               || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

// Définir le chemin de base selon l'environnement
if ($isLocalhost) {
    // Environnement local (XAMPP, WAMP, etc.)
    define('BASE_PATH', '/Projet-Tr-sor-de-Main');
} else {
    // Environnement de production (garageisep, etc.)
    define('BASE_PATH', '');
}

// Chemins utiles
define('CSS_PATH', BASE_PATH . '/CSS');
define('JS_PATH', BASE_PATH . '/JavaScript');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('IMAGES_PATH', ASSETS_PATH . '/images');
define('PHP_PATH', BASE_PATH . '/php');
