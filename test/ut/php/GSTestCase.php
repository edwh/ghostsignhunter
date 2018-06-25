<?php
use Pheanstalk\Pheanstalk;
require_once dirname(__FILE__) . '/../../../include/config.php';
require_once BASE . '/include/db.php';

require_once BASE . '/vendor/phpunit/phpunit/src/Framework/TestCase.php';
require_once BASE . '/vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
abstract class GSTestCase extends PHPUnit\Framework\TestCase {
    const LOG_SLEEP = 600;

    private $dbhr, $dbhm;

    public static $unique = 1;

    public function tidy() {
        if (defined('_SESSION')) {
            unset($_SESSION['id']);
        }

        # Leave only one of our test photo in the DB.
        $signs = $this->dbhr->preQuery("SELECT id FROM signs WHERE hash = '4c626860e068634e';");
        $id = NULL;
        foreach ($signs as $sign) {
            if (!$id) {
                $id = $sign['id'];
            } else {
                $this->dbhm->preExec("DELETE FROM signs WHERE id = ?;", [
                    $sign['id']
                ]);
            }
        }

        # Delete test users
        $this->dbhm->preExec("DELETE FROM users WHERE displayname = 'Test User';");
    }

    protected function setUp() {
        parent::setUp ();

        error_reporting(E_ALL);

        global $dbhr, $dbhm;
        $this->dbhr = $dbhr;
        $this->dbhm = $dbhm;

        $this->tidy();

        @session_destroy();
        @session_start();

        # Clear duplicate protection.
        $datakey = 'POST_DATA_' . session_id();
        $predis = new Redis();
        $predis->pconnect(REDIS_CONNECT);
        $predis->del($datakey);

        set_time_limit(600);
    }

    protected function tearDown() {
        parent::tearDown ();

        try {
            @session_destroy();
        } catch (Exception $e) {
            error_log("Session exception " . $e->getMessage());
        }

        $this->tidy();
    }

    public function unique($msg) {
        $unique = time() . rand(1,1000000) . GSTestCase::$unique++;
        $newmsg1 = preg_replace('/X-Yahoo-Newman-Id: (.*)\-m\d*/i', "X-Yahoo-Newman-Id: $1-m$unique", $msg);
        #assertNotEquals($msg, $newmsg1, "Newman-ID");
        $newmsg2 = preg_replace('/Message-Id:.*\<.*\>/i', 'Message-Id: <' . $unique . "@test>", $newmsg1);
        #assertNotEquals($newmsg2, $newmsg1, "Message-Id");
        #error_log("Unique $newmsg2");
        return($newmsg2);
    }

    public function waitBackground() {
        # We wait until either the queue is empty, or the first item on it has been put there since we started
        # waiting (and therefore anything we put on has been handled).
        $start = time();

        $pheanstalk = new Pheanstalk(PHEANSTALK_SERVER);
        $count = 0;
        do {
            $stats = $pheanstalk->stats();
            $ready = $stats['current-jobs-ready'];

            error_log("...waiting for background work, current $ready, try $count");

            if ($ready == 0) {
                # The background processor might have removed the job, but not quite yet processed the SQL.
                sleep(2);
                break;
            }

            try {
                $job = $pheanstalk->peekReady();
                $data = json_decode($job->getData(), true);

                if ($data['queued'] > $start) {
                    sleep(2);
                    break;
                }
            } catch (Exception $e) {}

            sleep(5);
            $count++;

        } while ($count < GSTestCase::LOG_SLEEP);

        if ($count >= GSTestCase::LOG_SLEEP) {
            assertFalse(TRUE, 'Failed to complete background work');
        }
    }

    public function findLog($type, $subtype, $logs) {
        foreach ($logs as $log) {
            if ($log['type'] == $type && $log['subtype'] == $subtype) {
                error_log("Found log " . var_export($log, true));
                return($log);
            }
        }

        error_log("Failed to find log $type $subtype in " . var_export($logs, TRUE));
        return(NULL);
    }
}

