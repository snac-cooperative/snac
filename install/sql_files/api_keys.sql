-- API keys table
create table api_keys (
        id          int  primary key default nextval('id_seq'),
        uid         int not null,       -- fk to appuser.id
        label       text,               -- user provided name of this api key 
        key         text not null,      -- the key
        generated   timestamp default(now()), -- time created
        expires     timestamp default(now() + interval '1 year') -- expiration time
        );

        create index api_keys_idx2 on api_keys(key);
        create index api_keys_idx3 on api_keys(uid);
