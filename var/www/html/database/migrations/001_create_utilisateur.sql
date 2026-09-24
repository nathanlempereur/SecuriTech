CREATE TABLE utilisateur (
    id INT UNSIGNED AUTO_INCREMENT,
    matricule VARCHAR(50) NOT NULL,
    etat BOOLEAN NOT NULL DEFAULT TRUE,

    CONSTRAINT pk_utilisateur PRIMARY KEY (id),
    CONSTRAINT uq_utilisateur_matricule UNIQUE (matricule)
);
