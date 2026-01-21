-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 19 jan. 2026 à 17:48
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `hangardb_yafa64220`
--
-- Note: Ne pas créer la base, elle existe déjà sur Hangar
-- CREATE DATABASE IF NOT EXISTS `hangardb_yafa64220` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `hangardb_yafa64220`;

-- --------------------------------------------------------
-- Suppression des tables existantes (dans l'ordre inverse des dépendances)
-- --------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `participation_evenement`;
DROP TABLE IF EXISTS `liens_externes`;
DROP TABLE IF EXISTS `commentaire`;
DROP TABLE IF EXISTS `favoris`;
DROP TABLE IF EXISTS `article`;
DROP TABLE IF EXISTS `my_page`;
DROP TABLE IF EXISTS `artisans`;
DROP TABLE IF EXISTS `clients`;
DROP TABLE IF EXISTS `evenement`;
DROP TABLE IF EXISTS `adresse_créateur`;
DROP TABLE IF EXISTS `utilisateurs`;
DROP TABLE IF EXISTS `administrateur`;
SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------

--
-- Structure de la table `administrateur`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Données pour la table `administrateur` (mot de passe: admin123)
--
INSERT INTO `administrateur` (`nom`, `email`, `password`, `role`, `actif`) VALUES
('Super Admin', 'admin@tresordemain.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 1);

-- --------------------------------------------------------

--
-- Structure de la table `adresse_créateur`
--

CREATE TABLE `adresse_créateur` (
  `id_createur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `article`
--

CREATE TABLE `article` (
  `id_article` int(11) NOT NULL,
  `nom_article` varchar(255) NOT NULL,
  `id_mypage` int(11) DEFAULT NULL,
  `id_artisan` int(11) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `type_objet` varchar(255) DEFAULT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `prix` float NOT NULL,
  `quantite` int(11) NOT NULL DEFAULT 1,
  `couleur` varchar(255) DEFAULT NULL,
  `taille` float DEFAULT NULL,
  `style` varchar(255) DEFAULT NULL,
  `dimensions` varchar(255) DEFAULT NULL,
  `materiau` varchar(255) DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `disponibilite` tinyint(1) NOT NULL DEFAULT 1,
  `mis_en_avant` tinyint(1) NOT NULL DEFAULT 0,
  `vue` int(11) NOT NULL DEFAULT 0,
  `date_ajout` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `artisans`
--

CREATE TABLE `artisans` (
  `id_artisan` int(11) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `specialite` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(20) NOT NULL,
  `decription` text NOT NULL,
  `adresse boutique` varchar(255) DEFAULT NULL,
  `ville` varchar(255) NOT NULL,
  `code_postal` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id_client` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `tel` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id_commentaire` int(11) NOT NULL,
  `id_article` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `note` int(11) DEFAULT 5,
  `date` date NOT NULL,
  `statut` enum('en_attente','approuve','rejete') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
--

CREATE TABLE `evenement` (
  `id_evenement` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `url_lieu` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `titre`, `date_debut`, `date_fin`, `lieu`, `description`, `url_lieu`, `image`) VALUES
(1, 'Marché de Noël', '2026-12-17', '2026-12-18', 'Jardin des Tuileries, Paris ', 'Le marché de noël le plus joyeux et festif ! Venez goûter nos barbes à papa.', 'https://www.google.com/maps/place/Jardin+des+Tuileries/@48.8634916,2.3249194,17z/data=!3m1!4b1!4m6!3m5!1s0x47e66e2c30000001:0xc219db09e1bfefc7!8m2!3d48.8634916!4d2.3274943!16s%2Fm%2F0bx_wd_?entry=ttu&g_ep=EgoyMDI2MDExMy4wIKXMDSoASAFQAw%3D%3D', 'marché-noel.jpg'),
(2, 'Ventes aux enchères', '2026-01-30', '2026-01-31', 'Versailles', 'Une vente aux enchères où vous retrouverez des créations réalisées à la main par les artisans de l\'association des artisans de région parisienne (ADRP).', 'https://www.google.com/maps/place/78000+Versailles/@48.8038637,2.0779953,13z/data=!3m1!4b1!4m6!3m5!1s0x47e67db475f420bd:0x869e00ad0d844aba!8m2!3d48.8022585!4d2.1297422!16zL20vMDgwZzM?entry=ttu&g_ep=EgoyMDI2MDExMy4wIKXMDSoASAFQAw%3D%3D', 'enchères.png'),
(3, 'Exposition au Palais de l\'Elysée', '2026-07-14', '2026-07-14', 'Palais de l\'Elysée, Paris', 'Exposition exceptionnelle au Palais présidentielle de l\'Elysée, à Paris, suivi d\'un feu d\'artifice financé par Emmanuel Macron en personne!', 'https://www.google.com/maps/place/Palais+de+l\'%C3%89lys%C3%A9e/@48.8703089,2.3141036,17z/data=!3m1!4b1!4m6!3m5!1s0x47e66fce8ca6e347:0x2e38f4467a582f22!8m2!3d48.8703089!4d2.3166785!16zL20vMDF2ZmJ2?entry=ttu&g_ep=EgoyMDI2MDExMy4wIKXMDSoASAFQAw%3D%3D', 'elysee.png'),
(1235, 'event', '2025-12-17', '2025-12-18', '', '111111', '', '');

-- --------------------------------------------------------

--
-- Structure de la table `favoris`
--

CREATE TABLE `favoris` (
  `id_favori` int(11) NOT NULL AUTO_INCREMENT,
  `id_client` int(11) NOT NULL,
  `id_article` int(11) NOT NULL,
  `date_ajout` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_favori`),
  UNIQUE KEY `unique_favori` (`id_client`, `id_article`),
  KEY `favoris_client` (`id_client`),
  KEY `favoris_article` (`id_article`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `liens_externes`
--

CREATE TABLE `liens_externes` (
  `id_lien` int(11) NOT NULL,
  `id_artisan` int(11) NOT NULL,
  `id_event` int(11) NOT NULL,
  `lien` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `my_page`
--

CREATE TABLE `my_page` (
  `id_mypage` int(11) NOT NULL,
  `id_artisan` int(11) NOT NULL,
  `poste` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `photo_de_profile` varchar(255) NOT NULL,
  `commentaire_client` text DEFAULT NULL,
  `bibliotheque` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `participation_evenement`
--

CREATE TABLE `participation_evenement` (
  `id_artisan` int(11) NOT NULL,
  `id_event` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `sexe` varchar(255) DEFAULT NULL,
  `datenaissance` date DEFAULT NULL,
  `nationalite` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` int(11) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `adresseboutique` varchar(255) DEFAULT NULL,
  `mdp` varchar(255) NOT NULL,
  `dates` datetime DEFAULT NULL,
  `type_compte` enum('artisan','acheteur') DEFAULT 'acheteur',
  `date_inscription` datetime DEFAULT current_timestamp(),
  `statut` enum('actif','inactif','suspendu') DEFAULT 'actif',
  `derniere_connexion` datetime DEFAULT NULL,
  `photo_profil` varchar(255) DEFAULT 'assets/images/default-avatar.svg',
  `photo_couverture` varchar(255) DEFAULT NULL,
  `badge_verifie` tinyint(1) DEFAULT 0,
  `specialite` varchar(255) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `note_moyenne` decimal(3,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `prenom`, `nom`, `sexe`, `datenaissance`, `nationalite`, `description`, `adresse`, `code_postal`, `ville`, `email`, `numero`, `adresseboutique`, `mdp`, `dates`, `type_compte`, `date_inscription`, `statut`, `derniere_connexion`, `photo_profil`) VALUES
(1, 'Ewenn', 'Daligaud', '', '0000-00-00', '', NULL, NULL, 0, '', 'ewenndaligaud@gmail.com', '', NULL, '$2y$10$4BLpAdg.Dy0mZUix7sjrp.k2Z7Y1eIHuib8lu59GzepEHLHWYUzv2', NULL, 'acheteur', '2026-01-12 16:21:44', 'actif', NULL, 'assets/images/default-avatar.svg'),
(4, 'Ewenn', 'Daligaud', '', '0000-00-00', '', NULL, NULL, 0, '', 'ewda62938@eleve.isep.fr', '', NULL, '$2y$10$cjZmusRtKZe9xQdhtXbNvuol4aot3YeqBsDfZdpumJ.4LxFWWi9be', NULL, 'acheteur', '2026-01-12 16:22:39', 'actif', '2026-01-16 16:22:34', 'uploads/user_4_1768514001.png');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `adresse_créateur`
--
ALTER TABLE `adresse_créateur`
  ADD PRIMARY KEY (`id_createur`),
  ADD UNIQUE KEY `id_createur` (`id_createur`);

--
-- Index pour la table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`id_article`),
  ADD UNIQUE KEY `id_article` (`id_article`),
  ADD KEY `Article_fk1` (`id_mypage`);

--
-- Index pour la table `artisans`
--
ALTER TABLE `artisans`
  ADD PRIMARY KEY (`id_artisan`),
  ADD UNIQUE KEY `id_artisan` (`id_artisan`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `tel` (`tel`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id_client`),
  ADD UNIQUE KEY `id_client` (`id_client`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `tel` (`tel`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id_commentaire`),
  ADD UNIQUE KEY `id_commentaire` (`id_commentaire`),
  ADD KEY `Commentaire_fk1` (`id_article`),
  ADD KEY `Commentaire_fk2` (`id_client`);

--
-- Index pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_evenement`),
  ADD UNIQUE KEY `id_event` (`id_evenement`);

--
-- Index pour la table `liens_externes`
--
ALTER TABLE `liens_externes`
  ADD PRIMARY KEY (`id_lien`),
  ADD UNIQUE KEY `id_lien` (`id_lien`),
  ADD KEY `Liens_externes_fk1` (`id_artisan`),
  ADD KEY `Liens_externes_fk2` (`id_event`);

--
-- Index pour la table `my_page`
--
ALTER TABLE `my_page`
  ADD PRIMARY KEY (`id_mypage`),
  ADD UNIQUE KEY `id_mypage` (`id_mypage`),
  ADD KEY `My_page_fk1` (`id_artisan`);

--
-- Index pour la table `participation_evenement`
--
ALTER TABLE `participation_evenement`
  ADD KEY `Participation_evenement_fk0` (`id_artisan`),
  ADD KEY `Participation_evenement_fk1` (`id_event`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id` (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `adresse_créateur`
--
ALTER TABLE `adresse_créateur`
  MODIFY `id_createur` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `article`
--
ALTER TABLE `article`
  MODIFY `id_article` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `artisans`
--
ALTER TABLE `artisans`
  MODIFY `id_artisan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id_client` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id_commentaire` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `favoris`
--
ALTER TABLE `favoris`
  MODIFY `id_favori` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1236;

--
-- AUTO_INCREMENT pour la table `liens_externes`
--
ALTER TABLE `liens_externes`
  MODIFY `id_lien` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `my_page`
--
ALTER TABLE `my_page`
  MODIFY `id_mypage` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `Article_fk1` FOREIGN KEY (`id_mypage`) REFERENCES `my_page` (`id_mypage`);

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `Commentaire_fk1` FOREIGN KEY (`id_article`) REFERENCES `article` (`id_article`),
  ADD CONSTRAINT `Commentaire_fk2` FOREIGN KEY (`id_client`) REFERENCES `clients` (`id_client`);

--
-- Contraintes pour la table `liens_externes`
--
ALTER TABLE `liens_externes`
  ADD CONSTRAINT `Liens_externes_fk1` FOREIGN KEY (`id_artisan`) REFERENCES `artisans` (`id_artisan`),
  ADD CONSTRAINT `Liens_externes_fk2` FOREIGN KEY (`id_event`) REFERENCES `evenement` (`id_evenement`);

--
-- Contraintes pour la table `my_page`
--
ALTER TABLE `my_page`
  ADD CONSTRAINT `My_page_fk1` FOREIGN KEY (`id_artisan`) REFERENCES `artisans` (`id_artisan`);

--
-- Contraintes pour la table `participation_evenement`
--
ALTER TABLE `participation_evenement`
  ADD CONSTRAINT `Participation_evenement_fk0` FOREIGN KEY (`id_artisan`) REFERENCES `artisans` (`id_artisan`),
  ADD CONSTRAINT `Participation_evenement_fk1` FOREIGN KEY (`id_event`) REFERENCES `evenement` (`id_evenement`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;