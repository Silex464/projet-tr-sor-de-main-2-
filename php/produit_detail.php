<?php
/**
 * ============================================================================
 * PRODUIT DETAIL - Page de détail d'un article
 * ============================================================================
 */

// Include centralized auth system (handles session)
require_once 'config.php';
require_once 'auth.php';

// Get product ID from URL
$articleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$articleId) {
    header('Location: All_Products.php');
    exit();
}

// Use auth functions for user state
$isLoggedIn = isLoggedIn();
$isClientUser = isClient();
$userId = getUserId();

try {
    $pdo = getConnection();
    
    // Fetch article with artisan info including social links and verification badge
    // LEFT JOIN pour afficher l'article même si l'artisan a été supprimé
    $stmt = $pdo->prepare("
        SELECT a.*, 
               COALESCE(u.id, a.id_artisan) AS artisan_id, 
               COALESCE(u.prenom, 'Artisan') AS artisan_prenom, 
               COALESCE(u.nom, 'Inconnu') AS artisan_nom, 
               u.description AS artisan_desc, 
               COALESCE(u.ville, '') AS artisan_ville, 
               u.photo_profil AS artisan_photo,
               u.specialite AS artisan_specialite, 
               u.site_web AS artisan_site, 
               u.instagram AS artisan_instagram, 
               u.facebook AS artisan_facebook,
               COALESCE(u.badge_verifie, 0) AS artisan_verifie
        FROM article a
        LEFT JOIN utilisateurs u ON a.id_artisan = u.id
        WHERE a.id_article = ?
    ");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: All_Products.php');
        exit();
    }
    
    // Increment view counter
    $pdo->prepare("UPDATE article SET vue = vue + 1 WHERE id_article = ?")->execute([$articleId]);
    
    // Check if article is in favorites (only for clients)
    $isFavorite = false;
    if ($isClientUser && $userId) {
        $favStmt = $pdo->prepare("SELECT 1 FROM favoris WHERE id_client = ? AND id_article = ?");
        $favStmt->execute([$userId, $articleId]);
        $isFavorite = (bool)$favStmt->fetch();
    }
    
    // Récupérer les commentaires
    $commStmt = $pdo->prepare("
        SELECT c.*, u.prenom, u.nom, u.photo_profil
        FROM commentaire c
        JOIN utilisateurs u ON c.id_client = u.id
        WHERE c.id_article = ? AND c.statut = 'approuve'
        ORDER BY c.date DESC
    ");
    $commStmt->execute([$articleId]);
    $commentaires = $commStmt->fetchAll();
    
    // Récupérer d'autres articles du même artisan
    $autresStmt = $pdo->prepare("
        SELECT id_article, nom_article, prix, image
        FROM article 
        WHERE id_artisan = ? AND id_article != ? AND disponibilite = 1
        LIMIT 4
    ");
    $autresStmt->execute([$article['artisan_id'], $articleId]);
    $autresArticles = $autresStmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Produit detail error: " . $e->getMessage());
    die("Une erreur est survenue.");
}

$imagePath = !empty($article['image']) ? '../' . $article['image'] : 'https://via.placeholder.com/600x400/8D5524/FFFFFF?text=Image';
$artisanPhoto = !empty($article['artisan_photo']) ? '../' . $article['artisan_photo'] : '../assets/images/default-avatar.svg';
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <title><?= htmlspecialchars($article['nom_article']) ?> - Trésor de Main</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars(substr($article['description'], 0, 160)) ?>">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&display=swap" rel="stylesheet">
    <link rel="icon" href="../favicon.ico">
    <style>
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 40px 20px; max-width: 1200px; margin: 0 auto; }
        
        /* Fil d'Ariane */
        .breadcrumb { margin-bottom: 30px; color: #666; }
        .breadcrumb a { color: #8D5524; text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }
        
        /* Section produit */
        .product-section { display: grid; grid-template-columns: 1fr 1fr; gap: 50px; margin-bottom: 50px; }
        @media (max-width: 768px) { .product-section { grid-template-columns: 1fr; } }
        
        /* Image produit */
        .product-image { position: relative; }
        .product-image img { width: 100%; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
        .badge-categorie {
            position: absolute; top: 20px; left: 20px;
            background: rgba(141,85,36,0.9); color: white;
            padding: 8px 16px; border-radius: 25px; font-size: 0.85rem; text-transform: uppercase;
        }
        .btn-favori-large {
            position: absolute; top: 20px; right: 20px;
            background: white; border: none; width: 50px; height: 50px;
            border-radius: 50%; cursor: pointer; font-size: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2); transition: all 0.3s;
        }
        .btn-favori-large:hover { transform: scale(1.1); }
        .btn-favori-large.active { background: #ff6b6b; }
        
        /* Info produit */
        .product-info h1 { color: #3E2723; margin-bottom: 15px; font-size: 2rem; }
        .product-price { color: #8D5524; font-size: 2.5rem; font-weight: bold; margin: 20px 0; }
        .product-desc { color: #666; line-height: 1.8; margin: 20px 0; }
        .product-details { background: #FFF8F0; padding: 20px; border-radius: 15px; margin: 20px 0; }
        .product-details p { margin: 10px 0; }
        .product-details strong { color: #8D5524; }
        .product-stock { padding: 10px 20px; border-radius: 25px; display: inline-block; margin: 15px 0; }
        .product-stock.available { background: #d4edda; color: #155724; }
        .product-stock.unavailable { background: #f8d7da; color: #721c24; }
        
        .btn-contacter {
            background: linear-gradient(135deg, #8D5524, #C58F5E);
            color: white; border: none; padding: 18px 40px;
            border-radius: 50px; font-size: 1.2rem; font-weight: bold;
            cursor: pointer; transition: all 0.3s; width: 100%;
            text-decoration: none; display: inline-block; text-align: center;
        }
        .btn-contacter:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(141,85,36,0.3); }
        
        /* Artisan card */
        .artisan-card {
            background: white; padding: 25px; border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1); margin-top: 30px;
            display: flex; align-items: center; gap: 20px;
        }
        .artisan-card img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #C58F5E; }
        .artisan-info h4 { color: #8D5524; margin: 0 0 5px 0; }
        .artisan-info p { color: #666; margin: 0; font-size: 0.9rem; }
        .artisan-info a { color: #8D5524; text-decoration: none; font-weight: bold; }
        
        /* Commentaires */
        .section-title { color: #8D5524; margin: 40px 0 20px; padding-bottom: 10px; border-bottom: 2px solid #E6E2DD; }
        .comment-card {
            background: white; padding: 20px; border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.08); margin-bottom: 15px;
        }
        .comment-header { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; }
        .comment-header img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; }
        .comment-author { font-weight: bold; color: #3E2723; }
        .comment-date { font-size: 0.85rem; color: #999; }
        .comment-rating { color: #FFB800; }
        .comment-text { color: #666; line-height: 1.6; }
        
        /* Autres articles */
        .autres-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .autre-card { background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .autre-card:hover { transform: translateY(-5px); }
        .autre-card img { width: 100%; height: 150px; object-fit: cover; }
        .autre-card-info { padding: 15px; }
        .autre-card-info h5 { margin: 0 0 5px 0; color: #3E2723; }
        .autre-card-info .prix { color: #8D5524; font-weight: bold; }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <main>
            <!-- Fil d'Ariane -->
            <div class="breadcrumb">
                <a href="Page_Acceuil.php">Accueil</a> &gt;
                <a href="All_Products.php">Produits</a> &gt;
                <a href="All_Products.php?categorie=<?= urlencode($article['categorie']) ?>"><?= ucfirst(htmlspecialchars($article['categorie'])) ?></a> &gt;
                <span><?= htmlspecialchars($article['nom_article']) ?></span>
            </div>

            <!-- Section Produit -->
            <div class="product-section">
                <div class="product-image">
                    <span class="badge-categorie"><?= htmlspecialchars($article['categorie']) ?></span>
                    <?php if ($isClientUser): ?>
                    <button class="btn-favori-large <?= $isFavorite ? 'active' : '' ?>" 
                            onclick="toggleFavori(<?= $article['id_article'] ?>, this)">
                        <?= $isFavorite ? 'Favori' : 'Ajouter' ?>
                    </button>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($article['nom_article']) ?>">
                </div>
                
                <div class="product-info">
                    <h1><?= htmlspecialchars($article['nom_article']) ?></h1>
                    
                    <div class="product-price"><?= number_format($article['prix'], 2, ',', ' ') ?> €</div>
                    
                    <div class="product-stock <?= $article['disponibilite'] ? 'available' : 'unavailable' ?>">
                        <?= $article['disponibilite'] ? 'En stock' : 'Indisponible' ?>
                        <?php if ($article['quantite'] > 0): ?>
                            (<?= $article['quantite'] ?> disponible<?= $article['quantite'] > 1 ? 's' : '' ?>)
                        <?php endif; ?>
                    </div>
                    
                    <p class="product-desc"><?= nl2br(htmlspecialchars($article['description'])) ?></p>
                    
                    <div class="product-details">
                        <?php if ($article['materiau']): ?>
                        <p><strong>Matériau :</strong> <?= htmlspecialchars($article['materiau']) ?></p>
                        <?php endif; ?>
                        <?php if ($article['dimensions']): ?>
                        <p><strong>Dimensions :</strong> <?= htmlspecialchars($article['dimensions']) ?></p>
                        <?php endif; ?>
                        <?php if ($article['couleur']): ?>
                        <p><strong>Couleur :</strong> <?= htmlspecialchars($article['couleur']) ?></p>
                        <?php endif; ?>
                        <?php if ($article['style']): ?>
                        <p><strong>Style :</strong> <?= htmlspecialchars($article['style']) ?></p>
                        <?php endif; ?>
                        <p><strong>Vues :</strong> <?= $article['vue'] ?> personnes ont consulté cet article</p>
                    </div>
                    
                    <?php if ($article['disponibilite']): ?>
                    <a href="Contact.php?artisan=<?= $article['id_artisan'] ?>&produit=<?= $article['id_article'] ?>" class="btn-contacter">
                        Contacter l'artisan
                    </a>
                    <?php else: ?>
                    <p style="text-align:center; color:#721c24; background:#f8d7da; padding:15px; border-radius:10px;">
                        Cette création n'est plus disponible
                    </p>
                    <?php endif; ?>
                    
                    <!-- Artisan card with social links -->
                    <div class="artisan-card">
                        <img src="<?= htmlspecialchars($artisanPhoto) ?>" alt="Photo artisan">
                        <div class="artisan-info">
                            <h4>
                                <?= htmlspecialchars($article['artisan_prenom'] . ' ' . $article['artisan_nom']) ?>
                                <?php if (!empty($article['artisan_verifie'])): ?>
                                <span class="badge-verified" style="margin-left:8px; font-size:0.7rem;">Vérifié</span>
                                <?php endif; ?>
                            </h4>
                            <?php if (!empty($article['artisan_specialite'])): ?>
                            <p style="color:#8D5524; font-weight:500;"><?= htmlspecialchars($article['artisan_specialite']) ?></p>
                            <?php endif; ?>
                            <p><?= htmlspecialchars($article['artisan_ville'] ?? 'France') ?></p>
                            
                            <!-- Social links -->
                            <div class="artisan-social" style="margin-top:10px; display:flex; gap:10px; flex-wrap:wrap;">
                                <?php if (!empty($article['artisan_site'])): ?>
                                <a href="<?= htmlspecialchars($article['artisan_site']) ?>" target="_blank" rel="noopener" 
                                   style="background:#8D5524; color:white; padding:5px 10px; border-radius:15px; font-size:0.8rem; text-decoration:none;">
                                    Site web
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($article['artisan_instagram'])): ?>
                                <a href="<?= htmlspecialchars($article['artisan_instagram']) ?>" target="_blank" rel="noopener"
                                   style="background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); color:white; padding:5px 10px; border-radius:15px; font-size:0.8rem; text-decoration:none;">
                                    Instagram
                                </a>
                                <?php endif; ?>
                                <?php if (!empty($article['artisan_facebook'])): ?>
                                <a href="<?= htmlspecialchars($article['artisan_facebook']) ?>" target="_blank" rel="noopener"
                                   style="background:#1877F2; color:white; padding:5px 10px; border-radius:15px; font-size:0.8rem; text-decoration:none;">
                                    Facebook
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <a href="artisan.php?id=<?= $article['artisan_id'] ?>" style="display:inline-block; margin-top:12px; background:#8D5524; color:white; padding:8px 18px; border-radius:20px; text-decoration:none; font-weight:600; transition:all 0.3s;">
                                Voir le profil de l'artisan →
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Commentaires -->
            <h3 class="section-title">Avis clients (<?= count($commentaires) ?>)</h3>
            <?php if (empty($commentaires)): ?>
            <p style="color:#666;">Aucun avis pour le moment. Soyez le premier à donner votre avis !</p>
            <?php else: ?>
            <?php foreach ($commentaires as $comm): ?>
            <div class="comment-card">
                <div class="comment-header">
                    <img src="<?= !empty($comm['photo_profil']) ? '../' . htmlspecialchars($comm['photo_profil']) : '../assets/images/default-avatar.svg' ?>" alt="Photo">
                    <div>
                        <div class="comment-author"><?= htmlspecialchars($comm['prenom'] . ' ' . $comm['nom']) ?></div>
                        <div class="comment-date"><?= date('d/m/Y', strtotime($comm['date'])) ?></div>
                    </div>
                    <div class="comment-rating">
                        <?= isset($comm['note']) ? $comm['note'] : 5 ?>/5
                    </div>
                </div>
                <p class="comment-text"><?= nl2br(htmlspecialchars($comm['commentaire'])) ?></p>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Autres créations de l'artisan -->
            <?php if (!empty($autresArticles)): ?>
            <h3 class="section-title">Autres créations de <?= htmlspecialchars($article['artisan_prenom']) ?></h3>
            <div class="autres-grid">
                <?php foreach ($autresArticles as $autre): ?>
                <a href="produit_detail.php?id=<?= $autre['id_article'] ?>" class="autre-card">
                    <img src="<?= !empty($autre['image']) ? '../' . htmlspecialchars($autre['image']) : 'https://via.placeholder.com/200x150/8D5524/FFFFFF' ?>" 
                         alt="<?= htmlspecialchars($autre['nom_article']) ?>">
                    <div class="autre-card-info">
                        <h5><?= htmlspecialchars($autre['nom_article']) ?></h5>
                        <div class="prix"><?= number_format($autre['prix'], 2, ',', ' ') ?> €</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>

        <!-- Footer -->
        <?php include '../HTML/footer.html'; ?>
    </div>

    <script src="../JavaScript/script.js"></script>
    <script>
        function toggleFavori(articleId, btn) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            
            fetch('api_favoris.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId, action: 'toggle' })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Réponse API:', data);
                if (data.success) {
                    btn.classList.toggle('active', data.isFavorite);
                    btn.innerHTML = data.isFavorite ? 'Favori' : 'Ajouter';
                } else {
                    if (data.requireLogin) {
                        if (confirm('Vous devez être connecté. Aller à la page de connexion ?')) {
                            window.location.href = 'login.php';
                        }
                    } else {
                        alert(data.message || 'Erreur inconnue');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur fetch:', error);
                alert('Erreur: ' + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    </script>
</body>
</html>
