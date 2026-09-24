CREATE TABLE badgeuse (
    id INT UNSIGNED AUTO_INCREMENT,
    code VARCHAR(50) NOT NULL,
    zone_id INT UNSIGNED NOT NULL,
    etat BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_badgeuse
        PRIMARY KEY (id),

    CONSTRAINT uq_badgeuse_code
        UNIQUE (code),

    CONSTRAINT fk_badgeuse_zone
        FOREIGN KEY (zone_id)
        REFERENCES zone(id)
);

