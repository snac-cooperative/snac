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
     * DBUser object for this class
     * @var $dbu \snac\server\database\DBUser object
     */ 
    private $dbu = null;


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
        // Consider creating a single parser instance here, and reusing it throughout, but be aware that this
        // is called for every test, so time consuming setup will be repeated for every test.
    }

    public function testBasic()
    {
        /*
         * Start by deleting the test account, if it exists. This happens if things failed on a previous run.
         */ 
        $appUserID = $this->dbu->findUserID("mst3k@example.com");
        if ($appUserID)
        {
            $this->dbu->getSQL()->deleteUser($appUserID);
        }

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
        $newUser = $this->dbu->createUser($userObj);

        $this->assertNotNull($newUser);

        /*
         * Update the user in order to exercise updateUser()
         */
        $newUser->setFirstName('Malvie');
        $this->dbu->saveUser($newUser);
        $newUser = $this->dbu->readUser($newUser->getUserID());
        $this->assertEquals('Malvie', $newUser->getFirstName());

        /*
         * Try adding a password. Yes, I know this password is not hashed.
         */ 
        $this->dbu->writePassword($newUser, 'foobarbaz');
        $this->assertTrue($this->dbu->checkPassword($newUser, 'foobarbaz'));

        /*
         * Add a role to our new user. Really, the db should be initialized with a 'researcher' or
         * 'contributor' role.
         */ 
        $roleObjList = $this->dbu->roleList();
        foreach($roleObjList as $roleObj)
        {
            if ($roleObj->getLabel() == 'system')
            {
                $this->dbu->addUserRole($newUser, $roleObj);
                break;
            }
        }
        $roleList = $this->dbu->listUserRole($newUser);
        $this->assertEquals($roleList[0]->getLabel(), 'system');

        /*
         * Write out the user object as for review.
         */ 
        /* 
         * $cfile = fopen('user_object.txt', 'w');
         * fwrite($cfile, var_export($newUser, 1));
         * fclose($cfile);
         */

        /*
         * Remove the role from our user, and count. The user should have zero roles.
         */ 
        $this->dbu->removeUserRole($newUser, $roleList[0]);
        $roleList = $this->dbu->listUserRole($newUser);
        $this->assertEquals(count($roleList), 0);

        /*
         * Create a new role, add it, check that our user has the role. Normally, roles probably aren't deleted, but we want to
         * delete the temp role as part of cleaning up.
         */ 
        $role = $this->dbu->createRole('demo', 'Demo role created during testing');
        $this->dbu->addUserRole($newUser, $role);
        $roleList = $this->dbu->listUserRole($newUser);
        $preCleaningRoleList = $this->dbu->roleList();
        $this->assertEquals($roleList[0]->getLabel(), 'demo');

        /* 
         * Clean up, some. Remove the role from our user, delete the role. The make sure our user is back to
         * the same number of roles as before.
         */
        $this->dbu->removeUserRole($newUser, $role);
        $this->dbu->getSQL()->deleteRole($role->getID());
        $postCleaningRoleList = $this->dbu->roleList();
        $this->assertEquals(count($preCleaningRoleList), count($postCleaningRoleList)+1);

        /*
         * This is clearly a lame session, but it will still be created. If we run call checkSessionActive() a
         * second time, the session should disappear.
         *
         * Uncomment the lines below to check the user-does-not-exist case. Later, make this a real test.
         */ 
        /* 
         * printf("\ndbusertest deleting appuserid: %s\n", $newUser->getUserID());
         * $this->dbu->getSQL()->deleteUser($newUser->getUserID());
         */

        $newUser->setToken(array('access_token' => 'foo',
                                'expires' => 12345));

        // printf("\ndbusertest session exists: %d\n", $this->dbu->sessionExists($newUser));

        $csaReturn = $this->dbu->checkSessionActive($newUser);

        $this->dbu->checkSessionActive($csaReturn);
        

        /*
         * When things are normally successful, we will clean up.  Or not. If we don't clean up, then we can
         * use psql to look at the database.
         */ 
        // $this->dbu->getSQL()->deleteUser($newUser->getUserID());

    }
    
}
