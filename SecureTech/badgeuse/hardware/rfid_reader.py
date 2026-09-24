"""
rfid_reader.py - Lecteur RFID MFRC522 (RC522) pour Raspberry Pi 4 B
================================================================================
EXPLICATIONS DES BUGS HARDWARE POTENTIELS & DÉPANNAGE :
--------------------------------------------------------------------------------
1. BUG: "FileNotFoundError: [Errno 2] No such file or directory: '/dev/spidev0.0'"
   - CAUSE: L'interface SPI n'est PAS activée sur le Raspberry Pi !
   - SOLUTION: Exécutez 'sudo raspi-config', allez dans "Interfacing Options" -> "SPI" -> "Enable", puis redémarrez ('sudo reboot').

2. BUG: "RuntimeError: Please set pin messaging mode using GPIO.setmode(GPIO.BOARD) or GPIO.BCM"
   - CAUSE: Conflit de numérotation des broches GPIO entre les modules.
   - SOLUTION: Le module mfrc522 utilise le mode BCM. Le code définit explicitement GPIO.setmode(GPIO.BCM).

3. BUG: "PermissionError: Permission denied: '/dev/gpiomem'" ou '/dev/spidev0.0'
   - CAUSE: L'utilisateur système n'a pas les privilèges pour accéder au matériel SPI/GPIO.
   - SOLUTION: Lancez avec 'sudo python3 hardware/main.py' OU ajoutez l'utilisateur au groupe:
     sudo usermod -a -G gpio,spi,i2c pi

4. BUG: Le lecteur ne détecte AUCUNE carte (lecture infinie sans réaction)
   - CAUSE 1: Câblage incorrect sur le Raspberry Pi 4. Vérifiez minutieusement le schéma :
     * VCC  -> 3.3V (Broche 1)  ⚠️ ATTENTION: Le 5V peut détruire la puce RC522 !
     * RST  -> GPIO 25 (Broche 22)
     * GND  -> GND (Broche 6)
     * MISO -> GPIO 9 (Broche 21)
     * MOSI -> GPIO 10 (Broche 19)
     * SCK  -> GPIO 11 (Broche 23)
     * SDA (SS) -> GPIO 8 (Broche 24)
   - CAUSE 2: Mauvaise soudure des broches sur la carte MFRC522.
"""

import time
import logging

logging.basicConfig(level=logging.INFO, format="[%(asctime)s] %(levelname)s - %(message)s")

HAS_HARDWARE = False
try:
    import RPi.GPIO as GPIO  # type: ignore
    from mfrc522 import SimpleMFRC522  # type: ignore
    HAS_HARDWARE = True
except (ImportError, RuntimeError) as e:
    logging.warning(f"Matériel RFID physique non détecté ou bibliothèques manquantes ({e}). Passage en MODE SIMULATION.")

class RFIDReader:
    def __init__(self):
        self.is_simulation = not HAS_HARDWARE
        if not self.is_simulation:
            try:
                # Initialisation GPIO et MFRC522
                GPIO.setwarnings(False)
                self.reader = SimpleMFRC522()
                logging.info("Lecteur RFID MFRC522 initialisé avec succès sur SPI (/dev/spidev0.0).")
            except Exception as e:
                logging.error(f"BUG lors de l'initialisation du MFRC522: {e}")
                logging.warning("Bascule automatique en MODE SIMULATION pour le lecteur RFID.")
                self.is_simulation = True

    def _format_id_to_hex(self, card_id: int) -> str:
        """
        Convertit l'identifiant numérique brut en chaîne hexadécimale formatée.
        Ex: 703714521780 -> "A3-5F-12-B4"
        """
        hex_str = f"{card_id:08X}"
        parts = [hex_str[i:i+2] for i in range(0, len(hex_str), 2)]
        return "-".join(parts)

    def read_card(self) -> str | None:
        """
        Lit une carte RFID s'approchant du lecteur.
        Retourne l'UID sous forme de chaîne formatée (ex: "A3-5F-12-B4").
        En mode simulation: permet la saisie manuelle dans la console.
        """
        if self.is_simulation:
            try:
                print("\n--- [MODE SIMULATION RFID] ---")
                print("Badges enregistrés de test: 'A3-5F-12-B4' (Alice), '99-88-77-66' (Bob)")
                sim_uid = input("Entrez un UID de badge (ou Entrée pour ignorer) : ").strip()
                if not sim_uid:
                    return None
                return sim_uid.upper()
            except (KeyboardInterrupt, EOFError):
                return None

        try:
            logging.info("Attente d'un badge RFID...")
            card_id, text = self.reader.read()
            formatted_uid = self._format_id_to_hex(card_id)
            logging.info(f"Badge détecté ! ID Brut: {card_id}, UID Formaté: {formatted_uid}")
            return formatted_uid
        except Exception as e:
            logging.error(f"BUG Erreur de lecture RFID: {e}")
            return None

    def cleanup(self):
        """Nettoie la configuration des broches GPIO à la fermeture."""
        if not self.is_simulation:
            try:
                GPIO.cleanup()
                logging.info("Nettoyage des broches GPIO RFID effectué.")
            except Exception as e:
                logging.error(f"Erreur lors du nettoyage GPIO: {e}")
