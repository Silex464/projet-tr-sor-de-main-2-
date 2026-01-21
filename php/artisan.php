<?php
/**
 * ============================================================================
 * PROFIL ARTISAN PUBLIC - Page de présentation d'un artisan
 * ============================================================================
 * 
 * Permet aux visiteurs de découvrir :
 * - Les informations de l'artisan
 * - Toutes ses créations
 * - Ses coordonnées et réseaux sociaux
 */

session_start();
require_once 'tresorsdemain.php';
require_once 'auth.php';

// Récupérer l'ID de l'artisan
$artisanId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$artisanId) {
    header('Location: All_Products.php');
    exit();
}

try {
    $pdo = getConnection();
    
    // Récupérer les infos de l'artisan
    $stmt = $pdo->prepare("
        SELECT id, prenom, nom, description, ville, specialite, photo_profil, photo_couverture,
               site_web, instagram, facebook, date_inscription, badge_verifie, note_moyenne
        FROM utilisateurs 
        WHERE id = ? AND type_compte = 'artisan' AND statut = 'actif'
    ");
    $stmt->execute([$artisanId]);
    $artisan = $stmt->fetch();
    
    if (!$artisan) {
        header('Location: All_Products.php');
        exit();
    }
    
    // Récupérer les créations de l'artisan
    $stmt = $pdo->prepare("
        SELECT a.*, 
               (SELECT COUNT(*) FROM favoris f WHERE f.id_article = a.id_article) as nb_favoris
        FROM article a 
        WHERE a.id_artisan = ? AND a.disponibilite = 1
        ORDER BY a.mis_en_avant DESC, a.date_ajout DESC
    ");
    $stmt->execute([$artisanId]);
    $creations = $stmt->fetchAll();
    
    // Statistiques
    $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(vue) as vues FROM article WHERE id_artisan = ?");
    $stmt->execute([$artisanId]);
    $stats = $stmt->fetch();
    
    // Nombre total de favoris sur ses créations
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total FROM favoris f 
        JOIN article a ON f.id_article = a.id_article 
        WHERE a.id_artisan = ?
    ");
    $stmt->execute([$artisanId]);
    $totalFavoris = $stmt->fetch()['total'];
    
    // Catégories de l'artisan
    $stmt = $pdo->prepare("SELECT DISTINCT categorie FROM article WHERE id_artisan = ? AND disponibilite = 1");
    $stmt->execute([$artisanId]);
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Vérifier si l'utilisateur connecté est un client (pour les favoris)
    $isClient = isClient();
    $userId = getUserId();
    $favoris = [];
    if ($isClient) {
        $favStmt = $pdo->prepare("SELECT id_article FROM favoris WHERE id_client = ?");
        $favStmt->execute([$userId]);
        $favoris = $favStmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch (PDOException $e) {
    error_log("Artisan profile error: " . $e->getMessage());
    header('Location: All_Products.php');
    exit();
}

$artisanPhoto = !empty($artisan['photo_profil']) ? '../' . $artisan['photo_profil'] : '../assets/images/default-avatar.svg';
$coverPhoto = !empty($artisan['photo_couverture']) ? '../' . $artisan['photo_couverture'] : '/Projet-Tr-sor-de-Main/assets/images/Tour-de-potier.jpg';
$memberSince = date('F Y', strtotime($artisan['date_inscription']));
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']) ?> - Artisan sur Trésor de Main</title>
    <meta name="description" content="Découvrez les créations de <?= htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']) ?>, artisan <?= htmlspecialchars($artisan['specialite'] ?? '') ?> sur Trésor de Main.">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/projet.css">
    <style>
        :root {
            --primary: #8D5524;
            --primary-light: #C58F5E;
            --secondary: #3E2723;
            --bg-light: #FFF8F0;
            --success: #28a745;
            --heart-red: #E74C3C;
        }
        
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; background: var(--bg-light); }
        
        /* Cover Section */
        .artisan-cover {
            height: 300px;
            background: linear-gradient(rgba(62,39,35,0.6), rgba(141,85,36,0.7)),
                        url('<?= htmlspecialchars($coverPhoto) ?>') center/cover no-repeat;
            position: relative;
        }
        
        /* Profile Header */
        .artisan-header {
            max-width: 1100px; margin: -80px auto 0; padding: 0 20px;
            position: relative; z-index: 10;
        }
        
        .artisan-info-card {
            background: white; border-radius: 25px; padding: 30px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.15);
            display: grid; grid-template-columns: auto 1fr auto; gap: 30px;
            align-items: center;
        }
        
        .artisan-photo-wrapper { position: relative; }
        .artisan-photo {
            width: 150px; height: 150px; border-radius: 50%; object-fit: cover;
            border: 5px solid white; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .verified-badge {
            position: absolute; bottom: 10px; right: 5px;
            background: var(--success); color: white;
            width: 35px; height: 35px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; border: 3px solid white;
        }
        
        .artisan-details h1 { color: var(--secondary); margin: 0 0 8px; font-size: 2rem; }
        .artisan-specialty { color: var(--primary); font-weight: 600; font-size: 1.1rem; margin-bottom: 8px; }
        .artisan-location { color: #666; display: flex; align-items: center; gap: 5px; margin-bottom: 15px; }
        .artisan-bio { color: #555; line-height: 1.7; margin-bottom: 15px; }
        
        .artisan-tags { display: flex; gap: 10px; flex-wrap: wrap; }
        .tag {
            background: rgba(141,85,36,0.1); color: var(--primary);
            padding: 6px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;
        }
        
        .artisan-social { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; }
        .social-link {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 20px; border-radius: 25px; text-decoration: none;
            font-weight: 600; transition: all 0.3s; font-size: 0.9rem;
        }
        .social-link.website { background: var(--primary); color: white; }
        .social-link.website:hover { background: var(--primary-light); }
        .social-link.instagram { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); color: white; }
        .social-link.facebook { background: #1877f2; color: white; }
        .social-link:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        /* Stats */
        .artisan-stats {
            max-width: 1100px; margin: 30px auto; padding: 0 20px;
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
        }
        .stat-card {
            background: white; padding: 25px; border-radius: 15px;
            text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .stat-card .number { font-size: 2rem; font-weight: bold; color: var(--primary); }
        .stat-card .label { color: #666; font-size: 0.9rem; margin-top: 5px; }
        
        /* Creations Section */
        .creations-section {
            max-width: 1100px; margin: 0 auto 50px; padding: 0 20px;
        }
        .section-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 30px; flex-wrap: wrap; gap: 15px;
        }
        .section-header h2 { color: var(--secondary); margin: 0; }
        .filter-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn {
            padding: 8px 20px; background: white; border: 2px solid #E6E2DD;
            border-radius: 25px; cursor: pointer; font-weight: 500; color: var(--secondary);
            transition: all 0.3s; font-size: 0.9rem;
        }
        .filter-btn:hover, .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
        
        /* Creations Grid */
        .creations-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;
        }
        
        .creation-card {
            background: white; border-radius: 20px; overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); transition: all 0.3s;
            position: relative;
        }
        .creation-card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .creation-card.featured { border: 3px solid var(--primary); }
        .featured-badge {
            position: absolute; top: 15px; left: 15px;
            background: var(--primary); color: white;
            padding: 5px 12px; border-radius: 15px; font-size: 0.75rem; font-weight: 600;
        }
        .creation-card img { width: 100%; height: 220px; object-fit: cover; }
        .creation-card-body { padding: 20px; }
        .creation-card h3 { margin: 0 0 10px; font-size: 1.15rem; }
        .creation-card h3 a { color: var(--secondary); text-decoration: none; transition: color 0.3s; }
        .creation-card h3 a:hover { color: var(--primary); }
        .creation-price { color: var(--primary); font-size: 1.4rem; font-weight: bold; margin-bottom: 10px; }
        .creation-meta { display: flex; gap: 15px; color: #888; font-size: 0.85rem; }
        .creation-meta span { display: flex; align-items: center; gap: 5px; }
        
        .btn-favori {
            position: absolute; top: 15px; right: 15px;
            background: white; border: none; width: 40px; height: 40px;
            border-radius: 50%; cursor: pointer; font-size: 1.2rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2); transition: all 0.3s;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-favori:hover { transform: scale(1.1); }
        .btn-favori.active { background: var(--heart-red); }
        
        /* Empty State */
        .empty-creations {
            text-align: center; padding: 60px 20px; background: white;
            border-radius: 20px; box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        .empty-creations .icon { font-size: 4rem; margin-bottom: 15px; opacity: 0.5; }
        .empty-creations h3 { color: var(--secondary); margin-bottom: 10px; }
        .empty-creations p { color: #666; }
        
        /* Contact CTA */
        .contact-cta {
            max-width: 1100px; margin: 0 auto 50px; padding: 0 20px;
        }
        .contact-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white; padding: 40px; border-radius: 25px; text-align: center;
        }
        .contact-card h3 { color: white; margin-bottom: 15px; }
        .contact-card p { margin-bottom: 25px; opacity: 0.9; }
        .contact-card .btn {
            display: inline-block; background: white; color: var(--primary);
            padding: 15px 40px; border-radius: 50px; text-decoration: none;
            font-weight: 700; transition: all 0.3s;
        }
        .contact-card .btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .artisan-cover { height: 200px; }
            .artisan-info-card { grid-template-columns: 1fr; text-align: center; padding: 20px; }
            .artisan-photo { width: 120px; height: 120px; }
            .artisan-social { align-items: center; flex-direction: row; justify-content: center; flex-wrap: wrap; }
            .artisan-stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <!-- Cover -->
            <div class="artisan-cover"></div>
            
            <!-- Artisan Info Card -->
            <div class="artisan-header">
                <div class="artisan-info-card">
                    <div class="artisan-photo-wrapper">
                        <img src="<?= htmlspecialchars($artisanPhoto) ?>" alt="<?= htmlspecialchars($artisan['prenom']) ?>" class="artisan-photo">
                        <?php if ($artisan['badge_verifie']): ?>
                        <div class="verified-badge" title="Artisan vérifié">✓</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artisan-details">
                        <h1><?= htmlspecialchars($artisan['prenom'] . ' ' . $artisan['nom']) ?></h1>
                        <?php if (!empty($artisan['specialite'])): ?>
                        <div class="artisan-specialty"><?= htmlspecialchars($artisan['specialite']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($artisan['ville'])): ?>
                        <div class="artisan-location"><?= htmlspecialchars($artisan['ville']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($artisan['description'])): ?>
                        <p class="artisan-bio"><?= nl2br(htmlspecialchars($artisan['description'])) ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($categories)): ?>
                        <div class="artisan-tags">
                            <?php foreach ($categories as $cat): ?>
                            <span class="tag"><?= ucfirst(htmlspecialchars($cat)) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="artisan-social">
                        <?php if (!empty($artisan['site_web'])): ?>
                        <a href="<?= htmlspecialchars($artisan['site_web']) ?>" target="_blank" class="social-link website">
                            Site web
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($artisan['instagram'])): ?>
                        <a href="https://instagram.com/<?= htmlspecialchars(ltrim($artisan['instagram'], '@')) ?>" target="_blank" class="social-link instagram">
                            Instagram
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($artisan['facebook'])): ?>
                        <a href="<?= htmlspecialchars($artisan['facebook']) ?>" target="_blank" class="social-link facebook">
                            Facebook
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="artisan-stats">
                <div class="stat-card">
                    <div class="number"><?= $stats['total'] ?? 0 ?></div>
                    <div class="label">Créations</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $stats['vues'] ?? 0 ?></div>
                    <div class="label">Vues totales</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= $totalFavoris ?></div>
                    <div class="label">En favoris</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?= date('Y') - date('Y', strtotime($artisan['date_inscription'])) ?: '< 1' ?></div>
                    <div class="label">Année(s) sur TdM</div>
                </div>
            </div>
            
            <!-- Creations -->
            <div class="creations-section">
                <div class="section-header">
                    <h2>Ses Créations (<?= count($creations) ?>)</h2>
                    <?php if (count($categories) > 1): ?>
                    <div class="filter-buttons">
                        <button class="filter-btn active" data-filter="all">Toutes</button>
                        <?php foreach ($categories as $cat): ?>
                        <button class="filter-btn" data-filter="<?= htmlspecialchars($cat) ?>"><?= ucfirst(htmlspecialchars($cat)) ?></button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($creations)): ?>
                <div class="creations-grid">
                    <?php foreach ($creations as $creation): ?>
                    <div class="creation-card <?= $creation['mis_en_avant'] ? 'featured' : '' ?>" data-category="<?= htmlspecialchars($creation['categorie']) ?>">
                        <?php if ($creation['mis_en_avant']): ?>
                        <span class="featured-badge">En vedette</span>
                        <?php endif; ?>
                        
                        <?php if ($isClient): ?>
                        <button class="btn-favori <?= in_array($creation['id_article'], $favoris) ? 'active' : '' ?>" 
                                onclick="toggleFavori(<?= $creation['id_article'] ?>, this)">
                            <?= in_array($creation['id_article'], $favoris) ? 'Favori' : 'Ajouter' ?>
                        </button>
                        <?php endif; ?>
                        
                        <a href="produit_detail.php?id=<?= $creation['id_article'] ?>">
                            <img src="../<?= htmlspecialchars($creation['image']) ?>" alt="<?= htmlspecialchars($creation['nom_article']) ?>">
                        </a>
                        
                        <div class="creation-card-body">
                            <h3><a href="produit_detail.php?id=<?= $creation['id_article'] ?>"><?= htmlspecialchars($creation['nom_article']) ?></a></h3>
                            <div class="creation-price"><?= number_format($creation['prix'], 2, ',', ' ') ?> €</div>
                            <div class="creation-meta">
                                <span><?= $creation['vue'] ?> vues</span>
                                <span><?= $creation['nb_favoris'] ?> favoris</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="empty-creations">
                    <div class="icon"></div>
                    <h3>Aucune création disponible</h3>
                    <p>Cet artisan n'a pas encore ajouté de créations ou elles sont actuellement indisponibles.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Contact CTA -->
            <div class="contact-cta">
                <div class="contact-card">
                    <h3>Intéressé par le travail de <?= htmlspecialchars($artisan['prenom']) ?> ?</h3>
                    <p>N'hésitez pas à contacter l'artisan pour discuter d'une commande, poser des questions ou demander une création personnalisée.</p>
                    <a href="Contact.php" class="btn">Contacter l'artisan</a>
                </div>
            </div>
        </main>

        <?php include '../HTML/footer.html'; ?>
    </div>

    <script>
        // Filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const filter = this.dataset.filter;
                
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                // Filter creations
                document.querySelectorAll('.creation-card').forEach(card => {
                    if (filter === 'all' || card.dataset.category === filter) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });
        
        // Favoris toggle
        function toggleFavori(articleId, button) {
            fetch('api_favoris.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId, action: 'toggle' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('active', data.isFavorite);
                    button.textContent = data.isFavorite ? 'Favori' : 'Ajouter';
                } else if (data.requireLogin) {
                    window.location.href = 'login.php';
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
    
    <script src="../JavaScript/script.js"></script>
</body>
</html>
