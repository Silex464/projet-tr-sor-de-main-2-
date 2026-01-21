<?php
session_start();
require_once 'auth.php';
require_once 'tresorsdemain.php';

requireAdmin();
requireAdminPermission('gerer_creations');

$pdo = getConnection();
$message = '';
$message_type = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $article_id = (int)($_POST['article_id'] ?? 0);
    
    if ($action === 'delete' && $article_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM article WHERE id_article = ?");
            $stmt->execute([$article_id]);
            $message = "Création supprimée avec succès.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Delete article error: " . $e->getMessage());
            $message = "Erreur lors de la suppression.";
            $message_type = "error";
        }
    } elseif ($action === 'toggle_available' && $article_id) {
        try {
            $stmt = $pdo->prepare("UPDATE article SET available = NOT available WHERE id_article = ?");
            $stmt->execute([$article_id]);
            $message = "Disponibilité mise à jour.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Toggle available error: " . $e->getMessage());
            $message = "Erreur lors de la mise à jour.";
            $message_type = "error";
        }
    }
}

// Récupérer les créations
$search = sanitize($_GET['search'] ?? '');
$type = sanitize($_GET['type'] ?? '');

$query = "SELECT a.* FROM article a WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (a.nom_article LIKE ? OR a.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($type) {
    $query .= " AND a.type = ?";
    $params[] = $type;
}

$query .= " ORDER BY a.id_article DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Récupérer les types d'articles
$types_stmt = $pdo->query("SELECT DISTINCT type FROM article WHERE type IS NOT NULL AND type != '' ORDER BY type");
$types = $types_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Créations - Admin</title>
    <link rel="stylesheet" href="/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/CSS/projet.css">
    <style>
        * { box-sizing: border-box; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2C1810; color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h3 { color: #C58F5E; margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #8D5524; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar li { margin: 10px 0; }
        .sidebar a { color: #E0D5CE; text-decoration: none; display: flex; align-items: center; padding: 10px 15px; border-radius: 8px; transition: all 0.3s; }
        .sidebar a:hover { background: #8D5524; color: white; }
        .sidebar a.active { background: #C58F5E; color: white; font-weight: bold; }
        .content { margin-left: 250px; flex: 1; padding: 20px; background: #F5F1ED; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #2C1810; }
        .logout-btn { background: #d32f2f; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .filters { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .filters input, .filters select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .filters button { background: #8D5524; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .articles-table { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .articles-table table { width: 100%; border-collapse: collapse; }
        .articles-table th { background: #8D5524; color: white; padding: 15px; text-align: left; }
        .articles-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
        .articles-table tr:hover { background: #f9f5f1; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .btn-danger:hover { background: #c62828; }
        .btn-warning:hover { background: #fb8500; }
        .btn-success:hover { background: #45a049; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; }
        .badge-featured { background: #fff3cd; color: #856404; }
        .badge-available { background: #d4edda; color: #155724; }
        .badge-unavailable { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <h3>Administration</h3>
            <ul>
                <li><a href="admin_dashboard.php">Tableau de bord</a></li>
                <li><a href="admin_users.php">Utilisateurs</a></li>
                <li><a href="admin_creations.php" class="active">Créations</a></li>
                <li><a href="admin_events.php">Événements</a></li>
                <li><a href="admin_comments.php">Commentaires</a></li>
                <?php if (isSuperAdmin()): ?>
                    <li><a href="admin_admins.php">Administrateurs</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="content">
            <div class="header">
                <h1>Gestion des Créations (Articles)</h1>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="filters">
                <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                    <input type="text" name="search" placeholder="Rechercher par nom..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; flex: 1; min-width: 200px;">
                    <select name="type" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">Tous les types</option>
                        <?php foreach ($types as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>" <?php if ($type === $t) echo 'selected'; ?>>
                                <?php echo ucfirst($t); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background: #8D5524; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Filtrer</button>
                </form>
            </div>

            <!-- Tableau des créations -->
            <div class="articles-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Quantité</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td><?php echo $article['id_article']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($article['nom_article']); ?></strong>
                                    <?php if (!empty($article['description'])): ?>
                                        <br><small style="color:#666;"><?php echo htmlspecialchars(substr($article['description'], 0, 50)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo ucfirst($article['type']); ?></td>
                                <td><?php echo number_format($article['prix'], 2); ?>€</td>
                                <td><?php echo $article['quantite']; ?></td>
                                <td>
                                    <?php if ($article['available']): ?>
                                        <span class="badge badge-available">Disponible</span>
                                    <?php else: ?>
                                        <span class="badge badge-unavailable">Indisponible</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="article_id" value="<?php echo $article['id_article']; ?>">
                                            <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette création ?');">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($articles) === 0): ?>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        <p style="font-style: italic;">Aucune création trouvée.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
