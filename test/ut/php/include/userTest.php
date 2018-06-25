<?php
if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/php/GSTestCase.php';
require_once BASE . '/include/User.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class userTest extends GSTestCase {
    private $dbhr, $dbhm;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;
    }

    protected function tearDown() {
        parent::tearDown ();
    }

    public function testBasic() {
        error_log(__METHOD__);

        $u = new User($this->dbhr, $this->dbhm);
        $id = $u->create('Test', 'User', NULL);
        $atts = $u->getPublic();
        assertEquals('Test', $atts['firstname']);
        assertEquals('User', $atts['lastname']);
        assertEquals('Test User', $atts['displayname']);

        $u->addEmail('test@ghostsignhunter.org');
        $u->addEmail('test@ghostsignhunter.org');
        assertEquals('test@ghostsignhunter.org', $u->getEmailPreferred());
        $emails = $u->getEmails();
        assertEquals(1, count($emails));
        assertEquals('test@ghostsignhunter.org', $emails[0]['email']);

        $u->addEmail('test3@ghostsignhunter.org');
        assertEquals($id, $u->findByEmail('test@ghostsignhunter.org'));
        assertEquals($id, $u->findByEmail('test3@ghostsignhunter.org'));

        assertNull($u->findByEmail('test2@ghostsignhunter.org'));

        $u->removeEmail('test@ghostsignhunter.org');
        $u->removeEmail('test3@ghostsignhunter.org');

        $u->addFacebook(123, '456');
        assertEquals(123, $u->getFacebook()['facebookid']);
        $u->removeFacebook(123);

        error_log(__METHOD__ . " end");
    }
}