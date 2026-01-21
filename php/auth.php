<?php
/**
 * ============================================================================
 * AUTH.PHP - Système d'authentification centralisé
 * ============================================================================
 * 
 * Ce fichier gère toute l'authentification et les rôles utilisateurs.
 * À inclure dans toutes les pages nécessitant un contrôle d'accès.
 * 
 * @author Trésor de Main
 * @version 1.0
 */

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données
require_once __DIR__ . '/tresorsdemain.php';

// ============================================================================
// FONCTIONS D'AUTHENTIFICATION
// ============================================================================

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est un artisan
 * @return bool
 */
function isArtisan(): bool {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artisan';
}

/**
 * Vérifie si l'utilisateur est un client (acheteur)
 * @return bool
 */
function isClient(): bool {
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'acheteur';
}

/**
 * Récupère l'ID de l'utilisateur connecté
 * @return int|null
 */
function getUserId(): ?int {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Vérifie si l'utilisateur est un administrateur
 * @return bool
 */
function isAdmin(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Vérifie si l'utilisateur est un super administrateur
 * @return bool
 */
function isSuperAdmin(): bool {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin';
}

/**
 * Vérifie si l'administrateur a une permission spécifique
 * @param string $permission
 * @return bool
 */
function hasAdminPermission(string $permission): bool {
    if (!isAdmin()) return false;
    
    if (isSuperAdmin()) return true;
    
    if (!isset($_SESSION['admin_permissions'])) return false;
    
    $permissions = $_SESSION['admin_permissions'];
    return isset($permissions[$permission]) && $permissions[$permission] === true;
}

/**
 * Récupère le type de compte de l'utilisateur
 * @return string|null
 */
function getUserType(): ?string {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : null;
}

/**
 * Récupère le nom complet de l'utilisateur
 * @return string
 */
function getUserFullName(): string {
    if (!isLoggedIn()) return 'Visiteur';
    $prenom = $_SESSION['user_prenom'] ?? '';
    $nom = $_SESSION['user_nom'] ?? '';
    return trim("$prenom $nom") ?: 'Utilisateur';
}

/**
 * Récupère l'email de l'utilisateur
 * @return string|null
 */
function getUserEmail(): ?string {
    return $_SESSION['user_email'] ?? null;
}

// ============================================================================
// FONCTIONS DE CONTRÔLE D'ACCÈS
// ============================================================================

/**
 * Exige que l'utilisateur soit connecté
 * Redirige vers la page de connexion si non connecté
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['flash_message'] = 'Vous devez être connecté pour accéder à cette page.';
        $_SESSION['flash_type'] = 'warning';
        header('Location: login.php');
        exit();
    }
}

/**
 * Exige que l'utilisateur soit un artisan
 * Redirige si non artisan
 */
function requireArtisan(): void {
    requireLogin();
    if (!isArtisan()) {
        $_SESSION['flash_message'] = 'Cette section est réservée aux artisans.';
        $_SESSION['flash_type'] = 'error';
        header('Location: MonCompte.php');
        exit();
    }
}

/**
 * Exige que l'utilisateur soit un administrateur
 * Redirige si non administrateur
 */
function requireAdmin(): void {
    if (!isAdmin()) {
        $_SESSION['flash_message'] = 'Accès refusé. Administrateur requis.';
        $_SESSION['flash_type'] = 'error';
        header('Location: MonCompte.php');
        exit();
    }
}

/**
 * Exige que l'utilisateur soit un super administrateur
 * Redirige si non super admin
 */
function requireSuperAdmin(): void {
    if (!isSuperAdmin()) {
        $_SESSION['flash_message'] = 'Accès refusé. Super administrateur requis.';
        $_SESSION['flash_type'] = 'error';
        header('Location: MonCompte.php');
        exit();
    }
}

/**
 * Exige une permission d'administration spécifique
 * @param string $permission
 */
function requireAdminPermission(string $permission): void {
    if (!hasAdminPermission($permission)) {
        $_SESSION['flash_message'] = 'Vous n\'avez pas les permissions pour cette action.';
        $_SESSION['flash_type'] = 'error';
        header('Location: MonCompte.php');
        exit();
    }
}

/**
 * Vérifie si l'utilisateur est propriétaire d'un article
 * @param int $articleId
 * @return bool
 */
function isArticleOwner(int $articleId): bool {
    if (!isLoggedIn()) return false;
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT id_artisan FROM article WHERE id_article = ?");
        $stmt->execute([$articleId]);
        $article = $stmt->fetch();
        
        return $article && $article['id_artisan'] == getUserId();
    } catch (PDOException $e) {
        error_log("isArticleOwner error: " . $e->getMessage());
        return false;
    }
}

// ============================================================================
// FONCTIONS DE MESSAGES FLASH
// ============================================================================

/**
 * Définit un message flash
 * @param string $message
 * @param string $type (success, error, warning, info)
 */
function setFlashMessage(string $message, string $type = 'info'): void {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Récupère et supprime le message flash
 * @return array|null
 */
function getFlashMessage(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Affiche le message flash en HTML
 */
function displayFlashMessage(): void {
    $flash = getFlashMessage();
    if ($flash) {
        $colors = [
            'success' => ['bg' => '#d4edda', 'text' => '#155724', 'border' => '#c3e6cb'],
            'error' => ['bg' => '#f8d7da', 'text' => '#721c24', 'border' => '#f5c6cb'],
            'warning' => ['bg' => '#fff3cd', 'text' => '#856404', 'border' => '#ffeeba'],
            'info' => ['bg' => '#d1ecf1', 'text' => '#0c5460', 'border' => '#bee5eb']
        ];
        $c = $colors[$flash['type']] ?? $colors['info'];
        echo '<div style="background:' . $c['bg'] . ';color:' . $c['text'] . ';border:1px solid ' . $c['border'] . ';padding:15px;border-radius:10px;margin-bottom:20px;text-align:center;">';
        echo htmlspecialchars($flash['message']);
        echo '</div>';
    }
}

// ============================================================================
// FONCTIONS DE SÉCURITÉ
// ============================================================================

/**
 * Génère un token CSRF
 * @return string
 */
function generateCSRFToken(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Régénère l'ID de session (protection contre le session fixation)
 */
function regenerateSession(): void {
    session_regenerate_id(true);
}

// ============================================================================
// VALIDATION MOT DE PASSE SÉCURISÉ (UTILISATEURS UNIQUEMENT)
// ============================================================================

/**
 * Valide la force d'un mot de passe utilisateur
 * Critères : 8 caractères min, 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
 * 
 * @param string $password Le mot de passe à valider
 * @return array ['valid' => bool, 'errors' => array, 'strength' => int (0-5)]
 */
function validatePassword(string $password): array {
    $errors = [];
    $strength = 0;
    
    // Longueur minimum : 8 caractères
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } else {
        $strength++;
        if (strlen($password) >= 12) $strength++; // Bonus pour 12+ caractères
    }
    
    // Au moins une lettre majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule.";
    } else {
        $strength++;
    }
    
    // Au moins une lettre minuscule
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule.";
    } else {
        $strength++;
    }
    
    // Au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre.";
    } else {
        $strength++;
    }
    
    // Au moins un caractère spécial
    if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\'":"\\|,.<>\/?]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*...).";
    } else {
        $strength++;
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'strength' => min($strength, 5) // Force de 0 à 5
    ];
}

/**
 * Retourne un message de force du mot de passe
 * @param int $strength Force de 0 à 5
 * @return array ['text' => string, 'color' => string]
 */
function getPasswordStrengthMessage(int $strength): array {
    $messages = [
        0 => ['text' => 'Très faible', 'color' => '#dc3545'],
        1 => ['text' => 'Faible', 'color' => '#fd7e14'],
        2 => ['text' => 'Moyen', 'color' => '#ffc107'],
        3 => ['text' => 'Bon', 'color' => '#20c997'],
        4 => ['text' => 'Fort', 'color' => '#28a745'],
        5 => ['text' => 'Très fort', 'color' => '#155724']
    ];
    return $messages[$strength] ?? $messages[0];
}
?>
