-- Drop the place_link and place vocabulary table and sequence

drop table if exists place_link;
drop table if exists geo_place;
drop sequence if exists geoplace_id_seq;


CREATE SEQUENCE "geoplace_id_seq";

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
        type         integer,               -- fk to vocabulary.id from place@localType
        role         integer,               -- fk to vocabulary.id, from cpf place/placeRole
        note         text,
        score        float,                 -- matching confidence score
        geo_place_id int,                   -- fk to geo_place.id, might be null
        primary key(id, version)
    );

create unique index place_link_idx1 on place_link(id,main_id,version);

--
-- Meta data, authority data, system link tables
-- 

-- There could be a normalization problem here with administration_code and country_code. Those might best
-- link to other records in this table, or to a controlled vocab/authority table.
--
-- Place records link here via place_link.

-- Based on research, lat and lon need no more than fix precision (10,7) numbers.

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
-- above (greater than) 41.7 degrees latitude
-- 
-- https://groups.google.com/forum/#!topic/google-maps-api/uSi1-8U1GCE

-- http://api.geonames.org/postalCodeSearch?postalcode=9011&maxRows=10&username=demo
-- <lat>47.60764</lat>
-- <lng>17.78194</lng>

-- http://api.geonames.org/get?geonameId=6295630&username=demo&style=full
--
-- <geonameId>6295630</geonameId>
--
-- The forum post gives the id for "Earth".  Geonames id values appear to be integer. Note singular name of
-- the element. However, the integer is not used for math, and we may want to join other authorities to this
-- table. Therefore we store geoname_id as text.

-- We have some geographic names from AnF's geographic vocab. They aren't geonames entries, but might still
-- fit in this table. Might. localType is admin code or country code. vocabularySource seems to be a
-- persistent id which could be geoname_id.
--
-- <placeEntry localType="voie" vocabularySource="d3nzbt224g-1wpyx0m9bwiae">louis-le-grand (rue)</placeEntry>

-- For the time being we can get away without having vocabulary source in this table. It appears that AnF's
-- use of @vocabularySource is an XML implementation of authority control applied to CPF <place> elements.
-- AnF and Robbie's geonames search creates @vocabularySource attribute in place_link.

-- The elements for admin_code are named:
--
-- <adminCode1/> <adminName1/> <adminCode2/> <adminName2/>
--
-- We will keep to that convention and name our field admin_code as opposed to the longer administrative_code
-- or administration_code. Note that the GeoTerm object calls this administrationCode.

-- Instead of a field for ID, lets just leap forward to assuming it will become a URI, so we name the field
-- 'uri', and put the best identifier we have in that field.


create table geo_place (
    id                  int default nextval('geoplace_id_seq'),
    uri                 text,          -- URI/URL, geoname_id, or vocabularySource attribute
    name                text,          -- The geonames name; we do not have alt names yet
    latitude            numeric(10,7), -- Fixed precision, perhaps more precise than we will need.
    longitude           numeric(10,7), -- Fixed precision, perhaps more precise than we will need.
    admin_code          text,          -- Should be an fk to geo_place.id for the encompassing administrative_code?
    country_code        text,          -- Should be an fk to geo_place.id for the encompassing country_code?
    primary key(id)
    );

create unique index geo_place_idx1 on geo_place (id);
