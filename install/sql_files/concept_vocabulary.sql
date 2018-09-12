DROP SEQUENCE IF EXISTS concept_id_seq CASCADE;
DROP SEQUENCE IF EXISTS concept_properties_id_seq CASCADE;
DROP SEQUENCE IF EXISTS concept_source_id_seq CASCADE;
DROP SEQUENCE IF EXISTS term_id_seq CASCADE;
DROP SEQUENCE IF EXISTS category_id_seq CASCADE;
DROP TABLE IF EXISTS concept CASCADE;
DROP TABLE IF EXISTS concept_properties CASCADE;
DROP TABLE IF EXISTS concept_category CASCADE;
DROP TABLE IF EXISTS concept_source CASCADE;
DROP TABLE IF EXISTS related_concept CASCADE;
DROP TABLE IF EXISTS broader_concept CASCADE;
DROP TABLE IF EXISTS term CASCADE;
DROP TABLE IF EXISTS category CASCADE;
DROP TABLE IF EXISTS concept_lookup CASCADE;

CREATE TABLE concept (id SERIAL PRIMARY KEY,
    deprecated BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE category (id SERIAL PRIMARY KEY,
    value TEXT NOT NULL,
    description TEXT DEFAULT NULL,
    domain TEXT DEFAULT NULL,
    range TEXT DEFAULT NULL
);

CREATE TABLE term (id SERIAL PRIMARY KEY,
    concept_id INT NOT NULL,
    value TEXT NOT NULL,
    preferred BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (concept_id) REFERENCES concept (id)
);


CREATE TABLE concept_source (id SERIAL PRIMARY KEY,
    concept_id INT NOT NULL,
    citation TEXT DEFAULT NULL,
    url TEXT DEFAULT NULL,
    found_data TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    FOREIGN KEY (concept_id) REFERENCES concept (id)
);

CREATE TABLE concept_category (concept_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY(concept_id, category_id),
    FOREIGN KEY (concept_id) REFERENCES concept (id),
    FOREIGN KEY (category_id) REFERENCES category (id)
);


CREATE TABLE concept_properties (id SERIAL PRIMARY KEY,
    concept_id INT NOT NULL,
    type TEXT NOT NULL,
    value TEXT NOT NULL,
    FOREIGN KEY (concept_id) REFERENCES concept (id)
);

CREATE TABLE broader_concept (narrower_id INT NOT NULL,
    broader_id INT NOT NULL,
    PRIMARY KEY(narrower_id, broader_id),
    FOREIGN KEY (narrower_id) REFERENCES concept (id),
    FOREIGN KEY (broader_id) REFERENCES concept (id)
);


CREATE TABLE related_concept (concept_id INT NOT NULL,
    related_id INT NOT NULL,
    PRIMARY KEY(concept_id, related_id),
    FOREIGN KEY (concept_id) REFERENCES concept (id),
    FOREIGN KEY (related_id) REFERENCES concept (id)
);

CREATE TABLE concept_lookup (old_concept INT NOT NULL,
    new_concept INT NOT NULL,
    PRIMARY KEY(old_concept, new_concept),
    FOREIGN KEY (old_concept) REFERENCES concept (id),
    FOREIGN KEY (new_concept) REFERENCES concept (id)
);

CREATE INDEX term_idx1 ON term (concept_id);
CREATE INDEX concept_source_idx1 ON concept_source (concept_id);
CREATE INDEX concept_category_idx1 ON concept_category (concept_id);
CREATE INDEX concept_category_idx2 ON concept_category (category_id);
CREATE INDEX concept_properties_idx1 ON concept_properties (concept_id);
CREATE INDEX broader_concept_idx1 ON broader_concept (broader_id);
CREATE INDEX broader_concept_idx2 ON broader_concept (narrower_id);
CREATE INDEX related_concept_idx1 ON related_concept (concept_id);

INSERT INTO category (value)
    VALUES ('ethnicity'),
        ('religion'),
        ('nationality'),
        ('occupation');
