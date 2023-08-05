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
 * states.inc.php
 *
 * MrJackPocket game states description
 *
 */

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => 10)
    ),

    10 => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must choose an action'),
        "descriptionmyturn" => clienttranslate('${you} must choose an action'),
        "type" => "activeplayer",
        "transitions" => array(
            "nextTurn" => 24,
        ),
    ),

    24 => array(
        "name" => "nextTurn",
        "type" => "game",
        "action" => "stNextTurn",
        "updateGameProgression" => true,
        "transitions" => array(
            "playerTurn" => 10,
            "roundEnd" => 25,
        ),
    ),

    25 => array(
        "name" => "roundEnd",
        "type" => "game",
        "action" => "stEndOfRound",
        "transitions" => array(
            "playerTurn" => 24,
            "gameEnd" => 99,
        ),
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99 => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )
);