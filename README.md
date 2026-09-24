# Securitech : Simulateur d'Infrastructures Spatiales (2080)

**Securitech** est un projet de simulation de gestion de vaisseau spatial. Il repose sur une architecture d'administration système utilisant des **conteneurs LXC sous Debian (Raspberry Pi)**, couplée à un système d'audit de sécurité automatisé et des interfaces web de type SCADA.

## 🛰️ Architecture du Vaisseau (Conteneurs LXC)

Le vaisseau est divisé en 5 zones isolées (conteneurs), chacune hébergeant son propre serveur web Apache2 et son propre réseau :

* ** Z1-AgriTech** : Botanique et capteurs environnementaux.

* ** Z2-BedTech** : Stase et moniteurs biométriques.

* ** Z3-HealthTech** : Infirmerie centrale avec moteur de rendu d'électrocardiogramme (ECG) en direct (HTML5 Canvas).

* ** Z4-OpenSpaceTech** : Hub de communication avec intercepteur de flux, radar d'ondes quantiques et décryptage terminal.

* ** Z5-EnergyTech** : Salle des machines lourde, simulation de réacteur plasma et surveillance thermique critique.

Toutes les interfaces web sont autonomes, alimentées par des bases de données locales (`.csv`), et animées en JavaScript pur pour simuler la vie du vaisseau sans nécessiter de backend lourd.

## 🛡️ Système de Sécurité (Threat Hunting)

Le projet intègre un script d'audit (`monitoring.sh`) exécuté depuis le Serveur Central (l'hôte Raspberry Pi). Ce script agit comme un système immunitaire pour le vaisseau :

* **Surveillance Continue** : Scanne les conteneurs à la recherche de compromissions.

* **Vérifications Critiques** :

  * Détection d'utilisateurs non autorisés (`/etc/passwd`).

  * Détection de portes dérobées SSH (Ajout de clés dans `authorized_keys`) ou de fichier de configs.

  * Détection de processus malveillants `exe` exécutés depuis `/tmp`.

  * Détection d'effacement de traces (Purge de `auth.log`).

* **Mise en Quarantaine (SCRAM)** : Si une anomalie est détectée, la zone compromise est immédiatement arrêtée (`lxc-stop`).

* **Persistance d'État** : Utilisation d'un système de cache (`.cache-error`) pour conserver les logs d'erreurs même lorsque les conteneurs sont éteints.

## ⚙️ Prérequis et Installation

### Matériel / OS

* Raspberry Pi (ou tout serveur Linux)

* Debian / Ubuntu

* LXC (Linux Containers) -> conteneur en 10.0.3.X de la zone

* Apache2 (sur les conteneurs)

### Déploiement basique


1. **Créer les conteneurs LXC :**
   Les conteneurs doivent être nommés `Z1-AgriTech`, `Z2-BedTech`, etc.

2. **Déployer les interfaces :**
   Copiez les fichiers `index.html` et `*_data.csv` correspondants dans le dossier `/var/www/html/` de chaque conteneur.

3. **Les dossier :** 
   Mettre les dossier html dans `/var/www/html`, le .service dans votre dossier de service, les .bashrc dans votre dossier user (/root), et les autres dossier nommée en zone et le SecureTech à la racine `/`
   
## 🌐 Routage et Réseau

Les conteneurs communiquent avec le Serveur Central via un pont réseau. L'accès internet est partagé via l'hôte grâce à des règles `iptables` de type MASQUERADE et FORWARD, injectées dynamiquement au démarrage.

*Projet développé dans le cadre d'une expérimentation alliant administration système, cybersécurité défensive et design d'interfaces de science-fiction.*
