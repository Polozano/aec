<?php
/**
 * @version $Id: dbtables.inc.php
 * @package AEC - Account Control Expiration - Membership Manager
 * @subpackage Install Includes
 * @copyright 2006-2012 Copyright (C) David Deutsch
 * @author David Deutsch <skore@valanx.org> & Team AEC - http://www.valanx.org
 * @license GNU/GPL v.2 http://www.gnu.org/licenses/old-licenses/gpl-2.0.html or, at your option, any later version
 */

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_config` ('
. '`id` int(11) NOT NULL AUTO_INCREMENT,'
. '`settings` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_heartbeat` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`last_beat` datetime NOT NULL default \'0000-00-00 00:00:00\','
. '	PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM AUTO_INCREMENT=1;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_metauser` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`userid` int(11) NOT NULL default \'0\','
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`modified_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`plan_history` text NULL,'
. '`processor_params` text NULL,'
. '`plan_params` text NULL,'
. '`params` text NULL,'
. '`custom_params` text NULL,'
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM AUTO_INCREMENT=1;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_displaypipeline` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`userid` int(11) NOT NULL default \'0\','
. '`only_user` int(4) NOT NULL default \'0\','
. '`once_per_user` int(4) NOT NULL default \'0\','
. '`timestamp` datetime NULL default \'0000-00-00 00:00:00\','
. '`expire` int(11) NOT NULL default \'0\','
. '`expstamp` datetime NULL default \'0000-00-00 00:00:00\','
. '`displaycount` int(11) NOT NULL default \'0\','
. '`displaymax` int(11) NOT NULL default \'0\','
. '`displaytext` text NULL,'
. '`params` text NULL,'
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM AUTO_INCREMENT=1;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_eventlog` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`datetime` datetime NULL default \'0000-00-00 00:00:00\','
. '`short` varchar(60) NOT NULL,'
. '`tags` text NULL,'
. '`event` text NULL,'
. '`level` int(4) NOT NULL default \'2\','
. '`notify` int(1) NOT NULL default \'0\','
. '`params` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM AUTO_INCREMENT=1;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_config_processors` ('
. '`id` int(11) NOT NULL AUTO_INCREMENT,'
. '`name` varchar(60) NOT NULL,'
. '`active` int(4) NOT NULL default \'1\','
. '`info` text NULL,'
. '`settings` text NULL,'
. '`params` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_invoices` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`active` int(4) NOT NULL default \'1\','
. '`counter` int(11) NOT NULL default \'0\','
. '`userid` int(11) NOT NULL default \'0\','
. '`subscr_id` int(11) NULL,'
. '`invoice_number` varchar(64) NULL,'
. '`invoice_number_format` varchar(64) NULL,'
. '`secondary_ident` varchar(64) NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`transaction_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`fixed` int(4) NOT NULL default \'0\','
. '`usage` varchar(255) NULL,'
. '`method` varchar(40) NULL,'
. '`amount` varchar(40) NULL,'
. '`currency` varchar(10) NULL,'
. '`coupons` text NULL,'
. '`transactions` text NULL,'
. '`params` text NULL,'
. '`conditions` text NULL,'
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_temptoken` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`token` varchar(200) NULL,'
. '`content` text NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`ip` varchar(25) NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_cart` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`userid` int(11) NOT NULL default \'0\','
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`last_updated` datetime NULL default \'0000-00-00 00:00:00\','
. '`content` text NULL,'
. '`history` text NULL,'
. '`params` text NULL,'
. '`customparams` text NULL,'
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_log_history` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`proc_id` int(4) NULL,'
. '`proc_name` varchar(100) NULL,'
. '`user_id` int(4) NULL,'
. '`user_name` varchar(100) NULL,'
. '`plan_id` int(4) NULL,'
. '`plan_name` varchar(100) NULL,'
. '`transaction_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`amount` varchar(40) NULL,'
. '`invoice_number` varchar(60) NULL,'
. '`response` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_plans` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`active` int(4) NOT NULL default \'1\','
. '`visible` int(4) NOT NULL default \'1\','
. '`ordering` int(11) NOT NULL default \'999999\','
. '`name` varchar(255) NULL,'
. '`desc` text NULL,'
. '`email_desc` text NULL,'
. '`params` text NULL,'
. '`custom_params` text NULL,'
. '`restrictions` text NULL,'
. '`micro_integrations` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_itemgroups` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`active` int(4) NOT NULL default \'1\','
. '`visible` int(4) NOT NULL default \'1\','
. '`ordering` int(11) NOT NULL default \'999999\','
. '`name` varchar(40) NULL,'
. '`desc` text NULL,'
. '`params` text NULL,'
. '`custom_params` text NULL,'
. '`restrictions` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_itemxgroup` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`type` varchar(255) NULL,'
. '`item_id` int(11) NULL,'
. '`group_id` int(11) NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_microintegrations` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`active` int(4) NOT NULL default \'1\','
. '`system` int(4) NOT NULL default \'0\','
. '`hidden` int(4) NOT NULL default \'0\','
. '`ordering` int(11) NOT NULL default \'999999\','
. '`name` varchar(40) NULL,'
. '`desc` text NULL,'
. '`class_name` varchar(40) NULL,'
. '`params` text NULL,'
. '`auto_check` int(4) NOT NULL default \'0\','
. '`on_userchange` int(4) NOT NULL default \'0\','
. '`pre_exp_check` int(4) NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_event` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`userid` int(11) NOT NULL default \'0\','
. '`status` varchar(40) NULL,'
. '`type` varchar(255) NULL,'
. '`subtype` varchar(255) NULL,'
. '`appid` int(11) NOT NULL default \'0\','
. '`event` varchar(255) NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`due_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`context` text NULL,'
. '`params` text NULL,'
. '`customparams` text NULL,'
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_subscr` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`userid` int(11) NULL,'
. '`primary` int(1) NOT NULL default \'0\','
. '`type` varchar(40) NULL,'
. '`status` varchar(10) NULL,'
. '`signup_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`lastpay_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`cancel_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`eot_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`eot_cause` varchar(100) NULL,'
. '`plan` int(11) NULL,'
. '`previous_plan` int(11) NULL,'
. '`used_plans` varchar(255) NULL,'
. '`recurring` int(1) NOT NULL default \'0\','
. '`lifetime` int(1) NOT NULL default \'0\','
. '`expiration` datetime NULL default \'0000-00-00 00:00:00\','
. '`params` text NULL,'
. '`customparams` text NULL,'
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_coupons` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`active` int(4) NOT NULL default \'1\','
. '`ordering` int(11) NOT NULL default \'999999\','
. '`coupon_code` varchar(255) NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`name` varchar(255) NULL,'
. '`desc` text NULL,'
. '`discount` text NULL,'
. '`restrictions` text NULL,'
. '`params` text NULL,'
. '`usecount` int(64) NOT NULL default \'0\','
. '`micro_integrations` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_coupons_static` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`active` int(4) NOT NULL default \'1\','
. '`ordering` int(11) NOT NULL default \'999999\','
. '`coupon_code` varchar(255) NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`name` varchar(255) NULL,'
. '`desc` text NULL,'
. '`discount` text NULL,'
. '`restrictions` text NULL,'
. '`params` text NULL,'
. '`usecount` int(64) NOT NULL default \'0\','
. '`micro_integrations` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_couponsxuser` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`coupon_id` int(11) NULL,'
. '`coupon_type` int(2) NOT NULL default \'0\','
. '`coupon_code` varchar(255) NULL,'
. '`userid` int(11) NOT NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`last_updated` datetime NULL default \'0000-00-00 00:00:00\','
. '`params` text NULL,'
. '`usecount` int(64) NOT NULL default \'0\','
. ' PRIMARY KEY (`id`),'
. ' KEY (`userid`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_export` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`system` int(2) NOT NULL default \'0\','
. '`name` varchar(255) NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`lastused_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`filter` text NULL,'
. '`options` text NULL,'
. '`params` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;

$queri[] = 'CREATE TABLE IF NOT EXISTS `#__acctexp_export_sales` ('
. '`id` int(11) NOT NULL auto_increment,'
. '`system` int(2) NOT NULL default \'0\','
. '`name` varchar(255) NULL,'
. '`created_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`lastused_date` datetime NULL default \'0000-00-00 00:00:00\','
. '`filter` text NULL,'
. '`options` text NULL,'
. '`params` text NULL,'
. ' PRIMARY KEY (`id`)'
. ') ENGINE=MyISAM;'
;
?>
