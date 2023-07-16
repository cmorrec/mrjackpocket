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

enum GAME_END_STATUS
{
    case JACK_WIN;
    case DETECTIVE_WIN;
    case PLAY_UNTIL_VISIBILITY;
    case NOT_GAME_END;
}

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
        // TODO THink about colors
        // // Set the colors of the players with HTML color code
        // // The default below is red/green/blue/orange/brown
        // // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        // $gameinfos = self::getGameinfos();
        // $default_colors = $gameinfos['player_colors'];
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
        // saving available_options in db
        $this->saveOptionsInDB(1, $this->getRandomOptions());

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

        $currentPlayerId = self::getCurrentPlayerId();
        $privateData = $this->getPrivateData($currentPlayerId);
        $publicData = $this->getPublicData();

        return array_merge($privateData, $publicData);
    }

    function getPublicData(): array
    {
        /**
         *      characters: {
         *          id,
         *          pos,
         *          isOpened,
         *          wallSide,
         *      }[],
         *      detectives: {
         *          id,
         *          pos,
         *      }[],
         *      currentOptions: {
         *          ability,
         *          wasUsed,
         *      }[],
         *      currentRound: {
         *          num,
         *          playUntilVisibility,
         *          activePlayerId,
         *          availableALibiCards: number;
         *      },
         *      previousRounds: {
         *          num,
         *          winPlayerId
         *      }[];
         */
        $result = array();
        $characters = $this->getCharacters();
        $result['characters'] = array_map(
            fn(array $character): array => array(
                'id' => $character['character_id'],
                'pos' => $character['tale_pos'],
                'isOpened' => $character['tale_is_opened'],
                'wallSide' => $character['wall_side'],
            ),
            $characters,
        );

        $detectives = $this->getDetectives();
        $result['detectives'] = array_map(
            fn(array $detective): array => array(
                'id' => $detective['detective_id'],
                'pos' => $detective['detective_pos'],
            ),
            $detectives,
        );

        $currentOptions = $this->getCurrentOptions();
        $result['currentOptions'] = array_map(
            fn(array $option): array => array(
                'ability' => $option['option'],
                'wasUsed' => $option['was_used'],
            ),
            $currentOptions,
        );

        $previousRounds = $this->getPreviousRounds();
        $result['previousRounds'] = array_map(
            fn(array $round): array => array(
                'num' => $round['round_num'],
                'winPlayerId' => $round['win_player_id'],
            ),
            $previousRounds,
        );

        $currentRound = $this->getLastRound();
        $availableALibiCards = $this->getAvailableAlibiCards();
        $activePlayer = $this->getActivePlayer();
        $result['currentRound'] = array(
            'num' => $currentRound['round_num'],
            'playUntilVisibility' => $currentRound['play_until_visibility'],
            'availableALibiCards' => count($availableALibiCards),
            'activePlayerId' => $activePlayer['player_id'],
        );

        return $result;
    }

    function getPrivateData(int $playerId): array
    {
        /*
         *      jack?: string,
         *      alibiCards: string[],
         *      winnedRounds: number[]; // maybe to public???
         */
        $result = array();
        $result['alibiCards'] = $this->getAlibiCardsByPlayerId($playerId);
        $result['winnedRounds'] = $this->getWinnedRoundsByPlayerId($playerId); // TODO remove it ???
        $player = $this->getPlayer($playerId);
        if ($player['player_is_jack']) {
            $jackCharacter = $this->getJackCharacter();
            $result['jackId'] = $jackCharacter['character_id'];
        }

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
        $currentRound = $this->getLastRound();
        $availableOptions = $this->getCurrentAvailableOptions();
        return (int) ((
            ($currentRound['round_num'] * 4 + count($availableOptions)) / $this->round_num)
            * 100);
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

    function getCurrentAvailableOptions(): array
    {
        $currentOptions = $this->getCurrentOptions();
        $availableOptions = array_filter(
            $currentOptions,
            fn(array $option) => !$option['was_used'],
        );

        return $availableOptions;
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
        $lastRound = $this->getLastRound();
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

    function getLastRound()
    {
        return self::getObjectFromDB("SELECT round_num, play_until_visibility FROM `round` ORDER BY round_num DESC LIMIT 1");
    }

    function getRounds()
    {
        return self::getObjectListFromDB("SELECT * FROM `round` ORDER BY round_num ASC");
    }

    function getLastRoundNum()
    {
        $lastRound = $this->getLastRound();
        return $lastRound['round_num'];
    }

    function getAlibiCardsByPlayerId(int $playerId): array
    {
        return self::getObjectListFromDB("SELECT character_id FROM character_status WHERE player_id_with_alibi = $playerId", true);
    }

    function getAvailableAlibiCards(): array
    {
        return self::getObjectListFromDB("SELECT * FROM character_status WHERE player_id_with_alibi IS NULL AND is_jack = FALSE");
    }

    function getWinnedRoundsByPlayerId(int $playerId): array
    {
        return self::getObjectListFromDB("SELECT round_num FROM `round` WHERE win_player_id = $playerId", true);
    }

    function getPlayer(int $playerId): array
    {
        return self::getObjectFromDB("SELECT * FROM player WHERE player_id = $playerId");
    }

    function getJackPlayer(): array
    {
        return self::getObjectFromDB("SELECT * FROM player WHERE player_is_jack = true");
    }

    function getDetectivePlayer(): array
    {
        return self::getObjectFromDB("SELECT * FROM player WHERE player_is_jack = false");
    }

    function getPlayers(): array
    {
        return self::getObjectListFromDB("SELECT * FROM player");
    }

    function getJackCharacter(): array
    {
        return self::getObjectFromDB("SELECT * FROM character_status WHERE is_jack = true");
    }

    function getCharacters(): array
    {
        return self::getObjectListFromDB("SELECT * FROM character_status");
    }

    function getCharacterById(string $characterId): array
    {
        return self::getObjectFromDB("SELECT * FROM character_status WHERE character_id = '$characterId'");
    }

    function getDetectives(): array
    {
        return self::getObjectListFromDB("SELECT * FROM detective_status");
    }

    function getDetectiveById(string $detectiveId): ?array
    {
        return self::getObjectFromDB("SELECT * FROM detective_status WHERE detective_id = '$detectiveId'");
    }

    function getCurrentOptions(): array
    {
        $lastRound = $this->getLastRoundNum();
        return self::getObjectListFromDB("SELECT * FROM available_options where round_num = $lastRound");
    }

    function getPreviousRounds(): array
    {
        return self::getObjectListFromDB("SELECT * FROM round where win_player_id is not NULL");
    }

    function getActivePlayer(): array
    {
        $currentOptions = $this->getCurrentOptions();
        if ($this->isJackTurn($currentOptions)) {
            return $this->getJackPlayer();
        } else {
            return $this->getDetectivePlayer();
        }
    }

    function getNotActivePlayer(): array
    {
        $currentOptions = $this->getCurrentOptions();
        if ($this->isJackTurn($currentOptions)) {
            return $this->getDetectivePlayer();
        } else {
            return $this->getJackPlayer();
        }
    }

    function isJackTurn(array $currentOptions): bool
    {
        $currentRoundNum = $this->getLastRoundNum();
        $usedOptions = count(
            array_filter(
                $currentOptions,
                fn(array $option) => $option['was_used'],
                ARRAY_FILTER_USE_BOTH,
            ),
        );
        if ($currentRoundNum % 2 === 1) {
            if ($usedOptions === 0 || $usedOptions === 3) {
                return false;
            } else {
                return true;
            }
        } else {
            if ($usedOptions === 0 || $usedOptions === 3) {
                return true;
            } else {
                return false;
            }
        }
    }

    function getMetaCharacterById(string $characterId): ?array
    {
        return $this->array_find(
            $this->characters,
            fn(array $character) => $character['id'] === $characterId,
        );
    }

    function getMetaDetectiveById(string $detectiveId): ?array
    {
        return $this->array_find(
            $this->detectives,
            fn(array $detective) => $detective['id'] === $detectiveId,
        );
    }

    function useOption(string $option)
    {
        $sql = "UPDATE available_options SET was_used = true WHERE was_used = false AND `option` = '$option' LIMIT 1";
        self::DbQuery($sql);
    }

    function closeCharacters(array $characters)
    {
        if (count($characters) === 0) {
            return;
        }

        $characterIds = implode(
            array_map(
                fn(array $character) => "'" . $character['character_id'] . ".'",
                $characters,
            ),
            ',',
        );
        $sql = "UPDATE character_status SET tale_is_opened = false WHERE character_id in ($characterIds)";
        self::DbQuery($sql);

        $manyRoadsCharacter = $this->array_find(
            $this->characters,
            fn(array $metaCharacter) => $metaCharacter['closed_roads'] === 4,
        );
        $manyRoadsCharacterId = $manyRoadsCharacter['id'];
        $isManyRoadsCharacterClosed = $this->array_any(
            $characters,
            fn(array $character) => $manyRoadsCharacterId === $character['character_id'],
        );
        if ($isManyRoadsCharacterClosed) {
            $sql = "UPDATE character_status SET wall_side = NULL WHERE character_id = '$manyRoadsCharacterId'";
            self::DbQuery($sql);
        }
    }

    function array_any(array $array, callable $fn): bool
    {
        foreach ($array as $value) {
            if ($fn($value)) {
                return true;
            }
        }
        return false;
    }

    function array_find(array $array, callable $fn): ?array
    {
        foreach ($array as $value) {
            if ($fn($value)) {
                return $value;
            }
        }
        return null;
    }

    function array_every(array $array, callable $fn): bool
    {
        foreach ($array as $value) {
            if (!$fn($value)) {
                return false;
            }
        }
        return true;
    }

    function getVisibleCharacters(array $characters, array $detectives): array
    {
        $result = array();
        foreach ($detectives as $detective) {
            $detectivePos = $this->detective_pos[$detective['detective_id']];
            if ($detectivePos['x'] !== 0 && $detectivePos['x'] !== 4) {
                $visibleCharacters = $this->getVisibleCharactersForColumn($characters, $detectivePos['x'], $detectivePos['y'] === 0);
            } else {
                $visibleCharacters = $this->getVisibleCharactersForRow($characters, $detectivePos['y'], $detectivePos['x'] === 0);
            }
            foreach ($visibleCharacters as $visibleCharacter) {
                $isCharacterFoundAlready = $this->array_any(
                    $result,
                    fn(array $character) => $character['character_id'] === $visibleCharacter['character_id'],
                );
                if (!$isCharacterFoundAlready) {
                    $result[] = $visibleCharacter;
                }
            }
        }

        return $result;
    }

    function getVisibleCharactersForColumn(array $characters, int $column, bool $isUpper): array
    {
        $columnCharacters = $this->getCharactersForLine(
            $characters,
            $isUpper,
            'x',
            'y',
            $column,
        );

        if ($isUpper) {
            $closestWall = 'up';
            $farthestWall = 'down';
        } else {
            $closestWall = 'down';
            $farthestWall = 'up';
        }

        return $this->getVisibleCharactersForLine(
            $columnCharacters,
            $closestWall,
            $farthestWall,
        );
    }

    function getVisibleCharactersForRow(array $characters, int $row, bool $isLefter): array
    {
        $rowCharacters = $this->getCharactersForLine(
            $characters,
            $isLefter,
            'y',
            'x',
            $row,
        );

        if ($isLefter) {
            $closestWall = 'left';
            $farthestWall = 'right';
        } else {
            $closestWall = 'right';
            $farthestWall = 'left';
        }

        return $this->getVisibleCharactersForLine(
            $rowCharacters,
            $closestWall,
            $farthestWall,
        );
    }

    function getSortKef(bool $asc): int
    {
        if ($asc) {
            return 1;
        }

        return -1;
    }

    function getCharactersForLine(
        array $characters,
        bool $asc,
        string $axis,
        string $normalAxis,
        int $lineNumber,
    ): array {
        $lineCharacters = array_filter(
            $characters,
            fn(array $character) => (
                $this->character_pos[$character['tale_pos']][$axis] === $lineNumber
            ),
            ARRAY_FILTER_USE_BOTH,
        );
        usort(
            $lineCharacters,
            fn($a, $b) => (
                $this->character_pos[$a['tale_pos']][$normalAxis]
                - $this->character_pos[$b['tale_pos']][$normalAxis]
            ) * $this->getSortKef($asc),
        );

        return $lineCharacters;
    }

    function getVisibleCharactersForLine(
        array $lineCharacters,
        string $closestWall,
        string $farthestWall
    ) {
        $result = array();
        foreach ($lineCharacters as $character) {
            $wallSide = $character['wall_side'];

            if ($wallSide === $closestWall) {
                return $result;
            }

            $result[] = $character;

            if ($wallSide === $farthestWall) {
                return $result;
            }
        }

        return $result;
    }

    function checkAction(int $playerId, string $action)
    {
        $activePlayer = $this->getActivePlayer();
        if ($activePlayer['player_id'] !== $playerId) {
            throw new BgaUserException(self::_("You are not an active player. You can't play now"));
        }
        $currentOptions = $this->getCurrentOptions();
        $isAvailableAction = $this->array_any(
            $currentOptions,
            fn(array $option) => !$option['was_used'] && $option['option'] === $action,
        );
        if (!$isAvailableAction) {
            throw new BgaUserException(self::_("This action is not available. You can't use it now"));
        }
    }

    function checkRotation(string $taleId, ?string $wallSide)
    {
        $metaCharacter = $this->getMetaCharacterById($taleId);
        if (is_null($metaCharacter)) {
            throw new BgaUserException(self::_("You can't rotate tale with id = $taleId. It doesn't exist"));
        }

        $character = $this->getCharacterById($taleId);
        $canIgnoreRotationCheck = !$character['tale_is_opened'] && $metaCharacter['closed_roads'] === 4;
        if (!$canIgnoreRotationCheck && $character['wall_side'] === $wallSide) {
            throw new BgaUserException(self::_("You can't stay tale as it is. You should rotate it"));
        }
        if (!$canIgnoreRotationCheck && is_null($wallSide)) {
            throw new BgaUserException(self::_("You can't stay tale as it is. You should rotate it"));
        }

        $isValidWallSide = is_null($wallSide) || $this->array_any(
            $this->wall_sides,
            fn(string $side) => $side === $wallSide,
        );
        if (!$isValidWallSide) {
            throw new BgaUserException(self::_("You can't rotate tale to $wallSide. This side doesn't exist"));
        }

        $currentRoundNum = $this->getLastRoundNum();
        if ($currentRoundNum === $character['last_round_rotated']) {
            throw new BgaUserException(self::_("You can't rotate this tale. It already was rotated in the current round"));
        }
    }

    function checkExchanging(string $taleId1, string $taleId2)
    {
        if ($taleId1 === $taleId2) {
            throw new BgaUserException(self::_("You can't exchange the tale with itself. Please, choose the different tales"));
        }

        $metaCharacter1 = $this->getMetaCharacterById($taleId1);
        if (is_null($metaCharacter1)) {
            throw new BgaUserException(self::_("You can't exchange tale with id = $taleId1. It does not exist"));
        }

        $metaCharacter2 = $this->getMetaCharacterById($taleId2);
        if (is_null($metaCharacter2)) {
            throw new BgaUserException(self::_("You can't exchange tale with id = $taleId2. It does not exist"));
        }
    }

    function checkJocker(int $playerId, ?string $detectiveId, ?int $newPos)
    {
        if (is_null($detectiveId) || is_null($newPos)) {
            $jackPlayer = $this->getJackPlayer();
            if ($jackPlayer['player_id'] !== $playerId) {
                throw new BgaUserException(self::_("You can't stay detectives as they are. Only Jack can do it"));
            }

            return;
        }

        $metaDetective = $this->getMetaDetectiveById($detectiveId);
        if (is_null($metaDetective)) {
            throw new BgaUserException(self::_("You can't move detective with id = $detectiveId. This detective does not exist"));
        }

        $metaNewPos = $this->detective_pos[$newPos];
        if (is_null($metaNewPos)) {
            throw new BgaUserException(self::_("You can't move detective to $newPos. This position doesn't exist"));
        }

        $detective = $this->getDetectiveById($detectiveId);
        $oldPos = $detective['detective_pos'];
        $difference = $this->getDifferencePos($oldPos, $newPos);
        if ($difference !== 1) {
            throw new BgaUserException(self::_("You can't move detective to $newPos. Jocker allows to move it only for one step ahead"));
        }
    }

    function checkDetective(string $detectiveId, int $newPos)
    {
        $metaDetective = $this->getMetaDetectiveById($detectiveId);
        if (is_null($metaDetective)) {
            throw new BgaUserException(self::_("You can't move detective with id = $detectiveId. This detective does not exist"));
        }

        $metaNewPos = $this->detective_pos[$newPos];
        if (is_null($metaNewPos)) {
            throw new BgaUserException(self::_("You can't move detective to $newPos. This position doesn't exist"));
        }

        $detective = $this->getDetectiveById($detectiveId);
        $oldPos = $detective['detective_pos'];
        $difference = $this->getDifferencePos($oldPos, $newPos);
        if (!($difference === 1 || $difference === 2)) {
            throw new BgaUserException(self::_("You can't move detective to $newPos. Detective can move only for 1 or 2 steps ahead"));
        }
    }

    function getDifferencePos($oldPos, $newPos): int
    {
        if ($newPos >= $oldPos) {
            return $newPos - $oldPos;
        } else {
            return $newPos - $oldPos + count($this->detective_pos);
        }
    }

    //////////////////////////////////////////////////////////////////////////////
//////////// Player actions
//////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in mrjackpocket.action.php)
    */

    function rotateTale(int $playerId, string $taleId, ?string $wallSide)
    {
        /**
         * 1) check ability of player to do it
         * 2) rotate
         * 3) go to the state next turn
         */
        $action = 'rotation';
        $this->checkAction($playerId, $action);
        $this->checkRotation($taleId, $wallSide);

        $currentRound = $this->getLastRoundNum();
        if (is_null($wallSide)) {
            $sql = "UPDATE character_status SET wall_side = NULL, last_round_rotated = $currentRound WHERE character_id = $taleId";
        } else {
            $sql = "UPDATE character_status SET wall_side = $wallSide, last_round_rotated = $currentRound WHERE character_id = $taleId";
        }
        self::DbQuery($sql);

        $this->useOption($action);

        $this->gamestate->nextState('nextTurn');
        // TODO notify about rotation
    }

    function exchangeTales(int $playerId, string $taleId1, string $taleId2)
    {
        /**
         * 1) check ability of player to do it
         * 2) exchange
         * 3) go to the state next turn
         */
        $action = 'exchange';
        $this->checkAction($playerId, $action);
        $this->checkExchanging($taleId1, $taleId2);

        $character1 = $this->getCharacterById($taleId1);
        $character2 = $this->getCharacterById($taleId2);
        $pos1 = $character1['tale_pos'];
        $pos2 = $character2['tale_pos'];
        $sql = "UPDATE character_status SET tale_pos = $pos2 WHERE tale_pos = $taleId1";
        self::DbQuery($sql);
        $sql = "UPDATE character_status SET tale_pos = $pos1 WHERE tale_pos = $taleId2";
        self::DbQuery($sql);

        $this->useOption($action);

        $this->gamestate->nextState('nextTurn');
        // TODO notify about exchange
    }

    function jocker(int $playerId, ?string $detectiveId, ?int $newPos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
        $action = 'jocker';
        $this->checkAction($playerId, $action);
        $this->checkJocker($playerId, $detectiveId, $newPos);

        if (!is_null($detectiveId) && !is_null($newPos)) {
            $sql = "UPDATE detective_status SET detective_pos = $newPos WHERE detective_id = '$detectiveId'";
            self::DbQuery($sql);
        }

        $this->useOption($action);

        $this->gamestate->nextState('nextTurn');
        // TODO notify about jocker move
    }

    function holmes(int $playerId, int $newPos)
    {
        $action = 'holmes';
        $this->moveDetective($playerId, $action, $newPos);
    }

    function watson(int $playerId, int $newPos)
    {
        $action = 'watson';
        $this->moveDetective($playerId, $action, $newPos);
    }

    function dog(int $playerId, int $newPos)
    {
        $action = 'dog';
        $this->moveDetective($playerId, $action, $newPos);
    }

    function moveDetective(int $playerId, string $action, int $newPos)
    {
        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
        $this->checkAction($playerId, $action);
        $this->checkDetective($action, $newPos);

        $sql = "UPDATE detective_status SET detective_pos = $newPos WHERE detective_id = '$action'";
        self::DbQuery($sql);

        $this->useOption($action);

        $this->gamestate->nextState('nextTurn');
        // TODO notify about detective
    }

    function alibi(int $playerId)
    {
        /**
         * 1) check ability of player to do it
         * 2) pull
         * 3) go to the state next turn
         */
        $action = 'alibi';
        $this->checkAction($playerId, $action);

        $availableAlibiCards = $this->getAvailableAlibiCards();
        $randomNum = bga_rand(0, count($availableAlibiCards) - 1);
        $alibiCharacter = $availableAlibiCards[$randomNum];
        $randomCharacterId = $alibiCharacter['character_id'];
        $sql = "UPDATE character_status SET player_id_with_alibi = $playerId WHERE character_id = '$randomCharacterId'";
        self::DbQuery($sql);
        $jackPlayer = $this->getJackPlayer();
        if ($jackPlayer['player_id'] === $playerId) {
            // TODO notify only him and everyone as a secret
        } else {
            $this->closeCharacters([$alibiCharacter]);
            // TODO notify all
        }

        $this->useOption($action);

        $this->gamestate->nextState('nextTurn');
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

    function stNextTurn()
    {
        /**
         * 1) define is it the end of round. if it is -- go to the state end of round
         * 2) if it is not - determine the next active player and go to the statuus playerTurn 
         */
        $availableOptions = $this->getCurrentAvailableOptions();
        $availableOptionsNum = count($availableOptions);
        if ($availableOptionsNum === 0) {
            $this->gamestate->nextState('roundEnd');
            return;
        }

        if ($availableOptionsNum === 1 || $availableOptionsNum === 3) {
            $notActivePlayer = $this->getNotActivePlayer();
            $this->gamestate->changeActivePlayer($notActivePlayer['player_id']);
        }
        $this->gamestate->nextState('playerTurn');
        // TODO notify about next player ???
    }

    function stEndOfRound()
    {
        /**
         * 1) isVisible
         * 2) do some characters closed (and change wallSide to null if 4-roads)
         * 3) update round table
         * 4) if end of the game go to the end of game state
         * 5) add round
         * 5) generate availableOptions
         * 6) change active player
         * 7) go to the playerTurn
         */
        $characters = $this->getCharacters();
        $detectives = $this->getDetectives();
        $visibleCharacters = $this->getVisibleCharacters($characters, $detectives);
        $isJackVisible = $this->array_any(
            $visibleCharacters,
            fn(array $character) => $character['is_jack'],
        );

        if (!$isJackVisible) {
            $charactersToClose = $visibleCharacters;
        } else {
            $charactersToClose = array_filter(
                $characters,
                fn(array $character) => !$this->array_any(
                    $visibleCharacters,
                    fn(array $visibleCharacter) => $visibleCharacter['character_id'] === $character['character_id'],
                ),
                ARRAY_FILTER_USE_BOTH,
            );
        }
        $charactersToClose = array_filter(
            $charactersToClose,
            fn(array $character) => $character['tale_is_opened'],
            ARRAY_FILTER_USE_BOTH,
        );

        $this->closeCharacters($charactersToClose);

        $detectivePlayer = $this->getDetectivePlayer();
        $detectivePlayerId = $detectivePlayer['player_id'];
        $jackPlayer = $this->getJackPlayer();
        $jackPlayerId = $jackPlayer['player_id'];
        if ($isJackVisible) {
            $winPlayerId = $detectivePlayerId;
        } else {
            $winPlayerId = $jackPlayerId;
        }
        $currentRoundNum = $this->getLastRoundNum();
        $sql = "UPDATE `round` SET is_criminal_visible = $isJackVisible, win_player_id = $winPlayerId WHERE round_num = $currentRoundNum;";
        self::DbQuery($sql);


        $gameEndStatus = $this->getGameEndStatus($isJackVisible, $jackPlayerId);
        if ($gameEndStatus === GAME_END_STATUS::PLAY_UNTIL_VISIBILITY) {
            $sql = "UPDATE `round` SET play_until_visibility = true WHERE round_num = $currentRoundNum;";
            self::DbQuery($sql);
        }

        if ($gameEndStatus === GAME_END_STATUS::DETECTIVE_WIN || $gameEndStatus === GAME_END_STATUS::JACK_WIN) {
            $this->gamestate->nextState('gameEnd');
            // TODO notify about changin player, end of round, new options, 
        }

        $this->addRound();
        $this->saveOptionsInDB(
            $currentRoundNum + 1,
            $this->getRandomOptions(),
        );

        if (($currentRoundNum + 1) % 2 === 0) {
            $nextActivePlayerId = $jackPlayerId;
        } else {
            $nextActivePlayerId = $detectivePlayerId;
        }
        $this->gamestate->changeActivePlayer($nextActivePlayerId);
        // TODO notify about changin player, end of round, new options, visiblity and closed characters

        $this->gamestate->nextState('playerTurn');
    }

    function getGameEndStatus(
        bool $isJackVisible,
        string $jackPlayerId,
    ): GAME_END_STATUS {
        $characters = $this->getCharacters();
        $rounds = $this->getRounds();
        $currentRoundNum = count($rounds);
        $jackWinRounds = count(
            array_filter(
                $rounds,
                fn(array $round) => $round['win_player_id'] === $jackPlayerId,
                ARRAY_FILTER_USE_BOTH,
            ),
        );
        $jackAlibiCharacters = array_filter(
            $characters,
            fn(array $character) => $character['player_id_with_alibi'] === $jackPlayerId,
            ARRAY_FILTER_USE_BOTH,
        );
        $jackAlibiBonuses = array_reduce(
            $jackAlibiCharacters,
            function (int $acc, array $character) {
                $metaCharacter = $this->getMetaCharacterById($character['character_id']);
                return $acc + $metaCharacter['points'];
            },
            0,
        );
        $isJackWin = ($jackWinRounds + $jackAlibiBonuses) >= 6;
        $openedCharacters = array_filter(
            $characters,
            fn(array $character) => $character['tale_is_opened'],
            ARRAY_FILTER_USE_BOTH,
        );
        $isDetectiveWin = count($openedCharacters) === 1;
        if ($isDetectiveWin && $isJackWin) {
            if ($isJackVisible) {
                return GAME_END_STATUS::DETECTIVE_WIN;
            } else if ($currentRoundNum === $this->round_num) {
                return GAME_END_STATUS::JACK_WIN;
            } else {
                return GAME_END_STATUS::PLAY_UNTIL_VISIBILITY;
            }
        }

        if ($isDetectiveWin) {
            return GAME_END_STATUS::DETECTIVE_WIN;
        }

        if ($isJackWin || $currentRoundNum === $this->round_num) {
            return GAME_END_STATUS::JACK_WIN;
        }

        return GAME_END_STATUS::NOT_GAME_END;
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