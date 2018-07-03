<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}

require_once UT_DIR . '/php/GSAPITestCase.php';

require(BASE . '/include/Sign.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class signAPITest extends GSAPITestCase
{
    public $dbhr, $dbhm;

    public function testSearch()
    {
        error_log(__METHOD__);

        $data = file_get_contents('images/Mosaic.jpg');
        $p = new Sign($this->dbhr, $this->dbhm);
        $id = $p->create(NULL, NULL, $data);
        assertNotNull($id);

        # Should be in news
        $ret = $this->call('news', 'GET', []);
        error_log("Should be in news " . var_export($ret, TRUE));
        $found = FALSE;

        foreach ($ret['news'] as $news) {
            if (pres('sign', $news) && $news['sign']['id'] == $id) {
                $found = TRUE;
            }
        }

        assertTrue($found);

        # Search should find it
        #
        # This one is at 53.872777777778, -2.3905555555556
        $ret = $this->call('sign', 'GET', [ 'swlat' => 53.87, 'nelat' => 53.88, 'swlng' => -2.4, 'nelng' => -2.39 ]);
        error_log("Search should find " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);

        $found = FALSE;

        foreach ($ret['signs'] as $sign) {
            if ($sign['id'] == $id) {
                $found = TRUE;
            }
        }

        assertTrue($found);

        # Outside shouldn't.
        $ret = $this->call('sign', 'GET', [ 'swlat' => 52.87, 'nelat' => 52.88, 'swlng' => -3.4, 'nelng' => -3.39 ]);
        error_log("Search shouldn't find " . var_export($ret, TRUE));
        assertEquals(0, $ret['ret']);

        $found = FALSE;

        foreach ($ret['signs'] as $sign) {
            if ($sign['id'] == $id) {
                $found = TRUE;
            }
        }

        assertFalse($found);

        error_log(__METHOD__ . " end");
    }
}
