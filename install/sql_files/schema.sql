-- SNAC web application Postgres schema

-- Notes
-- =============
-- 1. Main tables are single words
-- 2. Join/link tables are denoted by domain_range
-- 3. General format of the text:
--
--  table name definition (
--       field       type        notes/parameters,
--      ...         ...         ...
--
-- 4. Organized as follows:
--      A. Sequence definitions
--      B. Main tables
--      C. Join/Link tables

-- drop table if existss and sequences that may already exist, and create everything from scratch

drop table if exists control;
drop table if exists date_range;
drop table if exists function;
drop table if exists place_link;
drop table if exists scm;
drop table if exists geo_place;
drop table if exists name;
drop table if exists name_component;
drop table if exists name_contributor;
drop table if exists nationality;
drop table if exists nrd;
drop table if exists occupation;
drop table if exists otherid;
drop table if exists pre_snac_maintenance_history;
drop table if exists related_identity;
drop table if exists related_resource;
drop table if exists role;
drop table if exists appuser;
drop table if exists source;
drop table if exists split_merge_history;
drop table if exists subject;
drop table if exists appuser_role_link;
drop table if exists version_history;
drop table if exists language;
drop table if exists biog_hist;
drop table if exists convention_declaration;
drop table if exists gender;
drop table if exists mandate;
drop table if exists general_context;
drop table if exists structure_genealogy;

-- There is data in table vocabulary. If you really need to drop it, so so manually and reload the data.
-- drop table if exists vocabulary;
-- drop sequence vocabulary_id_seq;

drop table if exists vocabulary_use;
drop sequence if exists version_history_id_seq;

-- drop data types after any tables using them have been dropped.
drop type if exists icstatus;
drop sequence if exists id_seq;

--
-- Sequences
--

-- Sequence for data table record id values It is very, very important that both table.id and
-- version_history.main_id use this sequence. These two are used as foreign keys between table date_range and
-- other tables. Failure for nrd.main_id and table.id to be unique will (would) break data_range foreign
-- keys. (And might break other foreign keys and sql queries as well.)
CREATE SEQUENCE "id_seq";

-- Jan 29 2016 Just as with table vocabulary above not being dropped, do not drop the vocabulary_id_seq.
-- Really, all the vocabulary schema should be in a separate file because we initialize it separately, often.
--
-- Sequence for controlled vocabulary
-- CREATE SEQUENCE "vocabulary_id_seq";

CREATE SEQUENCE "version_history_id_seq";

--
-- Utility and meta-data tables
--

-- Table version_history is the root table for a EAC-CPF identity constellation, in the sense that the meta
-- data here describes the history, and all data table record versions. Note: the main identity id value is
-- version_history.main_id.

-- identity constellation record status (is_locked, is_published) is handled here. The lock needs to be here if we increment version
-- number only on modified tables. (Why?)

-- We are using the multi-version model where field version is only updated in updated data tables. Selecting version is is:
-- select * from foo where version=(select max(version) from foo where id=$yy and version<=$vv) and main_id=$mm

-- A record must be inserted into version_history for every change of data or change of status.

-- enum type for Identity Constellation status (thus type icstatus)
create type icstatus as enum ('published', 'needs review', 'rejected', 'being edited', 'bulk ingest');

create table version_history (
        id int default nextval('version_history_id_seq'),
        main_id int default nextval('id_seq'), -- main constellation id, when inserting a new identity, allow this to default
        is_locked boolean default false,       -- boolean, true is locked by version_history.user_id
        user_id int,                           -- fk to appuser.id
        role_id int,                           -- fk to role.id, defaults to users primary role, but can be any role the user has
        timestamp timestamp default now(),     -- now()
        status icstatus,                       -- enum icstatus note: an enum is a data type like int or text
        is_current boolean,                    -- most current published, optional field to enhance performance
        note text,                             -- checkin message
        primary key (id, main_id)
        );

-- We will often select status='published' so we need an index
create index version_history_idx1 on version_history(status);


-- Users of the system (editors, authors, researchers, admins etc)
-- SQL reserved word 'user', instead of always quoting it, change table name to appuser.

create table appuser (
        id     int  primary key default nextval('id_seq'),
        userid text unique, -- text-based user ids
        email  text,        -- contact information for tracking
        name   text         -- full name text
        );

-- Linking table to handle role membership for users Do we need a 'primary' role boolean field? This would be
-- the default for version_history.role when one of the alternate roles isn't supplied.

create table appuser_role_link (
        uid        int,    -- fk to appuser.id
        rid        int,    -- fk to role.id
        is_primary boolean -- this role is the primary for the given appuser
        );

-- Add a constraint to enforce only one is_primary per uid
create unique index appuser_role_link_ndx2 on appuser_role_link (uid) where is_primary=true;

-- SNAC roles. This includes roles such as 'admin', 'editor'. Also instutional affiliation: 'duke_affiliation', 'yale_affiliation'. 
-- We will either need some name conventions, or more fields such as institution_id foreign key to an IC for an institution

create table role (
        id          int  primary key default nextval('id_seq'),
        label       text unique, -- short name of this role
        description text         -- description of this role
        );

create table split_merge_history (
    from_id             int, -- fk nrd.id, also version_history.main_id
    to_id               int, -- fk nrd.id, also version_history.main_id
    timestamp           timestamp default now()
    );

--
-- Data tables
--

-- Note1: table.version is always the same as some identity_constellation.version. This won't be commented in every table.

-- Note2: table.main_id is always the same as the related version_history.main_id. This won't be commented in
-- every table.

-- Table of data 1:1 to the identity. The glue of the identity is table version_history.

-- Nov 12 2015 Remove unique constraint from ark_id because it is only unique with version, main_id.
-- Move to table: 
-- convention_declaration text,
-- mandate text,
-- general_context text,
-- structure_or_genealogy text,
-- nationality   int,          -- fk to vocabulary.id type nationality
-- gender        int,          -- fk to vocabulary.id type gender
-- language      text,         -- not in vocab yet, keep the string
-- language_code int,          -- (fk to vocabulary.id) 3 letter iso language code
-- script        text,         -- not in vocab yet, keep the string
-- script_code   int,          -- (fk to vocabulary.id) 
-- language_used int,          -- (fk to vocabulary.id) from languageUsed/language 
-- script_used   int,          -- (fk to vocabulary.id) from languageUsed, script (what the entity used)
-- exist_date    int,          -- fk to date_range.id

create table nrd (
    id            int default nextval('id_seq'),
    version       int not null,
    main_id       int not null, -- fk to version_history.main_id
    is_deleted    boolean default false,
    ark_id        text,         -- control/cpfId
    entity_type   int not null, -- (fk to vocabulary.id) corp, pers, family
    primary key (id, version)
    );

create unique index nrd_idx1 on nrd (ark_id,version,main_id);
create unique index nrd_idx2 on nrd(id,main_id,version);

-- I considered naming field text "value", but text is not a reserved word (amazingly), and although "text" is
-- overused, it fits our convention here.

create table convention_declaration (
    id            int default nextval('id_seq'),
    version       int not null,
    main_id       int not null, -- fk to version_history.main_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index convention_declaration_idx1 on convention_declaration(id,main_id,version);

create table mandate (
    id            int default nextval('id_seq'),
    version       int not null,
    main_id       int not null, -- fk to version_history.main_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index mandate_idx1 on mandate(id,main_id,version);

create table general_context (
    id            int default nextval('id_seq'),
    version       int not null,
    main_id       int not null, -- fk to version_history.main_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index general_context_idx1 on general_context(id,main_id,version);

create table structure_genealogy (
    id            int default nextval('id_seq'),
    version       int not null,
    main_id       int not null, -- fk to version_history.main_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index structure_genealogy_idx1 on structure_genealogy(id,main_id,version);

-- The biog_hist language is in table language, and is related to this where biog_hist.id=language.fk_id

create table biog_hist (
    id               int default nextval('id_seq'),
    version          int not null,
    main_id          int not null,
    is_deleted       boolean default false,
    text text
);

create unique index biog_hist_idx2 on biog_hist(id,main_id,version);


-- Name string. There may be multiple of these per identity constellation, nrd one-to-many name.
-- multiple authorizedForm, alternativeForm per name entry in the merged data

-- For authorizedForm or alternativeForm, see related table name_contributor.

create table name (
    id               int default nextval('id_seq'),
    version          int not null,
    main_id          int not null,
    is_deleted       boolean default false,
    language         int,   -- (fk to vocabulary.id) 
    script_code       int,   -- fk to vocabulary.id
    preference_score float, -- Preference to use this name
    original         text,  -- actual name (in <part>)
    corporate_name   text,
    prefix           text,
    first            text,
    middle           text,
    last             text,
    suffix           text,
    additional_parts text,
    primary key(id, version)
    );

-- Parsed components of name string. There are multiple of these per one name.

-- Note: no main_id because this table only related to name.id, and through that to the identity
-- constellation.

create table name_component (
             id int default nextval('id_seq'),
        name_id int,  -- fk to name.id
        version int,
     is_deleted boolean default false,
       nc_label text, -- at least semi-controlled vocab or at least semi-controlled? Surname, forename, etc.
       nc_value text, -- the string value of the component, Smith, John, etc.
        c_order int,  -- component order within this name, assuming direct order? Or assume indirect order?
        primary key(id, version)
    );

create unique index name_component_idx1 on name_component(id,name_id,version);

-- Link names to their contributing organization. Derived from: nameEntry/authorizedForm,
-- nameEntry/alternativeForm, both of which contain the short SNAC name of a contributing institution.
-- Conceptually this seems wrong, or certainly incomplete.
--
--
-- (Wrong? The name was contributed, so contributor links to the name. It makes no sense to link contributor
-- to constellation id.) In any case, it needs to link to SNAC constellation id, not to table contributor. See
-- comments for table contributor.

create table name_contributor (
    id             int default nextval('id_seq'),
    version        int not null,
    main_id        int not null,
    is_deleted     boolean default false,
    name_id        int,  -- (fk to name.id)
    short_name     text, -- short name of the contributing entity (VIAF, LC, WorldCat, NLA, etc)
    name_type      int,  -- (fk to vocabulary.id) -- type of name (authorizedForm, alternativeForm)
    primary key(id, version)
    );

create unique index name_contributor_idx1 on name_contributor(id,main_id,version);

-- Jan 29 2016 The original intent is muddy, but it seems clear now that table contributor is a duplication of
-- table name_contributor. Thus everything below is commented out. If you modify anything here, please include
-- an extensive commentary with examples of data supporting the change.

-- Nov 12 2015: New think: skip the table contributor, and simply save the short_name in name_contributor. No
-- matter what we do, there is a mess to clean up later on.

-- Removed from table name_contributor:    contributor_id int,  
-- (fk to contributor.id, which is a temp table until we link to SNAC records for each contributor)

-- Applies only to name/authorizedForm and name/alternativeForm. contributor.short_name is contributor
-- institution sort-cut name (aps, taro, VIAF, LC, nyu, WorldCat, etc). contributor.id needs to be converted
-- to a SNAC constellation id after a record is created in SNAC for each institution that contributed a name.

-- create table contributor (
--     id         int default nextval('id_seq'),
--     version    int not null,
--     main_id    int not null,
--     is_deleted boolean default false,
--     short_name text, -- short name of the contributing entity (VIAF, LC, WorldCat, NLA, etc)
--     primary key(id, version)
--     );
-- create unique index contributor_idx1 on contributor (short_name);
-- create unique index contributor_idx2 on contributor (id,version,main_id);



-- Use this date table for all things with dates, not just the identity constellation. Both single dates and
-- date ranges can be stored in the date range table. The alternative is some messy conditional code to handle
-- single dates and date ranges as separate formats. Sets of dates can consist of singles and ranges. It is
-- best to treat everything as a range with optional fields. Some rules must be followed:

-- If is_range then must have from and to or must set missing_from or missing_to to true. (Why? If is_range==t
-- and one of the dates is missing then it is missing. The database doesn't really care that the user
-- confirmed that one date is missing.) If not is_range then a date is two dates (example: birth and death)

-- Non-range should have from_type and to_type specified for non-null dates. (Should this be a requirement?)

-- If not birth and death, and if two dates, is_range must be true. If birth and death, one of the dates can
-- be left empty without setting missing_from or missing_to.

-- Active dates may be a single (from_date) or date range. 

-- date string is iso standard (mostly), largest to smallest units, left to right. By using a string in this
-- format, dates will sort alphbetically correctly, even for partial dates.

-- Dates BCE sort inverted, and thus must be sorted separately. Unclear what to do when from_date is BCE and to_date is CE.

-- Date ranges where the to_date is the present date and will continue to be present have an empty to_date
-- with is_present set to true.

-- There may be multiple date ranges for a single record in a related table. For example, multiple dates for a
-- single occupation. However, the reciprocal is not true, therefore a linking table is not necessary.

create table date_range (
        id              int default nextval('id_seq'),
        version         int not null,
        main_id         int not null,
        is_deleted      boolean default false,
        is_range        boolean default false, -- distinguish 1 or 2 dates from a range with possibly missing bounds
        missing_from    boolean default false, -- from date is missing or unknown, only is_range=t
        from_date       text,                  -- date as an iso string
        from_type       int,                   -- (fk to vocabulary.id) birth, death, active
        from_bc         boolean default false, -- just in case we ever run into a BC date
        from_not_before text,
        from_not_after  text,
        missing_to      boolean default false, -- to date is missing or unknown, only is_range=t
        to_date         text,
        to_type         int,                   -- (fk to vocabulary.id) birth, death, active
        to_bc           boolean default false, -- just in case we ever run into a BC date
        to_not_before   text,
        to_not_after    text,
        to_present      boolean,
        original        text,                  -- the original string, if entered as a single field
        fk_table        text,                  -- table name of the related foreign table. Exists only as a backup
        fk_id           int,                    -- table.id of the related record
        primary         key(id, version)
        );

create unique index date_range_idx1 on date_range(id,main_id,version);


-- This corresponds to a php Language object, and has both language id values and script_id values. This is
-- not language controlled vocabulary. The language controlled vocabulary id fk is language.language_id.

create table language (
        id              int default nextval('id_seq'),
        version           int not null,
        main_id           int not null,
        is_deleted        boolean default false,
        language_id       int,  -- fk to vocabulary
        script_id         int,  -- fk to vocabulary
        vocabulary_source text,
        note              text,
        fk_table          text, -- table name of the related foreign table. Exists only as a backup
        fk_id             int,  -- table.id of the related record
        primary         key(id, version)
        );

create unique index language_idx1 on date_range(id,main_id,version);

-- From the <source> element. The href and objectXMLWrap are not consistently used, so this may be
-- unnecessary. There will (often?) be a related entry in table language, related on source.id=language.fk_id.
--
-- From Source.php:
--
-- Snac Source File. A "source" is a citation source, and has qualities of an authority file although every
-- source is independent, even if it seems to be a duplicate.  This appears to derive from
-- /eac-cpf/control/source in the CPF. Going forward we use it for all sources.  For example,
-- SNACControlMetadata->citation is a Source object. Constellation->sources is a list of sources.
-- 
-- Source is not an authority or vocabulary, therefore the source links back to the original table via an fk
-- just like date. 

create table source (
    id          int default nextval('id_seq'),
    version     int not null,
    main_id     int not null,
    is_deleted  boolean default false,
    text        text,    -- Text of this source
    note        text,    -- Note related to this source
    uri         text,    -- URI of this source
    type_id     integer, -- Type of this source, fk to vocabulary.id
    language_id integer, -- language, fk to vocabulary.id
    fk_id       integer,
    fk_table    text,
    primary key(id, version)
    );

-- Not true that href values do not repeat. Why did we think href would be unique across the table?
-- create unique index source_idx1 on source (href);

create unique index source_idx2 on source (id,version,main_id);

-- Source is first order data, not an authority. Every source is treated separately. We rejected the idea of
-- an authority (broadly, controlled vocabulary) for source and will copy each source for each object it
-- applies to. As a result, linking is not necessary.

-- create table source_link (
--     id         int default nextval('id_seq'),
--     version    int not null,
--     main_id    int not null,
--     is_deleted boolean default false,
--     fk_id      int, -- fk to the related table.id, always or usually nrd.id
--     source_id  int, -- fk to source.id
--     primary key(id, version)
--     );

-- Some of the elements from <control>. Multiple control records per identity_constellation.  The XML
-- accumulated these over time, but we have versioning, so we can add records with a version. This kind of
-- begs the question what to do with multiple existing maintenance events.

create table control (
    id                  int default nextval('id_seq'),
    version             int not null,
    main_id             int not null,
    is_deleted          boolean default false,
    maintenance_agency  text, -- ideally from a controlled vocab, but we don't have one for agencies yet.
    maintenance_status  int,  -- (fk to vocabulary.id) 
    conven_dec_citation text, -- from control/conventionDeclaration/citation (currently just VIAF)
    primary key(id, version)
    );

create unique index control_idx1 on control(id,main_id,version);

-- maintenanceHistory before the record was imported into the database?

create table pre_snac_maintenance_history (
    id                  int default nextval('id_seq'),
    version             int not null,
    main_id int not null,
    is_deleted boolean default false,
    modified_time       date,
    event_type          int,  -- (fk to vocabulary.id)     
    agent_type          int,  -- (fk to vocabulary.id)     
    agent               text,
    description         text,
    primary key(id, version)
    );

create unique index pre_snac_maintenance_history_idx1 on pre_snac_maintenance_history(id,main_id,version);

-- Are all these controlled vocabulary-type of things really the same, and should be in a unified
-- tagging/markup table? (occupation, function, nationality, subject

-- For dates: select * from occupation,date_range where date_range.fk_id=id;

-- See the comments with table date_range.

create table occupation (
    id                int default nextval('id_seq'),
    version           int not null,
    main_id           int not null,
    is_deleted        boolean default false,
    occupation_id     int,  -- (fk to vocabulary.id)
    vocabulary_source text, -- occupation/term/@vocabularySource
    note              text, -- occupation/descriptiveNote
    primary key(id, version)
    );

create unique index occupation_idx1 on occupation(id,main_id,version);

create table function (
    id                int default nextval('id_seq'),
    version           int not null,
    main_id           int not null,
    is_deleted        boolean default false,
    function_id       int,  -- function/term, fk to vocabulary.id
    function_type     text, -- function/@localType, null?, "DerivedFromRole"?, text for now, should be a fk to vocabulary.id
    vocabulary_source text, -- is this anf href to a controlled vocab?
    note              text,
    primary key(id, version)
    );

create unique index function_idx1 on function(id,main_id,version);

create table nationality (
    id             int default nextval('id_seq'),
    version        int not null,
    main_id        int not null,
    is_deleted     boolean default false,
    term_id int,  -- (fk to vocabulary.id for a given nationality)
    primary key(id, version)
    );

create unique index nationality_idx1 on nationality(id,main_id,version);

create table subject (
    id         int default nextval('id_seq'),
    version    int not null,
    main_id    int not null,
    is_deleted boolean default false,
    term_id int, -- (fk to vocabulary.id)
    primary key(id, version)
    );

create unique index subject_idx1 on subject(id,main_id,version);

-- Jan 28 2016. This is newer than nationality, subject. I considered naming term_id vocab_id because "term"
-- is overused. However, term_id is conventional and descriptive.

create table gender (
    id         int default nextval('id_seq'),
    version    int not null,
    main_id    int not null,
    is_deleted boolean default false,
    term_id int, -- (fk to vocabulary.id)
    primary key(id, version)
    );

create unique index gender_idx1 on gender(id,main_id,version);


-- <cpfRelation> Was table cpf_relations. Fields moved here from the old table document: arcrole, role, href, type,
-- cpfRelationType. cpfRelation/relationEntry is a name, and (sadly) is not always identical to the preferred
-- name in the related record.  Field version relates to main_id, since this is a 1-way relation.

-- todo: remove extracted_record_id which is only in extracted CPF records, not in post-merge CPF records.

-- todo: related_identity.role is called targetEntityType in the php, and that's a less ambiguous name

-- todo: verify that extracted_record_id is (was) the related ARK and not a extracted CPF file/recordID, then
-- remove field extracted_record_id. Use related_ark

create table related_identity (
    id                  int default nextval('id_seq'),
    version             int not null,
    main_id             int not null,
    is_deleted          boolean default false,
    related_id          int,  -- fk to version_history.main_id of the related identity, was main_id2
    related_ark         text,
    role                int,  -- @xlink:role, fk to vocabulary.id, corporateBody, person, family
    arcrole             int,  -- @xlink:arcrole, (fk to vocabulary.id) associatedWith, correspondedWith, etc, was relation_type
    type                int,  -- @xlink:type, always simple?
    href                text, -- @xlink:href, optional
    relation_type       text, -- @cpfRelationType, only from AnF, maybe put in a second table
    relation_entry      text, -- relationEntry (name) of the related eac-cpf record (should be unnecessary in db)
    descriptive_note    text, -- descriptiveNote, xml fragment, used only for non-ExtractedRecordId data
    extracted_record_id text, -- descriptiveNote/p/span[localType='http://socialarchive.iath.virginia.edu/control/term#ExtractedRecordId']
    date                int,  -- fk to date_range.id, or something similar
    primary key(id, version)
    );

create unique index related_identity_idx1 on related_identity(id,main_id,version);

-- resourceRelation

create table related_resource (
    id                  int default nextval('id_seq'),
    version             int not null,
    main_id             int not null,
    is_deleted          boolean default false, --
    role                int,                   -- @xlink:role, fk to vocabulary.id, type document_type, e.g. ArchivalResource
    arcrole             int,                   -- @xlnk:arcrole, fk to vocabulary.id type document_role, creatorOf, referencedIn, etc
    type                int,                   -- @xlink:type, fk to vocabulary.id type source_type, was field document_type, always "simple"?
    href                text,                  -- @xlink:href, link to the resource
    relation_type       text,                  -- @resourceRelationType, only from AnF, maybe put in a second table
    relation_entry      text,                  -- relationEntry (name) of the related eac-cpf record (should be unnecessary in db)
    relation_entry_type text,                  -- relationEntry@localType, AnF, always "archiva"?
    descriptive_note text,
    object_xml_wrap     text,                  -- from objectXMLWrap, xml
    primary key(id, version)
    );

create unique index related_resource_idx1 on related_resource(id,main_id,version);

-- meta aka SNACControlMetadata aka SNAC Control Metadata
-- 
-- No language_id. Language is an object, and has its own table related where scm.id=language.fk_id.

create table scm (
    id           int default nextval('id_seq'),
    version      int not null,
    main_id      int not null,
    is_deleted   boolean default false,
    citation_id  int,  --  fk to source.id?
    sub_citation text, -- human readable location within the source
    source_data  text, -- original data "as entered" from CPF
    rule_id      int,  -- fk to some vocabulary of descriptive rules
    note         text,
    fk_id        int,  -- fk to related table.id
    fk_table     text  -- table name of the related foreign table. This field exists as backup
);

-- Tables needing place data use place_link to relate to geo_place. Table place_link also relates to snac meta
-- data in order to capture original strings. The php denormalizes (using more space, but optimising i/o) by
-- combining fields from place_link and geo_place into PlaceEntry.
--
-- At one point the Constellation concept of place required two classes, and three SQL tables. One of those
-- tables was place, now commented out.
--
-- Table place_link associates a place to another table. Each place_link relates to one geo_place authority
-- (controlled vocabulary) records. 
--
-- The original place text was here because it only occured once per Constellation place.
--
-- The various matches each had their own geo_place_id, place_match_type, and confidence so they were found in
-- table place_link.
--
-- Table place_link and geo_place are denormalized together to create php PlaceEntry objects.

-- Example values:
--
-- <placeRole>Lieu de Paris</placeRole>
-- <placeEntry localType="arrondissement_actuel" vocabularySource="d3nyv5k4th--1kog8v18wrm89">02e arrondissement</placeEntry>
-- <placeEntry localType="voie" vocabularySource="d3nzbt224g-1wpyx0m9bwiae">louis-le-grand (rue)</placeEntry>
-- <placeEntry localType="nomLieu">7 rue Louis-le-Grand</placeEntry>
-- <place localType="http://socialarchive.iath.virginia.edu/control/term#AssociatedPlace">
-- /data/merge/99166-w60v9g87.xml:            <placeEntry countryCode="FR"/>
-- /data/merge/99166-w6r24hfw.xml:            <placeEntry>Pennsylvania--Chester County</placeEntry>
-- /data/merge/99166-w6p37bxz.xml:            <placeEntry>Bermuda Islands, North Atlantic Ocean</placeEntry>
-- /data/merge/99166-w6rr6xvw.xml:            <placeEntry>Australia</placeEntry>


-- Feb 2 2016 table place unused. Stuff humans previously typed is in meta data related to place_link. First
-- order data which needs place uses place_link to relate to geo_place authority records.

-- create table place (
--     id         int default nextval('id_seq'),
--     version    int not null,
--     main_id    int not null,
--     is_deleted boolean default false,
--     original   text, -- the original place text
--     type       int,  -- fk to vocabulary.id, from place/@localType
--     note       text, -- descriptive note, from place/descriptiveNote
--     role       int   -- fk to vocabulary.id, from place/placeRole
--     primary key(id, version)
--     );

-- create unique index place_idx1 on place(id,main_id,version);

-- One to many linking table between place and geo_place. For the usual reasons SNAC place entry is
-- denormalized. Some of these fields are in the SNAC XML.
--
-- For convenience and i/o optimization, some of these fields are denormalized in the PHP PlaceEntry object.

        -- place_match_type int,  -- fk to vocabulary.id, likelySame, maybeSame, unmatched
        -- confidence       int,  -- confidence of this link, from snac place entry


create table place_link (
        id           int default nextval('id_seq'),
        version      int not null,
        main_id      int not null,
        is_deleted   boolean default false,
        fk_id        int,                   -- fk to related table.id
        fk_table     text,                  -- table name of the related foreign table. Exists only as a backup
        confirmed    boolean default false, -- true after confirmation by a human
        original     text,                  -- original as seen text from CPF import
        geo_place_id int,                   -- fk to geo_place.id, might be null
        primary key(id, version)
    );

create unique index place_link_idx1 on place_link(id,main_id,version);

--
-- Meta data, authority data, system link tables
-- 

-- Most (all?) of these fields should be coming out of a controlled vocabulary, probably geo names. Place
-- records link here via place_link.

-- If we fully normalize place, then we don't want to repleat country_code (and administrative_code) In that
-- case, both country_code and administrative_code will be self related foreign keys to other records in
-- geo_place.

-- We don't know the max decimal places in geoname lat and lon, so save them as text. Also, we don't want
-- Postgres or php to truncate or round the numbers. We could investigate GIS data types.

-- This quotes Google Maps docs: From the Google Maps documentation: "To keep the storage space required for
-- your table at a minimum, you can specify that the lat and lng attributes are floats of size (10,6)". This
-- is a bit odd/interesting since the last url below gives an example of needing 7 decimal places at high latitudes.
--
-- http://stackoverflow.com/questions/1196174/correct-datatype-for-latitude-and-longitude-in-activerecord

-- If stored numerically, fixed precision, perhaps: lat 9,7 and lon 9.6. Or perhaps not. Strings are safe,
-- although non-optimal for calculations.
-- 
-- http://stackoverflow.com/questions/1196415/what-datatype-to-use-when-storing-latitude-and-longitude-data-in-sql-databases

-- Contrary to the 6 decimal places some people suggest, this says explains why 7 decimal places are necessary
-- >41.7 degrees latitude
-- 
-- https://groups.google.com/forum/#!topic/google-maps-api/uSi1-8U1GCE

-- http://api.geonames.org/postalCodeSearch?postalcode=9011&maxRows=10&username=demo
-- <lat>47.60764</lat>
-- <lng>17.78194</lng>

-- http://api.geonames.org/get?geonameId=6295630&username=demo&style=full
-- The forum post gives the id for "Earth".
-- Geonames id values appear to be integer. Note singular name of the element.
-- <geonameId>6295630</geonameId>

-- We have some geographic names from AnF's geographic vocab. They aren't geonames entries, but might still
-- fit in this table. Might.
--
-- <placeEntry localType="voie" vocabularySource="d3nzbt224g-1wpyx0m9bwiae">louis-le-grand (rue)</placeEntry>

-- We don't need vocabulary source because that was a CPF hold over prior to using a geo authority.
-- vocabularySource text, -- AnF and Robbie's geonames search creates @vocabularySource attribute.


create table geo_place (
    id                  int default nextval('id_seq'),
    version             int not null,  -- fk to version_history.id, sequence is unique foreign key
    latitude            numeric(10,7), -- Fixed precision, perhaps more precise than we will need.
    longitude           numeric(10,7), -- Fixed precision, perhaps more precise than we will need.
    administrative_code text,          -- Should be an fk to geo_place.id for the encompassing administrative_code?
    country_code        text,          -- Should be an fk to geo_place.id for the encompassing country_code?
    name                text,          -- The (canonical?) geonames name of this place?
    geoname_id          text,          -- Persistent id, integer, text, or URI. vocabularySource goes here.
    primary key(id, version)
    );

create unique index geo_place_idx1 on geo_place (id,version);

-- Controlled Vocabulary. Will be superceded by multilingual controlled vocabularies for: occupation,
-- function, topical subject, nationality, language, language code, gender, script, name component labels,
-- date-predicates (from, to, born, died), maintenance status, maintenance event type, maintenance agent type,
-- place match type, function term, function type (e.g. DerivedFromRole), and more.

-- Context for use is in a separate table (perhaps vocabulary_use) because some (like entity_type) can be used
-- in several contexts.

-- Jan 29 2016 Just as with table vocabulary above not being dropped, do not drop the vocabulary_id_seq.
-- Really, all the vocabulary schema should be in a separate file because we initialize it separately, often.
--

-- create table vocabulary (
--     id          int primary key default nextval('vocabulary_id_seq'),
--     type        text,        -- Type of the vocab
--     value       text,        -- Value of the controlled vocab term
--     uri         text,        -- URI for this controlled vocab term, if it exists
--     description text         -- Textual description of this vocab term
--     );

-- create unique index vocabulary_idx on vocabulary(id);
-- create index vocabulary_type_idx on vocabulary(type);
-- create index vocabulary_value_idx on vocabulary(value);

-- We need a way for the data to sanity check that vocabulary is being used in the correct context.  If a
-- given vocabulary value can be used in multiple contexts, we need a linking table.

create table vocabulary_use (
    id       int primary key default nextval('vocabulary_id_seq'),
    vocab_id int,     -- fk to vocabulary.id
    db_table    text, -- table in this database, table is a Pg reserved word
    db_field    text  -- field in that table in this database
);

-- Link constellation to original imported record id, aka extract record id. Probably does not need version
-- since this is not user-editable, but we give everything id, version, and main_id for the sake of
-- consistency.

create table otherid (
        id         int default nextval('id_seq'),
        version    int not null,
        main_id    int not null,
        text text, -- unclear what this is parse from in CPF. See SameAs.php.
        uri  text, -- URI of the other record, might be extracted record id, fk to target version_history.main_id
        type int,  -- type of link, MergedRecord, viafID, fk to vocabulary.id
        primary    key(id, version)
    );


