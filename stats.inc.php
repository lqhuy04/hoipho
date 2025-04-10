<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FaiFo implementation : © Daniel Süß <xcid@steinlaus.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * FaiFo game statistics description
 *
 */

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = [

    // Statistics global to table
    "table"  => [

        "money_gained" => [
            "id"   => 10,
            "name" => totranslate("Money gained"),
            "type" => "int",
        ],

        "money_lost" => [
            "id"   => 11,
            "name" => totranslate("Money lost"),
            "type" => "int",
        ],

        "cheapest_contract" => [
            "id"   => 12,
            "name" => totranslate("Cheapest contract price"),
            "type" => "int",
        ],

        "most_expensive_contract" => [
            "id"   => 13,
            "name" => totranslate("Most expensive contract price"),
            "type" => "int",
        ],

        /*
                Examples:


                "table_teststat1" => array(   "id"=> 10,
                                        "name" => totranslate("table test stat 1"),
                                        "type" => "int" ),

                "table_teststat2" => array(   "id"=> 11,
                                        "name" => totranslate("table test stat 2"),
                                        "type" => "float" )
        */
    ],

    // Statistics existing for each player
    "player" => [

        "money_gained" => [
            "id"   => 10,
            "name" => totranslate("Money gained"),
            "type" => "int",
        ],

        "money_lost" => [
            "id"   => 11,
            "name" => totranslate("Money lost"),
            "type" => "int",
        ],

        "money_gained_from_players" => [
            "id"   => 12,
            "name" => totranslate("Money gained from other players"),
            "type" => "int",
        ],

        "money_lost_to_players" => [
            "id"   => 13,
            "name" => totranslate("Money lost to other players"),
            "type" => "int",
        ],

        "money_gained_from_ships" => [
            "id"   => 14,
            "name" => totranslate("Money gained from ships"),
            "type" => "int",
        ],

        "only_ship" => [
            "id"   => 15,
            "name" => totranslate("Played the only ship"),
            "type" => "int",
        ],

        "money_spend_on_contracts" => [
            "id"   => 16,
            "name" => totranslate("Money spend on contracts"),
            "type" => "int",
        ],

        /*
                Examples:


                "player_teststat1" => array(   "id"=> 10,
                                        "name" => totranslate("player test stat 1"),
                                        "type" => "int" ),

                "player_teststat2" => array(   "id"=> 11,
                                        "name" => totranslate("player test stat 2"),
                                        "type" => "float" )

        */
    ],

];
