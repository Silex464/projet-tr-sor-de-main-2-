<?php
session_start();
require_once 'auth.php';
require_once 'tresorsdemain.php';

requireAdmin();
requireAdminPermission('gerer_utilisateurs');

$pdo = getConnection();
$message = '';
$message_type = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    if ($action === 'ban' && $user_id) {
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'suspendu' WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "Compte utilisateur suspendu avec succès.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Ban user error: " . $e->getMessage());
            $message = "Erreur lors de la suspension du compte.";
            $message_type = "error";
        }
    } elseif ($action === 'unban' && $user_id) {
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET statut = 'actif' WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "Compte utilisateur réactivé avec succès.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Unban user error: " . $e->getMessage());
            $message = "Erreur lors de la réactivation du compte.";
            $message_type = "error";
        }
    } elseif ($action === 'delete' && $user_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $message = "Compte utilisateur supprimé avec succès.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            $message = "Erreur lors de la suppression du compte.";
            $message_type = "error";
        }
    }
}

// Récupérer les utilisateurs
$filter = sanitize($_GET['filter'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$query = "SELECT * FROM utilisateurs WHERE 1=1";
$params = [];

if ($filter && in_array($filter, ['actif', 'inactif', 'suspendu'])) {
    $query .= " AND statut = ?";
    $params[] = $filter;
}

if ($search) {
    $query .= " AND (prenom LIKE ? OR nom LIKE ? OR email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY date_inscription DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin</title>
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/projet.css">
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
        .filters { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filters input, .filters select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .filters button { background: #8D5524; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .users-table { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .users-table table { width: 100%; border-collapse: collapse; }
        .users-table th { background: #8D5524; color: white; padding: 15px; text-align: left; }
        .users-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
        .users-table tr:hover { background: #f9f5f1; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.9rem; }
        .status-actif { background: #d4edda; color: #155724; }
        .status-inactif { background: #fff3cd; color: #856404; }
        .status-suspendu { background: #f8d7da; color: #721c24; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-success { background: #4caf50; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        .btn-danger:hover { background: #c62828; }
        .btn-success:hover { background: #45a049; }
        .btn-warning:hover { background: #fb8500; }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <h3>Administration</h3>
            <ul>
                <li><a href="admin_dashboard.php">Tableau de bord</a></li>
                <li><a href="admin_users.php" class="active">Utilisateurs</a></li>
                <li><a href="admin_creations.php">Créations</a></li>
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
                <h1>Gestion des Utilisateurs</h1>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Filtres et recherche -->
            <div class="filters">
                <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%; align-items: center;">
                    <select name="filter" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                        <option value="">Tous les statuts</option>
                        <option value="actif" <?php if ($filter === 'actif') echo 'selected'; ?>>Actif</option>
                        <option value="inactif" <?php if ($filter === 'inactif') echo 'selected'; ?>>Inactif</option>
                        <option value="suspendu" <?php if ($filter === 'suspendu') echo 'selected'; ?>>Suspendu</option>
                    </select>
                    <input type="text" name="search" placeholder="Rechercher par nom, prénom ou email..." value="<?php echo htmlspecialchars($search); ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 5px; flex: 1; min-width: 200px;">
                    <button type="submit" style="background: #8D5524; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;">Rechercher</button>
                </form>
            </div>

            <!-- Tableau des utilisateurs -->
            <div class="users-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Nom Complet</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $userPhoto = !empty($user['photo_profil']) ? '/Projet-Tr-sor-de-Main/' . $user['photo_profil'] : '/Projet-Tr-sor-de-Main/assets/images/default-avatar.svg';
                        ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td><img src="<?php echo htmlspecialchars($userPhoto); ?>" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"></td>
                                <td><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo ucfirst($user['type_compte']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['statut']; ?>">
                                        <?php echo ucfirst($user['statut']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($user['statut'] !== 'suspendu'): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="ban">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir suspendre ce compte ?');">Suspendre</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="unban">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn-sm btn-success">Réactiver</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr ? Cette action est irréversible !');">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($users) === 0): ?>
                    <div style="padding: 20px; text-align: center; color: #666;">
                        <p style="font-style: italic;">Aucun utilisateur trouvé.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
