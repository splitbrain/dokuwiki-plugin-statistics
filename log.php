<?php
/**
 * Statistics plugin - data logger
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
session_write_close();

// all features are in the admin plugin
$plugin = plugin_load('helper','statistics');
if($_REQUEST['ol']){
    $plugin->Logger()->log_outgoing();
}else{
    $plugin->Logger()->log_access();
}
$plugin->sendGIF();

//Setup VIM: ex: et ts=4 :
