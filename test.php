<?php
// Fichier de test pour vérifier que PHP fonctionne
echo "<h1>Test PHP - Trésor de Main</h1>";
echo "<p>PHP fonctionne correctement!</p>";
echo "<p>Version PHP: " . phpversion() . "</p>";
echo "<p>Serveur: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Répertoire courant: " . __DIR__ . "</p>";

// Test de connexion BDD
echo "<h2>Test connexion BDD</h2>";
try {
    $pdo = new PDO(
        "mysql:host=178.33.122.21;dbname=hangardb_yafa64220;charset=utf8mb4",
        "hangardb_yafa64220",
        "XQisTXtNI4niZbhXTFDqEqlN"
    );
    echo "<p style='color:green'>✓ Connexion à la base de données réussie!</p>";
    
    // Lister les tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Tables trouvées: " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Erreur BDD: " . $e->getMessage() . "</p>";
}

phpinfo();
