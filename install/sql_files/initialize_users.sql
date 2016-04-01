--
-- Insert base-level users and roles into the system 
--

-- Insert roles into the system 

insert into role values (default, 'system', 'system services');
insert into role values (default, 'dba', 'database administrator');
insert into role values (default, 'web admin', 'web site administrator');
insert into role values (default, 'sys admin', 'system administrator');
insert into role values (default, 'editor', 'constellation editor, may modify any constellation data');

-- Create the system (root-level) user for system services

insert into appuser (userid, email, fullname) values ('system', 'system@localhost', 'System Service');

-- Apply system services role to system user

insert into appuser_role_link values
((select id from appuser where userid='system'),
(select id from role where label='system'), true);
