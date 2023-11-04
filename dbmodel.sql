
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MrJackPocket implementation : © Artem Katnov <a_katnov@mail.ru>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

CREATE TABLE IF NOT EXISTS `character_status` (
  `character_id` varchar(10) NOT NULL,
  `tale_pos` int(10) NOT NULL,
  `tale_is_opened` BOOLEAN NOT NULL DEFAULT '1',
  `is_jack` BOOLEAN NOT NULL,
  `wall_side` varchar(10) NOT NULL,
  `player_id_with_alibi` int DEFAULT NULL,
  `last_round_rotated` int DEFAULT NULL,
  PRIMARY KEY (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `available_options` (
  `round_num` int(10) unsigned NOT NULL,
  `option` varchar(16) NOT NULL,
  `index` int (10) unsigned NOT NULL,
  `was_used` BOOLEAN NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `detective_status` (
  `detective_id` varchar(10) NOT NULL,
  `detective_pos` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `round` (
  `round_num` int(10) unsigned NOT NULL,
  `is_criminal_visible` BOOLEAN DEFAULT NULL,
  `play_until_visibility` BOOLEAN NOT NULL DEFAULT '0',
  `win_player_id` int DEFAULT NULL,
  PRIMARY KEY (`round_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `player` ADD `player_is_jack` BOOLEAN NOT NULL DEFAULT FALSE;
