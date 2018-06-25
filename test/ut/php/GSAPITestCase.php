<?php

if (!defined('UT_DIR')) {
    define('UT_DIR', dirname(__FILE__) . '/../..');
}
require_once UT_DIR . '/php/GSTestCase.php';
require_once(UT_DIR . '/../../include/config.php');

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
abstract class GSAPITestCase extends GSTestCase {
    public $dbhr, $dbhm;

    private $lastOutput = NULL;

    protected function setUp() {
        parent::setUp ();

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        $_SESSION['id'] = NULL;
    }

    public function call($call, $type, $params, $decode = TRUE) {
        $_REQUEST = array_merge($params);

        $_SERVER['REQUEST_METHOD'] = $type;
        $_SERVER['REQUEST_URI'] = "/api/$call.php";
        $_REQUEST['call'] = $call;

        # API calls have to run from the api directory, as they would from the web server.
        chdir(BASE . '/http/api');
        require(BASE . '/http/api/api.php');

        # Get the output since we last did this.
        $op = $this->getActualOutput();

        if ($this->lastOutput) {
            $len = strlen($this->lastOutput);
            $this->lastOutput = $op;
            $op = substr($op, $len);
        } else {
            $this->lastOutput = $op;
        }

        if ($decode) {
            $ret = json_decode($op, true);
        } else {
            $ret = $op;
        }

        return($ret);
    }
}

