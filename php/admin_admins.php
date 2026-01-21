<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'tresorsdemain.php';

requireSuperAdmin();

$pdo = getConnection();
$message = '';
$message_type = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    
    if ($action === 'create') {
        $nom = sanitize($_POST['nom'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = sanitize($_POST['role'] ?? 'admin');
        
        if (empty($nom) || empty($email) || empty($password)) {
            $message = "Tous les champs sont obligatoires.";
            $message_type = "error";
        } elseif ($password !== $password_confirm) {
            $message = "Les mots de passe ne correspondent pas.";
            $message_type = "error";
        } elseif (strlen($password) < 8) {
            $message = "Le mot de passe doit contenir au moins 8 caractères.";
            $message_type = "error";
        } else {
            try {
                // Vérifier que l'email n'existe pas
                $stmt = $pdo->prepare("SELECT id_admin FROM administrateur WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->rowCount() > 0) {
                    $message = "Cet email est déjà utilisé.";
                    $message_type = "error";
                } else {
                    // Créer le nouvel admin
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Permissions par défaut selon le rôle
                    $permissions = [];
                    if ($role === 'super_admin') {
                        $permissions = [
                            'gerer_utilisateurs' => true,
                            'gerer_evenements' => true,
                            'gerer_creations' => true,
                            'gerer_admin' => true,
                            'gerer_commandes' => true
                        ];
                    } else {
                        $permissions = [
                            'gerer_utilisateurs' => true,
                            'gerer_evenements' => true,
                            'gerer_creations' => true,
                            'gerer_admin' => false,
                            'gerer_commandes' => false
                        ];
                    }
                    
                    $stmt = $pdo->prepare("INSERT INTO administrateur (nom, email, password, role, permissions, actif) VALUES (?, ?, ?, ?, ?, TRUE)");
                    $stmt->execute([$nom, $email, $password_hash, $role, json_encode($permissions)]);
                    $message = "Administrateur créé avec succès.";
                    $message_type = "success";
                }
            } catch (PDOException $e) {
                error_log("Create admin error: " . $e->getMessage());
                $message = "Erreur lors de la création.";
                $message_type = "error";
            }
        }
    } elseif ($action === 'delete') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        if ($admin_id === $_SESSION['admin_id']) {
            $message = "Vous ne pouvez pas supprimer votre propre compte.";
            $message_type = "error";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM administrateur WHERE id_admin = ?");
                $stmt->execute([$admin_id]);
                $message = "Administrateur supprimé.";
                $message_type = "success";
            } catch (PDOException $e) {
                error_log("Delete admin error: " . $e->getMessage());
                $message = "Erreur lors de la suppression.";
                $message_type = "error";
            }
        }
    } elseif ($action === 'toggle_active') {
        $admin_id = (int)($_POST['admin_id'] ?? 0);
        if ($admin_id === $_SESSION['admin_id']) {
            $message = "Vous ne pouvez pas désactiver votre propre compte.";
            $message_type = "error";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE administrateur SET actif = NOT actif WHERE id_admin = ?");
                $stmt->execute([$admin_id]);
                $message = "Statut de l'administrateur mis à jour.";
                $message_type = "success";
            } catch (PDOException $e) {
                error_log("Toggle admin error: " . $e->getMessage());
                $message = "Erreur lors de la mise à jour.";
                $message_type = "error";
            }
        }
    }
}

// Récupérer les administrateurs
$stmt = $pdo->query("SELECT * FROM administrateur ORDER BY id_admin DESC");
$admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Administrateurs - Admin</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
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
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-section { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-section h2 { color: #8D5524; margin-top: 0; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; }
        .form-group input:focus, .form-group select:focus { border-color: #8D5524; outline: none; }
        .btn-submit { background: #8D5524; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .btn-submit:hover { background: #C58F5E; }
        .admins-table { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admins-table table { width: 100%; border-collapse: collapse; }
        .admins-table th { background: #8D5524; color: white; padding: 15px; text-align: left; }
        .admins-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; }
        .admins-table tr:hover { background: #f9f5f1; }
        .badge { display: inline-block; padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; }
        .badge-super_admin { background: #ffd700; color: #000; }
        .badge-admin { background: #4caf50; color: white; }
        .badge-active { background: #d4edda; color: #155724; }
        .badge-inactive { background: #f8d7da; color: #721c24; }
        .action-buttons { display: flex; gap: 5px; }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; border: none; border-radius: 5px; cursor: pointer; }
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
                <li><a href="admin_comments.php">Commentaires</a></li>
                <li><a href="admin_admins.php" class="active">Administrateurs</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="content">
            <div class="header">
                <h1>Gestion des Administrateurs</h1>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Formulaire de création -->
            <div class="form-section">
                <h2>➕ Créer un nouvel administrateur</h2>
                <form method="post">
                    <input type="hidden" name="action" value="create">
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label for="nom">Nom :</label>
                            <input type="text" id="nom" name="nom" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Mot de passe :</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="password_confirm">Confirmer le mot de passe :</label>
                            <input type="password" id="password_confirm" name="password_confirm" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Rôle :</label>
                            <select id="role" name="role">
                                <option value="admin">Administrateur</option>
                                <option value="super_admin">Super Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Créer l'administrateur</button>
                </form>
            </div>

            <!-- Tableau des administrateurs -->
            <div class="admins-table">
                <h2 style="background: white; margin: 0 0 10px 0; padding: 20px; border-radius: 10px 10px 0 0; color: #8D5524;">Liste des administrateurs</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Dernière connexion</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id_admin']; ?></td>
                                <td><?php echo htmlspecialchars($admin['nom']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $admin['role']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo ($admin['actif'] ? 'active' : 'inactive'); ?>">
                                        <?php echo ($admin['actif'] ? 'Actif' : 'Inactif'); ?>
                                    </span>
                                </td>
                                <td><?php echo $admin['derniere_connexion'] ? date('d/m/Y H:i', strtotime($admin['derniere_connexion'])) : 'Jamais'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($admin['id_admin'] !== $_SESSION['admin_id']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id_admin']; ?>">
                                                <button type="submit" class="btn-sm btn-warning">
                                                    <?php echo ($admin['actif'] ? 'Désactiver' : 'Activer'); ?>
                                                </button>
                                            </form>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="admin_id" value="<?php echo $admin['id_admin']; ?>">
                                                <button type="submit" class="btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur ?');">Supprimer</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #666; font-style: italic;">Votre compte</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
