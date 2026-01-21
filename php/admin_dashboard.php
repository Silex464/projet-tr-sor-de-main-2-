<?php
session_start();
require_once 'config.php';
require_once 'auth.php';
require_once 'tresorsdemain.php';

// Vérifier que c'est un administrateur
requireAdmin();

$pdo = getConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord Admin - Trésor de Main</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
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
        .logout-btn:hover { background: #c62828; }
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid #8D5524; }
        .stat-card h3 { margin: 0 0 10px 0; color: #8D5524; }
        .stat-card .number { font-size: 2.5rem; font-weight: bold; color: #2C1810; }
        .admin-panel { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-panel h2 { color: #8D5524; margin-top: 0; }
        .btn-admin { display: inline-block; background: #8D5524; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; margin: 5px; font-weight: bold; transition: all 0.3s; }
        .btn-admin:hover { background: #C58F5E; transform: translateY(-2px); }
        .btn-danger { background: #d32f2f; }
        .btn-danger:hover { background: #c62828; }
        .button-group { display: flex; flex-wrap: wrap; gap: 10px; }
        @media (max-width: 768px) {
            .sidebar { width: 60px; }
            .sidebar span { display: none; }
            .content { margin-left: 60px; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <h3>Administration</h3>
            <ul>
                <li><a href="admin_dashboard.php" class="active">Tableau de bord</a></li>
                <li><a href="admin_users.php">Utilisateurs</a></li>
                <li><a href="admin_creations.php">Créations</a></li>
                <li><a href="admin_events.php">Événements</a></li>
                <li><a href="admin_comments.php">Commentaires</a></li>
                <?php if (isSuperAdmin()): ?>
                    <li><a href="admin_admins.php">Administrateurs</a></li>
                <?php endif; ?>
                <li><a href="logout.php" class="logout-btn">Déconnexion</a></li>
            </ul>
        </nav>

        <!-- Content -->
        <div class="content">
            <div class="header">
                <div>
                    <h1>Tableau de Bord Administrateur</h1>
                    <p style="margin: 5px 0 0 0; color: #666;">Bienvenue <?php echo htmlspecialchars($_SESSION['admin_nom']); ?> (<?php echo ucfirst($_SESSION['admin_role']); ?>)</p>
                </div>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>

            <!-- Statistiques -->
            <div class="dashboard-grid">
                <?php
                try {
                    // Nombre d'utilisateurs
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateurs");
                    $users_count = $stmt->fetch()['count'];
                    
                    // Nombre de créations
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM article");
                    $articles_count = $stmt->fetch()['count'];
                    
                    // Nombre d'événements
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM evenement");
                    $events_count = $stmt->fetch()['count'];
                    
                    // Utilisateurs suspendus
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateurs WHERE statut = 'suspendu'");
                    $suspended_count = $stmt->fetch()['count'];
                ?>
                    <div class="stat-card">
                        <h3>Utilisateurs</h3>
                        <div class="number"><?php echo $users_count; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Créations</h3>
                        <div class="number"><?php echo $articles_count; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Événements</h3>
                        <div class="number"><?php echo $events_count; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Comptes suspendus</h3>
                        <div class="number"><?php echo $suspended_count; ?></div>
                    </div>
                <?php
                } catch (PDOException $e) {
                    error_log("Dashboard stats error: " . $e->getMessage());
                }
                ?>
            </div>

            <!-- Panneaux d'administration -->
            <div class="admin-panel">
                <h2>Outils d'Administration</h2>
                <div class="button-group">
                    <?php if (hasAdminPermission('gerer_utilisateurs')): ?>
                        <a href="admin_users.php" class="btn-admin">Gérer les Utilisateurs</a>
                    <?php endif; ?>
                    
                    <?php if (hasAdminPermission('gerer_creations')): ?>
                        <a href="admin_creations.php" class="btn-admin">Gérer les Créations</a>
                    <?php endif; ?>
                    
                    <?php if (hasAdminPermission('gerer_evenements')): ?>
                        <a href="admin_events.php" class="btn-admin">Gérer les Événements</a>
                    <?php endif; ?>
                    
                    <?php if (isSuperAdmin()): ?>
                        <a href="admin_admins.php" class="btn-admin">Gérer les Administrateurs</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Utilisateurs récents suspendus -->
            <?php if (hasAdminPermission('gerer_utilisateurs')): ?>
            <div class="admin-panel" style="margin-top: 20px;">
                <h2>Comptes Suspendus Récemment</h2>
                <?php
                try {
                    $stmt = $pdo->query("
                        SELECT id, prenom, nom, email, statut, date_inscription 
                        FROM utilisateurs 
                        WHERE statut = 'suspendu' 
                        ORDER BY date_inscription DESC 
                        LIMIT 5
                    ");
                    $suspended = $stmt->fetchAll();
                    
                    if (count($suspended) > 0) {
                        echo '<table style="width: 100%; border-collapse: collapse;">';
                        echo '<tr style="background: #8D5524; color: white;"><th style="padding: 10px; text-align: left;">Nom</th><th style="padding: 10px; text-align: left;">Email</th><th style="padding: 10px; text-align: left;">Statut</th><th style="padding: 10px; text-align: left;">Actions</th></tr>';
                        foreach ($suspended as $user) {
                            echo '<tr style="border-bottom: 1px solid #ddd;">';
                            echo '<td style="padding: 10px;">' . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . '</td>';
                            echo '<td style="padding: 10px;">' . htmlspecialchars($user['email']) . '</td>';
                            echo '<td style="padding: 10px;"><span style="background: #ffebee; color: #c62828; padding: 5px 10px; border-radius: 5px;">' . ucfirst($user['statut']) . '</span></td>';
                            echo '<td style="padding: 10px;"><a href="admin_users.php?action=activate&id=' . $user['id'] . '" class="btn-admin" style="padding: 5px 10px; font-size: 0.9rem;">Réactiver</a></td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<p style="color: #666; font-style: italic;">Aucun compte suspendu</p>';
                    }
                } catch (PDOException $e) {
                    error_log("Suspended users query error: " . $e->getMessage());
                    echo '<p style="color: #d32f2f;">Erreur lors du chargement des données</p>';
                }
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
