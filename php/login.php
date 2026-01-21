<?php
session_start();
require_once 'config.php';
require_once 'tresorsdemain.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id']) || isset($_SESSION['admin_id'])) {
    if (isset($_SESSION['admin_id'])) {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: MonCompte.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $login_type = isset($_POST['login_type']) ? sanitize($_POST['login_type']) : 'user';
    
    // Validation basique
    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            $pdo = getConnection();
            
            if ($login_type === 'admin') {
                // Connexion administrateur
                $stmt = $pdo->prepare("SELECT id_admin, nom, email, password, role, permissions, actif FROM administrateur WHERE email = ? AND actif = TRUE");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    // Créer la session administrateur
                    $_SESSION['admin_id'] = $admin['id_admin'];
                    $_SESSION['admin_nom'] = $admin['nom'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_permissions'] = json_decode($admin['permissions'], true) ?? [];
                    
                    // Mettre à jour la dernière connexion
                    $updateStmt = $pdo->prepare("UPDATE administrateur SET derniere_connexion = NOW() WHERE id_admin = ?");
                    $updateStmt->execute([$admin['id_admin']]);
                    
                    // Redirection
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    $error = "Email ou mot de passe administrateur incorrect.";
                }
            } else {
                // Connexion utilisateur
                $stmt = $pdo->prepare("SELECT id, nom, prenom, email, mdp, type_compte, photo_profil FROM utilisateurs WHERE email = ? AND statut = 'actif'");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['mdp'])) {
                    // Créer la session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_prenom'] = $user['prenom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_type'] = $user['type_compte'];
                    $_SESSION['user_photo'] = $user['photo_profil'];
                    
                    // Mettre à jour la dernière connexion
                    $updateStmt = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Redirection
                    $redirect = isset($_SESSION['redirect_url']) ? $_SESSION['redirect_url'] : 'MonCompte.php';
                    unset($_SESSION['redirect_url']);
                    header('Location: ' . $redirect);
                    exit();
                } else {
                    $error = "Email ou mot de passe incorrect.";
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Trésor de Main</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/HeaderFooter.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/projet.css">
    <style>
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .form-container { background: rgba(255,255,255,0.95); max-width: 450px; width: 100%; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(62,39,35,0.1); border: 1px solid rgba(141,85,36,0.2); }
        .form-container h2 { color: #8D5524; text-align: center; margin-bottom: 30px; font-size: 2rem; }
        .tabs { display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #E6E2DD; }
        .tab { padding: 12px 20px; cursor: pointer; color: #999; border: none; background: none; font-weight: 500; font-size: 1rem; transition: all 0.3s; }
        .tab.active { color: #8D5524; border-bottom: 3px solid #8D5524; margin-bottom: -2px; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .error-message { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb; text-align: center; }
        .form-connexion label { display: block; margin: 15px 0 5px; color: #3E2723; font-weight: 500; }
        .form-connexion input { width: 100%; padding: 12px 15px; margin-bottom: 20px; border: 2px solid #E6E2DD; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; box-sizing: border-box; }
        .form-connexion input:focus { border-color: #8D5524; outline: none; box-shadow: 0 0 0 3px rgba(141,85,36,0.1); }
        .btn-submit { background: #8D5524; color: white; border: none; padding: 15px 30px; border-radius: 50px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: all 0.3s ease; width: 100%; margin-top: 10px; }
        .btn-submit:hover { background: #C58F5E; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(141,85,36,0.2); }
        .register-link { text-align: center; margin-top: 25px; color: #666; }
        .register-link a { color: #8D5524; text-decoration: none; font-weight: bold; }
        .register-link a:hover { text-decoration: underline; }
        @media (max-width: 768px) { .form-container { margin: 20px; padding: 25px; } main { padding: 20px 10px; } }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <?php include 'header.php'; ?>

        <main>
        <div class="form-container">
            <h2>Connexion</h2>
            
            <div class="tabs">
                <button class="tab active" onclick="switchTab('user')">Utilisateur</button>
                <button class="tab" onclick="switchTab('admin')">Administrateur</button>
            </div>

            <?php if ($error): ?><div class="error-message"><?php echo $error; ?></div><?php endif; ?>
            
            <!-- Onglet Utilisateur -->
            <div id="user" class="tab-content active">
                <form method="post" action="" class="form-connexion">
                    <input type="hidden" name="login_type" value="user">
                    <label>Email :</label>
                    <input type="email" name="email" placeholder="votre@email.com" required>
                    <label>Mot de passe :</label>
                    <input type="password" name="password" placeholder="Votre mot de passe" required>
                    <button type="submit" name="login" class="btn-submit">Se connecter</button>
                </form>
                <div class="register-link"><p>Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p></div>
            </div>

            <!-- Onglet Administrateur -->
            <div id="admin" class="tab-content">
                <form method="post" action="" class="form-connexion">
                    <input type="hidden" name="login_type" value="admin">
                    <label>Email administrateur :</label>
                    <input type="email" name="email" placeholder="admin@email.com" required>
                    <label>Mot de passe :</label>
                    <input type="password" name="password" placeholder="Votre mot de passe" required>
                    <button type="submit" name="login" class="btn-submit">Se connecter (Admin)</button>
                </form>
            </div>
        </div>
    </main>

        <!-- Footer-->
        <?php include '../HTML/footer.html'; ?>
    </div>
    <script>
        function switchTab(tabName) {
            // Masquer tous les onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
