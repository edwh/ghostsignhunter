<?php

require_once(BASE . '/include/utils.php');
require_once(BASE . '/include/misc/Entity.php');
require_once(BASE . '/include/misc/Image.php');
require_once(BASE . '/include/User.php');

use Jenssegers\ImageHash\ImageHash;
use WindowsAzure\Common\ServicesBuilder;
use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;

class News extends Entity {
    public $publicatts = [ 'id', 'timestamp', 'type', 'userid', 'text', 'lat', 'lng' ];

    const TYPE_ADDED = 'Added';
    const TYPE_FOUND = 'Found';

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL) {
        $this->fetch($dbhr, $dbhm, $id, 'news', 'news', $this->publicatts);
    }

    public function create($type, $userid, $signid, $text, $lat, $lng) {
        $rc = $this->dbhm->preExec("INSERT INTO news (`type`, userid, signid, `text`, lat, lng) VALUES (?, ?, ?, ?, ?, ?);", [
            $type,
            $userid,
            $signid,
            $text,
            $lat,
            $lng
        ]);

        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        if ($id) {
            $this->id = $id;
            $this->fetch($this->dbhr, $this->dbhm, $id, 'news', 'news', $this->publicatts);
        }

        return($id);
    }

    public function delete() {
        $this->dbhm->preExec("DELETE FROM news WHERE id = {$this->id};");
    }

    public function get(&$ctx) {
        $ctx = $ctx ? $ctx : [];
        $id = intval(presdef('id', $ctx, NULL));
        $idq = $id ? "id < $id" : '';

        $news = $this->dbhr->preQuery("SELECT * FROM news $idq LIMIT 10");
        foreach ($news as &$n) {
            $n['timestamp'] = ISODATE($n['timestamp']);

            if (pres('signid', $n)) {
                $s = new Sign($this->dbhr, $this->dbhm, $n['signid']);
                $n['sign'] = $s->getPublic();
                unset($n['signid']);
            }

            if (pres('userid', $n)) {
                $u = new User($this->dbhr, $this->dbhm, $n['userid']);
                $n['user'] = $s->getPublic();
                unset($n['userid']);
            }
        }

        return($news);
    }
}