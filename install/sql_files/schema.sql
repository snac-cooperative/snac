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

-- drop table if exists control;
-- drop table if exists pre_snac_maintenance_history;

drop table if exists appuser; 
drop table if exists appuser_role_link;
drop table if exists biog_hist;
drop table if exists convention_declaration;
drop table if exists date_range;
drop table if exists function;
drop table if exists gender;
drop table if exists general_context;
drop table if exists geo_place;
drop table if exists language;
drop table if exists legal_status;
drop table if exists mandate;
drop table if exists name;
drop table if exists name_component;
drop table if exists name_contributor;
drop table if exists nationality;
drop table if exists nrd;
drop table if exists occupation;
drop table if exists otherid;
drop table if exists place_link;
drop table if exists related_identity;
drop table if exists related_resource;
drop table if exists role;
drop table if exists session;
drop table if exists scm;
drop table if exists structure_genealogy;
drop table if exists source;
-- drop table if exists split_merge_history;
drop table if exists subject;
drop table if exists version_history;
-- drop table if exists source_link;
drop table if exists control;
drop table if exists pre_snac_maintenance_history;
drop table if exists contributor;
-- these shouldn't even be in the database anymore
drop table if exists place;
drop table if exists geoplace;


-- drop table if exists vocabulary_use;
drop sequence if exists version_history_id_seq;

-- Feb 19 2016 stop using type icstatus 
-- drop data types after any tables using them have been dropped.
-- drop type if exists icstatus;

drop sequence if exists id_seq;

--
-- Sequences
--

-- Sequence for data table record id values It is very, very important that both table.id and
-- version_history.ic_id use this sequence. These two are used as foreign keys between table date_range and
-- other tables. Failure for nrd.ic_id and table.id to be unique will (would) break data_range foreign
-- keys. (And might break other foreign keys and sql queries as well.)
CREATE SEQUENCE "id_seq";

-- Feb 11 2016 This was removed and came back apparently via a bad git merge. See vocabulary_init.sql
--
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
-- version_history.ic_id.

-- identity constellation record status (is_locked, is_published) is handled here. The lock needs to be here if we increment version
-- number only on modified tables. (Why?)

-- We are using the multi-version model where field version is only updated in updated data tables. Selecting version is is:
-- select * from foo where version=(select max(version) from foo where id=$yy and version<=$vv) and ic_id=$mm

-- A record must be inserted into version_history for every change of data or change of status.

-- Feb 19 2016: Commenting out icstatus. Too difficult to manage for no benefits. Just use text.
-- enum type for Identity Constellation status (thus type icstatus)
-- create type icstatus as enum ('published', 'needs review', 'rejected', 'being edited', 'bulk ingest');


-- By convention, limit status to certain values: published, needs review, rejected, being edited, bulk ingest, deleted
--
-- This list is expected to grow over time, but we probably shouldn't remove any items without careful
-- testing.

create table version_history (
        id         int default nextval('id_seq'), -- constellation id, aka ic_id, use default when inserting new constellation
        version    int default nextval('version_history_id_seq'),
        is_locked  boolean default false,         -- (not used, see status) boolean, true is locked by version_history.user_id
        user_id    int,                           -- fk to appuser.id
        role_id    int,                           -- fk to role.id, defaults to users primary role, but can be any role the user has
        timestamp  timestamp default now(),       -- now()
        status     text,                          -- a curated list of status terms.
        is_current boolean,                       -- (not used) most current published, optional field to enhance performance
        note       text,                          -- checkin message
        primary key (id, version)
        );

-- We will often select status='published' so we need an index
create index version_history_idx1 on version_history(status);


-- Users of the system (editors, authors, researchers, admins etc)
-- SQL reserved word 'user', instead of always quoting it, change table name to appuser.

-- aka table user

create table appuser (
        id           int primary key default nextval('id_seq'),
        active          boolean default 't', -- true for active account
        username        text unique,         -- text-based user id, the user email address
        email           text,                -- non-unique, current default is username is also email
        first           text,                -- first name
        last            text,                -- last name
        fullname        text,                -- full name text
        password        text,
        avatar          text,                -- url
        avatar_small    text,                -- url
        avatar_large    text,                -- url
        work_email      text,
        work_phone      text,
        affiliation     int,                 -- fk is version_history.id aka constellation ID aka ic_id.
        preferred_rules text                 -- preferred descriptive name rules
        );

-- Linking table to handle role membership for users Do we need a 'primary' role boolean field? This would be
-- the default for version_history.role when one of the alternate roles isn't supplied.

create table appuser_role_link (
        uid        int,                -- fk to appuser.id
        rid        int,                -- fk to role.id
        is_primary boolean default 'f' -- this role is the primary for the given appuser
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

-- There may be multiple active sessions per user, so we need a separate table for sessions.

create table session (
        appuser_fk   int,         -- fk to appuser.id
        access_token text unique, -- the session token
        expires      integer      -- when the token expires, seconds since the epoch, UTC
);

-- As of Feb 10 2016 this table is not used. Perhaps we are planning to use it, but I suspect the split/merge
-- data has been moved to table version_history. Or somewhere else? Where?

-- create table split_merge_history (
--     from_id             int, -- fk nrd.id, also version_history.ic_id
--     to_id               int, -- fk nrd.id, also version_history.ic_id
--     timestamp           timestamp default now()
--     );

--
-- Data tables
--

-- Note1: table.version is always the same as nrd.version (constellation version). This won't be commented in
-- every table.

-- Note2: table.ic_id is always the same as the related version_history.ic_id. This won't be commented in
-- every table.

-- Table nrd is data with a 1:1 to the constellation. The glue of the constellation is table version_history,
-- although in historical times (before versioning) table nrd was "the" central CPF table.

-- Nov 12 2015 Remove unique constraint from ark_id because it is only unique with version, ic_id.
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

-- Change nrd.id to not use a default. We are currently writing the ic_id into nrd.id, even though nrd.id is
-- never used. The "id" of nrd is the constellation id. Any foreign key to nrd must use the ic_id. The id of
-- table nrd is Constellation->getID() which is the constellation id, not a table id as with all other data
-- objects. Since "the id" of table nrd is the constellation id, we put that value in nrd.ic_id as
-- always. There are no tables that are related to nrd.
--
-- Important: the is no php object that corresponds to nrd, thus no object for which nrd.id = Object->getID(). Remember, Constellation->getID() is nrd.ic_id.

create table nrd (
        id          integer,
        version     int not null,
        ic_id     int not null, -- fk to version_history.ic_id
        is_deleted  boolean default false,
        ark_id      text,         -- control/cpfId
        entity_type int not null, -- (fk to vocabulary.id) corp, pers, family
        primary key (ic_id, version)
        );

create unique index nrd_idx1 on nrd (ark_id,version,ic_id);
create unique index nrd_idx2 on nrd(ic_id,version);

-- I considered naming field text "value", but text is not a reserved word (amazingly), and although "text" is
-- overused, it fits our convention here.

create table convention_declaration (
    id            int default nextval('id_seq'),
    version       int not null,
    ic_id       int not null, -- fk to version_history.ic_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index convention_declaration_idx1 on convention_declaration(id,ic_id,version);

create table mandate (
    id            int default nextval('id_seq'),
    version       int not null,
    ic_id       int not null, -- fk to version_history.ic_id
    is_deleted    boolean default false,
    text text                   -- the text term
);

create unique index mandate_idx1 on mandate(id,ic_id,version);

create table general_context (
    id            int default nextval('id_seq'),
    version       int not null,
    ic_id       int not null, -- fk to version_history.ic_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index general_context_idx1 on general_context(id,ic_id,version);

create table structure_genealogy (
    id            int default nextval('id_seq'),
    version       int not null,
    ic_id       int not null, -- fk to version_history.ic_id
    is_deleted    boolean default false,
    text text                  -- the text term
);

create unique index structure_genealogy_idx1 on structure_genealogy(id,ic_id,version);

-- The biog_hist language is in table language, and is related to this where biog_hist.id=language.fk_id

create table biog_hist (
    id               int default nextval('id_seq'),
    version          int not null,
    ic_id          int not null,
    is_deleted       boolean default false,
    text text
);

create unique index biog_hist_idx2 on biog_hist(id,ic_id,version);


-- Name string. There may be multiple of these per identity constellation, nrd one-to-many name.
-- multiple authorizedForm, alternativeForm per name entry in the merged data

-- For authorizedForm or alternativeForm, see related table name_contributor.

-- Feb 10 2016 These fields aren't being used, so I've removed them. The rationale for corporate_name is
-- unclear, but there was a reason for this. There is no corporate_name equivalent in the NameEntry class.

-- The other fields are name components, and that is being handled by table name_component.

-- corporate_name   text,
-- prefix           text,
-- first            text,
-- middle           text,
-- last             text,
-- suffix           text,
-- additional_parts text,

create table name (
    id               int default nextval('id_seq'),
    version          int not null,
    ic_id          int not null,
    is_deleted       boolean default false,
    preference_score float, -- Preference to use this name
    original         text,  -- actual name (in <part>)
    primary key(id, version)
    );

-- Parsed components of name string. There are multiple of these per one name.

-- Note: no ic_id because this table only related to name.id, and through that to the identity
-- constellation.

create table name_component (
             id int default nextval('id_seq'),
        name_id int,  -- fk to name.id
        version int,
     is_deleted boolean default false,
       nc_label int,  -- typeID, getType() fk to vocabulary.id, surname, forename, etc.
       nc_value text, -- text, getText(), the string value of the component, Smith, John, etc.
        c_order int,  -- component order within this name, as entered.
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
    ic_id        int not null,
    is_deleted     boolean default false,
    name_id        int,  -- (fk to name.id)
    short_name     text, -- short name of the contributing entity (VIAF, LC, WorldCat, NLA, etc)
    name_type      int,  -- (fk to vocabulary.id) -- type of name (authorizedForm, alternativeForm)
    rule           int,  -- (fk to vocabulary.id) -- rule used to formulate the bame by this contributing entity 
    primary key(id, version)
    );

create unique index name_contributor_idx1 on name_contributor(id,ic_id,version);

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
--     ic_id    int not null,
--     is_deleted boolean default false,
--     short_name text, -- short name of the contributing entity (VIAF, LC, WorldCat, NLA, etc)
--     primary key(id, version)
--     );
-- create unique index contributor_idx1 on contributor (short_name);
-- create unique index contributor_idx2 on contributor (id,version,ic_id);



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

-- Note about originals: from_original, to_original: These are not CPF original exactly. These are values of
-- <fromDate> and <toDate> which are themselves created by the XLST date parser. For this reason we have no
-- singular original string such as "1901-1989" as can be found in name strings.


create table date_range (
        id               int default nextval('id_seq'),
        version          int not null,
        ic_id          int not null,
        is_deleted       boolean default false,
        is_range         boolean default false, -- distinguish 1 or 2 dates from a range with possibly missing bounds
        missing_from     boolean default false, -- from date is missing or unknown, only is_range=t
        from_date        text,                  -- date as an iso string
        from_type        int,                   -- (fk to vocabulary.id) birth, death, active
        from_bc          boolean default false, -- just in case we ever run into a BC date
        from_not_before  text,
        from_not_after   text,
        from_original    text,                  -- from date tag value
        missing_to       boolean default false, -- to date is missing or unknown, only is_range=t
        to_date          text,
        to_type          int,                   -- (fk to vocabulary.id) birth, death, active
        to_bc            boolean default false, -- just in case we ever run into a BC date
        to_not_before    text,
        to_not_after     text,
        to_present       boolean,
        to_original      text,                  -- the to date tag value 
        descriptive_note text, 
        fk_table         text,                  -- table name of the related foreign table. Exists only as a backup
        fk_id            int,                   -- table.id of the related record
        primary          key(id, version)
        );

create unique index date_range_idx1 on date_range(id,ic_id,version);


-- This corresponds to a php Language object, and has both language id values and script_id values. This is
-- not language controlled vocabulary. The language controlled vocabulary id fk is language.language_id.

create table language (
        id              int default nextval('id_seq'),
        version           int not null,
        ic_id           int not null,
        is_deleted        boolean default false,
        language_id       int,  -- fk to vocabulary
        script_id         int,  -- fk to vocabulary
        vocabulary_source text,
        note              text,
        fk_table          text, -- table name of the related foreign table. Exists only as a backup
        fk_id             int,  -- table.id of the related record
        primary         key(id, version)
        );

create unique index language_idx1 on date_range(id,ic_id,version);

-- From the <source> element. This appears to derive from /eac-cpf/control/source in the CPF. The href and
-- objectXMLWrap is not consistently used. There will (often?) be a related entry in table language, related
-- on source.id=language.fk_id.
--
-- SNAC Source File. A "source" is a cited (citation) source, and has qualities of an authority file.
-- Currently, each constellation has its own list of sources. (Other constellations might duplicate these
-- sources, but each constellation thinks its sources are unique).  Going forward we use this table for all
-- sources.  For example, SNACControlMetadata->citation is a Source object. Constellation->sources is a list
-- of sources.
-- 
-- Source is not an authority or vocabulary, therefore the source links back to the original table via an fk
-- just like date. 

-- Apr 4 2016. We have switched to per constellation list of sources. Finally removing type, which is always
-- "simple", and essentially not used. Removing fk_id and fk_table since sources are linked to each
-- constellation by ic_id. Any constellation component using a source does a relational link aka foreign key
-- by source.id

create table source (
    id           int default nextval('id_seq'),
    version      int not null,
    ic_id        int not null,
    is_deleted   boolean default false,
    display_name text,    -- User entered display name to distinguish sources, esp in the UI
    text         text,    -- Text of this source
    note         text,    -- Note related to this source
    uri          text,    -- URI of this source
    language_id  integer, -- language, fk to vocabulary.id
    primary key(id, version)
    );

-- Not true that href values do not repeat. Why did we think href would be unique across the table?
-- create unique index source_idx1 on source (href);

create unique index source_idx2 on source (id,version,ic_id);

-- Source is related to whatever needs it by putting a source.id foreign key in the table that wants to refer
-- back to a source. There is no need for this linking table because we do not have a many-to-many relation.
--
-- At one point, Source was first order data, not an authority. Every source was treated separately.

-- create table source_link (
--     id         int default nextval('id_seq'),
--     version    int not null,
--     ic_id    int not null,
--     is_deleted boolean default false,
--     fk_id      int, -- fk to the related table.id, always or usually nrd.id
--     source_id  int, -- fk to source.id
--     primary key(id, version)
--     );
-- create unique index source__link_idx1 on source_link (id,version,ic_id);

-- Feb 10 2016 Data in <control> is planned to be stored elsewhere, probably version_history.

-- Some of the elements from <control>. Multiple control records per identity_constellation.  The XML
-- accumulated these over time, but we have versioning, so we can add records with a version. This kind of
-- begs the question what to do with multiple existing maintenance events.

-- create table control (
--     id                  int default nextval('id_seq'),
--     version             int not null,
--     ic_id             int not null,
--     is_deleted          boolean default false,
--     maintenance_agency  text, -- ideally from a controlled vocab, but we don't have one for agencies yet.
--     maintenance_status  int,  -- (fk to vocabulary.id) 
--     conven_dec_citation text, -- from control/conventionDeclaration/citation (currently just VIAF)
--     primary key(id, version)
--     );
-- create unique index control_idx1 on control(id,ic_id,version);

-- Feb 10 2016. Table pre_snac_maintenance_history not used. I think all of the pre-merge history was thrown
-- out. Also, history and status will be captured in table version_history.

-- maintenanceHistory before the record was imported into the database?

-- create table pre_snac_maintenance_history (
--     id                  int default nextval('id_seq'),
--     version             int not null,
--     ic_id int not null,
--     is_deleted boolean default false,
--     modified_time       date,
--     event_type          int,  -- (fk to vocabulary.id)     
--     agent_type          int,  -- (fk to vocabulary.id)     
--     agent               text,
--     description         text,
--     primary key(id, version)
--     );
-- create unique index pre_snac_maintenance_history_idx1 on pre_snac_maintenance_history(id,ic_id,version);

-- Are all these controlled vocabulary-type of things really the same, and should be in a unified
-- tagging/markup table? (occupation, function, nationality, subject

-- For dates: select * from occupation,date_range where date_range.fk_id=id;

-- See the comments with table date_range.

create table occupation (
    id                int default nextval('id_seq'),
    version           int not null,
    ic_id           int not null,
    is_deleted        boolean default false,
    occupation_id     int,  -- (fk to vocabulary.id)
    vocabulary_source text, -- occupation/term/@vocabularySource
    note              text, -- occupation/descriptiveNote
    primary key(id, version)
    );

create unique index occupation_idx1 on occupation(id,ic_id,version);

create table function (
    id                int default nextval('id_seq'),
    version           int not null,
    ic_id           int not null,
    is_deleted        boolean default false,
    function_id       int,  -- function/term, fk to vocabulary.id
    function_type     text, -- function/@localType, null?, "DerivedFromRole"?, text for now, should be a fk to vocabulary.id
    vocabulary_source text, -- is this anf href to a controlled vocab?
    note              text,
    primary key(id, version)
    );

create unique index function_idx1 on function(id,ic_id,version);

create table nationality (
    id             int default nextval('id_seq'),
    version        int not null,
    ic_id        int not null,
    is_deleted     boolean default false,
    term_id int,  -- (fk to vocabulary.id for a given nationality)
    primary key(id, version)
    );

create unique index nationality_idx1 on nationality(id,ic_id,version);

create table subject (
    id         int default nextval('id_seq'),
    version    int not null,
    ic_id    int not null,
    is_deleted boolean default false,
    term_id int, -- (fk to vocabulary.id)
    primary key(id, version)
    );

create unique index subject_idx1 on subject(id,ic_id,version);

create table legal_status (
    id         int default nextval('id_seq'),
    version    int not null,
    ic_id    int not null,
    is_deleted boolean default false,
    term_id int, -- (fk to vocabulary.id)
    primary key(id, version)
    );

create unique index legal_status_idx1 on legal_status(id,ic_id,version);

-- Jan 28 2016. This is newer than nationality, subject. I considered naming term_id vocab_id because "term"
-- is overused. However, term_id is conventional and descriptive.

create table gender (
    id         int default nextval('id_seq'),
    version    int not null,
    ic_id    int not null,
    is_deleted boolean default false,
    term_id int, -- (fk to vocabulary.id)
    primary key(id, version)
    );

create unique index gender_idx1 on gender(id,ic_id,version);


-- <cpfRelation> Was table cpf_relations. Fields moved here from the old table document: arcrole, role, href, type,
-- cpfRelationType. cpfRelation/relationEntry is a name, and (sadly) is not always identical to the preferred
-- name in the related record.  Field version relates to ic_id, since this is a 1-way relation.

-- todo: remove extracted_record_id which is only in extracted CPF records, not in post-merge CPF records.

-- todo: related_identity.role is called targetEntityType in the php, and that's a less ambiguous name

-- todo: verify that extracted_record_id is (was) the related ARK and not a extracted CPF file/recordID, then
-- remove field extracted_record_id. Use related_ark

create table related_identity (
    id                  int default nextval('id_seq'),
    version             int not null,
    ic_id             int not null,
    is_deleted          boolean default false,
    related_id          int,  -- fk to version_history.ic_id of the related identity, was ic_id2
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

create unique index related_identity_idx1 on related_identity(id,ic_id,version);

-- resourceRelation

create table related_resource (
    id                  int default nextval('id_seq'),
    version             int not null,
    ic_id             int not null,
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

create unique index related_resource_idx1 on related_resource(id,ic_id,version);

-- meta aka SNACControlMetadata aka SNAC Control Metadata
-- 
-- No language_id. Language is an object, and has its own table related where scm.id=language.fk_id.

create table scm (
    id           int default nextval('id_seq'),
    version      int not null,
    ic_id      int not null,
    is_deleted   boolean default false,
    citation_id  int,  -- fk to source.id
    sub_citation text, -- human readable location within the source
    source_data  text, -- original data "as entered" from CPF
    rule_id      int,  -- fk to some vocabulary of descriptive rules
    note         text,
    fk_id        int,  -- fk to related table.id
    fk_table     text  -- table name of the related foreign table. This field exists as backup
);


-- Link constellation to original imported record id, aka extract record id. Probably does not need version
-- since this is not user-editable, but we give everything id, version, and ic_id for the sake of
-- consistency.

create table otherid (
        id         int default nextval('id_seq'),
        version    int not null,
        ic_id    int not null,
        text text, -- unclear what this is parse from in CPF. See SameAs.php.
        uri  text, -- URI of the other record, might be extracted record id, fk to target version_history.ic_id
        type int,  -- type of link, MergedRecord, viafID, fk to vocabulary.id
        primary    key(id, version)
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
-- <place>
--   <placeRole>Lieu de Paris</placeRole>
--   <placeEntry localType="arrondissement_actuel" vocabularySource="d3nyv5k4th--1kog8v18wrm89">02e arrondissement</placeEntry>
--   <placeEntry localType="voie" vocabularySource="d3nzbt224g-1wpyx0m9bwiae">louis-le-grand (rue)</placeEntry>
--   <placeEntry localType="nomLieu">7 rue Louis-le-Grand</placeEntry>
-- </place>

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
--     ic_id    int not null,
--     is_deleted boolean default false,
--     original   text, -- the original place text
--     type       int,  -- fk to vocabulary.id, from place/@localType
--     note       text, -- descriptive note, from place/descriptiveNote
--     role       int   -- fk to vocabulary.id, from place/placeRole
--     primary key(id, version)
--     );

-- create unique index place_idx1 on place(id,ic_id,version);

-- One to many linking table between place and geo_place. For the usual reasons SNAC place entry is
-- denormalized. Some of these fields are in the SNAC XML.
--
-- For convenience and i/o optimization, some of these fields are denormalized in the PHP PlaceEntry object.

        -- place_match_type int,  -- fk to vocabulary.id, likelySame, maybeSame, unmatched
        -- confidence       int,  -- confidence of this link, from snac place entry


create table place_link (
        id           int default nextval('id_seq'),
        version      int not null,
        ic_id      int not null,
        is_deleted   boolean default false,
        fk_id        int,                   -- fk to related table.id
        fk_table     text,                  -- table name of the related foreign table. Exists only as a backup
        confirmed    boolean default false, -- true after confirmation by a human
        original     text,                  -- original as seen text from CPF import
        type         integer,               -- fk to vocabulary.id from place@localType
        role         integer,               -- fk to vocabulary.id, from cpf place/placeRole
        note         text,
        score        float,                 -- matching confidence score
        geo_place_id int,                   -- fk to geo_place.id, might be null
        primary key(id, version)
    );

create unique index place_link_idx1 on place_link(id,ic_id,version);
