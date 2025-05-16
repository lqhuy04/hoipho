<?php

/*
    From this file, you can edit the various meta-information of your game.

    Once you modified the file, don't forget to click on "Reload game informations" from the Control Panel in order in can be taken into account.

    See documentation about this file here:
    http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php

*/

$gameinfos = [

    // Name of the game in English (will serve as the basis for translation)
    'game_name'                            => "Hội Phố (Second Edition)",

    // Game designer (or game designers, separated by commas)
    'designer'                             => 'Toàn Nguyễn, Mẫn Trần',

    // Game artist (or game artists, separated by commas)
    'artist'                               => 'Toàn Nguyễn, Mẫn Trần',

    // Year of FIRST publication of this game. Can be negative.
    'year'                                 => 2021,

    // Game publisher (use empty string if there is no publisher)
    'publisher'                            => 'Ngũ Hành Games',

    // Url of game publisher website
    'publisher_website'                    => 'https://nguhanhgames.com/',

    // Board Game Geek ID of the publisher
    'publisher_bgg_id'                     => 43460,

    // Board game geek ID of the game
    'bgg_id'                               => 350468,


    // Players configuration that can be played (ex: 2 to 4 players)
    'players'                              => [3, 4, 5],

    // Suggest players to play with this number of players. Must be null if there is no such advice, or if there is only one possible player configuration.
    'suggest_player_number'                => 4,

    // Discourage players to play with these numbers of players. Must be null if there is no such advice.
    'not_recommend_player_number'          => [3],
    // 'not_recommend_player_number' => array( 2, 3 ),      // <= example: this is not recommended to play this game with 2 or 3 players


    // Estimated game duration, in minutes (used only for the launch, afterward the real duration is computed)
    'estimated_duration'                   => 30,

    // Time in second add to a player when "giveExtraTime" is called (speed profile = fast)
    'fast_additional_time'                 => 30,

    // Time in second add to a player when "giveExtraTime" is called (speed profile = medium)
    'medium_additional_time'               => 40,

    // Time in second add to a player when "giveExtraTime" is called (speed profile = slow)
    'slow_additional_time'                 => 50,

    // If you are using a tie breaker in your game (using "player_score_aux"), you must describe here
    // the formula used to compute "player_score_aux". This description will be used as a tooltip to explain
    // the tie breaker to the players.
    // Note: if you are NOT using any tie breaker, leave the empty string.
    //
    // Example: 'tie_breaker_description' => totranslate( "Number of remaining cards in hand" ),
    'tie_breaker_description'              => totranslate("Higher remaining card in hand"),

    // If in the game, all losers are equal (no score to rank them or explicit in the rules that losers are not ranked between them), set this to true
    // The game end result will display "Winner" for the 1st player and "Loser" for all other players
    'losers_not_ranked'                    => false,

    // Allow to rank solo games for games where it's the only available mode (ex: Thermopyles). Should be left to false for games where solo mode exists in addition to multiple players mode.
    'solo_mode_ranked'                     => false,

    // Game is "beta". A game MUST set is_beta=1 when published on BGA for the first time, and must remains like this until all bugs are fixed.
    'is_beta'                              => 1,

    // Is this game cooperative (all players wins together or loose together)
    'is_coop'                              => 0,


    // Complexity of the game, from 0 (extremely simple) to 5 (extremely complex)
    'complexity'                           => 1,

    // Luck of the game, from 0 (absolutely no luck in this game) to 5 (totally luck driven)
    'luck'                                 => 4,

    // Strategy of the game, from 0 (no strategy can be setup) to 5 (totally based on strategy)
    'strategy'                             => 2,

    // Diplomacy of the game, from 0 (no interaction in this game) to 5 (totally based on interaction and discussion between players)
    'diplomacy'                            => 3,

    // Colors attributed to players
    'player_colors'                        => ["ff0000", "008000", "0000ff", "ffa500", "773300"],

    // Favorite colors support : if set to "true", support attribution of favorite colors based on player's preferences (see reattributeColorsBasedOnPreferences PHP method)
    // NB: this parameter is used only to flag games supporting this feature; you must use (or not use) reattributeColorsBasedOnPreferences PHP method to actually enable or disable the feature.
    'favorite_colors_support'              => true,

    // When doing a rematch, the player order is swapped using a "rotation" so the starting player is not the same
    // If you want to disable this, set this to true
    'disable_player_order_swap_on_rematch' => false,

    // Game interface width range (pixels)
    // Note: game interface = space on the left side, without the column on the right
    'game_interface_width'                 => [

        // Minimum width
        //  default: 740
        //  maximum possible value: 740 (ie: your game interface should fit with a 740px width (correspond to a 1024px screen)
        //  minimum possible value: 320 (the lowest value you specify, the better the display is on mobile)
        'min' => 470,

        // Maximum width
        //  default: null (ie: no limit, the game interface is as big as the player's screen allows it).
        //  maximum possible value: unlimited
        //  minimum possible value: 740
        'max' => null,
    ],

    // Game presentation
    // Short game presentation text that will appear on the game description page, structured as an array of paragraphs.
    // Each paragraph must be wrapped with totranslate() for translation and should not contain html (plain text without formatting).
    // A good length for this text is between 100 and 150 words (about 6 to 9 lines on a standard display)
    'presentation'                         => [
        totranslate("It is hard to make a business at Fai-fo – the biggest city-port in Southeast Asia during 16th -17th century."),
        totranslate("There are old and new competitors entering the city every day. The only way to secure your business success is to win 3 contracts from the King of Dai Viet. But don’t worry, you are not alone, there are others friendly merchants that are willing to help you only if you could use them wisely."),
        totranslate("Fai-fo is a game of risk taking, deduction and surprise for 3-5 players. There are only 12 merchant cards and 4-8 Merchant Ship Cards (depend on number of players) which make the deck total of 20 cards. Each merchant card has a Reputation Rank from 1 to 12 and a different Skill. Every round, each player will be deal 2 coins, 1 Merchant Ship card and 3 random Merchant Cards, at the end of the round the player with the most coins win 1 contract – who ever win 3 contracts win the game."),
        totranslate("The game will be happened simultaneously as there are no turn order. Playing the Highest Reputation Merchant will give you money but only the lowest merchant can trigger its special Skill. The Merchant Ship counting as reputation 0 may give you the chance to earn a great deal but be aware, if there are too many Merchant Ship sailing, there will be no money for anyone."),
        //    ...
    ],

    // Games categories
    //  You can attribute a maximum of FIVE "tags" for your game.
    //  Each tag has a specific ID (ex: 22 for the category "Prototype", 101 for the tag "Science-fiction theme game")
    //  Please see the "Game meta information" entry in the BGA Studio documentation for a full list of available tags:
    //  http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php
    //  IMPORTANT: this list should be ORDERED, with the most important tag first.
    //  IMPORTANT: it is mandatory that the FIRST tag is 1, 2, 3 and 4 (= game category)
    'tags'                                 => [2, 10, 11, 200, 204],


    //////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)

    // simple : A plays, B plays, C plays, A plays, B plays, ...
    // circuit : A plays and choose the next player C, C plays and choose the next player D, ...
    // complex : A+B+C plays and says that the next player is A+B
    'is_sandbox'                           => false,
    'turnControl'                          => 'simple'

    ////////
];
