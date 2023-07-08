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
 * mrjackpocket.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class MrJackPocket extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::initGameStateLabels(array());
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "mrjackpocket";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        // // Set the colors of the players with HTML color code
        // // The default below is red/green/blue/orange/brown
        // // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        // $gameinfos = self::getGameinfos();
        // $default_colors = $gameinfos['player_colors'];

        // // Create players
        // // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        // $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        // $values = array();
        // foreach ($players as $player_id => $player) {
        //     $color = array_shift($default_colors);
        //     $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        // }
        // $sql .= implode(',', $values);
        // self::DbQuery($sql);
        // self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        // self::reloadPlayersBasicInfos();







        // TODO check all sql and php syntax
        /**
         * 0) find Jack
         * 1) shuffle tales, their rotation and assign pos to x, y
         * 2) rotate tales near detectives if there is a need
         * 3) choose detective and jack from players
         * 4) make generation of cards ???
         * 5) save everything in DB: character_status, available_options, detective_status, round, player.isCriminal
         * 6) go to the next state
         */

        $jackId = (string) bga_rand(1, 9);
        $jackIndex = bga_rand(0, 1);
        $jackPlayerId = $players[$jackIndex]['player_id'];

        // saving player in db
        // TODO THink about colors
        $default_color = array("ff0000", "008000", "0000ff", "ffa500");
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_is_jack, player_no) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $playerIsJack = (int) $player_id === $jackPlayerId;
            $color = array_shift($default_color);
            $player_no = ((int) $playerIsJack === 1) + 1;
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "','" . $playerIsJack . "','" . $player_no . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reloadPlayersBasicInfos();

        // 1) shuffle tales, their rotation and assign pos to x, y
        // 2) rotate tales near detectives if there is a need
        $tales = array();
        // { character_id, tale_pos, tale_is_opened, is_criminal, player_id_with_alibi }
        $posArray = $this->getRandomPosArray(count($this->characters));
        foreach ($this->characters as $index => $character) {
            $tales[] = array(
                'character_id' => $character['id'],
                'tale_pos' => $posArray[$index],
                'tale_is_opened' => true,
                'is_criminal' => $character['id'] === $jackId,
                'player_id_with_alibi' => null,
                'wall_side' => $this->getInitialWallSide($posArray[$index]),
            );
        }

        // saving character_status in db
        $sql = "INSERT INTO character_status (character_id, tale_pos, tale_is_opened, is_jack, wall_side) VALUES ";
        $values = array();
        foreach ($tales as $tale) {
            $values[] = "('" . $tale['character_id'] . "','" . $tale['tale_pos'] . "','" . $tale['tale_is_opened'] . "','" . $tale['is_jack'] . "','" . $tale['wall_side'] . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);

        // saving available_options in db
        $this->saveOptionsInDB(1, $this->getRandomOptions());

        // saving detective_status in db
        $sql = "INSERT INTO detective_status (detective_id, detective_pos) VALUES ";
        $values = array();
        foreach ($this->init_pos as $pos) {
            $values[] = "('" . $pos['detective'] . "','" . $pos['pos'] . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);

        // saving round in db
        $this->addRound();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId(); // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);

        // TODO: Gather all information about current game situation (visible by player $current_player_id).

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        // TODO: compute and return the game progression
        // getCurrentRound / round_num

        return 0;
    }


    //////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getInitialWallSide($tale_pos)
    {
        if (array_key_exists($this->init_tale_rotations, $tale_pos)) {
            return $this->init_tale_rotations[$tale_pos];
        }

        $side = bga_rand(1, 4);
        return $this->wall_sides[$side];
    }

    // TODO check algo, maybe here you will find an errors
    function getRandomPosArray(int $length): array
    {
        $result = array();
        $numbers = range(1, $length);
        foreach ($numbers as $number) {
            $rand = bga_rand(0, $length - $number);
            foreach ($numbers as $randNumber) {
                if (array_key_exists($randNumber, $result)) {
                    if ($rand === 0) {
                        $result[$randNumber] = $number;
                    } else {
                        $rand--;
                    }
                }
            }
        }

        return $result;
    }

    function getRandomOptions(): array
    {
        $randomPoses = $this->getRandomPosArray(count($this->options_to_move));

        return array_map(
            fn(int $pos): string => $this->options_to_move[$pos][bga_rand(0, 1)],
            $randomPoses
        );
    }

    function saveOptionsInDB(int $roundNum, array $options): void
    {
        $sql = "INSERT INTO available_options (round_num, `option`, was_used) VALUES ";
        $values = array();

        foreach ($options as $option) {
            $wasUsed = 0;
            $values[] = "('" . $roundNum . "','" . $option . "','" . $wasUsed . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
    }

    function addRound(): void
    {
        $lastRound = self::getObjectFromDB("SELECT round_num, play_until_visibility FROM `round` ORDER BY round_num DESC LIMIT 1");
        if (is_null($lastRound)) {
            $roundNum = 1;
            $playUntilVisibility = false;
        } else {
            $roundNum = $lastRound['round_num'] + 1;
            $playUntilVisibility = $lastRound['play_until_visibility'];
        }

        $sql = "INSERT INTO `round` (round_num, play_until_visibility) VALUES ($roundNum, $playUntilVisibility)";
        self::DbQuery($sql);
    }


    //////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in mrjackpocket.action.php)
    */

    function rotateTale($player_id, $tale_id, $wall_side)
    {
        /**
         * 1) check ability of player to do it
         * 2) rotate
         * 3) go to the state next turn
         */
    }

    function exchangeTales($player_id, $tale_id_1, $tale_id_2)
    {
        /**
         * 1) check ability of player to do it
         * 2) exchange
         * 3) go to the state next turn
         */
    }

    function jocker($player_id, $detective_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function holmes($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function watson($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function dog($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function jockerHolmes($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function jockerWatson($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function jockerDog($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    function jockerNothing($player_id, $new_pos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
    }

    /*
    
    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */


    //////////////////////////////////////////////////////////////////////////////
//////////// Game state arguments
////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

    //////////////////////////////////////////////////////////////////////////////
//////////// Game state actions
////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    // function stGameSetup() {}

    function stAlibi()
    {
        /**
         * 1) define who has asked alibi or get it from params
         * 2) check if person could ask alibi
         * 3) get random alibi, knowing db statuses
         * 4) return it to all players (notifyAllPlayers)
         * 5) go the next state: nextTurn
         */
    }

    function stNextTurn()
    {
        /**
         * 1) define is it the end of round. if it is -- go to the state end of round
         * 2) if it is not - determine the next active player and go to the statuus playerTurn 
         */
    }

    function stEndOfRound()
    {
        /**
         * 
         */
    }

    /*
    
    Example for game state "MyGameState":

    function stMyGameState()
    {
        // Do some stuff ...
        
        // (very often) go to another gamestate
        $this->gamestate->nextState( 'some_gamestate_transition' );
    }    
    */

    //////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        /**
         * random actions:
         *  alibi -- no random
         *  rotate -- random tale, random rotate
         *  exchange -- 2 random tales
         *  watson, dog, holmes -- random 1 or 2 steps
         *  joker -- random detective or nothing, if detective -- random 1 or 0
         */
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    ///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
//        if( $from_version <= 1404301345 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        if( $from_version <= 1405061421 )
//        {
//            // ! important ! Use DBPREFIX_<table_name> for all tables
//
//            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//            self::applyDbUpgradeToAllDB( $sql );
//        }
//        // Please add your future database scheme changes here
//
//


    }
}