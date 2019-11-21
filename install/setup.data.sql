CREATE TABLE `{PREFIX}blang` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `title` varchar(255) NOT NULL DEFAULT '',
  `ru` varchar(255) NOT NULL DEFAULT '',
  `ua` varchar(255) NOT NULL DEFAULT '',
  `en` varchar(255) NOT NULL DEFAULT '',
  `it` varchar(1000) NOT NULL DEFAULT '',
  `au` varchar(1000) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `{PREFIX}blang_settings` (
  `name` varchar(50) NOT NULL DEFAULT '',
  `value` text,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Contains Content Manager settings.';

#
# Dumping data for table `{PREFIX}blang_settings`
#

INSERT INTO `{PREFIX}blang_settings` VALUES ('yadexn_lang_key','');

INSERT INTO `{PREFIX}blang_settings` VALUES ('translate_provider','yandex');

INSERT INTO `{PREFIX}blang_settings` VALUES ('fields','1');

INSERT INTO `{PREFIX}blang_settings` VALUES ('translate','0');

INSERT INTO `{PREFIX}blang_settings` VALUES ('yandexKey','');

INSERT INTO `{PREFIX}blang_settings` VALUES ('default','en');

INSERT INTO `{PREFIX}blang_settings` VALUES ('menu_controller_fields','pagetitle,menutitle');

INSERT INTO `{PREFIX}blang_settings` VALUES ('content_controller_fields','pagetitle,menutitle,introtext,longtitle,description');

INSERT INTO `{PREFIX}blang_settings` VALUES ('lang_key','ua==uk');

INSERT INTO `{PREFIX}blang_settings` VALUES ('suffixes','ru==_ru||en==');

INSERT INTO `{PREFIX}blang_settings` VALUES ('languages','ru||en');

INSERT INTO `{PREFIX}blang_settings` VALUES ('clientSettingsPrefix','client_');

INSERT INTO `{PREFIX}blang_settings` VALUES ('autoFields','1');

INSERT INTO `{PREFIX}blang_settings` VALUES ('autoUrl','1');



CREATE TABLE `{PREFIX}blang_tmplvar_templates` (
  `tmplvarid` int(10) NOT NULL DEFAULT '0' COMMENT 'Template Variable id',
  `templateid` int(11) NOT NULL DEFAULT '0',
  `rank` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`tmplvarid`,`templateid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Site Template Variables Templates Link Table';


CREATE TABLE `{PREFIX}blang_tmplvars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL DEFAULT '',
  `name` varchar(50) NOT NULL DEFAULT '',
  `caption` varchar(80) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `editor_type` int(11) NOT NULL DEFAULT '0' COMMENT '0-plain text,1-rich text,2-code editor',
  `category` int(11) NOT NULL DEFAULT '0' COMMENT 'category id',
  `locked` tinyint(4) NOT NULL DEFAULT '0',
  `elements` text,
  `rank` int(11) NOT NULL DEFAULT '0',
  `display` varchar(20) NOT NULL DEFAULT '' COMMENT 'Display Control',
  `display_params` text COMMENT 'Display Control Properties',
  `default_text` text,
  `multitv_translate_fields` varchar(255) DEFAULT NULL,
  `tab` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indx_rank` (`rank`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='Site Template Variables';
