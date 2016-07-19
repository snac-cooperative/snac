<?php
/**
 * Database User Test File
 *
 *
 * License:
 *
 * @author Tom Laudeman
 * @license http://opensource.org/licenses/BSD-3-Clause BSD 3-Clause
 * @copyright 2015 the Rector and Visitors of the University of Virginia, and
 *            the Regents of the University of California
 */

/**
 * Database User test suite
 *
 * @author Tom Laudeman
 *
 */
class DBUserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \Monolog\Logger $logger the logger for this server
     *
     * See enableLogging() in this file.
     */
    private $logger = null;

    /**
     * Enable logging
     *
     * Call this to enabled loggin for objects of this class. For various reasons, logging is not enabled by default.
     *
     * Check that we don't have a logger before creating a new one. This can be called as often as one wants
     * with no problems.
     */
    private function enableLogging()
    {
        global $log;
        if (! $this->logger)
        {
            // create a log channel
            $this->logger = new \Monolog\Logger('DBUtil');
            $this->logger->pushHandler($log);
        }
    }

    /**
     * Wrap logging
     *
     * When logging is disabled, we don't want to call the logger because we don't want to generate errors. We
     * also don't want logs to just magically start up. Doing logging should be very intentional, especially
     * in a low level class like SQL. Call enableLogging() before calling logDebug().
     *
     * @param string $msg The logging messages
     *
     * @param string[] $debugArray An associative list of keys and values to send to the logger.
     */
    private function logDebug($msg, $debugArray=array())
    {
        if ($this->logger)
        {
            $this->logger->addDebug($msg, $debugArray);
        }
    }

    /**
     * DBUser object for this class
     * @var $dbu \snac\server\database\DBUser object
     */
    private $dbu = null;

    /**
     * Database Utility object
     *
     * We need to be able to read a constellation, so we need a DBUtil object.
     *
     * @var snac\server\database\DBUtil object.
     */
    private $dbutil;

    /**
     * Constructor
     *
     * Note about how things are different here in testing world vs normal execution:
     *
     * Any vars that aren't set up in the constructor won't be initialized, even though the other functions
     * appear to run in order. Initializing instance vars anywhere except the constructor does not initialize
     * for the whole class. phpunit behaves as though the class where being instantiated from scratch for each
     * test.
     *
     * In cases where tests need to happen in order, all the ordered tests are most easily done inside one
     * test, with multiple assertions.
     */
    public function __construct()
    {
        $this->dbu = new snac\server\database\DBUser();
        $this->dbutil = new \snac\server\database\DBUtil();

        // Prototypeing..
        // $this->traverseHead();
        // exit();
    }


    /**
     * {@inheritDoc}
     * @see PHPUnit_Framework_TestCase::setUp()
     *
     * This is run before each test, not just once before all tests.
     */
    public function setUp()
    {
        /*
         * In retrospect, leaving old users in the db after testing is a bad idea. If you need to do
         * diagnostics on the db, comment out some user cleaning code elsewhere. That is: after writing the
         * user cleanup code.
         *
         * Start by deleting the test account, if it exists. We leave the old user after a test for debugging
         * purposes.
         *
         * We do not want to leave the 'demo' role, but failures errors can cause that. So also delete the demo role, if it exists.
         */
        $userList = $this->dbu->listUsers();
        foreach($userList as $oldUser)
        {
            if ($oldUser->getUserName() == "mst3k@example.com")
            {
                /*
                 * $testUser = new \snac\data\User();
                 * $testUser->setUserName("mst3k@example.com");
                 * $this->user = $this->dbu->readUser($testUser);
                 */
                $appUserID = $oldUser->getUserID();
                $oldRoleList = $this->dbu->listUserRoles($oldUser);
                foreach($oldRoleList as $role)
                {
                    $this->dbu->removeUserRole($oldUser, $role);
                }
                $this->dbu->clearAllSessions($oldUser);
                $this->dbu->eraseUser($oldUser);
            }
        }
    }

    /**
     * Test fundamentals of Roles and Privileges
     *
     */
    public function testRolePrivilege()
    {
        $priv = new \snac\data\Privilege(sprintf("demo1 priv %s", time()), 'This is a demo test privilege');
        /*
         * $priv has the id added, in place.
         */
        $this->dbu->writePrivilege($priv);

        /*
         * Check that the priv we just wrote really made it to the database.
         */
        $privList = $this->dbu->listPrivileges();
        $foundPriv = false;
        foreach($privList as $tPriv)
        {
            if ($tPriv->getID() == $priv->getID())
            {
                $foundPriv = true;
            }
        }
        $this->assertTrue($foundPriv);

        /*
         * Create a new role, add a priv to it before writing to the db, then write to the db.
         * An alternate way of adding a priv to a role would is to call addPrivilegeToRole()
         */
        $role = new \snac\data\Role();
        $role->setLabel(sprintf("demo2 role %s", time()));
        $role->setDescription('This is a demo test role');
        $role->addPrivilege($priv);
        $this->dbu->writeRole($role);

        /*
         * Check that the priv we added to the role is still there. Read all the roles from the db, and check
         * that our role exists then check that is has the expected priv.
         */
        $allRole = $this->dbu->listRoles();
        $roleHasPriv = false;
        foreach($allRole as $tRole)
        {
            if ($tRole->getID() == $role->getID())
            {
                foreach($tRole->getPrivilegeList() as $tPriv)
                {
                    if ($tPriv->getID() == $priv->getID())
                    {
                        $roleHasPriv = true;
                    }
                }
            }
        }
        $this->assertTrue($roleHasPriv);


        /*
         * Save the role count, erase the demo role, check the post erase count.  Must delete the role before
         * deleting any privileges used by it. The low level SQL code checks that a privilege is not used by
         * any role before deleting the privilege.
         */
        $preDeleteRoleCount = count($allRole);
        $this->dbu->eraseRole($role);
        $postDeleteRoleCount = count($allRole = $this->dbu->listRoles());
        $this->assertEquals($preDeleteRoleCount, ($postDeleteRoleCount+1));

        /*
         * Save the priv count, erase the demo priv, check the count afterwards.
         */
        $preDeletePrivilegeCount = count($privList);
        $this->dbu->erasePrivilege($priv);
        $postDeletePrivilegeCount = count($this->dbu->listPrivileges());
        $this->assertEquals($preDeletePrivilegeCount, ($postDeletePrivilegeCount+1));
    }

    /**
     * Test adding and modifying a user.
     *
     * Also test user roles, privileges and groups.
     *
     */
    public function testBasic()
    {
        /*
         * Create a new user.
         */
        $userObj = new \snac\data\User();
        $userObj->setFirstName("Malf");
        $userObj->setLastName("Torrent");
        $userObj->setFullName("Malf S Torrent");
        $userObj->setAvatar("http://example.com/avatar");
        $userObj->setAvatarSmall("http://example.com/avatar_small");
        $userObj->setAvatarLarge("http://example.com/avatar_large");
        $userObj->setEmail("mst3k@example.com");

        /*
         * Hard code the ARK for UVa. This means that the UVa constellation must be in the database.
         * When second param is true, we get a summary constellation.
         */
        $inst = $this->dbutil->readPublishedConstellationByArk('http://n2t.net/ark:/99166/w6xq0t7h', true);
        $userObj->setAffiliation($inst);

        $newUser = $this->dbu->createUser($userObj);

        $this->assertNotNull($newUser);

        /*
         * Update the user in order to exercise updateUser()
         */
        $newUser->setFirstName('Malvie');
        $this->dbu->saveUser($newUser);
        $newUser = $this->dbu->readUser($newUser);
        $this->assertEquals('Malvie', $newUser->getFirstName());

        /*
         * Try adding a password. Yes, I know this password is not hashed.
         */
        $this->dbu->writePassword($newUser, 'foobarbaz');
        $this->assertTrue($this->dbu->checkPassword($newUser, 'foobarbaz'));

        /*
         * Add an existing role to our new user. The database is initialized with roles: Contributor, "Editor,
         * Training", "Editor, Full", Reviewer, Administrator, System Administrator
         *
         */
        $roleObjList = $this->dbu->listRoles();
        $testUserRole = null;
        foreach($roleObjList as $roleObj)
        {
            if ($roleObj->getLabel() == 'System Administrator')
            {
                $this->dbu->addRoleToUser($newUser, $roleObj);
                $testUserRole = $roleObj;
                break;
            }
        }
        $roleList = $this->dbu->listUserRoles($newUser);
        $this->assertEquals($roleList[0]->getLabel(), 'System Administrator');

        /*
         * Create two demo privs, write to db, add the first demo privilege to our test role so we can be sure it has
         * at least one privilege. Later all the second priv via the alternative method.
         */
        $privZero = new \snac\data\Privilege();
        $privZero->setLabel(sprintf("demo3 priv %s", time()));
        $privZero->setDescription('This is a demo test privilege');
        $this->dbu->writePrivilege($privZero);

        $privOne = new \snac\data\Privilege();
        $privOne->setLabel(sprintf("demo4 priv %s", time()+1));
        $privOne->setDescription('This is a demo test privilege');
        $this->dbu->writePrivilege($privOne);
        $pList = $this->dbu->listPrivileges();
        $testUserRole->addPrivilege($privZero);
        $this->dbu->writeRole($testUserRole);
        /*
         * Change the role. Unfortunatly, the user object we have is now stale, so read it back from the db.
         */
        $this->dbu->addPrivilegeToRole($testUserRole,$privOne); // just to exercise the code
        $newUser = $this->dbu->readUser($newUser);

        $userAsJson = $newUser->toJSON();

        /*
         * $cfile = fopen('user_object_json.txt', 'w');
         * fwrite($cfile, $userAsJson);
         * fclose($cfile);
         */

        $this->enableLogging();
        $this->logDebug(sprintf("user as json\n%s", $userAsJson));

        $userList = $this->dbu->listUsers();
        $roleList = $this->dbu->listUserRoles($newUser);
        $roleCopy = $this->dbu->readRole($testUserRole->getID());
        $privList = $this->dbu->listPrivileges();
        $this->dbu->removePrivilegeFromRole($testUserRole, $privOne);

        $this->assertTrue($this->dbu->hasRole($newUser, $testUserRole));
        $this->assertTrue($this->dbu->hasPrivilegeByLabel($newUser, $privZero->getLabel()));
        $this->assertTrue($this->dbu->hasPrivilege($newUser, $privZero));

        /*
         * Remove the demo privs. Don't delete the test role which is an existing role 'System Administrator'.
         */
        $this->dbu->removePrivilegeFromRole($testUserRole, $privZero);
        $this->dbu->removePrivilegeFromRole($testUserRole, $privOne);
        $this->dbu->erasePrivilege($privZero);
        $this->dbu->erasePrivilege($privOne);

        /*
         * We might add a default role (not necessarily 'Public HRT'), so even during testing we cannot assume
         * that role[0] is 'System Administrator'.
         */
        $roleList = $this->dbu->listUserRoles($newUser);
        /*
         * $foundSystem = false;
         * $systemRole = null;
         * foreach($roleList as $role)
         * {
         *     if ($role->getLabel() == 'System Administrator')
         *     {
         *         $foundSystem = true;
         *         $systemRole = $role;
         *     }
         * }
         */
        $systemRole = $this->dbu->checkRoleByLabel($newUser, 'System Administrator');
        // false == null, so we only need to check for != null.
        $this->assertTrue($systemRole != null);

        /*
         * Write out the user object as for review.
         */
        /*
         * $cfile = fopen('user_object_json.txt', 'w');
         * fwrite($cfile, $newUser->toJSON());
         * fclose($cfile);
         */

        /*
         * Remove the role 'System Administrator' from our user, and count. User might always have a default
         * role which we should not remove.
         *
         * Rather than rely on an index, the code above saves the system role in a variable, and we use that
         * variable here.
         */
        // $this->dbu->removeUserRole($newUser, $roleList[0]);
        $this->dbu->removeUserRole($newUser, $systemRole);
        $roleList = $this->dbu->listUserRoles($newUser);
        $this->assertEquals(count($roleList), 0);

        /*
         * Create a new role, add it, check that our user has the role. Normally, roles probably aren't
         * deleted, but we want to delete the temp role as part of cleaning up.
         */
        $roleLabel = sprintf("demo5 %s",time()+2);
        $demoRole = new \snac\data\Role();
        $demoRole->setLabel($roleLabel);
        $demoRole->setDescription('Demo role created during testing');
        $this->dbu->writeRole($demoRole);
        $this->dbu->addRoleToUser($newUser, $demoRole);
        $roleList = $this->dbu->listUserRoles($newUser);
        $preCleaningRoleList = $this->dbu->listRoles();

        // false == null so we only check for != null
        $this->assertTrue($this->dbu->checkRoleByLabel($newUser, $roleLabel) != null);

        /*
         * Clean up, some. Remove the role from our user, delete the role. The make sure our user is back to
         * the same number of roles as before.
         */
        $this->dbu->removeUserRole($newUser, $demoRole);
        $this->dbu->eraseRoleByID($demoRole->getID());
        $postCleaningRoleList = $this->dbu->listRoles();
        $this->assertEquals(count($preCleaningRoleList), count($postCleaningRoleList)+1);


        /*
         * Start again with a new demo role, add to user then, remove all user roles by setting the role list
         * to an empty array, then saving the user with $saveRole=true. Finally, remove the demo role.
         *
         */
        $roleLabel = sprintf("demo6 %s", time()+3);
        $demoRole = new \snac\data\Role();
        $demoRole->setLabel($roleLabel);
        $demoRole->setDescription('Demo role created during testing');
        $this->dbu->writeRole($demoRole);

        /*
         * Using toJSON() did not reveal the bug. Must use toArray() and the constructor.
         */ 
        $dArray = $demoRole->toArray();
        $checkRole = new \snac\data\Role($dArray);
        $this->assertEquals($checkRole->getID(), $demoRole->getID());
        $this->assertEquals($checkRole->getLabel(), $demoRole->getLabel());
        $this->assertEquals($checkRole->getDescription(), $demoRole->getDescription());
        
        $this->dbu->addRoleToUser($newUser, $demoRole);

        $newUser->setRoleList(array()); // zero the role list

        // public function saveUser($user, $saveRole=false)
        $this->dbu->saveUser($newUser, true); // saving with zero role list removes all roles

        $roleList = $this->dbu->listUserRoles($newUser);
        $this->assertEquals(0, count($roleList));
        $this->dbu->eraseRoleByID($demoRole->getID());
        $reallyPostCleaningRoleList = $this->dbu->listRoles();
        $this->assertEquals(count($reallyPostCleaningRoleList), count($postCleaningRoleList));

        /*
         * Create group, exercise the group functions.
         */
        $startGroups = $this->dbu->listGroups();
        $gLabel = sprintf("demo7 %s", time()+4);
        $demoGroup = new \snac\data\Group();
        $demoGroup->setLabel($gLabel);
        $demoGroup->setDescription("Demo test $gLabel group for testing");
        $this->dbu->writeGroup($demoGroup); // test insert

        $groupFromDB = $this->dbu->readGroup($demoGroup);
        $this->assertEquals($groupFromDB->getID(), $demoGroup->getID());

        $afterAddGroups = $this->dbu->listGroups();
        $this->assertEquals(count($startGroups)+1, count($afterAddGroups));

        $demoGroup->setDescription("Demo test $gLabel group for testing, updated");
        $this->dbu->writeGroup($demoGroup); // test update
        $this->dbu->addUserToGroup($newUser, $demoGroup);
        $newUserGroupList = $this->dbu->listGroupsForUser($newUser);
        $this->assertEquals($newUserGroupList[0]->getDescription(), "Demo test $gLabel group for testing, updated");
        $this->assertEquals(1, count($newUserGroupList));

        // $everyone=true
        $usersInDemoGroup = $this->dbu->listUsersInGroup($demoGroup, true);
        $this->assertEquals(1,count($usersInDemoGroup));

        /*
         * Disable $newUser and confirm that the group has zero active users. $everyone=false, list only
         * active users. Then enable the user and confirm that active users is 1.
         */
        $this->dbu->disableUser($newUser);
        $usersInDemoGroup = $this->dbu->listUsersInGroup($demoGroup, false);
        $this->assertEquals(0,count($usersInDemoGroup));

        $this->dbu->enableUser($newUser);
        $usersInDemoGroup = $this->dbu->listUsersInGroup($demoGroup, false);
        $this->assertEquals(1,count($usersInDemoGroup));

        $this->dbu->removeUserFromGroup($newUser, $demoGroup);
        $newUserGroupList = $this->dbu->listGroupsForUser($newUser);
        $this->assertEquals(0, count($newUserGroupList));
        $this->dbu->eraseGroup($demoGroup);

        $afterEraseGroups = $this->dbu->listGroups();
        $this->assertEquals(count($startGroups), count($afterEraseGroups));


        /*
         * Uncomment the lines below to check the user-does-not-exist case. Later, make this a real test.
         */
        /*
         * printf("\ndbusertest deleting appuserid: %s\n", $newUser->getUserID());
         * $this->dbu->eraseUser($newUser);
         */


        /*
         * This is clearly a lame session, which is the point of the test.  If the user is not cleaned up, the
         * old test session will linger. So, before testing session code remove any copies of session 'foo'.
         */
         $newUser->setToken(array('access_token' => 'foo',
                                  'expires' => 12345));
         $this->dbu->removeSession($newUser);
         if (! $this->dbu->sessionExists($newUser))
         {
             $this->dbu->addSession($newUser);
         }

         /*
          * The expire time is in the past, so this session is not active.
          */
         $this->assertFalse($this->dbu->sessionActive($newUser));

         /*
          * Extend the session into the future
          */
         $this->dbu->sessionExtend($newUser, time() + (60*60) + 10);
         $this->assertTrue($this->dbu->sessionActive($newUser));

         /*
          * When things are normally successful, we will clean up.
          */
         $this->dbu->eraseUser($newUser);

    }

    public function testAutoUser()
    {
        /*
         * Create a new user object
         */
        $userObj = new \snac\data\User();
        $userObj->setFirstName("Malf");
        $userObj->setLastName("Torrent");
        $userObj->setFullName("Malf S Torrent");
        $userObj->setAvatar("http://example.com/avatar");
        $userObj->setAvatarSmall("http://example.com/avatar_small");
        $userObj->setAvatarLarge("http://example.com/avatar_large");
        $userObj->setEmail("mst3k@example.com");
        $userObj->setUserActive(true);

        /*
         * User does not exist in db.
         *
         * This is a more or less valid session, with an expires 1 hour in the future.
         */
        $userObj->setToken(array('access_token' => 'foo',
                                 'expires' => time() + (60*60)));
        $csaReturn = $this->dbu->createUser($userObj);

        $cleanUpUser = clone($csaReturn);

        $foundUserID = $this->dbu->findUserID("mst3k@example.com");
        $this->assertEquals($foundUserID, $csaReturn->getUserID());

        $this->dbu->removeSession($csaReturn);
         if (! $this->dbu->sessionExists($csaReturn))
         {
             $this->dbu->addSession($csaReturn);
         }
        $this->assertEquals($csaReturn->getToken()['access_token'], 'foo');
        $this->assertTrue($this->dbu->sessionActive($csaReturn));

        /*
         * We don't have default role, but if we did this would verify that we got the default role of Public
         * HRT.
         *
         * false == null so we only check for != null
         * We don't current have a role for public hrt.
         * $this->assertTrue($this->dbu->checkRoleByLabel($csaReturn, 'Public HRT') != null);
         */

        $goodUserID = $csaReturn->getUserID();
        /*
         * Test userExists() with the ficticious user id.
         */
        $csaReturn->setUserID('123456');
        $this->assertFalse($this->dbu->userExists($csaReturn));

        /*
         * Check that listUsers for everyone includes the disabled (inactive) user. And that disableUser makes
         * the user inactive.
         */ 
        $firstUserList = $this->dbu->listUsers();
        $this->dbu->disableUser($cleanUpUser);
        $secondUserList = $this->dbu->listUsers();
        $thirdUserList = $this->dbu->listUsers(true);
        $this->assertEquals(count($firstUserList), count($thirdUserList));
        $this->assertEquals(count($firstUserList), count($secondUserList)+1);
        
        /*
         * When things are normally successful, we might want to clean up.
         */
        $this->dbu->eraseUser($cleanUpUser);

    }

    public function testInstitution()
    {
        $firstObj = new \snac\data\Constellation();
        /*
         * Yes, it is a ficticious ic_id, but in the testing world, the ic_id isn't related to anything.
         * The first write tests insert, and the second tests update.
         *
         * Since we are using ic_id in table snac_institution, is it theoretically possible that 1234 and 4567
         * are valid ic_id values. Not likely, but possible. We could use 1 and 2 which are so small that they
         * will never be constellation IDs in the real world.
         *
         * The field typt is int, so we can use negative numbers for testing.
         */
        $initialList = $this->dbu->listInstitutions();

        $firstObj->setID(1234);
        $this->dbu->writeInstitution($firstObj);

        $firstICID = $firstObj->getID();
        $secondObj = new \snac\data\Constellation();
        $secondObj->setID(4567);
        $this->dbu->writeInstitution($secondObj);

        $iList = $this->dbu->listInstitutions();
        $this->dbu->eraseInstitution($firstObj);
        $this->dbu->eraseInstitution($secondObj);
        $postList = $this->dbu->listInstitutions();
        /*
         * Do all the assertions at the end. Failed assertions halt execution, so any error prevents the test
         * from cleaning up. Of course, some kinds of frank bugs will prevent cleaning up as well.
         */
        $this->assertNotNull($firstObj->getID());
        $this->assertEquals($firstICID, 1234);
        $this->assertEquals($secondObj->getID(), 4567);
        $this->assertEquals(count($initialList), count($iList)-2);
        $this->assertEquals(count($initialList), count($postList));
    }

}
