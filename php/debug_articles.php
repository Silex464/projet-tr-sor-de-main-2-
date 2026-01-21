<?php
// Debug script - Simule exactement All_Products.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=tresordemain;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Simule les paramètres de All_Products.php
    $page = 1;
    $perPage = 12;
    $offset = ($page - 1) * $perPage;
    $categorie = '';
    $prixMax = 1000;
    $tri = 'recent';
    
    // Construction de la requête (copié de All_Products.php)
    $where = ["a.disponibilite = 1"];
    $params = [];
    
    if (!empty($categorie)) {
        $where[] = "a.categorie = :categorie";
        $params[':categorie'] = $categorie;
    }
    
    if ($prixMax < 1000) {
        $where[] = "a.prix <= :prix_max";
        $params[':prix_max'] = $prixMax;
    }
    
    $whereClause = implode(' AND ', $where);
    echo "WHERE clause: $whereClause\n";
    echo "Params: " . print_r($params, true) . "\n";
    
    // Compter le total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM article a WHERE $whereClause");
    $countStmt->execute($params);
    $totalArticles = $countStmt->fetchColumn();
    echo "Total articles: $totalArticles\n";
    
    // Tri
    $orderBy = match($tri) {
        'prix_asc' => 'a.prix ASC',
        'prix_desc' => 'a.prix DESC',
        'nom' => 'a.nom_article ASC',
        default => 'a.date_ajout DESC'
    };
    
    // Requête principale (copiée de All_Products.php)
    $sql = "SELECT a.*, 
                   COALESCE(u.prenom, 'Artisan') AS artisan_prenom, 
                   COALESCE(u.nom, 'Inconnu') AS artisan_nom, 
                   COALESCE(u.badge_verifie, 0) AS artisan_verifie
            FROM article a 
            LEFT JOIN utilisateurs u ON a.id_artisan = u.id 
            WHERE $whereClause 
            ORDER BY $orderBy 
            LIMIT :limit OFFSET :offset";
    
    echo "SQL: $sql\n";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    echo "\n=== RESULTATS ===\n";
    echo "Nombre d'articles récupérés: " . count($articles) . "\n";
    
    foreach ($articles as $article) {
        echo "- ID: {$article['id_article']}, Nom: {$article['nom_article']}, Artisan: {$article['artisan_prenom']} {$article['artisan_nom']}\n";
    }
    
    if (empty($articles)) {
        echo "\n!! TABLEAU VIDE - Quelque chose filtre les résultats\n";
    } else {
        echo "\n** CA FONCTIONNE **\n";
    }
    
} catch (PDOException $e) {
    echo "ERREUR PDO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}
