<?php
session_start();
require_once 'auth.php';
require_once 'tresorsdemain.php';

requireAdmin();
requireAdminPermission('gerer_evenements');

$pdo = getConnection();
$message = '';
$message_type = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $event_id = (int)($_POST['event_id'] ?? 0);
    
    if ($action === 'create') {
        // Création d'un nouvel événement
        $titre = sanitize($_POST['titre'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $date_debut = sanitize($_POST['date_debut'] ?? '');
        $date_fin = sanitize($_POST['date_fin'] ?? '');
        $lieu = sanitize($_POST['lieu'] ?? '');
        $url_lieu = sanitize($_POST['url_lieu'] ?? '');
        
        // Gestion de l'image
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/creations/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'event_' . time() . '.' . $extension;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $image = $filename;
            }
        }
        
        if (empty($titre) || empty($date_debut) || empty($date_fin)) {
            $message = "Le titre et les dates sont obligatoires.";
            $message_type = "error";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO evenement (titre, description, date_debut, date_fin, lieu, url_lieu, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$titre, $description, $date_debut, $date_fin, $lieu, $url_lieu, $image]);
                $message = "Événement créé avec succès !";
                $message_type = "success";
            } catch (PDOException $e) {
                error_log("Create event error: " . $e->getMessage());
                $message = "Erreur lors de la création de l'événement.";
                $message_type = "error";
            }
        }
    } elseif ($action === 'edit' && $event_id) {
        // Modification d'un événement
        $titre = sanitize($_POST['titre'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $date_debut = sanitize($_POST['date_debut'] ?? '');
        $date_fin = sanitize($_POST['date_fin'] ?? '');
        $lieu = sanitize($_POST['lieu'] ?? '');
        $url_lieu = sanitize($_POST['url_lieu'] ?? '');
        
        if (empty($titre) || empty($date_debut) || empty($date_fin)) {
            $message = "Le titre et les dates sont obligatoires.";
            $message_type = "error";
        } else {
            try {
                // Gestion de l'image si une nouvelle est uploadée
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../assets/images/creations/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filename = 'event_' . time() . '.' . $extension;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                        $stmt = $pdo->prepare("UPDATE evenement SET titre=?, description=?, date_debut=?, date_fin=?, lieu=?, url_lieu=?, image=? WHERE id_evenement=?");
                        $stmt->execute([$titre, $description, $date_debut, $date_fin, $lieu, $url_lieu, $filename, $event_id]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE evenement SET titre=?, description=?, date_debut=?, date_fin=?, lieu=?, url_lieu=? WHERE id_evenement=?");
                    $stmt->execute([$titre, $description, $date_debut, $date_fin, $lieu, $url_lieu, $event_id]);
                }
                $message = "Événement modifié avec succès !";
                $message_type = "success";
            } catch (PDOException $e) {
                error_log("Edit event error: " . $e->getMessage());
                $message = "Erreur lors de la modification.";
                $message_type = "error";
            }
        }
    } elseif ($action === 'delete' && $event_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM evenement WHERE id_evenement = ?");
            $stmt->execute([$event_id]);
            $message = "Événement supprimé avec succès.";
            $message_type = "success";
        } catch (PDOException $e) {
            error_log("Delete event error: " . $e->getMessage());
            $message = "Erreur lors de la suppression.";
            $message_type = "error";
        }
    }
}

// Récupérer les événements
$search = sanitize($_GET['search'] ?? '');

$query = "SELECT * FROM evenement WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (titre LIKE ? OR lieu LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " ORDER BY date_debut DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements - Admin</title>
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/projet.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #2C1810; color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; z-index: 100; left: 0; top: 0; }
        .sidebar h3 { color: #C58F5E; margin-top: 0; padding-bottom: 15px; border-bottom: 2px solid #8D5524; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar li { margin: 10px 0; }
        .sidebar a { color: #E0D5CE; text-decoration: none; display: block; padding: 10px 15px; border-radius: 8px; transition: all 0.3s; }
        .sidebar a:hover { background: #8D5524; color: white; }
        .sidebar a.active { background: #C58F5E; color: white; font-weight: bold; }
        .content { margin-left: 250px; flex: 1; padding: 20px; background: #F5F1ED; min-height: 100vh; width: calc(100% - 250px); }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { margin: 0; color: #2C1810; }
        .logout-btn { background: #d32f2f; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Formulaire création */
        .form-section { background: white; padding: 25px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-section h2 { color: #8D5524; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #333; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; 
        }
        .form-group input:focus, .form-group textarea:focus { border-color: #8D5524; outline: none; }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group.full-width { grid-column: 1 / -1; }
        .btn-submit { background: #8D5524; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 1rem; }
        .btn-submit:hover { background: #C58F5E; }
        
        /* Filtres */
        .filters { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .filters input { padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 1rem; flex: 1; min-width: 200px; }
        .filters button { background: #8D5524; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        
        /* Tableau */
        .events-table { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .events-table table { width: 100%; border-collapse: collapse; }
        .events-table th { background: #8D5524; color: white; padding: 15px; text-align: left; }
        .events-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; vertical-align: top; }
        .events-table tr:hover { background: #f9f5f1; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .btn-sm { padding: 8px 12px; font-size: 0.85rem; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #d32f2f; color: white; }
        .btn-primary { background: #1976d2; color: white; }
        .btn-sm:hover { opacity: 0.8; }
        .event-image { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 30px; border-radius: 10px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { margin: 0; color: #8D5524; }
        .close { font-size: 28px; cursor: pointer; color: #666; }
        .close:hover { color: #333; }
        
        @media (max-width: 768px) {
            .sidebar { width: 60px; padding: 10px; }
            .sidebar h3, .sidebar span { display: none; }
            .content { margin-left: 60px; width: calc(100% - 60px); }
        }
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
                <li><a href="admin_events.php" class="active">Événements</a></li>
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
                <h1>Gestion des Événements</h1>
                <form action="logout.php" method="post" style="margin: 0;">
                    <button type="submit" class="logout-btn">Déconnexion</button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Formulaire de création -->
            <div class="form-section">
                <h2>➕ Créer un nouvel événement</h2>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Titre *</label>
                            <input type="text" name="titre" required placeholder="Nom de l'événement">
                        </div>
                        <div class="form-group">
                            <label>Date de début *</label>
                            <input type="date" name="date_debut" required>
                        </div>
                        <div class="form-group">
                            <label>Date de fin *</label>
                            <input type="date" name="date_fin" required>
                        </div>
                        <div class="form-group">
                            <label>Lieu</label>
                            <input type="text" name="lieu" placeholder="Adresse de l'événement">
                        </div>
                        <div class="form-group">
                            <label>URL Google Maps</label>
                            <input type="url" name="url_lieu" placeholder="https://www.google.com/maps/...">
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input type="file" name="image" accept="image/*">
                        </div>
                        <div class="form-group full-width">
                            <label>Description</label>
                            <textarea name="description" placeholder="Description de l'événement..."></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-submit">Créer l'événement</button>
                </form>
            </div>

            <!-- Filtres -->
            <div class="filters">
                <form method="get" style="display: flex; gap: 10px; flex-wrap: wrap; width: 100%;">
                    <input type="text" name="search" placeholder="Rechercher par titre, lieu..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">Rechercher</button>
                    <?php if ($search): ?>
                        <a href="admin_events.php" style="padding: 10px 20px; background: #666; color: white; border-radius: 5px; text-decoration: none;">Réinitialiser</a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tableau des événements -->
            <div class="events-table">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Dates</th>
                            <th>Lieu</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($event['image'])): ?>
                                        <img src="../assets/images/creations/<?php echo htmlspecialchars($event['image']); ?>" alt="" class="event-image" onerror="this.src='../assets/images/default-event.jpg'">
                                    <?php else: ?>
                                        <div style="width:80px;height:60px;background:#ddd;border-radius:5px;display:flex;align-items:center;justify-content:center;color:#999;">-</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($event['titre']); ?></strong>
                                    <?php if (!empty($event['description'])): ?>
                                        <br><small style="color:#666;"><?php echo htmlspecialchars(substr($event['description'], 0, 80)); ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($event['date_debut'])); ?>
                                    <?php if ($event['date_debut'] !== $event['date_fin']): ?>
                                        <br>→ <?php echo date('d/m/Y', strtotime($event['date_fin'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($event['lieu']); ?>
                                    <?php if (!empty($event['url_lieu'])): ?>
                                        <br><a href="<?php echo htmlspecialchars($event['url_lieu']); ?>" target="_blank" style="font-size:0.85rem;">Voir sur Maps</a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button type="button" class="btn-sm btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($event)); ?>)">Modifier</button>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id_evenement']; ?>">
                                            <button type="submit" class="btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (count($events) === 0): ?>
                    <div style="padding: 30px; text-align: center; color: #666;">
                        <p style="font-size: 1.2rem;">Aucun événement trouvé.</p>
                        <p>Créez votre premier événement avec le formulaire ci-dessus !</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Modifier l'événement</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="event_id" id="edit_event_id">
                <div class="form-group">
                    <label>Titre *</label>
                    <input type="text" name="titre" id="edit_titre" required>
                </div>
                <div class="form-group">
                    <label>Date de début *</label>
                    <input type="date" name="date_debut" id="edit_date_debut" required>
                </div>
                <div class="form-group">
                    <label>Date de fin *</label>
                    <input type="date" name="date_fin" id="edit_date_fin" required>
                </div>
                <div class="form-group">
                    <label>Lieu</label>
                    <input type="text" name="lieu" id="edit_lieu">
                </div>
                <div class="form-group">
                    <label>URL Google Maps</label>
                    <input type="url" name="url_lieu" id="edit_url_lieu">
                </div>
                <div class="form-group">
                    <label>Nouvelle image (optionnel)</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description"></textarea>
                </div>
                <button type="submit" class="btn-submit">Enregistrer les modifications</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(event) {
            document.getElementById('edit_event_id').value = event.id_evenement;
            document.getElementById('edit_titre').value = event.titre;
            document.getElementById('edit_date_debut').value = event.date_debut;
            document.getElementById('edit_date_fin').value = event.date_fin;
            document.getElementById('edit_lieu').value = event.lieu || '';
            document.getElementById('edit_url_lieu').value = event.url_lieu || '';
            document.getElementById('edit_description').value = event.description || '';
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Fermer le modal en cliquant en dehors
        window.onclick = function(e) {
            if (e.target == document.getElementById('editModal')) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
