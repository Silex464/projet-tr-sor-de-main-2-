<?php
/**
 * Point d'entrée principal - Trésor de Main
 */

// Activer l'affichage des erreurs pour le debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérifier que le fichier existe avant de l'inclure
$pagePath = __DIR__ . '/php/Page_Acceuil.php';

if (file_exists($pagePath)) {
    include $pagePath;
} else {
    echo "Erreur: Fichier Page_Acceuil.php non trouvé à: " . $pagePath;
    echo "<br>Répertoire actuel: " . __DIR__;
    echo "<br>Fichiers dans le répertoire: <pre>";
    print_r(scandir(__DIR__));
    echo "</pre>";
}
