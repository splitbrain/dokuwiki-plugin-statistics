<?php

require dirname(__FILE__).'/StatisticsBrowscap.class.php';

class StatisticsLogger {
    private $hlp;

    private $ua_agent;
    private $ua_type;
    private $ua_name;
    private $ua_version;
    private $ua_platform;

    private $uid;

    /**
     * Parses browser info and set internal vars
     */
    public function __construct($hlp){
        $this->hlp = $hlp;

        $this->ua_agent = trim($_SERVER['HTTP_USER_AGENT']);
        $bc = new StatisticsBrowscap();
        $ua = $bc->getBrowser($this->ua_agent);
        $this->ua_name = $ua->Browser;
        $this->ua_type = 'browser';
        if($ua->Crawler) $this->ua_type = 'robot';
        if($ua->isSyndicationReader) $this->ua_type = 'feedreader';
        $this->ua_version  = $ua->Version;
        $this->ua_platform = $ua->Platform;

        $this->uid = $this->getUID();
    }

    /**
     * get the unique user ID
     */
    private function getUID(){
        $uid = $_REQUEST['uid'];
        if(!$uid) $uid = get_doku_pref('plgstats',false);
        if(!$uid) $uid = session_id();
        return $uid;
    }

    /**
     * Log external search queries
     *
     * Will not write anything if the referer isn't a search engine
     */
    public function log_externalsearch($referer,&$type){
        $referer = utf8_strtolower($referer);
        include(dirname(__FILE__).'/searchengines.php');

        $query = '';
        $name  = '';

        // parse the referer
        $urlparts = parse_url($referer);
        $domain   = $urlparts['host'];
        $qpart    = $urlparts['query'];
        if(!$qpart) $qpart = $urlparts['fragment']; //google does this

        $params = array();
        parse_str($qpart, $params);

        // check domain against common search engines
        foreach($SEARCHENGINES as $regex => $info){
            if(preg_match('/'.$regex.'/',$domain)){
                $type   = 'search';
                $name   = array_shift($info);
                // check the known parameters for content
                foreach($info as $k){
                    if(empty($params[$k])) continue;
                    $query = $params[$k];
                    break;
                }
                break;
            }
        }

        // try some generic search engin parameters
        if($type != 'search') foreach(array('search','query','q','keywords','keyword') as $k){
            if(empty($params[$k])) continue;
            $query = $params[$k];
            // we seem to have found some generic search, generate name from domain
            $name = preg_replace('/(\.co)?\.([a-z]{2,5})$/','',$domain); //strip tld
            $name = explode('.',$name);
            $name = array_pop($name);
            $type = 'search';
            break;
        }

        // still no hit? return
        if($type != 'search') return;

        // clean the query
        $query = preg_replace('/^(cache|related):[^\+]+/','',$query);  // non-search queries
        $query = preg_replace('/ +/',' ',$query);                      // ws compact
        $query = trim($query);
        if(!utf8_check($query)) $query = utf8_encode($query);          // assume latin1 if not utf8

        // no query? no log
        if(!$query) return;

        // log it!
        $words = explode(' ',utf8_stripspecials($query,' ','\._\-:\*'));
        $this->log_search($_REQUEST['p'],$query,$words,$name);
    }

    /**
     * The given data to the search related tables
     */
    public function log_search($page,$query,$words,$engine){
        $page   = addslashes($page);
        $query  = addslashes($query);
        $engine = addslashes($engine);

        $sql  = "INSERT INTO ".$this->hlp->prefix."search
                    SET dt       = NOW(),
                        page     = '$page',
                        query    = '$query',
                        engine   = '$engine'";
        $id = $this->hlp->runSQL($sql);
        if(is_null($id)) return;

        foreach($words as $word){
            if(!$word) continue;
            $word = addslashes($word);
            $sql = "INSERT DELAYED INTO ".$this->hlp->prefix."searchwords
                       SET sid  = $id,
                           word = '$word'";
            $ok = $this->hlp->runSQL($sql);
        }
    }

    /**
     * Log that the session was seen
     *
     * This is used to calculate the time people spend on the whole site
     * during their session
     *
     * Viewcounts are used for bounce calculation
     *
     * @param int $addview set to 1 to count a view
     */
    public function log_session($addview=0){
        // only log browser sessions
        if($this->ua_type != 'browser') return;

        $addview = addslashes($addview);
        $session = addslashes(session_id());
        $uid     = addslashes($this->uid);
        $sql = "INSERT DELAYED INTO ".$this->hlp->prefix."session
                   SET session = '$session',
                       dt      = NOW(),
                       end     = NOW(),
                       views   = $addview,
                       uid     = '$uid'
                ON DUPLICATE KEY UPDATE
                       end     = NOW(),
                       views   = views + $addview,
                       uid     = '$uid'";
        $this->hlp->runSQL($sql);
    }

    /**
     * Resolve IP to country/city
     */
    public function log_ip($ip){
        // check if IP already known and up-to-date
        $sql = "SELECT ip
                  FROM ".$this->hlp->prefix."iplocation
                 WHERE ip ='".addslashes($ip)."'
                   AND lastupd > DATE_SUB(CURDATE(),INTERVAL 30 DAY)";
        $result = $this->hlp->runSQL($sql);
        if($result[0]['ip']) return;

        $http = new DokuHTTPClient();
        $http->timeout = 10;
        $data = $http->get('http://api.hostip.info/get_html.php?ip='.$ip);

        if(preg_match('/^Country: (.*?) \((.*?)\)\nCity: (.*?)$/s',$data,$match)){
            $country = addslashes(ucwords(strtolower(trim($match[1]))));
            $code    = addslashes(strtolower(trim($match[2])));
            $city    = addslashes(ucwords(strtolower(trim($match[3]))));
            $host    = addslashes(gethostbyaddr($ip));
            $ip      = addslashes($ip);

            $sql = "REPLACE INTO ".$this->hlp->prefix."iplocation
                        SET ip = '$ip',
                            country = '$country',
                            code    = '$code',
                            city    = '$city',
                            host    = '$host'";
            $this->hlp->runSQL($sql);
        }
    }

    /**
     * log a click on an external link
     *
     * called from log.php
     */
    public function log_outgoing(){
        if(!$_REQUEST['ol']) return;

        $link_md5 = md5($link);
        $link     = addslashes($_REQUEST['ol']);
        $session  = addslashes(session_id());
        $page     = addslashes($_REQUEST['p']);

        $sql  = "INSERT DELAYED INTO ".$this->hlp->prefix."outlinks
                    SET dt       = NOW(),
                        session  = '$session',
                        page     = '$page',
                        link_md5 = '$link_md5',
                        link     = '$link'";
        $ok = $this->hlp->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }
    }

    /**
     * log a page access
     *
     * called from log.php
     */
    public function log_access(){
        if(!$_REQUEST['p']) return;

        # FIXME check referer against blacklist and drop logging for bad boys

        // handle referer
        $referer = trim($_REQUEST['r']);
        if($referer){
            $ref     = addslashes($referer);
            $ref_md5 = ($ref) ? md5($referer) : '';
            if(strpos($referer,DOKU_URL) === 0){
                $ref_type = 'internal';
            }else{
                $ref_type = 'external';
                $this->log_externalsearch($referer,$ref_type);
            }
        }else{
            $ref      = '';
            $ref_md5  = '';
            $ref_type = '';
        }

        // handle user agent

        $ua      = addslashes($this->ua_agent);
        $ua_type = addslashes($this->ua_type);
        $ua_ver  = addslashes($this->ua_version);
        $os      = addslashes($this->ua_platform);
        $ua_info = addslashes($this->ua_name);

        $page    = addslashes($_REQUEST['p']);
        $ip      = addslashes(clientIP(true));
        $sx      = (int) $_REQUEST['sx'];
        $sy      = (int) $_REQUEST['sy'];
        $vx      = (int) $_REQUEST['vx'];
        $vy      = (int) $_REQUEST['vy'];
        $js      = (int) $_REQUEST['js'];
        $uid     = addslashes($this->uid);
        $user    = addslashes($_SERVER['REMOTE_USER']);
        $session = addslashes(session_id());

        $sql  = "INSERT DELAYED INTO ".$this->hlp->prefix."access
                    SET dt       = NOW(),
                        page     = '$page',
                        ip       = '$ip',
                        ua       = '$ua',
                        ua_info  = '$ua_info',
                        ua_type  = '$ua_type',
                        ua_ver   = '$ua_ver',
                        os       = '$os',
                        ref      = '$ref',
                        ref_md5  = '$ref_md5',
                        ref_type = '$ref_type',
                        screen_x = '$sx',
                        screen_y = '$sy',
                        view_x   = '$vx',
                        view_y   = '$vy',
                        js       = '$js',
                        user     = '$user',
                        session  = '$session',
                        uid      = '$uid'";
        $ok = $this->hlp->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }

        $sql = "INSERT DELAYED IGNORE INTO ".$this->hlp->prefix."refseen
                   SET ref_md5  = '$ref_md5',
                       dt       = NOW()";
        $ok = $this->hlp->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }

        // resolve the IP
        $this->log_ip(clientIP(true));
    }

    /**
     * Log edits
     */
    public function log_edit($page, $type){
        $ip      = addslashes(clientIP(true));
        $user    = addslashes($_SERVER['REMOTE_USER']);
        $session = addslashes(session_id());
        $uid     = addslashes($this->uid);
        $page    = addslashes($page);
        $type    = addslashes($type);

        $sql  = "INSERT DELAYED INTO ".$this->hlp->prefix."edits
                    SET dt       = NOW(),
                        page     = '$page',
                        type     = '$type',
                        ip       = '$ip',
                        user     = '$user',
                        session  = '$session',
                        uid      = '$uid'";
        $this->hlp->runSQL($sql);
    }

    /**
     * Log login/logoffs and user creations
     */
    public function log_login($type, $user=''){
        if(!$user) $user = $_SERVER['REMOTE_USER'];

        $ip      = addslashes(clientIP(true));
        $user    = addslashes($user);
        $session = addslashes(session_id());
        $uid     = addslashes($this->uid);
        $type    = addslashes($type);

        $sql  = "INSERT DELAYED INTO ".$this->hlp->prefix."logins
                    SET dt       = NOW(),
                        type     = '$type',
                        ip       = '$ip',
                        user     = '$user',
                        session  = '$session',
                        uid      = '$uid'";
        $this->hlp->runSQL($sql);
    }

}
