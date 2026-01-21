<?php
/**
 * ============================================================================
 * MES FAVORIS - Page de gestion des favoris pour les acheteurs
 * ============================================================================
 * 
 * Permet aux clients de :
 * - Voir tous leurs articles favoris
 * - Retirer des articles des favoris
 * - Accéder aux fiches produits
 */

session_start();
require_once 'tresorsdemain.php';
require_once 'auth.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

$pdo = getConnection();
$userId = getUserId();
$message = '';

// ============================================================================
// TRAITEMENT : Supprimer un favori
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favori'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $articleId = (int)$_POST['article_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_client = ? AND id_article = ?");
            $stmt->execute([$userId, $articleId]);
            setFlashMessage('Article retiré de vos favoris.', 'success');
        } catch (PDOException $e) {
            error_log("Remove favorite error: " . $e->getMessage());
        }
    }
    header('Location: mes_favoris.php');
    exit();
}

// ============================================================================
// RÉCUPÉRATION DES FAVORIS
// ============================================================================

$favoris = [];
$totalValue = 0;

try {
    $stmt = $pdo->prepare("
        SELECT a.*, f.date_ajout as date_favori,
               u.nom as artisan_nom, u.prenom as artisan_prenom
        FROM favoris f
        JOIN article a ON f.id_article = a.id_article
        JOIN utilisateurs u ON a.id_artisan = u.id
        WHERE f.id_client = ?
        ORDER BY f.date_ajout DESC
    ");
    $stmt->execute([$userId]);
    $favoris = $stmt->fetchAll();
    
    // Calculer la valeur totale
    foreach ($favoris as $fav) {
        $totalValue += $fav['prix'];
    }
} catch (PDOException $e) {
    error_log("Fetch favorites error: " . $e->getMessage());
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <title>Mes Favoris - Trésor de Main</title>
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
            --heart-red: #E74C3C;
            --success: #28a745;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 30px 20px; background: var(--bg-light); }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .page-header { text-align: center; margin-bottom: 40px; }
        .page-header h1 { color: var(--secondary); font-size: 2.5rem; margin-bottom: 10px; }
        .page-header p { color: #666; font-size: 1.1rem; }
        
        /* Stats */
        .favoris-stats { display: flex; justify-content: center; gap: 40px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-item { background: white; padding: 20px 40px; border-radius: 15px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .stat-value { font-size: 2rem; font-weight: bold; color: var(--primary); }
        .stat-label { color: #666; font-size: 0.9rem; }
        
        /* Grid */
        .favoris-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        
        /* Card */
        .favori-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.3s; position: relative; }
        .favori-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        
        .favori-card img { width: 100%; height: 220px; object-fit: cover; }
        
        .favori-card-body { padding: 20px; }
        .favori-card h3 { margin: 0 0 8px; font-size: 1.2rem; color: var(--secondary); }
        .favori-card h3 a { color: inherit; text-decoration: none; transition: color 0.3s; }
        .favori-card h3 a:hover { color: var(--primary); }
        
        .artisan-link { color: #666; font-size: 0.9rem; text-decoration: none; display: block; margin-bottom: 10px; }
        .artisan-link:hover { color: var(--primary); }
        
        .favori-price { font-size: 1.4rem; font-weight: bold; color: var(--primary); margin-bottom: 15px; }
        
        .favori-meta { display: flex; gap: 15px; color: #888; font-size: 0.85rem; margin-bottom: 15px; }
        .favori-meta span { display: flex; align-items: center; gap: 5px; }
        
        .favori-actions { display: flex; gap: 10px; }
        .btn-view { flex: 1; background: var(--primary); color: white; padding: 12px; border-radius: 10px; text-decoration: none; text-align: center; font-weight: 600; transition: all 0.3s; }
        .btn-view:hover { background: var(--primary-light); }
        
        .btn-remove { background: transparent; border: 2px solid var(--heart-red); color: var(--heart-red); padding: 12px 15px; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .btn-remove:hover { background: var(--heart-red); color: white; }
        
        /* Badge dispo */
        .badge-dispo { position: absolute; top: 15px; left: 15px; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-dispo.available { background: var(--success); color: white; }
        .badge-dispo.unavailable { background: #6c757d; color: white; }
        
        /* Date ajout */
        .date-ajout { position: absolute; top: 15px; right: 15px; background: rgba(0,0,0,0.7); color: white; padding: 5px 10px; border-radius: 15px; font-size: 0.75rem; }
        
        /* Empty state */
        .empty-state { text-align: center; padding: 80px 20px; background: white; border-radius: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .empty-state .icon { font-size: 5rem; margin-bottom: 20px; }
        .empty-state h2 { color: var(--secondary); margin-bottom: 15px; }
        .empty-state p { color: #666; margin-bottom: 30px; max-width: 400px; margin-left: auto; margin-right: auto; }
        .btn-explore { display: inline-flex; align-items: center; gap: 10px; background: var(--primary); color: white; padding: 15px 35px; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 1.1rem; transition: all 0.3s; }
        .btn-explore:hover { background: var(--primary-light); transform: translateY(-3px); box-shadow: 0 10px 25px rgba(141,85,36,0.3); }
        
        /* Animation coeur */
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        .heart-icon { display: inline-block; }
        .btn-remove:hover .heart-icon { animation: heartBeat 0.6s ease-in-out; }
        
        /* Alert */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        
        @media (max-width: 768px) {
            .favoris-stats { gap: 15px; }
            .stat-item { padding: 15px 25px; }
            .page-header h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <div class="container">
                <?php displayFlashMessage(); ?>
                
                <div class="page-header">
                    <h1>Mes Favoris</h1>
                    <p>Les créations que vous avez sauvegardées pour plus tard</p>
                </div>
                
                <?php if (!empty($favoris)): ?>
                
                <!-- Stats -->
                <div class="favoris-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= count($favoris) ?></div>
                        <div class="stat-label">Créations sauvegardées</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalValue, 0, ',', ' ') ?> €</div>
                        <div class="stat-label">Valeur totale</div>
                    </div>
                </div>
                
                <!-- Grid -->
                <div class="favoris-grid">
                    <?php foreach ($favoris as $fav): ?>
                    <div class="favori-card">
                        <span class="badge-dispo <?= $fav['disponibilite'] ? 'available' : 'unavailable' ?>">
                            <?= $fav['disponibilite'] ? 'Disponible' : 'Indisponible' ?>
                        </span>
                        
                        <span class="date-ajout">
                            Ajouté le <?= date('d/m/Y', strtotime($fav['date_favori'])) ?>
                        </span>
                        
                        <img src="../<?= htmlspecialchars($fav['image']) ?>" alt="<?= htmlspecialchars($fav['nom_article']) ?>">
                        
                        <div class="favori-card-body">
                            <h3><a href="produit_detail.php?id=<?= $fav['id_article'] ?>"><?= htmlspecialchars($fav['nom_article']) ?></a></h3>
                            
                            <a href="#" class="artisan-link">
                                👤 <?= htmlspecialchars($fav['artisan_prenom'] . ' ' . $fav['artisan_nom']) ?>
                            </a>
                            
                            <div class="favori-price"><?= number_format($fav['prix'], 2, ',', ' ') ?> €</div>
                            
                            <div class="favori-meta">
                                <span>📂 <?= ucfirst(htmlspecialchars($fav['categorie'])) ?></span>
                                <span><?= $fav['vue'] ?> vues</span>
                            </div>
                            
                            <div class="favori-actions">
                                <a href="produit_detail.php?id=<?= $fav['id_article'] ?>" class="btn-view">
                                    Voir le détail
                                </a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <input type="hidden" name="article_id" value="<?= $fav['id_article'] ?>">
                                    <button type="submit" name="remove_favori" class="btn-remove" title="Retirer des favoris">
                                        Retirer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php else: ?>
                
                <!-- Empty State -->
                <div class="empty-state">
                    <h2>Votre liste de favoris est vide</h2>
                    <p>Parcourez les créations de nos artisans et cliquez sur le coeur pour les ajouter à vos favoris.</p>
                    <a href="All_Products.php" class="btn-explore">
                        Explorer les créations
                    </a>
                </div>
                
                <?php endif; ?>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>
    
    <script src="../JavaScript/script.js"></script>
</body>
</html>
