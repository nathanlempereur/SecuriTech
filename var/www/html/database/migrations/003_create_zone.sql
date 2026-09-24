CREATE TABLE zone (
    id INT UNSIGNED AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    criticite TINYINT UNSIGNED NOT NULL DEFAULT 1,
    etat BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_zone
        PRIMARY KEY (id),

    CONSTRAINT uq_zone_nom
        UNIQUE (nom),

    CONSTRAINT chk_zone_criticite
        CHECK (criticite BETWEEN 1 AND 5)
);

