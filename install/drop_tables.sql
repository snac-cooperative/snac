drop table contributor;
drop table control;
drop table date_range;
drop table function;
drop table geoplace;
drop table name;
drop table name_component;
drop table name_contributor;
drop table nationality;
drop table nrd;
drop table occupation;
drop table otherid;
drop table place;
drop table pre_snac_maintenance_history;
drop table related_identity;
drop table related_resource;
drop table role;
drop table appuser;
drop table source;
drop table source_link;
drop table split_merge_history;
drop table subject;
drop table appuser_role_link;
drop table version_history;

-- Normally there is data in table vocabulary. If you really need to drop it, so so manually and reload the
-- data.

-- drop table vocabulary;
-- drop sequence vocabulary_id_seq;

drop table vocabulary_use;
drop sequence version_history_id_seq;

-- drop data types after any tables using them have been dropped.
drop type icstatus;
drop sequence id_seq;
