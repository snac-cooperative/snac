-- Drop the vocabulary table and sequence

drop table if exists vocabulary;
drop sequence if exists vocabulary_id_seq;

-- Create the vocabulary table, sequence, and indices

CREATE SEQUENCE "vocabulary_id_seq";

-- Controlled Vocabulary. These are terms to be used in related tables where controlled vocabulary values are
-- required. The related table has only the vocabulary.id value.
--
-- Supported types: occupation, function, topical subject, nationality, language, language code, gender,
-- script, name component labels, date-predicates (from, to, born, died), maintenance status, maintenance
-- event type, maintenance agent type, place match type, function term, function type (e.g. DerivedFromRole),
-- and more in the future.

-- These vocabulary records are used to create Term objects, which in turn supply ID values for related tables that
-- need controlled vocabulary terms.

-- Will be superceded by multilingual controlled vocabularies.

-- Feb 8 2016 add "if not exists" just so we don't get a warning from Postgres.

-- Feb 17 2016 "if not exists" may not be necessary now that the vocabulary schema is here in a separate
-- file. In a simple world the whole schema would always be run on an empty database, but that is not the
-- case. Our controlled vocabulary and authority data will often not be reloaded when the rest of the database
-- is reset.

create table if not exists vocabulary (
        id          int primary key default nextval('vocabulary_id_seq'),
        type        text,        -- Type of the vocab
        value       text,        -- Value of the controlled vocab term
        uri         text,        -- URI for this controlled vocab term, if it exists
        description text         -- Textual description of this vocab term
        );

create unique index vocabulary_idx on vocabulary(id);
create index vocabulary_type_idx on vocabulary(type);
create index vocabulary_value_idx on vocabulary(value);


-- Not yet implemented: We need a way for the data to sanity check that vocabulary is being used in the
-- correct context.  If a given vocabulary value can be used in multiple contexts, we need a linking table.

-- Feb 11 2016 This table should not exist until we need it, philosophically. Practically, if the table
-- exists, then it has to be dropped when initializing, or else "drop sequence" has to cascade. We may never
-- need it since the vocabulary folks seems to have some idea of "domain", so this is an idea for later.

-- create table vocabulary_use (
--     id       int primary key default nextval('vocabulary_id_seq'),
--     vocab_id int,     -- fk to vocabulary.id
--     db_table    text, -- table in this database, table is a Pg reserved word
--     db_field    text  -- field in that table in this database
-- );

