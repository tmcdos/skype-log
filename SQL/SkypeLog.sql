CREATE TABLE `user_last` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `skype_name` varchar(80) NOT NULL,
  `last_file` int(10) unsigned NOT NULL,
  `last_chat` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`skype_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Last ID for chat and transfers in MAIN.DB (SQLite)';

CREATE TABLE `msg_log` (
  `msg_from` int(10) unsigned NOT NULL,
  `msg_to` int(10) unsigned NOT NULL,
  `stamp` datetime NOT NULL,
  `body` text NOT NULL,
  PRIMARY KEY (`msg_from`,`msg_to`,`stamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Log history';