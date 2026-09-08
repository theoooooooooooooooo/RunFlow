# Runflow — Procédure de déploiement

| | |
|---|---|
| **Application** | Runflow — gestion des interventions |
| **Rédacteur** | Théo Celemani |
| **Version du document** | 1.0 |
| **Date** | Août 2026 |
| **Environnement décrit** | Préproduction (opérationnel) et Production (préparé) |

> **⚠ À COMPLÉTER avant remise.** Les valeurs entre chevrons `<…>` doivent être remplacées par les vôtres : URL du dépôt, nom d'hôte, chemins réels, noms de conteneurs. Vérifiez chaque commande sur votre serveur avant de figer le document — une procédure qui ne s'exécute pas est pire qu'une procédure absente.

---

## 1. Objet et périmètre

Ce document décrit la procédure complète de déploiement de l'application Runflow sur un serveur distant, depuis la préparation initiale du serveur jusqu'à la mise à jour continue de l'application.

Il couvre :

- la préparation du serveur (opération réalisée une seule fois) ;
- le déploiement initial de l'application ;
- le déploiement automatisé des évolutions via l'intégration continue ;
- les vérifications à effectuer après chaque déploiement ;
- la procédure de retour arrière en cas d'échec ;
- la sauvegarde et la restauration des données ;
- les incidents connus et leur résolution.

Il ne couvre pas le développement local, décrit dans le fichier `README.md` du dépôt.

---

## 2. Stratégie d'environnements

L'application suit une logique à trois niveaux, chaque environnement correspondant à une branche Git.

| Environnement | Branche | Rôle | Correspondance usuelle | Alimentation |
|---|---|---|---|---|
| Développement | `dev` | Intégration des fonctionnalités au fil de l'eau | Environnement de développement | Push direct après validation locale |
| Préproduction | `prepod` | Validation du comportement en conditions réelles, recette fonctionnelle | SIT puis UAT | Pull Request depuis `dev`, après succès de la suite de tests |
| Production | `main` | Service rendu aux utilisateurs | Production | Pull Request depuis `prepod`, après validation manuelle de la recette |

**Règle non négociable :** aucune fusion vers `prepod` ou `main` n'est possible si le job de tests du pipeline échoue. Le job de déploiement dépend explicitement du job de tests.

**Recette (UAT).** Avant toute promotion de `prepod` vers `main`, les scénarios du plan de test sont rejoués manuellement sur l'environnement de préproduction, avec le jeu de données de démonstration. La promotion n'a lieu que si l'intégralité des cas est conforme.

---

## 3. Architecture cible

Trois conteneurs applicatifs par environnement, sur un réseau Docker interne, plus un reverse proxy mutualisé.

```
Navigateur
   │ HTTPS 443
   ▼
Nginx Proxy Manager  (conteneur, réseau Docker partagé « web »)
   │ proxy_pass HTTP 80
   ▼
runflow_prepod_web   — Nginx interne
   │ FastCGI 9000
   ▼
runflow_prepod_app   — PHP-FPM 8.4 + Symfony
   │ SQL 3306
   ▼
runflow_prepod_db    — MySQL 8.0
   │
   ▼
Volume Docker nommé « prepod_mysql_data »  (persistance)
```

Seul le conteneur `web` est joignable par le reverse proxy. Les conteneurs `app` et `db` ne sont exposés sur aucun port public.

---

## 4. Prérequis

### 4.1 Serveur

| Élément | Valeur |
|---|---|
| Hébergeur | VPS OVHcloud |
| Système | Ubuntu 26.04 LTS |
| Accès | SSH par clé, port 22 |
| Nom d'hôte public | `<51-91-159-178.sslip.io>` |

### 4.2 Comptes et accès nécessaires

- Un compte utilisateur non-root sur le serveur, membre du groupe `docker`.
- Un accès au dépôt GitHub `<organisation/runflow>`.
- Un accès administrateur à l'interface Nginx Proxy Manager.

### 4.3 Secrets à disposer avant de commencer

| Secret | Usage | Génération |
|---|---|---|
| `MYSQL_ROOT_PASSWORD` | Compte administrateur MySQL | `openssl rand -hex 24` |
| `MYSQL_PASSWORD` | Compte applicatif MySQL | `openssl rand -hex 24` |
| `APP_SECRET` | Signature des sessions et jetons Symfony | `openssl rand -hex 32` |
| Clé SSH de déploiement | Connexion du pipeline au serveur | `ssh-keygen -t ed25519 -C "deploy-runflow"` |

> **Convention obligatoire.** Les secrets de production sont générés **exclusivement en hexadécimal**. Un mot de passe contenant `/` ou `=` casse l'interprétation de l'URL de connexion Doctrine (voir §11.4).

---

## 5. Préparation initiale du serveur

*Opération réalisée une seule fois, à la mise à disposition du serveur.*

### 5.1 Première connexion et sécurisation du compte

```bash
ssh <utilisateur>@<adresse-ip>
passwd                      # changement obligatoire du mot de passe initial
```

### 5.2 Mise à jour du système

```bash
sudo apt update && sudo apt upgrade -y
```

### 5.3 Configuration du pare-feu

Politique de refus par défaut, trois ports seulement.

```bash
sudo apt install ufw -y
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp        # SSH
sudo ufw allow 80/tcp        # HTTP (redirection et validation Let's Encrypt)
sudo ufw allow 443/tcp       # HTTPS
sudo ufw enable
sudo ufw status verbose      # vérification
```

> Le port d'administration de Nginx Proxy Manager (81) n'est **jamais** ouvert. L'interface est accessible uniquement via un tunnel SSH : `ssh -L 8081:localhost:81 <utilisateur>@<adresse-ip>`, puis `http://localhost:8081` depuis le poste local.

### 5.4 Installation de Docker

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker $USER
newgrp docker
docker --version && docker compose version   # vérification
```

### 5.5 Création du réseau Docker partagé

Ce réseau permet au reverse proxy d'atteindre les conteneurs `web` de chaque environnement.

```bash
docker network create web
```

### 5.6 Déploiement du reverse proxy

```bash
mkdir -p ~/proxy && cd ~/proxy
# docker-compose.yml de Nginx Proxy Manager, rattaché au réseau « web »
docker compose up -d
```

Puis, via le tunnel SSH décrit en §5.3 :

1. Se connecter à l'interface et changer immédiatement les identifiants par défaut.
2. Créer un *Proxy Host* : domaine `<51-91-159-178.sslip.io>`, destination `http://runflow_prepod_web:80`.
3. Onglet SSL : demander un certificat Let's Encrypt, activer **Force SSL** et **HTTP/2**.
4. Vérifier que le renouvellement automatique est actif.

---

## 6. Déploiement initial de l'application

### 6.1 Récupération du code

```bash
mkdir -p ~/runflow && cd ~/runflow
git clone -b prepod <url-du-depot> .
```

### 6.2 Création du fichier d'environnement

Le fichier `.env.prepod` **n'est jamais versionné**. Il est créé manuellement sur le serveur, avec les secrets générés en §4.3.

```bash
cat > .env.prepod <<'EOF'
APP_ENV=prod
APP_DEBUG=0
APP_SECRET=<hexadecimal-32-octets>
DATABASE_URL="mysql://runflow:<hexadecimal-24-octets>@database:3306/runflow?serverVersion=8.0"
MYSQL_ROOT_PASSWORD=<hexadecimal-24-octets>
MYSQL_DATABASE=runflow
MYSQL_USER=runflow
MYSQL_PASSWORD=<hexadecimal-24-octets>
EOF

chmod 600 .env.prepod
```

> `MYSQL_PASSWORD` et le mot de passe contenu dans `DATABASE_URL` doivent être **identiques**.

### 6.3 Construction et démarrage des services

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prepod build
docker compose -f docker-compose.prod.yml --env-file .env.prepod up -d
docker compose -f docker-compose.prod.yml ps      # les 3 services doivent être « running »
```

Le script `docker-entrypoint.sh` du conteneur applicatif exécute automatiquement, au démarrage :

1. la création d'un fichier `.env` vide, exigé par le noyau Symfony ;
2. la création du dossier `var/` et l'attribution des droits à `www-data` ;
3. une boucle d'attente jusqu'à ce que MySQL réponde ;
4. la génération du cache applicatif de production ;
5. l'exécution des migrations Doctrine.

### 6.4 Vérification des migrations

```bash
docker compose -f docker-compose.prod.yml exec app \
  php bin/console doctrine:migrations:status
```

Aucune migration ne doit rester en attente.

### 6.5 Chargement du jeu de données

Les fixtures Doctrine ne sont **pas** chargées sur le serveur : le bundle correspondant est absent en environnement de production. Le jeu de données est généré localement puis importé.

**Sur le poste local :**

```bash
php bin/console doctrine:fixtures:load --no-interaction
mysqldump -u root -p --no-create-info --complete-insert runflow > jeu_essai.sql
scp jeu_essai.sql <utilisateur>@<adresse-ip>:~/runflow/
```

**Sur le serveur :**

```bash
docker compose -f docker-compose.prod.yml exec -T database \
  mysql -u runflow -p"<mot-de-passe>" runflow < ~/runflow/jeu_essai.sql
```

Contenu attendu : 1 administrateur, 6 techniciens dont 1 désactivé, 20 clients, 10 références de matériel dont 1 en stock critique, 40 interventions réparties sur les 7 statuts.

---

## 7. Déploiement automatisé des évolutions

Une fois le déploiement manuel validé, les mises à jour passent exclusivement par le pipeline.

### 7.1 Secrets à configurer dans le dépôt GitHub

*Settings → Secrets and variables → Actions*

| Secret | Contenu |
|---|---|
| `SSH_PRIVATE_KEY` | Clé privée de la paire dédiée au déploiement, distincte de toute clé personnelle |
| `SSH_HOST` | Adresse IP ou nom d'hôte du serveur |
| `SSH_USER` | Utilisateur de déploiement |
| `MYSQL_PASSWORD` | Utilisé par le job de tests |

La clé publique correspondante est installée sur le serveur dans `~/.ssh/authorized_keys`.

### 7.2 Déclencheur

Le job de déploiement s'exécute **uniquement** sur un push vers `prepod`, et **uniquement** si le job de tests a réussi (`needs: tests`). Aucun état intermédiaire de Pull Request ne peut être déployé.

### 7.3 Séquence exécutée

1. Connexion SSH du runner GitHub Actions vers le VPS.
2. `git pull` de la dernière version de la branche `prepod`.
3. Reconstruction de l'image Docker applicative.
4. Redémarrage des services avec la nouvelle image.
5. Exécution des migrations Doctrine par le script d'entrypoint.
6. Suppression des images Docker devenues obsolètes (`docker image prune -f`).

### 7.4 Équivalent manuel

En cas d'indisponibilité de GitHub Actions, la même séquence s'exécute à la main :

```bash
cd ~/runflow
git pull origin prepod
docker compose -f docker-compose.prod.yml --env-file .env.prepod build app
docker compose -f docker-compose.prod.yml --env-file .env.prepod up -d
docker image prune -f
```

---

## 8. Scripts d'évolution de la base de données

Toute modification du schéma passe par une migration Doctrine versionnée. **Aucune modification manuelle du schéma n'est autorisée sur le serveur.**

**Génération, sur le poste local :**

```bash
php bin/console make:migration
```

Le fichier généré dans `migrations/` est relu, complété si nécessaire, puis committé avec le code qui en dépend.

**Application :** automatique au démarrage du conteneur applicatif, via l'entrypoint.

**Application manuelle si besoin :**

```bash
docker compose -f docker-compose.prod.yml exec app \
  php bin/console doctrine:migrations:migrate --no-interaction
```

**Retour arrière d'une migration :**

```bash
docker compose -f docker-compose.prod.yml exec app \
  php bin/console doctrine:migrations:migrate prev --no-interaction
```

> Une migration supprimant une colonne est **irréversible sur les données**. Avant toute migration destructive, exécuter la sauvegarde décrite en §10.1.

---

## 9. Vérifications après déploiement

Checklist à dérouler après chaque mise en ligne.

| # | Vérification | Commande ou action | Résultat attendu |
|---|---|---|---|
| 1 | Services démarrés | `docker compose -f docker-compose.prod.yml ps` | 3 services `running` |
| 2 | Absence d'erreur au démarrage | `docker compose -f docker-compose.prod.yml logs --tail=50 app` | Aucune trace `CRITICAL` ni `Fatal error` |
| 3 | Migrations à jour | `... exec app php bin/console doctrine:migrations:status` | Aucune migration en attente |
| 4 | Application joignable | `curl -I https://<hote>` | `HTTP/2 200` |
| 5 | Certificat valide | Navigateur, cadenas | Émetteur Let's Encrypt, échéance > 15 jours |
| 6 | Authentification | Connexion avec les trois comptes de test | Redirection vers le bon tableau de bord |
| 7 | Parcours critique | Créer une demande, l'accepter, la planifier | Statut `Planifiée`, apparition au planning |
| 8 | Débogage désactivé | Provoquer une page 404 | Page d'erreur générique, aucune trace de pile |

Si l'un des points 1 à 4 échoue, appliquer la procédure de retour arrière (§10.3).

---

## 10. Sauvegarde, restauration et retour arrière

### 10.1 Sauvegarde de la base

```bash
docker compose -f docker-compose.prod.yml exec -T database \
  mysqldump -u root -p"<mot-de-passe-root>" --single-transaction runflow \
  > ~/sauvegardes/runflow_$(date +%F_%H%M).sql
```

L'option `--single-transaction` garantit un instantané cohérent sans interrompre le service.

### 10.2 Restauration

```bash
docker compose -f docker-compose.prod.yml exec -T database \
  mysql -u root -p"<mot-de-passe-root>" runflow < ~/sauvegardes/<fichier>.sql
```

> **Une sauvegarde jamais restaurée n'est pas une sauvegarde.** La procédure de restauration doit être testée périodiquement sur un environnement jetable.

### 10.3 Retour arrière applicatif

```bash
cd ~/runflow
git log --oneline -5                # identifier le dernier commit stable
git checkout <sha-du-commit-stable>
docker compose -f docker-compose.prod.yml --env-file .env.prepod build app
docker compose -f docker-compose.prod.yml --env-file .env.prepod up -d
```

Si le déploiement fautif comportait une migration, restaurer d'abord la base (§10.2), puis revenir au code.

---

## 11. Incidents connus et résolution

### 11.1 Fichier `.env` absent à la construction de l'image

**Symptôme.** La construction échoue : le noyau Symfony réclame un fichier `.env` inexistant.
**Cause.** Le fichier est volontairement exclu du dépôt, mais le noyau exige sa présence physique, même lorsque les variables sont fournies par l'environnement.
**Résolution.** La génération du cache est reportée du *build* au démarrage du conteneur, via `docker-entrypoint.sh`, qui crée au passage un `.env` vide. **Ne jamais remettre le fichier `.env` dans le dépôt.**

### 11.2 Échec d'attribution des droits sur `var/`

**Symptôme.** `chown: cannot access 'var/': No such file or directory`.
**Cause.** Le dossier est exclu par `.gitignore`, donc absent de l'image.
**Résolution.** L'entrypoint crée explicitement le dossier avant d'attribuer les droits à `www-data`.

### 11.3 Le conteneur applicatif démarre avant la base

**Symptôme.** `SQLSTATE[HY000] [2002] Connection refused` au premier démarrage.
**Cause.** MySQL n'a pas terminé son initialisation quand PHP tente de se connecter.
**Résolution.** Boucle d'attente dans l'entrypoint, qui interroge la disponibilité de la base avant de poursuivre.

### 11.4 Caractères réservés dans un mot de passe

**Symptôme.** Doctrine rejette l'URL de connexion.
**Cause.** Les caractères `/` et `=` d'un mot de passe généré en base64 cassent la syntaxe de l'URL.
**Résolution.** Génération exclusivement hexadécimale (`openssl rand -hex`). Voir §4.3.

### 11.5 Le certificat HTTPS ne se renouvelle pas

**Vérification.** Onglet SSL de Nginx Proxy Manager, date d'expiration.
**Cause fréquente.** Port 80 fermé : Let's Encrypt ne peut plus valider le domaine.
**Résolution.** `sudo ufw status` doit montrer le port 80 autorisé, puis relancer le renouvellement depuis l'interface.

---

## 12. Exploitation courante

```bash
# Consulter les journaux applicatifs en direct
docker compose -f docker-compose.prod.yml logs -f app

# Redémarrer un service
docker compose -f docker-compose.prod.yml restart app

# Vider le cache Symfony
docker compose -f docker-compose.prod.yml exec app php bin/console cache:clear

# Espace disque et nettoyage des images obsolètes
df -h && docker system df
docker image prune -f
```

---

## 13. Activation de l'environnement de production

L'environnement de production n'est pas activé à ce jour. L'architecture est prête : `docker-compose.prod.yml` et `Dockerfile.prod` sont ceux déjà utilisés en préproduction.

Étapes restantes :

1. Acquérir un nom de domaine et le faire pointer vers l'adresse IP du serveur.
2. Créer `.env.prod`, avec des secrets **distincts** de ceux de la préproduction.
3. Déclarer un second *Proxy Host* dans Nginx Proxy Manager, vers `runflow_prod_web`, avec son propre certificat.
4. Nommer les conteneurs et le volume avec le préfixe `prod_`, afin d'éviter toute collision avec la préproduction.
5. Étendre le pipeline d'un job déclenché sur la branche `main`.
6. Charger un jeu de données réel, sans les comptes de démonstration.
7. Mettre en place une sauvegarde planifiée de la base, et **tester la restauration**.

---

## 14. Historique du document

| Version | Date | Auteur | Modifications |
|---|---|---|---|
| 1.0 | Août 2026 | Théo Celemani | Rédaction initiale — préproduction opérationnelle, production préparée |
