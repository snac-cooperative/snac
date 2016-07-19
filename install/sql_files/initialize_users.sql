--
-- Insert base-level users into the system 
--
-- This must be run after role_privilege.sql.
-- 

-- Create the system (root-level) user for system services

insert into appuser (username, email, fullname) values ('system@localhost', 'system@localhost', 'System Service');
insert into appuser (username, email, fullname) values ('testing@localhost', 'testing@localhost', 'Database Testing User');

-- Apply 'System Administrator' role to system user

insert into appuser_role_link (uid, rid, is_primary) values
    ((select id from appuser where username='system@localhost'),
        (select id from role where label='System Administrator'), true);
