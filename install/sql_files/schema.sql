
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

drop view if exists biog_hist_view;
drop view if exists convention_declaration_view;
drop view if exists date_range_view;
drop view if exists function_view;
drop view if exists gender_view;
drop view if exists general_context_view;
drop view if exists language_view;
drop view if exists legal_status_view;
drop view if exists mandate_view;
drop view if exists name_view;
drop view if exists name_component_view;
drop view if exists name_contributor_view;
drop view if exists nationality_view;
drop view if exists nrd_view;
drop view if exists occupation_view;
drop view if exists otherid_view;
drop view if exists entityid_view;
drop view if exists place_link_view;
drop view if exists related_identity_view;
drop view if exists related_resource_origination_name_view;
drop view if exists related_resource_view;
drop view if exists scm_view;
drop view if exists structure_genealogy_view;
drop view if exists source_view;
drop view if exists subject_view;
drop view if exists version_history_view;
drop view if exists address_line_view;


drop table if exists appuser;
drop table if exists appuser_role_link;
drop table if exists appuser_group;
drop table if exists appuser_group_link;
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
drop table if exists entityid;
drop table if exists place_link;
drop table if exists related_identity;
drop table if exists related_resource_origination_name;
drop table if exists related_resource;
drop table if exists privilege_role_link;
drop table if exists privilege;
drop table if exists role;
drop table if exists session;
drop table if exists scm;
drop table if exists snac_institution;
drop table if exists structure_genealogy;
drop table if exists source;
drop table if exists maybe_same;
drop table if exists stakeholder;
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
drop table if exists address_line;
drop table if exists unassigned_arks;
drop table if exists resource_cache; 
drop table if exists resource_language; 
drop table if exists resource_origination_name;
drop table if exists constellation_lookup;

-- drop table if exists vocabulary_use;
drop sequence if exists version_history_id_seq;

-- Feb 19 2016 stop using type icstatus
-- drop data types after any tables using them have been dropped.
-- drop type if exists icstatus;

drop sequence if exists id_seq;
drop sequence if exists "resource_id_seq";
drop sequence if exists "resource_version_id_seq";

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


CREATE SEQUENCE "resource_id_seq";
CREATE SEQUENCE "resource_version_id_seq";

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

-- By convention, limit status to certain values: published, needs review, rejected, being edited, bulk
-- ingest, deleted, currently editing, ingest cpf.
--
-- That list is expected to grow over time, and adding items is fine. However, we probably shouldn't remove
-- any status values without careful testing.
--
-- Fields is_locked and is_current are not used. Their planned function has been taken over by a more
-- comprehensive system that relies on status.

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
create index version_history_idx2 on version_history(user_id);


-- Users of the system (editors, authors, researchers, admins etc)
-- SQL reserved word 'user', instead of always quoting it, change table name to appuser.

-- aka table user

create table appuser (
        id              int primary key default nextval('id_seq'),
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

-- SNAC roles. These are groups of privileges This includes roles such as 'admin', 'editor',
-- 'contributor'. This really is yet another vocabulary, and therefore should be in the vocabulary table. We
-- can always move it there later.

create table role (
        id          int  primary key default nextval('id_seq'),
        label       text unique, -- short name of this role
        description text         -- description of this role
        );

create table privilege (
        id          int  primary key default nextval('id_seq'),
        label       text unique, -- short name
        description text         -- description
        );

-- Linking table between privileges and roles. appuser links to role which links to privilege, and thus users
-- have privileges.

create table privilege_role_link (
        pid        int,                -- fk to privilege.id
        rid        int                -- fk to role.id
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
    ic_id            int not null,
    is_deleted       boolean default false,
    preference_score float, -- Preference to use this name
    original         text,  -- actual name (in <part>)
    primary key(id, version)
    );

create index name_idx1 on name (ic_id, version);

-- Parsed components of name string. There are multiple of these per one name.

-- Note: no ic_id because this table only related to name.id, and through that to the identity
-- constellation.

create table name_component (
             id int default nextval('id_seq'),
        name_id int,  -- fk to name.id
          ic_id int,  -- fk to name.id
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
create unique index name_contributor_idx2 on name_contributor(id,name_id,version);

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

create unique index language_idx1 on language(id,ic_id,version);

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
    relation_type       int, -- @cpfRelationType, only from AnF, maybe put in a second table
    relation_entry      text, -- relationEntry (name) of the related eac-cpf record (should be unnecessary in db)
    descriptive_note    text, -- descriptiveNote, xml fragment, used only for non-ExtractedRecordId data
    extracted_record_id text, -- descriptiveNote/p/span[localType='http://socialarchive.iath.virginia.edu/control/term#ExtractedRecordId']
    date                int,  -- fk to date_range.id, or something similar
    primary key(id, version)
    );

create unique index related_identity_idx1 on related_identity(id,ic_id,version);

--
-- resourceRelation
-- The role aka roleTerm of repo_ic_id is always http://id.loc.gov/vocabulary/relators/rps
--

create table related_resource (
    id                  int default nextval('id_seq'),
    is_deleted          boolean default false,

    -- this constellation information
    version             int not null,
    ic_id               int not null,

    -- connection between this constellation and resource
    arcrole             int,  -- @xlnk:arcrole, fk to vocabulary.id type document_role, creatorOf, referencedIn, etc

    -- information about the resource
    relation_entry      text, -- relationEntry — “shortcut” description of this resource
                              -- other useful information
    resource_id         int,
    resource_version    int,
    
    descriptive_note    text,
    primary key(id, version)
);

create unique index related_resource_idx1 on related_resource(id,ic_id,version);
create index related_resource_idx2 on related_resource(ic_id,version);
create index related_resource_idx3 on related_resource(resource_id, resource_version);

-- Resources are cached directly, but also versioned
create table resource_cache (
        id         int default nextval('resource_id_seq'), 
        version    int default nextval('resource_version_id_seq'),
        is_deleted boolean default false,
        href       text,
        type       int,
        entry_type int,
        title      text,
        abstract   text,
        extent     text,
        repo_ic_id int,
        object_xml_wrap text,
        primary key (id, version)
        );

create index resource_idx1 on resource_cache(href);
create index resource_idx2 on resource_cache(id, version, is_deleted);
create index resource_idx3 on resource_cache(repo_ic_id);

-- Languages relating to resources are stored identically to but separately
-- from languages for constellations.  They have their own versioning based on
-- resource_version!
create table resource_language (
        id                int default nextval('resource_id_seq'),
        version           int not null,
        resource_id       int not null,
        is_deleted        boolean default false,
        language_id       int,  -- fk to vocabulary
        script_id         int,  -- fk to vocabulary
        vocabulary_source text,
        note              text,
        primary           key(id, version)
        );

create unique index resource_language_idx1 on resource_language(id,resource_id,version);
create index resource_language_idx2 on resource_language(language_id, script_id);
create index resource_language_idx3 on resource_language (resource_id, is_deleted);

-- Origination names are now part of resources
create table resource_origination_name (
    id           int default nextval('resource_id_seq'),
    is_deleted   boolean default false,
    version      int not null,
    resource_id  int not null,                          
    name_ic_id   int,                            -- ic_id of this creator
    name         text                            -- name of creator
);

create unique index resource_origination_name_idx1 on resource_origination_name(id,resource_id,version);
create index resource_origination_name_idx2 on resource_origination_name (resource_id, is_deleted);

-- meta aka SNACControlMetadata aka SNAC Control Metadata
--
-- No language_id. Language is an object, and has its own table related where scm.id=language.fk_id.

create table scm (
    id           int default nextval('id_seq'),
    version      int not null,
    ic_id        int not null,
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
        is_deleted       boolean default false,
        primary    key(id, version)
    );


-- Entity id table
create table entityid (
        id         int default nextval('id_seq'),
        version    int not null,
        ic_id    int not null,
        text text, -- text of the entityId field (ex: ISNI id, MARC organization code, etc)
        uri  text, -- URI of the other record, might not be used (not supported in EAC-CPF)
        type int,  -- type of id, such as ISNI, MARC org, etc
        is_deleted       boolean default false,
        primary    key(id, version)
);


-- Tables needing place data use place_link, and if there is a related geo_place then the place_link has the
-- geo_place foreign key as well. Table place_link also relates to snac meta data in order to capture original
-- strings. The php denormalizes (using more space, but optimising i/o) by combining fields from place_link
-- and geo_place into PlaceEntry.
--
-- At one point the Constellation concept of place required two classes, and three SQL tables. One of those
-- tables was place, now superceded by place_link.
--
-- Table place_link associates a place to another table. Each place_link relates to zero or one geo_place authority
-- (controlled vocabulary) records.
--
-- The original place text is here because it only occurs once per Constellation place.
--
-- When there are matches to geo_place, each match has their own geo_place_id, place_match_type, and score,
-- therefore the matches are handled via one record per match in table place_link.
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

-- Capture place info and serve as a one to many linking table between any other table and geo_place. This has
-- the odd trait that there may be no related geo_place, so place_link is something of a conditional link. For
-- the usual reasons SNAC place entry is denormalized. Some of these fields are in the SNAC XML.
--
-- For convenience and i/o optimization, some of these fields are denormalized in the PHP PlaceEntry object.

create table place_link (
        id           int default nextval('id_seq'),
        version      int not null,
        ic_id        int not null,
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

-- Address lines related to a place_link. Note that place_link holds place data and optionally links that data
-- to a geo_place when possible.
-- 
-- The address_line mirrors the EAC-CPF addressLine tag, and is associated with place tags.  That seems to
-- make sense so that you can have a P.O. Box address for the special collections department and a geo_place
-- that has the lat/lon for the Small Library at UVA (for example)
    --

create table address_line (
             id int default nextval('id_seq'),
       place_id int,  -- fk to place.id
          ic_id int,  -- fk to place.id
        version int,
     is_deleted boolean default false,
          label int,  -- typeID, getType() fk to vocabulary.id: City, State, Street, etc..
          value text, -- text, getText(), the string value of the address line
     line_order int,  -- line order within this address/place, as entered.
        primary key(id, version)
    );

create unique index address_line_idx1 on address_line(id,place_id,version);

-- Maybe SameAs links, a binary relationship between Constellations that may be the same

create table maybe_same (
        ic_id1  integer,  -- fk to version_history.id
        ic_id2  integer,  -- fk to version_history.id
        status  integer,  -- fk to vocabulary.id, status of the maybe_same
        note    text      -- fk to version_history.id
);

-- Stakeholder links, a binary relationship between an institution constellation
-- and the constellation that it has a stake in.
create table stakeholder (
        institution_id  integer,  -- fk to version_history.id (institution's ic_id)
        ic_id           integer   -- fk to version_history.id (constellation's ic_id)
);


        -- SNAC institution records. These are records in SNAC for the institutions participating in SNAC. They are used for
-- appuser.affiliation. snac_institution.ic_id=appuser.affiliation.

create table snac_institution (
        ic_id int  -- fk to version_history.id, aka ic_id of the institution SNAC constellation
        );

-- Groups of appusers. Use case is a group of reviewers, or a group of editors.  Postgres group is a reserved
-- word, so if we name the table "group" we have to put "group" in double quotes all over the place and that's
-- not happening.

create table appuser_group (
        id          int  primary key default nextval('id_seq'),
        label       text unique, -- short name of this
        description text         -- description of this
        );

-- Linking table for appuser and groups. Field is_default no currently used, but reserved for future use.

create table appuser_group_link (
        uid        int,                -- fk to appuser.id
        gid        int,                -- fk to appuser_group.id
        is_default boolean default 'f' -- this group is a default for the given user
);

-- Table for the arks that the system may assign (queue)
create table unassigned_arks (
           ark text
);


-- Table for the constellation id mapping (DAG) for getting the correct constellation if an
-- given an outdated ARK/ID that has been merged or split
create table constellation_lookup (
        ic_id           int,                        -- The main ICID (to query)
        ark_id          text,                       -- The original ARK (to query)
        current_ic_id   int,                        -- The current ICID for this constellation
        current_ark_id  text,                       -- The current ARK for this constellation
        modified        timestamp default now(),    -- The time this mapping was updated
        note            text                        -- Any notes that may be useful
);
-- Forward looking index (unique)
create index constellation_lookup_idx1 on constellation_lookup(ic_id, ark_id);
-- Backward looking index (non-unique)
create index constellation_lookup_idx2 on constellation_lookup(current_ic_id, current_ark_id);

-- Prefill
insert into constellation_lookup (ic_id, ark_id) select distinct ic_id, ark_id from nrd where ark_id is not null;
update constellation_lookup set current_ic_id = ic_id, current_ark_id = ark_id;





-- Long list of indices that are useful in querying the database

create index otherid_idx1 on otherid (ic_id, version);
create index subject_idx2 on subject (ic_id, version);
create index scm_idx1 on scm (id, version, ic_id);
create index source_idx3 on source (ic_id, version);
create index related_identity_idx2 on related_identity (ic_id, version);
create index name_component_idx2 on name_component (name_id, version);
create index name_contributor_idx3 on name_contributor (name_id, version);
create index name_contributor_idx4 on name_contributor (ic_id, version);
create index language_idx2 on language (ic_id, version);
create index occupation_idx2 on occupation (ic_id, version);
create index function_idx2 on function (ic_id, version);
create index biog_hist_idx3 on biog_hist (ic_id, version);
create index address_line_idx2 on address_line (place_id, version);
create index address_line_idx3 on address_line (ic_id, version); 
create index place_link_idx2 on place_link (ic_id, version);
create index place_link_idx3 on place_link (fk_id, fk_table, version);
create index convention_declaration_idx2 on convention_declaration (ic_id, version);
create index gender_idx2 on gender (ic_id, version);
create index general_context_idx2 on general_context (ic_id, version);
create index legal_status_idx2 on legal_status (ic_id, version);
create index language_idx3 on language (fk_id, fk_table, version);

-- Views that allow us to query the most recent constellation data

create or replace view address_line_view as
select g.* 
    from address_line g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from address_line g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view biog_hist_view as
select g.* 
    from biog_hist g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from biog_hist g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view convention_declaration_view as
select g.* 
    from convention_declaration g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from convention_declaration g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view date_range_view as
select g.* 
    from date_range g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from date_range g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view entityid_view as
select g.* 
    from entityid g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from entityid g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view function_view as
select g.* 
    from function g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from function g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view gender_view as
select g.* 
    from gender g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from gender g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view general_context_view as
select g.* 
    from general_context g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from general_context g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view language_view as
select g.* 
    from language g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from language g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view legal_status_view as
select g.* 
    from legal_status g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from legal_status g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view mandate_view as
select g.* 
    from mandate g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from mandate g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view name_view as
select g.* 
    from name g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from name g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view name_component_view as
select g.* 
    from name_component g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from name_component g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view name_contributor_view as
select g.* 
    from name_contributor g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from name_contributor g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view nationality_view as
select g.* 
    from nationality g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from nationality g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view nrd_view as
select g.* 
    from nrd g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from nrd g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view occupation_view as
select g.* 
    from occupation g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from occupation g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view otherid_view as
select g.* 
    from otherid g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from otherid g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view place_link_view as
select g.* 
    from place_link g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from place_link g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view related_identity_view as
select g.* 
    from related_identity g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from related_identity g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view related_resource_view as
select g.* 
    from related_resource g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from related_resource g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view scm_view as
select g.* 
    from scm g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from scm g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

create or replace view subject_view as
select g.* 
    from subject g 
        right join
        (select g.id, g.ic_id, max(g.version) as version
        from subject g 
            left join 
            (select id, max(version) as version 
                from version_history 
                where status='published' 
                group by id) vh 
            on vh.id = g.ic_id 
        where g.version < vh.version
        group by g.id, g.ic_id) mg on g.id = mg.id and g.version = mg.version
    where not g.is_deleted;

