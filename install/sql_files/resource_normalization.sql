drop table if exists resource_cache; 
drop table if exists resource_language; 
drop table if exists resource_origination_name;
drop sequence if exists "resource_id_seq";
drop sequence if exists "resource_version_id_seq";

CREATE SEQUENCE "resource_id_seq";
CREATE SEQUENCE "resource_version_id_seq";

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

-- Copy over the distinct resources
-- insert into resource_cache (href, type, title, object_xml_wrap) (select distinct href, role, relation_entry, object_xml_wrap from related_resource where href != '');

-- Daniel suggests making relationEntry the title on any that don't have an
-- href or object_xml_wrap and including everything as a resource (deduped).
-- In that case, we would want to use the following query to insert
insert into resource_cache (href, type, title, object_xml_wrap) (select distinct href, role, relation_entry, object_xml_wrap from related_resource);

-- Then alter the related_resource table
alter table related_resource add column resource_id integer;
alter table related_resource add column resource_version integer;
create index related_resource_idx3 on related_resource(resource_id, resource_version);

-- Then connect the resource relations to the resources
update related_resource set resource_id = r.id, resource_version = r.version from resource_cache r where coalesce(related_resource.href, 'null') = coalesce(r.href, 'null') and coalesce(related_resource.role, 0) = coalesce(r.type, 0) and coalesce(related_resource.relation_entry, 'null') = coalesce(r.title, 'null') and coalesce(related_resource.object_xml_wrap, 'null') = coalesce(r.object_xml_wrap, 'null');

