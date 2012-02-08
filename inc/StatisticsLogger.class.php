<?php

require dirname(__FILE__).'/StatisticsBrowscap.class.php';

class StatisticsLogger {
    private $hlp;

    public function __construct($hlp){
        $this->hlp = $hlp;
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
        $referer = strtolower($referer);
        $ref     = strtr($referer,' +','__');

        include(dirname(__FILE__).'/inc/search_engines.php');

        foreach($SearchEnginesSearchIDOrder as $regex){
            if(preg_match('/'.$regex.'/',$ref)){
                if(!$NotSearchEnginesKeys[$regex] ||
                   !preg_match('/'.$NotSearchEnginesKeys[$regex].'/',$ref)){
                    // it's a search engine!
                    $type = 'search';
                    break;
                }
            }
        }
        if($type != 'search') return; // we're done here

        // extract query
        $engine = $SearchEnginesHashID[$regex];
        $param = $SearchEnginesKnownUrl[$engine];
        if($param && preg_match('/'.$param.'(.*?)[&$]/',$referer,$match)){
            $query = array_pop($match);
        }elseif(preg_match('/'.$WordsToExtractSearchUrl.'(.*?)[&$]/',$referer,$match)){
            $query = array_pop($match);
        }
        if(!$query) return; // we failed

        // clean the query
        $query = preg_replace('/^(cache|related):[^\+]+/','',$query);  // non-search queries
        $query = preg_replace('/%0[ad]/',' ',$query);                  // LF CR
        $query = preg_replace('/%2[02789abc]/',' ',$query);            // space " ' ( ) * + ,
        $query = preg_replace('/%3a/',' ',$query);                     // :
        $query = strtr($query,'+\'()"*,:','        ');                 // badly encoded
        $query = preg_replace('/ +/',' ',$query);                      // ws compact
        $query = trim($query);
        $query = urldecode($query);
        if(!utf8_check($query)) $query = utf8_encode($query);          // assume latin1 if not utf8
        $query = utf8_strtolower($query);

        // log it!
        $words = explode(' ',utf8_stripspecials($query,' ','\._\-:\*'));
        $this->log_search($_REQUEST['p'],$query,$words,$engine);
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
     */
    public function log_session(){
        $session = addslashes(session_id());
        $sql = "INSERT DELAYED INTO ".$this->hlp->prefix."session
                   SET session = '$session',
                       begin   = NOW(),
                       end     = NOW()
                ON DUPLICATE KEY UPDATE
                       end     = NOW()";
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
            $country = addslashes(trim($match[1]));
            $code    = addslashes(strtolower(trim($match[2])));
            $city    = addslashes(trim($match[3]));
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
        $agent   = trim($_SERVER['HTTP_USER_AGENT']);

        $ua      = addslashes($agent);
        $ua_type = '';
        $ua_ver  = '';
        $os      = '';
        $ua_info = addslashes($this->ua_info($agent,$ua_type,$ua_ver,$os));

        $page    = addslashes($_REQUEST['p']);
        $ip      = addslashes(clientIP(true));
        $sx      = (int) $_REQUEST['sx'];
        $sy      = (int) $_REQUEST['sy'];
        $vx      = (int) $_REQUEST['vx'];
        $vy      = (int) $_REQUEST['vy'];
        $js      = (int) $_REQUEST['js'];
        $uid     = addslashes($this->getUID());
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
        $uid     = addslashes($this->getUID());
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
     * Returns a short name for a User Agent and sets type, version and os info
     */
    private function ua_info($agent,&$type,&$version,&$os){
        $bc = new StatisticsBrowscap();
        $ua = $bc->getBrowser($agent);

        $type = 'browser';
        if($ua->Crawler) $type = 'robot';
        if($ua->isSyndicationReader) $type = 'feedreader';

        $version = $ua->Version;
        $os      = $ua->Platform;
        return $ua->Browser;
    }
}
