<?php

require_once(BASE . '/include/utils.php');
require_once(BASE . '/include/misc/Entity.php');
require_once(BASE . '/include/misc/Image.php');

class User extends Entity {
    public $publicatts = [ 'id', 'firstname', 'lastname', 'fullname', 'displayname', 'added', 'systemrole', 'lastaccess', 'settings' ];

    const TYPE_SIGN = 'Sign';

    public function getPublic() {
        $ret = parent::getPublic();

        $ret['added'] = ISODate($ret['added']);
        $ret['lastaccess'] = ISODate($ret['lastaccess']);

        $ret['facebook'] = $this->getFacebook();

        return($ret);
    }

    function __construct(LoggedPDO $dbhr, LoggedPDO $dbhm, $id = NULL) {
        $this->fetch($dbhr, $dbhm, $id, 'users', 'user', $this->publicatts);
    }

    public function create($firstname, $lastname, $fullname) {
        $rc = $this->dbhm->preExec("INSERT INTO users (`firstname`, `lastname`, `fullname`) VALUES (?, ?, ?);", [
            $firstname,
            $lastname,
            $fullname
        ]);

        $id = $rc ? $this->dbhm->lastInsertId() : NULL;

        if ($id) {
            $this->id = $id;
            $this->fetch($this->dbhr, $this->dbhm, $id, 'users', 'user', $this->publicatts);
        }

        return($id);
    }

    public function getEmails($recent = FALSE) {
        # Don't return canon - don't need it on the client.
        $ordq = $recent ? 'id' : 'preferred';

        $sql = "SELECT id, userid, email, preferred, added, validated FROM users_emails WHERE userid = ? ORDER BY $ordq DESC, email ASC;";
        $emails = $this->dbhr->preQuery($sql, [$this->id]);

        return($emails);
    }

    public function getEmailPreferred() {
        # This gets the email address which we think the user actually uses.  So we pay attention to:
        # - the preferred flag, which gets set by end user action
        # - the date added, as most recently added emails are most likely to be right
        # - exclude our own invented mails
        # - exclude any yahoo groups mails which have snuck in.
        $emails = $this->getEmails();
        $ret = NULL;

        foreach ($emails as $email) {
            $ret = $email['email'];
            break;
        }

        return($ret);
    }

    public function findByEmail($email) {
        # Take care not to pick up empty or null else that will cause is to overmerge.
        #
        # Use canon to match - that handles variant TN addresses or % addressing.
        $users = $this->dbhr->preQuery("SELECT userid FROM users_emails WHERE (canon = ? OR canon = ?) AND canon IS NOT NULL AND LENGTH(canon) > 0;",
            [
                User::canonMail($email),
                User::canonMail($email, TRUE)
            ]);

        foreach ($users as $user) {
            return($user['userid']);
        }

        return(NULL);
    }

    public function addEmail($email, $primary = 1, $changeprimary = TRUE)
    {
        # If the email already exists in the table, then that's fine.  But we don't want to use INSERT IGNORE as
        # that scales badly for clusters.
        $canon = User::canonMail($email);

        # Don't cache - lots of emails so don't want to flood the query cache.
        $sql = "SELECT SQL_NO_CACHE id, preferred FROM users_emails WHERE userid = ? AND email = ?;";
        $emails = $this->dbhm->preQuery($sql, [
            $this->id,
            $email
        ]);

        if (count($emails) == 0) {
            $sql = "INSERT IGNORE INTO users_emails (userid, email, preferred, canon, backwards) VALUES (?, ?, ?, ?, ?)";
            $rc = $this->dbhm->preExec($sql,
                [$this->id, $email, $primary, $canon, strrev($canon)]);
            $rc = $this->dbhm->lastInsertId();

            if ($rc && $primary) {
                # Make sure no other email is flagged as primary
                $this->dbhm->preExec("UPDATE users_emails SET preferred = 0 WHERE userid = ? AND id != ?;", [
                    $this->id,
                    $rc
                ]);
            }
        } else {
            $rc = $emails[0]['id'];

            if ($changeprimary && $primary != $emails[0]['preferred']) {
                # Change in status.
                $this->dbhm->preExec("UPDATE users_emails SET preferred = ? WHERE id = ?;", [
                    $primary,
                    $rc
                ]);
            }

            if ($primary) {
                # Make sure no other email is flagged as primary
                $this->dbhm->preExec("UPDATE users_emails SET preferred = 0 WHERE userid = ? AND id != ?;", [
                    $this->id,
                    $rc
                ]);
            }
        }

        return($rc);
    }

    public static function canonMail($email) {
        # Googlemail is Gmail really in US and UK.
        $email = str_replace('@googlemail.', '@gmail.', $email);
        $email = str_replace('@googlemail.co.uk', '@gmail.co.uk', $email);

        # Canonicalise TN addresses.
        if (preg_match('/(.*)\-(.*)(@user.trashnothing.com)/', $email, $matches)) {
            $email = $matches[1] . $matches[3];
        }

        # Remove plus addressing, which is sometimes used by spammers as a trick, except for Facebook where it
        # appears to be genuinely used for routing to distinct users.
        if (preg_match('/(.*)\+(.*)(@.*)/', $email, $matches) && strpos($email, '@proxymail.facebook.com') === FALSE) {
            $email = $matches[1] . $matches[3];
        }

        # Remove dots in LHS, which are ignored by gmail and can therefore be used to give the appearance of separate
        # emails.
        $p = strpos($email, '@');

        if ($p !== FALSE) {
            $lhs = substr($email, 0, $p);
            $rhs = substr($email, $p);

            if (stripos($rhs, '@gmail') !== FALSE || stripos($rhs, '@googlemail') !== FALSE) {
                $lhs = str_replace('.', '', $lhs);
            }

            # Remove dots from the RHS - saves a little space and is the format we have historically used.
            # Very unlikely to introduce ambiguity.
            $email = $lhs . str_replace('.', '', $rhs);
        }

        return($email);
    }

    public function removeEmail($email)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_emails WHERE userid = ? AND email = ?;",
            [$this->id, $email]);
        return($rc);
    }

    public function addFacebook($facebookid, $token) {
        $rc = $this->dbhm->preExec("REPLACE INTO users_facebook (userid, facebookid, token) VALUES (?, ?, ?);", [
            $this->id,
            $facebookid,
            $token
        ]);

        $id = $rc ? $this->dbhm->lastInsertId() : NULL;
    }

    public function removeFacebook($facebookid)
    {
        $rc = $this->dbhm->preExec("DELETE FROM users_facebook WHERE userid = ? AND facebookid = ?;",
            [$this->id, $facebookid]);
        return($rc);
    }

    public function getFacebook($token = FALSE) {
        $users = $this->dbhr->preQuery("SELECT * FROM users_facebook WHERE userid = ?;", [
            $this->id
        ]);

        $ret = NULL;

        if (count($users) > 0) {
            $ret = $users[0];
            if (!$token) {
                unset($ret['token']);
            }
        }

        return(count($ret) > 0 ? $ret : NULL);
    }

    public function findByFacebook($facebookid) {
        $users = $this->dbhr->preQuery("SELECT userid FROM users_facebook WHERE facebookid = ?;",
            [
                $facebookid
            ]);

        foreach ($users as $user) {
            return($user['userid']);
        }

        return(NULL);
    }

    public function delete() {
        $this->dbhm->preExec("DELETE FROM users WHERE id = {$this->id};");
    }
}