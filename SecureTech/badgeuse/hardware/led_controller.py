"""
led_controller.py - Contrôleur de LEDs de statut et Speaker (GPIO) pour Raspberry Pi 4 B
================================================================================
EXPLICATIONS DES BUGS HARDWARE POTENTIELS & DÉPANNAGE GPIO :
--------------------------------------------------------------------------------
1. BUG: "RuntimeError: Cannot determine SOC peripheral base address" ou "Permission error"
   - CAUSE: Droits d'accès insuffisants sur les périphériques mémoire /dev/gpiomem.
   - SOLUTION: Lancez avec 'sudo python3 hardware/main.py' ou ajoutez l'utilisateur au groupe gpio:
     `sudo usermod -a -G gpio pi`

2. BUG: Les LEDs ne s'allument pas
   - CAUSE 1: LED branchée à l'envers (l'anode +, la broche la plus longue, doit être côté GPIO avec une résistance 220Ω).
   - CAUSE 2: Résistance oubliée ou trop forte (Ex: 10kΩ au lieu de 220Ω).
   - CAUSE 3: Broches GPIO inversées (Ex: numérotation BCM 17 vs Broche physique 11).

BROCHES CONSEILLÉES (Mode BCM) :
- LED Verte  : GPIO 17 (Broche physique 11) -> Accès Autorisé
- LED Rouge  : GPIO 27 (Broche physique 13) -> Accès Refusé
- Speaker    : GPIO 22 (Broche physique 15) -> Signal sonore
- GND (Masse) : Broche physique 9, 14, 20, 25, 30, 34 ou 39
"""

import time
import logging

logging.basicConfig(level=logging.INFO, format="[%(asctime)s] %(levelname)s - %(message)s")

HAS_GPIO = False
try:
    import RPi.GPIO as GPIO  # type: ignore
    HAS_GPIO = True
except (ImportError, RuntimeError):
    HAS_GPIO = False

class LEDController:
    # On utilise la numérotation physique (BOARD) pour être compatible avec MFRC522
    BLUE_LED_PIN = 11  # Broche 11 (GPIO 17)
    RED_LED_PIN = 13   # Broche 13 (GPIO 27)
    BUZZER_PIN = 32    # Broche 32 (GPIO 12) - Utilisé pour le Speaker

    def __init__(self, blue_pin: int = BLUE_LED_PIN, red_pin: int = RED_LED_PIN, buzzer_pin: int = BUZZER_PIN):
        self.is_simulation = not HAS_GPIO
        self.blue_pin = blue_pin
        self.red_pin = red_pin
        self.buzzer_pin = buzzer_pin
        self.buzzer_pwm = None

        if not self.is_simulation:
            try:
                # MFRC522 utilise GPIO.BOARD par défaut, on s'aligne !
                GPIO.setmode(GPIO.BOARD)
                GPIO.setwarnings(False)
                GPIO.setup(self.blue_pin, GPIO.OUT, initial=GPIO.LOW)
                GPIO.setup(self.red_pin, GPIO.OUT, initial=GPIO.LOW)
                GPIO.setup(self.buzzer_pin, GPIO.OUT, initial=GPIO.LOW)
                
                # Configuration du PWM pour générer un vrai son sur le Speaker
                self.buzzer_pwm = GPIO.PWM(self.buzzer_pin, 1000) # Fréquence initiale 1000 Hz
                
                logging.info(f"GPIO LEDs & Speaker initialisés (Bleu: GPIO{self.blue_pin}, Rouge: GPIO{self.red_pin}, Speaker: GPIO{self.buzzer_pin}).")
            except Exception as e:
                logging.error(f"BUG lors de l'initialisation des broches GPIO: {e}")
                logging.warning("Bascule automatique en MODE SIMULATION pour les LEDs et le Speaker.")
                self.is_simulation = True

    def grant_access_signal(self):
        """Son de succès enrichi : Arpège cristal ascendant + fondu sonore (decay)"""
        if self.is_simulation:
            logging.info("SIGNAL HARDWARE: [LED VERTE ALLUMÉE] + [SON SUCCÈS HAUTE QUALITÉ]")
            return

        try:
            GPIO.output(self.blue_pin, GPIO.HIGH)
            GPIO.output(self.red_pin, GPIO.LOW)

            if self.buzzer_pwm:
                # 1. Arpège rapide ascendant (Do6 -> Mi6 -> Sol6 -> Do7)
                arpeggio = [
                    
                    (1175, 0.1),
		    (2325, 0.1)
                    
                ]

                for freq, duree in arpeggio:
                    self.buzzer_pwm.ChangeFrequency(freq)
                    self.buzzer_pwm.start(50)
                    time.sleep(duree)

                # 2. Fondu sonore naturel sur la dernière note (atténuation du volume)
                for volume in (35, 20, 8, 2):
                    self.buzzer_pwm.ChangeDutyCycle(volume)
                    time.sleep(0.025)

                self.buzzer_pwm.stop()
                self.buzzer_pwm.ChangeDutyCycle(50)  # Remise du volume par défaut
        except Exception as e:
            logging.error(f"Erreur commande GPIO Vert : {e}")

    def deny_access_signal(self):
        """Son d'échec enrichi : Chute de fréquence dynamique (glissando descendant)"""
        if self.is_simulation:
            logging.info("SIGNAL HARDWARE: [LED ROUGE ALLUMÉE] + [SON ÉCHEC DYNAMIQUE]")
            return

        try:
            GPIO.output(self.blue_pin, GPIO.LOW)
            GPIO.output(self.red_pin, GPIO.HIGH)

            if self.buzzer_pwm:
                # Deux bips d'erreur avec effet de chute de ton (Pitch Bend)
                for _ in range(2):
                    self.buzzer_pwm.start(50)
                    # Balayage rapide de 500 Hz à 180 Hz
                    for freq in range(500, 180, -25):
                        self.buzzer_pwm.ChangeFrequency(freq)
                        time.sleep(0.008)
                    self.buzzer_pwm.stop()
                    time.sleep(0.06)
        except Exception as e:
            logging.error(f"Erreur commande GPIO Rouge : {e}")

    def turn_off(self):
        """Éteint toutes les LEDs."""
        if self.is_simulation:
            return
        try:
            GPIO.output(self.blue_pin, GPIO.LOW)
            GPIO.output(self.red_pin, GPIO.LOW)
            if self.buzzer_pwm:
                self.buzzer_pwm.stop()
        except Exception as e:
            logging.error(f"Erreur éteinte GPIO: {e}")

    def cleanup(self):
        """Nettoie la configuration des broches GPIO à la fermeture."""
        self.turn_off()
        if not self.is_simulation:
            try:
                GPIO.cleanup([self.blue_pin, self.red_pin, self.buzzer_pin])
                logging.info("Broches GPIO LEDs & Speaker nettoyées.")
            except Exception as e:
                logging.error(f"Erreur nettoyage GPIO LEDs: {e}")
