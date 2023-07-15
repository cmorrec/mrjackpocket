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

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!


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
        // "possibleactions" => array(), // ?
        "transitions" => array(
            "nextTurn" => 24,
            // "alibi" => 11,
            // "rotation" => 12,
            // "exchange" => 13,
            // "watson" => 23,
            // "holmes" => 23,
            // "dog" => 23,
            // "watson" => 14,
            // "holmes" => 15,
            // "dog" => 16,
            // "jocker" => 17,
        ),
    ),

    // 11 => array(
    //     "name" => "alibi",
    //     "description" => clienttranslate('${actplayer} obtains alibi card'),
    //     "descriptionmyturn" => clienttranslate('${you} obtain alibi card'),
    //     "type" => "game",
    //     "action" => "stAlibi",
    //     "transitions" => array(
    //         "nextTurn" => 24,
    //     ),
    // ),

    // 12 => array(
    //     "name" => "rotation",
    //     "description" => clienttranslate('${actplayer} must choose a tale to rotate'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a tale to rotate'),
    //     "type" => "activeplayer",
    //     "possibleactions" => array(), // ?
    //     "transitions" => array(
    //         "chooseRotationForTale" => 19, // TODO squash into one and separate only for frontend
    //     ),
    // ),

    // 13 => array(
    //     "name" => "exchange",
    //     "description" => clienttranslate('${actplayer} must choose a first tale to exhange'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a first tale to exhange'),
    //     "type" => "activeplayer",
    //     "possibleactions" => array(), // ?
    //     "transitions" => array(
    //         "chooseSecondTaleToExhange" => 21, // TODO squash into one and separate only for frontend
    //     ),
    // ),

    // 14 => array(
    //     "name" => "watson",
    //     "description" => clienttranslate('${actplayer} must choose a final location for Dr. Watson'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a final location for Dr. Watson'),
    //     "type" => "activeplayer",
    //     "transitions" => array(
    //         "chooseFinalLocation" => 23,
    //     ),
    // ),

    // 15 => array(
    //     "name" => "holmes",
    //     "description" => clienttranslate('${actplayer} must choose a final location for Mr. Holmes'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a final location for Mr. Holmes'),
    //     "type" => "activeplayer",
    //     "transitions" => array(
    //         "chooseFinalLocation" => 23,
    //     ),
    // ),

    // 16 => array(
    //     "name" => "dog",
    //     "description" => clienttranslate('${actplayer} must choose a final location for Dog'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a final location for Dog'),
    //     "type" => "activeplayer",
    //     "transitions" => array(
    //         "chooseFinalLocation" => 23,
    //     ),
    // ),

    // 17 => array(
    //     "name" => "jocker",
    //     "description" => clienttranslate('${actplayer} must choose a person to move'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a person to move'),
    //     "type" => "activeplayer",
    //     "possibleactions" => array("jockerWatson", "jockerHolmes", "jockerDog", "jockerNothing"),
    //     "transitions" => array(
    //         // "chooseFinalLocation" => 23,
    //         "nextTurn" => 24,
    //     ),
    // ),

    // 19 => array(
    //     "name" => "chooseRotationForTale", // TODO squash into one and separate only for frontend
    //     "description" => clienttranslate('${actplayer} must choose a rotation for the tale'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a rotation for the tale'),
    //     "type" => "activeplayer",
    //     "possibleactions" => array(), // ?
    //     "transitions" => array(
    //         "nextTurn" => 24,
    //     ),
    // ),

    // 21 => array(
    //     "name" => "chooseSecondTaleToExhange", // TODO squash into one and separate only for frontend
    //     "description" => clienttranslate('${actplayer} must choose a second tale to exhange'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a second tale to exhange'),
    //     "type" => "activeplayer",
    //     "possibleactions" => array(), // ?
    //     "transitions" => array(
    //         "nextTurn" => 24,
    //     ),
    // ),

    // 23 => array(
    //     "name" => "chooseFinalLocation", // TODO squash into one and separate only for frontend
    //     "description" => clienttranslate('${actplayer} must choose a final location for the person'),
    //     "descriptionmyturn" => clienttranslate('${you} must choose a final location for the person'),
    //     "type" => "activeplayer",
    //     "possibleactions" => array(), // ?
    //     "transitions" => array(
    //         "nextTurn" => 24,
    //     ),
    // ),

    24 => array(
        "name" => "nextTurn",
        "type" => "game",
        "action" => "stNextTurn",
        "transitions" => array(
            "playerTurn" => 10,
            "roundEnd" => 25,
        ),
    ),

    25 => array(
        "name" => "roundEnd",
        "type" => "game",
        "updateGameProgression" => true,
        "action" => "stEndOfRound",
        "transitions" => array(
            "nextTurn" => 24,
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