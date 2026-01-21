<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = new PDO('mysql:host=localhost;dbname=tresordemain;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Test COUNT:<br>";
$count = $pdo->query("SELECT COUNT(*) FROM article a WHERE a.disponibilite = 1")->fetchColumn();
echo "Total: " . $count . "<br>";

echo "<br>Test SELECT avec LEFT JOIN:<br>";
$sql = "SELECT a.*, 
               COALESCE(u.prenom, 'Artisan') AS artisan_prenom, 
               COALESCE(u.nom, 'Inconnu') AS artisan_nom, 
               COALESCE(u.badge_verifie, 0) AS artisan_verifie
        FROM article a 
        LEFT JOIN utilisateurs u ON a.id_artisan = u.id 
        WHERE a.disponibilite = 1 
        ORDER BY a.date_ajout DESC 
        LIMIT 12 OFFSET 0";

$result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
echo "Nombre de résultats: " . count($result) . "<br>";
foreach ($result as $r) {
    echo "Article: " . $r['nom_article'] . " | Artisan: " . $r['artisan_prenom'] . " " . $r['artisan_nom'] . "<br>";
}
