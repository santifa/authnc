<?php
/**
 * DokuWiki Plugin authnc (Auth Component)
 *
 * The commented functions are kept fore reference or later implementation.
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Henrik Jürges <h.juerges@cobios.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class auth_plugin_authnc extends DokuWiki_Auth_Plugin
{

    protected $con = NULL;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(); // for compatibility
        global $config_cascade;
        global $config;

        $this->cando['addUser']     = false; // can Users be created?
        $this->cando['delUser']     = false; // can Users be deleted?
        $this->cando['modLogin']    = false; // can login names be changed?
        $this->cando['modPass']     = false; // can passwords be changed?
        $this->cando['modName']     = false; // can real names be changed?
        $this->cando['modMail']     = false; // can emails be changed?
        $this->cando['modGroups']   = false; // can groups be changed?
        $this->cando['getUsers']    = true; // can a (filtered) list of users be retrieved?
        $this->cando['getUserCount']= true; // can the number of users be retrieved?
        $this->cando['getGroups']   = true; // can a list of available groups be retrieved?
        $this->cando['external']    = true; // does the module do external auth checking?
        $this->cando['logout']      = true; // can the user logout again? (eg. not possible with HTTP auth)

        if (!function_exists('curl_init') || ! $this->server_online()) {
            $this->success = false;
        }
        $this->success = true;
    }





    /**
     * Log off the current user [ OPTIONAL ]
     */
    public function logOff()
    {
        // return nothing to log out
    }

    /**
     * Do all authentication [ OPTIONAL ]
     *
     * @param   string $user   Username
     * @param   string $pass   Cleartext Password
     * @param   bool   $sticky Cookie should not expire
     *
     * @return  bool             true on successful auth
     */
    public function trustExternal($user, $pass, $sticky = false)
    {
        global $USERINFO;
        global $conf;
        $sticky ? $sticky = true : $sticky = false; //sanity check

        // check only if a user tries to log in, otherwise the function is called with every pageload
        if (!empty($user)) {
            // try the login
            $server = $this->con . 'users/' . $user;
            $xml = $this->nc_request($server, $user, $pass);
            $logged_in = false;
            if ($xml && $xml->meta->status == "ok") {
                // hurray, we're succeded
                $logged_in = true;
            } else {
                $msg = $xml ? " with error " . $xml->meta->message : " connection error";
                msg("Failed to log in " . $msg);
            }

            // we've got valid xml and the user is not disabled in the nc
            if ($logged_in && $xml->data->enabled == '1') {
                $groups = array();
                foreach ($xml->data->groups->element as $grp) {
                    $groups[] = (string)$grp;
                }
                //msg($groups);
                // set the globals if authed
                $USERINFO['name'] = (string)$xml->data->displayname;
                $USERINFO['mail'] = (string)$xml->data->email;
                $USERINFO['grps'] = $groups;
                $_SERVER['REMOTE_USER'] = $user;
                $_SESSION[DOKU_COOKIE]['auth']['user'] = $user;
                $_SESSION[DOKU_COOKIE]['auth']['pass'] = $pass;
                $_SESSION[DOKU_COOKIE]['auth']['info'] = $USERINFO;
            }
            return $logged_in;
        }

        // check if already logged in
        if (!empty($_SESSION[DOKU_COOKIE]['auth']['info'])) {
            $USERINFO['name'] = $_SESSION[DOKU_COOKIE]['auth']['info']['name'];
            $USERINFO['mail'] = $_SESSION[DOKU_COOKIE]['auth']['info']['mail'];
            $USERINFO['grps'] = $_SESSION[DOKU_COOKIE]['auth']['info']['grps'];
            $_SERVER['REMOTE_USER'] = $_SESSION[DOKU_COOKIE]['auth']['user'];
            return true;
        }
    }

    /**
     * Check user+password
     *
     * May be ommited if trustExternal is used.
     *
     * @param   string $user the user name
     * @param   string $pass the clear text password
     *
     * @return  bool
     */
    public function checkPass($user, $pass)
    {
        return false;
    }

    /**
     * Return user info
     *
     * Returns info about the given user needs to contain
     * at least these fields:
     *
     * name string  full name of the user
     * mail string  email addres of the user
     * grps array   list of groups the user is in
     *
     * @param   string $user          the user name
     * @param   bool   $requireGroups whether or not the returned data must include groups
     *
     * @return  array  containing user data or false
     */
    public function getUserData($user, $requireGroups=true)
    {
        global $USERINFO;
        $self['user'] = $_SESSION[DOKU_COOKIE]['auth']['user'];
        $self['name'] = $USERINFO['name'];
        $self['mail'] = $USERINFO['mail'];
        $self['grps'] = $USERINFO['grps'];
        return $self;
    }

    /**
     * Create a new User [implement only where required/possible]
     *
     * Returns false if the user already exists, null when an error
     * occurred and true if everything went well.
     *
     * The new user HAS TO be added to the default group by this
     * function!
     *
     * Set addUser capability when implemented
     *
     * @param  string     $user
     * @param  string     $pass
     * @param  string     $name
     * @param  string     $mail
     * @param  null|array $grps
     *
     * @return bool|null
     */
    //public function createUser($user, $pass, $name, $mail, $grps = null)
    //{
        // FIXME implement
    //    return null;
    //}

    /**
     * Modify user data [implement only where required/possible]
     *
     * Set the mod* capabilities according to the implemented features
     *
     * @param   string $user    nick of the user to be changed
     * @param   array  $changes array of field/value pairs to be changed (password will be clear text)
     *
     * @return  bool
     */
    //public function modifyUser($user, $changes)
    //{
        // FIXME implement
    //    return false;
    //}

    /**
     * Delete one or more users [implement only where required/possible]
     *
     * Set delUser capability when implemented
     *
     * @param   array  $users
     *
     * @return  int    number of users deleted
     */
    //public function deleteUsers($users)
    //{
        // FIXME implement
    //    return false;
    //}

    /**
     * Bulk retrieval of user data [implement only where required/possible]
     *
     * Set getUsers capability when implemented
     *
     * @param   int   $start  index of first user to be returned
     * @param   int   $limit  max number of users to be returned, 0 for unlimited
     * @param   array $filter array of field/pattern pairs, null for no filter
     *
     * @return  array list of userinfo (refer getUserData for internal userinfo details)
     */
    public function retrieveUsers($start = 0, $limit = 0, $filter = null)
    {
        global $USERINFO;
        $server = $this->con . 'users';
        $xml = $this->nc_request($server, $_SESSION[DOKU_COOKIE]['auth']['user'], $_SESSION[DOKU_COOKIE]['auth']['pass']);
        if (! $xml || ! $xml->data->users) {
            msg("Retrieving user list failed");
            return array();
        }

        $users = array();
        $self['user'] = $_SESSION[DOKU_COOKIE]['auth']['user'];
        $self['name'] = $USERINFO['name'];
        $self['mail'] = $USERINFO['mail'];
        $self['grps'] = $USERINFO['grps'];
        $users[] = $self;
        foreach($xml->data->users->element as $user) {
            // Request the user information for every user, this may take a while

            $server = $this->con . 'users/' . (string)$user;
            $xml = $this->nc_request($server, $_SESSION[DOKU_COOKIE]['auth']['user'], $_SESSION[DOKU_COOKIE]['auth']['pass']);
            if ($xml && $xml->meta->status == "ok" && $xml->data->enabled == '1') {
                $usr['user'] = (string)$user;
                $usr['name'] = (string)$xml->data->displayname;
                $usr['mail'] = (string)$xml->data->email;
                $groups = array();
                foreach ($xml->data->groups->element as $grp) {
                    $groups[] = (string)$grp;
                }
                 $usr['grps'] = $groups;
                 $users[] = $usr;  
            }
        }
        return $users;
    }

    /**
     * Return a count of the number of user which meet $filter criteria
     * [should be implemented whenever retrieveUsers is implemented]
     *
     * Set getUserCount capability when implemented
     *
     * @param  array $filter array of field/pattern pairs, empty array for no filter
     *
     * @return int
     */
    public function getUserCount($filter = array())
    {
        $server = $this->con . 'users';
        $xml = $this->nc_request($server, $_SESSION[DOKU_COOKIE]['auth']['user'], $_SESSION[DOKU_COOKIE]['auth']['pass']);
        if (! $xml || ! $xml->data->users) {
            msg("Retrieving user count failed");
            return 0;
        }
        return count($xml->data->users->element);
    }

    /**
     * Define a group [implement only where required/possible]
     *
     * Set addGroup capability when implemented
     *
     * @param   string $group
     *
     * @return  bool
     */
    //public function addGroup($group)
    //{
        // FIXME implement
    //    return false;
    //}

    /**
     * Retrieve groups [implement only where required/possible]
     *
     * Set getGroups capability when implemented
     *
     * @param   int $start
     * @param   int $limit
     *
     * @return  array
     */
    public function retrieveGroups($start = 0, $limit = 0)
    {
        $server = $this->con . 'groups';
        $xml = $this->nc_request($server, $_SESSION[DOKU_COOKIE]['auth']['user'], $_SESSION[DOKU_COOKIE]['auth']['pass']);
        if (! $xml || ! $xml->data->groups) {
            msg("Retrieving groups failed");
            return array();
        }
        $groups = array();
        foreach ($xml->data->groups->element as $grp) {
            msg((string) $grp);
            $groups[(string)$grp] = (string)$grp;
        }
        msg($groups);
        return $groups;
    }

    /**
     * Return case sensitivity of the backend
     *
     * When your backend is caseinsensitive (eg. you can login with USER and
     * user) then you need to overwrite this method and return false
     *
     * @return bool
     */
    public function isCaseSensitive()
    {
        return true;
    }

    /**
     * Sanitize a given username
     *
     * This function is applied to any user name that is given to
     * the backend and should also be applied to any user name within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce username restrictions.
     *
     * @param string $user username
     * @return string the cleaned username
     */
    public function cleanUser($user)
    {
        return $user;
    }

    /**
     * Sanitize a given groupname
     *
     * This function is applied to any groupname that is given to
     * the backend and should also be applied to any groupname within
     * the backend before returning it somewhere.
     *
     * This should be used to enforce groupname restrictions.
     *
     * Groupnames are to be passed without a leading '@' here.
     *
     * @param  string $group groupname
     *
     * @return string the cleaned groupname
     */
    public function cleanGroup($group)
    {
        return $group;
    }

    /**
     * Check Session Cache validity [implement only where required/possible]
     *
     * DokuWiki caches user info in the user's session for the timespan defined
     * in $conf['auth_security_timeout'].
     *
     * This makes sure slow authentication backends do not slow down DokuWiki.
     * This also means that changes to the user database will not be reflected
     * on currently logged in users.
     *
     * To accommodate for this, the user manager plugin will touch a reference
     * file whenever a change is submitted. This function compares the filetime
     * of this reference file with the time stored in the session.
     *
     * This reference file mechanism does not reflect changes done directly in
     * the backend's database through other means than the user manager plugin.
     *
     * Fast backends might want to return always false, to force rechecks on
     * each page load. Others might want to use their own checking here. If
     * unsure, do not override.
     *
     * @param  string $user - The username
     *
     * @return bool
     */
    //public function useSessionCache($user)
    //{
      // FIXME implement
    //}

    protected function server_online() {
        if ($this->con) return true; // some link is already set
        // check if the server is reachable by opening a socket
        $host = explode(':', $this->getConf('server'));
        $fp = fSockOpen('ssl:' . $host[1], $this->getConf('port'), $errno, $errstr, 5);
        if (!$fp) return false; // server is not reachable
        $this->con = $this->getConf('server') . ':' . $this->getConf('port') . '/' . $this->getConf('ocs-path');
        return true; // no more error checking, assume reachable
    }

    /**
     * Send a request to the nextcloud instance.
     *
     * Returns the parsed xml file or NULL if
     * the request or parsing failed.
     *
     * At some point curl generates an invalid syntax 998 error
     * see https://www.freedesktop.org/wiki/Specifications/open-collaboration-services/
     * and https://help.nextcloud.com/t/api-error-creating-user-failure-998-invalid-query/56530
     *
     * @param string $url  request url, shall return xml
     * @param string $user the user name
     * @param string $pass the users password
     *
     * @return object the parsed xml or NULL
     */
    protected function nc_request($url, $user, $pass) {
        $ret = NULL;
        $ch = curl_init($url);
        $opts = array(
            CURLOPT_HTTPGET => 1, // default, but make clear
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_USERPWD => $user . ':' . $pass,
            CURLOPT_HTTPHEADER => array("OCS-APIRequest:true"),
        );
        curl_setopt_array($ch, $opts);
        if (! $result = curl_exec($ch)) {
            msg('Request failed with error ' . curl_error($ch) . '. Return code: ' . $result);
        } else {
            $ret = simplexml_load_string($result);
        }
        curl_close($ch);
        return $ret;
    }
}
