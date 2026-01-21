<?php
session_start();
require_once 'tresorsdemain.php';
require_once 'auth.php';

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: MonCompte.php');
    exit();
}

$error = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données
    $nom = sanitize($_POST['nom']);
    $prenom = sanitize($_POST['prenom']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $type_compte = isset($_POST['type_compte']) ? sanitize($_POST['type_compte']) : 'acheteur';
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Adresse email invalide.";
    } elseif ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Validation du mot de passe sécurisé
        $passwordValidation = validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors = $passwordValidation['errors'];
            $error = implode('<br>', $errors);
        }
    }
    
    if (empty($error)) {
        try {
            $pdo = getConnection();
            
            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Cet email est déjà utilisé.";
            } else {
                // Hasher le mot de passe
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insérer l'utilisateur
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mdp, type_compte, date_inscription) VALUES (?, ?, ?, ?, ?, NOW())");
                
                if ($stmt->execute([$nom, $prenom, $email, $password_hash, $type_compte])) {
                    // Récupérer l'ID et créer la session
                    $user_id = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_type'] = $type_compte;
                    
                    $success = true;
                } else {
                    $error = "Erreur lors de la création du compte. Veuillez réessayer.";
                }
            }
        } catch (PDOException $e) {
            error_log("Inscription error: " . $e->getMessage());
            $error = "Erreur base de données: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Trésor de Main</title>
    <link rel="stylesheet" href="/CSS/HeaderFooter.css">
    <link rel="stylesheet" href="/CSS/projet.css">
    <style>
        .page-container { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1; padding: 40px 20px; display: flex; justify-content: center; align-items: center; }
        .form-container { background: rgba(255,255,255,0.95); max-width: 500px; width: 100%; padding: 40px; border-radius: 20px; box-shadow: 0 15px 40px rgba(62,39,35,0.1); border: 1px solid rgba(141,85,36,0.2); }
        .form-container h2 { color: #8D5524; text-align: center; margin-bottom: 30px; font-size: 2rem; }
        .message { padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-inscription label { display: block; margin: 15px 0 5px; color: #3E2723; font-weight: 500; }
        .form-inscription input, .form-inscription select { width: 100%; padding: 12px 15px; margin-bottom: 20px; border: 2px solid #E6E2DD; border-radius: 10px; font-size: 1rem; transition: all 0.3s ease; }
        .form-inscription input:focus, .form-inscription select:focus { border-color: #8D5524; outline: none; box-shadow: 0 0 0 3px rgba(141,85,36,0.1); }
        .radio-group { display: flex; gap: 20px; margin: 10px 0 20px; }
        .radio-group label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .radio-group input[type="radio"] { width: auto; margin: 0; }
        .btn-submit { background: #8D5524; color: white; border: none; padding: 15px 30px; border-radius: 50px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: all 0.3s ease; width: 100%; margin-top: 20px; }
        .btn-submit:hover { background: #C58F5E; transform: translateY(-3px); box-shadow: 0 10px 20px rgba(141,85,36,0.2); }
        .login-link { text-align: center; margin-top: 25px; color: #666; }
        .login-link a { color: #8D5524; text-decoration: none; font-weight: bold; }
        .login-link a:hover { text-decoration: underline; }
        
        /* Styles pour l'indicateur de force du mot de passe */
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin: -10px 0 20px 0;
            border: 1px solid #e9ecef;
        }
        .strength-bar {
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 4px;
        }
        .strength-text {
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0 0 10px 0;
            text-align: center;
        }
        .requirements-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.85rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
        }
        .requirements-list li {
            color: #6c757d;
            transition: all 0.2s;
        }
        .requirements-list li.valid {
            color: #28a745;
        }
        .requirements-list li .icon {
            margin-right: 5px;
            font-weight: bold;
        }
        .password-match {
            font-size: 0.85rem;
            margin: -15px 0 15px 0;
            font-weight: 500;
        }
        
        @media (max-width: 768px) { 
            .form-container { margin: 20px; padding: 25px; }
            .requirements-list { grid-template-columns: 1fr; } 
            main { padding: 20px 10px; }
            .radio-group { flex-direction: column; gap: 10px; }
        }
    </style>
</head>
<body>
    <div class="page-container">
		<!-- Header -->
		<?php include '../php/header.php'; ?>

        <main>
            <div class="form-container">
                <h2>Créer un compte</h2>
                
                <?php if ($error): ?>
                    <div class="message error"><?php echo $error; ?></div>
                <?php elseif ($success): ?>
                    <div class="message success">Compte créé avec succès ! Redirection vers votre compte...</div>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'MonCompte.php';
                        }, 2000);
                    </script>
                <?php endif; ?>
                
                <?php if (!$success): ?>
                <form action="" method="post" class="form-inscription">
                    <fieldset>
                        <legend>Vous êtes :</legend>
                        <div class="radio-group">
                            <label class="role-option">
                                <input type="radio" name="type_compte" value="artisan" required>
                                <div class="role-card">
                                    <span class="role-title">Artisan</span>
                                    <span class="role-desc">Je veux vendre mes créations</span>
                                </div>
                            </label>
                            <label class="role-option">
                                <input type="radio" name="type_compte" value="acheteur" required checked>
                                <div class="role-card">
                                    <span class="role-title">Acheteur</span>
                                    <span class="role-desc">Je veux découvrir des créations</span>
                                </div>
                            </label>
                        </div>
                    </fieldset>
                    
                    <label>Nom :</label>
                    <input type="text" name="nom" placeholder="Votre nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                    
                    <label>Prénom :</label>
                    <input type="text" name="prenom" placeholder="Votre prénom" value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required>
                    
                    <label>Email :</label>
                    <input type="email" name="email" placeholder="votre@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    
                    <label>Mot de passe :</label>
                    <input type="password" name="password" id="password" placeholder="Créez un mot de passe sécurisé" required oninput="checkPasswordStrength()">
                    
                    <!-- Indicateur de force du mot de passe -->
                    <div class="password-requirements">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <p class="strength-text" id="strengthText">Force du mot de passe</p>
                        <ul class="requirements-list">
                            <li id="req-length"><span class="icon">○</span> Au moins 8 caractères</li>
                            <li id="req-upper"><span class="icon">○</span> Une lettre majuscule</li>
                            <li id="req-lower"><span class="icon">○</span> Une lettre minuscule</li>
                            <li id="req-number"><span class="icon">○</span> Un chiffre</li>
                            <li id="req-special"><span class="icon">○</span> Un caractère spécial (!@#$%...)</li>
                        </ul>
                    </div>
                    
                    <label>Confirmer le mot de passe :</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirmez votre mot de passe" required oninput="checkPasswordMatch()">
                    <p class="password-match" id="passwordMatch"></p>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">Créer mon compte</button>
                </form>
                <?php endif; ?>
                
                <div class="login-link">
                    <p>Vous avez déjà un compte ? <a href="login.php">Se connecter</a></p>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include '../HTML/footer.html'; ?>

    </div>
    <script src="script.js"></script>
    <script>
        // Validation du mot de passe en temps réel
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            const requirements = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
            };
            
            // Mettre à jour les indicateurs visuels
            Object.keys(requirements).forEach(req => {
                const el = document.getElementById('req-' + req);
                if (requirements[req]) {
                    el.classList.add('valid');
                    el.querySelector('.icon').textContent = '✓';
                    strength++;
                } else {
                    el.classList.remove('valid');
                    el.querySelector('.icon').textContent = '○';
                }
            });
            
            // Bonus pour longueur >= 12
            if (password.length >= 12) strength++;
            
            // Mettre à jour la barre de force
            const percentage = (strength / 6) * 100;
            strengthFill.style.width = percentage + '%';
            
            // Couleur et texte selon la force
            const levels = [
                { text: 'Très faible', color: '#dc3545' },
                { text: 'Faible', color: '#fd7e14' },
                { text: 'Moyen', color: '#ffc107' },
                { text: 'Bon', color: '#20c997' },
                { text: 'Fort', color: '#28a745' },
                { text: 'Très fort', color: '#155724' }
            ];
            
            const level = Math.min(strength, 5);
            strengthFill.style.backgroundColor = levels[level].color;
            strengthText.textContent = levels[level].text;
            strengthText.style.color = levels[level].color;
            
            checkPasswordMatch();
            updateSubmitButton();
        }
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const matchText = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchText.textContent = '';
            } else if (password === confirm) {
                matchText.textContent = '✓ Les mots de passe correspondent';
                matchText.style.color = '#28a745';
            } else {
                matchText.textContent = '✗ Les mots de passe ne correspondent pas';
                matchText.style.color = '#dc3545';
            }
            updateSubmitButton();
        }
        
        function updateSubmitButton() {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const btn = document.getElementById('submitBtn');
            
            const isValid = password.length >= 8 &&
                           /[A-Z]/.test(password) &&
                           /[a-z]/.test(password) &&
                           /[0-9]/.test(password) &&
                           /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password) &&
                           password === confirm;
            
            btn.disabled = !isValid;
            btn.style.opacity = isValid ? '1' : '0.6';
            btn.style.cursor = isValid ? 'pointer' : 'not-allowed';
        }
        
        // Initialiser au chargement
        document.addEventListener('DOMContentLoaded', updateSubmitButton);
    </script>
</body>
</html>