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


