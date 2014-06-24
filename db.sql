CREATE TABLE `stats_access` (
  `id`       BIGINT(20) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `dt`       TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `page`     VARCHAR(255)
             COLLATE utf8_unicode_ci NOT NULL,
  `ip`       VARCHAR(15)
             COLLATE utf8_unicode_ci NOT NULL,
  `ua`       VARCHAR(255)
             COLLATE utf8_unicode_ci NOT NULL,
  `ua_info`  VARCHAR(255)
             COLLATE utf8_unicode_ci NOT NULL,
  `ua_type`  VARCHAR(32)
             COLLATE utf8_unicode_ci NOT NULL,
  `ua_ver`   VARCHAR(10)
             COLLATE utf8_unicode_ci NOT NULL,
  `os`       VARCHAR(32)
             COLLATE utf8_unicode_ci NOT NULL,
  `ref_md5`  VARCHAR(32)
             COLLATE utf8_unicode_ci NOT NULL,
  `ref_type` VARCHAR(32)
             COLLATE utf8_unicode_ci NOT NULL,
  `ref`      TEXT
             COLLATE utf8_unicode_ci NOT NULL,
  `screen_x` INT(10) UNSIGNED        NOT NULL,
  `screen_y` INT(10) UNSIGNED        NOT NULL,
  `view_x`   INT(10) UNSIGNED        NOT NULL,
  `view_y`   INT(10) UNSIGNED        NOT NULL,
  `user`     VARCHAR(255)
             COLLATE utf8_unicode_ci NOT NULL,
  `session`  VARCHAR(255)
             COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ref_type` (`ref_type`),
  KEY `page` (`page`),
  KEY `ref_md5` (`ref_md5`),
  KEY `dt` (`dt`)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;

CREATE TABLE `stats_iplocation` (
  `ip`      VARCHAR(15)
            COLLATE utf8_unicode_ci NOT NULL,
  `code`    VARCHAR(3)
            COLLATE utf8_unicode_ci NOT NULL,
  `country` VARCHAR(255)
            COLLATE utf8_unicode_ci NOT NULL,
  `city`    VARCHAR(255)
            COLLATE utf8_unicode_ci NOT NULL,
  `host`    VARCHAR(255)
            COLLATE utf8_unicode_ci NOT NULL,
  `lastupd` TIMESTAMP               NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ip`),
  KEY `code` (`code`)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;

-- UPGRADE added 2007-01-28
ALTER TABLE `stats_access` CHANGE `dt` `dt` DATETIME NOT NULL;
ALTER TABLE `stats_access` ADD `js` TINYINT(1) NOT NULL
AFTER `view_y`;
UPDATE `stats_access`
SET js = 1;

-- UPGRADE added 2007-01-31
ALTER TABLE `stats_access` ADD `uid` VARCHAR(50) NOT NULL;

CREATE TABLE `stats_outlinks` (
  `id`       BIGINT(20) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `dt`       DATETIME                NOT NULL,
  `session`  VARCHAR(255)
             COLLATE utf8_unicode_ci NOT NULL,
  `link_md5` VARCHAR(32)
             COLLATE utf8_unicode_ci NOT NULL,
  `link`     TEXT
             COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `link_md5` (`link_md5`)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;

-- UPGRADE added 2007-02-04
ALTER TABLE `stats_outlinks` ADD `page` VARCHAR(255) NOT NULL
AFTER `dt`;

CREATE TABLE `stats_search` (
  `id`     BIGINT(20) UNSIGNED     NOT NULL AUTO_INCREMENT,
  `dt`     DATETIME                NOT NULL,
  `page`   VARCHAR(255)
           COLLATE utf8_unicode_ci NOT NULL,
  `query`  VARCHAR(255)
           COLLATE utf8_unicode_ci NOT NULL,
  `engine` VARCHAR(255)
           COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE =MyISAM
  DEFAULT CHARSET =utf8
  COLLATE =utf8_unicode_ci;

CREATE TABLE `stats_searchwords` (
  `sid`  BIGINT UNSIGNED NOT NULL,
  `word` VARCHAR(255)    NOT NULL,
  PRIMARY KEY (`sid`, `word`)
)
  ENGINE = MYISAM
  CHARACTER SET utf8
  COLLATE utf8_unicode_ci;

-- statistic fixes
UPDATE stats_access
SET ref_type='external'
WHERE ref LIKE 'http://digg.com/%';
UPDATE stats_access
SET ref_type='external'
WHERE ref LIKE 'http://del.icio.us/%';
UPDATE stats_access
SET ref_type='external'
WHERE ref LIKE 'http://www.stumbleupon.com/%';
UPDATE stats_access
SET ref_type='external'
WHERE ref LIKE 'http://swik.net/%';
UPDATE stats_access
SET ref_type='external'
WHERE ref LIKE 'http://segnalo.alice.it/%';

-- UPGRADE added 2008-06-15
CREATE TABLE `stats_refseen` (
  `ref_md5` VARCHAR(32)
            COLLATE utf8_unicode_ci NOT NULL,
  `dt`      DATETIME                NOT NULL,
  PRIMARY KEY (`ref_md5`),
  KEY `dt` (`dt`)
)
  ENGINE = MYISAM
  CHARACTER SET utf8
  COLLATE utf8_unicode_ci;

-- This will take some time...
INSERT INTO stats_refseen (`ref_md5`, `dt`) SELECT
                                          `ref_md5`,
                                          MIN(`dt`)
                                        FROM stats_access
                                        GROUP BY `ref_md5`;

-- UPGRADE added 2012-02-08
CREATE TABLE `stats_edits` (
  `id`      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `dt`      DATETIME            NOT NULL,
  `ip`      VARCHAR(40)         NOT NULL,
  `user`    VARCHAR(255)        NOT NULL,
  `session` VARCHAR(255)        NOT NULL,
  `uid`     VARCHAR(50)         NOT NULL,
  `page`    VARCHAR(255)        NOT NULL,
  `type`    CHAR(1)
            COLLATE 'ascii_bin' NOT NULL
)
  ENGINE ='MyISAM'
  COLLATE 'utf8_general_ci';

ALTER TABLE `stats_access` CHANGE `ip` `ip` VARCHAR(40);

ALTER TABLE `stats_search` ADD INDEX `engine` (`engine`);

CREATE TABLE `stats_session` (
  `session` VARCHAR(255) NOT NULL PRIMARY KEY,
  `dt`      DATETIME     NOT NULL,
  `end`     DATETIME     NOT NULL,
  `views`   INT UNSIGNED NOT NULL,
  `uid`     VARCHAR(50)  NOT NULL
)
  COMMENT =''
  ENGINE ='MyISAM'
  COLLATE 'utf8_general_ci';

CREATE TABLE `stats_logins` (
  `id`      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `dt`      DATETIME            NOT NULL,
  `ip`      VARCHAR(40)         NOT NULL,
  `user`    VARCHAR(255)        NOT NULL,
  `session` VARCHAR(255)        NOT NULL,
  `uid`     VARCHAR(50)         NOT NULL,
  `type`    CHAR(1)
            COLLATE 'ascii_bin' NOT NULL
)
  ENGINE ='MyISAM'
  COLLATE 'utf8_general_ci';

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

-- UPGRADE added 2014-06-18
CREATE TABLE `stats_lastseen` (
  `user` VARCHAR(255) NOT NULL,
  `dt`   TIMESTAMP    NOT NULL,
  PRIMARY KEY (`user`)
)
  ENGINE ='MEMORY'
  COLLATE 'utf8_general_ci';

CREATE TABLE `stats_media` (
  `id`      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dt`      DATETIME            NOT NULL,
  `media`   VARCHAR(255)        NOT NULL,
  `ip`      VARCHAR(40) DEFAULT NULL,
  `ua`      VARCHAR(255)        NOT NULL,
  `ua_info` VARCHAR(255)        NOT NULL,
  `ua_type` VARCHAR(32)         NOT NULL,
  `ua_ver`  VARCHAR(10)         NOT NULL,
  `os`      VARCHAR(32)         NOT NULL,
  `user`    VARCHAR(255)        NOT NULL,
  `session` VARCHAR(255)        NOT NULL,
  `uid`     VARCHAR(50)         NOT NULL,
  `size`    INT UNSIGNED        NOT NULL,
  `mime1`   VARCHAR(50)         NOT NULL,
  `mime2`   VARCHAR(50)         NOT NULL,
  `inline`  TINYINT(1)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `media` (`media`),
  KEY `dt` (`dt`),
  KEY `ua_type` (`ua_type`)
)
  ENGINE ='MyISAM'
  COLLATE ='utf8_unicode_ci';

ALTER TABLE `stats_media` ADD INDEX `mime1` (`mime1`);

CREATE TABLE `stats_history` (
  `info`    VARCHAR(50)         NOT NULL,
  `dt`      DATE                NOT NULL,
  `value`   INT UNSIGNED        NOT NULL,
  PRIMARY KEY (`info`, `dt`)
)
  ENGINE ='MyISAM'
  COLLATE ='utf8_unicode_ci';

CREATE TABLE `stats_groups` (
  `id`      BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dt`      DATETIME            NOT NULL,
  `group`   VARCHAR(255)        NOT NULL,
  `type`    VARCHAR(50)         NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dt` (`dt`),
  KEY `type` (`type`)
)
  ENGINE ='MyISAM'
  COLLATE ='utf8_unicode_ci';
