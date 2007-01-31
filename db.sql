-- phpMyAdmin SQL Dump
-- version 2.9.1.1-Debian-2
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jan 26, 2007 at 05:34 PM
-- Server version: 5.0.30
-- PHP Version: 4.4.4-8
-- 
-- Database: `stats`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `stats_access`
-- 

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

-- --------------------------------------------------------

-- 
-- Table structure for table `stats_iplocation`
-- 

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

--- added 2007-01-28
ALTER TABLE `stats_access` CHANGE `dt` `dt` DATETIME NOT NULL ;
ALTER TABLE `stats_access` ADD `js` TINYINT( 1 ) NOT NULL AFTER `view_y` ;
UPDATE `stats_access` SET js = 1 ;

--- added 2007-01-31
ALTER TABLE `stats_access` ADD `uid` VARCHAR( 50 ) NOT NULL ;
