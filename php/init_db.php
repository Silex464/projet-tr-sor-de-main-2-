<?php
// Script d'initialisation de la base de données
try {
    $pdo = new PDO(
        "mysql:host=localhost;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Créer la base de données si elle n'existe pas
    $pdo->exec("CREATE DATABASE IF NOT EXISTS tresordemain DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE tresordemain");

    // Créer la table utilisateurs
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS `utilisateurs` (
        `id` int AUTO_INCREMENT NOT NULL UNIQUE,
        `prenom` varchar(255) NOT NULL,
        `nom` varchar(255) NOT NULL,
        `sexe` varchar(10),
        `datenaissance` date,
        `nationalite` varchar(255),
        `description` text,
        `adresse` varchar(255),
        `code_postal` varchar(10),
        `ville` varchar(255),
        `email` varchar(255) NOT NULL UNIQUE,
        `numero` varchar(20),
        `adresseboutique` varchar(255),
        `mdp` varchar(255) NOT NULL,
        `type_compte` ENUM('artisan', 'acheteur') DEFAULT 'acheteur',
        `photo_profil` varchar(255) DEFAULT 'assets/images/default-avatar.svg',
        `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
        `derniere_connexion` datetime,
        `statut` ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Ajouter les colonnes manquantes si elles n'existent pas
    $columnsToAdd = [
        'type_compte' => "ALTER TABLE utilisateurs ADD COLUMN type_compte ENUM('artisan', 'acheteur') DEFAULT 'acheteur'",
        'date_inscription' => "ALTER TABLE utilisateurs ADD COLUMN date_inscription datetime DEFAULT CURRENT_TIMESTAMP",
        'statut' => "ALTER TABLE utilisateurs ADD COLUMN statut ENUM('actif', 'inactif', 'suspendu') DEFAULT 'actif'",
        'derniere_connexion' => "ALTER TABLE utilisateurs ADD COLUMN derniere_connexion datetime",
        'photo_profil' => "ALTER TABLE utilisateurs ADD COLUMN photo_profil varchar(255) DEFAULT 'assets/images/default-avatar.svg'",
        'photo_couverture' => "ALTER TABLE utilisateurs ADD COLUMN photo_couverture varchar(255) DEFAULT NULL",
        'mdp' => "ALTER TABLE utilisateurs ADD COLUMN mdp varchar(255) NOT NULL",
        'badge_verifie' => "ALTER TABLE utilisateurs ADD COLUMN badge_verifie tinyint(1) DEFAULT 0",
        'specialite' => "ALTER TABLE utilisateurs ADD COLUMN specialite varchar(255) DEFAULT NULL",
        'site_web' => "ALTER TABLE utilisateurs ADD COLUMN site_web varchar(255) DEFAULT NULL",
        'instagram' => "ALTER TABLE utilisateurs ADD COLUMN instagram varchar(255) DEFAULT NULL",
        'facebook' => "ALTER TABLE utilisateurs ADD COLUMN facebook varchar(255) DEFAULT NULL",
        'note_moyenne' => "ALTER TABLE utilisateurs ADD COLUMN note_moyenne decimal(3,2) DEFAULT 0.00"
    ];

    foreach ($columnsToAdd as $columnName => $alterQuery) {
        $stmt = $pdo->query("SHOW COLUMNS FROM utilisateurs LIKE '$columnName'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec($alterQuery);
            echo "Colonne '$columnName' ajoutée avec succès.<br>";
        }
    }

    // Supprimer les contraintes UNIQUE inutiles sur numero et email (sauf email)
    try {
        $indexes = $pdo->query("SHOW INDEXES FROM utilisateurs WHERE Column_name='numero' AND Non_unique=0")->fetchAll();
        foreach ($indexes as $index) {
            $pdo->exec("ALTER TABLE utilisateurs DROP INDEX " . $index['Key_name']);
            echo "Contrainte UNIQUE supprimée de la colonne 'numero'.<br>";
        }
    } catch (Exception $e) {
        // Continuer si l'index n'existe pas
    }

    echo "Base de données initialisée avec succès.<br>";
    echo "Vous pouvez maintenant <a href='inscription.php'>créer un compte</a>";

} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
