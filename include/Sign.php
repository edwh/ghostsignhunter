<?php

require_once(BASE . '/include/utils.php');
require_once(BASE . '/include/misc/Entity.php');
require_once(BASE . '/include/misc/Image.php');
require_once(BASE . '/include/User.php');
require_once(BASE . '/include/News.php');

use Jenssegers\ImageHash\ImageHash;
use WindowsAzure\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;

class Sign extends Entity {
    public $publicatts = [ 'id', 'lat', 'lng', 'title', 'notes', 'userid', 'added' ];

    const TYPE_SIGN = 'Sign';

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    public function getPath($thumb = FALSE, $id = NULL) {
        $id = $id ? $id : $this->id;

        # We serve up our attachment names as though they are files.
        # When these are fetched it will go through image.php
        $name = $thumb ? "timg" : 'img';
        $domain = $this->archived ? IMAGE_ARCHIVED_DOMAIN : IMAGE_DOMAIN;

        return("https://$domain/{$name}_{$id}.jpg");
    }

    public function getPublic() {
        $ret = parent::getPublic();

        $ret['path'] = $this->getPath(FALSE);
        $ret['paththumb'] = $this->getPath(TRUE);

        $ret['added'] = ISODate($ret['added']);

        return($ret);
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL) {
        $this->fetch($dbhr, $dbhm, $id, 'signs', 'sign', $this->publicatts);

        if ($id) {
            $sql = "SELECT hash, archived FROM signs WHERE id = ?;";
            $atts = $this->dbhr->preQuery($sql, [$id]);
            foreach ($atts as $att) {
                $this->hash = $att['hash'];
                $this->archived = $att['archived'];
            }
        }

        $this->archived = FALSE;
    }

    private function format_gps_data($gpsdata,$lat_lon_ref){
        $gps_info = array();
        foreach($gpsdata as $gps){
            list($j , $k) = explode('/', $gps);
            array_push($gps_info,$j/$k);
        }

        $coordination = $gps_info[0] + ($gps_info[1]/60.00) + ($gps_info[2]/3600.00);
        return (($lat_lon_ref == "S" || $lat_lon_ref == "W" ) ? '-'.$coordination : $coordination);
    }

    public function create($lat, $lng, $data, $title = NULL, $notes = NULL) {
        # We generate a perceptual hash.  This allows us to spot duplicate or similar images later.
        $hasher = new ImageHash;
        $img = @imagecreatefromstring($data);
        $hash = $img ? $hasher->hash($img) : NULL;

        if (!$lat && !$lng) {
            # We want to find any lat/lng in the exif data of the image.
            $fn = tempnam('/tmp/', 'gs_');
            file_put_contents($fn, $data);
            $exif = exif_read_data($fn);

            if($exif) {
                $sections = explode(',', $exif['SectionsFound']);

                if (in_array('GPS', array_flip($sections))) {
                    error_log("EXIF " . var_export($sections, TRUE));
                    $lat = $this->format_gps_data($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
                    $lng = $this->format_gps_data($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
                }
            }
        }

        $id = NULL;

        if ($lat !== NULL && $lng !== NULL) {
            $rc = $this->dbhm->preExec("INSERT INTO signs (`lat`, `lng`, `latlng`, `data`, `hash`, `title`, `notes`) VALUES (?, ?, GEOMFROMTEXT('POINT($lng $lat)'), ?, ?, ?, ?);", [
                $lat,
                $lng,
                $data,
                $hash,
                $title,
                $notes
            ]);

            $id = $rc ? $this->dbhm->lastInsertId() : NULL;
        }

        if ($id) {
            $this->id = $id;

            $n = new News($this->dbhr, $this->dbhm);
            $n->create(News::TYPE_ADDED, presdef('id', $_SESSION, NULL), $id, $title, $lat, $lng);
        }

        return($id);
    }

    public function archive() {
        # We archive out of the DB into Azure.  This reduces load on the servers because we don't have to serve
        # the images up, and it also reduces the disk space we need within the DB (which is not an ideal
        # place to store large amounts of image data);
        #
        # If we fail then we leave it unchanged for next time.
        $data = $this->getData();
        $rc = TRUE;

        if ($data) {
            $rc = FALSE;

            try {
                $blobRestProxy = ServicesBuilder::getInstance()->createBlobService(AZURE_CONNECTION_STRING);
                $options = new CreateBlobOptions();
                $options->setContentType("image/jpeg");

                $tname = 'timg';
                $name = 'img';

                # Upload the thumbnail.  If this fails we'll leave it untouched.
                $i = new Image($data);
                if ($i->img) {
                    $i->scale(250, 250);
                    $thumbdata = $i->getData(100);
                    $blobRestProxy->createBlockBlob("images", "{$tname}_{$this->id}.jpg", $thumbdata, $options);

                    # Upload the full size image.
                    $blobRestProxy->createBlockBlob("images", "{$name}_{$this->id}.jpg", $data, $options);

                    $rc = TRUE;
                } else {
                    error_log("...failed to create image {$this->id}");
                }

            } catch (Exception $e) { error_log("Archive failed " . $e->getMessage()); }
        }

        if ($rc) {
            # Remove from the DB.
            $sql = "UPDATE signs SET archived = 1, data = NULL WHERE id = {$this->id};";
            $this->dbhm->exec($sql);
        }

        return($rc);
    }

    public function setData($data) {
        $this->dbhm->preExec("UPDATE signs SET archived = 0, data = ? WHERE id = ?;", [
            $data,
            $this->id
        ]);
    }

    public function getData() {
        $ret = NULL;

        # Use dbhm to bypass query cache as this data is too large to cache.
        $sql = "SELECT * FROM signs WHERE id = ?;";
        $datas = $this->dbhm->preQuery($sql, [$this->id]);
        foreach ($datas as $data) {
            if ($data['archived']) {
                # This attachment has been archived out of our database, to a CDN.  Normally we would expect
                # that we wouldn't come through here, because we'd serve up an image link directly to the CDN, but
                # there is a timing window where we could archive after we've served up a link, so we have
                # to handle it.
                #
                # We fetch the data - not using SSL as we don't need to, and that host might not have a cert.  And
                # we put it back in the DB, because we are probably going to fetch it again.
                # Only these types are in archive_attachments.
                $tname = 'timg';
                $name = 'img';

                $url = 'https://' . IMAGE_ARCHIVED_DOMAIN . "/{$name}_{$this->id}.jpg";

                # Apply a short timeout to avoid hanging the server if Azure is down.
                $ctx = stream_context_create(array('http'=>
                    array(
                        'timeout' => 2,
                    )
                ));

                $ret = @file_get_contents($url, false, $ctx);
            } else {
                $ret = $data['data'];
            }
        }

        return($ret);
    }

    public function setPrivate($att, $val) {
        $this->dbhm->preExec("UPDATE signs SET `$att` = ? WHERE id = {$this->id};", [$val]);
    }

    public function delete() {
        $this->dbhm->preExec("DELETE FROM signs WHERE id = {$this->id};");
    }

    public function search($swlat, $swlng, $nelat, $nelng) {
        $poly = "POLYGON(($swlng $swlat, $swlng $nelat, $nelng $nelat, $nelng $swlat, $swlng $swlat))";

        # Get all the atts to reduce queries.
        $sql = "SELECT " . implode(',', $this->publicatts) . " FROM signs WHERE MBRWithin(latlng, GeomFromText('$poly')) ORDER BY id;";
        $signs = $this->dbhr->preQuery($sql);

        $ret = [];
        $users = [];

        foreach ($signs as $sign) {
            $sign['path'] = $this->getPath(FALSE, $sign['id']);
            $sign['paththumb'] = $this->getPath(TRUE, $sign['id']);
            $sign['added'] = ISODate($sign['added']);

            if (pres('userid', $sign) && !array_key_exists($sign['userid'], $users)) {
                $u = new User($this->dbhr, $this->dbhm, $sign['userid']);
                $uatts = $u->getPublic();
                $users[$sign['userid']] = $uatts;
            }

            $ret[] = $sign;
        }

        return([ $users, $ret ]);
    }
}