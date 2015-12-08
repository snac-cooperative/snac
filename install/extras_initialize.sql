
-- Nov 10 2015 Before we can put data into the db, we need at least 1 appuser and 1 role.

-- This is the kind of stuff necessary to init the db appuser and role

insert into role values (default, 'dba', 'database administrator');
insert into role values (default, 'web admin', 'web site administrator');
insert into role values (default, 'sys admin', 'system administrator');
insert into role values (default, 'editor', 'constellation editor, may modify any constellation data');

insert into appuser values (default, 'twl8n', 'tom@laudeman.com', 'Tom');

insert into appuser_role_link values
((select id from appuser where userid='twl8n'),
(select id from role where label='dba'), true);

insert into appuser_role_link values
((select id from appuser where userid='twl8n'),
(select id from role where label='editor'), false);

insert into appuser_role_link values
((select id from appuser where userid='twl8n'),
(select id from role where label='web admin'), false);


-- Example: get appuser 'twl8n' and the user's primary role

select appuser.id,role.id from appuser, appuser_role_link, role
where 
appuser.userid='twl8n'
and appuser.id=appuser_role_link.uid
and role.id = appuser_role_link.rid
and appuser_role_link.is_primary=true;

