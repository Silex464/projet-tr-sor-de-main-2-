<?php
/**
 * ============================================================================
 * MON COMPTE - Page de profil avec distinction Artisan / Client
 * ============================================================================
 * 
 * Artisan : Dashboard avec statistiques, gestion des créations, performances
 * Client  : Tableau de bord favoris, historique, suggestions
 */

session_start();
require_once 'tresorsdemain.php';
require_once 'auth.php';

// Redirection si non connecté
requireLogin();

$pdo = getConnection();
$userId = getUserId();

// Récupérer les infos utilisateur complètes
$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$isArtisan = ($user['type_compte'] === 'artisan');
$message = '';

// ============================================================================
// RÉCUPÉRATION DES DONNÉES SELON LE TYPE DE COMPTE
// ============================================================================

if ($isArtisan) {
    // --- DONNÉES ARTISAN ---
    
    // Mes créations récentes
    $stmt = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM favoris f WHERE f.id_article = a.id_article) as nb_favoris
        FROM article a 
        WHERE a.id_artisan = ? 
        ORDER BY a.date_ajout DESC 
        LIMIT 6
    ");
    $stmt->execute([$userId]);
    $mesCreations = $stmt->fetchAll();
    
    // Statistiques globales
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_creations,
        SUM(vue) as total_vues,
        SUM(quantite) as stock_total
        FROM article WHERE id_artisan = ?");
    $stmt->execute([$userId]);
    $stats = $stmt->fetch();
    
    // Total favoris
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM favoris f 
        JOIN article a ON f.id_article = a.id_article 
        WHERE a.id_artisan = ?
    ");
    $stmt->execute([$userId]);
    $totalFavoris = $stmt->fetch()['total'];
    
    // Nombre d'avis
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM commentaire c 
        JOIN article a ON c.id_article = a.id_article 
        WHERE a.id_artisan = ? AND c.statut = 'approuve'
    ");
    $stmt->execute([$userId]);
    $totalAvis = $stmt->fetch()['total'];
    
} else {
    // --- DONNÉES CLIENT ---
    
    // Mes favoris récents
    $stmt = $pdo->prepare("
        SELECT a.*, f.date_ajout as date_favori,
               u.nom as artisan_nom, u.prenom as artisan_prenom
        FROM favoris f
        JOIN article a ON f.id_article = a.id_article
        JOIN utilisateurs u ON a.id_artisan = u.id
        WHERE f.id_client = ?
        ORDER BY f.date_ajout DESC
        LIMIT 6
    ");
    $stmt->execute([$userId]);
    $mesFavoris = $stmt->fetchAll();
    
    // Nombre total de favoris
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM favoris WHERE id_client = ?");
    $stmt->execute([$userId]);
    $totalFavoris = $stmt->fetch()['total'];
    
    // Suggestions (articles populaires non dans favoris)
    $stmt = $pdo->prepare("
        SELECT a.*, u.prenom as artisan_prenom, u.nom as artisan_nom,
               (SELECT COUNT(*) FROM favoris WHERE id_article = a.id_article) as nb_favoris
        FROM article a
        JOIN utilisateurs u ON a.id_artisan = u.id
        WHERE a.id_article NOT IN (SELECT id_article FROM favoris WHERE id_client = ?)
        AND a.disponibilite = 1
        ORDER BY a.vue DESC, a.date_ajout DESC
        LIMIT 4
    ");
    $stmt->execute([$userId]);
    $suggestions = $stmt->fetchAll();
}

// ============================================================================
// TRAITEMENT DES FORMULAIRES
// ============================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    
    // Upload de photo de profil
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = mime_content_type($_FILES['photo_profil']['tmp_name']);
        
        if (in_array($fileType, $allowed) && $_FILES['photo_profil']['size'] <= 2 * 1024 * 1024) {
            $uploadDir = '../assets/images/profils/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['photo_profil']['name'], PATHINFO_EXTENSION);
            $fileName = 'user_' . $userId . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['photo_profil']['tmp_name'], $filePath)) {
                $imagePath = 'assets/images/profils/' . $fileName;
                $stmt = $pdo->prepare("UPDATE utilisateurs SET photo_profil = ? WHERE id = ?");
                $stmt->execute([$imagePath, $userId]);
                $user['photo_profil'] = $imagePath;
                setFlashMessage('Photo de profil mise à jour !', 'success');
            }
        } else {
            setFlashMessage('Format ou taille de fichier invalide (max 2 Mo).', 'error');
        }
    }
    
    // Mise à jour rapide description
    if (isset($_POST['description'])) {
        $description = sanitize($_POST['description']);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET description = ? WHERE id = ?");
        $stmt->execute([$description, $userId]);
        $user['description'] = $description;
        setFlashMessage('Description mise à jour !', 'success');
    }
    
    header('Location: MonCompte.php');
    exit();
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isArtisan ? 'Mon Atelier' : 'Mon Espace' ?> - Trésor de Main</title>
    <link rel="stylesheet" href="/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/CSS/projet.css">
    <style>
        :root {
            --primary: #8D5524;
            --primary-light: #C58F5E;
            --secondary: #3E2723;
            --bg-light: #FFF8F0;
            --artisan-accent: #D4A574;
            --client-accent: #7B9E89;
            --success: #28a745;
            --heart-red: #E74C3C;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 30px 20px; background: var(--bg-light); }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* ===== HEADER PROFIL ===== */
        .profile-header {
            background: linear-gradient(135deg, <?= $isArtisan ? 'var(--artisan-accent)' : 'var(--client-accent)' ?> 0%, var(--primary) 100%);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="white" stroke-width="0.5" opacity="0.1"/></svg>');
            animation: rotate 60s linear infinite;
        }
        
        @keyframes rotate { to { transform: rotate(360deg); } }
        
        .profile-content { display: flex; gap: 30px; align-items: center; position: relative; z-index: 1; flex-wrap: wrap; }
        
        .profile-pic-wrapper { position: relative; }
        .profile-pic {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .upload-photo-btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: white;
            color: var(--primary);
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .upload-photo-btn:hover { transform: scale(1.1); }
        
        .profile-info { flex: 1; min-width: 250px; }
        .profile-info h1 { font-size: 2rem; margin: 0 0 5px; }
        .profile-info .badge-type {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .profile-info .location { opacity: 0.9; margin-bottom: 15px; }
        
        .profile-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-profile {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }
        .btn-profile-primary { background: white; color: var(--primary); }
        .btn-profile-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-profile-secondary { background: rgba(255,255,255,0.2); color: white; }
        .btn-profile-secondary:hover { background: rgba(255,255,255,0.3); }
        
        /* ===== STATS CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        .stat-icon { font-size: 2rem; margin-bottom: 10px; }
        .stat-value { font-size: 2.2rem; font-weight: bold; color: var(--primary); }
        .stat-label { color: #666; font-size: 0.9rem; margin-top: 5px; }
        
        /* ===== SECTIONS ===== */
        .section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h2 { color: var(--secondary); margin: 0; font-size: 1.4rem; }
        
        .btn-action {
            background: var(--primary);
            color: white;
            padding: 10px 25px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-action:hover { background: var(--primary-light); transform: translateY(-2px); }
        
        /* ===== GRILLE ARTICLES/FAVORIS ===== */
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .item-card {
            background: #FAFAFA;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #EEE;
        }
        .item-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .item-card img { width: 100%; height: 180px; object-fit: cover; }
        
        .item-card-body { padding: 15px; }
        .item-card h3 { margin: 0 0 8px; font-size: 1rem; color: var(--secondary); }
        .item-card h3 a { color: inherit; text-decoration: none; }
        .item-card h3 a:hover { color: var(--primary); }
        
        .item-price { color: var(--primary); font-weight: bold; font-size: 1.1rem; }
        
        .item-stats { display: flex; gap: 12px; color: #888; font-size: 0.85rem; margin-top: 10px; }
        .item-stats span { display: flex; align-items: center; gap: 4px; }
        
        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        .empty-state .icon { font-size: 4rem; margin-bottom: 15px; opacity: 0.5; }
        .empty-state h3 { color: var(--secondary); margin-bottom: 10px; }
        
        /* ===== DESCRIPTION ===== */
        .description-section { margin-top: 20px; }
        .description-section textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #E6E2DD;
            border-radius: 12px;
            font-size: 1rem;
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
            box-sizing: border-box;
        }
        .description-section textarea:focus { border-color: var(--primary); outline: none; }
        
        .description-actions { margin-top: 15px; text-align: right; }
        
        /* ===== QUICK LINKS ===== */
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            background: #F5F5F5;
            border-radius: 12px;
            text-decoration: none;
            color: var(--secondary);
            transition: all 0.3s;
        }
        .quick-link:hover { background: var(--primary); color: white; transform: translateX(5px); }
        .quick-link .icon { font-size: 1.5rem; }
        
        /* Alert messages */
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        
        /* Hidden file input */
        .hidden-input { display: none; }
        
        @media (max-width: 768px) {
            .profile-content { flex-direction: column; text-align: center; }
            .profile-actions { justify-content: center; }
            .section-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <div class="container">
                <?php displayFlashMessage(); ?>
                
                <!-- ===== HEADER PROFIL ===== -->
                <div class="profile-header">
                    <div class="profile-content">
                        <form method="POST" enctype="multipart/form-data" class="profile-pic-wrapper">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <img src="<?= !empty($user['photo_profil']) ? '../' . htmlspecialchars($user['photo_profil']) : '../assets/images/default-avatar.svg' ?>" 
                                 alt="Photo de profil" class="profile-pic">
                            <label class="upload-photo-btn" title="Changer la photo">
                                +
                                <input type="file" name="photo_profil" class="hidden-input" accept="image/*" onchange="this.form.submit()">
                            </label>
                        </form>
                        
                        <div class="profile-info">
                            <span class="badge-type"><?= $isArtisan ? 'Artisan' : 'Visiteur' ?></span>
                            <h1><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h1>
                            <?php if (!empty($user['ville'])): ?>
                                <p class="location"><?= htmlspecialchars($user['ville']) ?></p>
                            <?php endif; ?>
                            
                            <div class="profile-actions">
                                <a href="edit_profile.php" class="btn-profile btn-profile-primary">✏️ Modifier le profil</a>
                                <?php if ($isArtisan): ?>
                                    <a href="gestion_creations.php?mode=add" class="btn-profile btn-profile-secondary">➕ Nouvelle création</a>
                                <?php else: ?>
                                    <a href="mes_favoris.php" class="btn-profile btn-profile-secondary">Mes favoris</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($isArtisan): ?>
                <!-- ============================================================ -->
                <!-- ==================== DASHBOARD ARTISAN ==================== -->
                <!-- ============================================================ -->
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_creations'] ?? 0 ?></div>
                        <div class="stat-label">Créations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $stats['total_vues'] ?? 0 ?></div>
                        <div class="stat-label">Vues totales</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $totalFavoris ?? 0 ?></div>
                        <div class="stat-label">En favoris</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= $totalAvis ?? 0 ?></div>
                        <div class="stat-label">Avis reçus</div>
                    </div>
                </div>
                
                <!-- Mes Créations -->
                <div class="section">
                    <div class="section-header">
                        <h2>Mes Créations</h2>
                        <a href="gestion_creations.php" class="btn-action">Gérer toutes mes créations →</a>
                    </div>
                    
                    <?php if (!empty($mesCreations)): ?>
                    <div class="items-grid">
                        <?php foreach ($mesCreations as $article): ?>
                        <div class="item-card">
                            <img src="../<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['nom_article']) ?>">
                            <div class="item-card-body">
                                <h3><a href="produit_detail.php?id=<?= $article['id_article'] ?>"><?= htmlspecialchars($article['nom_article']) ?></a></h3>
                                <div class="item-price"><?= number_format($article['prix'], 2, ',', ' ') ?> €</div>
                                <div class="item-stats">
                                    <span><?= $article['vue'] ?> vues</span>
                                    <span><?= $article['nb_favoris'] ?> favoris</span>
                                    <span><?= $article['disponibilite'] ? 'Dispo' : 'Indispo' ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <h3>Aucune création pour le moment</h3>
                        <p>Commencez à partager votre talent avec le monde !</p>
                        <a href="gestion_creations.php?mode=add" class="btn-action">Créer ma première œuvre</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Liens rapides artisan -->
                <div class="section">
                    <h2>Accès rapides</h2>
                    <div class="quick-links">
                        <a href="gestion_creations.php?mode=add" class="quick-link">
                            <span>Ajouter une création</span>
                        </a>
                        <a href="gestion_creations.php" class="quick-link">
                            <span>Gérer mes créations</span>
                        </a>
                        <a href="evenements.php" class="quick-link">
                            <span>Voir les événements</span>
                        </a>
                        <a href="edit_profile.php" class="quick-link">
                            <span>Paramètres du compte</span>
                        </a>
                    </div>
                </div>

                <?php else: ?>
                <!-- ============================================================ -->
                <!-- ==================== DASHBOARD CLIENT ===================== -->
                <!-- ============================================================ -->
                
                <!-- Stats client -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?= $totalFavoris ?></div>
                        <div class="stat-label">Favoris</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?= count($suggestions) ?>+</div>
                        <div class="stat-label">À découvrir</div>
                    </div>
                </div>
                
                <!-- Mes Favoris -->
                <div class="section">
                    <div class="section-header">
                        <h2>Mes Favoris</h2>
                        <a href="mes_favoris.php" class="btn-action">Voir tous mes favoris →</a>
                    </div>
                    
                    <?php if (!empty($mesFavoris)): ?>
                    <div class="items-grid">
                        <?php foreach ($mesFavoris as $fav): ?>
                        <div class="item-card">
                            <img src="../<?= htmlspecialchars($fav['image']) ?>" alt="<?= htmlspecialchars($fav['nom_article']) ?>">
                            <div class="item-card-body">
                                <h3><a href="produit_detail.php?id=<?= $fav['id_article'] ?>"><?= htmlspecialchars($fav['nom_article']) ?></a></h3>
                                <p style="color:#666; font-size:0.85rem; margin:5px 0;">
                                    Par <?= htmlspecialchars($fav['artisan_prenom'] . ' ' . $fav['artisan_nom']) ?>
                                </p>
                                <div class="item-price"><?= number_format($fav['prix'], 2, ',', ' ') ?> €</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <h3>Pas encore de favoris</h3>
                        <p>Explorez les créations de nos artisans et ajoutez-les à vos favoris !</p>
                        <a href="All_Products.php" class="btn-action">Explorer les créations</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Suggestions -->
                <?php if (!empty($suggestions)): ?>
                <div class="section">
                    <div class="section-header">
                        <h2>Vous pourriez aimer</h2>
                        <a href="All_Products.php" class="btn-action">Voir plus →</a>
                    </div>
                    
                    <div class="items-grid">
                        <?php foreach ($suggestions as $sugg): ?>
                        <div class="item-card">
                            <img src="../<?= htmlspecialchars($sugg['image']) ?>" alt="<?= htmlspecialchars($sugg['nom_article']) ?>">
                            <div class="item-card-body">
                                <h3><a href="produit_detail.php?id=<?= $sugg['id_article'] ?>"><?= htmlspecialchars($sugg['nom_article']) ?></a></h3>
                                <p style="color:#666; font-size:0.85rem; margin:5px 0;">
                                    Par <?= htmlspecialchars($sugg['artisan_prenom'] . ' ' . $sugg['artisan_nom']) ?>
                                </p>
                                <div class="item-price"><?= number_format($sugg['prix'], 2, ',', ' ') ?> €</div>
                                <div class="item-stats">
                                    <span><?= $sugg['vue'] ?> vues</span>
                                    <span><?= $sugg['nb_favoris'] ?> favoris</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Liens rapides client -->
                <div class="section">
                    <h2>Accès rapides</h2>
                    <div class="quick-links">
                        <a href="mes_favoris.php" class="quick-link">
                            <span>Mes favoris</span>
                        </a>
                        <a href="All_Products.php" class="quick-link">
                            <span>Toutes les créations</span>
                        </a>
                        <a href="evenements.php" class="quick-link">
                            <span>Événements à venir</span>
                        </a>
                        <a href="edit_profile.php" class="quick-link">
                            <span>Paramètres du compte</span>
                        </a>
                    </div>
                </div>
                
                <?php endif; ?>
                
                <!-- Description commune -->
                <div class="section">
                    <h2>Ma description</h2>
                    <form method="POST" class="description-section">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <textarea name="description" placeholder="Parlez de vous..."><?= htmlspecialchars($user['description'] ?? '') ?></textarea>
                        <div class="description-actions">
                            <button type="submit" class="btn-action">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>
    
    <script src="../JavaScript/script.js"></script>
</body>
</html>
