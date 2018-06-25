<?php
if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/php/GSTestCase.php';
require_once BASE . '/include/Sign.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class signTest extends GSTestCase {
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

        $data = file_get_contents('images/Tile.jpg');
        $p = new Sign($this->dbhr, $this->dbhm);
        $id = $p->create(52, 0, $data);
        assertNotNull($id);

        $p = new Sign($this->dbhr, $this->dbhm, $id);
        assertEquals($id, $p->getId());
        $hash = $p->getHash();
        error_log("$id hash $hash");
        assertNotNull($hash);
        assertNotNull($p->getPath());
        assertEquals($hash, $p->getPublic()['hash']);
        assertEquals($data, $p->getData());
        $p->setData($data);
        $p->setPrivate('hash', $hash);
        $p->delete();

        error_log(__METHOD__ . " end");
    }

    public function testLatlng() {
        error_log(__METHOD__);

        $data = file_get_contents('images/Mosaic.jpg');
        $p = new Sign($this->dbhr, $this->dbhm);
        $id = $p->create(NULL, NULL, $data);
        assertNotNull($id);

        $p = new Sign($this->dbhr, $this->dbhm, $id);
        assertEquals($id, $p->getId());
        $hash = $p->getHash();
        error_log("$id hash $hash");
        assertNotNull($hash);
        assertNotNull($p->getPath());
        assertEquals($hash, $p->getPublic()['hash']);
        assertEquals($data, $p->getData());
        $p->setData($data);
        $p->setPrivate('hash', $hash);
        $p->delete();

        error_log(__METHOD__ . " end");
    }
}