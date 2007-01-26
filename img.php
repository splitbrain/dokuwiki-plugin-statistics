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
require_once(DOKU_INC.'inc/auth.php');
require_once(dirname(__FILE__).'/admin.php');
session_write_close();

// all features are in the admin plugin
$plugin = new admin_plugin_statistics();
$plugin->handle(); // initialize some internal vars
$plugin->img_build($_REQUEST['img']);

//Setup VIM: ex: et ts=4 enc=utf-8 :
