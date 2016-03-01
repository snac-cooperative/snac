-- Drop the vocabulary table and sequence

drop table if exists vocabulary;
drop table if exists vocabulary_use;
drop sequence if exists vocabulary_id_seq;

-- Create the vocabulary table, sequence, and indices

CREATE SEQUENCE "vocabulary_id_seq";

-- Controlled Vocabulary. Will be superceded by multilingual controlled vocabularies for: occupation,
-- function, topical subject, nationality, language, language code, gender, script, name component labels,
-- date-predicates (from, to, born, died), maintenance status, maintenance event type, maintenance agent type,
-- place match type, function term, function type (e.g. DerivedFromRole), and more.

-- Context for use is in a separate table (perhaps vocabulary_use) because some (like entity_type) can be used
-- in several contexts.

-- Jan 29 2016 Just as with table vocabulary above not being dropped, do not drop the vocabulary_id_seq.
-- Really, all the vocabulary schema should be in a separate file because we initialize it separately, often.

-- Feb 8 2016 add "if not exists" just so we don't get a warning from Postgres. This needs to be moved to a
-- separate schema file. In a simple world the whole schema would always be run on an empty database, but that
-- is not the case. Our controlled vocabulary and authority data will often not be reloaded when the rest of
-- the database is reset.
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

-- create unique index vocabulary_idx on vocabulary(id);
-- create index vocabulary_type_idx on vocabulary(type);
-- create index vocabulary_value_idx on vocabulary(value);

-- We need a way for the data to sanity check that vocabulary is being used in the correct context.  If a
-- given vocabulary value can be used in multiple contexts, we need a linking table.

create table if not exists vocabulary_use (
    id       int primary key default nextval('vocabulary_id_seq'),
    vocab_id int,     -- fk to vocabulary.id
    db_table    text, -- table in this database, table is a Pg reserved word
    db_field    text  -- field in that table in this database
);

