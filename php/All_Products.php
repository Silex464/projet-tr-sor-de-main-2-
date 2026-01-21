<?php
/**
 * ============================================================================
 * ALL PRODUCTS - Page de tous les produits
 * ============================================================================
 * 
 * Displays all articles from database with filters and pagination.
 * Clients can add favorites.
 * 
 * @author Trésor de Main
 * @version 2.0
 */

// Include centralized auth system (handles session)
require_once 'auth.php';

// User state using auth functions
$isLoggedIn = isLoggedIn();
$isClient = isClient();
$userId = getUserId();

// Paramètres de pagination et filtres
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

$categorie = isset($_GET['categorie']) ? trim($_GET['categorie']) : '';
$prixMax = isset($_GET['prix_max']) ? (int)$_GET['prix_max'] : 1000;
$tri = isset($_GET['tri']) ? $_GET['tri'] : 'recent';

// Récupérer les articles
try {
    $pdo = getConnection();
    
    // Construction de la requête
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
    
    // Tri
    $orderBy = match($tri) {
        'prix_asc' => 'a.prix ASC',
        'prix_desc' => 'a.prix DESC',
        'nom' => 'a.nom_article ASC',
        default => 'a.date_ajout DESC'
    };
    
    // Compter le total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM article a WHERE $whereClause");
    $countStmt->execute($params);
    $totalArticles = $countStmt->fetchColumn();
    $totalPages = ceil($totalArticles / $perPage);
    
    // Récupérer les articles avec info artisan incluant le badge de vérification
    // LEFT JOIN pour afficher les articles même si l'artisan a été supprimé
    $sql = "SELECT a.*, 
                   COALESCE(u.prenom, 'Artisan') AS artisan_prenom, 
                   COALESCE(u.nom, 'Inconnu') AS artisan_nom, 
                   COALESCE(u.badge_verifie, 0) AS artisan_verifie
            FROM article a 
            LEFT JOIN utilisateurs u ON a.id_artisan = u.id 
            WHERE $whereClause 
            ORDER BY $orderBy 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $articles = $stmt->fetchAll();
    
    // Récupérer les favoris de l'utilisateur
    $favoris = [];
    if ($isClient) {
        $favStmt = $pdo->prepare("SELECT id_article FROM favoris WHERE id_client = ?");
        $favStmt->execute([$userId]);
        $favoris = $favStmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    // Récupérer les catégories disponibles
    $catStmt = $pdo->query("SELECT DISTINCT categorie FROM article WHERE disponibilite = 1 ORDER BY categorie");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    error_log("All_Products error: " . $e->getMessage());
    $articles = [];
    $totalArticles = 0;
    $totalPages = 1;
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <title>Tous les produits - Trésor de Main</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Découvrez toutes les créations artisanales sur Trésor de Main">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/projet.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&display=swap" rel="stylesheet">
    <style>
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        .main-content { display: flex; flex: 1; flex-direction: column; }
        @media (min-width: 992px) { .main-content { flex-direction: row; } }
        
        /* Sidebar Filtres */
        .filtre-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin: 20px;
        }
        @media (min-width: 992px) {
            .filtre-container { width: 280px; position: sticky; top: 100px; height: fit-content; }
        }
        .filtre-container h3 { color: #8D5524; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #C58F5E; }
        .filtre-liste { list-style: none; padding: 0; }
        .filtre-liste li {
            margin-bottom: 10px; padding: 12px 15px; background: rgba(255,255,255,0.7);
            border-radius: 10px; border-left: 4px solid #C58F5E; transition: all 0.3s;
        }
        .filtre-liste li a { text-decoration: none; color: #3E2723; display: block; }
        .filtre-liste li:hover, .filtre-liste li.active { background: rgba(141,85,36,0.15); border-left-color: #8D5524; }
        .filtre-liste li.active { font-weight: bold; }
        
        /* Grille produits */
        .produits-container { flex: 1; padding: 20px; }
        .produits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        /* Carte produit */
        .card-produit {
            background: white; border-radius: 15px; overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); transition: all 0.3s; position: relative;
        }
        .card-produit:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .card-produit img { width: 100%; height: 220px; object-fit: cover; }
        .card-contenu { padding: 20px; }
        .card-contenu h4 { margin: 0 0 10px; color: #3E2723; font-size: 1.2rem; }
        .card-prix { color: #8D5524; font-size: 1.3rem; font-weight: bold; margin: 10px 0; }
        .card-description { color: #666; font-size: 0.9rem; line-height: 1.5; margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-artisan { font-size: 0.85rem; color: #888; margin-bottom: 15px; }
        .card-artisan a { color: #8D5524; text-decoration: none; }
        .card-artisan a:hover { text-decoration: underline; }
        
        /* Bouton favoris */
        .btn-favori {
            position: absolute; top: 15px; right: 15px;
            background: white; border: none; width: 40px; height: 40px;
            border-radius: 50%; cursor: pointer; font-size: 1.3rem;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2); transition: all 0.3s;
            display: flex; align-items: center; justify-content: center;
        }
        .btn-favori:hover { transform: scale(1.1); }
        .btn-favori.active { background: #ff6b6b; color: white; }
        
        /* Catégorie badge */
        .badge-categorie {
            position: absolute; top: 15px; left: 15px;
            background: rgba(141,85,36,0.9); color: white;
            padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; text-transform: uppercase;
        }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 10px; margin: 40px 0; flex-wrap: wrap; }
        .page-btn {
            padding: 10px 18px; background: white; border: 2px solid #8D5524;
            color: #8D5524; border-radius: 8px; cursor: pointer; text-decoration: none; transition: all 0.3s;
        }
        .page-btn.active, .page-btn:hover { background: #8D5524; color: white; }
        
        /* Titre section */
        .titre-section {
            text-align: center; padding: 30px; background: linear-gradient(rgba(255,255,255,0.9), rgba(255,255,255,0.7));
            margin-bottom: 30px; border-radius: 15px;
        }
        .titre-section h2 { color: #8D5524; margin-bottom: 10px; }
        .titre-section p { color: #666; }
        
        /* Message vide */
        .no-products {
            text-align: center; padding: 60px 20px; background: white;
            border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .no-products h3 { color: #8D5524; margin-bottom: 15px; }
        
        /* Mobile */
        .btn-filtres-mobile {
            display: none; background: #8D5524; color: white; border: none;
            padding: 12px 20px; border-radius: 10px; margin: 20px auto; cursor: pointer;
            font-weight: bold;
        }
        @media (max-width: 991px) {
            .btn-filtres-mobile { display: block; }
            .filtre-container {
                display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(255,255,255,0.98); z-index: 2000; margin: 0;
                border-radius: 0; overflow-y: auto; padding: 60px 20px 20px;
            }
            .filtre-container.active { display: block; }
            .close-filtres {
                position: absolute; top: 20px; right: 20px; background: #8D5524;
                color: white; border: none; width: 40px; height: 40px; border-radius: 50%;
                font-size: 1.5rem; cursor: pointer;
            }
        }
        
        /* Tri */
        .tri-container { display: flex; justify-content: flex-end; margin-bottom: 20px; gap: 10px; align-items: center; }
        .tri-container select { padding: 10px 15px; border: 2px solid #E6E2DD; border-radius: 8px; }
        
        /* Résultats count */
        .results-count { color: #666; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <button class="btn-filtres-mobile" onclick="toggleFiltresMobile()">🔍 Filtres</button>

        <div class="main-content container">
            <!-- Sidebar Filtres -->
            <aside class="filtre-container" id="filtresMobile">
                <button class="close-filtres" onclick="toggleFiltresMobile()">×</button>
                
                <h3>Catégories</h3>
                <ul class="filtre-liste">
                    <li class="<?= empty($categorie) ? 'active' : '' ?>">
                        <a href="All_Products.php">Tous les articles</a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                    <li class="<?= $categorie === $cat ? 'active' : '' ?>">
                        <a href="?categorie=<?= urlencode($cat) ?>"><?= ucfirst(htmlspecialchars($cat)) ?></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <h3>Prix maximum</h3>
                <form method="get" action="">
                    <?php if ($categorie): ?>
                    <input type="hidden" name="categorie" value="<?= htmlspecialchars($categorie) ?>">
                    <?php endif; ?>
                    <input type="range" name="prix_max" min="0" max="1000" value="<?= $prixMax ?>" 
                           oninput="this.nextElementSibling.value = this.value + '€'" style="width:100%;">
                    <output><?= $prixMax ?>€</output>
                    <button type="submit" class="bouton" style="width:100%; margin-top:15px;">Appliquer</button>
                </form>
            </aside>

            <!-- Contenu principal -->
            <main class="produits-container">
                <div class="titre-section">
                    <h2><?= empty($categorie) ? 'Tous les articles' : ucfirst(htmlspecialchars($categorie)) ?></h2>
                    <p>Découvrez notre sélection de créations artisanales uniques</p>
                </div>
                
                <div class="results-count">
                    <?= $totalArticles ?> article<?= $totalArticles > 1 ? 's' : '' ?> trouvé<?= $totalArticles > 1 ? 's' : '' ?>
                </div>
                
                <div class="tri-container">
                    <label>Trier par :</label>
                    <select onchange="window.location.href = this.value">
                        <option value="?<?= http_build_query(array_merge($_GET, ['tri' => 'recent'])) ?>" <?= $tri === 'recent' ? 'selected' : '' ?>>Plus récents</option>
                        <option value="?<?= http_build_query(array_merge($_GET, ['tri' => 'prix_asc'])) ?>" <?= $tri === 'prix_asc' ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="?<?= http_build_query(array_merge($_GET, ['tri' => 'prix_desc'])) ?>" <?= $tri === 'prix_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                        <option value="?<?= http_build_query(array_merge($_GET, ['tri' => 'nom'])) ?>" <?= $tri === 'nom' ? 'selected' : '' ?>>Nom A-Z</option>
                    </select>
                </div>
                
                <?php if (empty($articles)): ?>
                <div class="no-products">
                    <h3>Aucun article trouvé</h3>
                    <p>Essayez de modifier vos filtres ou <a href="All_Products.php">voir tous les articles</a></p>
                </div>
                <?php else: ?>
                <div class="produits-grid">
                    <?php foreach ($articles as $article): 
                        $isFavorite = in_array($article['id_article'], $favoris);
                        $imagePath = !empty($article['image']) ? '../' . $article['image'] : 'https://via.placeholder.com/400x300/8D5524/FFFFFF?text=Image';
                    ?>
                    <div class="card-produit">
                        <span class="badge-categorie"><?= htmlspecialchars($article['categorie']) ?></span>
                        
                        <?php if ($isClient): ?>
                        <button class="btn-favori <?= $isFavorite ? 'active' : '' ?>" 
                                onclick="toggleFavori(<?= $article['id_article'] ?>, this)"
                                title="<?= $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                            <?= $isFavorite ? 'Favori' : 'Ajouter' ?>
                        </button>
                        <?php endif; ?>
                        
                        <img src="<?= htmlspecialchars($imagePath) ?>" alt="<?= htmlspecialchars($article['nom_article']) ?>">
                        
                        <div class="card-contenu">
                            <h4><?= htmlspecialchars($article['nom_article']) ?></h4>
                            <div class="card-artisan">
                                Par <a href="artisan.php?id=<?= $article['id_artisan'] ?>">
                                    <?= htmlspecialchars($article['artisan_prenom'] . ' ' . $article['artisan_nom']) ?>
                                </a>
                                <?php if (!empty($article['artisan_verifie'])): ?>
                                <span class="badge-verified" title="Artisan vérifié">Vérifié</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-prix"><?= number_format($article['prix'], 2, ',', ' ') ?> €</div>
                            <p class="card-description"><?= htmlspecialchars($article['description']) ?></p>
                            <a href="produit_detail.php?id=<?= $article['id_article'] ?>" class="bouton" style="width:100%; text-align:center; display:block;">
                                Voir le détail
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">← Précédent</a>
                    <?php endif; ?>
                    
                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                       class="page-btn <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">Suivant →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>

        <!-- Footer -->
        <?php include '../HTML/footer.html'; ?>
    </div>

    <script src="../JavaScript/script.js"></script>
    <script>
        function toggleFiltresMobile() {
            const filtres = document.getElementById('filtresMobile');
            filtres.classList.toggle('active');
            document.body.style.overflow = filtres.classList.contains('active') ? 'hidden' : '';
        }
        
        // Fonction favoris AJAX avec toast
        function toggleFavori(articleId, btn) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            
            fetch('api_favoris.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ article_id: articleId, action: 'toggle' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    btn.classList.toggle('active', data.isFavorite);
                    btn.innerHTML = data.isFavorite ? 'Favori' : 'Ajouter';
                    btn.title = data.isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris';
                    // Toast notification
                    if (typeof showToast === 'function') {
                        showToast(data.message, 'success');
                    }
                } else if (data.requireLogin) {
                    if (typeof showToast === 'function') {
                        showToast('Connectez-vous pour ajouter aux favoris', 'warning');
                    }
                    setTimeout(() => window.location.href = 'login.php', 1500);
                } else {
                    if (typeof showToast === 'function') {
                        showToast(data.message || 'Erreur', 'error');
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                if (typeof showToast === 'function') {
                    showToast('Erreur de connexion', 'error');
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    </script>
</body>
</html>
