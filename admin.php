<?php
/**
 * statistics plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_statistics extends DokuWiki_Admin_Plugin {
    public    $dblink = null;
    protected $opt    = '';
    protected $from   = '';
    protected $to     = '';
    protected $start  = '';
    protected $tlimit = '';

    /**
     * Available statistic pages
     */
    protected $pages  = array('dashboard','page','referer','newreferer',
                              'outlinks','searchphrases','searchwords',
                              'searchengines','browser','os','country',
                              'resolution');

    /**
     * Access for managers allowed
     */
    function forAdminOnly(){
        return false;
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 150;
    }

    /**
     * handle user request
     */
    function handle() {
        $this->opt = preg_replace('/[^a-z]+/','',$_REQUEST['opt']);
        if(!in_array($this->opt,$this->pages)) $this->opt = 'dashboard';

        $this->start = (int) $_REQUEST['s'];
        $this->setTimeframe($_REQUEST['f'],$_REQUEST['t']);
    }

    /**
     * set limit clause
     */
    function setTimeframe($from,$to){
        // fixme add better sanity checking here:
        $from = preg_replace('/[^\d\-]+/','',$from);
        $to   = preg_replace('/[^\d\-]+/','',$to);
        if(!$from) $from = date('Y-m-d');
        if(!$to)   $to   = date('Y-m-d');

        //setup limit clause
        $tlimit = "A.dt >= '$from 00:00:00' AND A.dt <= '$to 23:59:59'";
        $this->tlimit = $tlimit;
        $this->from   = $from;
        $this->to     = $to;
    }

    /**
     * Output the Statistics
     */
    function html() {
        echo '<h1>Access Statistics</h1>';
        $this->html_timeselect();

        $method = 'html_'.$this->opt;
        if(method_exists($this,$method)){
            echo '<div class="plg_stats_'.$this->opt.'">';
            echo '<h2>'.$this->getLang($this->opt).'</h2>';
            $this->$method();
            echo '</div>';
        }
    }

    function getTOC(){
        $toc = array();
        foreach($this->pages as $page){
            $toc[] = array(
                    'link'  => '?do=admin&amp;page=statistics&amp;opt='.$page.'&amp;f='.$this->from.'&amp;t='.$this->to,
                    'title' => $this->getLang($page),
                    'level' => 1,
                    'type'  => 'ul'
            );
        }
        return $toc;
    }

    function html_pager($limit,$next){
        echo '<div class="plg_stats_pager">';

        if($this->start > 0){
            $go = max($this->start - $limit, 0);
            echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$go.'" class="prev">previous page</a>';
        }

        if($next){
            $go = $this->start + $limit;
            echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$go.'" class="next">next page</a>';
        }
        echo '</div>';
    }

    /**
     * Print the time selection menu
     */
    function html_timeselect(){
        $now   = date('Y-m-d');
        $yday  = date('Y-m-d',time()-(60*60*24));
        $week  = date('Y-m-d',time()-(60*60*24*7));
        $month = date('Y-m-d',time()-(60*60*24*30));

        echo '<div class="plg_stats_timeselect">';
        echo '<span>Select the timeframe:</span>';
        echo '<ul>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$now.'&amp;t='.$now.'">';
        echo 'today';
        echo '</a>';
        echo '</li>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$yday.'&amp;t='.$yday.'">';
        echo 'yesterday';
        echo '</a>';
        echo '</li>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$week.'&amp;t='.$now.'">';
        echo 'last 7 days';
        echo '</a>';
        echo '</li>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$month.'&amp;t='.$now.'">';
        echo 'last 30 days';
        echo '</a>';
        echo '</li>';

        echo '</ul>';


        echo '<form action="" method="get">';
        echo '<input type="hidden" name="do" value="admin" />';
        echo '<input type="hidden" name="page" value="statistics" />';
        echo '<input type="hidden" name="opt" value="'.$this->opt.'" />';
        echo '<input type="text" name="f" value="'.$this->from.'" class="edit" />';
        echo '<input type="text" name="t" value="'.$this->to.'" class="edit" />';
        echo '<input type="submit" value="go" class="button" />';
        echo '</form>';

        echo '</div>';
    }


    /**
     * Print an introductionary screen
     */
    function html_dashboard(){
        echo '<p>This page gives you a quick overview on what is happening in your Wiki. For detailed lists
              choose a topic from the list.</p>';

        // general info
        echo '<div class="plg_stats_top">';
        $result = $this->sql_aggregate($this->tlimit);
        echo '<ul>';
        echo '<li><span>'.$result['pageviews'].'</span> page views </li>';
        echo '<li><span>'.$result['sessions'].'</span> visits (sessions) </li>';
        echo '<li><span>'.$result['visitors'].'</span> unique visitors </li>';
        echo '<li><span>'.$result['users'].'</span> logged in users</li>';

        echo '</ul>';
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=trend&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        echo '</div>';


        // top pages today
        echo '<div>';
        echo '<h2>Most popular pages</h2>';
        $result = $this->sql_pages($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=page&amp;f='.$this->from.'&amp;t='.$this->to.'" class="more">more</a>';
        echo '</div>';

        // top referer today
        echo '<div>';
        echo '<h2>Newest incoming links</h2>';
        $result = $this->sql_newreferer($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=newreferer&amp;f='.$this->from.'&amp;t='.$this->to.'" class="more">more</a>';
        echo '</div>';

        // top searches today
        echo '<div>';
        echo '<h2>Top search phrases</h2>';
        $result = $this->sql_searchphrases($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=searchphrases&amp;f='.$this->from.'&amp;t='.$this->to.'" class="more">more</a>';
        echo '</div>';
    }

    function html_country(){
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=country&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        $result = $this->sql_countries($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_page(){
        $result = $this->sql_pages($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_browser(){
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=browser&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        $result = $this->sql_browsers($this->tlimit,$this->start,150,true);
        $this->html_resulttable($result,'',150);
    }

    function html_os(){
        $result = $this->sql_os($this->tlimit,$this->start,150,true);
        $this->html_resulttable($result,'',150);
    }

    function html_referer(){
        $result = $this->sql_aggregate($this->tlimit);

        $all    = $result['search']+$result['external']+$result['direct'];

        if($all){
            printf("<p>Of all %d external visits, %d (%.1f%%) were bookmarked (direct) accesses,
                    %d (%.1f%%) came from search engines and %d (%.1f%%) were referred through
                    links from other pages.</p>",$all,$result['direct'],(100*$result['direct']/$all),
                    $result['search'],(100*$result['search']/$all),$result['external'],
                    (100*$result['external']/$all));
        }

        $result = $this->sql_referer($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_newreferer(){
        echo '<p>The following incoming links where first logged in the selected time frame,
              and have never been seen before.</p>';

        $result = $this->sql_newreferer($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_outlinks(){
        $result = $this->sql_outlinks($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_searchphrases(){
        $result = $this->sql_searchphrases($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_searchwords(){
        $result = $this->sql_searchwords($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_searchengines(){
        $result = $this->sql_searchengines($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }


    function html_resolution(){
        $result = $this->sql_resolution($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);

        echo '<p>While the data above gives you some info about the resolution your visitors use, it does not tell you
              much about about the real size of their browser windows. The graphic below shows the size distribution of
              the view port (document area) of your visitor\'s browsers. Please note that this data can not be logged
              in all browsers. Because users may resize their browser window while browsing your site the statistics may
              be flawed. Take it with a grain of salt.</p>';

        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=view&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
    }


    /**
     * Display a result in a HTML table
     */
    function html_resulttable($result,$header='',$pager=0){
        echo '<table>';
        if(is_array($header)){
            echo '<tr>';
            foreach($header as $h){
                echo '<th>'.hsc($h).'</th>';
            }
            echo '</tr>';
        }

        $count = 0;
        if(is_array($result)) foreach($result as $row){
            echo '<tr>';
            foreach($row as $k => $v){
                echo '<td class="plg_stats_X'.$k.'">';
                if($k == 'page'){
                    echo '<a href="'.wl($v).'" class="wikilink1">';
                    echo hsc($v);
                    echo '</a>';
                }elseif($k == 'url'){
                    $url = hsc($v);
                    $url = preg_replace('/^https?:\/\/(www\.)?/','',$url);
                    if(strlen($url) > 45){
                        $url = substr($url,0,30).' &hellip; '.substr($url,-15);
                    }
                    echo '<a href="'.$v.'" class="urlextern">';
                    echo $url;
                    echo '</a>';
                }elseif($k == 'lookup'){
                    echo '<a href="http://www.google.com/search?q='.rawurlencode($v).'">';
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/search/google.png" alt="lookup in Google" border="0" />';
                    echo '</a> ';

                    echo '<a href="http://search.yahoo.com/search?p='.rawurlencode($v).'">';
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/search/yahoo.png" alt="lookup in Yahoo" border="0" />';
                    echo '</a> ';

                    echo '<a href="http://search.msn.com/results.aspx?q='.rawurlencode($v).'">';
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/search/msn.png" alt="lookup in MSN Live" border="0" />';
                    echo '</a> ';

                }elseif($k == 'engine'){
                    include_once(dirname(__FILE__).'/inc/search_engines.php');
                    echo $SearchEnginesHashLib[$v];
                }elseif($k == 'browser'){
                    include_once(dirname(__FILE__).'/inc/browsers.php');
                    echo $BrowsersHashIDLib[$v];
                }elseif($k == 'bflag'){
                    include_once(dirname(__FILE__).'/inc/browsers.php');
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/browser/'.$BrowsersHashIcon[$v].'.png" alt="'.hsc($v).'" />';
                }elseif($k == 'os'){
                    if(empty($v)){
                        echo 'unknown';
                    }else{
                        include_once(dirname(__FILE__).'/inc/operating_systems.php');
                        echo $OSHashLib[$v];
                    }
                }elseif($k == 'osflag'){
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/os/'.hsc($v).'.png" alt="'.hsc($v).'" />';
                }elseif($k == 'cflag'){
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/flags/'.hsc($v).'.png" alt="'.hsc($v).'" width="18" height="12" />';
                }elseif($k == 'html'){
                    echo $v;
                }else{
                    echo hsc($v);
                }
                echo '</td>';
            }
            echo '</tr>';

            if($pager && ($count == $pager)) break;
            $count++;
        }
        echo '</table>';

        if($pager) $this->html_pager($pager,count($result) > $pager);
    }

    /**
     * Create an image
     */
    function img_build($img){
        include(dirname(__FILE__).'/inc/AGC.class.php');

        switch($img){
            case 'country':
                // build top countries + other
                $result = $this->sql_countries($this->tlimit,$this->start,0);
                $data = array();
                $top = 0;
                foreach($result as $row){
                    if($top < 7){
                        $data[$row['country']] = $row['cnt'];
                    }else{
                        $data['other'] += $row['cnt'];
                    }
                    $top++;
                }
                $pie = new AGC(300, 200);
                $pie->setProp("showkey",true);
                $pie->setProp("showval",false);
                $pie->setProp("showgrid",false);
                $pie->setProp("type","pie");
                $pie->setProp("keyinfo",1);
                $pie->setProp("keysize",8);
                $pie->setProp("keywidspc",-50);
                $pie->setProp("key",array_keys($data));
                $pie->addBulkPoints(array_values($data));
                @$pie->graph();
                $pie->showGraph();
                break;
            case 'browser':
                // build top browsers + other
                include_once(dirname(__FILE__).'/inc/browsers.php');

                $result = $this->sql_browsers($this->tlimit,$this->start,0,false);
                $data = array();
                $top = 0;
                foreach($result as $row){
                    if($top < 5){
                        $data[strip_tags($BrowsersHashIDLib[$row['ua_info']])] = $row['cnt'];
                    }else{
                        $data['other'] += $row['cnt'];
                    }
                    $top++;
                }
                $pie = new AGC(300, 200);
                $pie->setProp("showkey",true);
                $pie->setProp("showval",false);
                $pie->setProp("showgrid",false);
                $pie->setProp("type","pie");
                $pie->setProp("keyinfo",1);
                $pie->setProp("keysize",8);
                $pie->setProp("keywidspc",-50);
                $pie->setProp("key",array_keys($data));
                $pie->addBulkPoints(array_values($data));
                @$pie->graph();
                $pie->showGraph();
                break;
            case 'view':

                $graph = new AGC(400, 200);
                $graph->setColor('color',0,'blue');
                $graph->setColor('color',1,'red');
                $graph->setProp("showkey",true);
                $graph->setProp("key",'view port width',0);
                $graph->setProp("key",'view port height',1);

                $result = $this->sql_viewport($this->tlimit,0,0,true);
                foreach($result as $row){
                    $graph->addPoint($row['cnt'],$row['res_x'],0);
                }

                $result = $this->sql_viewport($this->tlimit,0,0,false);
                foreach($result as $row){
                    $graph->addPoint($row['cnt'],$row['res_y'],1);
                }

                @$graph->graph();
                $graph->showGraph();

                break;
            case 'trend':
                $hours  = ($this->from == $this->to);
                $result = $this->sql_trend($this->tlimit,$hours);
                $data1   = array();
                $data2   = array();

                $graph = new AGC(400, 150);
                $graph->setProp("type","bar");
                $graph->setProp("showgrid",false);
                $graph->setProp("barwidth",.8);

                $graph->setColor('color',0,'blue');
                $graph->setColor('color',1,'red');
                $graph->setColor('color',2,'yellow');

                if($hours){
                    //preset $hours
                    for($i=0;$i<24;$i++){
                        $data1[$i] = 0;
                        $data2[$i] = 0;
                        $data3[$i] = 0;
                        $graph->setProp("scale",array(' 0h','   4h','   8h','    12h','    16h','    20h','    24h'));
                    }
                }else{
                    $graph->setProp("scale",array(next(array_keys($data1)),$this->to));
                }

                foreach($result as $row){
                    $data1[$row['time']] = $row['pageviews'];
                    $data2[$row['time']] = $row['sessions'];
                    $data3[$row['time']] = $row['visitors'];
                }

                foreach($data1 as $key => $val){
                    $graph->addPoint($val,$key,0);
                }
                foreach($data2 as $key => $val){
                    $graph->addPoint($val,$key,1);
                }
                foreach($data3 as $key => $val){
                    $graph->addPoint($val,$key,2);
                }

                @$graph->graph();
                $graph->showGraph();

            default:
                $this->sendGIF();
        }
    }


    /**
     * Return some aggregated statistics
     */
    function sql_aggregate($tlimit){
        $data = array();

        $sql = "SELECT ref_type, COUNT(*) as cnt
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY ref_type";
        $result = $this->runSQL($sql);

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
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'";
        $result = $this->runSQL($sql);

        $data['users']     = max($result[0]['users'] - 1,0); // subtract empty user
        $data['sessions']  = $result[0]['sessions'];
        $data['pageviews'] = $result[0]['views'];
        $data['visitors']  = $result[0]['visitors'];

        $sql = "SELECT COUNT(id) as robots
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'robot'";
        $result = $this->runSQL($sql);
        $data['robots'] = $result[0]['robots'];

        return $data;
    }

    /**
     * standard statistics follow, only accesses made by browsers are counted
     * for general stats like browser or OS only visitors not pageviews are counted
     */
    function sql_trend($tlimit,$hours=false){
        if($hours){
            $sql = "SELECT HOUR(dt) as time,
                           COUNT(DISTINCT session) as sessions,
                           COUNT(session) as pageviews,
                           COUNT(DISTINCT uid) as visitors
                      FROM ".$this->getConf('db_prefix')."access as A
                     WHERE $tlimit
                       AND ua_type = 'browser'
                  GROUP BY HOUR(dt)
                  ORDER BY time";
        }else{
            $sql = "SELECT DATE(dt) as time,
                           COUNT(DISTINCT session) as sessions,
                           COUNT(session) as pageviews,
                            COUNT(DISTINCT uid) as visitors
                      FROM ".$this->getConf('db_prefix')."access as A
                     WHERE $tlimit
                       AND ua_type = 'browser'
                  GROUP BY DATE(dt)
                  ORDER BY time";
        }
        return $this->runSQL($sql);
    }

    function sql_searchengines($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, engine
                  FROM ".$this->getConf('db_prefix')."search as A
                 WHERE $tlimit
              GROUP BY engine
              ORDER BY cnt DESC, engine".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_searchphrases($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, query, query as lookup
                  FROM ".$this->getConf('db_prefix')."search as A
                 WHERE $tlimit
              GROUP BY query
              ORDER BY cnt DESC, query".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_searchwords($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, word, word as lookup
                  FROM ".$this->getConf('db_prefix')."search as A,
                       ".$this->getConf('db_prefix')."searchwords as B
                 WHERE $tlimit
                   AND A.id = B.sid
              GROUP BY word
              ORDER BY cnt DESC, word".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_outlinks($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, link as url
                  FROM ".$this->getConf('db_prefix')."outlinks as A
                 WHERE $tlimit
              GROUP BY link
              ORDER BY cnt DESC, link".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_pages($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, page
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY page
              ORDER BY cnt DESC, page".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_referer($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, ref as url
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
                   AND ref_type = 'external'
              GROUP BY ref_md5
              ORDER BY cnt DESC, url".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_newreferer($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, ref as url
                  FROM ".$this->getConf('db_prefix')."access as B,
                       ".$this->getConf('db_prefix')."refseen as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
                   AND ref_type = 'external'
                   AND A.ref_md5 = B.ref_md5
              GROUP BY A.ref_md5
              ORDER BY cnt DESC, url".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_countries($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, B.code AS cflag, B.country
                  FROM ".$this->getConf('db_prefix')."access as A,
                       ".$this->getConf('db_prefix')."iplocation as B
                 WHERE $tlimit
                   AND A.ip = B.ip
              GROUP BY B.country
              ORDER BY cnt DESC, B.country".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_browsers($tlimit,$start=0,$limit=20,$ext=true){
        if($ext){
            $sel = 'ua_info as bflag, ua_info as browser, ua_ver';
            $grp = 'ua_info, ua_ver';
        }else{
            $grp = 'ua_info';
            $sel = 'ua_info';
        }

        $sql = "SELECT COUNT(DISTINCT session) as cnt, $sel
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY $grp
              ORDER BY cnt DESC, ua_info".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_os($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, os as osflag, os
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY os
              ORDER BY cnt DESC, os".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_resolution($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, CONCAT(screen_x,'x',screen_y) as res
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type  = 'browser'
                   AND screen_x != 0
              GROUP BY screen_x, screen_y
              ORDER BY cnt DESC, screen_x".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_viewport($tlimit,$start=0,$limit=20,$x=true){
        if($x){
            $col = 'view_x';
            $res = 'res_x';
        }else{
            $col = 'view_y';
            $res = 'res_y';
        }

        $sql = "SELECT COUNT(*) as cnt,
                       ROUND($col/10)*10 as $res
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type  = 'browser'
                   AND $col != 0
              GROUP BY $res
              ORDER BY cnt DESC, $res".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }


    /**
     * Builds a limit clause
     */
    function sql_limit($start,$limit){
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

    /**
     * Return a link to the DB, opening the connection if needed
     */
    function dbLink(){
        // connect to DB if needed
        if(!$this->dblink){
            $this->dblink = mysql_connect($this->getConf('db_server'),
                                          $this->getConf('db_user'),
                                          $this->getConf('db_password'));
            if(!$this->dblink){
                msg('DB Error: connection failed',-1);
                return null;
            }
            // set utf-8
            if(!mysql_db_query($this->getConf('db_database'),'set names utf8',$this->dblink)){
                msg('DB Error: could not set UTF-8 ('.mysql_error($this->dblink).')',-1);
                return null;
            }
        }
        return $this->dblink;
    }

    /**
     * Simple function to run a DB query
     */
    function runSQL($sql_string) {
        $link = $this->dbLink();

        $result = mysql_db_query($this->conf['db_database'],$sql_string,$link);
        if(!$result){
            msg('DB Error: '.mysql_error($link).' '.hsc($sql_string),-1);
            return null;
        }

        $resultarray = array();

        //mysql_db_query returns 1 on a insert statement -> no need to ask for results
        if ($result != 1) {
            for($i=0; $i< mysql_num_rows($result); $i++) {
                $temparray = mysql_fetch_assoc($result);
                $resultarray[]=$temparray;
            }
            mysql_free_result($result);
        }

        if (mysql_insert_id($link)) {
            $resultarray = mysql_insert_id($link); //give back ID on insert
        }

        return $resultarray;
    }

    /**
     * Returns a short name for a User Agent and sets type, version and os info
     */
    function ua_info($ua,&$type,&$ver,&$os){
        $ua = strtr($ua,' +','__');
        $ua = strtolower($ua);

        // common browsers
        $regvermsie     = '/msie([+_ ]|)([\d\.]*)/i';
        $regvernetscape = '/netscape.?\/([\d\.]*)/i';
        $regverfirefox  = '/firefox\/([\d\.]*)/i';
        $regversvn      = '/svn\/([\d\.]*)/i';
        $regvermozilla  = '/mozilla(\/|)([\d\.]*)/i';
        $regnotie       = '/webtv|omniweb|opera/i';
        $regnotnetscape = '/gecko|compatible|opera|galeon|safari/i';

        $name = '';
        # IE ?
        if(preg_match($regvermsie,$ua,$m) && !preg_match($regnotie,$ua)){
            $type = 'browser';
            $ver  = $m[2];
            $name = 'msie';
        }
        # Firefox ?
        elseif (preg_match($regverfirefox,$ua,$m)){
            $type = 'browser';
            $ver  = $m[1];
            $name = 'firefox';
        }
        # Subversion ?
        elseif (preg_match($regversvn,$ua,$m)){
            $type = 'rcs';
            $ver  = $m[1];
            $name = 'svn';
        }
        # Netscape 6.x, 7.x ... ?
        elseif (preg_match($regvernetscape,$ua,$m)){
            $type = 'browser';
            $ver  = $m[1];
            $name = 'netscape';
        }
        # Netscape 3.x, 4.x ... ?
        elseif(preg_match($regvermozilla,$ua,$m) && !preg_match($regnotnetscape,$ua)){
            $type = 'browser';
            $ver  = $m[2];
            $name = 'netscape';
        }else{
            include(dirname(__FILE__).'/inc/browsers.php');
            foreach($BrowsersSearchIDOrder as $regex){
                if(preg_match('/'.$regex.'/',$ua)){
                    // it's a browser!
                    $type = 'browser';
                    $name = strtolower($regex);
                    break;
                }
            }
        }

        // check versions for Safari and Opera
        if($name == 'safari'){
            if(preg_match('/safari\/([\d\.]*)/i',$ua,$match)){
                $ver = $BrowsersSafariBuildToVersionHash[$match[1]];
            }
        }elseif($name == 'opera'){
            if(preg_match('/opera[\/ ]([\d\.]*)/i',$ua,$match)){
                $ver = $match[1];
            }
        }


        // check OS for browsers
        if($type == 'browser'){
            include(dirname(__FILE__).'/inc/operating_systems.php');
            foreach($OSSearchIDOrder as $regex){
                if(preg_match('/'.$regex.'/',$ua)){
                    $os = $OSHashID[$regex];
                    break;
                }
            }

        }

        // are we done now?
        if($name) return $name;

        include(dirname(__FILE__).'/inc/robots.php');
        foreach($RobotsSearchIDOrder as $regex){
            if(preg_match('/'.$regex.'/',$ua)){
                    // it's a robot!
                    $type = 'robot';
                    return strtolower($regex);
            }
        }

        // dunno
        return '';
    }

    /**
     * Log search queries
     */
    function log_search($referer,&$type){
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
        $page  = addslashes($_REQUEST['p']);
        $query = addslashes($query);
        $sql  = "INSERT INTO ".$this->getConf('db_prefix')."search
                    SET dt       = NOW(),
                        page     = '$page',
                        query    = '$query',
                        engine   = '$engine'";
        $id = $this->runSQL($sql);
        if(is_null($id)){
            global $MSG;
            print_r($MSG);
            return;
        }

        // log single keywords
        $words = explode(' ',utf8_stripspecials($query,' ','\._\-:\*'));
        foreach($words as $word){
            if(!$word) continue;
            $word = addslashes($word);
            $sql = "INSERT DELAYED INTO ".$this->getConf('db_prefix')."searchwords
                       SET sid  = $id,
                           word = '$word'";
            $ok = $this->runSQL($sql);
            if(is_null($ok)){
                global $MSG;
                print_r($MSG);
            }
        }
    }

    /**
     * Resolve IP to country/city
     */
    function log_ip($ip){
        // check if IP already known and up-to-date
        $sql = "SELECT ip
                  FROM ".$this->getConf('db_prefix')."iplocation
                 WHERE ip ='".addslashes($ip)."'
                   AND lastupd > DATE_SUB(CURDATE(),INTERVAL 30 DAY)";
        $result = $this->runSQL($sql);
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

            $sql = "REPLACE INTO ".$this->getConf('db_prefix')."iplocation
                        SET ip = '$ip',
                            country = '$country',
                            code    = '$code',
                            city    = '$city',
                            host    = '$host'";
            $this->runSQL($sql);
        }
    }

    /**
     * log a click on an external link
     *
     * called from log.php
     */
    function log_outgoing(){
        if(!$_REQUEST['ol']) return;

        $link_md5 = md5($link);
        $link     = addslashes($_REQUEST['ol']);
        $session  = addslashes(session_id());
        $page     = addslashes($_REQUEST['p']);

        $sql  = "INSERT DELAYED INTO ".$this->getConf('db_prefix')."outlinks
                    SET dt       = NOW(),
                        session  = '$session',
                        page     = '$page',
                        link_md5 = '$link_md5',
                        link     = '$link'";
        $ok = $this->runSQL($sql);
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
    function log_access(){
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
                $this->log_search($referer,$ref_type);
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
        $ip      = addslashes($_SERVER['REMOTE_ADDR']);
        $sx      = (int) $_REQUEST['sx'];
        $sy      = (int) $_REQUEST['sy'];
        $vx      = (int) $_REQUEST['vx'];
        $vy      = (int) $_REQUEST['vy'];
        $js      = (int) $_REQUEST['js'];
        $uid     = addslashes($_REQUEST['uid']);
        $user    = addslashes($_SERVER['REMOTE_USER']);
        $session = addslashes(session_id());
        if(!$uid) $uid = $session;

        $sql  = "INSERT DELAYED INTO ".$this->getConf('db_prefix')."access
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
        $ok = $this->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }

        $sql = "INSERT DELAYED IGNORE INTO ".$this->getConf('db_prefix')."refseen
                   SET ref_md5  = '$ref_md5',
                       dt       = NOW()";
        $ok = $this->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }

        // resolve the IP
        $this->log_ip($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Just send a 1x1 pixel blank gif to the browser
     *
     * @called from log.php
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Harry Fuecks <fuecks@gmail.com>
     */
    function sendGIF(){
        $img = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAEALAAAAAABAAEAAAIBTAA7');
        header('Content-Type: image/gif');
        header('Content-Length: '.strlen($img));
        header('Connection: Close');
        print $img;
        flush();
        // Browser should drop connection after this
        // Thinks it's got the whole image
    }

}
