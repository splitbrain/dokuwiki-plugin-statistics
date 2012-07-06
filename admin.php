<?php
/**
 * statistics plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@splitbrain.org>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


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
                              'outlinks','searchengines','searchphrases',
                              'searchwords', 'internalsearchphrases',
                              'internalsearchwords','browsers','os',
                              'countries','resolution','viewport');

    /**
     * Initialize the helper
     */
    public function __construct() {
        $this->hlp = plugin_load('helper','statistics');
    }

    /**
     * Access for managers allowed
     */
    public function forAdminOnly(){
        return false;
    }

    /**
     * return sort order for position in admin menu
     */
    public function getMenuSort() {
        return 350;
    }

    /**
     * handle user request
     */
    public function handle() {
        $this->opt = preg_replace('/[^a-z]+/','',$_REQUEST['opt']);
        if(!in_array($this->opt,$this->pages)) $this->opt = 'dashboard';

        $this->start = (int) $_REQUEST['s'];
        $this->setTimeframe($_REQUEST['f'],$_REQUEST['t']);
    }

    /**
     * set limit clause
     */
    public function setTimeframe($from,$to){
        $this->tlimit = $this->hlp->Query()->mktlimit($from,$to);
        $this->from   = $from;
        $this->to     = $to;
    }

    /**
     * Output the Statistics
     */
    function html() {
        echo '<div id="plugin__statistics">';
        echo '<h1>'.$this->getLang('menu').'</h1>';
        $this->html_timeselect();
        tpl_flush();

        $method = 'html_'.$this->opt;
        if(method_exists($this,$method)){
            echo '<div class="plg_stats_'.$this->opt.'">';
            echo '<h2>'.$this->getLang($this->opt).'</h2>';
            $this->$method();
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Return the TOC
     *
     * @return array
     */
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


    function html_graph($name,$width,$height){
        $url = DOKU_BASE.'lib/plugins/statistics/img.php?img='.$name.
               '&amp;f='.$this->from.'&amp;t='.$this->to;
        echo '<img src="'.$url.'" class="graph" width="'.$width.'" height="'.$height.'"/>';
    }


    /**
     * Outputs pagination links
     *
     * @param type $limit
     * @param type $next
     */
    function html_pager($limit,$next){
        echo '<div class="plg_stats_pager">';

        if($this->start > 0){
            $go = max($this->start - $limit, 0);
            echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$go.'" class="prev button">'.$this->getLang('prev').'</a>';
        }

        if($next){
            $go = $this->start + $limit;
            echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$go.'" class="next button">'.$this->getLang('next').'</a>';
        }
        echo '</div>';
    }

    /**
     * Print the time selection menu
     */
    function html_timeselect(){
        $today   = date('Y-m-d');
        $last1   = date('Y-m-d',time()-(60*60*24));
        $last7   = date('Y-m-d',time()-(60*60*24*7));
        $last30  = date('Y-m-d',time()-(60*60*24*30));

        echo '<div class="plg_stats_timeselect">';
        echo '<span>'.$this->getLang('time_select').'</span> ';

        echo '<form action="" method="get">';
        echo '<input type="hidden" name="do" value="admin" />';
        echo '<input type="hidden" name="page" value="statistics" />';
        echo '<input type="hidden" name="opt" value="'.$this->opt.'" />';
        echo '<input type="text" name="f" value="'.$this->from.'" class="edit" />';
        echo '<input type="text" name="t" value="'.$this->to.'" class="edit" />';
        echo '<input type="submit" value="go" class="button" />';
        echo '</form>';

        echo '<ul>';
        foreach(array('today','last1','last7','last30') as $time){
            echo '<li>';
            echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$$time.'&amp;t='.$today.'">';
            echo $this->getLang('time_'.$time);
            echo '</a>';
            echo '</li>';
        }
        echo '</ul>';

        echo '</div>';
    }


    /**
     * Print an introductionary screen
     */
    function html_dashboard(){
        echo '<p>'.$this->getLang('intro_dashboard').'</p>';

        // general info
        echo '<div class="plg_stats_top">';
        $result = $this->hlp->Query()->aggregate($this->tlimit);

        echo '<ul class="left">';
        foreach(array('pageviews','sessions','visitors','users','logins') as $name){
            echo '<li><div class="li">'.sprintf($this->getLang('dash_'.$name),$result[$name]).'</div></li>';
        }
        echo '</ul>';

        echo '<ul class="left">';
        foreach(array('bouncerate','timespent','avgpages','newvisitors','registrations') as $name){
            echo '<li><div class="li">'.sprintf($this->getLang('dash_'.$name),$result[$name]).'</div></li>';
        }
        echo '</ul>';

        $this->html_graph('dashboardviews',700,280);
        $this->html_graph('dashboardwiki',700,280);
        echo '</div>';


        // top pages today
        echo '<div>';
        echo '<h2>'.$this->getLang('dash_mostpopular').'</h2>';
        $result = $this->hlp->Query()->pages($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=page&amp;f='.$this->from.'&amp;t='.$this->to.'" class="more button">'.$this->getLang('more').'</a>';
        echo '</div>';

        // top referer today
        echo '<div>';
        echo '<h2>'.$this->getLang('dash_newincoming').'</h2>';
        $result = $this->hlp->Query()->newreferer($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=newreferer&amp;f='.$this->from.'&amp;t='.$this->to.'" class="more button">'.$this->getLang('more').'</a>';
        echo '</div>';

        // top searches today
        echo '<div>';
        echo '<h2>'.$this->getLang('dash_topsearch').'</h2>';
        $result = $this->hlp->Query()->searchphrases(true,$this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=searchphrases&amp;f='.$this->from.'&amp;t='.$this->to.'" class="more button">'.$this->getLang('more').'</a>';
        echo '</div>';
    }

    function html_countries(){
        echo '<p>'.$this->getLang('intro_countries').'</p>';
        $this->html_graph('countries',400,200);
        $result = $this->hlp->Query()->countries($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_page(){
        echo '<p>'.$this->getLang('intro_page').'</p>';
        $result = $this->hlp->Query()->pages($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_browsers(){
        echo '<p>'.$this->getLang('intro_browsers').'</p>';
        $this->html_graph('browsers',400,200);
        $result = $this->hlp->Query()->browsers($this->tlimit,$this->start,150,true);
        $this->html_resulttable($result,'',150);
    }

    function html_os(){
        echo '<p>'.$this->getLang('intro_os').'</p>';
        $this->html_graph('os',400,200);
        $result = $this->hlp->Query()->os($this->tlimit,$this->start,150,true);
        $this->html_resulttable($result,'',150);
    }

    function html_referer(){
        $result = $this->hlp->Query()->aggregate($this->tlimit);

        $all    = $result['search']+$result['external']+$result['direct'];

        if($all){
            printf('<p>'.$this->getLang('intro_referer').'</p>',
                   $all,$result['direct'],(100*$result['direct']/$all),
                   $result['search'],(100*$result['search']/$all),$result['external'],
                   (100*$result['external']/$all));
        }

        $result = $this->hlp->Query()->referer($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_newreferer(){
        echo '<p>'.$this->getLang('intro_newreferer').'</p>';

        $result = $this->hlp->Query()->newreferer($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_outlinks(){
        echo '<p>'.$this->getLang('intro_outlinks').'</p>';
        $result = $this->hlp->Query()->outlinks($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_searchphrases(){
        echo '<p>'.$this->getLang('intro_searchphrases').'</p>';
        $result = $this->hlp->Query()->searchphrases(true,$this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_searchwords(){
        echo '<p>'.$this->getLang('intro_searchwords').'</p>';
        $result = $this->hlp->Query()->searchwords(true,$this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_internalsearchphrases(){
        echo '<p>'.$this->getLang('intro_internalsearchphrases').'</p>';
        $result = $this->hlp->Query()->searchphrases(false,$this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_internalsearchwords(){
        echo '<p>'.$this->getLang('intro_internalsearchwords').'</p>';
        $result = $this->hlp->Query()->searchwords(false,$this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_searchengines(){
        echo '<p>'.$this->getLang('intro_searchengines').'</p>';
        $this->html_graph('searchengines',400,200);
        $result = $this->hlp->Query()->searchengines($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_resolution(){
        echo '<p>'.$this->getLang('intro_resolution').'</p>';
        $this->html_graph('resolution',650,490);
        $result = $this->hlp->Query()->resolution($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
    }

    function html_viewport(){
        echo '<p>'.$this->getLang('intro_viewport').'</p>';
        $this->html_graph('viewport',650,490);
        $result = $this->hlp->Query()->viewport($this->tlimit,$this->start,150);
        $this->html_resulttable($result,'',150);
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
                if($k == 'res_x') continue;
                if($k == 'res_y') continue;

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
                }elseif($k == 'ilookup'){
                    echo '<a href="'.wl('',array('id'=>$v,'do'=>'search')).'">Search</a>';
                }elseif($k == 'lookup'){
                    echo '<a href="http://www.google.com/search?q='.rawurlencode($v).'">';
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/search/google.png" alt="Google" border="0" />';
                    echo '</a> ';

                    echo '<a href="http://search.yahoo.com/search?p='.rawurlencode($v).'">';
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/search/yahoo.png" alt="Yahoo!" border="0" />';
                    echo '</a> ';

                    echo '<a href="http://www.bing.com/search?q='.rawurlencode($v).'">';
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/search/bing.png" alt="Bing" border="0" />';
                    echo '</a> ';

                }elseif($k == 'engine'){
                    include_once(dirname(__FILE__).'/inc/searchengines.php');
                    if(isset($SEARCHENGINEINFO[$v])){
                        echo '<a href="'.$SEARCHENGINEINFO[$v][1].'">'.$SEARCHENGINEINFO[$v][0].'</a>';
                    }else{
                        echo hsc(ucwords($v));
                    }
                }elseif($k == 'eflag'){
                    $this->html_icon('search',$v);
                }elseif($k == 'bflag'){
                    $this->html_icon('browser',$v);
                }elseif($k == 'osflag'){
                    $this->html_icon('os',$v);
                }elseif($k == 'cflag'){
                    $this->html_icon('flags',$v);
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

    function html_icon($type,$value){
        $value = strtolower(preg_replace('/[^\w]+/','',$value));
        $value = str_replace(' ','_',$value);
        $file  = 'lib/plugins/statistics/ico/'.$type.'/'.$value.'.png';
        if($type == 'flags'){
            $w = 18;
            $h = 12;
        }else{
            $w = 16;
            $h = 16;
        }
        if(file_exists(DOKU_INC.$file)){
            echo '<img src="'.DOKU_BASE.$file.'" alt="'.hsc($value).'" width="'.$w.'" height="'.$h.'" />';
        }
    }
}
