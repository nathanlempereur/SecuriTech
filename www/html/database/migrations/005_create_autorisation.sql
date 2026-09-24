CREATE TABLE autorisation (
    id INT UNSIGNED AUTO_INCREMENT,
    utilisateur_id INT UNSIGNED NOT NULL,
    zone_id INT UNSIGNED NOT NULL,
    date_debut DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME NULL,

    CONSTRAINT pk_autorisation
        PRIMARY KEY (id),

    CONSTRAINT fk_autorisation_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id),

    CONSTRAINT fk_autorisation_zone
        FOREIGN KEY (zone_id)
        REFERENCES zone(id),

    CONSTRAINT chk_autorisation_dates
        CHECK (
            date_fin IS NULL
            OR date_fin >= date_debut
        )
);

