<?php
// Démarrer la session si elle n'est pas active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Récupérer le nom du fichier actuel
$current_page = basename($_SERVER['PHP_SELF']);

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$userName = $isLoggedIn ? htmlspecialchars(($_SESSION['user_prenom'] ?? '') . ' ' . ($_SESSION['user_nom'] ?? '')) : '';
$userType = $_SESSION['user_type'] ?? null;
$isArtisan = $userType === 'artisan';
$userPhoto = $_SESSION['user_photo'] ?? '';
$defaultAvatar = '/Projet-Tr-sor-de-Main/assets/images/default-avatar.svg';
$avatarSrc = !empty($userPhoto) ? '/Projet-Tr-sor-de-Main/' . $userPhoto : $defaultAvatar;
?>
<!DOCTYPE html>
<html lang="fr-FR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Le site web de tous les artisans">
    <link rel="stylesheet" href="/Projet-Tr-sor-de-Main/CSS/HeaderFooter.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Special+Elite&display=swap" rel="stylesheet">
    <style>
        .user-menu { position: relative; display: inline-block; }
        .user-btn { background: linear-gradient(135deg, #8D5524, #C58F5E); color: white; border: none; padding: 6px 16px 6px 6px; border-radius: 25px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .user-btn:hover { background: linear-gradient(135deg, #C58F5E, #8D5524); }
        .user-btn .user-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3); }
        .user-dropdown { display: none; position: absolute; right: 0; top: 100%; background: #FFFFFF; min-width: 220px; box-shadow: 0 8px 25px rgba(0,0,0,0.25); border-radius: 12px; overflow: hidden; z-index: 1000; margin-top: 8px; border: 2px solid #8D5524; }
        .user-dropdown.show { display: block; }
        .user-dropdown a { display: block; padding: 14px 20px; color: #1a1a1a !important; text-decoration: none; transition: all 0.2s; font-weight: 600; font-size: 1rem; border-left: 4px solid transparent; background: #FFFFFF; }
        .user-dropdown a:hover { background: #F5E6D3; color: #000000 !important; border-left: 4px solid #8D5524; }
        .user-dropdown hr { margin: 0; border: none; border-top: 2px solid #8D5524; }
        .role-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; margin-left: 5px; }
        .role-badge.artisan { background: rgba(141,85,36,0.15); color: #8D5524; }
        .role-badge.client { background: rgba(74,144,164,0.15); color: #4A90A4; }
    </style>
</head>
<body>
    <!-- Menu Desktop - Toujours visible sur grand écran -->
<header>
    <img src="/Projet-Tr-sor-de-Main/assets/images/Logo Site détouré.png" alt="Logo Trésor de Main" width="60">
    <h1>Trésor de Main</h1>
    <nav class="menu-desktop">
        <a href="Page_Acceuil.php" <?php echo ($current_page == 'Page_Acceuil.php') ? 'class="active"' : ''; ?>>Accueil</a>
        <a href="All_Products.php" <?php echo ($current_page == 'All_Products.php') ? 'class="active"' : ''; ?>>Créations</a>
        <a href="evenements.php" <?php echo ($current_page == 'evenements.php') ? 'class="active"' : ''; ?>>Événements</a>
        <a href="a-propos.php" <?php echo ($current_page == 'a-propos.php') ? 'class="active"' : ''; ?>>À propos</a>
        <a href="Contact.php" <?php echo ($current_page == 'Contact.php') ? 'class="active"' : ''; ?>>Contact</a>
        
        <?php if ($isLoggedIn): ?>
            <!-- Menu utilisateur connecté -->
            <div class="user-menu">
                <button class="user-btn" onclick="toggleUserMenu()">
                    <img src="<?php echo htmlspecialchars($avatarSrc); ?>" alt="Photo de profil" class="user-avatar">
                    <?php echo trim($userName) ?: 'Mon compte'; ?>
                    <span class="role-badge <?php echo $isArtisan ? 'artisan' : 'client'; ?>">
                        <?php echo $isArtisan ? 'Artisan' : 'Client'; ?>
                    </span>
                    ▼
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="MonCompte.php">🏠 Mon Compte</a>
                    <?php if ($isArtisan): ?>
                        <a href="gestion_creations.php">Mes Créations</a>
                        <a href="gestion_creations.php?mode=add">➕ Ajouter une création</a>
                    <?php else: ?>
                        <a href="mes_favoris.php">Mes Favoris</a>
                    <?php endif; ?>
                    <a href="edit_profile.php">⚙️ Paramètres</a>
                    <hr>
                    <a href="logout.php">🚪 Déconnexion</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Liens connexion/inscription pour visiteurs -->
            <a href="login.php" class="<?php echo in_array($current_page, ['login.php', 'inscription.php']) ? 'active' : ''; ?>">Connexion</a>
        <?php endif; ?>
    </nav>
    <button class="btn-menu-mobile" onclick="toggleMobileMenu()">Menu ▼</button>
</header>

<nav class="menu-mobile" id="menuMobile">
    <a href="Page_Acceuil.php">Accueil</a>
    <a href="All_Products.php">Créations</a>
    <a href="evenements.php">Événements</a>
    <a href="a-propos.php">À propos</a>
    <a href="Contact.php">Contact</a>
    <?php if ($isLoggedIn): ?>
        <a href="MonCompte.php">Mon Compte</a>
        <?php if ($isArtisan): ?>
            <a href="gestion_creations.php">Mes Créations</a>
        <?php else: ?>
            <a href="mes_favoris.php">Mes Favoris</a>
        <?php endif; ?>
        <a href="logout.php">Déconnexion</a>
    <?php else: ?>
        <a href="login.php">Connexion</a>
        <a href="inscription.php">Inscription</a>
    <?php endif; ?>
</nav>

<script>
function toggleUserMenu() {
    document.getElementById('userDropdown').classList.toggle('show');
}
// Fermer le menu si on clique ailleurs
document.addEventListener('click', function(e) {
    if (!e.target.closest('.user-menu')) {
        var dropdown = document.getElementById('userDropdown');
        if (dropdown) dropdown.classList.remove('show');
    }
});
</script>
