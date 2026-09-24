# 🚀 Securitech : Simulateur d'Infrastructures Spatiales (2080)

**Securitech** est un projet de simulation immersive de gestion de vaisseau spatial. Il repose sur une architecture d'administration système bien réelle utilisant des **conteneurs LXC sous Debian (Raspberry Pi)**, couplée à un système d'audit de sécurité automatisé et des interfaces web de type SCADA hautement dynamiques.

## 🛰️ Architecture du Vaisseau (Conteneurs LXC)

Le vaisseau est divisé en 5 zones isolées (conteneurs), chacune hébergeant son propre serveur web Apache2 et son propre réseau :

* **🌱 Z1-AgriTech** : Botanique et capteurs environnementaux.

* **💤 Z2-BedTech** : Stase et moniteurs biométriques.

* **🏥 Z3-HealthTech** : Infirmerie centrale avec moteur de rendu d'électrocardiogramme (ECG) en direct (HTML5 Canvas).

* **📡 Z4-OpenSpaceTech** : Hub de communication avec intercepteur de flux, radar d'ondes quantiques et décryptage terminal.

* **☢️ Z5-EnergyTech** : Salle des machines lourde, simulation de réacteur plasma et surveillance thermique critique.

Toutes les interfaces web sont autonomes, alimentées par des bases de données locales (`.csv`), et animées en JavaScript pur pour simuler la vie du vaisseau sans nécessiter de backend lourd.

## 🛡️ Système de Sécurité (Threat Hunting)

Le projet intègre un puissant script d'audit (`audit_zones.sh`) exécuté depuis le Serveur Central (l'hôte Raspberry Pi). Ce script agit comme un système immunitaire pour le vaisseau :

* **Surveillance Continue** : Scanne les conteneurs à la recherche de compromissions (Rootkits, Reverse Shells).

* **Vérifications Critiques** :

  * Détection d'utilisateurs non autorisés (`/etc/passwd`).

  * Détection de portes dérobées SSH (Ajout de clés dans `authorized_keys`).

  * Détection de processus malveillants exécutés depuis `/tmp`.

  * Détection d'effacement de traces (Purge de `auth.log`).

* **Mise en Quarantaine (SCRAM)** : Si une anomalie est détectée, la zone compromise est immédiatement arrêtée (`lxc-stop`).

* **Persistance d'État** : Utilisation d'un système de cache (`.cache-error`) pour conserver les logs d'erreurs même lorsque les conteneurs sont éteints.

## ⚙️ Prérequis et Installation

### Matériel / OS

* Raspberry Pi (ou tout serveur Linux)

* Debian / Ubuntu

* LXC (Linux Containers)

* Apache2 (sur les conteneurs)

### Déploiement basique

1. **Cloner le dépôt :**

   ```
   git clone https://github.com/TonPseudo/Securitech.git
   
   ```

2. **Créer les conteneurs LXC :**
   Les conteneurs doivent être nommés `Z1-AgriTech`, `Z2-BedTech`, etc.

3. **Déployer les interfaces :**
   Copiez les fichiers `index.html` et `*_data.csv` correspondants dans le dossier `/var/www/html/` de chaque conteneur.

4. **Automatiser l'audit :**
   Ajoutez le script d'audit dans votre crontab sur l'hôte pour une vérification toutes les minutes (ou exécutez-le via une boucle infinie).

   ```
   crontab -e
   # Ajouter la ligne suivante :
   * * * * * /bin/bash /root/monitoring/audit_zones.sh
   
   ```

## 🌐 Routage et Réseau

Les conteneurs communiquent avec le Serveur Central via un pont réseau. L'accès internet est partagé via l'interface `wlan0` de l'hôte grâce à des règles `iptables` de type MASQUERADE et FORWARD, injectées dynamiquement au démarrage.

*Projet développé dans le cadre d'une expérimentation alliant administration système, cybersécurité défensive et design d'interfaces de science-fiction.*
