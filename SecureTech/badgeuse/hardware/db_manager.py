"""
db_manager.py - Gestionnaire Supabase adapté au schéma d'accès EPSI
====================================================================
Lecture seule de la base Supabase et enregistrement des logs.
Gère les badges sans date d'expiration (date_expiration IS NULL).
"""

import os
import logging
from pathlib import Path
import psycopg2

# Chargeur .env natif sans dépendance externe
def load_env_file(env_path):
    if env_path.is_file():
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    key, value = line.split("=", 1)
                    os.environ.setdefault(key.strip(), value.strip().strip("'\""))

BASE_DIR = Path(__file__).resolve().parent.parent
load_env_file(BASE_DIR / ".env")

logging.basicConfig(level=logging.INFO, format="[%(asctime)s] %(levelname)s - %(message)s")


class DatabaseManager:
    def __init__(self, badgeuse_code: str = "BADGEUSE-01"):
        self.host = os.getenv("DB_HOST", "aws-1-eu-west-1.pooler.supabase.com")
        self.port = int(os.getenv("DB_PORT", 5432))
        self.database = os.getenv("DB_NAME", "postgres")
        self.user = os.getenv("DB_USER", "postgres.ywnwzmbcppuzajwlxsvb")
        self.password = os.getenv("DB_PASSWORD", "")

        # Récupération automatique de la zone à l'instanciation
        self.zone_id = self.get_zone_id_from_badgeuse(badgeuse_code)

        if not self.password:
            logging.warning("ATTENTION: 'DB_PASSWORD' n'est pas défini dans le fichier .env !")

    def _get_connection(self):
        return psycopg2.connect(
            host=self.host,
            port=self.port,
            dbname=self.database,
            user=self.user,
            password=self.password,
            connect_timeout=5
        )

    def _get_badgeuse_id(self, conn, zone_id: int) -> int:
        """Récupère la badgeuse active pour la zone demandée (ou 1 par défaut)."""
        try:
            with conn.cursor() as cur:
                cur.execute("SELECT id FROM public.badgeuse WHERE zone_id = %s AND etat = true LIMIT 1;", (zone_id,))
                res = cur.fetchone()
                return res[0] if res else 1
        except Exception:
            return 1
    
    def get_zone_id_from_badgeuse(self, badgeuse_code: str) -> int:
        """Récupère le zone_id associé au code de la badgeuse dans Supabase."""
        conn = None
        try:
            conn = self._get_connection()
            with conn.cursor() as cur:
                # Adapte 'code' si le nom de la colonne dans ta table badgeuse est différent (ex: 'nom_badgeuse')
                cur.execute("SELECT zone_id FROM public.badgeuse WHERE code = %s LIMIT 1;", (badgeuse_code,))
                res = cur.fetchone()
                return res[0] if (res and res[0] is not None) else 1
        except Exception as e:
            logging.error(f"Erreur lors de la récupération de la zone pour '{badgeuse_code}' : {e}")
            return 1
        finally:
            if conn:
                conn.close()

    def check_access(self, uid: str, zone_id: int = 1) -> tuple[bool, str]:
        """
        Vérifie les droits d'un badge scanné.
        - Si date_expiration est NULL, le badge est considéré actif (sauf si etat = False).
        """
        clean_uid = uid.strip().upper()
        conn = None

        try:
            conn = self._get_connection()
            with conn.cursor() as cur:
                # 1. Récupération des infos du badge & de l'utilisateur
                # Le test (b.date_expiration IS NOT NULL AND b.date_expiration < CURRENT_TIMESTAMP)
                # renvoie FALSE si date_expiration est NULL (ce qui signifie : badge NON expiré).
                query_badge = """
                    SELECT 
                        b.id AS badge_id,
                        b.etat AS badge_etat,
                        (b.date_expiration IS NOT NULL AND b.date_expiration < CURRENT_TIMESTAMP) AS is_expired,
                        u.id AS utilisateur_id,
                        u.matricule,
                        u.etat AS utilisateur_etat
                    FROM public.badge b
                    JOIN public.utilisateur u ON b.utilisateur_id = u.id
                    WHERE UPPER(b.numero_serie) = %s
                    LIMIT 1;
                """
                cur.execute(query_badge, (clean_uid,))
                record = cur.fetchone()

                if not record:
                    self._internal_log(conn, clean_uid, zone_id, "REFUSE", "Badge inconnu", None)
                    return False, "Badge Inconnu"

                badge_id, badge_etat, is_expired, user_id, matricule, user_etat = record

                # 2. Vérification de l'état du badge
                if not badge_etat:
                    self._internal_log(conn, clean_uid, zone_id, "REFUSE", "Badge désactivé", user_id)
                    return False, f"{matricule} (Badge Inactif)"

                # 3. Vérification de l'expiration du badge
                if is_expired:
                    self._internal_log(conn, clean_uid, zone_id, "REFUSE", "Badge expiré", user_id)
                    return False, f"{matricule} (Expiré)"

                # 4. Vérification de l'état de l'utilisateur
                if not user_etat:
                    self._internal_log(conn, clean_uid, zone_id, "REFUSE", "Utilisateur désactivé", user_id)
                    return False, f"{matricule} (Inactif)"

                # 5. Vérification de l'autorisation d'accès à la zone
                query_auth = """
                    SELECT id FROM public.autorisation
                    WHERE utilisateur_id = %s 
                      AND zone_id = %s
                      AND date_debut <= CURRENT_TIMESTAMP
                      AND (date_fin IS NULL OR date_fin >= CURRENT_TIMESTAMP)
                    LIMIT 1;
                """
                cur.execute(query_auth, (user_id, zone_id))
                has_auth = cur.fetchone()

                if not has_auth:
                    self._internal_log(conn, clean_uid, zone_id, "REFUSE", "Non autorisé pour cette zone", user_id)
                    return False, f"{matricule}    ;(Non Autorisé)"

                # Accès accordé
                self._internal_log(conn, clean_uid, zone_id, "AUTORISE", None, user_id)
                return True, matricule

        except Exception as e:
            logging.error(f"Erreur lors de la vérification dans Supabase : {e}")
            return False, "Erreur BDD"
        finally:
            if conn:
                conn.close()

    def _internal_log(self, conn, uid: str, zone_id: int, resultat: str, motif_refus: str | None, user_id: int | None):
        """Insère le résultat du scan dans la table public.log_acces."""
        try:
            badgeuse_id = self._get_badgeuse_id(conn, zone_id)
            with conn.cursor() as cur:
                query = """
                    INSERT INTO public.log_acces 
                    (numero_serie_lu, zone_id, badgeuse_id, utilisateur_id, resultat, motif_refus, type_acces)
                    VALUES (%s, %s, %s, %s, %s, %s, 'BADGE_RFID');
                """
                cur.execute(query, (uid, zone_id, badgeuse_id, user_id, resultat, motif_refus))
                conn.commit()
                logging.info(f"Log Supabase -> {resultat} | Badge: {uid} | Utilisateur ID: {user_id} | Motif: {motif_refus}")
        except Exception as e:
            logging.error(f"Erreur écriture log_acces : {e}")

    def log_access(self, uid: str, user_name: str, zone_id: int, status: str) -> None:
        """Méthode de compatibilité main.py (les logs sont enregistrés automatiquement dans check_access)."""
        pass
