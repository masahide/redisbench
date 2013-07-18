use test;

DROP TABLE IF EXISTS `test_innodb256`;
CREATE TABLE `test_innodb256` (
	  `key` varchar(32) NOT NULL,
	  `value` varchar(256) NOT NULL ,
	  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_myisam256`;
CREATE TABLE `test_myisam256` (
	  `key` varchar(32) NOT NULL,
	  `value` varchar(256) NOT NULL ,
	  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_innodb5120`;
CREATE TABLE `test_innodb5120` (
	  `key` varchar(32) NOT NULL,
	  `value` varchar(5120) NOT NULL ,
	  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_myisam5120`;
CREATE TABLE `test_myisam5120` (
	  `key` varchar(32) NOT NULL,
	  `value` varchar(5120) NOT NULL ,
	  PRIMARY KEY (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

