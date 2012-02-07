<?php

class StatisticsQuery {
    private $hlp;

    public function __construct($hlp){
        $this->hlp = $hlp;
    }

    /**
     * Return some aggregated statistics
     */
    public function aggregate($tlimit){
        $data = array();

        $sql = "SELECT ref_type, COUNT(*) as cnt
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY ref_type";
        $result = $this->hlp->runSQL($sql);

        if(is_array($result)) foreach($result as $row){
            if($row['ref_type'] == 'search')   $data['search']   = $row['cnt'];
            if($row['ref_type'] == 'external') $data['external'] = $row['cnt'];
            if($row['ref_type'] == 'internal') $data['internal'] = $row['cnt'];
            if($row['ref_type'] == '')         $data['direct']   = $row['cnt'];
        }

        $sql = "SELECT COUNT(DISTINCT session) as sessions,
                       COUNT(session) as views,
                       COUNT(DISTINCT user) as users,
                       COUNT(DISTINCT uid) as visitors
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'";
        $result = $this->hlp->runSQL($sql);

        $data['users']     = max($result[0]['users'] - 1,0); // subtract empty user
        $data['sessions']  = $result[0]['sessions'];
        $data['pageviews'] = $result[0]['views'];
        $data['visitors']  = $result[0]['visitors'];

        $sql = "SELECT COUNT(id) as robots
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'robot'";
        $result = $this->hlp->runSQL($sql);
        $data['robots'] = $result[0]['robots'];

        return $data;
    }

    /**
     * standard statistics follow, only accesses made by browsers are counted
     * for general stats like browser or OS only visitors not pageviews are counted
     */
    public function trend($tlimit,$hours=false){
        if($hours){
            $sql = "SELECT HOUR(dt) as time,
                           COUNT(DISTINCT session) as sessions,
                           COUNT(session) as pageviews,
                           COUNT(DISTINCT uid) as visitors
                      FROM ".$this->hlp->prefix."access as A
                     WHERE $tlimit
                       AND ua_type = 'browser'
                  GROUP BY HOUR(dt)
                  ORDER BY time";
        }else{
            $sql = "SELECT DATE(dt) as time,
                           COUNT(DISTINCT session) as sessions,
                           COUNT(session) as pageviews,
                            COUNT(DISTINCT uid) as visitors
                      FROM ".$this->hlp->prefix."access as A
                     WHERE $tlimit
                       AND ua_type = 'browser'
                  GROUP BY DATE(dt)
                  ORDER BY time";
        }
        return $this->hlp->runSQL($sql);
    }

    public function searchengines($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, engine
                  FROM ".$this->hlp->prefix."search as A
                 WHERE $tlimit
              GROUP BY engine
              ORDER BY cnt DESC, engine".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function searchphrases($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, query, query as lookup
                  FROM ".$this->hlp->prefix."search as A
                 WHERE $tlimit
              GROUP BY query
              ORDER BY cnt DESC, query".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function searchwords($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, word, word as lookup
                  FROM ".$this->hlp->prefix."search as A,
                       ".$this->hlp->prefix."searchwords as B
                 WHERE $tlimit
                   AND A.id = B.sid
              GROUP BY word
              ORDER BY cnt DESC, word".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function outlinks($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, link as url
                  FROM ".$this->hlp->prefix."outlinks as A
                 WHERE $tlimit
              GROUP BY link
              ORDER BY cnt DESC, link".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function pages($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, page
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY page
              ORDER BY cnt DESC, page".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function referer($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, ref as url
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
                   AND ref_type = 'external'
              GROUP BY ref_md5
              ORDER BY cnt DESC, url".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function newreferer($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, ref as url
                  FROM ".$this->hlp->prefix."access as B,
                       ".$this->hlp->prefix."refseen as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
                   AND ref_type = 'external'
                   AND A.ref_md5 = B.ref_md5
              GROUP BY A.ref_md5
              ORDER BY cnt DESC, url".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function countries($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, B.code AS cflag, B.country
                  FROM ".$this->hlp->prefix."access as A,
                       ".$this->hlp->prefix."iplocation as B
                 WHERE $tlimit
                   AND A.ip = B.ip
              GROUP BY B.country
              ORDER BY cnt DESC, B.country".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function browsers($tlimit,$start=0,$limit=20,$ext=true){
        if($ext){
            $sel = 'ua_info as bflag, ua_info as browser, ua_ver';
            $grp = 'ua_info, ua_ver';
        }else{
            $grp = 'ua_info';
            $sel = 'ua_info';
        }

        $sql = "SELECT COUNT(DISTINCT session) as cnt, $sel
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY $grp
              ORDER BY cnt DESC, ua_info".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function os($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, os as osflag, os
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY os
              ORDER BY cnt DESC, os".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function resolution($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, CONCAT(screen_x,'x',screen_y) as res
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type  = 'browser'
                   AND screen_x != 0
              GROUP BY screen_x, screen_y
              ORDER BY cnt DESC, screen_x".
              $this->mklimit($start,$limit);
        return $this->hlp->runSQL($sql);
    }

    public function viewport($tlimit,$start=0,$limit=20,$x=true){
        if($x){
            $col = 'view_x';
            $res = 'res_x';
        }else{
            $col = 'view_y';
            $res = 'res_y';
        }

        $sql = "SELECT COUNT(*) as cnt,
                       ROUND(view_x/100)*100 as res_x,
                       ROUND(view_y/100)*100 as res_y
                  FROM ".$this->hlp->prefix."access as A
                 WHERE $tlimit
                   AND ua_type  = 'browser'
                   AND view_x != 0
                   AND view_y != 0
              GROUP BY res_x, res_y
              ORDER BY cnt".
              $this->mklimit($start,$limit);

        return $this->hlp->runSQL($sql);
    }


    /**
     * Builds a limit clause
     */
    private function mklimit($start,$limit){
        $start = (int) $start;
        $limit = (int) $limit;
        if($limit){
            $limit += 1;
            return " LIMIT $start,$limit";
        }elseif($start){
            return " OFFSET $start";
        }
        return '';
    }


}
