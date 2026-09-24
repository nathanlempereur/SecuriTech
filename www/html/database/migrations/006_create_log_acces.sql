CREATE TABLE log_acces (
    id BIGINT UNSIGNED AUTO_INCREMENT,
    date_heure DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    type_acces VARCHAR(50) NULL,
    resultat VARCHAR(50) NOT NULL,
    motif_refus VARCHAR(100) NULL,

    numero_serie_lu VARCHAR(100) NOT NULL,

    badgeuse_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED NOT NULL,
    utilisateur_id INT UNSIGNED NULL,

    CONSTRAINT pk_log_acces
        PRIMARY KEY (id),

    CONSTRAINT fk_log_acces_badgeuse
        FOREIGN KEY (badgeuse_id)
        REFERENCES badgeuse(id),

    CONSTRAINT fk_log_acces_zone
        FOREIGN KEY (zone_id)
        REFERENCES zone(id),

    CONSTRAINT fk_log_acces_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id)
);