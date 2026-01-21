<?php
/**
 * Script d'initialisation du compte administrateur
 * 
 * INSTRUCTIONS :
 * 1. Accédez à ce fichier via votre navigateur : http://localhost/php/init_admin.php
 * 2. Une fois le compte créé, SUPPRIMEZ ce fichier pour des raisons de sécurité
 */

require_once 'tresorsdemain.php';

$message = '';
$success = false;

try {
    $pdo = getConnection();
    
    // Vérifier si la table administrateur a la bonne structure
    $stmt = $pdo->query("DESCRIBE administrateur");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Si la table n'a pas les bonnes colonnes, la recréer
    $required_columns = ['id_admin', 'nom', 'email', 'password', 'role', 'actif'];
    $missing_columns = array_diff($required_columns, $columns);
    
    if (!empty($missing_columns)) {
        // Recréer la table avec la bonne structure
        $pdo->exec("DROP TABLE IF EXISTS administrateur");
        $pdo->exec("
            CREATE TABLE `administrateur` (
                `id_admin` int(11) NOT NULL AUTO_INCREMENT,
                `nom` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL UNIQUE,
                `password` varchar(255) NOT NULL,
                `role` enum('admin','super_admin') DEFAULT 'admin',
                `permissions` JSON DEFAULT NULL,
                `actif` tinyint(1) DEFAULT 1,
                `derniere_connexion` datetime DEFAULT NULL,
                PRIMARY KEY (`id_admin`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
        $message .= "Table administrateur recréée avec la bonne structure.<br>";
    }
    
    // Vérifier s'il existe déjà un super admin
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM administrateur WHERE role = 'super_admin'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $message = "Un compte super administrateur existe déjà.";
    } else {
        // Créer le super admin par défaut
        // Mot de passe: admin123
        $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO administrateur (nom, email, password, role, actif) 
            VALUES (?, ?, ?, 'super_admin', 1)
        ");
        $stmt->execute(['Super Admin', 'admin@tresordemain.fr', $password_hash]);
        
        $success = true;
        $message = "Compte super administrateur créé avec succès.";
    }
    
} catch (PDOException $e) {
    $message = "Erreur : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation Admin - Trésor de Main</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        h1 {
            color: #2C1810;
            margin-bottom: 30px;
        }
        .message {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: left;
        }
        .credentials h3 {
            margin-top: 0;
            color: #8D5524;
        }
        .credentials p {
            margin: 10px 0;
            font-family: monospace;
            font-size: 1rem;
        }
        .btn {
            display: inline-block;
            background: #8D5524;
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #C58F5E;
            transform: translateY(-3px);
        }
        .warning-box {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Initialisation Admin</h1>
        
        <div class="message <?php echo $success ? 'success' : ($result['count'] > 0 ? 'warning' : 'error'); ?>">
            <?php echo $message; ?>
        </div>
        
        <?php if ($success): ?>
        <div class="credentials">
            <h3>Identifiants de connexion :</h3>
            <p><strong>Email :</strong> admin@tresordemain.fr</p>
            <p><strong>Mot de passe :</strong> admin123</p>
        </div>
        <?php endif; ?>
        
        <a href="login.php" class="btn">Aller à la page de connexion</a>
        
        <div class="warning-box">
            <strong>IMPORTANT :</strong> Par sécurité, supprimez ce fichier (init_admin.php) après utilisation !
        </div>
    </div>
</body>
</html>
