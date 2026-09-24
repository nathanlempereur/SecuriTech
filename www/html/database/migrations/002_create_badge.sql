CREATE TABLE badge (
    id INT UNSIGNED AUTO_INCREMENT,
    utilisateur_id INT UNSIGNED NOT NULL,
    numero_serie VARCHAR(100) NOT NULL,
    etat BOOLEAN NOT NULL DEFAULT TRUE,
    date_expiration DATETIME NULL,

    CONSTRAINT pk_badge
        PRIMARY KEY (id),

    CONSTRAINT uq_badge_numero_serie
        UNIQUE (numero_serie),

    CONSTRAINT uq_badge_utilisateur
        UNIQUE (utilisateur_id),

    CONSTRAINT fk_badge_utilisateur
        FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateur(id)
);

