<?php
/**
 * Statistics plugin - image creator
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
session_write_close();

$plugin = plugin_load('helper','statistics');
$plugin->Graph()->render($_REQUEST['img'],$_REQUEST['f'],$_REQUEST['t'],$_REQUEST['s']);

//Setup VIM: ex: et ts=4 enc=utf-8 :
