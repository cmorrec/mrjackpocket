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
 * mrjackpocket.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');

// enum GAME_END_STATUS
// {
//     case JACK_WIN;
//     case DETECTIVE_WIN;
//     case PLAY_UNTIL_VISIBILITY;
//     case NOT_GAME_END;
// }

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

        // $jackPlayerId = $players[$jackIndex]['player_id'];

        // saving player in db
        $default_color = array("ff0000", "008000", "0000ff", "ffa500");
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar, player_is_jack, player_no) VALUES ";
        $values = array();
        $index = 0;
        foreach ($players as $player_id => $player) {
            $playerIsJack = (int) $index === $jackIndex;
            if ($playerIsJack == 1) {
                $jackPlayerId = (int) $player_id;
            } else {
                $detectivePlayerId = (int) $player_id;
            }
            $color = array_shift($default_color);
            $player_no = ((int) $playerIsJack === 1) + 1;
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "','" . $playerIsJack . "','" . $player_no . "')";
            $index += 1;
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
            $pos = $posArray[$index] + 1;
            $tales[] = array(
                'character_id' => $character['id'],
                'tale_pos' => $pos,
                'is_jack' => (int) ($character['id'] === $jackId),
                'wall_side' => $this->getInitialWallSide($pos),
            );
        }

        // saving character_status in db
        $sql = "INSERT INTO character_status (character_id, tale_pos, is_jack, wall_side) VALUES ";
        $values = array();
        foreach ($tales as $tale) {
            $values[] = "('" . $tale['character_id'] . "','" . $tale['tale_pos'] . "','" . $tale['is_jack'] . "','" . $tale['wall_side'] . "')";
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
        self::initStat('table', 'closed_characters', 0);
        self::initStat('table', 'jack_win', false);
        self::initStat('table', 'draw', false);
        self::initStat('table', 'last_round', 1);
        self::initStat('player', 'winned_rounds', 0, $detectivePlayerId);
        self::initStat('player', 'is_win', false, $detectivePlayerId);
        self::initStat('player', 'is_jack', false, $detectivePlayerId);
        self::initStat('player', 'num_rounds', 0, $detectivePlayerId);
        self::initStat('player', 'winned_rounds', 0, $jackPlayerId);
        self::initStat('player', 'is_win', false, $jackPlayerId);
        self::initStat('player', 'is_jack', true, $jackPlayerId);
        self::initStat('player', 'num_rounds', 0, $jackPlayerId);

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
                'pos' => (int) $character['tale_pos'],
                'isOpened' => $character['tale_is_opened'] === '1',
                'wallSide' => $character['wall_side'],
                'lastRoundRotated' => (int) $character['last_round_rotated'],
            ),
            $characters,
        );

        $detectives = $this->getDetectives();
        $result['detectives'] = array_map(
            fn(array $detective): array => array(
                'id' => $detective['detective_id'],
                'pos' => (int) $detective['detective_pos'],
            ),
            $detectives,
        );

        $currentOptions = $this->getCurrentOptions();
        $result['currentOptions'] = array_map(
            fn(array $option): array => array(
                'ability' => $option['option'],
                'wasUsed' => $option['was_used'] === '1',
            ),
            $currentOptions,
        );
        $currentRound = $this->getLastRound();
        $currentRoundNum = (int) $currentRound['round_num'];

        if ($currentRoundNum % 2 === 1) {
            $nextOptions = $this->getRevertOptions();
            $result['nextOptions'] = array_map(
                fn(string $option): array => array(
                    'ability' => $option,
                    'wasUsed' => false,
                ),
                $nextOptions,
            );
        }

        $previousRounds = $this->getPreviousRounds();
        $result['previousRounds'] = array_map(
            fn(array $round): array => array(
                'num' => $round['round_num'],
                'winPlayerId' => $round['win_player_id'],
                'isCriminalVisible' => $round['is_criminal_visible'] === '1',
            ),
            $previousRounds,
        );

        $availableALibiCards = $this->getAvailableAlibiCards();
        $activePlayer = $this->getActivePlayer();
        $result['currentRound'] = array(
            'num' => $currentRoundNum,
            'playUntilVisibility' => $currentRound['play_until_visibility'] === '1',
            'availableALibiCards' => count($availableALibiCards),
            'activePlayerId' => (int) $activePlayer['player_id'],
        );

        $result['meta'] = array(
            'roundNum' => $this->round_num,
            'characterPos' => $this->character_pos,
            'detectivePos' => $this->detective_pos,
            'characters' => $this->characters,
            'detectives' => $this->detectives,
        );

        $result['visibleCharacters'] = array_map(
            fn(array $character): array => array(
                'id' => $character['character_id'],
                'pos' => (int) $character['tale_pos'],
                'isOpened' => $character['tale_is_opened'] === '1',
                'wallSide' => $character['wall_side'],
            ),
            $this->getVisibleCharacters($characters, $detectives),
        );
        $result['detectiveAlibiCards'] = $this->getDetectiveAlibiCards();
        $jackPlayer = $this->getJackPlayer();
        $result['jackPlayerId'] = (int) $jackPlayer['player_id'];
        $detectivePlayer = $this->getDetectivePlayer();
        $result['detectivePlayerId'] = (int) $detectivePlayer['player_id'];

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
        $result['winnedRounds'] = $this->getWinnedRoundsByPlayerId($playerId); // TODO remove it ???
        $jackPlayer = $this->getJackPlayer();
        $jackPlayerId = (int) $jackPlayer['player_id'];
        $jackALibiCards = $this->getAlibiCardsByPlayerId($jackPlayerId);
        $result['jackAlibiCardsNum'] = count($jackALibiCards);
        $result['playersInfo'] = array_map(
            fn(array $player): array => array(
                'player_id' => $player['player_id'],
                'player_is_jack' => $player['player_is_jack'],
            ),
            $this->getPlayers(),
        );
        $player = $this->getPlayer($playerId);
        if (is_null($player)) {
            return $result;
        }
        if ($player['player_is_jack'] === '1') {
            $jackCharacter = $this->getJackCharacter();
            $result['jackId'] = $jackCharacter['character_id'];
            $result['jackAlibiCards'] = $jackALibiCards;
        }

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
        100 / (8 * 4) = 100 / 32 =~ 3
        0 = ((1 - 1) * 4 + 0) * 3 = 0
        3 = ((1 - 1) * 4 + 1) * 3 = 0
        100 = ((8 - 1) * 4 + 4) * 3 = 0
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $currentRound = $this->getLastRound();
        $availableOptions = $this->getCurrentAvailableOptions();
        $MAX_AVAILABLE_OPTIONS = 4;
        $closedOptions = $MAX_AVAILABLE_OPTIONS - count($availableOptions);
        $part = 100 / ($this->round_num * $MAX_AVAILABLE_OPTIONS);

        return (int) ($part * (($currentRound['round_num'] - 1) * $MAX_AVAILABLE_OPTIONS + $closedOptions));
    }

    //////////////////////////////////////////////////////////////////////////////
//////////// Utility functions
////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getInitialWallSide($tale_pos)
    {
        if (array_key_exists($tale_pos, $this->init_tale_rotations)) {
            return $this->init_tale_rotations[$tale_pos];
        }

        $side = bga_rand(1, 4);
        return $this->wall_sides[$side];
    }

    // TODO check algo, maybe here you will find an errors
    function getRandomPosArray(int $length): array
    {
        $result = array();
        $numbers = range(0, $length - 1);
        foreach ($numbers as $number) {
            $rand = bga_rand(0, $length - 1 - $number);
            foreach ($numbers as $randIndex) {
                // . . . . 
                // . . 0 . rand = 2 
                // . . 0 1 rand = 2
                if ($rand === 0) {
                    if (array_key_exists($randIndex, $result)) {
                        continue;
                    } else {
                        $result[$randIndex] = $number;
                        break;
                    }
                } else {
                    $rand--;
                }
            }
        }

        return $result;
    }

    function getRandomOptions(): array
    {
        return array_map(
            fn(array $options): string => $options[bga_rand(0, 1)],
            $this->options_to_move,
        );
    }

    function getRevertOptions(): array
    {
        $currentOptions = $this->getCurrentOptions();
        return array_map(
            // fn(int $index, array $options): string => $options[0] === $currentOptions[$index] ? $options[1] : $options[0],
            // array_keys($this->options_to_move),
            // array_values($this->options_to_move)
            fn(int $index, array $options) => $this->array_find(
                $options,
                fn(string $option) => $option !== $currentOptions[$index]['option'],
            ),
            array_keys($this->options_to_move),
            array_values($this->options_to_move)
        );
    }

    function getCurrentAvailableOptions(): array
    {
        $currentOptions = $this->getCurrentOptions();
        $availableOptions = array_filter(
            $currentOptions,
            fn(array $option) => $option['was_used'] === '0',
        );

        return $availableOptions;
    }

    function saveOptionsInDB(int $roundNum, array $options): void
    {
        $sql = "INSERT INTO available_options (round_num, `option`, `index`) VALUES ";
        $values = array();

        foreach ($options as $index => $option) {
            $values[] = "('" . $roundNum . "','" . $option . "','" . $index . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
    }

    function addRound(): void
    {
        $lastRound = $this->getLastRound();
        if (!is_null($lastRound) && ((int) $lastRound['round_num']) == 8) {
            return;
        }
        if (is_null($lastRound)) {
            $roundNum = 1;
            $playUntilVisibility = 0;
        } else {
            $roundNum = ((int) $lastRound['round_num']) + 1;
            $playUntilVisibility = (int) $lastRound['play_until_visibility'];
        }

        $sql = "INSERT INTO `round` (round_num, play_until_visibility) VALUES ('$roundNum', '$playUntilVisibility')";
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
        return (int) $lastRound['round_num'];
    }

    function getAlibiCardsByPlayerId(int $playerId): array
    {
        return self::getObjectListFromDB("SELECT character_id FROM character_status WHERE player_id_with_alibi = $playerId", true);
    }

    function getDetectiveAlibiCards(): array
    {
        $playerDetective = $this->getDetectivePlayer();
        $playerDetectiveId = (int) $playerDetective['player_id'];
        return $this->getAlibiCardsByPlayerId($playerDetectiveId);
    }

    function getAvailableAlibiCards(): array
    {
        return self::getObjectListFromDB("SELECT * FROM character_status WHERE player_id_with_alibi IS NULL AND is_jack = '0'");
    }

    function getWinnedRoundsByPlayerId(int $playerId): array
    {
        return self::getObjectListFromDB("SELECT round_num FROM `round` WHERE win_player_id = $playerId", true);
    }

    function getPlayer(int $playerId): ?array
    {
        return self::getObjectFromDB("SELECT * FROM player WHERE player_id = $playerId");
    }

    function getJackPlayer(): array
    {
        return self::getObjectFromDB("SELECT * FROM player WHERE player_is_jack = '1'");
    }

    function getDetectivePlayer(): array
    {
        return self::getObjectFromDB("SELECT * FROM player WHERE player_is_jack = '0'");
    }

    function getPlayers(): array
    {
        return self::getObjectListFromDB("SELECT * FROM player");
    }

    function getJackCharacter(): array
    {
        return self::getObjectFromDB("SELECT * FROM character_status WHERE is_jack = '1'");
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
        return self::getObjectListFromDB("SELECT * FROM available_options where round_num = $lastRound ORDER BY `index` ASC");
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
                fn(array $option) => $option['was_used'] !== '0',
                ARRAY_FILTER_USE_BOTH,
            ),
        );
        if ($usedOptions === 0 || $usedOptions === 3) {
            return $currentRoundNum % 2 === 0;
        } else {
            return $currentRoundNum % 2 === 1;
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
        $sql = "UPDATE available_options SET was_used = '1' WHERE was_used = '0' AND `option` = '$option' LIMIT 1";
        self::DbQuery($sql);
    }

    function closeCharacters(array $characters)
    {
        if (count($characters) === 0) {
            return;
        }

        $characterIds = implode(
            array_map(
                fn(array $character) => "'" . $character['character_id'] . "'",
                $characters,
            ),
            ',',
        );
        $sql = "UPDATE character_status SET tale_is_opened = '0' WHERE character_id in ($characterIds)";
        self::DbQuery($sql);
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

    function array_find(array $array, callable $fn)
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
            $detectivePos = $this->detective_pos[(int) $detective['detective_pos']];
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
        int $lineNumber
    ): array {
        $lineCharacters = array_filter(
            $characters,
            fn(array $character) => (
                $this->character_pos[(int) $character['tale_pos']][$axis] === $lineNumber
            ),
            ARRAY_FILTER_USE_BOTH,
        );
        usort(
            $lineCharacters,
            fn($a, $b) => (
                $this->character_pos[(int) $a['tale_pos']][$normalAxis]
                - $this->character_pos[(int) $b['tale_pos']][$normalAxis]
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
            $manyRoadsCharacter = $this->array_find(
                $this->characters,
                fn(array $metaCharacter) => $metaCharacter['closed_roads'] === 4,
            );
            $isManyRoadsCharacter = $character['character_id'] === $manyRoadsCharacter['id']
                && $character['tale_is_opened'] === '0';

            if ($wallSide === $closestWall && !$isManyRoadsCharacter) {
                return $result;
            }

            $result[] = $character;

            if ($wallSide === $farthestWall && !$isManyRoadsCharacter) {
                return $result;
            }
        }

        return $result;
    }

    function checkActionCustom(int $playerId, string $action)
    {
        $activePlayer = $this->getActivePlayer();
        if (((int) $activePlayer['player_id']) !== ((int) $playerId)) {
            throw new BgaUserException(self::_("You are not an active player. You can't play now"));
        }
        $currentOptions = $this->getCurrentOptions();
        $isAvailableAction = $this->array_any(
            $currentOptions,
            fn(array $option) => $option['was_used'] === '0' && $option['option'] === $action,
        );
        if (!$isAvailableAction) {
            throw new BgaUserException(self::_("This action is not available. You can't use it now"));
        }
    }

    function checkRotation(string $taleId, ?string $wallSide)
    {
        $metaCharacter = $this->getMetaCharacterById($taleId);
        if (is_null($metaCharacter)) {
            throw new BgaUserException(
                sprintf(self::_('You can not rotate tale with id = %s. It does not exist'), $taleId),
            );
        }

        $character = $this->getCharacterById($taleId);
        if ($character['wall_side'] === $wallSide) {
            throw new BgaUserException(self::_("You can't stay tale as it is. You should rotate it"));
        }
        if (is_null($wallSide)) {
            throw new BgaUserException(self::_("You can't stay tale as it is. You should rotate it"));
        }

        $isValidWallSide = $this->array_any(
            $this->wall_sides,
            fn(string $side) => $side === $wallSide,
        );
        if (!$isValidWallSide) {
            throw new BgaUserException(self::_("You can't rotate tale to this side. It doesn't exist"));
        }

        $currentRoundNum = $this->getLastRoundNum();
        if ($currentRoundNum === ((int) $character['last_round_rotated'])) {
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
            throw new BgaUserException(
                sprintf(self::_('You can not exchange tale with id = %s. It does not exist'), $taleId1),
            );
        }

        $metaCharacter2 = $this->getMetaCharacterById($taleId2);
        if (is_null($metaCharacter2)) {
            throw new BgaUserException(
                sprintf(self::_('You can not exchange tale with id = %s. It does not exist'), $taleId2),
            );
        }
    }

    function checkJocker(int $playerId, ?string $detectiveId, ?int $newPos)
    {
        if (is_null($detectiveId) || is_null($newPos)) {
            $jackPlayer = $this->getJackPlayer();
            if (((int) $jackPlayer['player_id']) !== ((int) $playerId)) {
                throw new BgaUserException(self::_("You can't stay detectives as they are. Only Jack can do it"));
            }

            return;
        }

        $metaDetective = $this->getMetaDetectiveById($detectiveId);
        if (is_null($metaDetective)) {
            throw new BgaUserException(
                sprintf(self::_('You can not move detective with id = %s. This detective does not exist'), $detectiveId),
            );
        }

        $metaNewPos = $this->detective_pos[$newPos];
        if (is_null($metaNewPos)) {
            throw new BgaUserException(
                sprintf(self::_('You can not move detective to %d. This position does not exist'), $newPos),
            );
        }

        $detective = $this->getDetectiveById($detectiveId);
        $oldPos = (int) $detective['detective_pos'];
        $difference = $this->getDifferencePos($oldPos, $newPos);
        if ($difference !== 1) {
            throw new BgaUserException(
                sprintf(self::_('You can not move detective to %d. Jocker allows to move it only for one step ahead'), $newPos),
            );
        }
    }

    function checkDetective(string $detectiveId, int $newPos)
    {
        $metaDetective = $this->getMetaDetectiveById($detectiveId);
        if (is_null($metaDetective)) {
            throw new BgaUserException(
                sprintf(self::_('You can not move detective with id = %s. This detective does not exist'), $detectiveId),
            );
        }

        $metaNewPos = $this->detective_pos[$newPos];
        if (is_null($metaNewPos)) {
            throw new BgaUserException(
                sprintf(self::_('You can not move detective to %d. This position does not exist'), $newPos),
            );
        }

        $detective = $this->getDetectiveById($detectiveId);
        $oldPos = (int) $detective['detective_pos'];
        $difference = $this->getDifferencePos($oldPos, $newPos);
        if (!($difference === 1 || $difference === 2)) {
            throw new BgaUserException(
                sprintf(self::_('You can not move detective to %d. Detective can move only for 1 or 2 steps ahead'), $newPos),
            );
        }
    }

    function getDifferencePos(int $oldPos, int $newPos): int
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

    function rotateTale(string $taleId, string $wallSide, ?int $playerId)
    {
        self::checkAction( "rotate" );
        if (is_null($playerId)) {
            $playerId = (int) $this->getCurrentPlayerId();
        }

        /**
         * 1) check ability of player to do it
         * 2) rotate
         * 3) go to the state next turn
         */
        $action = 'rotation';
        $this->checkActionCustom((int) $playerId, $action);
        $this->checkRotation($taleId, $wallSide);

        $metaCharacter = $this->getMetaCharacterById($taleId);
        $currentRound = $this->getLastRoundNum();
        $sql = "UPDATE character_status SET wall_side = '$wallSide', last_round_rotated = '$currentRound' WHERE character_id = '$taleId'";
        self::DbQuery($sql);

        $this->useOption($action);

        self::notifyAllPlayers(
            "rotateTale",
            clienttranslate('${playerName} rotates ${characterName} to the ${wallSide}'),
            array(
                'i18n' => array('wallSide'),
                'playerId' => $playerId,
                'playerName' => self::getActivePlayerName(),
                'characterName' => $metaCharacter['name'],
                'characterId' => $taleId,
                'wallSide' => $wallSide,
            ),
        );

        $this->gamestate->nextState('nextTurn');
    }

    function exchangeTales(string $taleId1, string $taleId2, ?int $playerId)
    {
        self::checkAction( "exchange" );
        if (is_null($playerId)) {
            $playerId = (int) $this->getCurrentPlayerId();
        }

        /**
         * 1) check ability of player to do it
         * 2) exchange
         * 3) go to the state next turn
         */
        $action = 'exchange';
        $this->checkActionCustom($playerId, $action);
        $this->checkExchanging($taleId1, $taleId2);

        $character1 = $this->getCharacterById($taleId1);
        $character2 = $this->getCharacterById($taleId2);
        $metaCharacter1 = $this->getMetaCharacterById($taleId1);
        $metaCharacter2 = $this->getMetaCharacterById($taleId2);
        $pos1 = $character1['tale_pos'];
        $pos2 = $character2['tale_pos'];
        $sql = "UPDATE character_status SET tale_pos = '$pos2' WHERE character_id = '$taleId1'";
        self::DbQuery($sql);
        $sql = "UPDATE character_status SET tale_pos = '$pos1' WHERE character_id = '$taleId2'";
        self::DbQuery($sql);

        $this->useOption($action);

        self::notifyAllPlayers(
            "exchangeTales",
            clienttranslate('${playerName} exchanges ${characterName1} to the ${characterName2}'),
            array(
                'playerId' => $playerId,
                'playerName' => self::getActivePlayerName(),
                'characterName1' => $metaCharacter1['name'],
                'characterName2' => $metaCharacter2['name'],
                'characterId1' => $taleId1,
                'characterId2' => $taleId2,
            ),
        );

        $this->gamestate->nextState('nextTurn');
    }

    function jocker(?string $detectiveId, ?int $newPos, ?int $playerId)
    {
        self::checkAction( "jocker" );
        if (is_null($playerId)) {
            $playerId = (int) $this->getCurrentPlayerId();
        }

        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
        $action = 'jocker';
        $this->checkActionCustom((int) $playerId, $action);
        $this->checkJocker((int) $playerId, $detectiveId, $newPos);

        if (!is_null($detectiveId) && !is_null($newPos)) {
            $sql = "UPDATE detective_status SET detective_pos = '$newPos' WHERE detective_id = '$detectiveId'";
            self::DbQuery($sql);
        }

        $this->useOption($action);

        if (!is_null($detectiveId) && !is_null($newPos)) {
            $clientMessage = clienttranslate('${playerName} uses jocker to move ${detectiveName}');
            $metaDetective = $this->getMetaDetectiveById($detectiveId);
            $detectiveName = $metaDetective['name'];
        } else {
            $clientMessage = clienttranslate('${playerName} uses jocker to save detectives where they are');
            $detectiveName = null;
        }
        self::notifyAllPlayers(
            "jocker",
            $clientMessage,
            array(
                'playerId' => $playerId,
                'playerName' => self::getActivePlayerName(),
                'detectiveId' => $detectiveId,
                'detectiveName' => $detectiveName,
                'newPos' => $newPos,
            ),
        );

        $this->gamestate->nextState('nextTurn');
    }

    function detective(string $action, int $newPos, ?int $playerId)
    {
        self::checkAction( "detective" );
        if (is_null($playerId)) {
            $playerId = (int) $this->getCurrentPlayerId();
        }

        /**
         * 1) check ability of player to do it
         * 2) move
         * 3) go to the state next turn
         */
        $this->checkActionCustom($playerId, $action);
        $this->checkDetective($action, $newPos);

        $sql = "UPDATE detective_status SET detective_pos = '$newPos' WHERE detective_id = '$action'";
        self::DbQuery($sql);

        $this->useOption($action);

        $metaDetective = $this->getMetaDetectiveById($action);
        $detectiveName = $metaDetective['name'];
        self::notifyAllPlayers(
            "detective",
            clienttranslate('${playerName} moves ${detectiveName}'),
            array(
                'playerId' => $playerId,
                'playerName' => self::getActivePlayerName(),
                'detectiveId' => $action,
                'detectiveName' => $detectiveName,
                'newPos' => $newPos,
            ),
        );

        $this->gamestate->nextState('nextTurn');
    }

    function alibi(?int $playerId)
    {
        self::checkAction( "alibi" );
        if (is_null($playerId)) {
            $playerId = (int) $this->getCurrentPlayerId();
        }

        /**
         * 1) check ability of player to do it
         * 2) pull
         * 3) go to the state next turn
         */
        $action = 'alibi';
        $this->checkActionCustom($playerId, $action);

        $availableAlibiCards = $this->getAvailableAlibiCards();
        $randomNum = bga_rand(0, count($availableAlibiCards) - 1);
        $alibiCharacter = $availableAlibiCards[$randomNum];
        $alibiCharacterId = $alibiCharacter['character_id'];
        $alibiMetaCharacter = $this->getMetaCharacterById($alibiCharacterId);
        $sql = "UPDATE character_status SET player_id_with_alibi = '$playerId' WHERE character_id = '$alibiCharacterId'";
        self::DbQuery($sql);
        $jackPlayer = $this->getJackPlayer();
        if (((int) $jackPlayer['player_id']) !== $playerId && $alibiCharacter['tale_is_opened'] === '1') {
            $this->closeCharacters([$alibiCharacter]);
            self::incStat(1, 'closed_characters');
        }

        $this->useOption($action);

        if (((int) $jackPlayer['player_id']) === $playerId) {
            self::notifyPlayer(
                $playerId,
                "alibiJack",
                clienttranslate('${playerName} opens alibi card with ${alibiName}'),
                array(
                    'playerId' => $playerId,
                    'playerName' => self::getActivePlayerName(),
                    'alibiId' => $alibiCharacterId,
                    'alibiName' => $alibiMetaCharacter['name'],
                    'points' => $alibiMetaCharacter['points'],
                ),
            );
            self::notifyAllPlayers(
                "alibiAllExceptJack",
                clienttranslate('${playerName} opens alibi card'),
                array(
                    'playerId' => $playerId,
                    'playerName' => self::getActivePlayerName(),
                ),
            );
        } else {
            self::notifyAllPlayers(
                "alibiAll",
                clienttranslate('${playerName} opens alibi card with ${alibiName}'),
                array(
                    'playerId' => $playerId,
                    'playerName' => self::getActivePlayerName(),
                    'alibiId' => $alibiCharacterId,
                    'alibiName' => $alibiMetaCharacter['name'],
                    'close' => $alibiCharacter['tale_is_opened'] === '1',
                ),
            );
        }

        $this->gamestate->nextState('nextTurn');
    }

    function confirmGameEnd()
    {
        // self::checkAction( "confirmGameEnd" );
        $currentState = $this->gamestate->state();
        if ($currentState['name'] === 'gameEndApprove') {
            $this->gamestate->nextState('gameEnd');
        }
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
            $this->activeNextPlayer();
            // $notActivePlayer = $this->getNotActivePlayer();
            // $this->gamestate->changeActivePlayer((int) $notActivePlayer['player_id']);
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
            fn(array $character) => $character['is_jack'] === '1',
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
            fn(array $character) => $character['tale_is_opened'] === '1',
            ARRAY_FILTER_USE_BOTH,
        );

        $this->closeCharacters($charactersToClose);
        self::incStat(count($charactersToClose), 'closed_characters');

        $detectivePlayer = $this->getDetectivePlayer();
        $detectivePlayerId = (int) $detectivePlayer['player_id'];
        $jackPlayer = $this->getJackPlayer();
        $jackPlayerId = (int) $jackPlayer['player_id'];
        if ($isJackVisible) {
            $winPlayerId = $detectivePlayerId;
        } else {
            $winPlayerId = $jackPlayerId;
        }
        self::incStat(1, 'winned_rounds', $winPlayerId);
        self::incStat(1, 'num_rounds', $detectivePlayerId);
        self::incStat(1, 'num_rounds', $jackPlayerId);

        $currentRoundNum = $this->getLastRoundNum();
        self::setStat($currentRoundNum, 'last_round');
        $sqlIsJackVisible = (int) $isJackVisible;
        $sql = "UPDATE `round` SET is_criminal_visible = '$sqlIsJackVisible', win_player_id = '$winPlayerId' WHERE round_num = '$currentRoundNum';";
        self::DbQuery($sql);


        $gameEndStatus = $this->getGameEndStatus($isJackVisible, $jackPlayerId);
        $playUntilVisibility = $gameEndStatus === 'PLAY_UNTIL_VISIBILITY';
        if ($playUntilVisibility) {
            $sql = "UPDATE `round` SET play_until_visibility = '1' WHERE round_num = '$currentRoundNum'";
            self::DbQuery($sql);
            self::setStat(true, 'draw');
        }

        $isGameEnd = $gameEndStatus === 'DETECTIVE_WIN' || $gameEndStatus === 'JACK_WIN';

        if ($isGameEnd) {
            if ($gameEndStatus === 'DETECTIVE_WIN') {
                $winnerId = $detectivePlayerId;
                self::setStat('0', 'jack_win');
            } else {
                $winnerId = $jackPlayerId;
                self::setStat('1', 'jack_win');
            }
            self::setStat(true, 'is_win', $winnerId);
            self::DbQuery("UPDATE player
                              SET player_score = 1,
                                  player_score_aux = 1
                            WHERE player_id='$winnerId' ");
        }

        if ($currentRoundNum % 2 === 1) {
            $newOptions = $this->getRevertOptions();
        } else {
            $newOptions = $this->getRandomOptions();
        }
        if (!$isGameEnd) {
            $this->addRound();
            $this->saveOptionsInDB($currentRoundNum + 1, $newOptions);
        }

        if ($currentRoundNum % 2 === 1) {
            $nextOptions = null;
            $nextActivePlayerId = (int) $jackPlayerId;
        } else {
            $nextOptions = $this->getRevertOptions();
            $nextActivePlayerId = (int) $detectivePlayerId;
        }
        if (!$isGameEnd) {
            $this->activeNextPlayer();
            $this->gamestate->nextState('playerTurn');
        }

        self::notifyAllPlayers(
            "roundEnd",
            clienttranslate('Round ${oldRoundNum} is over'),
            array(
                'nextActivePlayerId' => $nextActivePlayerId,
                'newRoundNum' => $currentRoundNum + 1,
                'oldRoundNum' => $currentRoundNum,
                'newOptions' => $newOptions,
                'newNextOptions' => $nextOptions,
                'characterIdsToClose' => array_map(
                    fn(array $character): string => $character['character_id'],
                    $charactersToClose,
                ),
                'isVisible' => $isJackVisible,
                'playUntilVisibility' => $playUntilVisibility,
                'winPlayerId' => $winPlayerId,
                'gameEndStatus' => $gameEndStatus,
            ),
        );

        if ($isGameEnd) {
            $this->gamestate->nextState('gameEndAnimation');
        }
    }

    function getGameEndStatus(
        bool $isJackVisible,
        int $jackPlayerId
    ): string {
        $characters = $this->getCharacters();
        $rounds = $this->getRounds();
        $currentRoundNum = count($rounds);
        $jackWinRounds = count(
            array_filter(
                $rounds,
                fn(array $round) => ((int) $round['win_player_id']) === ((int) $jackPlayerId),
                ARRAY_FILTER_USE_BOTH,
            ),
        );
        $jackAlibiCharacters = array_filter(
            $characters,
            fn(array $character) => ((int) $character['player_id_with_alibi']) === ((int) $jackPlayerId),
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
            fn(array $character) => $character['tale_is_opened'] === '1',
            ARRAY_FILTER_USE_BOTH,
        );
        $isDetectiveWin = count($openedCharacters) <= 1;
        if ($isDetectiveWin && $isJackWin) {
            if ($isJackVisible) {
                return 'DETECTIVE_WIN';
            } else if ($currentRoundNum === $this->round_num) {
                return 'JACK_WIN';
            } else {
                return 'PLAY_UNTIL_VISIBILITY';
            }
        }

        if ($isDetectiveWin) {
            return 'DETECTIVE_WIN';
        }

        if ($isJackWin || $currentRoundNum === $this->round_num) {
            return 'JACK_WIN';
        }

        return 'NOT_GAME_END';
    }

    function stEndOfGame()
    {
        $characters = $this->getCharacters();
        $detectives = $this->getDetectives();
        $visibleCharacters = $this->getVisibleCharacters($characters, $detectives);
        $isJackVisible = $this->array_any(
            $visibleCharacters,
            fn(array $character) => $character['is_jack'] === '1',
        );
        $jackPlayer = $this->getJackPlayer();
        $jackPlayerId = (int) $jackPlayer['player_id'];
        $gameEndStatus = $this->getGameEndStatus($isJackVisible, $jackPlayerId);
        $jackCharacter = $this->getJackCharacter();
        if ('JACK_WIN' === $gameEndStatus) {
            $text = clienttranslate('End game. Jack win');
        } else {
            $text = clienttranslate('End game. Detective win');
        }
        $jackALibiCards = $this->getAlibiCardsByPlayerId($jackPlayerId);
        self::notifyAllPlayers(
            "gameEnd",
            $text,
            array(
                'jackCharacterId' => $jackCharacter['character_id'],
                'gameEndStatus' => $gameEndStatus,
                'jackAlibiCards' => $jackALibiCards,
            ),
        );
        $this->gamestate->nextState('gameEndApprove');
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
        if ($state['type'] !== "activeplayer") {
            return;
        }

        $playerId = (int) $active_player['player_id'];
        $availableOptions = $this->getCurrentAvailableOptions();
        $randomOptionNum = bga_rand(0, count($availableOptions) - 1);
        $optionToMove = $availableOptions[$randomOptionNum];
        $optionToMoveName = $optionToMove['option'];

        if ($optionToMoveName === 'alibi') {
            $this->alibi($playerId);
        }

        if ($optionToMoveName === 'watson' || $optionToMoveName === 'holmes' || $optionToMoveName === 'dog') {
            $steps = bga_rand(1, 2);
            $newPos = $this->getNewPosFor($optionToMoveName, $steps);
            $this->detective($optionToMoveName, $newPos, $playerId);
        }

        if ($optionToMoveName === 'jocker') {
            $detectiveIndex = bga_rand(0, count($this->detectives));
            if ($detectiveIndex === count($this->detectives)) {
                $this->jocker(null, null, $playerId);
            } else {
                $metaDetective = $this->detectives[$detectiveIndex];
                $detectiveId = $metaDetective['id'];
                $newPos = $this->getNewPosFor($detectiveId, 1);
                $this->jocker($detectiveId, $newPos, $playerId);
            }
        }

        if ($optionToMoveName === 'rotation') {
            $randomCharacterIndex = bga_rand(0, count($this->characters) - 1);
            $metaCharacter = $this->characters[$randomCharacterIndex];
            $characterId = $metaCharacter['id'];
            $character = $this->getCharacterById($characterId);
            $currentWallSide = $character['wall_side'];
            do {
                $wallIndex = bga_rand(1, 4);
                $wallSide = $this->wall_sides[$wallIndex];
            } while ($currentWallSide === $wallSide);
            $this->rotateTale($characterId, $wallSide, $playerId);
        }

        if ($optionToMoveName === 'exchange') {
            $randomCharacterIndex1 = bga_rand(0, count($this->characters) - 1);
            do {
                $randomCharacterIndex2 = bga_rand(0, count($this->characters) - 1);
            } while ($randomCharacterIndex1 === $randomCharacterIndex2);
            $metaCharacter1 = $this->characters[$randomCharacterIndex1];
            $metaCharacter2 = $this->characters[$randomCharacterIndex2];
            $characterId1 = $metaCharacter1['id'];
            $characterId2 = $metaCharacter2['id'];
            $this->exchangeTales($characterId1, $characterId2, $playerId);
        }

        // throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    function getNewPosFor(string $detectiveId, int $steps): int
    {
        $detective = $this->getDetectiveById($detectiveId);
        $currentPos = $detective['detective_pos'];
        $newPos = $currentPos + $steps;
        if ($newPos > 12) {
            return $newPos - 12;
        } else {
            return $newPos;
        }
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