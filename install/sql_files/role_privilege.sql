
insert into privilege (label, description) values ('Edit', 'Edit constellations');
insert into privilege (label, description) values ('Create', 'Create new constellations');
insert into privilege (label, description) values ('Publish', 'Publish or commit a constellation after reviewing');
insert into privilege (label, description) values ('Send for Review', 'Send a constellation to reviewer(s)');
insert into privilege (label, description) values ('Simplified Create', 'Create basic, simplified, constellations');
insert into privilege (label, description) values ('Suggest Edits', 'Suggest constellation edits');
insert into privilege (label, description) values ('Change Locks', 'Change which user has a constellation locked');
insert into privilege (label, description) values ('Unlock Currently Editing', 'Unlock constellations stuck in status Currently Editing');
insert into privilege (label, description) values ('Add Users', 'Enrole new SNAC participants (new users), edit new user info');
insert into privilege (label, description) values ('Assign Roles', 'Assign, modify user roles');
insert into privilege (label, description) values ('Modify Users', 'Modify user email, phone numbers, affiliation, etc.');
insert into privilege (label, description) values ('Inactivate Users', 'Able to inactivate user accounts');
insert into privilege (label, description) values ('Manage Groups','Add users to groups, remove users from groups, create and delete groups');
insert into privilege (label, description) values ('Manage My Group', 'Administer the membership of groups I belong to');
insert into privilege (label, description) values ('Manage Institutions', 'Manage the list of SNAC member institutions');
insert into privilege (label, description) values ('View Admin Dashboard', 'Use the Administration dashboard to manage users and groups');
insert into privilege (label, description) values ('View Reports', 'View reports on SNAC');
insert into privilege (label, description) values ('Generate Reports', 'Generate reports on SNAC');

insert into role (label, description) values ('Contributor', 'Create simplified constellations, suggest edits');
insert into role (label, description) values ('Editor, Training', 'Editor in training');
insert into role (label, description) values ('Editor, Full', 'Full editor');
insert into role (label, description) values ('Reviewer', 'Editor and moderator');
insert into role (label, description) values ('Administrator', 'Manage users, roles, groups');
insert into role (label, description) values ('System Administrator', 'SNAC developers, super users');

-- Build privilege role links, that is: add privileges to each role.

insert into privilege_role_link (rid, pid)
select (select id from role where label='Contributor'), id from privilege where 
    label in ('Simplified Create', 'Suggest Edits');

insert into privilege_role_link (rid, pid)
select (select id from role where label='Editor, Training'), id from privilege where 
    label in ('Create', 'Edit');

insert into privilege_role_link (rid, pid)
select (select id from role where label='Editor, Full'), id from privilege where 
    label in ('Create', 'Edit', 'Publish');

insert into privilege_role_link (rid, pid)
select (select id from role where label='Reviewer'), id from privilege where 
    label in ('Create', 'Edit', 'Publish', 'Change Locks', 'Unlock Currently Editing');

insert into privilege_role_link (rid, pid)
select (select id from role where label='Administrator'), id from privilege where 
    label in ('Add Users', 'Assign Roles', 'Modify Users', 'Manage Groups', 'Inactivate Users', 'View Admin Dashboard', 'View Reports', 'Generate Reports');

insert into privilege_role_link 
    (pid, rid) 
    select id as pid, (select id from role where label='System Administrator') as rid from privilege;

