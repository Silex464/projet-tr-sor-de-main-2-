<?php
/**
 * ============================================================================
 * GESTION CRÉATIONS - Upload et gestion des articles pour artisans
 * ============================================================================
 * 
 * Permet aux artisans de :
 * - Ajouter une nouvelle création
 * - Modifier une création existante
 * - Supprimer une création
 * - Gérer les images
 */

session_start();
require_once 'tresorsdemain.php';
require_once 'auth.php';

// Vérifier que l'utilisateur est un artisan connecté
requireLogin();
if (!isArtisan()) {
    setFlashMessage('Cette section est réservée aux artisans.', 'error');
    header('Location: MonCompte.php');
    exit();
}

$pdo = getConnection();
$userId = getUserId();
$message = '';
$error = '';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Catégories disponibles
$categories = [
    'bijoux' => '💍 Bijoux',
    'sculptures' => '🗿 Sculptures',
    'peintures' => 'Peintures',
    'ceramique' => '🏺 Céramique',
    'textile' => '🧵 Textile',
    'bois' => '🪵 Bois',
    'verre' => '🔮 Verre',
    'cuir' => '👜 Cuir',
    'metal' => '⚙️ Métal',
    'autres' => 'Autres'
];

// ============================================================================
// TRAITEMENT DES ACTIONS POST
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $action = $_POST['action'] ?? '';
        
        // ----- AJOUTER UNE CRÉATION -----
        if ($action === 'add') {
            $nom = sanitize($_POST['nom_article'] ?? '');
            $type = sanitize($_POST['type_objet'] ?? '');
            $categorie = $_POST['categorie'] ?? '';
            $prix = floatval($_POST['prix'] ?? 0);
            $quantite = intval($_POST['quantite'] ?? 1);
            $description = sanitize($_POST['description'] ?? '');
            $materiau = sanitize($_POST['materiau'] ?? '');
            $dimensions = sanitize($_POST['dimensions'] ?? '');
            $couleur = sanitize($_POST['couleur'] ?? '');
            $style = sanitize($_POST['style'] ?? '');
            
            // Validation
            if (empty($nom) || empty($type) || empty($categorie) || $prix <= 0 || empty($description)) {
                $error = "Veuillez remplir tous les champs obligatoires.";
            } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = "Une image est requise pour votre création.";
            } else {
                // Upload de l'image
                $uploadDir = '../assets/images/creations/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                
                if (!in_array($fileType, $allowedTypes)) {
                    $error = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
                } elseif ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $error = "L'image est trop volumineuse (max 5 Mo).";
                } else {
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $fileName = 'creation_' . $userId . '_' . time() . '.' . $extension;
                    $filePath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        $imagePath = 'assets/images/creations/' . $fileName;
                        
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO article (id_artisan, nom_article, type_objet, categorie, prix, quantite, 
                                                     description, materiau, dimensions, couleur, style, image, disponibilite, vue)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0)
                            ");
                            $stmt->execute([$userId, $nom, $type, $categorie, $prix, $quantite, 
                                            $description, $materiau, $dimensions, $couleur, $style, $imagePath]);
                            
                            setFlashMessage('Votre création a été ajoutée avec succès !', 'success');
                            header('Location: gestion_creations.php');
                            exit();
                        } catch (PDOException $e) {
                            error_log("Add creation error: " . $e->getMessage());
                            // Afficher l'erreur détaillée pour le debug
                            $error = "Erreur SQL: " . $e->getMessage();
                        }
                    } else {
                        $error = "Erreur lors de l'upload de l'image.";
                    }
                }
            }
        }
        
        // ----- MODIFIER UNE CRÉATION -----
        if ($action === 'edit' && $articleId > 0) {
            // Vérifier que l'article appartient à l'artisan
            if (!isArticleOwner($articleId)) {
                $error = "Vous n'êtes pas autorisé à modifier cet article.";
            } else {
                $nom = sanitize($_POST['nom_article'] ?? '');
                $type = sanitize($_POST['type_objet'] ?? '');
                $categorie = $_POST['categorie'] ?? '';
                $prix = floatval($_POST['prix'] ?? 0);
                $quantite = intval($_POST['quantite'] ?? 1);
                $description = sanitize($_POST['description'] ?? '');
                $materiau = sanitize($_POST['materiau'] ?? '');
                $dimensions = sanitize($_POST['dimensions'] ?? '');
                $couleur = sanitize($_POST['couleur'] ?? '');
                $style = sanitize($_POST['style'] ?? '');
                $disponibilite = isset($_POST['disponibilite']) ? 1 : 0;
                $mis_en_avant = isset($_POST['mis_en_avant']) ? 1 : 0;
                
                $imagePath = $_POST['current_image'] ?? '';
                
                // Nouvelle image ?
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../assets/images/creations/';
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $fileType = mime_content_type($_FILES['image']['tmp_name']);
                    
                    if (in_array($fileType, $allowedTypes) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                        $fileName = 'creation_' . $userId . '_' . time() . '.' . $extension;
                        $filePath = $uploadDir . $fileName;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                            // Supprimer l'ancienne image
                            if (!empty($imagePath) && file_exists('../' . $imagePath)) {
                                unlink('../' . $imagePath);
                            }
                            $imagePath = 'assets/images/creations/' . $fileName;
                        }
                    }
                }
                
                try {
                    $stmt = $pdo->prepare("
                        UPDATE article SET nom_article = ?, type_objet = ?, categorie = ?, prix = ?, 
                               quantite = ?, description = ?, materiau = ?, dimensions = ?, couleur = ?, 
                               style = ?, image = ?, disponibilite = ?, mis_en_avant = ?
                        WHERE id_article = ? AND id_artisan = ?
                    ");
                    $stmt->execute([$nom, $type, $categorie, $prix, $quantite, $description, 
                                    $materiau, $dimensions, $couleur, $style, $imagePath, 
                                    $disponibilite, $mis_en_avant, $articleId, $userId]);
                    
                    setFlashMessage('Création modifiée avec succès !', 'success');
                    header('Location: gestion_creations.php');
                    exit();
                } catch (PDOException $e) {
                    error_log("Edit creation error: " . $e->getMessage());
                    $error = "Erreur lors de la modification.";
                }
            }
        }
        
        // ----- SUPPRIMER UNE CRÉATION -----
        if ($action === 'delete' && $articleId > 0) {
            if (!isArticleOwner($articleId)) {
                $error = "Vous n'êtes pas autorisé à supprimer cet article.";
            } else {
                try {
                    // Récupérer l'image pour la supprimer
                    $stmt = $pdo->prepare("SELECT image FROM article WHERE id_article = ?");
                    $stmt->execute([$articleId]);
                    $article = $stmt->fetch();
                    
                    // Supprimer l'article
                    $stmt = $pdo->prepare("DELETE FROM article WHERE id_article = ? AND id_artisan = ?");
                    $stmt->execute([$articleId, $userId]);
                    
                    // Supprimer l'image physique
                    if ($article && !empty($article['image']) && file_exists('../' . $article['image'])) {
                        unlink('../' . $article['image']);
                    }
                    
                    setFlashMessage('Création supprimée avec succès.', 'success');
                    header('Location: gestion_creations.php');
                    exit();
                } catch (PDOException $e) {
                    error_log("Delete creation error: " . $e->getMessage());
                    $error = "Erreur lors de la suppression.";
                }
            }
        }
    }
}

// ============================================================================
// RÉCUPÉRER LES DONNÉES
// ============================================================================

// Liste des créations de l'artisan
$articles = [];
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM favoris f WHERE f.id_article = a.id_article) as nb_favoris,
               (SELECT COUNT(*) FROM commentaire c WHERE c.id_article = a.id_article AND c.statut = 'approuve') as nb_avis
        FROM article a 
        WHERE a.id_artisan = ? 
        ORDER BY a.date_ajout DESC
    ");
    $stmt->execute([$userId]);
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch articles error: " . $e->getMessage());
}

// Si mode édition, récupérer l'article
$currentArticle = null;
if ($mode === 'edit' && $articleId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM article WHERE id_article = ? AND id_artisan = ?");
        $stmt->execute([$articleId, $userId]);
        $currentArticle = $stmt->fetch();
        if (!$currentArticle) {
            header('Location: gestion_creations.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Fetch article error: " . $e->getMessage());
    }
}

// Statistiques globales
$stats = [
    'total' => count($articles),
    'vues' => array_sum(array_column($articles, 'vue')),
    'favoris' => array_sum(array_column($articles, 'nb_favoris')),
    'disponibles' => count(array_filter($articles, fn($a) => $a['disponibilite']))
];

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <title>Gestion de mes créations - Trésor de Main</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/CSS/projet.css">
    <style>
        :root {
            --primary: #8D5524;
            --primary-light: #C58F5E;
            --secondary: #3E2723;
            --bg-light: #FFF8F0;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 30px 20px; background: var(--bg-light); }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Messages */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Header page */
        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 20px; }
        .page-header h1 { color: var(--secondary); margin: 0; }
        .btn-add { background: var(--primary); color: white; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-add:hover { background: var(--primary-light); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(141,85,36,0.3); }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stat-value { font-size: 2rem; font-weight: bold; color: var(--primary); }
        .stat-label { color: #666; font-size: 0.9rem; margin-top: 5px; }
        
        /* Grille articles */
        .articles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        
        .article-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.08); transition: all 0.3s; position: relative; }
        .article-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.12); }
        .article-card img { width: 100%; height: 200px; object-fit: cover; }
        .article-card-body { padding: 20px; }
        .article-card h3 { margin: 0 0 10px; color: var(--secondary); font-size: 1.1rem; }
        .article-price { color: var(--primary); font-weight: bold; font-size: 1.2rem; }
        .article-stats { display: flex; gap: 15px; margin: 15px 0; color: #666; font-size: 0.85rem; }
        .article-stats span { display: flex; align-items: center; gap: 5px; }
        .article-actions { display: flex; gap: 10px; margin-top: 15px; }
        .btn-edit, .btn-delete { padding: 8px 15px; border-radius: 8px; font-size: 0.9rem; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-edit { background: var(--primary); color: white; text-decoration: none; }
        .btn-edit:hover { background: var(--primary-light); }
        .btn-delete { background: var(--danger); color: white; }
        .btn-delete:hover { background: #c82333; }
        
        /* Badges */
        .badge { position: absolute; top: 10px; right: 10px; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-featured { background: var(--warning); color: #333; }
        .badge-unavailable { background: #6c757d; color: white; }
        
        /* Formulaire */
        .form-card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; }
        .form-card h2 { color: var(--primary); margin-bottom: 30px; text-align: center; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { display: block; margin-bottom: 8px; color: var(--secondary); font-weight: 600; }
        .form-group label .required { color: var(--danger); }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 12px 15px; border: 2px solid #E6E2DD; border-radius: 10px; 
            font-size: 1rem; transition: all 0.3s; 
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
            border-color: var(--primary); outline: none; box-shadow: 0 0 0 3px rgba(141,85,36,0.1); 
        }
        .form-group textarea { min-height: 120px; resize: vertical; }
        
        /* Image upload */
        .image-upload { border: 2px dashed #ccc; border-radius: 15px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .image-upload:hover { border-color: var(--primary); background: rgba(141,85,36,0.05); }
        .image-upload.has-image { border-style: solid; border-color: var(--success); }
        .image-preview { max-width: 200px; max-height: 200px; border-radius: 10px; margin-top: 15px; }
        
        /* Checkboxes */
        .checkbox-group { display: flex; gap: 20px; flex-wrap: wrap; }
        .checkbox-item { display: flex; align-items: center; gap: 8px; }
        .checkbox-item input { width: auto; }
        
        /* Buttons */
        .form-actions { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        .btn-submit { background: var(--primary); color: white; padding: 15px 40px; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-cancel { background: #6c757d; color: white; padding: 15px 40px; border: none; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; }
        
        /* Empty state */
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 20px; }
        .empty-state h3 { color: var(--secondary); margin-bottom: 15px; }
        .empty-state p { color: #666; margin-bottom: 25px; }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <div class="container">
                <?php displayFlashMessage(); ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($mode === 'list'): ?>
                <!-- ============ MODE LISTE ============ -->
                <div class="page-header">
                    <h1>Mes Créations</h1>
                    <a href="?mode=add" class="btn-add">➕ Ajouter une création</a>
                </div>
                
                <!-- Statistiques -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total'] ?></div>
                        <div class="stat-label">Créations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['vues'] ?></div>
                        <div class="stat-label">Vues totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['favoris'] ?></div>
                        <div class="stat-label">En favoris</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['disponibles'] ?></div>
                        <div class="stat-label">Disponibles</div>
                    </div>
                </div>
                
                <?php if (empty($articles)): ?>
                <div class="empty-state">
                    <h3>Vous n'avez pas encore de créations</h3>
                    <p>Commencez à partager votre travail avec le monde !</p>
                    <a href="?mode=add" class="btn-add">➕ Ajouter ma première création</a>
                </div>
                <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                    <div class="article-card">
                        <?php if ($article['mis_en_avant']): ?>
                            <span class="badge badge-featured">Mis en avant</span>
                        <?php elseif (!$article['disponibilite']): ?>
                            <span class="badge badge-unavailable">Indisponible</span>
                        <?php endif; ?>
                        
                        <img src="../<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['nom_article']) ?>">
                        
                        <div class="article-card-body">
                            <h3><?= htmlspecialchars($article['nom_article']) ?></h3>
                            <div class="article-price"><?= number_format($article['prix'], 2, ',', ' ') ?> €</div>
                            
                            <div class="article-stats">
                                <span><?= $article['vue'] ?> vues</span>
                                <span><?= $article['nb_favoris'] ?> favoris</span>
                                <span><?= $article['nb_avis'] ?> avis</span>
                            </div>
                            
                            <div class="article-actions">
                                <a href="?mode=edit&id=<?= $article['id_article'] ?>" class="btn-edit">Modifier</a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer cette création ?');">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $article['id_article'] ?>">
                                    <button type="submit" class="btn-delete" name="delete" value="<?= $article['id_article'] ?>">X</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php elseif ($mode === 'add' || $mode === 'edit'): ?>
                <!-- ============ MODE FORMULAIRE ============ -->
                <div class="form-card">
                    <h2><?= $mode === 'add' ? 'Ajouter une création' : 'Modifier la création' ?></h2>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="<?= $mode ?>">
                        <?php if ($currentArticle): ?>
                            <input type="hidden" name="current_image" value="<?= htmlspecialchars($currentArticle['image']) ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Nom de la création <span class="required">*</span></label>
                                <input type="text" name="nom_article" value="<?= htmlspecialchars($currentArticle['nom_article'] ?? '') ?>" required placeholder="Ex: Vase en céramique bleu">
                            </div>
                            
                            <div class="form-group">
                                <label>Type d'objet <span class="required">*</span></label>
                                <input type="text" name="type_objet" value="<?= htmlspecialchars($currentArticle['type_objet'] ?? '') ?>" required placeholder="Ex: Vase décoratif">
                            </div>
                            
                            <div class="form-group">
                                <label>Catégorie <span class="required">*</span></label>
                                <select name="categorie" required>
                                    <option value="">-- Choisir une catégorie --</option>
                                    <?php foreach ($categories as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($currentArticle['categorie'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Prix (€) <span class="required">*</span></label>
                                <input type="number" name="prix" step="0.01" min="0.01" value="<?= htmlspecialchars($currentArticle['prix'] ?? '') ?>" required placeholder="Ex: 45.00">
                            </div>
                            
                            <div class="form-group">
                                <label>Quantité disponible</label>
                                <input type="number" name="quantite" min="0" value="<?= htmlspecialchars($currentArticle['quantite'] ?? 1) ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Matériau</label>
                                <input type="text" name="materiau" value="<?= htmlspecialchars($currentArticle['materiau'] ?? '') ?>" placeholder="Ex: Céramique, bois, métal...">
                            </div>
                            
                            <div class="form-group">
                                <label>Dimensions</label>
                                <input type="text" name="dimensions" value="<?= htmlspecialchars($currentArticle['dimensions'] ?? '') ?>" placeholder="Ex: 20cm x 15cm x 10cm">
                            </div>
                            
                            <div class="form-group">
                                <label>Couleur principale</label>
                                <input type="text" name="couleur" value="<?= htmlspecialchars($currentArticle['couleur'] ?? '') ?>" placeholder="Ex: Bleu cobalt">
                            </div>
                            
                            <div class="form-group">
                                <label>Style</label>
                                <input type="text" name="style" value="<?= htmlspecialchars($currentArticle['style'] ?? '') ?>" placeholder="Ex: Moderne, rustique, bohème...">
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Description <span class="required">*</span></label>
                                <textarea name="description" required placeholder="Décrivez votre création en détail..."><?= htmlspecialchars($currentArticle['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Image principale <?= $mode === 'add' ? '<span class="required">*</span>' : '(laisser vide pour garder l\'actuelle)' ?></label>
                                <div class="image-upload <?= $currentArticle ? 'has-image' : '' ?>" onclick="document.getElementById('image-input').click();">
                                    <p>Cliquez pour sélectionner une image</p>
                                    <p style="font-size: 0.85rem; color: #666;">JPG, PNG, GIF ou WEBP (max 5 Mo)</p>
                                    <?php if ($currentArticle && $currentArticle['image']): ?>
                                        <img src="../<?= htmlspecialchars($currentArticle['image']) ?>" class="image-preview" id="preview">
                                    <?php else: ?>
                                        <img src="" class="image-preview" id="preview" style="display:none;">
                                    <?php endif; ?>
                                </div>
                                <input type="file" name="image" id="image-input" accept="image/*" style="display:none;" <?= $mode === 'add' ? 'required' : '' ?> onchange="previewImage(this)">
                            </div>
                            
                            <?php if ($mode === 'edit'): ?>
                            <div class="form-group full-width">
                                <div class="checkbox-group">
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="disponibilite" <?= ($currentArticle['disponibilite'] ?? 1) ? 'checked' : '' ?>>
                                        Disponible à la vente
                                    </label>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="mis_en_avant" <?= ($currentArticle['mis_en_avant'] ?? 0) ? 'checked' : '' ?>>
                                        Mettre en avant sur mon profil
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-submit"><?= $mode === 'add' ? 'Publier ma création' : 'Enregistrer les modifications' ?></button>
                            <a href="gestion_creations.php" class="btn-cancel">Annuler</a>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const uploadDiv = input.closest('.form-group').querySelector('.image-upload');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    uploadDiv.classList.add('has-image');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
    <script src="../JavaScript/script.js"></script>
</body>
</html>
