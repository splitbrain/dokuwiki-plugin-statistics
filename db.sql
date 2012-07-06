
CREATE TABLE `stats_access` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `dt` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `page` varchar(255) collate utf8_unicode_ci NOT NULL,
  `ip` varchar(15) collate utf8_unicode_ci NOT NULL,
  `ua` varchar(255) collate utf8_unicode_ci NOT NULL,
  `ua_info` varchar(255) collate utf8_unicode_ci NOT NULL,
  `ua_type` varchar(32) collate utf8_unicode_ci NOT NULL,
  `ua_ver` varchar(10) collate utf8_unicode_ci NOT NULL,
  `os` varchar(32) collate utf8_unicode_ci NOT NULL,
  `ref_md5` varchar(32) collate utf8_unicode_ci NOT NULL,
  `ref_type` varchar(32) collate utf8_unicode_ci NOT NULL,
  `ref` text collate utf8_unicode_ci NOT NULL,
  `screen_x` int(10) unsigned NOT NULL,
  `screen_y` int(10) unsigned NOT NULL,
  `view_x` int(10) unsigned NOT NULL,
  `view_y` int(10) unsigned NOT NULL,
  `user` varchar(255) collate utf8_unicode_ci NOT NULL,
  `session` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `ref_type` (`ref_type`),
  KEY `page` (`page`),
  KEY `ref_md5` (`ref_md5`),
  KEY `dt` (`dt`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `stats_iplocation` (
  `ip` varchar(15) collate utf8_unicode_ci NOT NULL,
  `code` varchar(3) collate utf8_unicode_ci NOT NULL,
  `country` varchar(255) collate utf8_unicode_ci NOT NULL,
  `city` varchar(255) collate utf8_unicode_ci NOT NULL,
  `host` varchar(255) collate utf8_unicode_ci NOT NULL,
  `lastupd` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`ip`),
  KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- UPGRADE added 2007-01-28
ALTER TABLE `stats_access` CHANGE `dt` `dt` DATETIME NOT NULL ;
ALTER TABLE `stats_access` ADD `js` TINYINT( 1 ) NOT NULL AFTER `view_y` ;
UPDATE `stats_access` SET js = 1 ;

-- UPGRADE added 2007-01-31
ALTER TABLE `stats_access` ADD `uid` VARCHAR( 50 ) NOT NULL ;

CREATE TABLE `stats_outlinks` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `dt` datetime NOT NULL,
  `session` varchar(255) collate utf8_unicode_ci NOT NULL,
  `link_md5` varchar(32) collate utf8_unicode_ci NOT NULL,
  `link` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `link_md5` (`link_md5`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- UPGRADE added 2007-02-04
ALTER TABLE `stats_outlinks` ADD `page` VARCHAR( 255 ) NOT NULL AFTER `dt` ;

CREATE TABLE `stats_search` (
  `id` bigint(20) unsigned NOT NULL auto_increment,
  `dt` datetime NOT NULL,
  `page` varchar(255) collate utf8_unicode_ci NOT NULL,
  `query` varchar(255) collate utf8_unicode_ci NOT NULL,
  `engine` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `stats_searchwords` (
  `sid` BIGINT UNSIGNED NOT NULL ,
  `word` VARCHAR( 255 ) NOT NULL ,
  PRIMARY KEY ( `sid` , `word` )
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- statistic fixes
update stats_access set ref_type='external' where ref LIKE 'http://digg.com/%';
update stats_access set ref_type='external' where ref LIKE 'http://del.icio.us/%';
update stats_access set ref_type='external' where ref LIKE 'http://www.stumbleupon.com/%';
update stats_access set ref_type='external' where ref LIKE 'http://swik.net/%';
update stats_access set ref_type='external' where ref LIKE 'http://segnalo.alice.it/%';

-- UPGRADE added 2008-06-15
CREATE TABLE `stats_refseen` (
  `ref_md5` varchar(32) collate utf8_unicode_ci NOT NULL,
  `dt` datetime NOT NULL,
  PRIMARY KEY ( `ref_md5` ),
  KEY `dt` (`dt`)
) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- This will take some time...
INSERT INTO stats_refseen (ref_md5,dt) SELECT ref_md5, MIN(dt) FROM stats_access GROUP BY ref_md5;

-- UPGRADE added 2012-02-08
CREATE TABLE `stats_edits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `dt` datetime NOT NULL,
  `ip` varchar(40) NOT NULL,
  `user` varchar(255) NOT NULL,
  `session` varchar(255) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `page` varchar(255) NOT NULL,
  `type` char(1) COLLATE 'ascii_bin' NOT NULL
) ENGINE='MyISAM' COLLATE 'utf8_general_ci';

ALTER TABLE `stats_access` CHANGE `ip` `ip` varchar(40);

ALTER TABLE `stats_search` ADD INDEX `engine` (`engine`);

CREATE TABLE `stats_session` (
  `session` varchar(255) NOT NULL PRIMARY KEY,
  `dt` datetime NOT NULL,
  `end` datetime NOT NULL,
  `views` int unsigned NOT NULL,
  `uid` varchar(50) NOT NULL
) COMMENT='' ENGINE='MyISAM' COLLATE 'utf8_general_ci';

CREATE TABLE `stats_logins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `dt` datetime NOT NULL,
  `ip` varchar(40) NOT NULL,
  `user` varchar(255) NOT NULL,
  `session` varchar(255) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `type` char(1) COLLATE 'ascii_bin' NOT NULL
) ENGINE='MyISAM' COLLATE 'utf8_general_ci';

ALTER TABLE `stats_edits` ADD INDEX `dt` (`dt`);
ALTER TABLE `stats_edits` ADD INDEX `type` (`type`);
ALTER TABLE `stats_logins` ADD INDEX `dt` (`dt`);
ALTER TABLE `stats_logins` ADD INDEX `type` (`type`);
ALTER TABLE `stats_outlinks` ADD INDEX `dt` (`dt`);
ALTER TABLE `stats_search` ADD INDEX `dt` (`dt`);
ALTER TABLE `stats_session` ADD INDEX `dt` (`dt`);
ALTER TABLE `stats_session` ADD INDEX `views` (`views`);
ALTER TABLE `stats_session` ADD INDEX `uid` (`uid`);
ALTER TABLE `stats_access` ADD INDEX `ua_type` (`ua_type`);

