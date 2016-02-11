-- Drop the vocabulary table and sequence

drop table if exists vocabulary;
drop sequence if exists vocabulary_id_seq;

-- Create the vocabulary table, sequence, and indices

CREATE SEQUENCE "vocabulary_id_seq";

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


-- We need a way for the data to sanity check that vocabulary is being used in the correct context.  If a
-- given vocabulary value can be used in multiple contexts, we need a linking table.

-- Feb 11 2016 This table should not exist until we need it, philosophically. Practically, if the table
-- exists, then it has to be dropped when initializing, or else "drop sequence" has to cascade. We may never
-- need it since the vocabulary folks seems to have some idea of "domain", so this is an idea for later.

-- create table vocabulary_use (
--     id       int primary key default nextval('vocabulary_id_seq'),
--     vocab_id int,     -- fk to vocabulary.id
--     db_table    text, -- table in this database, table is a Pg reserved word
--     db_field    text  -- field in that table in this database
-- );

