# 🚀 Guide de Déploiement - Trésor de Main

Ce guide vous explique comment mettre votre site en ligne.

---

## Option 1 : Railway (Recommandé - Gratuit pour démarrer)

### Étapes :

1. **Créer un compte Railway**
   - Allez sur [railway.app](https://railway.app/)
   - Connectez-vous avec votre compte GitHub

2. **Créer un nouveau projet**
   - Cliquez sur "New Project"
   - Sélectionnez "Deploy from GitHub repo"
   - Choisissez votre repository `projet-tr-sor-de-main-2-`

3. **Ajouter une base de données MySQL**
   - Dans votre projet, cliquez sur "+ New"
   - Sélectionnez "Database" → "MySQL"
   - Railway créera automatiquement la base de données

4. **Configurer les variables d'environnement**
   - Cliquez sur votre service web
   - Allez dans "Variables"
   - Ajoutez les variables suivantes (Railway les génère automatiquement pour MySQL):
   ```
   DB_HOST=${{MySQL.MYSQL_HOST}}
   DB_NAME=${{MySQL.MYSQL_DATABASE}}
   DB_USER=${{MySQL.MYSQL_USER}}
   DB_PASS=${{MySQL.MYSQL_PASSWORD}}
   ```

5. **Importer la base de données**
   - Utilisez la connexion externe MySQL fournie par Railway
   - Importez le fichier `BDD.sql` via un client MySQL ou phpMyAdmin

6. **Votre site est en ligne !** 🎉
   - Railway génère automatiquement une URL comme `tresor-de-main.up.railway.app`

---

## Option 2 : Render (Gratuit avec limitations)

### Étapes :

1. **Créer un compte Render**
   - Allez sur [render.com](https://render.com/)
   - Connectez-vous avec GitHub

2. **Créer un nouveau Web Service**
   - "New" → "Web Service"
   - Connectez votre repository GitHub
   - Sélectionnez "Docker" comme environnement
   - Plan : Free

3. **Créer une base de données**
   - "New" → "PostgreSQL" (MySQL n'est pas gratuit sur Render)
   - Ou utilisez un service MySQL externe comme [PlanetScale](https://planetscale.com/) (gratuit)

4. **Configurer les variables**
   ```
   DB_HOST=<votre-host-mysql>
   DB_NAME=<votre-base>
   DB_USER=<votre-user>
   DB_PASS=<votre-password>
   ```

---

## Option 3 : Hébergement ISEP (Hangar ISEP)

Si vous avez accès au Hangar ISEP :

1. **Connexion SSH**
   ```bash
   ssh votre_login@garageisep.music
   ```

2. **Cloner le projet**
   ```bash
   cd ~/public_html
   git clone https://github.com/votre-username/projet-tr-sor-de-main-2-.git
   ```

3. **La base de données est déjà configurée**
   - Host: `178.33.122.21`
   - Base: `hangardb_yafa64220`

---

## Option 4 : VPS / Serveur dédié

Pour un contrôle total, louez un VPS (DigitalOcean, OVH, Scaleway...) :

1. **Installer Docker sur le VPS**
   ```bash
   curl -fsSL https://get.docker.com -o get-docker.sh
   sudo sh get-docker.sh
   ```

2. **Cloner et lancer**
   ```bash
   git clone https://github.com/votre-username/projet-tr-sor-de-main-2-.git
   cd projet-tr-sor-de-main-2-
   docker-compose up -d
   ```

---

## Commandes utiles

### Test local avec Docker
```bash
# Construire et lancer
docker-compose up --build

# Accéder au site
# http://localhost:8080
```

### Vérifier que tout fonctionne
```bash
# Voir les logs
docker-compose logs -f

# Voir les conteneurs actifs
docker-compose ps
```

---

## Variables d'environnement

| Variable | Description | Exemple |
|----------|-------------|---------|
| `DB_HOST` | Hôte de la base de données | `mysql.railway.app` |
| `DB_NAME` | Nom de la base de données | `tresordemain` |
| `DB_USER` | Utilisateur MySQL | `root` |
| `DB_PASS` | Mot de passe MySQL | `secret123` |
| `PORT` | Port du serveur (auto sur Railway/Render) | `80` |

---

## Besoin d'aide ?

- **Railway** : [docs.railway.app](https://docs.railway.app/)
- **Render** : [render.com/docs](https://render.com/docs)
- **Docker** : [docs.docker.com](https://docs.docker.com/)
