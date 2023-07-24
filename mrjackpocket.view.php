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
 * mrjackpocket.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in mrjackpocket_mrjackpocket.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */

require_once(APP_BASE_PATH . "view/common/game.view.php");

class view_mrjackpocket_mrjackpocket extends game_view
{
    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "mrjackpocket";
    }

    function build_page($viewArgs)
    {
        // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count($players);

        /*********** Place your code below:  ************/


        /*
        
        // Examples: set the value of some element defined in your tpl file like this: {MY_VARIABLE_ELEMENT}

        // Display a specific number / string
        $this->tpl['MY_VARIABLE_ELEMENT'] = $number_to_display;

        // Display a string to be translated in all languages: 
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::_("A string to be translated");

        // Display some HTML content of your own:
        $this->tpl['MY_VARIABLE_ELEMENT'] = self::raw( $some_html_code );
        
        */



        // Example: display a specific HTML block for each player in this game.
        // (note: the block is defined in your .tpl file like this:
        //      <!-- BEGIN myblock --> 
        //          ... my HTML code ...
        //      <!-- END myblock --> 

        $round_num = 8;

        $options_to_move = [
            ['rotation', 'exchange'],
            ['rotation', 'jocker'],
            ['alibi', 'holmes'],
            ['watson', 'dog'],
        ];


        $this->page->begin_block("mrjackpocket_mrjackpocket", "available_option");
        foreach ($options_to_move as $index => $_) {
            $this->page->insert_block(
                "available_option",
                array("index" => $index),
            );
        }

        $this->page->begin_block("mrjackpocket_mrjackpocket", "round");
        foreach (range(1, $round_num) as $roundNum) {
            $this->page->insert_block(
                "round",
                array("round_num" => $roundNum),
            );
        }

        $this->page->begin_block("mrjackpocket_mrjackpocket", "tale");
        foreach (range(1, 25) as $pos) {
            if (($pos - 1) / 5 < 1 || ($pos - 1) / 5 >= 4) {
                $status = 'detective-field';
            } else if (($pos - 1) % 5 === 0 || ($pos - 1) % 5 === 4) {
                $status = 'detective-field';
            } else {
                $status = 'character-field';
            }
            $this->page->insert_block(
                "tale",
                array("pos" => $pos, "status" => $status),
            );
        }



        /*********** Do not change anything below this line  ************/
    }
}