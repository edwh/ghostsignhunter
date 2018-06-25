<?php
function sign() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $swlat = $swlng = $nelat = $nelng = 0;
            $some = FALSE;

            foreach (['nelat', 'nelng', 'swlat', 'swlng'] as $key) {
                $$key = NULL;

                if (pres($key, $_REQUEST)) {
                    $some = TRUE;
                    $$key = floatval($_REQUEST[$key]);
                }
            }

            $s = new Sign($dbhr, $dbhm);

            $users = $signs = [];

            if ($some) {
                list ($users, $signs) = $s->search($swlat, $swlng, $nelat, $nelng);
            }

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'signs' => $signs,
                'users' => $users
            ];
        }
            break;
    }

    return($ret);
}
