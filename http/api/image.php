<?php

function image() {
    global $dbhr, $dbhm;

    $ret = [ 'ret' => 100, 'status' => 'Unknown verb' ];
    $id = intval(presdef('id', $_REQUEST, 0));
    $circle = presdef('circle', $_REQUEST, NULL);

    $sizelimit = 800;

    switch ($_REQUEST['type']) {
        case 'GET': {
            $s = new Sign($dbhr, $dbhm, $id);
            $data = $s->getData();
            $i = new Image($data);

            $ret = [
                'ret' => 1,
                'status' => "Failed to create image $id"
            ];

            if ($i->img) {
                $w = intval(presdef('w', $_REQUEST, $i->width()));
                $h = intval(presdef('h', $_REQUEST, $i->height()));

                if (($w > 0) || ($h > 0)) {
                    # Need to resize
                    $i->scale($w, $h);
                }

                if ($circle) {
                    $i->circle($w);
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'img' => $i->getDataPNG()
                    ];
                } else {
                    $ret = [
                        'ret' => 0,
                        'status' => 'Success',
                        'img' => $i->getData()
                    ];
                }
            }

            break;
        }

        case 'POST': {
            $ret = [ 'ret' => 1, 'status' => 'No photo provided' ];

            # This next line is to simplify UT.
            $rotate = pres('rotate', $_REQUEST) ? intval($_REQUEST['rotate']) : NULL;

            if ($rotate) {
                # We want to rotate.  Do so.
                $s = new Sign($dbhr, $dbhm, $id);
                $data = $s->getData();
                $i = new Image($data);
                $i->rotate($rotate);
                $newdata = $i->getData(100);
                $s->setData($newdata);

                $ret = [
                    'ret' => 0,
                    'status' => 'Success',
                    'rotatedsize' => strlen($newdata)
                ];
            } else {
                $photo = presdef('photo', $_FILES, NULL) ? $_FILES['photo'] : $_REQUEST['photo'];
                $lat = pres('lat', $_REQUEST) ? floatval($_REQUEST['lat']) : NULL;
                $lng = pres('lng', $_REQUEST) ? floatval($_REQUEST['lng']) : NULL;
                $title = pres('title', $_REQUEST) ? $_REQUEST['title'] : NULL;
                $notes = pres('notes', $_REQUEST) ? $_REQUEST['notes'] : NULL;

                $mimetype = presdef('type', $photo, NULL);

                # Make sure what we have looks plausible - the file upload plugin should ensure this is the case.
                if ($photo &&
                    pres('tmp_name', $photo) &&
                    strpos($mimetype, 'image/') === 0) {

                    # We may need to rotate.
                    $data = file_get_contents($photo['tmp_name']);
                    $image = imagecreatefromstring($data);
                    $exif = @exif_read_data($photo['tmp_name']);

                    if($exif && !empty($exif['Orientation'])) {
                        switch($exif['Orientation']) {
                            case 2:
                                imageflip($image , IMG_FLIP_HORIZONTAL);
                                break;
                            case 3:
                                $image = imagerotate($image,180,0);
                                break;
                            case 4:
                                $image = imagerotate($image,180,0);
                                imageflip($image , IMG_FLIP_HORIZONTAL);
                                break;
                            case 5:
                                $image = imagerotate($image,90,0);
                                imageflip ($image , IMG_FLIP_VERTICAL);
                                break;
                            case 6:
                                $image = imagerotate($image,-90,0);
                                break;
                            case 7:
                                $image = imagerotate($image,-90,0);
                                imageflip ($image , IMG_FLIP_VERTICAL);
                                break;
                            case 8:
                                $image = imagerotate($image,90,0);
                                break;
                        }

                        ob_start();
                        imagejpeg($image, NULL, 100);
                        $data = ob_get_contents();
                        ob_end_clean();
                    }

                    error_log("Create image len " . strlen($data));

                    if ($data) {
                        $s = new Sign($dbhr, $dbhm, NULL);
                        $id = $s->create($lat, $lng, $data, $title, $notes);

                        error_log("Session " . var_export($_SESSION, TRUE));

                        if (pres('id', $_SESSION)) {
                            $s->setPrivate('userid', $_SESSION['id']);
                        }

                        # Make sure it's not too large, to keep DB size down.  Ought to have been resized by
                        # client, but you never know.
                        $data = $s->getData();
                        $i = new Image($data);
                        $h = $i->height();
                        $w = $i->width();

                        if ($w > $sizelimit) {
                            $h = $h * $sizelimit / $w;
                            $w = $sizelimit;
                            $i->scale($w, $h);
                            $data = $i->getData(100);
                            $s->setPrivate('data', $data);
                        }

                        $ret = [
                            'ret' => 0,
                            'status' => 'Success',
                            'id' => $id,
                            'path' => $s->getPath(FALSE),
                            'paththumb' => $s->getPath(TRUE)
                        ];
                    }
                }

                # Uploader code requires this field.
                $ret['error'] = $ret['ret'] == 0 ? NULL : $ret['status'];
            }

            break;
        }
    }

    return($ret);
}
