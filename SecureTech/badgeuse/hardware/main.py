"""
main.py - Script Principal de Contrôle d'Accès RFID & Matériel sur Raspberry Pi 4 B
================================================================================
DESCRIPTION DU PROCESSUS HARDWARE :
--------------------------------------------------------------------------------
1. Initialisation de la BDD SQLite (app.db en mode WAL).
2. Initialisation de l'écran Grove LCD RGB, du lecteur MFRC522 et du contrôleur GPIO.
3. Boucle infinie d'écoute RFID :
   - Attente du passage d'une carte/badge NFC.
   - Lecture du UID (ex: "A3-5F-12-B4").
   - Interrogation de la table 'users' via DatabaseManager pour vérifier l'accès à la Zone 1.
   - Si AUTORISÉ :
     * Écran LCD passa au VERT avec nom de l'utilisateur.
     * LED Verte ALLUMÉE + 1 Bip sonore.
     * Log 'ALLOWED' enregistré dans la BDD.
   - Si REFUSÉ :
     * Écran LCD passe au ROUGE avec "ACCES REFUSE".
     * LED Rouge ALLUMÉE + 3 Bips rapides d'alerte.
     * Log 'DENIED' enregistré dans la BDD.
   - Temporisation de 2.5 secondes puis retour en mode attente (LCD Bleu).

LANCEMENT & DÉPANNAGE MATÉRIEL :
--------------------------------------------------------------------------------
- Exécution directe sur Raspberry Pi :
  `sudo python3 -m hardware.main`  ou  `sudo python3 hardware/main.py`

- Si vous testez sur PC (Windows/Mac) :
  `python -m hardware.main` (Passage automatique en MODE SIMULATION interactif)

- Pour lancer automatiquement au démarrage du Raspberry Pi (Service systemd) :
  Créez le fichier '/etc/systemd/system/epsi-access.service' :
  [Unit]
  Description=EPSI Space Access Control Hardware Daemon
  After=network.target

  [Service]
  ExecStart=/usr/bin/python3 /home/pi/Workshop_EPSI_SPACE/hardware/main.py
  WorkingDirectory=/home/pi/Workshop_EPSI_SPACE
  StandardOutput=inherit
  StandardError=inherit
  Restart=always
  User=root

  [Install]
  WantedBy=multi-user.target
"""

import sys
import time
import logging
from pathlib import Path

# S'assurer que le dossier racine du projet est dans sys.path
BASE_DIR = Path(__file__).resolve().parent.parent
if str(BASE_DIR) not in sys.path:
    sys.path.insert(0, str(BASE_DIR))

from hardware.db_manager import DatabaseManager
from hardware.rfid_reader import RFIDReader
from hardware.lcd_display import LCDDisplay
from hardware.led_controller import LEDController

logging.basicConfig(level=logging.INFO, format="[%(asctime)s] %(levelname)s - %(message)s")

def run_hardware_loop():
    logging.info("=" * 60)
    logging.info("  DÉMARRAGE DU SYSTÈME EPSI SPACE ACCESS CONTROL (HARDWARE)  ")
    logging.info("=" * 60)

    # 1. Initialisation des composants matériels
    db = DatabaseManager()
    current_zone_id = db.zone_id

    rfid = RFIDReader()
    lcd = LCDDisplay()
    leds = LEDController()

    # 2. Mise en veille initiale
    logging.info(f"BADGEUSE-01 associée à la Zone ID : {current_zone_id}")
    lcd.display_standby(f"Zone {current_zone_id}")

    last_scan_time = 0
    last_uid = None
    DEBOUNCE_DELAY = 2.0  # Évite les scans répétés de la même carte en 2 secondes

    try:
        while True:
            # Attente de lecture d'une carte RFID
            uid = rfid.read_card()

            if not uid:
                time.sleep(0.2)
                continue

            current_time = time.time()
            # Filtre anti-rebond pour éviter de scanner la même carte plusieurs fois d'affilée
            if uid == last_uid and (current_time - last_scan_time) < DEBOUNCE_DELAY:
                time.sleep(0.2)
                continue

            last_uid = uid
            last_scan_time = current_time

            logging.info(f"--- TRAITEMENT DU BADGE DETECTE: {uid} ---")

            # 3. Vérification des droits dans la BDD SQLite
            is_allowed, user_name = db.check_access(uid, zone_id=current_zone_id)

            # 4. Action & Réactions Matérielles
            if is_allowed:
                logging.info(f"ACCÈS ACCORDÉ à '{user_name}' pour la zone {current_zone_id}.")
                lcd.display_granted(user_name=user_name, zone_name=f"Zone {current_zone_id}")
                leds.grant_access_signal()
                db.log_access(uid=uid, user_name=user_name, zone_id=current_zone_id, status="ALLOWED")
            else:
                logging.warning(f"ACCÈS REFUSÉ pour l'UID {uid} ('{user_name}').")
                lcd.display_denied(user_name=user_name)
                leds.deny_access_signal()
                db.log_access(uid=uid, user_name=user_name, zone_id=current_zone_id, status="DENIED")

            # Maintient l'affichage de résultat pendant 2.5 secondes
            time.sleep(2.5)

            # Éteint les LEDs de statut et repasse l'écran en attente
            leds.turn_off()
            # Correction ici pour passer le paramètre obligatoire
            lcd.display_standby(zone_name="Zone "+str(current_zone_id))

    except KeyboardInterrupt:
        logging.info("\nInterruption utilisateur (Ctrl+C). Arrêt propre du système...")
    except Exception as e:
        logging.critical(f"BUG Erreur inattendue dans la boucle principale hardware: {e}", exc_info=True)
    finally:
        # Nettoyage sécurisé de toutes les broches GPIO & bus I2C/SPI
        leds.cleanup()
        lcd.cleanup()
        rfid.cleanup()
        logging.info("Nettoyage matériel terminé. Système arrêté.")

if __name__ == "__main__":
    run_hardware_loop()
    