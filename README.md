# Securitech : Simulateur d'Infrastructures Spatiales (2080)

**Securitech** est un projet de simulation de gestion de vaisseau spatial. Il repose sur une architecture d'administration système utilisant des **conteneurs LXC sous Debian (Raspberry Pi)**, couplée à un système d'audit de sécurité automatisé et des interfaces web de type SCADA.

## 🛰️ Architecture du Vaisseau (Conteneurs LXC)

Le vaisseau est divisé en 5 zones isolées (conteneurs), chacune hébergeant son propre serveur web Apache2 et son propre réseau :

* **Z1-AgriTech** : Botanique et capteurs environnementaux.
  <img width="1900" height="888" alt="image" src="https://github.com/user-attachments/assets/1e66a63b-cf04-4820-b1e7-7f6591c30a1e" />


* **Z2-BedTech** : Stase et moniteurs biométriques.
  <img width="1902" height="885" alt="image" src="https://github.com/user-attachments/assets/6a37a4e4-d7ab-4f3a-917c-402853365b4c" />


* **Z3-HealthTech** : Infirmerie centrale avec moteur de rendu d'électrocardiogramme (ECG) en direct (HTML5 Canvas).
   <img width="1920" height="887" alt="image" src="https://github.com/user-attachments/assets/8c46c4cb-d149-4bbb-a418-fdd053a44ca5" />


* **Z4-OpenSpaceTech** : Hub de communication avec intercepteur de flux, radar d'ondes quantiques et décryptage terminal.
  <img width="1864" height="888" alt="image" src="https://github.com/user-attachments/assets/18f29475-bcfd-43ea-bae9-7536e0b75b4f" />


* **Z5-EnergyTech** : Salle des machines lourde, simulation de réacteur plasma et surveillance thermique critique.
  <img width="1917" height="883" alt="image" src="https://github.com/user-attachments/assets/f2b54664-16f4-4231-9b10-ba24736971de" />



Toutes les interfaces web sont autonomes, alimentées par des bases de données locales (`.csv`), et animées en JavaScript pur pour simuler la vie du vaisseau sans nécessiter de backend lourd.

## 🛡️ Système de Sécurité (Threat Hunting)

Le projet intègre un script d'audit (`monitoring.sh`) exécuté depuis le Serveur Central (l'hôte Raspberry Pi). Ce script agit comme un système immunitaire pour le vaisseau et renvoie les données sur le Dashboard:

<img width="1902" height="889" alt="image" src="https://github.com/user-attachments/assets/851d3616-3c20-4309-8b4f-a2cdded08d5e" />


* **Surveillance Continue** : Scanne les conteneurs à la recherche de compromissions.

* **Vérifications Critiques** :

  * Détection d'utilisateurs non autorisés (`/etc/passwd`).

  * Détection de portes dérobées SSH (Ajout de clés dans `authorized_keys`) ou de fichiers de configs.

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
   Mettre les dossier var et etc dans leurs emplacement de base (a la racine juste ajouter les fichiers des répertoires) , le .service dans votre dossier de service, les .bashrc dans votre dossier user (/root), et les autres dossier nommée en zone et le SecureTech à la racine `/`

4. **Badgeuse :**
   Pour utiliser les scripts python de `badgeuse/hardware` et la gestion dans le dashboard branchez un lecteur de badge RFID, un écran et un Haut parleur pour simuler une badgeuse de zone avec carte RFID (NFC) de préférence sur RaspberryPi 4 model B et liez le dashboard a une BDD Postgres.

   <img width="445" height="668" alt="image" src="https://github.com/user-attachments/assets/98f79794-084f-47e4-b109-79fbdc9cfa9c" />
   
   <img width="309" height="668" alt="image" src="https://github.com/user-attachments/assets/15c2f548-5520-43a6-94c0-673c3f502391" />
   
    <img width="1448" height="668" alt="image" src="https://github.com/user-attachments/assets/0c1d44e2-9084-4aeb-888c-87efff12cf67" />

   
## 🌐 Routage et Réseau

Les conteneurs communiquent avec le Serveur Central via un pont réseau. L'accès internet est partagé via l'hôte grâce à des règles `iptables` de type MASQUERADE et FORWARD, injectées dynamiquement au démarrage.

*Projet développé dans le cadre d'une expérimentation alliant administration système, cybersécurité défensive et design d'interfaces de science-fiction.*
