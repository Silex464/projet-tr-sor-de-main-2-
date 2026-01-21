<?php
/**
 * API Favoris - Gestion des favoris via AJAX
 * Allows adding/removing articles from favorites
 */

// Include auth.php first - it handles session_start internally
require_once 'auth.php';

// Set JSON response header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté', 'requireLogin' => true]);
    exit();
}

// Check if user is a client (only clients can have favorites)
if (!isClient()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Seuls les acheteurs peuvent avoir des favoris']);
    exit();
}

$userId = getUserId();

// Récupérer les données (support GET et POST/JSON)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
    $action = isset($_GET['action']) ? $_GET['action'] : 'check';
} else {
    $data = json_decode(file_get_contents('php://input'), true);
    $articleId = isset($data['article_id']) ? (int)$data['article_id'] : 0;
    $action = isset($data['action']) ? $data['action'] : 'toggle';
}

if (!$articleId) {
    echo json_encode(['success' => false, 'message' => 'Article invalide', 'debug' => 'articleId=' . $articleId]);
    exit();
}

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Utilisateur non identifié']);
    exit();
}

try {
    $pdo = getConnection();
    
    // Vérifier si l'article existe
    $checkArticle = $pdo->prepare("SELECT id_article FROM article WHERE id_article = ?");
    $checkArticle->execute([$articleId]);
    if (!$checkArticle->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Article inexistant']);
        exit();
    }
    
    // Vérifier si l'article est déjà en favori
    $stmt = $pdo->prepare("SELECT id_favori FROM favoris WHERE id_client = ? AND id_article = ?");
    $stmt->execute([$userId, $articleId]);
    $favori = $stmt->fetch();
    
    if ($action === 'add' || ($action === 'toggle' && !$favori)) {
        // Ajouter aux favoris
        if (!$favori) {
            $stmt = $pdo->prepare("INSERT INTO favoris (id_client, id_article, date_ajout) VALUES (?, ?, NOW())");
            $stmt->execute([$userId, $articleId]);
            echo json_encode(['success' => true, 'isFavorite' => true, 'message' => 'Ajouté aux favoris']);
        } else {
            echo json_encode(['success' => true, 'isFavorite' => true, 'message' => 'Déjà en favoris']);
        }
    } else if ($action === 'remove' || ($action === 'toggle' && $favori)) {
        // Retirer des favoris
        if ($favori) {
            $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_client = ? AND id_article = ?");
            $stmt->execute([$userId, $articleId]);
            echo json_encode(['success' => true, 'isFavorite' => false, 'message' => 'Retiré des favoris']);
        } else {
            echo json_encode(['success' => true, 'isFavorite' => false, 'message' => 'Pas en favoris']);
        }
    } else if ($action === 'check') {
        // Vérifier si l'article est en favori
        echo json_encode(['success' => true, 'isFavorite' => (bool)$favori]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue: ' . $action]);
    }
    
} catch (PDOException $e) {
    error_log("API Favoris error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>
