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
    var $dblink = null;

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }

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
        return 140;
    }

    /**
     * handle user request
     */
    function handle() {
    }

    /**
     * output appropriate html
     */
    function html() {
        echo 'fixme';
    }


    /**
     * Simple function to run a DB query
     */
    function runSQL($sql_string) {
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

        $result = mysql_db_query($this->getConf('db_database'),$sql_string,$this->dblink);
        if(!mysql_db_query($this->conf['db_database'],$sql_string,$this->dblink)){
            msg('DB Error: '.mysql_error($this->dblink),-1);
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

        if (mysql_insert_id($this->dblink)) {
            $resultarray = mysql_insert_id($this->dblink); //give back ID on insert
        }

        return $resultarray;
    }

    /**
     * Returns a short name for a User Agent
     *
     * @fixme: needs to be implemented
     */
    function ua_info($ua){
        return '';
    }



    /**
     * log a page access
     *
     * called from log.php
     */
    function log_access(){
        $page    = addslashes($_REQUEST['p']);
        $ip      = addslashes($_SERVER['REMOTE_ADDR']);
        $ua      = addslashes($_SERVER['USER_AGENT']);
        $ua_info = addslashes($this->ua_info($_SERVER['USER_AGENT']));
        $ref     = addslashes($_REQUEST['r']);
        $sx      = (int) $_REQUEST['sx'];
        $sy      = (int) $_REQUEST['sy'];
        $vx      = (int) $_REQUEST['vx'];
        $vy      = (int) $_REQUEST['vy'];
        $user    = addslashes($_SERVER['REMOTE_USER']);
        $session = addslashes(session_id());

        $sql  = "INSERT INTO ".$this->getConf('db_prefix')."access
                    SET page     = '$page',
                        ip       = '$ip',
                        ua       = '$ua',
                        ua_info  = '$ua_info',
                        ref      = '$ref',
                        ref_md5  = MD5('$ref'),
                        screen_x = '$sx',
                        screen_y = '$sy',
                        view_x   = '$vx',
                        view_y   = '$vy',
                        user     = '$user',
                        session  = '$session'";
        $ok = $this->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }
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
