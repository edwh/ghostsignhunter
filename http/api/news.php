<?php
function news() {
    global $dbhr, $dbhm;

    $id = intval(presdef('id', $_REQUEST, NULL));
    $ctx = presdef('ctx', $_REQUEST, []);
    $n = new News($dbhr, $dbhm, $id);

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'news' => $n->get($ctx)
            ];
            break;
        }
    }

    return($ret);
}
