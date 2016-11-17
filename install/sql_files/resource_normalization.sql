drop table if exists resource_cache; 
drop table if exists resource_language; 
drop sequence if exists "resource_id_seq";
drop sequence if exists "resource_version_id_seq";

CREATE SEQUENCE "resource_id_seq";
CREATE SEQUENCE "resource_version_id_seq";

-- Resources are cached directly, but also versioned
create table resource_cache (
        id         int default nextval('resource_id_seq'), 
        version    int default nextval('resource_version_id_seq'),
        href       text,
        type       int,
        title      text,
        abstract   text,
        extent     text,
        repo_ic_id int,
        object_xml_wrap text,
        primary key (id, version)
        );

create index resource_idx1 on resource_cache(href);
-- create index resource_idx2 on resource_cache(title);
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


-- Copy over the distinct resources
-- insert into resource_cache (href, type, title, object_xml_wrap) (select distinct href, role, relation_entry, object_xml_wrap from related_resource where href != '');

-- Daniel suggests making relationEntry the title on any that don't have an
-- href or object_xml_wrap and including everything as a resource (deduped).
-- In that case, we would want to use the following query to insert
insert into resource_cache (href, type, title, object_xml_wrap) (select distinct href, role, relation_entry, object_xml_wrap from related_resource);

-- Then alter the related_resource table
alter table related_resource add column resource_id integer;
alter table related_resource add column resource_version integer;

-- Then connect the resource relations to the resources
update related_resource set resource_id = r.id, resource_version = r.version from resource_cache r where related_resource.href = r.href and related_resource.role = r.type and related_resource.relation_entry = r.title and related_resource.object_xml_wrap = r.object_xml_wrap;

