<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MrJackPocket implementation : © Artem Katnov <a_katnov@mail.ru>
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
  1 => clienttranslate('left'),
  2 => clienttranslate('up'),
  3 => clienttranslate('right'),
  4 => clienttranslate('down'),
);

$this->detective_pos = array(
  1 => array('x' => 1, 'y' => 0, 'index' => 1),
  2 => array('x' => 2, 'y' => 0, 'index' => 2),
  3 => array('x' => 3, 'y' => 0, 'index' => 3),
  4 => array('x' => 4, 'y' => 1, 'index' => 4),
  5 => array('x' => 4, 'y' => 2, 'index' => 5),
  6 => array('x' => 4, 'y' => 3, 'index' => 6),
  7 => array('x' => 3, 'y' => 4, 'index' => 7),
  8 => array('x' => 2, 'y' => 4, 'index' => 8),
  9 => array('x' => 1, 'y' => 4, 'index' => 9),
  10 => array('x' => 0, 'y' => 3, 'index' => 10),
  11 => array('x' => 0, 'y' => 2, 'index' => 11),
  12 => array('x' => 0, 'y' => 1, 'index' => 12),
);

$this->characters = [
  array('id' => '1', 'name' => 'Sgt Goodley', 'color' => 'black', 'closed_roads' => 3, 'tale_img' => 'img/tale_1.png', 'alibi_img' => 'img/alibi_1.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 0),
  array('id' => '2', 'name' => 'Insp. Lestrade', 'color' => 'blue', 'closed_roads' => 3, 'tale_img' => 'img/tale_2.png', 'alibi_img' => 'img/alibi_2.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 0),
  array('id' => '3', 'name' => 'Miss Stealthy', 'color' => 'green', 'closed_roads' => 3, 'tale_img' => 'img/tale_3.png', 'alibi_img' => 'img/alibi_3.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 1),
  array('id' => '4', 'name' => 'John Smith', 'color' => 'yellow', 'closed_roads' => 3, 'tale_img' => 'img/tale_4.png', 'alibi_img' => 'img/alibi_4.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 1),
  array('id' => '5', 'name' => 'John Pizer', 'color' => 'white', 'closed_roads' => 3, 'tale_img' => 'img/tale_5.png', 'alibi_img' => 'img/alibi_5.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 1),
  array('id' => '6', 'name' => 'William Gull', 'color' => 'violet', 'closed_roads' => 3, 'tale_img' => 'img/tale_6.png', 'alibi_img' => 'img/alibi_6.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 1),
  array('id' => '7', 'name' => 'Joseph Lane', 'color' => 'gray', 'closed_roads' => 4, 'tale_img' => 'img/tale_7.png', 'alibi_img' => 'img/alibi_7.png', 'closed_tale_img' => 'img/closed_4_roads.png', 'points' => 1),
  array('id' => '8', 'name' => 'Jeremy Bert', 'color' => 'orange', 'closed_roads' => 3, 'tale_img' => 'img/tale_8.png', 'alibi_img' => 'img/alibi_8.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 1),
  array('id' => '9', 'name' => 'Madame', 'color' => 'pink', 'closed_roads' => 3, 'tale_img' => 'img/tale_9.png', 'alibi_img' => 'img/alibi_9.png', 'closed_tale_img' => 'img/closed_3_roads.png', 'points' => 2),
];

$this->detectives = [
  array('id' => 'holmes', 'name' => 'Mr. Holmes', 'img' => 'img/holmes.png'),
  array('id' => 'watson', 'name' => 'Mr. Watson', 'img' => 'img/watson.png'),
  array('id' => 'dog', 'name' => 'Toby', 'img' => 'img/dog.png'),
];

$this->init_pos = [
  array('detective' => 'holmes', 'pos' => 12),
  array('detective' => 'watson', 'pos' => 4),
  array('detective' => 'dog', 'pos' => 8),
];