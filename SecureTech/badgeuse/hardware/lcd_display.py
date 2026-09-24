"""
lcd_display.py - Contrôle de l'écran Grove LCD RGB (I2C) pour Raspberry Pi 4 B
================================================================================
EXPLICATIONS DES BUGS HARDWARE POTENTIELS & DÉPANNAGE ÉCRAN LCD :
--------------------------------------------------------------------------------
1. BUG: "FileNotFoundError: [Errno 2] No such file or directory: '/dev/i2c-1'"
   - CAUSE: L'interface I2C n'est pas activée sur le Raspberry Pi !
   - SOLUTION: Exécutez 'sudo raspi-config', allez dans "Interfacing Options" -> "I2C" -> "Enable", puis redémarrez ('sudo reboot').

2. BUG: "OSError: [Errno 121] Remote I/O error"
   - CAUSE 1: Mauvaise adresse I2C ou mauvais câble/port Grove.
   - CAUSE 2: L'écran n'est pas alimenté correctement (connecté sur le port I2C 5V du Shield Grove).
   - SOLUTION: Vérifiez les adresses I2C avec la commande: `sudo i2cdetect -y 1`
     L'écran Grove LCD RGB utilise généralement les adresses I2C `0x3e` (pour le texte) et `0x62` (pour la couleur RGB).

3. BUG: L'écran reste noir ou affiche des caractères étranges / carrés noirs
   - CAUSE: Potentiomètre de contraste non ajusté ou bus I2C désynchronisé.
   - SOLUTION: Réinitialisez l'écran en le débranchant/rebranchant ou via un reset logiciel (`clear()`).
"""

import time
import logging

logging.basicConfig(level=logging.INFO, format="[%(asctime)s] %(levelname)s - %(message)s")

HAS_HARDWARE_LCD = False
try:
    from smbus2 import SMBus  # type: ignore
    HAS_HARDWARE_LCD = True
except (ImportError, RuntimeError):
    HAS_HARDWARE_LCD = False

class LCDDisplay:
    # Adresses I2C par défaut du Grove LCD RGB
    DISPLAY_RGB_ADDR = 0x62
    DISPLAY_TEXT_ADDR = 0x3e

    def __init__(self):
        self.is_simulation = not HAS_HARDWARE_LCD
        self.bus = None

        if not self.is_simulation:
            try:
                self.bus = SMBus(1)
                self.init_lcd()
                logging.info("Écran Grove LCD RGB initialisé avec succès sur le bus I2C (/dev/i2c-1).")
            except Exception as e:
                logging.error(f"BUG lors de l'initialisation de l'écran I2C: {e}")
                logging.warning("Bascule automatique en MODE SIMULATION pour l'écran LCD.")
                self.is_simulation = True

    def init_lcd(self):
        """Initialise la configuration matérielle de l'écran Grove LCD RGB."""
        if self.is_simulation or not self.bus:
            return
        try:
            # Séquence d'init correcte pour le contrôleur Grove LCD RGB (JHD1214)
            # Le registre de commande est 0x00 (et non 0x80 qui est pour d'autres LCD I2C)
            time.sleep(0.05)                                        # Attente démarrage écran
            self._write_command(self.DISPLAY_TEXT_ADDR, 0x28)       # Function set: 2 lignes, 5x8
            time.sleep(0.005)
            self._write_command(self.DISPLAY_TEXT_ADDR, 0x28)       # Répété 2x pour fiabilité
            time.sleep(0.005)
            self._write_command(self.DISPLAY_TEXT_ADDR, 0x0C)       # Display ON, Cursor OFF
            time.sleep(0.005)
            self._write_command(self.DISPLAY_TEXT_ADDR, 0x01)       # Clear display
            time.sleep(0.002)                                       # Clear nécessite >= 1.5ms !
            self._write_command(self.DISPLAY_TEXT_ADDR, 0x06)       # Entry mode: gauche à droite
            time.sleep(0.005)
        except Exception as e:
            logging.error(f"Erreur d'initialisation LCD: {e}")

    def _write_command(self, addr: int, cmd: int):
        """Envoie une commande au Grove LCD RGB.
        Le registre correct pour les commandes est 0x00 (et non 0x80).
        """
        if self.bus:
            self.bus.write_byte_data(addr, 0x00, cmd)

    def set_color(self, r: int, g: int, b: int):
        """Définit la couleur du rétroéclairage RGB (valeurs entre 0 et 255)."""
        if self.is_simulation or not self.bus:
            return
        try:
            self.bus.write_byte_data(self.DISPLAY_RGB_ADDR, 0x00, 0x00)
            self.bus.write_byte_data(self.DISPLAY_RGB_ADDR, 0x01, 0x00)
            self.bus.write_byte_data(self.DISPLAY_RGB_ADDR, 0x08, 0xAA)
            self.bus.write_byte_data(self.DISPLAY_RGB_ADDR, 0x04, r)
            self.bus.write_byte_data(self.DISPLAY_RGB_ADDR, 0x03, g)
            self.bus.write_byte_data(self.DISPLAY_RGB_ADDR, 0x02, b)
        except Exception as e:
            logging.error(f"BUG Erreur de modification couleur RGB ({r},{g},{b}): {e}")

    def write_text(self, line1: str, line2: str = ""):
        """Affiche du texte sur les deux lignes de l'écran (max 16 caractères par ligne)."""
        l1 = line1[:16].ljust(16)
        l2 = line2[:16].ljust(16)

        if self.is_simulation:
            print("\n┌────────────────────────┐")
            print(f"│ {l1} │")
            print(f"│ {l2} │")
            print("└────────────────────────┘")
            return

        try:
            self._write_command(self.DISPLAY_TEXT_ADDR, 0x01)  # Clear display
            time.sleep(0.003)                                   # Clear nécessite >= 1.5ms !
            # Ligne 1 - registre 0x40 pour l'envoi de données de caractère
            for char in l1:
                self.bus.write_byte_data(self.DISPLAY_TEXT_ADDR, 0x40, ord(char))
            # Ligne 2 - commande 0xC0 pour placer le curseur au début de la ligne 2
            self._write_command(self.DISPLAY_TEXT_ADDR, 0xC0)
            time.sleep(0.001)
            for char in l2:
                self.bus.write_byte_data(self.DISPLAY_TEXT_ADDR, 0x40, ord(char))
        except Exception as e:
            logging.error(f"BUG Erreur d'écriture de texte LCD: {e}")

    def display_standby(self, zone_name):
        """Mode attente: Rétroéclairage Bleu, message de bienvenue.
        zone_name : sera fourni par la BDD plus tard (ex: 'Zone {current_zone_id}', 'Labo EPSI').
        """
        self.set_color(0, 128, 255) # Bleu
        # Ligne 1: Nom de la zone (depuis la BDD plus tard), Ligne 2: invitation à scanner
        self.write_text(zone_name, "Scan en attente")

    def display_granted(self, user_name: str, zone_name: str = "Labo EPSI"):
        """Accès autorisé: Rétroéclairage Vert, nom de l'utilisateur."""
        self.set_color(0, 255, 0) # Vert
        self.write_text("ACCES AUTORISE", user_name)

    def display_denied(self, user_name: str = "Inconnu"):
        """Accès refusé: Rétroéclairage Rouge, avertissement."""
        self.set_color(255, 0, 0) # Rouge
        self.write_text(" ACCES REFUSE ! ", user_name)

    def cleanup(self):
        """Efface l'écran et éteint la lumière à la fermeture."""
        self.set_color(0, 0, 0)
        self.write_text("", "")
        if self.bus:
            try:
                self.bus.close()
            except Exception:
                pass
