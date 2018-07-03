<?php
function user() {
    global $dbhr, $dbhm;

    $id = intval(presdef('id', $_REQUEST, NULL));

    $ret = [ 'ret' => 1, 'status' => 'Unknown verb' ];

    switch ($_REQUEST['type']) {
        case 'GET': {
            $u = new User($dbhr, $dbhm, $id);

            $ret = [
                'ret' => 0,
                'status' => 'Success',
                'user' => $u->getPublic()
            ];
            break;
        }

        case 'POST': {
            $firstname = $lastname = $fullname = $facebooktoken = NULL;
            foreach(['firstname', 'lastname', 'fullname', 'facebooktoken'] as $key) {
                $$key = presdef($key, $_REQUEST, NULL);
            }

            $u = new User($dbhr, $dbhm);

            $ret = [
                'ret' => 2,
                'status' => 'Create failed'
            ];

            if ($facebooktoken) {
                # We have a token from Facebook.  Validate it.
                try {
                    $fb = new \Facebook\Facebook([
                        'app_id' => FBAPP_ID,
                        'app_secret' => FBAPP_SECRET,
                        'default_access_token' => $facebooktoken
                    ]);

                    $response = $fb->get('/me?fields=id,name,first_name,last_name,email', $facebooktoken);
                    $fbme = $response->getGraphUser()->asArray();
                    $facebookid = presdef('id', $fbme, NULL);
                    $firstname = presdef('first_name', $fbme, NULL);
                    $lastname = presdef('last_name', $fbme, NULL);
                    $fullname = presdef('name', $fbme, NULL);
                    $email = presdef('email', $fbme, NULL);

                    if ($email) {
                        $u->addEmail($email);
                    }

                    $uid = $u->findByFacebook($facebookid);

                    if (!$uid) {
                        $uid = $u->create($firstname, $lastname, $fullname);
                        $u->addFacebook($facebookid, $facebooktoken);
                    }

                    if ($uid) {
                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'id' => $uid
                        ];

                        error_log("Save uid $uid");
                        $_SESSION['id'] = $uid;
                    }

                } catch (Exception $e) {
                    error_log("Facebook failed with " . $e->getMessage());

                    $ret = [
                        'ret' => 3,
                        'status' => "Facebook failed with " . $e->getMessage()
                    ];
                }
            }


            break;
        }
    }

    return($ret);
}
