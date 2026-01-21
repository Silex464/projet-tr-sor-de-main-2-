# Documentation du Système d'Administration - Trésor de Main

---

## Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Installation](#installation)
3. [Connexion administrateur](#connexion-administrateur)
4. [Fonctionnalités](#fonctionnalités)
5. [Guide des actions](#guide-des-actions)
6. [Rôles et permissions](#rôles-et-permissions)
7. [Sécurité](#sécurité)
8. [Fichiers créés](#fichiers-créés)
9. [Dépannage](#dépannage)

---

## Vue d'ensemble

Un système d'administration complet a été mis en place pour **Trésor de Main**. Les administrateurs peuvent :

- [X] **Gérer les utilisateurs** - Bannir, débannir, supprimer des comptes
- [X] **Modérer les créations** - Mettre en avant, gérer la disponibilité, supprimer
- [X] **Gérer les événements** - Modifier les statuts, supprimer
- [X] **Modérer les commentaires** - Approuver, rejeter, supprimer
- [X] **Gérer les administrateurs** - Créer, modifier, supprimer (super admin)

---

## Installation

### Étape 1 : Mise à jour de la base de données
Exécutez le fichier `BDD.sql` dans votre gestionnaire MySQL (phpMyAdmin, MySQL Workbench, etc.)

### Étape 2 : Vérifier les fichiers
Assurez-vous que ces fichiers sont présents dans `/php/` :
- `admin_dashboard.php`
- `admin_users.php`
- `admin_creations.php`
- `admin_events.php`
- `admin_comments.php`
- `admin_admins.php`

### Étape 3 : Tester la connexion
1. Allez sur `/php/login.php`
2. Cliquez sur l'onglet "Administrateur"
3. Connectez-vous avec les identifiants par défaut

---

## Connexion administrateur

### Comment se connecter ?

1. Allez sur la page `/php/login.php`
2. Cliquez sur l'onglet **"Administrateur"** (pas "Utilisateur")
3. Entrez vos identifiants
4. Cliquez sur "Se connecter (Admin)"

### Comptes par défaut

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| **Super Admin** | admin@tresordemain.fr | admin123 |
| **Admin** | moderation@tresordemain.fr | admin123 |

IMPORTANT : **Changez ces mots de passe en production !**

---

## Fonctionnalités

### Dashboard (Tableau de bord)
**URL :** `/php/admin_dashboard.php`

Affiche les statistiques du site :
- Nombre d'utilisateurs
- Nombre de créations
- Nombre d'événements
- Comptes suspendus

### Gestion des Utilisateurs
**URL :** `/php/admin_users.php`

Actions disponibles :
- Voir tous les utilisateurs
- Filtrer par statut (Actif/Inactif/Suspendu)
- Rechercher par nom ou email
- **Suspendre** un compte (bannir)
- **Réactiver** un compte (débannir)
- **Supprimer** un compte (irréversible)

### Gestion des Créations
**URL :** `/php/admin_creations.php`

Actions disponibles :
- Voir tous les articles
- Filtrer par catégorie
- **Mettre en avant** une création
- **Retirer de l'avant** une création
- **Marquer disponible/indisponible**
- **Supprimer** une création

### Gestion des Événements
**URL :** `/php/admin_events.php`

Actions disponibles :
- Voir tous les événements
- Changer le statut : Planifié, En cours, Terminé, Annulé
- **Supprimer** un événement

### Modération des Commentaires
**URL :** `/php/admin_comments.php`

Actions disponibles :
- Voir tous les commentaires
- Filtrer par statut (En attente/Approuvé/Rejeté)
- **Approuver** un commentaire
- **Rejeter** un commentaire
- **Supprimer** un commentaire

### ️ Gestion des Administrateurs (Super Admin)
**URL :** `/php/admin_admins.php`

Actions disponibles :
- Voir tous les administrateurs
- **Créer** un nouvel administrateur
- **Désactiver/Activer** un administrateur
- **Supprimer** un administrateur

---

## Guide des actions

### Bannir un utilisateur

1. Allez dans **Gestion des Utilisateurs**
2. Trouvez l'utilisateur (utilisez la recherche si besoin)
3. Cliquez sur **"Suspendre"**
4. Confirmez dans la pop-up

**Résultat :** L'utilisateur ne peut plus se connecter.

### Débannir un utilisateur

1. Allez dans **Gestion des Utilisateurs**
2. Filtrez par statut "Suspendu"
3. Trouvez l'utilisateur
4. Cliquez sur **"Réactiver"**

**Résultat :** L'utilisateur peut à nouveau se connecter.

### Mettre en avant une création

1. Allez dans **Gestion des Créations**
2. Trouvez l'article
3. Cliquez sur **"Mettre EN AVANT"**

**Résultat :** L'article apparaît dans les créations mises en avant.

### Approuver un commentaire

1. Allez dans **Modération des Commentaires**
2. Filtrez par "En attente"
3. Lisez le commentaire
4. Cliquez sur **"Approuver"**

**Résultat :** Le commentaire est visible sur le site.

### Modifier le statut d'un événement

1. Allez dans **Gestion des Événements**
2. Trouvez l'événement
3. Utilisez le menu déroulant pour changer le statut

### Créer un administrateur

1. Allez dans **Gestion des Administrateurs** (Super Admin requis)
2. Remplissez le formulaire :
   - Nom
   - Email
   - Mot de passe (min. 8 caractères)
   - Rôle (Admin ou Super Admin)
3. Cliquez sur **"Créer l'administrateur"**

---

## Rôles et permissions

### Super Administrateur
Accès complet :
- [X] Gérer les utilisateurs
- [X] Gérer les créations
- [X] Gérer les événements
- [X] Modérer les commentaires
- [X] Gérer les administrateurs

### Administrateur
Accès limité :
- [X] Gérer les utilisateurs
- [X] Gérer les créations
- [X] Gérer les événements
- [X] Modérer les commentaires
- [ ] Gérer les administrateurs

---

## Sécurité

### Bonnes pratiques

1. **Changez les mots de passe par défaut** immédiatement
2. **Utilisez HTTPS** en production
3. **Limitez le nombre d'administrateurs**
4. **Faites des sauvegardes** régulières

### Mots de passe

Les mots de passe sont hashés avec `password_hash(PASSWORD_DEFAULT)` de PHP.

Pour changer un mot de passe en base de données :
```php
<?php
$nouveau_mdp = password_hash('VotreNouveauMotDePasse', PASSWORD_DEFAULT);
echo $nouveau_mdp; // Copiez ce hash dans la BDD
?>
```

### Sessions

- Les sessions admin sont séparées des sessions utilisateur
- Déconnexion automatique via `/php/logout.php`

---

## Fichiers créés

### Pages d'administration (dans `/php/`)

| Fichier | Description |
|---------|-------------|
| `admin_dashboard.php` | Tableau de bord avec statistiques |
| `admin_users.php` | Gestion des utilisateurs |
| `admin_creations.php` | Gestion des créations/articles |
| `admin_events.php` | Gestion des événements |
| `admin_comments.php` | Modération des commentaires |
| `admin_admins.php` | Gestion des administrateurs |

### Fichiers modifiés

| Fichier | Modifications |
|---------|---------------|
| `BDD.sql` | Table administrateur améliorée + index |
| `auth.php` | Fonctions d'administration ajoutées |
| `login.php` | Onglet connexion administrateur |

### Fonctions ajoutées dans auth.php

```php
isAdmin()              // Vérifie si admin connecté
isSuperAdmin()         // Vérifie si super admin
hasAdminPermission()   // Vérifie une permission
requireAdmin()         // Exige connexion admin
requireSuperAdmin()    // Exige super admin
requireAdminPermission() // Exige une permission
```

---

## Dépannage

### "Accès refusé" ou page blanche

**Cause :** Pas connecté comme admin ou permissions insuffisantes.

**Solution :**
1. Connectez-vous via l'onglet "Administrateur" de `/php/login.php`
2. Vérifiez votre rôle (admin vs super admin)

### Les statistiques n'apparaissent pas

**Cause :** Problème de connexion à la base de données.

**Solution :**
1. Vérifiez les identifiants dans `tresorsdemain.php`
2. Vérifiez que la BDD est accessible

### Le mot de passe ne fonctionne pas

**Cause :** Mauvais identifiants ou compte désactivé.

**Solution :**
1. Vérifiez l'email et le mot de passe
2. Vérifiez que le compte admin est `actif = TRUE` dans la BDD

### Les boutons ne répondent pas

**Cause :** Problème JavaScript ou formulaire.

**Solution :**
1. Rafraîchissez la page
2. Vérifiez la console du navigateur (F12)

### Je ne vois pas l'onglet "Administrateur"

**Cause :** Mauvaise page.

**Solution :** Allez directement sur `/php/login.php`

---

## Questions fréquentes

**Q : Puis-je annuler une suppression ?**
R : Non, les suppressions sont irréversibles. Faites des sauvegardes !

**Q : Comment créer un administrateur sans être Super Admin ?**
R : Impossible. Seuls les Super Admins peuvent créer des comptes admin.

**Q : Les utilisateurs bannis peuvent-ils récupérer leurs données ?**
R : Oui, leurs données restent en base. Seule la connexion est bloquée.

**Q : Comment supprimer les comptes admin par défaut ?**
R : Créez d'abord votre propre compte Super Admin, puis supprimez les autres.

---

## Support

En cas de problème :
1. Consultez cette documentation
2. Vérifiez les logs d'erreur PHP
3. Vérifiez la console du navigateur (F12)
4. Vérifiez que la base de données est à jour

---

## Notes importantes

- **Changez les mots de passe par défaut !**
- **Les suppressions sont irréversibles !**
- **Faites des sauvegardes régulières !**
- **Utilisez HTTPS en production !**

---

*Documentation pour le projet Trésor de Main*
