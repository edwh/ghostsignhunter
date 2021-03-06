<?php
@session_start();
$scriptstart = microtime(true);

$_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD']);
$_REQUEST['type'] = $_SERVER['REQUEST_METHOD'];

if (array_key_exists('HTTP_X_HTTP_METHOD_OVERRIDE', $_SERVER)) {
    # Used by Backbone's emulateHTTP to work around servers which don't handle verbs like PATCH very well.
    #
    # We use this because when we issue a PATCH we don't seem to be able to get the body parameters.
    $_REQUEST['type'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    #error_log("Request method override to {$_REQUEST['type']}");
}

require_once('../../include/misc/apiheaders.php');
require_once('../../include/config.php');

// @codeCoverageIgnoreStart

if (file_exists(BASE . '/http/maintenance_on.html')) {
    echo json_encode(array('ret' => 111, 'status' => 'Down for maintenance'));
    exit(0);
}
// @codeCoverageIgnoreEnd

require_once(BASE . '/include/db.php');
global $dbhr, $dbhm;

# Include modules
require_once(BASE . '/include/utils.php');
require_once(BASE . '/include/misc/Image.php');
require_once(BASE . '/include/Sign.php');
require_once(BASE . '/include/User.php');
require_once(BASE . '/include/News.php');

# Include each API call
require_once(BASE . '/http/api/sign.php');
require_once(BASE . '/http/api/image.php');
require_once(BASE . '/http/api/user.php');
require_once(BASE . '/http/api/news.php');

$includetime = microtime(true) - $scriptstart;

# All API calls come through here.
#error_log("Request " . var_export($_REQUEST, TRUE));
#error_log("Server " . var_export($_SERVER, TRUE));

if (array_key_exists('model', $_REQUEST)) {
    # Used by Backbone's emulateJSON to work around servers which don't handle requests encoded as
    # application/json.
    $_REQUEST = array_merge($_REQUEST, json_decode($_REQUEST['model'], true));
    unset($_REQUEST['model']);
}

$call = pres('call', $_REQUEST);

if ($_REQUEST['type'] == 'OPTIONS') {
    # We don't bother returning different values for different calls.
    http_response_code(204);
    @header('Allow: POST, GET, DELETE, PUT');
    @header('Access-Control-Allow-Methods:  POST, GET, DELETE, PUT');
} else {
    # Actual API calls
    $ret = array('ret' => 1000, 'status' => 'Invalid API call');
    $t = microtime(true);

    # We wrap the whole request in a retry handler.  This is so that we can deal with errors caused by
    # conflicts within the Percona cluster.
    $apicallretries = 0;

    do {
        # Duplicate POST protection.  We upload multiple images so don't protect against those.
        if ((DUPLICATE_POST_PROTECTION > 0) &&
            array_key_exists('REQUEST_METHOD', $_SERVER) && ($_REQUEST['type'] == 'POST') &&
            $call != 'image' &&
            $call != 'user'
        ) {
            # We want to make sure that we don't get duplicate POST requests within the same session.  We can't do this
            # using information stored in the session because when Redis is used as the session handler, there is
            # no session locking, and therefore two requests in quick succession could be allowed.  So instead
            # we use Redis directly with a roll-your-own mutex.
            #
            # TODO uniqid() is not actually unique.  Nor is md5.
            $req = $_SERVER['REQUEST_URI'] . serialize($_REQUEST);
            $lockkey = 'POST_LOCK_' . session_id();
            $datakey = 'POST_DATA_' . session_id();
            $uid = uniqid('', TRUE);
            $predis = new Redis();
            $predis->pconnect(REDIS_CONNECT);

            # Get a lock.
            $start = time();
            do {
                $rc = $predis->setNx($lockkey, $uid);

                if ($rc) {
                    # We managed to set it.  Ideally we would set an expiry time to make sure that if we got
                    # killed right now, this session wouldn't hang.  But that's an extra round trip on each
                    # API call, and the worst case would be a single session hanging, which we can live with.

                    # Sound out the last POST.
                    $last = $predis->get($datakey);

                    # Some actions are ok, so we exclude those.
                    if (!in_array($call, [ ]) &&
                        $last === $req) {
                        # The last POST request was the same.  So this is a duplicate.
                        $predis->del($lockkey);
                        $ret = array('ret' => 999, 'text' => 'Duplicate request - rejected.', 'data' => $_REQUEST);
                        echo json_encode($ret);
                        break 2;
                    }

                    # The last request wasn't the same.  Save this one.
                    $predis->set($datakey, $req);
                    $predis->expire($datakey, DUPLICATE_POST_PROTECTION);

                    # We're good to go - release the lock.
                    $predis->del($lockkey);
                    break;
                    // @codeCoverageIgnoreStart
                } else {
                    # We didn't get the lock - another request for this session must have it.
                    usleep(100000);
                }
            } while (time() < $start + 45);
            // @codeCoverageIgnoreEnd
        }

        try {
            # Each call is inside a file with a suitable name.
            #
            # call_user_func doesn't scale well on multicores with HHVM, so we can't figure out the function from
            # the call name - use a switch instead.
            switch ($call) {
                case 'image':
                    $ret = image();
                    break;
                case 'news':
                    $ret = news();
                    break;
                case 'sign':
                    $ret = sign();
                    break;
                case 'user':
                    $ret = user();
                    break;
                case 'DBexceptionWork':
                    # For UT
                    if ($apicallretries < 2) {
                        error_log("Fail DBException $apicallretries");
                        throw new DBException();
                    }

                    break;
                case 'DBexceptionFail':
                    # For UT
                    throw new DBException();
                case 'DBleaveTrans':
                    # For UT
                    $dbhm->beginTransaction();

                    break;
            }

            # If we get here, everything worked.
            if (pres('img', $ret)) {
                # This is an image we want to output.  Can cache forever - if an image changes it would get a new id
                @header('Content-Type: image/jpeg');
                @header('Content-Length: ' . strlen($ret['img']));
                @header('Cache-Control: max-age=5360000');
                print $ret['img'];
            } else {
                # This is a normal API call.  Add profiling info.
                $ret['call'] = $call;
                $ret['type'] = $_REQUEST['type'];
                $ret['session'] = session_id();
                $ret['duration'] = (microtime(true) - $scriptstart);
                $ret['cpucost'] = getCpuUsage();
                $ret['dbwaittime'] = $dbhr->getWaitTime() + $dbhm->getWaitTime();
                $ret['includetime'] = $includetime;
                $ret['cachetime'] = $dbhr->getCacheTime();
                $ret['cachequeries'] = $dbhr->getCacheQueries();
                $ret['cachehits'] = $dbhr->getCacheHits();

                filterResult($ret);
                $str = json_encode($ret);
                echo $str;
            }

            if ($apicallretries > 0) {
                error_log("API call $call worked after $apicallretries");
            }

            $ip = presdef('REMOTE_ADDR', $_SERVER, '');

            if (BROWSERTRACKING && (presdef('type', $_REQUEST, NULL) != 'GET') &&
                (gettype($ret) == 'array' && !array_key_exists('nolog', $ret))) {
                # Save off the API call and result.  Don't save GET calls as they don't change the DB and there are a
                # lot of them.
                #
                # Beanstalk has a limit on the size of job that it accepts; no point trying to log absurdly large
                # API requests.
                $req = json_encode($_REQUEST);
                $rsp = json_encode($ret);

                if (strlen($req) + strlen($rsp) > 180000) {
                    $req = substr($req, 0, 1000);
                    $rsp = substr($rsp, 0, 1000);
                }

                $sql = "INSERT INTO logs_api (`userid`, `ip`, `session`, `request`, `response`) VALUES (" . presdef('id', $_SESSION, 'NULL') . ", '" . presdef('REMOTE_ADDR', $_SERVER, '') . "', " . $dbhr->quote(session_id()) .
                    ", " . $dbhr->quote($req) . ", " . $dbhr->quote($rsp) . ");";
                $dbhm->background($sql);
            }

            break;
        } catch (Exception $e) {
            # This is our retry handler - see apiheaders.
            if ($e instanceof DBException) {
                # This is a DBException.  We want to retry, which means we just go round the loop
                # again.
                error_log("DB Exception try $apicallretries," . $e->getMessage() . ", " . $e->getTraceAsString());
                $apicallretries++;

                if ($apicallretries >= API_RETRIES) {
                    if (strpos($e->getMessage(), 'WSREP has not yet prepared node for application') !== FALSE) {
                        # Our cluster is sick.  Make it look like maintenance.
                        echo json_encode(array('ret' => 111, 'status' => 'Cluster not operational'));
                    } else {
                        echo json_encode(array('ret' => 997, 'status' => 'DB operation failed after retry', 'exception' => $e->getMessage()));
                    }
                }
            } else {
                # Something else.
                error_log("Uncaught exception at " . $e->getFile() . " line " . $e->getLine() . " " . $e->getMessage());
                echo json_encode(array('ret' => 998, 'status' => 'Unexpected error', 'exception' => $e->getMessage()));
                break;
            }

            # Make sure the duplicate POST detection doesn't throw us.
            $_REQUEST['retry'] = uniqid('', TRUE);
        }
    } while ($apicallretries < API_RETRIES);

    # Any outstanding transaction is a bug; force a rollback to avoid locks lasting beyond this call.
    if ($dbhm->inTransaction()) {
        $dbhm->rollBack();
    }

    if ($_REQUEST['type'] != 'GET') {
        # This might have changed things.
        $_SESSION['modorowner'] = [];
    }
}
