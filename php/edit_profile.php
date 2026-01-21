<?php
/**
 * ============================================================================
 * MODIFICATION DU PROFIL - Version améliorée avec champs artisan
 * ============================================================================
 */

session_start();
require_once 'tresorsdemain.php';
require_once 'auth.php';

// Redirection si non connecté
requireLogin();

$pdo = getConnection();
$userId = getUserId();

// Récupérer les infos utilisateur
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
$error = '';

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Vérification CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $prenom = sanitize($_POST['prenom'] ?? '');
        $nom = sanitize($_POST['nom'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $ville = sanitize($_POST['ville'] ?? '');
        $telephone = sanitize($_POST['telephone'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        
        // Champs spécifiques artisan
        $specialite = $isArtisan ? sanitize($_POST['specialite'] ?? '') : null;
        $site_web = $isArtisan ? sanitize($_POST['site_web'] ?? '') : null;
        $instagram = $isArtisan ? sanitize($_POST['instagram'] ?? '') : null;
        $facebook = $isArtisan ? sanitize($_POST['facebook'] ?? '') : null;
        
        // Validation
        if (empty($prenom) || empty($nom) || empty($email)) {
            $error = "Les champs Prénom, Nom et Email sont obligatoires.";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "L'adresse email n'est pas valide.";
        } else if ($site_web && !empty($site_web) && !filter_var($site_web, FILTER_VALIDATE_URL)) {
            $error = "L'URL du site web n'est pas valide.";
        } else {
            try {
                // Vérifier que l'email n'existe pas ailleurs
                $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $error = "Cet email est déjà utilisé par un autre utilisateur.";
                } else {
                    // Construire la requête selon le type de compte
                    if ($isArtisan) {
                        $sql = "UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, ville = ?, 
                                telephone = ?, description = ?, specialite = ?, site_web = ?, 
                                instagram = ?, facebook = ? WHERE id = ?";
                        $params = [$prenom, $nom, $email, $ville, $telephone, $description,
                                   $specialite, $site_web, $instagram, $facebook, $userId];
                    } else {
                        $sql = "UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, ville = ?, 
                                telephone = ?, description = ? WHERE id = ?";
                        $params = [$prenom, $nom, $email, $ville, $telephone, $description, $userId];
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute($params)) {
                        // Mettre à jour la session
                        $_SESSION['user_prenom'] = $prenom;
                        $_SESSION['user_nom'] = $nom;
                        $_SESSION['user_email'] = $email;
                        
                        setFlashMessage('Votre profil a été mis à jour avec succès !', 'success');
                        header('Location: MonCompte.php');
                        exit();
                    } else {
                        $error = "Une erreur est survenue lors de la mise à jour.";
                    }
                }
            } catch (PDOException $e) {
                error_log("Edit profile error: " . $e->getMessage());
                // Si la colonne n'existe pas, essayer sans elle
                if (strpos($e->getMessage(), 'Unknown column') !== false) {
                    try {
                        $sql = "UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, ville = ?, description = ? WHERE id = ?";
                        $stmt = $pdo->prepare($sql);
                        if ($stmt->execute([$prenom, $nom, $email, $ville, $description, $userId])) {
                            $_SESSION['user_prenom'] = $prenom;
                            $_SESSION['user_nom'] = $nom;
                            $_SESSION['user_email'] = $email;
                            setFlashMessage('Profil mis à jour (certains champs non disponibles).', 'success');
                            header('Location: MonCompte.php');
                            exit();
                        }
                    } catch (PDOException $e2) {
                        $error = "Erreur lors de la mise à jour.";
                    }
                } else {
                    $error = "Une erreur est survenue.";
                }
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon Profil - Trésor de Main</title>
    <link rel="stylesheet" href="/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/CSS/projet.css">
    <style>
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 40px 20px; background: #FFF8F0; }
        .edit-profile-container { max-width: 700px; margin: 0 auto; }
        .edit-profile-card { background: rgba(255,255,255,0.95); padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(62,39,35,0.1); border: 1px solid rgba(141,85,36,0.2); }
        .edit-profile-card h2 { color: #8D5524; margin-bottom: 30px; text-align: center; }
        .edit-profile-card h2:after { width: 100px; }
        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; margin-bottom: 8px; color: #3E2723; font-weight: 600; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px 15px; border: 2px solid #E6E2DD; border-radius: 8px; font-size: 1rem; font-family: inherit; transition: all 0.3s ease; box-sizing: border-box; }
        .form-group input:focus, .form-group textarea:focus { border-color: #8D5524; outline: none; box-shadow: 0 0 0 3px rgba(141,85,36,0.1); }
        .form-group textarea { min-height: 120px; resize: vertical; }
        .form-group .help-text { font-size: 0.9rem; color: #666; margin-top: 5px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb; }
        .button-group { display: flex; gap: 15px; justify-content: center; margin-top: 30px; }
        .save-btn { background: #8D5524; color: white; padding: 12px 40px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; }
        .save-btn:hover { background: #C58F5E; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(141,85,36,0.2); }
        .cancel-btn { background: #6c757d; color: white; padding: 12px 40px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block; text-align: center; }
        .cancel-btn:hover { background: #5a6268; transform: translateY(-3px); }
        
        /* Section Artisan */
        .artisan-section { margin-top: 30px; padding-top: 30px; border-top: 2px dashed #E6E2DD; }
        .artisan-section h3 { color: #8D5524; margin-bottom: 20px; font-size: 1.2rem; }
        
        @media (max-width: 768px) {
            main { padding: 20px 10px; }
            .edit-profile-card { padding: 25px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <?php include 'header.php'; ?>

        <main>
            <div class="edit-profile-container">
                <div class="edit-profile-card">
                    <h2>Modifier mon Profil</h2>
                    
                    <?php displayFlashMessage(); ?>
                    
                    <?php if ($error): ?>
                        <div class="error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="prenom">Prénom *</label>
                                <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="nom">Nom *</label>
                                <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            <div class="help-text">Utilisé pour la connexion et les notifications</div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="ville">Ville</label>
                                <input type="text" id="ville" name="ville" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="telephone">Téléphone</label>
                                <input type="tel" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">À propos de moi</label>
                            <textarea id="description" name="description" placeholder="Décrivez-vous..."><?php echo htmlspecialchars($user['description'] ?? ''); ?></textarea>
                            <div class="help-text">Cela sera visible sur votre profil public</div>
                        </div>
                        
                        <?php if ($isArtisan): ?>
                        <!-- Champs spécifiques artisan -->
                        <div class="artisan-section">
                            <h3>Informations Artisan</h3>
                            
                            <div class="form-group">
                                <label for="specialite">Spécialité / Métier</label>
                                <input type="text" id="specialite" name="specialite" 
                                       value="<?php echo htmlspecialchars($user['specialite'] ?? ''); ?>" 
                                       placeholder="Ex: Céramiste, Bijoutier, Ébéniste...">
                            </div>
                            
                            <div class="form-group">
                                <label for="site_web">Site web</label>
                                <input type="url" id="site_web" name="site_web" 
                                       value="<?php echo htmlspecialchars($user['site_web'] ?? ''); ?>" 
                                       placeholder="https://votresite.com">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="instagram">Instagram</label>
                                    <input type="text" id="instagram" name="instagram" 
                                           value="<?php echo htmlspecialchars($user['instagram'] ?? ''); ?>" 
                                           placeholder="@votre_compte">
                                </div>
                                <div class="form-group">
                                    <label for="facebook">Facebook</label>
                                    <input type="text" id="facebook" name="facebook" 
                                           value="<?php echo htmlspecialchars($user['facebook'] ?? ''); ?>" 
                                           placeholder="URL ou nom de page">
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="button-group">
                            <button type="submit" class="save-btn">Enregistrer les modifications</button>
                            <a href="MonCompte.php" class="cancel-btn">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Footer-->
        <?php include '../HTML/footer.html'; ?>
        
    </div>
    <script src="../JavaScript/script.js"></script>
</body>
</html>