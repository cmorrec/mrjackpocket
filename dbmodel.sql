
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MrJackPocket implementation : © <Your name here> <Your email address here>
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

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.

-- Example 1: create a standard "card" table to be used with the "Deck" tools (see example game "hearts"):

-- CREATE TABLE IF NOT EXISTS `card` (
--   `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
--   `card_type` varchar(16) NOT NULL,
--   `card_type_arg` int(11) NOT NULL,
--   `card_location` varchar(16) NOT NULL,
--   `card_location_arg` int(11) NOT NULL,
--   PRIMARY KEY (`card_id`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


-- character_status:
    -- character_id
    -- tale_pos(?x,y?)
    -- tale_is_opened(=1)
    -- is_criminal
    -- player_id_with_alibi
    -- wall_side(null, right, left, up, down)
CREATE TABLE IF NOT EXISTS `character_status` (
  `character_id` varchar(3) NOT NULL,
  `tale_pos` int(3) NOT NULL,
  `tale_is_opened` BOOLEAN NOT NULL,
  `is_jack` BOOLEAN NOT NULL,
  `wall_side` varchar(10) DEFAULT NULL,
  `player_id_with_alibi` varchar(21) DEFAULT NULL,
  PRIMARY KEY (`character_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- available_options: round_num, option, was_used
CREATE TABLE IF NOT EXISTS `available_options` (
  `round_num` int(3) unsigned NOT NULL,
  `option` varchar(16) NOT NULL,
  `was_used` BOOLEAN NOT NULL DEFAULT FALSE,
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- detective_status: detective_id, detective_pos(?x,y?)
CREATE TABLE IF NOT EXISTS `detective_status` (
  `detective_id` varchar(10) NOT NULL,
  `detective_pos` int(3) NOT NULL,
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- round:
    -- round_num
    -- win_player_id
    -- is_criminal_visible
    -- play until visibility (default false, after draw before 8 round true)
CREATE TABLE IF NOT EXISTS `round` (
  `round_num` int(3) unsigned NOT NULL,
  `is_criminal_visible` BOOLEAN DEFAULT NULL,
  `play_until_visibility` BOOLEAN NOT NULL DEFAULT FALSE,
  `win_player_id` varchar(21) DEFAULT NULL,
  PRIMARY KEY (`round_num`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- change player ???
ALTER TABLE `player` ADD `player_is_jack` BOOLEAN NOT NULL DEFAULT FALSE;
-- player_id int
-- cancel, approve action ???
