<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FaiFo implementation : © Daniel Süß <xcid@steinlaus.com>
 * Second Edition was implemented by Le Quoc Huy <lqhuy.work@gmail.com> reusing some code from the first edition
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * faifo.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');
require_once('modules/GameLog.php');


class hoipholqhuy extends Table
{
    public static $instance = null;

    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();
        self::$instance = $this;

        self::initGameStateLabels([
            "current_round"              => 10,
            "players_count"              => 11,
            "current_move"               => 12,
            "discard_move"               => 13,
            "skill_to_resolve_card_type" => 14,
            "skill_to_resolve_player"    => 15,
            "skill_done"                 => 16,
            "special_skill_type"         => 17,
            "return_card_to_player"      => 18,
            "skill_actions_done"         => 19,
            "rps_opponent"               => 20,
            "active_card_id"             => 21,
            "copied_skill_type_id"       => 22,
            "stolen_card_id"             => 23,
            "remaining_cards_revealed"   => 24,
            "named_card_id"         => 25,
        ]);

        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
    }

    public static function get()
    {
        return self::$instance;
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "hoipholqhuy";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = [])
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = [];
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here

        $this->setInitialGameStateValues();
        $this->createDeckOfCards();

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
        $result = [];

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score, reveal_money FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);


        // MATERIAL
        $result['card_size'] = $this->card_size;
        $result['card_types'] = $this->merchant_card;
        $result['card_id_to_type'] = $this->getCardIds();

        // PLAYER COINS AND CONTRACTS
        $result['players_assets'] = $this->getPlayersAssets();

        // CURRENT PLAYER HAND CARDS
        $result['player_hand_cards'] = $this->cards->getCardsInLocation('hand', $current_player_id);

        // DISCARDED CARDS
        $result['discarded_cards'] = $this->cards->getCardsInLocation('discard');

        // PLAYERS HAND CARDS ON TABLES
        //        $result['player_table_cards'] = $this->cards->getCardsInLocation('table');
        $result['selected_cards_info'] = $this->getSelectedCardsBeforeReveal($current_player_id);
        $result['selected_card_to_pass_id'] = $this->getSelectedCardToPass($current_player_id);
        $result['played_cards'] = $this->getPlayedCards();


        // PLAYER NEIGHBORS
        if (!$this->isSpectator()) {
            $result['neighbors'] = $this->getPlayerNeighbors($this->getCurrentPlayerId());
            $result['all_neighbors'] = $this->getAllPlayerNeighbors();
        }

        // ACTION RELATED
        $result['return_card_to_player'] = self::getGameStateValue('return_card_to_player');
        $result['active_card_id'] = self::getGameStateValue('active_card_id');
        $result['copied_skill_type_id'] = self::getGameStateValue('copied_skill_type_id');
        $result['named_card_id'] = self::getGameStateValue('named_card_id');
        $result['skill_to_resolve_player'] = self::getGameStateValue('skill_to_resolve_player');
        //
        //                echo "<pre>";
        //                print_r($result);
        //                echo "</pre>";


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
        $players = self::loadPlayersBasicInfos();

        // Highest contract amount of any player is the first metric
        $sql = "SELECT MAX(contracts_won) FROM player";
        $max_contract_amount = self::getUniqueValueFromDB($sql);
        $progress1 = $max_contract_amount * 33.33;

        // Total contracts won
        $sql = "SELECT SUM(contracts_won) FROM player";
        $sum_contracts_won = self::getUniqueValueFromDB($sql);
        $max_contracts = (count($players) * 2) + 1;
        $progress2 = ($sum_contracts_won * 100) / $max_contracts;

        $progress_avg = ($progress1 + $progress2) / 2;

        return $progress_avg;
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////

    function setInitialGameStateValues()
    {
        $players_count = self::getPlayersNumber();

        // Players
        self::setGameStateValue('players_count', $players_count);

        // Rounds
        self::setGameStateValue('current_round', 0);
        self::setGameStateValue('current_move', 0);

        //
        self::setGameStateValue('discard_move', 0);

        self::setGameStateValue('copied_skill_type_id', 0);
        self::setGameStateValue('named_card_id', 0);
    }

    private function playerIsSpectator($player_id)
    {
        return $this->isSpectator();
    }

    function createDeckOfCards()
    {
        $cards = [];

        // Merchants first, one of each
        for (
            $i = 1;
            $i <= 15;
            $i++
        ) {
            $cards[] = [
                'type'     => $i,
                'type_arg' => 0,
                'nbr'      => 1,
            ];
        }

        // Add merchant ship cards to the deck, according to the players count
        $players_count = self::getGameStateValue('players_count');
        $amount_ships_to_add = $this->setup_rules['ships_in_deck'][$players_count];
        $cards[] = [
            'type'     => $this->merchant_ship_type_id,
            'type_arg' => 0,
            'nbr'      => $amount_ships_to_add,
        ];

        $this->cards->createCards($cards, 'deck');
    }

    private function addToSkillQueue($player_id, $card_id, $card_type, $reputation)
    {
        $sql = "INSERT INTO skill_queue (card_id, player_id, card_type, reputation) VALUES ($card_id, $player_id, $card_type, $reputation)";
        self::DbQuery($sql);
    }

    private function getNextSkillFromQueue()
    {
        $sql = "SELECT * FROM skill_queue WHERE done = 0 ORDER BY pos ASC LIMIT 1";
        $result = self::getCollectionFromDB($sql);

        return array_shift($result);
    }

    private function setSkillInQueueAsDone()
    {
        $sql = "UPDATE skill_queue SET done = 1 WHERE done = 0 ORDER BY pos ASC LIMIT 1";
        self::DbQuery($sql);
    }

    private function discardAllSkillsInQueue()
    {
        $sql = "TRUNCATE skill_queue";
        self::DbQuery($sql);
    }

    protected function addClass(array $expand_cards_element_ids)
    {
        self::notifyAllPlayers(
            'addClass',
            '',
            [
                'element_ids' => $expand_cards_element_ids,
                'class_name'  => 'expanded-card',
            ]
        );
    }

    protected function removeClass(array $expand_cards_element_ids)
    {
        self::notifyAllPlayers(
            'removeClass',
            '',
            [
                'element_ids' => $expand_cards_element_ids,
                'class_name'  => 'expanded-card',
            ]
        );
    }

    function getCardIds()
    {
        $sql = "SELECT card_id, card_type FROM card";
        $result = self::getCollectionFromDB($sql);

        return $result;
    }

    // function getPlayersAssets($player_id = null)
    // {
    //     $sql = "SELECT player_id, coins_total, coins_this_turn, contracts_won as contracts FROM player";
    //     $result = self::getCollectionFromDB($sql);

    //     if ($player_id) {
    //         return $result[$player_id];
    //     } else {
    //         return $result;
    //     }
    // }
    function getPlayersAssets($player_id = null)
    {
        $sql = "SELECT player_id, coins_total, coins_this_turn, contracts_won as contracts, top_contract_value FROM player";
        $result = self::getCollectionFromDB($sql);

        if ($player_id) {
            return $result[$player_id];
        } else {
            return $result;
        }
    }

    function refreshPlayedCards()
    {
        self::notifyAllPlayers(
            'refreshPlayedCards',
            '',
            [
                'played_cards' => $this->getPlayedCards(),
            ]
        );
    }

    function getSkillsToCopy()
    {
        $sql = "SELECT card_id, card_type FROM card WHERE (revealed = 1 OR card_location = 'table' or card_location = 'discard') AND (card_type <> 11 AND card_type <> 13 AND card_type <> 16) ORDER BY card_type ASC";
        $cards = self::getCollectionFromDB($sql);

        return $cards;
    }

    function getAllCardsToForce()
    {
        $sql = "SELECT card_id, card_type FROM card WHERE (card_type <> 4 AND card_type <> 16) ORDER BY card_type ASC";
        $cards = self::getCollectionFromDB($sql);

        return $cards;
    }

    function getAllCardsToTakeThreeMoney()
    {
        $sql = "SELECT card_id, card_type FROM card WHERE (card_type <> 5 AND card_type <> 16) ORDER BY card_type ASC";
        $cards = self::getCollectionFromDB($sql);

        return $cards;
    }

    function getSelectedCards()
    {
        $sql = "SELECT selected_card_id, player_id FROM player WHERE selected_card_id > 0";
        $selected_card_ids = self::getCollectionFromDB($sql);

        if (count($selected_card_ids)) {
            $sql = "SELECT * FROM card WHERE card_id in (" . implode(',', array_keys($selected_card_ids)) . ")";
            $selected_cards = self::getCollectionFromDB($sql);

            foreach ($selected_card_ids as $selected_card_id => $data) {
                $selected_cards[$selected_card_id]['player_id'] = $data['player_id'];
            }

            return $selected_cards;
        }

        return [];
    }

    private function logCardsAsPlayed($selected_cards, $round)
    {
        foreach ($selected_cards as $selected_card) {
            $player_id = $selected_card['player_id'];
            $sql = "UPDATE player SET played_cards_this_round = concat(played_cards_this_round, '" . $selected_card['card_id'] . ";') WHERE player_id = $player_id";
            self::DbQuery($sql);
        }
    }

    private function getPlayedCards(): array
    {
        $result = [];
        $sql = "SELECT player_id, played_cards_this_round FROM player";
        $played_cards = self::getCollectionFromDB($sql);

        foreach ($played_cards as $player_id => $card_info) {
            $cards = $card_info['played_cards_this_round'];
            $cards_arr = explode(";", $cards);

            $result[$player_id] = [];

            foreach ($cards_arr as $card_id) {
                if ($card_id) {
                    $result[$player_id][] = ['card_id' => $card_id, 'value' => self::getCardValue($card_id)];
                }
            }
        }

        return $result;
    }

    private function resetPlayedCards()
    {
        $sql = "UPDATE player SET played_cards_this_round = ''";
        self::DbQuery($sql);
    }

    private function getCardValue($card_id)
    {
        $sql = "SELECT card_type as value FROM card WHERE card_id = $card_id";

        return self::getUniqueValueFromDB($sql);
    }

    private function getSelectedCardsBeforeReveal($player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();

        $result = [];
        foreach ($players as $player_id => $player) {
            $result[$player_id]['card_selected'] = false;
            $result[$player_id]['card_id'] = 0;
        }

        $sql = "SELECT selected_card_id, player_id FROM player WHERE selected_card_id > 0";
        $selected_card_ids = self::getCollectionFromDB($sql);

        if (count($selected_card_ids) > 0) {
            $sql = "SELECT * FROM card WHERE card_id in (" . implode(',', array_keys($selected_card_ids)) . ")";
            $selected_cards = self::getCollectionFromDB($sql);

            foreach ($selected_card_ids as $selected_card_id => $data) {
                $player_id = $data['player_id'];
                $result[$player_id]['card_selected'] = true;
                if ($player_id == $current_player_id || $selected_cards[$selected_card_id]['revealed']) {
                    $result[$player_id]['card_id'] = $selected_card_id;
                }
            }
        }

        return $result;
    }

    private function getSelectedCardToPass($player_id)
    {
        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();

        $result = [];
        foreach ($players as $player_id => $player) {
            $result[$player_id]['card_selected'] = false;
            $result[$player_id]['card_id'] = 0;
        }

        $sql = "SELECT selected_card_to_pass_id FROM player WHERE player_id = $current_player_id";
        $selected_card_to_pass_id = self::getUniqueValueFromDB($sql);

        return $selected_card_to_pass_id;
    }

    private function playerHasNamedCard($player_id) {
        $named_card_id = self::getGameStateValue('named_card_id');
        if ($named_card_id = 0) return false;
        else{
            $sql = "SELECT card_id FROM card WHERE card_location = 'hand' AND card_location_arg = $player_id AND card_id = $named_card_id";
            $result = self::getUniqueValueFromDB($sql);
            if (!$result) {
                return false;
            }
        }
        return true;
    }

    function checkPlayerPossessesCard($player_id, $card_id)
    {
        $sql = "SELECT card_id FROM card WHERE card_id = $card_id AND card_location_arg = $player_id";
        $result = self::getUniqueValueFromDB($sql);
        if (!$result) {
            throw new BgaUserException(self::_('This card is not in your hand!'));
        }
    }

    function autoSelectLastCard()
    {
        $players = self::loadPlayersBasicInfos();

        self::notifyAllPlayers('msg', clienttranslate('The remaining hand card is automatically selected'), []);

        foreach (array_keys($players) as $player_id) {
            // get remaining card
            $sql = "SELECT card_id FROM card WHERE card_location = 'hand' AND card_location_arg = '$player_id' LIMIT 1";
            $remaining_card_id = self::getUniqueValueFromDB($sql);

            $sql = "UPDATE player SET selected_card_to_pass_id = $remaining_card_id WHERE player_id = $player_id";
            self::DbQuery($sql);
        }
    }

    function resetCoinsThisTurn()
    {
        $sql = "UPDATE player SET coins_this_turn = 0";
        self::DbQuery($sql);
    }

    function manageCoins($player_id, $action, $amount = 0, $notification = false)
    {
        $players = self::loadPlayersBasicInfos();
        $player_name = $players[$player_id]['player_name'];

        $sql = "SELECT coins_total FROM player WHERE player_id = $player_id";
        $current_coins_total = self::getUniqueValueFromDB($sql);
        $new_amount_of_coins_total = 0;

        $sql = "SELECT coins_this_turn FROM player WHERE player_id = $player_id";
        $coins_this_turn = self::getUniqueValueFromDB($sql);
        $new_amount_of_coins_this_turn = 0;

        $msg = '';

        switch ($action) {
            case 'set':
                $new_amount_of_coins_total = $amount;
                break;
            case 'add':
                if ($amount == 1) {
                    $msg = clienttranslate('${player_name} gains 1 coin');
                } else {
                    $msg = clienttranslate('${player_name} gains ${amount} coins');
                }
                $new_amount_of_coins_total = $current_coins_total + $amount;
                $new_amount_of_coins_this_turn = $coins_this_turn + $amount;

                self::incStat($amount, "money_gained");
                self::incStat($amount, "money_gained", $player_id);
                break;
            case 'sub':
                if ($amount == 1) {
                    $msg = clienttranslate('${player_name} loses 1 coin');
                } else {
                    $msg = clienttranslate('${player_name} loses ${amount} coins');
                }
                $new_amount_of_coins_total = $current_coins_total - $amount;
                $new_amount_of_coins_this_turn = $coins_this_turn - $amount;

                self::incStat($amount, "money_lost");
                self::incStat($amount, "money_lost", $player_id);
                break;
            case 'get_gained':
                return $coins_this_turn;
                break;
        }

        if ($new_amount_of_coins_total < 0) {
            $new_amount_of_coins_total = 0;
        }
        if ($new_amount_of_coins_this_turn < 0) {
            $new_amount_of_coins_this_turn = 0;
        }

        $sql = "UPDATE player SET coins_total = $new_amount_of_coins_total, coins_this_turn = $new_amount_of_coins_this_turn WHERE player_id = $player_id";
        self::DbQuery($sql);

        if ($notification) {
            self::notifyAllPlayers(
                'msg',
                $msg,
                [
                    'player_name' => $player_name,
                    'amount'      => $amount,
                ]
            );
        }

        return ['new_amount' => $new_amount_of_coins_total, 'old' => $current_coins_total];
    }

    function manageContracts($player_id, $action, $amount, $notification = true)
    {
        $players = self::loadPlayersBasicInfos();
        $player_name = $players[$player_id]['player_name'];


        $sql = "SELECT contracts_won FROM player WHERE player_id = $player_id";
        $amount_contracts = self::getUniqueValueFromDB($sql);

        $msg = '';

        switch ($action) {
            case 'set':
                $amount_contracts = $amount;
                break;
            case 'add':
                $amount_contracts += $amount;
                $msg = clienttranslate('${player_name} wins a contract!');
                break;
        }

        $sql = "UPDATE player SET contracts_won = $amount_contracts WHERE player_id = $player_id";
        self::DbQuery($sql);

        if ($notification) {
            self::notifyAllPlayers(
                'msg',
                $msg,
                [
                    'player_name' => $player_name,
                    'amount'      => $amount,
                ]
            );
        }

        return $amount_contracts;
    }

    function runSwitchCardTextAnimation($player_id, $skill_type_id)
    {
        self::notifyAllPlayers('switchCardText', '', [
            'player_id'     => $player_id,
            'skill_type_id' => $skill_type_id,
        ]);
        $this->doPause(3000);
    }


    function runAddCoinAnimation($player_id, $amount)
    {
        for ($counter = 1; $counter <= $amount; $counter++) {
            self::notifyAllPlayers('addCoins', '', [
                'player_id' => $player_id,
                'amount'    => $amount,
                'counter'   => $counter,
            ]);
        }
    }

    function runSubCoinAnimation($player_id, $amount)
    {
        for ($counter = 1; $counter <= $amount; $counter++) {
            self::notifyAllPlayers('subCoins', '', [
                'player_id'     => $player_id,
                'amount'        => $amount,
                'counter'       => $counter,
                'player_assets' => $this->getPlayersAssets(),
            ]);
        }
    }

    function runSwitchCoinAnimation($player_from_id, $player_to_id, $amount)
    {
        $player_assets = $this->getPlayersAssets();

        for ($counter = 1; $counter <= $amount; $counter++) {
            self::notifyAllPlayers('switchCoins', '', [
                'player_from_id'           => $player_from_id,
                'player_to_id'             => $player_to_id,
                'amount'                   => $amount,
                'counter'                  => $counter,
                'player_from_coins_amount' => $player_assets[$player_from_id]['coins_total'] + $amount - $counter,
                'player_to_coins_amount'   => $player_assets[$player_to_id]['coins_total'] - $amount + $counter,
                'player_assets'            => $this->getPlayersAssets(),
            ]);
            $this->doPause(500);
        }
        $this->doPause(1000);
    }

    function runContractAnimation($player_id, $amount)
    {
        for ($counter = 1; $counter <= $amount; $counter++) {
            self::notifyAllPlayers('addContract', '', [
                'player_id' => $player_id,
                'amount'    => $amount,
                'counter'   => $counter,
            ]);
        }
    }

    function runGiveCardPassiveAnimation($from, $to)
    {
        $players = self::loadPlayersBasicInfos();

        foreach ($players as $player_id => $player) {
            if ($player_id != $from && $player_id != $to) {
                self::notifyPlayer($player_id, 'giveCardAnimation', '', [
                    'from' => $from,
                    'to'   => $to,
                ]);
            }
        }
    }

    function setSkillCardNonActive()
    {
        self::setGameStateValue('active_card_id', 0);
        self::notifyAllPlayers('setSkillCardNonActive', '', []);
    }

    function removeCoinsAndContractsFromPlayerTable()
    {
        self::notifyAllPlayers('removeCoinsAndContractsFromTables', '', [
            'player_assets' => $this->getPlayersAssets(),
        ]);
        $this->doPause(500);
        $this->refreshPlayerAssets();
    }

    function discardSelectedCards()
    {
        $selected_cards = $this->getSelectedCards();

        foreach ($selected_cards as $selected_card_id => $data) {
            $this->cards->playCard($selected_card_id);
        }

        self::notifyAllPlayers('clearTables', '', [
            'selected_cards' => $selected_cards,
        ]);
        $this->removeEverythingFromTables();
        $this->refreshPlayedCards();
        $this->doPause(2000);
    }

    function resetCardSelections()
    {
        $sql = "UPDATE player SET selected_card_id = 0";
        self::DbQuery($sql);
    }

    function clearTables()
    {
        $selected_cards = $this->getSelectedCards();

        self::notifyAllPlayers('clearTables', '', [
            'selected_cards' => $selected_cards,
        ]);
        $this->refreshPlayedCards();
        $this->doPause(2000);
    }

    function removeEverythingFromTables()
    {
        self::notifyAllPlayers('removeEverythingFromTables', '', []);
    }

    function returnGainedCoins()
    {
        $players = self::loadPlayersBasicInfos();

        foreach ($players as $player_id => $player) {
            $gained_coins = $this->manageCoins($player_id, 'get_gained');
            $this->manageCoins($player_id, 'sub', $gained_coins);
            $this->runSubCoinAnimation($player_id, $gained_coins);
        }
    }

    function enablePlayerSelect(
        $player_ids,
        $skill_type,
        $amount_players_to_pick,
        $selectable_players = [],
        $topic = ''
    ) {
        //        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->setPlayersMultiactive($player_ids, 'multiActiveSkill');


        //        foreach ($player_ids as $player_id) {
        //            if ($selectable_players) {
        //                foreach ($selectable_players as $selectable_player) {
        //                    $this->setPlayerIsSelectable($player_id, $selectable_player, true);
        //                }
        //            }
        //        }
    }

    function setPlayerIsSelectable($player_id, $selectable_player, $status)
    {
        self::notifyPlayer(
            $player_id,
            'enablePlayerSelect',
            '',
            [
                'player_id'         => $player_id,
                'selectable_player' => $selectable_player,
                'status'            => $status,
            ]
        );
    }

    function getPlayersToDirection()
    {
        $result = [];

        $players = self::loadPlayersBasicInfos();
        $amt_players = count($players);
        $nextPlayer = self::createNextPlayerTable(array_keys($players));
        $current_player = self::getCurrentPlayerId();
        $counter = 1;

        if (!isset($nextPlayer[$current_player])) {
            // Spectator mode: take any player for table 1
            $player_id = $nextPlayer[0];
            $result[$player_id] = $counter;
        } else {
            // Normal mode: current player is on table 1
            $player_id = $current_player;
            $result[$player_id] = $counter;
        }

        while ($counter < $amt_players) {
            $counter++;
            $player_id = $nextPlayer[$player_id];
            $result[$player_id] = $counter;
        }

        return $result;
    }

    function getPlayerName($player_id)
    {
        $players = self::loadPlayersBasicInfos();

        return $players[$player_id]['player_name'];
    }

    function doPause($amount)
    {
        self::notifyAllPlayers('simplePause', '', ['time' => $amount]); // time is in milliseconds
    }

    function unselectCarsToPass()
    {
        $sql = "UPDATE player SET selected_card_to_pass_id = 0";
        self::DbQuery($sql);
    }

    function resetRPSSelection()
    {
        $sql = "UPDATE player SET selected_rps = null";
        self::DbQuery($sql);
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    ////////////

    function selectCard($card_id)
    {
        $this->gamestate->checkPossibleAction('selectCard');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();
        $selection_changed = false;
        $old_card_id = 0;

        // Get the forced card ID for this player
        $sql = "SELECT forced_card_id FROM player WHERE player_id = $current_player_id";
        $forced_card_id = self::getUniqueValueFromDB($sql);

        // Player already selected a card and changed their mind?
        $sql = "SELECT selected_card_id FROM player WHERE player_id = $current_player_id AND selected_card_id > 0";
        $result = self::getUniqueValueFromDB($sql);
        if ($result) {
            $old_card_id = $result;
            $selection_changed = true;
            self::DbQuery($sql);
        }

        // Check if player has the named card and must play it
        if ($forced_card_id > 0) {
            // Check if the selected card is the forced card
            if ($card_id != $forced_card_id) {
                // Check if player actually has the forced card
                $sql = "SELECT card_id FROM card WHERE card_location = 'hand' AND card_location_arg = $current_player_id AND card_id = $forced_card_id";
                $has_forced_card = self::getUniqueValueFromDB($sql);
                
                if ($has_forced_card) {
                    // Force player to play the named card
                    $sql = "UPDATE player SET selected_card_id = $forced_card_id WHERE player_id = $current_player_id";
                    self::DbQuery($sql);
                    
                    self::notifyAllPlayers('msg', clienttranslate('${player_name} has the named card and must play it'), [
                        'player_name' => $current_player_name,
                        'card_name' => $this->merchant_card[$this->getCardType($forced_card_id)]['name']
                    ]);
                    
                    $this->gamestate->setPlayerNonMultiactive($current_player_id, '');
                    return;
                }
            }
        }

        // Normal card selection
        self::checkPlayerPossessesCard($current_player_id, $card_id);
        $sql = "UPDATE player SET selected_card_id = $card_id WHERE player_id = $current_player_id";
        self::DbQuery($sql);
        // $sql = "UPDATE player SET selected_card_id = $card_id WHERE player_id = $current_player_id";
        // self::DbQuery($sql);

        if (self::getGameStateValue('discard_move') == 1) {
            $string_select = clienttranslate('${player_name} selects a card to discard');
            $string_select_change = clienttranslate('${player_name} selects a different card to discard');
        } else {
            $string_select = clienttranslate('${player_name} selects a card');
            $string_select_change = clienttranslate('${player_name} selects a different card');
        }

        if ($selection_changed) {
            $message = $string_select_change;
        } else {
            $message = $string_select;
        }

        //        if (self::getGameStateValue('discard_move') != 1) {
        //            foreach ($players as $player_id => $player) {
        ////                $this->cards->moveCard($card_id, 'table', $player_id);
        //                if ($current_player_id == $player_id) {
        //                    self::notifyPlayer($player_id, 'selectCard', $message,
        //                        [
        //                            'player_id'   => $current_player_id,
        //                            'player_name' => $current_player_name,
        //                            'card_id'     => $card_id,
        //                        ]);
        //                } else {
        //                    self::notifyPlayer($player_id, 'selectCard', $message,
        //                        [
        //                            'player_id'   => $current_player_id,
        //                            'player_name' => $current_player_name,
        //                        ]);
        //                }
        //            }
        //        }

        if (self::getGameStateValue('discard_move') != 1) {
            if ($selection_changed) {
                self::notifyPlayer(
                    $current_player_id,
                    'changeSelectedCard',
                    '',
                    [
                        'player_id'     => $current_player_id,
                        'player_name'   => $current_player_name,
                        'card_id'       => $card_id,
                        'old_card_id'   => $old_card_id,
                        'old_card_type' => $this->getCardType($old_card_id),
                    ]
                );

                self::notifyAllPlayers(
                    'msg',
                    $message,
                    [
                        'player_id'   => $current_player_id,
                        'player_name' => $current_player_name,
                    ]
                );
            } else {
                self::notifyPlayer(
                    $current_player_id,
                    'selectCard',
                    '',
                    [
                        'player_id'   => $current_player_id,
                        'player_name' => $current_player_name,
                        'card_id'     => $card_id,
                    ]
                );

                self::notifyAllPlayers(
                    'selectCard',
                    $message,
                    [
                        'player_id'   => $current_player_id,
                        'player_name' => $current_player_name,
                    ]
                );
            }
        } else {
            self::notifyAllPlayers(
                'msg',
                $message,
                [
                    'player_id'   => $current_player_id,
                    'player_name' => $current_player_name,
                ]
            );
        }

        $this->gamestate->setPlayerNonMultiactive($current_player_id, '');
    }

    function selectCardToGiveLeft($card_id)
    {
        $this->gamestate->checkPossibleAction('selectCardToGiveLeft');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();
        $selection_changed = false;

        self::checkPlayerPossessesCard($current_player_id, $card_id);

        $sql = "UPDATE player SET selected_card_to_pass_id = $card_id WHERE player_id = $current_player_id";
        self::DbQuery($sql);

        $this->gamestate->setPlayerNonMultiactive($current_player_id, "multiActiveSkillCheckTransition");
    }

    function moveCardsToLeft()
    {
        $players = self::loadPlayersBasicInfos();
        $cards_to_pass = [];

        self::notifyAllPlayers('msg', clienttranslate('Each player passes a card to their left neighbor'), []);

        foreach (array_keys($players) as $player_id) {
            $neighbors = $this->getPlayerNeighbors($player_id);
            $player_to_left_id = $neighbors[0];
            $player_to_right_id = $neighbors[1];

            $sql = "SELECT selected_card_to_pass_id FROM player WHERE player_id = $player_id";
            $card_to_give_id = self::getUniqueValueFromDB($sql);

            $sql = "SELECT selected_card_to_pass_id FROM player WHERE player_id = $player_to_right_id";
            $card_to_receive_id = self::getUniqueValueFromDB($sql);
            $card_to_receive_type = $this->getCardType($card_to_receive_id);

            $this->cards->moveCard($card_to_give_id, 'hand', $player_to_left_id);

            $cards_to_pass[$player_id] = [
                'player_to_the_left_id'  => $player_to_left_id,
                'player_to_the_right_id' => $player_to_right_id,
                'card_to_give_id'        => $card_to_give_id,
                'card_to_receive_id'     => $card_to_receive_id,
                'card_to_receive_type'   => $card_to_receive_type,
            ];
        }

        $this->unselectCarsToPass();

        foreach (array_keys($players) as $player_id) {
            self::notifyPlayer(
                $player_id,
                'giveCardToPlayer',
                clienttranslate('You give <strong>${selected_card}</strong> to ${receiving_player_name}'),
                [
                    'to_player_id'          => $cards_to_pass[$player_id]['player_to_the_left_id'],
                    'card_id'               => $cards_to_pass[$player_id]['card_to_give_id'],
                    'selected_card'         => $this->merchant_card[$this->getCardType($cards_to_pass[$player_id]['card_to_give_id'])]['name'],
                    'receiving_player_name' => $players[$cards_to_pass[$player_id]['player_to_the_left_id']]['player_name'],
                ]
            );
        }
        $this->doPause(1000);

        foreach (array_keys($players) as $player_id) {
            self::notifyPlayer(
                $player_id,
                'getCardFromPlayer',
                clienttranslate('You receive <strong>${received_card}</strong> from ${giving_player_name}'),
                [
                    'from_player_id'     => $cards_to_pass[$player_id]['player_to_the_right_id'],
                    'card_id'            => $cards_to_pass[$player_id]['card_to_receive_id'],
                    'card_type'          => $cards_to_pass[$player_id]['card_to_receive_type'],
                    'received_card'      => $this->merchant_card[$cards_to_pass[$player_id]['card_to_receive_type']]['name'],
                    'giving_player_name' => $players[$cards_to_pass[$player_id]['player_to_the_right_id']]['player_name'],
                ]
            );
        }

        $this->doPause(2000);
    }

    function stealHalfMoney($target_player_id)
    {
        $this->gamestate->checkPossibleAction('stealHalfMoney');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();

        self::giveExtraTime($current_player_id);

        $player_assets = $this->getPlayersAssets($target_player_id);
        $coins_available_to_steal = floor($player_assets['coins_total'] / 2);

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} steals ${coins_amount} coins from ${target_player_name}'),
            [
                'player_name'        => $players[$current_player_id]['player_name'],
                'target_player_name' => $players[$target_player_id]['player_name'],
                'coins_amount'       => $coins_available_to_steal,
            ]
        );

        $this->manageCoins($target_player_id, 'sub', $coins_available_to_steal);
        $this->manageCoins($current_player_id, 'add', $coins_available_to_steal);
        self::incStat($coins_available_to_steal, "money_gained_from_players", $current_player_id);
        self::incStat($coins_available_to_steal, "money_lost_to_players", $target_player_id);
        $this->runSwitchCoinAnimation($target_player_id, $current_player_id, $coins_available_to_steal);

        $this->refreshPlayerAssets();

        self::setGameStateValue('skill_done', 1);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }

    function stealThreeCoins($target_player_id)
    {
        $this->gamestate->checkPossibleAction('stealThreeCoins');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();

        self::giveExtraTime($current_player_id);

        $player_assets = $this->getPlayersAssets($target_player_id);
        $coins_available_to_steal = $player_assets['coins_total'] >= 3 ? 3 : $player_assets['coins_total'];

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} steals ${coins_amount} coins from ${target_player_name}'),
            [
                'player_name'        => $players[$current_player_id]['player_name'],
                'target_player_name' => $players[$target_player_id]['player_name'],
                'coins_amount'       => $coins_available_to_steal,
            ]
        );

        $this->manageCoins($target_player_id, 'sub', $coins_available_to_steal);
        $this->manageCoins($current_player_id, 'add', $coins_available_to_steal);
        self::incStat($coins_available_to_steal, "money_gained_from_players", $current_player_id);
        self::incStat($coins_available_to_steal, "money_lost_to_players", $target_player_id);
        $this->runSwitchCoinAnimation($target_player_id, $current_player_id, $coins_available_to_steal);

        $this->refreshPlayerAssets();

        self::setGameStateValue('skill_done', 1);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }

    function stealOneCard($target_player_id)
    {
        $this->gamestate->checkPossibleAction('stealOneCard');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();
        self::giveExtraTime($current_player_id);

        $target_player_handcards = array_keys($this->cards->getCardsInLocation('hand', $target_player_id));
        $rnd = array_rand($target_player_handcards, 1);
        $card_id = $target_player_handcards[$rnd];
        $card_type = $this->getCardType($card_id);
        self::setGameStateValue('stolen_card_id', $card_id);

        $this->cards->moveCard($card_id, 'hand', $current_player_id);

        $this->runGiveCardPassiveAnimation($target_player_id, $current_player_id);

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} steals a card from ${target_player_name}'),
            [
                'player_name'        => $players[$current_player_id]['player_name'],
                'target_player_name' => $players[$target_player_id]['player_name'],
            ]
        );

        self::notifyPlayer(
            $current_player_id,
            'getCardFromPlayer',
            '',
            [
                'from_player_id' => $target_player_id,
                'card_id'        => $card_id,
                'card_type'      => $card_type,
            ]
        );

        self::notifyPlayer(
            $target_player_id,
            'giveCardToPlayer',
            '',
            [
                'to_player_id' => $current_player_id,
                'card_id'      => $card_id,
                'card_type'    => $card_type,
            ]
        );


        self::setGameStateValue('special_skill_type', 42);
        self::setGameStateValue('return_card_to_player', $target_player_id);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }

    function returnOneCard($card_id)
    {
        $this->gamestate->checkPossibleAction('returnOneCard');

        if ($card_id == self::getGameStateValue('stolen_card_id')) {
            throw new BgaUserException(self::_("You must return a different card!"));
        } else {
            self::setGameStateValue('stolen_card_id', 0);
        }

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();
        $target_player_id = self::getGameStateValue('return_card_to_player');

        self::giveExtraTime($current_player_id);


        $card_type = $this->getCardType($card_id);

        $this->cards->moveCard($card_id, 'hand', $target_player_id);
        $this->runGiveCardPassiveAnimation($current_player_id, $target_player_id);

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} returns a card to ${target_player_name}'),
            [
                'player_name'        => $players[$current_player_id]['player_name'],
                'target_player_name' => $players[$target_player_id]['player_name'],
            ]
        );


        self::notifyPlayer(
            $current_player_id,
            'giveCardToPlayer',
            '',
            [
                'to_player_id' => $target_player_id,
                'card_id'      => $card_id,
                'card_type'    => $card_type,
            ]
        );

        self::notifyPlayer(
            $target_player_id,
            'getCardFromPlayer',
            '',
            [
                'from_player_id' => $current_player_id,
                'card_id'        => $card_id,
                'card_type'      => $card_type,
            ]
        );

        self::setGameStateValue('special_skill_type', 9999);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }

    function giveOneCoin($target_player_id)
    {
        $this->gamestate->checkPossibleAction('giveOneCoin');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();

        $player_assets = $this->getPlayersAssets($target_player_id);
        $coins_to_give = 1;

        $this->manageCoins($target_player_id, 'add', $coins_to_give);
        $this->manageCoins($current_player_id, 'sub', $coins_to_give);
        self::incStat($coins_to_give, "money_gained_from_players", $target_player_id);
        self::incStat($coins_to_give, "money_lost_to_players", $current_player_id);
        $this->runSwitchCoinAnimation($current_player_id, $target_player_id, 1);

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} gives ${coins_amount} coin to ${target_player_name}'),
            [
                'player_name'        => $players[$current_player_id]['player_name'],
                'target_player_name' => $players[$target_player_id]['player_name'],
                'coins_amount'       => $coins_to_give,
            ]
        );

        $this->refreshPlayerAssets();

        self::setGameStateValue('skill_done', 1);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }
    // function nameOneCard()
    // {
    //     $this->gamestate->checkPossibleAction('nameOneCard');

    //     $players = self::loadPlayersBasicInfos();
    //     $current_player_id = self::getCurrentPlayerId();
    //     $current_player_name = self::getCurrentPlayerName();

    //     // Find which player has this card type in hand
    //     $player_with_card = null;
    //     foreach ($players as $player_id => $player) {
    //         $hand = $this->cards->getCardsInLocation('hand', $player_id);
    //         foreach ($hand as $card) {
    //             if ($card['type'] == $card_type) {
    //                 $player_with_card = $player_id;
    //                 break 2;
    //             }
    //         }
    //     }

    //     if ($player_with_card) {
    //         // Store that this player must play this card next turn
    //         self::setGameStateValue('forced_card_player', $player_with_card);
    //         self::setGameStateValue('forced_card_type', $card_type);

    //         self::notifyAllPlayers(
    //             'msg',
    //             clienttranslate('${player_name} must play the ${card_name} card next turn'),
    //             [
    //                 'player_name' => $players[$player_with_card]['player_name'],
    //                 'card_name' => $this->merchant_card[$card_type]['name']
    //             ]
    //         );
    //     } else {
    //         self::notifyAllPlayers(
    //             'msg',
    //             clienttranslate('No player has the selected merchant card in hand'),
    //             []
    //         );
    //     }

    //     self::setGameStateValue('special_skill_type', 11);
    //     self::setGameStateValue('named_card_id', $card_id);
    //     $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    //     $this->gamestate->setPlayerNonMultiactive($current_player_id, "multiActiveSkillCheckTransition");
    // }

    function choseRPSOpponent($target_player_id)
    {
        $this->gamestate->checkPossibleAction('choseRPSOpponent');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} picks ${target_player_name} to play Rock-Paper-Scissor'),
            [
                'player_name'        => $players[$current_player_id]['player_name'],
                'target_player_name' => $players[$target_player_id]['player_name'],
            ]
        );

        self::setGameStateValue('special_skill_type', 12);
        self::setGameStateValue('rps_opponent', $target_player_id);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }

    function selectRPS($rps)
    {
        $this->gamestate->checkPossibleAction('selectRPS');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();

        $rps = substr($rps, 0, 1);

        $sql = "UPDATE player SET selected_rps = '$rps' WHERE player_id = $current_player_id";
        self::DbQuery($sql);

        $this->gamestate->setPlayerNonMultiactive($current_player_id, "multiActiveSkillCheckTransition");
    }

    function playRPS()
    {
        $players = self::loadPlayersBasicInfos();
        $winner = 0;
        $loser = 0;

        $sql = "SELECT player_id, selected_rps FROM player WHERE selected_rps IS NOT NULL";
        $result = self::getCollectionFromDb($sql);

        $this->resetRPSSelection();

        $opponents = array_keys($result);

        foreach ($result as $player_id => $data) {
            self::notifyAllPlayers(
                'showSpeechBubble',
                '',
                [
                    'player_id' => $player_id,
                    'text'      => 'ROCK!',
                    'length'    => 500,
                ]
            );
        }
        $this->doPause(500);

        foreach ($result as $player_id => $data) {
            self::notifyAllPlayers(
                'showSpeechBubble',
                '',
                [
                    'player_id' => $player_id,
                    'text'      => 'PAPER!',
                    'length'    => 500,
                ]
            );
        }
        $this->doPause(500);

        foreach ($result as $player_id => $data) {
            self::notifyAllPlayers(
                'showSpeechBubble',
                '',
                [
                    'player_id' => $player_id,
                    'text'      => 'SCISSORS!',
                    'length'    => 500,
                ]
            );
        }
        $this->doPause(1000);

        if ($result[$opponents[0]]['selected_rps'] == $result[$opponents[1]]['selected_rps']) {
            self::notifyAllPlayers(
                'msg',
                clienttranslate('Draw. Both players showed ${rps}. They have to play again.'),
                [
                    'rps' => $this->langitem['rps_' . $result[$opponents[0]]['selected_rps']],
                ]
            );
        } else {
            self::notifyAllPlayers(
                'msg',
                clienttranslate('${player_one} shows ${rps_player_one}<br>${player_two} shows ${rps_player_two}'),
                [
                    'player_one'     => $players[$opponents[0]]['player_name'],
                    'player_two'     => $players[$opponents[1]]['player_name'],
                    'rps_player_one' => $this->langitem['rps_' . $result[$opponents[0]]['selected_rps']],
                    'rps_player_two' => $this->langitem['rps_' . $result[$opponents[1]]['selected_rps']],
                ]
            );
            switch ($result[$opponents[0]]['selected_rps']) {
                case 'r':
                    if ($result[$opponents[1]]['selected_rps'] == 's') {
                        $winner = $opponents[0];
                        $loser = $opponents[1];
                    } else {
                        $winner = $opponents[1];
                        $loser = $opponents[0];
                    }
                    break;
                case 'p':
                    if ($result[$opponents[1]]['selected_rps'] == 'r') {
                        $winner = $opponents[0];
                        $loser = $opponents[1];
                    } else {
                        $winner = $opponents[1];
                        $loser = $opponents[0];
                    }
                    break;
                case 's':
                    if ($result[$opponents[1]]['selected_rps'] == 'p') {
                        $winner = $opponents[0];
                        $loser = $opponents[1];
                    } else {
                        $winner = $opponents[1];
                        $loser = $opponents[0];
                    }
                    break;
            }
        }


        foreach ($result as $player_id => $data) {
            $rps_choice = $data['selected_rps'];

            if ($player_id == $winner) {
                $text = "<strong>" . strtoupper($this->langitem['rps_' . $rps_choice]) . "!</strong>";
            } else {
                $text = strtoupper($this->langitem['rps_' . $rps_choice]) . "!";
            }
            self::notifyAllPlayers(
                'showSpeechBubble',
                '',
                [
                    'player_id' => $player_id,
                    'text'      => $text,
                ]
            );
        }

        $this->doPause(3000);

        if ($winner) {

            $loser_assets = $this->getPlayersAssets($loser);
            $amount = floor($loser_assets['coins_total'] / 2);

            self::notifyAllPlayers(
                'msg',
                clienttranslate('${winner_name} wins against ${loser_name}. <br><br>${loser_name} loses ${amount} coins.'),
                [
                    'winner_name' => $players[$winner]['player_name'],
                    'loser_name'  => $players[$loser]['player_name'],
                    'amount'      => $amount,
                ]
            );

            $this->manageCoins($loser, 'sub', $amount);
            $this->manageCoins($winner, 'add', $amount);
            $this->runSwitchCoinAnimation($loser, $winner, $amount);
            $this->refreshPlayerAssets();

            self::setGameStateValue('skill_done', 1);
            self::setGameStateValue('special_skill_type', 9999);
        } else {
            self::setGameStateValue('special_skill_type', 12);
        }
        $this->gamestate->nextState('multiActiveSkill');
    }

    function switchMoney($target_player_id)
    {
        $this->gamestate->checkPossibleAction('switchMoney');

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();

        self::giveExtraTime($current_player_id);

        $player_assets1 = $this->getPlayersAssets($current_player_id);
        $player_assets2 = $this->getPlayersAssets($target_player_id);

        $this->revealPlayerMoney($current_player_id);
        $this->revealPlayerMoney($target_player_id);

        // notify players whose money is revealed
        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player1_name} and ${player2_name} reveal and switch their money'),
            [
                'player1_name' => $players[$current_player_id]['player_name'],
                'player2_name' => $players[$target_player_id]['player_name'],
            ]
        );

        if ($player_assets1['coins_total'] >= $player_assets2['coins_total']) {
            $from_player = $current_player_id;
            $to_player = $target_player_id;
            $amount = $player_assets1['coins_total'] - $player_assets2['coins_total'];
        } else {
            $from_player = $target_player_id;
            $to_player = $current_player_id;
            $amount = $player_assets2['coins_total'] - $player_assets1['coins_total'];
        }

        $this->manageCoins($to_player, 'add', $amount);
        $this->manageCoins($from_player, 'sub', $amount);

        self::incStat($amount, "money_gained_from_players", $to_player);
        self::incStat($amount, "money_lost_to_players", $from_player);

        $this->runSwitchCoinAnimation($from_player, $to_player, $amount);

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player1_name} and ${player2_name} are switching their money'),
            [
                'player1_name' => $players[$current_player_id]['player_name'],
                'player2_name' => $players[$target_player_id]['player_name'],
            ]
        );

        $this->refreshPlayerAssets();

        self::setGameStateValue('skill_done', 1);
        $this->gamestate->setAllPlayersNonMultiactive("multiActiveSkill");
    }

    function copySkill($skill_type_id)
    {
        $this->gamestate->checkPossibleAction('copySkill');

        $this->doPause(1);

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = self::getCurrentPlayerName();
        self::setGameStateValue('copied_skill_type_id', $skill_type_id);

        $this->runSwitchCardTextAnimation($current_player_id, $skill_type_id);

        $skill_with_player_input = $this->resolveInstantSkills($current_player_id, $skill_type_id);

        if ($skill_with_player_input) {
            self::setGameStateValue('skill_to_resolve_card_type', $skill_type_id);
            self::setGameStateValue('special_skill_type', 0);
            $this->gamestate->nextState('multiActiveSkill');
        } else {
            $this->gamestate->nextState('endOfTurnCleanup');
        }
    }

    function nameCardToForcePlay($card_type)
    {
        $this->gamestate->checkPossibleAction('nameCardToForcePlay');

        $this->doPause(1);

        $players = self::loadPlayersBasicInfos();
        $current_player_id = self::getCurrentPlayerId();
        $current_player_name = $this->getCurrentPlayerName();

        //get card id from card type
        $sql = "SELECT card_id FROM card WHERE card_type = $card_type";
        $card_id = self::getUniqueValueFromDB($sql);
        
        // Set the named card in game state
        self::setGameStateValue('named_card_id', $card_id);

        //update forced_card_id in player table of all player
        $sql = "UPDATE player SET forced_card_id = $card_id";
        self::DbQuery($sql);

        self::notifyAllPlayers(
            'msg',
            clienttranslate('${player_name} names the ${card_name} card'),
            [
                'player_name' => $players[$current_player_id]['player_name'],
                'card_name'   => $this->merchant_card[$this->getCardType($card_id)]['name'],
            ]
        );

        $this->gamestate->nextState('endOfTurnCleanup');
    }

    function nameCardToTakeMoney($card_type)
    {
            $this->gamestate->checkPossibleAction('nameCardToTakeMoney');

            $this->doPause(1);

            $players = self::loadPlayersBasicInfos();
            $current_player_id = self::getCurrentPlayerId();
            $current_player_name = $this->getCurrentPlayerName();

            //get card id from card type
            $sql = "SELECT card_id FROM card WHERE card_type = $card_type";
            $card_id = self::getUniqueValueFromDB($sql);
            
            // Set the named card in game state
            self::setGameStateValue('named_card_id', $card_id);

            //update forced_card_id in player table of all player
            $sql = "UPDATE player SET give_three_coins_card_id = $card_id";
            self::DbQuery($sql);

            self::notifyAllPlayers(
                'msg',
                clienttranslate('${player_name} names the ${card_name} card'),
                [
                    'player_name' => $players[$current_player_id]['player_name'],
                    'card_name'   => $this->merchant_card[$this->getCardType($card_id)]['name'],
                ]
            );

            $this->takeThreeMoneyFromNamedCardHolder($current_player_id);

            $this->gamestate->nextState('endOfTurnCleanup');
        }

        function takeThreeMoneyFromNamedCardHolder($current_player_id) {
        $named_card_id = self::getGameStateValue('named_card_id');
        if ($named_card_id == 0) return; // No named card
        
        $players = self::loadPlayersBasicInfos();
        $current_player_name = $this->getPlayerName($current_player_id);
        
        // Check if any player has the named card in hand
        $sql = "SELECT card_location_arg as player_id FROM card 
                WHERE card_id = $named_card_id 
                AND card_location = 'hand'";
        $result = self::getCollectionFromDb($sql);
        
        if (count($result)) {
            $target_player_id = key($result);
            $target_player_name = $players[$target_player_id]['player_name'];
            $card_name = $this->merchant_card[$this->getCardType($named_card_id)]['name'];
            
            // Check if target player has at least 3 coins
            $target_coins = $this->getPlayersAssets($target_player_id)['coins_total'];
            $amount_to_take = min(3, $target_coins);
            
            if ($amount_to_take > 0) {
                // Take money from target player and give to current player
                $this->manageCoins($target_player_id, 'sub', $amount_to_take, true);
                $this->manageCoins($current_player_id, 'add', $amount_to_take, false);
                
                self::notifyAllPlayers(
                    'msg', 
                    clienttranslate('${player_name} takes ${amount} coins from ${target_player_name} who had the named card (${card_name})'),
                    [
                        'player_name' => $current_player_name,
                        'target_player_name' => $target_player_name,
                        'amount' => $amount_to_take,
                        'card_name' => $card_name
                    ]
                );
                
                $this->runSwitchCoinAnimation($target_player_id, $current_player_id, $amount_to_take);
                $this->refreshPlayerAssets();
            } else {
                self::notifyAllPlayers(
                    'msg', 
                    clienttranslate('${target_player_name} has the named card (${card_name}) but no coins to give'),
                    [
                        'target_player_name' => $target_player_name,
                        'card_name' => $card_name
                    ]
                );
            }
        } else {
            self::notifyAllPlayers(
                'msg', 
                clienttranslate('No player has the named card (${card_name}) in hand'),
                [
                    'card_name' => $this->merchant_card[$this->getCardType($named_card_id)]['name']
                ]
            );
        }
        
        // Reset the named card
        self::setGameStateValue('named_card_id', 0);
        $sql = "UPDATE player SET give_three_coins_card_id = 0";
        self::DbQuery($sql);
    }


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

    function argDiscardOneCard()
    {
        return [];
    }

    function argMultiActiveSkill()
    {
        $players = self::loadPlayersBasicInfos();
        $skill_player_id = self::getGameStateValue('skill_to_resolve_player');
        $skill_player_name = $players[$skill_player_id]['player_name'];
        $skill_type = $this->getCurrentMultiActiveSkillType();
        $skill_target_player_id = null;
        $selected_player_name = '';
        $target_player_name = '';
        $selectable_players = [];
        $amt_selectable_players = 0;
        $skills_to_copy = [];
        $all_cards = [];

        switch ($skill_type) {
            case 'steal_three_coins':
            case 'steal_half_money':
            case 'steal_one_card':
            case 'rock_paper_scissor':
                $amt_selectable_players = 1;
                $selectable_players[$skill_player_id] = $this->getOtherPlayers($skill_player_id);
                break;
            case 'all_players_pass_one_card':
                break;
            case 'reveal_and_switch_money':
                $amt_selectable_players = 1;
                $selectable_players[$skill_player_id] = $this->getOtherPlayers($skill_player_id);
                break;
            case 'return_one_card':
                $selected_player_id = self::getGameStateValue('return_card_to_player');
                $skill_target_player_id = $selected_player_id;
                $selected_player_name = $players[$selected_player_id]['player_name'];
                break;
            case 'reveal_money_and_give_two_coin_to_neighbours':
                $this->revealPlayerMoney($skill_player_id);
                $this->doPause(500);
                $asset = $this->getPlayersAssets($skill_player_id);
                if ($asset['coins_total'] == 1) {
                    $amt_selectable_players = 1;
                    $selectable_players[$skill_player_id] = $this->getPlayerNeighbors($skill_player_id);
                }
                break;
            case 'copy_skill':
                $skills_to_copy = $this->getSkillsToCopy();
                break;
            case 'gain_one_coin_and_name_card':
                $all_cards = $this->getAllCardsToForce();
                break;
            case 'name_card_and_take_three_coins':
                $all_cards = $this->getAllCardsToTakeThreeMoney();
                break;
            case 'pick_rps':
                $selected_player_id = self::getGameStateValue('rps_opponent');
                $selected_player_name = $players[$selected_player_id]['player_name'];
                break;
        }

        $status_bar_texts = isset($this->skill_actions_langitems[$skill_type]) ? $this->skill_actions_langitems[$skill_type] : '';
        //        if ($status_bar_texts != '') {
        //            $status_bar_texts['active'] = str_replace('${active_player_name}', $skill_player_name,
        //                $status_bar_texts['active']);
        //            $status_bar_texts['passive'] = str_replace('${active_player_name}', $skill_player_name,
        //                $status_bar_texts['passive']);
        //
        //            $status_bar_texts['active'] = str_replace('${target_player_name}', $skill_player_name,
        //                $status_bar_texts['active']);
        //            $status_bar_texts['passive'] = str_replace('${target_player_name}', $skill_player_name,
        //                $status_bar_texts['passive']);
        //
        //            $status_bar_texts['active'] = str_replace('${selected_player_name}', $selected_player_name,
        //                $status_bar_texts['active']);
        //            $status_bar_texts['passive'] = str_replace('${selected_player_name}', $selected_player_name,
        //                $status_bar_texts['passive']);
        //
        //        }

        return [
            'skill_player_id'        => $skill_player_id,
            'skill_type'             => $skill_type,
            'selectable_players'     => $selectable_players,
            'target_player_id'       => $skill_target_player_id,
            'amt_selectable_players' => $amt_selectable_players,
            'status_bar_texts'       => $status_bar_texts,
            'skills_to_copy'         => $skills_to_copy,
            'all_cards'             => $all_cards,
            'placeholders'           => [
                'skill_player_name'    => $skill_player_name,
                'selected_player_name' => $selected_player_name,
            ],
        ];
    }

    function giveTimeToAllPlayers()
    {
        $players = self::loadPlayersBasicInfos();
        foreach ($players as $player_id => $player) {
            self::giveExtraTime($player_id);
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////


    function st_MultiPlayerInit()
    {
        $this->gamestate->setAllPlayersMultiactive();
    }


    function stSetupNewRound()
    {
        // Increase round number
        $current_round = self::incGameStateValue('current_round', 1);

        // All cards go back into the deck
        $this->cards->moveAllCardsInLocation(null, 'deck');
        $this->setAllCardsAsNotRevealed();
        $this->resetPlayedCards();
        $this->refreshPlayedCards();

        // Clean everything from the previous round
        if ($current_round > 1) {
            self::notifyAllPlayers('resetAllCards', '', []);
            $this->doPause(2000);
        }

        self::notifyAllPlayers('msg', clienttranslate('Round ${round_counter}'), ['round_counter' => $current_round]);

        // Set move number
        self::setGameStateValue('current_move', 0);

        // In a 3 player game, the first move is to discard the 5th card
        $players_count = self::getGameStateValue('players_count');
        if ($players_count == 3) {
            self::setGameStateValue('discard_move', 1);
        }

        $players = self::loadPlayersBasicInfos();

        // Deal coins
        // foreach ($players as $player_id => $player) {
        //     $this->manageCoins($player_id, 'set', $this->setup_rules['coins_per_round']);
        // }
        // $this->refreshPlayerAssets();

        $sql = "SELECT SUM(contracts_won) FROM player";
        $sum_contracts_won = self::getUniqueValueFromDB($sql);

        // Calculate current top contract value (5 + round number - 1)
        $top_contract_value = 5 + ($sum_contracts_won * 2);

        // Update all players' top contract value
        $sql = "UPDATE player SET top_contract_value = $top_contract_value";
        self::DbQuery($sql);

        $players = self::loadPlayersBasicInfos();

        // Adjust each player's money based on top contract value
        foreach ($players as $player_id => $player) {
            $player_assets = $this->getPlayersAssets($player_id);
            $current_coins = $player_assets['coins_total'];

            if ($current_coins > $top_contract_value) {
                // Player has too much money - reduce to top contract value
                $coins_to_remove = $current_coins - $top_contract_value;
                $this->manageCoins($player_id, 'sub', $coins_to_remove);

                self::notifyPlayer(
                    $player_id,
                    'moneyAdjusted',
                    clienttranslate('Your money exceeds the contract limit (${limit}). ${amount} coin(s) were returned to the bank.'),
                    [
                        'amount' => $coins_to_remove,
                        'limit' => $top_contract_value,
                        'player_id' => $player_id
                    ]
                );
            } else if ($current_coins < $top_contract_value) {
                // Player doesn't have enough - no change needed
                self::notifyPlayer(
                    $player_id,
                    'moneyAdjusted',
                    clienttranslate('Your money (${current}) is below the contract limit (${limit}). No coins are returned.'),
                    [
                        'current' => $current_coins,
                        'limit' => $top_contract_value,
                        'player_id' => $player_id
                    ]
                );
            } else {
                // Player has exactly the right amount
                self::notifyPlayer(
                    $player_id,
                    'moneyAdjusted',
                    clienttranslate('Your money matches the contract limit (${limit}). No coins are returned.'),
                    [
                        'limit' => $top_contract_value,
                        'player_id' => $player_id
                    ]
                );
            }
        }

        $this->refreshPlayerAssets();
        $this->doPause(1500); // Pause to let players see the notifications

        $this->refreshPlayerAssets();


        // Deal 1 merchant ship to each player
        foreach ($players as $player_id => $player) {
            $merchant_ship_type_id = $this->merchant_ship_type_id;
            $sql = "UPDATE card SET card_location = 'hand', card_location_arg = $player_id WHERE card_location = 'deck' AND card_type = $merchant_ship_type_id LIMIT 1";
            self::DbQuery($sql);
        }

        // Shuffle the rest
        $this->cards->shuffle('deck');

        // Deal remaining cards to players
        foreach ($players as $player_id => $player) {
            $this->cards->pickCards($this->setup_rules['cards_to_deal'][$players_count], 'deck', $player_id);

            // Deal animation
            self::notifyPlayer(
                $player_id,
                'drawNewCards',
                '',
                [
                    'player_id' => $player_id,
                    'new_cards' => $this->cards->getCardsInLocation('hand', $player_id),
                ]
            );
        }


        // Playing with 3 players, every player has to discard a card first
        // Otherwise, players select a card to play on the table
        if (self::getGameStateValue('discard_move') == 1) {
            $this->gamestate->nextState('discardOneCard');
        } else {
            $this->gamestate->nextState('selectCard');
        }
    }

    function stRevealDiscardedCards()
    {
        $players = self::loadPlayersBasicInfos();

        $sql = "SELECT selected_card_id, player_id FROM player WHERE selected_card_id > 0";
        $selected_card_ids = self::getCollectionFromDB($sql);

        foreach ($selected_card_ids as $selected_card_id => $data) {
            $this->cards->playCard($selected_card_id);
        }

        $sql = "SELECT * FROM card WHERE card_id in (" . implode(',', array_keys($selected_card_ids)) . ")";
        $selected_cards = self::getCollectionFromDB($sql);

        foreach ($selected_card_ids as $selected_card_id => $data) {
            $selected_cards[$selected_card_id]['player_id'] = $data['player_id'];
        }

        self::setGameStateValue('discard_move', 0);

        self::notifyAllPlayers('discardCards_all', $this->langitem['discarding_cards'], [
            'selected_cards' => $selected_cards,
        ]);

        $this->logCardsAsPlayed($this->getSelectedCards(), 'd');
        $this->refreshPlayedCards();

        $this->resetCardSelections();
        $this->gamestate->nextState('selectCard');

        self::giveTimeToAllPlayers();
    }

    function stSelectCard()
    {
        self::incGameStateValue('current_move', 1);
        $this->st_MultiPlayerInit();
    }

    function stResolveSelectedCards()
    {
        self::giveTimeToAllPlayers();
        $players = self::loadPlayersBasicInfos();
        $current_move = self::getGameStateValue('current_move');
        $current_player_id = self::getCurrentPlayerId();

        $move_results = [];
        $played_ships_players = [];
        $merchants = [];
        $type_ids = [];

        $sql = "SELECT selected_card_id, player_id FROM player WHERE selected_card_id > 0";
        $selected_card_ids = self::getCollectionFromDB($sql);

        foreach ($selected_card_ids as $selected_card_id => $data) {
            $this->cards->moveCard($selected_card_id, 'table', $data['player_id']);
            $this->setCardAsRevealed($selected_card_id);
        }
        $this->logCardsAsPlayed(self::getSelectedCards(), self::getGameStateValue('current_round'));

        $sql = "SELECT * FROM card WHERE card_id in (" . implode(',', array_keys($selected_card_ids)) . ")";
        $selected_cards_details = self::getCollectionFromDB($sql);

        foreach ($selected_card_ids as $selected_card_id => $data) {
            $selected_cards_details[$selected_card_id]['player_id'] = $data['player_id'];
        }

        $this->doPause(1000);
        self::notifyAllPlayers('revealCards_all', $this->langitem['revealing_cards'], [
            'selected_cards' => $selected_cards_details,
        ]);
        $this->doPause(2000);

        self::notifyAllPlayers('flipCards_all', '', [
            'selected_cards' => $selected_cards_details,
        ]);
        $this->doPause(750);
        $this->refreshPlayedCards();
        $this->doPause(2000);


        // LET'S GET SOME PLAYED CARD DATA
        $count_ships = 0;
        foreach ($selected_cards_details as $card_id => $card_detail) {
            if ($card_detail['card_type'] == $this->merchant_ship_type_id) {
                $count_ships++;
                $played_ships_players[$selected_card_ids[$card_id]['player_id']] = $card_id;
            } else {
                $merchants[$card_id] = $this->merchant_card[$card_detail['card_type']];
                $merchants[$card_id]['player_id'] = $selected_card_ids[$card_id]['player_id'];
                $merchants[$card_id]['card_id'] = $card_id;
                $type_ids[] = $card_detail['card_type'];
            }
        }

        // LET'S DEAL WITH THE SHIPS FIRST
        $amt_coins_to_add = 0;
        $message = '';
        switch ($count_ships) {
            case 0:
                $message = $this->langitem['no_ships_played'];
                break;
            case 1:
                $amt_coins_to_add = $this->scoring_rules['coins']['only_ship'];
                $message = $this->langitem['one_ship_played'];
                break;
            case 2:
                $amt_coins_to_add = $this->scoring_rules['coins']['two_ships'];
                $message = $this->langitem['two_ships_played'];
                break;
            case 3:
            case 4:
            case 5:
                $message = $this->langitem['more_than_two_ships_played'];
                break;
        }
        $player_names = [];


        $this->lightUpPlayersCards(array_keys($played_ships_players));
        $this->doPause(500);

        $expand_cards_element_ids = [];
        foreach ($played_ships_players as $played_ships_player_id => $card_id) {
            $this->manageCoins($played_ships_player_id, 'add', $amt_coins_to_add);
            self::incStat($amt_coins_to_add, "money_gained_from_ships", $played_ships_player_id);
            if ($amt_coins_to_add == 3) {
                self::incStat(1, "only_ship", $played_ships_player_id);
            }
            $this->runAddCoinAnimation($played_ships_player_id, $amt_coins_to_add);
            $player_names[] = $this->getPlayerName($played_ships_player_id);
            $expand_cards_element_ids[] = 'flip-card_' . $played_ships_player_id;
            $expand_cards_element_ids[] = 'card_' . $card_id;
        }

        //        $this->addClass($expand_cards_element_ids);
        //        $this->doPause(1000);

        self::notifyAllPlayers(
            'msg',
            $message,
            [
                'player_name'  => isset($player_names[0]) ? $player_names[0] : '',
                'player_name2' => isset($player_names[1]) ? $player_names[1] : '',
                'amount'       => $amt_coins_to_add,
            ]
        );
        //        $this->doPause(2000);
        //        $this->removeClass($expand_cards_element_ids);
        $this->doPause(1000);

        $this->removeLitUpPlayerCards();
        $this->removeCoinsAndContractsFromPlayerTable();
        $this->doPause(1000);


        // NOW REWARD THE HIGHEST CARD(S)

        $reputation_to_zero_applied = [];
        // Deal with the special conditions
        foreach ($merchants as $card_id => $merchant) {
            if (isset($merchant['rival_card_ids'])) {
                if (count($merchant['rival_card_ids']) > 0) {
                    foreach ($merchant['rival_card_ids'] as $rival_card_id) {
                        if (in_array($rival_card_id, $type_ids)) {
                            $merchants[$card_id]['reputation'] = 0;
                            if (!in_array($merchant['player_id'], $reputation_to_zero_applied)) {
                                $reputation_to_zero_applied[] = $merchant['player_id'];
                                self::notifyAllPlayers(
                                    'reputationToZero',
                                    $this->langitem['reputation_set_zero'],
                                    [
                                        'player_id'     => $merchant['player_id'],
                                        'rival_card_id' => $rival_card_id,
                                        'player_name'   => $this->getPlayerName($merchant['player_id']),
                                    ]
                                );
                                $this->doPause(1000);
                            }
                        }
                    }
                }
            }
        }

        // Sort the merchants by their reputation
        usort($merchants, function ($a, $b) {
            return $b['reputation'] <=> $a['reputation'];
        });


        // NOW, DEAL WITH THE RESULTS
        $count_merchants = count($merchants);


        if ($count_merchants > 0) {
            $move_results['highest'] = [
                'reputation' => $merchants[0]['reputation'],
                'player_id'  => $merchants[0]['player_id'],
                'type_id'    => $merchants[0]['type_id'],
            ];


            if ($count_merchants != 2) {
                $move_results['lowest'][0] = [
                    'reputation' => $merchants[sizeof($merchants) - 1]['reputation'],
                    'player_id'  => $merchants[sizeof($merchants) - 1]['player_id'],
                    'type_id'    => $merchants[sizeof($merchants) - 1]['type_id'],
                    'card_id'    => $merchants[sizeof($merchants) - 1]['card_id'],
                ];

                // Are two cards the lowest with both 0 reputation?
                if ($merchants[sizeof($merchants) - 1]['reputation'] == 0) {
                    $move_results['lowest'][0] = [
                        'reputation' => $merchants[sizeof($merchants) - 1]['reputation'],
                        'player_id'  => $merchants[sizeof($merchants) - 1]['player_id'],
                        'type_id'    => $merchants[sizeof($merchants) - 1]['type_id'],
                        'card_id'    => $merchants[sizeof($merchants) - 1]['card_id'],
                    ];
                }
                if (isset($merchants[sizeof($merchants) - 2])) {
                    if ($merchants[sizeof($merchants) - 2]['reputation'] == 0) {
                        $move_results['lowest'][1] = [
                            'reputation' => $merchants[sizeof($merchants) - 2]['reputation'],
                            'player_id'  => $merchants[sizeof($merchants) - 2]['player_id'],
                            'type_id'    => $merchants[sizeof($merchants) - 2]['type_id'],
                            'card_id'    => $merchants[sizeof($merchants) - 2]['card_id'],
                        ];
                    }
                }
                if (isset($merchants[sizeof($merchants) - 3])) {
                    if ($merchants[sizeof($merchants) - 3]['reputation'] == 0) {
                        $move_results['lowest'][2] = [
                            'reputation' => $merchants[sizeof($merchants) - 3]['reputation'],
                            'player_id'  => $merchants[sizeof($merchants) - 3]['player_id'],
                            'type_id'    => $merchants[sizeof($merchants) - 3]['type_id'],
                            'card_id'    => $merchants[sizeof($merchants) - 3]['card_id'],
                        ];
                    }
                }
                usort($move_results['lowest'], function ($a, $b) {
                    return $b['type_id'] <=> $a['type_id'];
                });

                foreach ($move_results['lowest'] as $move_result) {
                    $this->addToSkillQueue(
                        $move_result['player_id'],
                        $move_result['card_id'],
                        $move_result['type_id'],
                        $move_result['reputation']
                    );
                }
            }
            switch ($count_merchants) {
                case 1:
                    $move_results['second_highest'] = null;
                    break;
                default:
                    $move_results['second_highest'] = [
                        'reputation' => $merchants[1]['reputation'],
                        'player_id'  => $merchants[1]['player_id'],
                        'type_id'    => $merchants[1]['type_id'],
                    ];
                    break;
            }

            switch ($count_merchants) {
                case 1:
                    self::notifyAllPlayers('msg', $this->langitem['monopoly_rule'], []);
                    break;
                case 2:
                    self::notifyAllPlayers('msg', $this->langitem['solo_rule'], []);
                    break;
            }

            $this->doPause(500);
            $this->lightUpMerchants($move_results);
            $this->doPause(500);

            // Coins for the winners!!
            $this->manageCoins(
                $move_results['highest']['player_id'],
                'add',
                $this->scoring_rules['coins']['highest']
            );
            $this->runAddCoinAnimation($move_results['highest']['player_id'], $this->scoring_rules['coins']['highest']);
            self::notifyAllPlayers(
                'msg',
                $this->langitem['highest_card_played'],
                [
                    'player_name' => $this->getPlayerName($move_results['highest']['player_id']),
                    'reputation'  => $move_results['highest']['reputation'],
                    'coin_amount' => $this->scoring_rules['coins']['highest'],
                ]
            );
            $this->removeLitUpPlayerCards($move_results['highest']['player_id']);


            if ($move_results['second_highest']) {
                $this->doPause(1000);
                $this->manageCoins(
                    $move_results['second_highest']['player_id'],
                    'add',
                    $this->scoring_rules['coins']['second_highest']
                );
                $this->runAddCoinAnimation(
                    $move_results['second_highest']['player_id'],
                    $this->scoring_rules['coins']['second_highest']
                );
                self::notifyAllPlayers(
                    'msg',
                    $this->langitem['second_highest_card_played'],
                    [
                        'player_name' => $this->getPlayerName($move_results['second_highest']['player_id']),
                        'reputation'  => $move_results['second_highest']['reputation'],
                        'coin_amount' => $this->scoring_rules['coins']['second_highest'],
                    ]
                );
                $this->removeLitUpPlayerCards($move_results['second_highest']['player_id']);
            }
            //
            //        echo "<pre>";
            //        print_r($merchants);
            //        print_r($move_results);
            //        echo "</pre>";
            $this->doPause(1000);

            $this->removeCoinsAndContractsFromPlayerTable();
            $this->doPause(2000);

            // AT LAST, ACTIVATE CARD SKILLS
            $this->activateNextSkillInQueue();
        } else {
            $this->gamestate->nextState('endOfTurnCleanup');
        }
    }

    function stMultiActiveSkill()
    {
        $players = self::loadPlayersBasicInfos();

        $skill_player_id = self::getGameStateValue('skill_to_resolve_player');
        $skill_type = $this->getCurrentMultiActiveSkillType();

        $skill_actions_done = self::getGameStateValue('skill_actions_done');
        $skill_done = self::getGameStateValue('skill_done');

        //        self::setGameStateValue('skill_to_resolve_card_type', 0);
        //        self::setGameStateValue('skill_to_resolve_player', 0);


        if (!$skill_done) {
            switch ($skill_type) {
                case 'reveal_money_and_give_two_coin_to_neighbours':
                    $asset = $this->getPlayersAssets($skill_player_id);
                    switch ($asset['coins_total']) {
                        case 0:
                            // Player got no coins? Skip
                            self::notifyAllPlayers(
                                'msg',
                                clienttranslate('${player_name} has no coins to give'),
                                [
                                    'player_name' => $players[$skill_player_id]['player_name'],
                                ]
                            );
                            $skill_done = true;
                            break;
                        case 1:
                            // Player only has one coin? Pick a player to give this coin to
                            $this->gamestate->setPlayersMultiactive([$skill_player_id], 'multiActiveSkill');
                            break;
                        default:
                            $player_neighbors = $this->getPlayerNeighbors($skill_player_id);
                            $this->manageCoins($skill_player_id, 'sub', 1);
                            $this->manageCoins($player_neighbors[0], 'add', 1);
                            self::incStat(1, "money_gained_from_players", $player_neighbors[0]);
                            self::incStat(1, "money_lost_to_players", $skill_player_id);
                            self::notifyAllPlayers(
                                'msg',
                                clienttranslate('${player_name} gives one coin to ${target_player_name}'),
                                [
                                    'player_name'        => $players[$skill_player_id]['player_name'],
                                    'target_player_name' => $players[$player_neighbors[0]]['player_name'],
                                ]
                            );
                            $this->runSwitchCoinAnimation($skill_player_id, $player_neighbors[0], 1);
                            $this->doPause(500);
                            $this->manageCoins($skill_player_id, 'sub', 1);
                            $this->manageCoins($player_neighbors[1], 'add', 1);
                            self::incStat(1, "money_gained_from_players", $player_neighbors[1]);
                            self::incStat(1, "money_lost_to_players", $skill_player_id);
                            self::notifyAllPlayers(
                                'msg',
                                clienttranslate('${player_name} gives one coin to ${target_player_name}'),
                                [
                                    'player_name'        => $players[$skill_player_id]['player_name'],
                                    'target_player_name' => $players[$player_neighbors[1]]['player_name'],
                                ]
                            );
                            $this->runSwitchCoinAnimation($skill_player_id, $player_neighbors[1], 1);
                            $this->doPause(500);
                            $skill_done = true;
                            break;
                    }
                    break;
                case 'reveal_all_players_money_and_check_less_money':
                    $this->revealAllMoneyAndCheckLess($skill_player_id);
                    $skill_done = true;
                    break;
                case 'steal_three_coins':
                case 'steal_half_money':
                case 'steal_one_card':
                case 'return_one_card':
                case 'reveal_and_switch_money':
                case 'rock_paper_scissor':
                case 'copy_skill':
                    if (count($this->getSkillsToCopy()) > 0) {
                        $this->gamestate->setPlayersMultiactive([$skill_player_id], 'multiActiveSkill');
                    } else {
                        self::notifyAllPlayers(
                            'msg',
                            '${player_name} can\'t activate this skill, since there are no skills to copy available.',
                            [
                                'player_name' => $this->getPlayerName($skill_player_id),
                            ]
                        );
                        $skill_done = true;
                    }
                    break;
                case 'gain_one_coin_and_name_card':
                    // add 1 coin to player
                    $this->manageCoins($skill_player_id, 'add', 1, true);
                    $this->runAddCoinAnimation($skill_player_id, 1);
                    $this->doPause(500);
                    $this->removeCoinsAndContractsFromPlayerTable();
                    $this->doPause(500);
                    $this->refreshPlayerAssets();
                    $this->doPause(2000);
                    $this->gamestate->setPlayersMultiactive([$skill_player_id], 'multiActiveSkill');
                    break;
                case 'name_card_and_take_three_coins':
                    $this->gamestate->setPlayersMultiactive([$skill_player_id], 'multiActiveSkill');
                    break;
                case 'all_players_pass_one_card':
                    // if it's the last card, auto-pass it
                    $current_move = self::getGameStateValue('current_move');
                    if ($current_move == 3) {
                        $this->autoSelectLastCard();
                        $this->moveCardsToLeft();
                        $skill_done = true;
                    } else {
                        $this->gamestate->setAllPlayersMultiactive();
                    }
                    break;
                case 'pick_rps':
                    $this->gamestate->setPlayersMultiactive([$skill_player_id], 'multiActiveSkill');
                    $this->gamestate->setPlayersMultiactive(
                        [self::getGameStateValue('rps_opponent')],
                        'multiActiveSkill'
                    );
                    $this->enableRPSButtons($skill_player_id, self::getGameStateValue('rps_opponent'));
                    break;
                case 'play_rps':
                    $this->playRPS();
                    break;
                case 'moveCardsToLeft':
                    $this->moveCardsToLeft();
                    $skill_done = true;
                    break;
                case 'skill_tree_resolved':
                    $skill_done = true;
                    break;
            }
        }

        //        $this->st_MultiPlayerInit();
        if ($skill_done) {
            self::setGameStateValue('skill_done', 0);
            self::getGameStateValue('skill_to_resolve_player', 0);
            self::getGameStateValue('skill_to_resolve_card_type', 0);
            self::setGameStateValue('special_skill_type', 0);
            self::setGameStateValue('skill_actions_done', 0);
            $this->removeLitUpPlayerCards($skill_player_id);
            $this->setSkillInQueueAsDone();
            $this->gamestate->nextState('activateSkills');
        }
    }

    function stMultiActiveSkillCheckTransition()
    {
        self::setGameStateValue('skill_actions_done', 1);
        $skill_type = $this->getCurrentMultiActiveSkillType();

        switch ($skill_type) {
            case 'all_players_pass_one_card':
                self::setGameStateValue('special_skill_type', 32);
                break;
            case 'pick_rps':
                self::setGameStateValue('special_skill_type', 13);
                break;
            case 'gain_one_coin_and_name_card':
                self::setGameStateValue('special_skill_type', 11);
                break;
        }

        $this->gamestate->nextState('multiActiveSkill');
    }

    function stEndOfTurnCleanup()
    {
        $current_move = self::getGameStateValue('current_move');

        $this->removeLitUpPlayerCards();
        $this->doPause(500);

        // Move on to the next move
        $this->setSkillCardNonActive();
        $this->removeCoinsAndContractsFromPlayerTable();
        $this->doPause(500);
        $this->discardSelectedCards();
        $this->resetCardSelections();
        $this->resetCoinsThisTurn();
        $this->discardAllSkillsInQueue();

        self::setGameStateValue('copied_skill_type_id', 0);

        if ($current_move == 3) {
            $this->gamestate->nextState('resolveRound');
        } else {
            $this->gamestate->nextState('selectCard');
        }
    }

    function stActivateSkills()
    {
        $this->activateNextSkillInQueue();
    }

    function stResolveRound()
    {
        $this->revealAllMoneyForComparison();
        $this->doPause(1000);

        $winner = $this->getWinnerOfThisRound();
        $round_winner_player_id = $winner['winner_player_id'];

        if ($round_winner_player_id != 0) {
            $this->manageContracts($round_winner_player_id, 'add', 1);
            self::incStat($winner['highest_amount'], "money_spend_on_contracts", $round_winner_player_id);
            $this->runContractAnimation($round_winner_player_id, 1);
        }

        $victory_condition_met = $this->updatePlayerScores();
        $this->doPause(2000);
        $this->removeCoinsAndContractsFromPlayerTable();
        $this->doPause(2000);
        $this->hideUnrevealedMoney();
        $this->refreshPlayerAssets();
        $this->doPause(1000);

        // Check if we have a winner
        if ($victory_condition_met) {
            $this->gamestate->nextState('prepareGameEnd');
        } else {
            self::setGameStateValue("remaining_cards_revealed", 0);
            $this->removeEverythingFromTables();
            $this->doPause(1000);

            $this->gamestate->nextState('newRound');
        }
    }

    function stBuffer()
    {
        echo "<pre>";
        print_r('BUFFER');
        echo "</pre>";

        //        $this->gamestate->nextState('buffer');
        //        $this->gamestate->nextState('gameEnd');
    }

    function stPrepareGameEnd()
    {
        // Is there a tie? Try to solve it. Highest card in hand wins.
        $sql = "SELECT player_id, player_score FROM player WHERE player_score = (SELECT max(player_score) FROM player)";
        $winners = self::getCollectionFromDB($sql);

        if (sizeof(($winners)) > 1) {
            $players_most_contracts = [];
            foreach ($winners as $winner) {
                $remaining_card = $this->getRemainingPlayerHandCardValue($winner['player_id']);
                $players_most_contracts[] = [
                    'player_id'            => $winner['player_id'],
                    'remaining_card_value' => $remaining_card['value'],
                    'id'                   => $remaining_card['id'],
                ];
            }
            $this->revealFinalHandCards($players_most_contracts);
        }

        //        $this->gamestate->nextState('buffer');
        $this->gamestate->nextState('gameEnd');
    }


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

    private function resolveInstantSkills($player_id, $type_id)
    {
        $players = self::loadPlayersBasicInfos();

        $skill_with_player_input = false;
        $skill_type = $this->merchant_card[$type_id]['skill_type'];

        switch ($skill_type) {
            case 'gain_four_coins':
                $this->manageCoins($player_id, 'add', 4, true);
                $this->runAddCoinAnimation($player_id, 4);
                break;
            case 'gain_one_contract':
                $this->manageContracts($player_id, 'add', 1, true);
                $this->runContractAnimation($player_id, 1);
                break;
            case 'reveal_money_and_lose_three_coins':
                $this->revealPlayerMoney($player_id);
                $this->doPause(500);
                $this->manageCoins($player_id, 'sub', 3, true);
                $this->runSubCoinAnimation($player_id, 3);
                break;
            case 'gain_contract_and_all_players_gain_coin':
                $this->manageContracts($player_id, 'add', 1, true);
                $this->runContractAnimation($player_id, 1);
                foreach ($players as $opponent_id => $opponent) {
                    if ($opponent_id != $player_id) {
                        $this->manageCoins($opponent_id, 'add', 1, true);
                        $this->runAddCoinAnimation($opponent_id, 1);
                    }
                }
                break;
            case 'all_players_pay_you':
                foreach ($players as $opponent_id => $opponent) {
                    if ($opponent_id != $player_id) {
                        $amount = 1;
                        $sql = "SELECT contracts_won FROM player WHERE player_id = $opponent_id";
                        $contracts = self::getUniqueValueFromDB($sql);
                        if ($contracts > 0) $amount = 2;

                        $this->manageCoins($opponent_id, 'sub', $amount, true);
                        $this->manageCoins($player_id, 'add', $amount, false);
                        $this->runSwitchCoinAnimation($opponent_id, $player_id, $amount);
                    }
                }
                break;
            case 'return_one_coin':
                $coin_amounts = $this->manageCoins($player_id, 'sub', 1, true);
                if ($coin_amounts['old'] > 0) {
                    $this->runSubCoinAnimation($player_id, 1);
                }
                break;
            case 'return_gained_coins':
                $this->returnGainedCoins();
                break;
            // case 'gain_one_coin_and_name_card':
            //     $this->manageCoins($player_id, 'add', 1, true);
            //     $this->runAddCoinAnimation($player_id, 1);
            //     $this->doPause(500);
            //     $this->removeCoinsAndContractsFromPlayerTable();
            //     $this->doPause(500);
            //     $this->refreshPlayerAssets();
            //     $this->doPause(2000);
            //     break;
            case 'rock_paper_scissor':
                $this->doPause(500);
                $this->removeCoinsAndContractsFromPlayerTable();
                $this->doPause(500);
                $this->refreshPlayerAssets();
                $this->doPause(2000);
                $skill_with_player_input = true;
                break;
            case 'no_skill':
                break;
            default:
                $skill_with_player_input = true;
        }

        return $skill_with_player_input;
    }

    private function getWinnerOfThisRound()
    {
        $highest_amount = 0;
        $players_most_coins = [];
        $winner = null;

        $players_assets = $this->getPlayersAssets();
        foreach ($players_assets as $player_id => $assets) {
            if ($assets['coins_total'] > $highest_amount) {
                $highest_amount = $assets['coins_total'];
            }
        }

        foreach ($players_assets as $player_id => $assets) {
            if ($assets['coins_total'] == $highest_amount) {
                $remaining_card = $this->getRemainingPlayerHandCardValue($player_id);
                $players_most_coins[] = [
                    'player_id'            => $player_id,
                    'remaining_card_value' => $remaining_card['value'],
                    'id'                   => $remaining_card['id'],
                ];
            }
        }

        // In case of draw, look at the highest remaining card
        if (count($players_most_coins) > 1) {
            self::notifyAllPlayers(
                'msg',
                clienttranslate('${amount} players have the most coins. Revealing their final card to determine the winner.'),
                [
                    'amount' => count($players_most_coins),
                ]
            );
            $this->doPause(2000);

            $this->revealFinalHandCards($players_most_coins);
            $this->doPause(2000);

            $highest_value = 0;
            foreach ($players_most_coins as $one_player) {
                if ($one_player['remaining_card_value'] > $highest_value) {
                    $highest_value = $one_player['remaining_card_value'];
                    $winner = $one_player['player_id'];
                }
            }

            // tied players all had ships as their last card, no one wins this round
            if ($highest_value == 0) {
                self::notifyAllPlayers(
                    'msg',
                    clienttranslate('Tied players only have ships as their last card. There is no winner this round!'),
                    [
                        'amount' => count($players_most_coins),
                    ]
                );
                $this->doPause(2000);

                return 0;
            }
        } else {
            $winner = $players_most_coins[0]['player_id'];
        }

        return ['winner_player_id' => $winner, 'highest_amount' => $highest_amount];
    }

    private function revealFinalHandCards($player_cards)
    {
        if (self::getGameStateValue("remaining_cards_revealed") == 0) {
            self::setGameStateValue("remaining_cards_revealed", 1);
            self::notifyAllPlayers(
                'revealFinalHandCards',
                '',
                [
                    'players' => $player_cards,
                ]
            );
        }
    }

    private function getRemainingPlayerHandCardValue($player_id)
    {
        $player_hand = $this->cards->getPlayerHand($player_id);
        $first = array_shift($player_hand);
        $value = $this->merchant_card[$first['type']]['reputation'];
        $id = $first['id'];

        return ['id' => $id, 'value' => $value];
    }

    private function updatePlayerScores()
    {
        $victory_condition_met = false;

        $players = self::loadPlayersBasicInfos();

        $sql = "UPDATE player SET player_score = contracts_won";
        self::DbQuery($sql);

        foreach ($players as $player_id => $player) {
            $remaining_card = $this->getRemainingPlayerHandCardValue($player_id);
            $sql = "UPDATE player SET player_score_aux = " . $remaining_card['value'] . " WHERE player_id = " . $player_id;
            self::DbQuery($sql);
        }

        $sql = "SELECT max(player_score) FROM player";
        $max_score = self::getUniqueValueFromDB($sql);
        if ($max_score >= 3) {
            $victory_condition_met = true;
        }

        $scores = self::getCollectionFromDB("SELECT player_id, player_score FROM player", true);
        self::notifyAllPlayers("updatePlayersScore", '', ['scores' => $scores]);

        return $victory_condition_met;
    }

    private function refreshPlayerAssets()
    {
        self::notifyAllPlayers(
            'refreshPlayerAssets',
            '',
            [
                'player_assets' => $this->getPlayersAssets(),
            ]
        );
    }

    private function getPlayerNeighbors($player_id): array
    {
        $result[0] = $this->getPlayerBefore($player_id); // left
        $result[1] = $this->getPlayerAfter($player_id); // right

        return $result;
    }

    private function getAllPlayerNeighbors(): array
    {
        $players = self::loadPlayersBasicInfos();
        $result = [];

        foreach ($players as $player_id => $player_data) {
            $result[$player_id] = self::getPlayerNeighbors($player_id);
        }

        return $result;
    }


    private function getOtherPlayers($this_player_id): array
    {
        $result = [];
        $players = self::loadPlayersBasicInfos();

        foreach ($players as $player_id => $player_data) {
            if ($player_id != $this_player_id) {
                $result[] = $player_id;
            }
        }

        return $result;
    }

    private function getAllPlayers(): array
    {
        $result = [];
        $players = self::loadPlayersBasicInfos();

        return array_keys($players);
    }
    private function getAllCardsInLocation($location, $player_id = null)
    {
        $sql = "SELECT * FROM card WHERE card_location = '$location'";
        if ($player_id !== null) {
            $sql .= " AND card_location_arg = $player_id";
        }
        return self::getCollectionFromDB($sql);
    }

    private function setCardAsRevealed(int $selected_card_id)
    {
        $sql = "UPDATE card SET revealed = 1 WHERE card_id = " . $selected_card_id;
        self::DbQuery($sql);
    }

    private function setAllCardsAsNotRevealed()
    {
        $sql = "UPDATE card SET revealed = 0";
        self::DbQuery($sql);
    }

    private function getCardType(int $card_id)
    {
        $card_data = $this->cards->getCard($card_id);

        return $card_data['type'];
    }

    private function getCurrentMultiActiveSkillType()
    {
        $skill_type = $this->merchant_card[self::getGameStateValue('skill_to_resolve_card_type')]['skill_type'];
        $special_skill_type_id = self::getGameStateValue('special_skill_type');

        if ($special_skill_type_id) {
            $skill_type = $this->specialSkillTypes[$special_skill_type_id]['skill_type'];
        }

        return $skill_type;
    }

    private function hideUnrevealedMoney() {
        // Hide money for players who weren't revealed during gameplay
        $sql = "UPDATE player SET forced_reveal = 0 WHERE reveal_money = 0 AND forced_reveal = 1";
        self::DbQuery($sql);
        
        // Reset the forced_reveal flag for next round
        $sql = "UPDATE player SET forced_reveal = 0 WHERE forced_reveal = 1";
        self::DbQuery($sql);
        
        $this->refreshPlayerAssets();
    }

    private function revealAllMoneyForComparison() {
        $players = self::loadPlayersBasicInfos();

        // Reveal all players' money
        $sql = "UPDATE player SET forced_reveal = 1";
        self::DbQuery($sql);
        $this->refreshPlayerAssets();

        // Reveal all money temporarily
        self::notifyAllPlayers('revealAllMoney', '', [
            'all_players' => array_keys($players)
        ]);


    }

    function revealPlayerMoney($player_id) {
        // Update reveal_money in database
        $sql = "UPDATE player SET reveal_money = 1 WHERE player_id = $player_id";
        self::DbQuery($sql);
        
        // Notify all players
        self::notifyAllPlayers('revealMoney', '', [
            'player_id' => $player_id
        ]);
        
        // Refresh player data
        $this->refreshPlayerAssets();
    }

    function revealAllMoneyAndCheckLess($player_id) {
        $players = self::loadPlayersBasicInfos();
        $current_player_coins = $this->getPlayersAssets($player_id)['coins_total'];
        
        // Reveal all players' money
        $sql = "UPDATE player SET reveal_money = 1";
        self::DbQuery($sql);
        
        // Get all opponents' coins
        $all_have_more = true;
        foreach ($players as $opponent_id => $opponent) {
            if ($opponent_id != $player_id) {
                $opponent_coins = $this->getPlayersAssets($opponent_id)['coins_total'];
                if ($opponent_coins <= $current_player_coins) {
                    $all_have_more = false;
                    break;
                }
            }
        }
        
        // Notify all players about money reveal
        self::notifyAllPlayers('revealAllMoney', '', [
            'player_id' => $player_id,
            'all_players' => array_keys($players)
        ]);
        
        // If current player has less than all opponents, award contract
        if ($all_have_more) {
            $this->manageContracts($player_id, 'add', 1);
            $this->runContractAnimation($player_id, 1);
            self::notifyAllPlayers('msg', clienttranslate('${player_name} has less money than all opponents and gains a contract!'), [
                'player_name' => $players[$player_id]['player_name']
            ]);
        } else {
            self::notifyAllPlayers('msg', clienttranslate('${player_name} does not have less money than all opponents'), [
                'player_name' => $players[$player_id]['player_name']
            ]);
        }
        
        return $all_have_more;
    }

    private function enableRPSButtons($skill_player_id, $opponent_player_id)
    {
        self::notifyPlayer($skill_player_id, 'enableRPSButton', '', []);
        self::notifyPlayer($opponent_player_id, 'enableRPSButton', '', []);
    }

    private function activateNextSkillInQueue(): void
    {
        $skill = $this->getNextSkillFromQueue();
        if ($skill) {

            $game_log = new GameLog('setSkillCardActive', 'all');
            $game_log->addToMessage(
                clienttranslate('${player_name} played the lowest card (${reputation}) and activates its skill:'),
                true,
                [
                    'player_name' => $this->getPlayerName($skill['player_id']),
                    'reputation'  => $skill['reputation'],
                ]
            );
            $game_log->addToMessage('<br><br>');
            $game_log->addToMessage(
                clienttranslate('Skill: ${card_skill}'),
                true,
                ['card_skill' => $this->cardSkills[$skill['card_type']]]
            );
            $game_log->addAdditionVariables([
                'card_id' => $skill['card_id'],
            ]);
            $game_log->sendLog();

            $this->doPause(1500);

            $skill_with_player_input = $this->resolveInstantSkills($skill['player_id'], $skill['card_type']);
            if ($skill_with_player_input) {
                self::setGameStateValue('active_card_id', $skill['card_id']);
                self::setGameStateValue('skill_to_resolve_card_type', $skill['card_type']);
                self::setGameStateValue('skill_to_resolve_player', $skill['player_id']);
                $this->gamestate->nextState('multiActiveSkill');
            } else {
                $this->setSkillInQueueAsDone();
                $this->removeLitUpPlayerCards($skill['player_id']);
                $this->doPause(1500);
                $this->gamestate->nextState('activateSkills');
            }
        } else {
            $this->gamestate->nextState('endOfTurnCleanup');
        }
    }

    private function lightUpPlayersCards(array $player_ids)
    {
        self::notifyAllPlayers(
            'lightUpPlayerCards',
            '',
            [
                'player_ids' => $player_ids,
            ]
        );
    }

    private function removeLitUpPlayerCards($player_id = null)
    {
        self::notifyAllPlayers(
            'removeLitUpPlayerCards',
            '',
            [
                'player_id' => $player_id,
            ]
        );
    }

    private function lightUpMerchants(array $move_results)
    {
        $player_ids = [];
        if ($move_results['highest']) {
            $player_ids[] = $move_results['highest']['player_id'];
        }
        if ($move_results['second_highest']) {
            $player_ids[] = $move_results['second_highest']['player_id'];
        }
        if (isset($move_results['lowest'][0])) {
            $player_ids[] = $move_results['lowest'][0]['player_id'];
        }
        if (isset($move_results['lowest'][1])) {
            $player_ids[] = $move_results['lowest'][1]['player_id'];
        }
        if (isset($move_results['lowest'][2])) {
            $player_ids[] = $move_results['lowest'][2]['player_id'];
        }

        $this->lightUpPlayersCards($player_ids);
    }
}
