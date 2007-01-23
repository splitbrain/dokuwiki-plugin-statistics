<?php
/**
 * Statistics plugin - data logger
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

define('DOKU_INC','/var/www/dokuwiki/');

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/auth.php');
require_once(dirname(__FILE__).'/admin.php');
session_write_close();

// all feature are in the admin plugin
$plugin = new admin_plugin_statistics();
$plugin->log_access();
$plugin->sendGIF();

//Setup VIM: ex: et ts=4 enc=utf-8 :
