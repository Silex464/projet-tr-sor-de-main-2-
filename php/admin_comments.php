<?php
session_start();
require_once 'auth.php';
require_once 'tresorsdemain.php';

requireAdmin();

$pdo = getConnection();
$message = '';
$message_type = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $comment_id = (int)($_POST['comment_id'] ?? 0);
    
    if ($action === 'approve' && $comment_id) {
        try {
            $stmt = $pdo->prepare("UPDATE commentaire SET statut = 'approuve' WHERE id_commentaire = ?");
            $stmt->execute([$comment_id]);
            $message = "Commentaire approuvé.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Approve comment error: " . $e->getMessage());
            $message = "Erreur lors de l'approbation.";
            $message_type = "error";
        }
    } elseif ($action === 'reject' && $comment_id) {
        try {
            $stmt = $pdo->prepare("UPDATE commentaire SET statut = 'rejete' WHERE id_commentaire = ?");
            $stmt->execute([$comment_id]);
            $message = "Commentaire rejeté.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Reject comment error: " . $e->getMessage());
            $message = "Erreur lors du rejet.";
            $message_type = "error";
        }
    } elseif ($action === 'delete' && $comment_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM commentaire WHERE id_commentaire = ?");
            $stmt->execute([$comment_id]);
            $message = "Commentaire supprimé.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Delete comment error: " . $e->getMessage());
            $message = "Erreur lors de la suppression.";
            $message_type = "error";
        }
    }
}

// Récupérer les commentaires
$statut = sanitize($_GET['statut'] ?? '');

$query = "SELECT c.*, a.nom_article, u.prenom, u.nom FROM commentaire c 
          JOIN article a ON c.id_article = a.id_article 
          JOIN utilisateurs u ON c.id_client = u.id 
          WHERE 1=1";
$params = [];

if ($statut && in_array($statut, ['approuve', 'en_attente', 'rejete'])) {
    $query .= " AND c.statut = ?";
    $params[] = $statut;
}

$query .= " ORDER BY c.date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Commentaires - Admin</title>
    <link rel="stylesheet" href="/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/CSS/projet.css">
    <style>
        * { box-sizing: border-box; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2C1810; color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h3 { color: #C58F5E; margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #8D5524; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar li { margin: 10px 0; }
        .sidebar a { color: #E0D5CE; text-decoration: none; padding: 10px 15px; border-radius: 8px; transition: all 0.3s; display: block; }
        .sidebar a:hover { background: #8D5524; color: white; }
        .sidebar a.active { background: #C58F5E; color: white; font-weight: bold; }
        .content { margin-left: 250px; flex: 1; padding: 20px; background: #F5F1ED; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #2C1810; }
        .logout-btn { background: #d32f2f; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .filters { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .comments-list { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .comment-item { padding: 20px; border-bottom: 1px solid #ddd; }
        .comment-item:last-child { border-bottom: none; }
        .comment-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px; }
        .comment-meta { color: #666; font-size: 0.9rem; }
        .comment-text { margin: 10px 0 15px 0; line-height: 1.6; }
        .comment-rating { color: #ff9800; font-weight: bold; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; }
        .badge-approuve { background: #d4edda; color: #155724; }
        .badge-en_attente { background: #fff3cd; color: #856404; }
        .badge-rejete { background: #f8d7da; color: #721c24; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-success { background: #4caf50; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-sm:hover { opacity: 0.8; }
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
                <li><a href="admin_creations.php">Créations</a></li>
                <li><a href="admin_events.php">Événements</a></li>
                <li><a href="admin_comments.php" class="active">Commentaires</a></li>
                <?php if (isSuperAdmin()): ?>
                    <li><a href="admin_admins.php">Administrateurs</a></li>
                <?php endif; ?>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="content">
            <div class="header">
                <h1>Modération des Commentaires</h1>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="filters">
                <form method="get" style="display: flex; gap: 10px;">
                    <select name="statut" onchange="this.form.submit();" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente" <?php if ($statut === 'en_attente') echo 'selected'; ?>>En attente</option>
                        <option value="approuve" <?php if ($statut === 'approuve') echo 'selected'; ?>>Approuvé</option>
                        <option value="rejete" <?php if ($statut === 'rejete') echo 'selected'; ?>>Rejeté</option>
                    </select>
                </form>
            </div>

            <!-- Liste des commentaires -->
            <div class="comments-list">
                <?php if (count($comments) > 0): ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <div>
                                    <strong><?php echo htmlspecialchars($comment['prenom'] . ' ' . $comment['nom']); ?></strong>
                                    <span class="badge badge-<?php echo $comment['statut']; ?>">
                                        <?php echo ucfirst($comment['statut']); ?>
                                    </span>
                                </div>
                                <span class="comment-meta"><?php echo date('d/m/Y H:i', strtotime($comment['date'])); ?></span>
                            </div>
                            <div>
                                <strong><?php echo htmlspecialchars($comment['nom_article']); ?></strong>
                            </div>
                            <div class="comment-rating">
                                Note : <?php echo $comment['note']; ?>/5
                            </div>
                            <div class="comment-text">
                                <?php echo nl2br(htmlspecialchars($comment['commentaire'])); ?>
                            </div>
                            <div class="action-buttons">
                                <?php if ($comment['statut'] !== 'approuve'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                        <button type="submit" class="btn-sm btn-success">Approuver</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($comment['statut'] !== 'rejete'): ?>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                        <button type="submit" class="btn-sm btn-warning">Rejeter</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id_commentaire']; ?>">
                                    <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr ?');">Supprimer</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        <p style="font-style: italic;">Aucun commentaire trouvé.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
