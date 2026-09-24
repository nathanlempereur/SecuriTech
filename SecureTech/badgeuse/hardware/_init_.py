"""
Module Hardware - EPSI Space Access Control
================================================================================
Ce module gère le contrôle d'accès sur Raspberry Pi 4 Model B :
- Lecteur RFID RC522 (MFRC522 via SPI)
- Écran Grove LCD RGB (I2C)
- LEDs de statut & Buzzer (GPIO)
- Gestion de la base de données SQLite (app.db)

Toutes les classes disposent d'un mode SIMULATION automatique pour permettre
de tester l'application sur un PC (Windows/Mac/Linux) sans matériel physique.
"""

from .db_manager import DatabaseManager
from .rfid_reader import RFIDReader
from .lcd_display import LCDDisplay
from .led_controller import LEDController

__all__ = ["DatabaseManager", "RFIDReader", "LCDDisplay", "LEDController"]
