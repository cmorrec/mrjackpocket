<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MrJackPocket implementation : © <Your name here> <Your email address here>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * MrJackPocket game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->round_num = 8;

$this->options_to_move = [
  ['rotation', 'exchange'],
  ['rotation', 'jocker'],
  ['alibi', 'holmes'],
  ['watson', 'dog'],
];

$this->character_pos = array(
  1 => array('x' => 1, 'y' => 1),
  2 => array('x' => 2, 'y' => 1),
  3 => array('x' => 3, 'y' => 1),
  4 => array('x' => 1, 'y' => 2),
  5 => array('x' => 2, 'y' => 2),
  6 => array('x' => 3, 'y' => 2),
  7 => array('x' => 1, 'y' => 3),
  8 => array('x' => 2, 'y' => 3),
  9 => array('x' => 3, 'y' => 3),
);

$this->init_tale_rotations = array(
  1 => 'left',
  3 => 'right',
  8 => 'down',
);

$this->wall_sides = array(
  1 => 'left',
  2 => 'up',
  3 => 'right',
  4 => 'down',
);

$this->detective_pos = array(
  1 => array('x' => 1, 'y' => 0),
  2 => array('x' => 2, 'y' => 0),
  3 => array('x' => 3, 'y' => 0),
  4 => array('x' => 4, 'y' => 1),
  5 => array('x' => 4, 'y' => 2),
  6 => array('x' => 4, 'y' => 3),
  7 => array('x' => 3, 'y' => 4),
  8 => array('x' => 2, 'y' => 4),
  9 => array('x' => 1, 'y' => 4),
  10 => array('x' => 0, 'y' => 3),
  11 => array('x' => 0, 'y' => 2),
  12 => array('x' => 0, 'y' => 1),
);

$this->characters = [
  array('id' => '1', 'name' => 'Sgt Goodley', 'color' => 'black', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 0),
  array('id' => '2', 'name' => 'Insp. Lestrade', 'color' => 'blue', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 0),
  array('id' => '3', 'name' => 'Miss Stealthy', 'color' => 'green', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 1),
  array('id' => '4', 'name' => 'John Smith', 'color' => 'yellow', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 1),
  array('id' => '5', 'name' => 'John Pizer', 'color' => 'white', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 1),
  array('id' => '6', 'name' => 'William Gull', 'color' => 'violet', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 1),
  array('id' => '7', 'name' => 'Joseph Lane', 'color' => 'gray', 'closed_roads' => 4, 'tale_img' => '', 'alibi_img' => '', 'points' => 1),
  array('id' => '8', 'name' => 'Jeremy Bert', 'color' => 'orange', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 1),
  array('id' => '9', 'name' => 'Madame', 'color' => 'pink', 'closed_roads' => 3, 'tale_img' => '', 'alibi_img' => '', 'points' => 2),
];

$this->detectives = [
  array('id' => 'holmes', 'name' => 'Mr. Holmes', 'img' => ''),
  array('id' => 'watson', 'name' => 'Mr. Watson', 'img' => ''),
  array('id' => 'dog', 'name' => 'Toby', 'img' => ''),
];

$this->init_pos = [
  array('detective' => 'holmes', 'pos' => 12),
  array('detective' => 'watson', 'pos' => 4),
  array('detective' => 'dog', 'pos' => 8),
];