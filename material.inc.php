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
 * material.inc.php
 *
 * FaiFo game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$this->setup_rules = [
    'coins_per_round' => 5,
    'cards_to_deal'   => [
        3 => 5,
        4 => 4,
        5 => 3,
    ],
    'ships_in_deck'   => [
        3 => 8,
        4 => 8,
        5 => 8,
    ],
];

$this->card_size = [
    'width'   => 123.5,
    'height'  => 205,
    'img_url' => 'img/cards_blank(2).png',
];

$this->scoring_rules = [
    'coins' => [
        'only_ship'      => 3,
        'two_ships'      => 1,
        'highest'        => 2,
        'second_highest' => 1,
    ],
];

$this->merchant_ship_type_id = 16;

$this->merchant_card = [
    1  => [
        'type_id'        => 1,
        'name'           => 'Zhang Shu',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 1,
        'rival_card_ids' => [],
        'skill_type'     => 'rock_paper_scissor',
        'sprite_pos'     => 2,
    ],
    2  => [
        'type_id'        => 2,
        'name'           => 'Tran Trau',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 2,
        'rival_card_ids' => [],
        'skill_type'     => 'gain_four_coins',
        'sprite_pos'     => 3,
    ],
    3  => [
        'type_id'        => 3,
        'name'           => 'Baagh Acharya',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 3,
        'rival_card_ids' => [],
        'skill_type'     => 'all_players_pay_you',
        'sprite_pos'     => 4,
    ],
    4  => [
        'type_id'        => 4,
        'name'           => 'Madame Rabbit',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 4,
        'rival_card_ids' => [],
        'skill_type'     => 'gain_one_coin_and_name_card',
        'sprite_pos'     => 5,
    ],
    5  => [
        'type_id'        => 5,
        'name'           => 'Chat de Merchant',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 5,
        'rival_card_ids' => [],
        'skill_type'     => 'return_gained_coins',
        'sprite_pos'     => 6,
    ],
    6  => [
        'type_id'        => 6,
        'name'           => 'Cri Naga',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 6,
        'rival_card_ids' => [],
        'skill_type'     => 'steal_three_coins',
        'sprite_pos'     => 7,
    ],
    7  => [
        'type_id'        => 7,
        'name'           => 'Ryu Ito',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 7,
        'rival_card_ids' => [],
        'skill_type'     => 'two_players_switching_money',
        'sprite_pos'     => 8,
    ],
    8  => [
        'type_id'        => 8,
        'name'           => 'Schlange Adler',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 8,
        'rival_card_ids' => [],
        'skill_type'     => 'steal_half_money',
        'sprite_pos'     => 9,
    ],
    9  => [
        'type_id'        => 9,
        'name'           => 'Caballo Garcia',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 9,
        'rival_card_ids' => [],
        'skill_type'     => 'reveal_and_switch_money',
        'sprite_pos'     => 10,
    ],
    10 => [
        'type_id'        => 10,
        'name'           => 'Yoo Yang',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 10,
        'rival_card_ids' => [11, 12],
        'skill_type'     => 'reveal_all_players_money_and_check_less_money',
        'sprite_pos'     => 11,
    ],
    11 => [
        'type_id'        => 11,
        'name'           => 'Lee Yoemso',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 11,
        'rival_card_ids' => [4],
        'skill_type'     => 'copy_skill',
        'sprite_pos'     => 12,
    ],
    12 => [
        'type_id'        => 12,
        'name'           => 'Monyet Gunawan',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 12,
        'rival_card_ids' => [2],
        'skill_type'     => 'gain_contract_and_all_players_gain_coin',
        'sprite_pos'     => 13,
    ],
    13 => [
        'type_id'        => 13,
        'name'           => 'Ki Sahin',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 13,
        'rival_card_ids' => [14, 15],
        'skill_type'     => '',
        'sprite_pos'     => 14,
    ],
    14 => [
        'type_id'        => 14,
        'name'           => 'Lady of Doggingham',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 14,
        'rival_card_ids' => [5],
        'skill_type'     => 'reveal_money_and_lose_three_coins',
        'sprite_pos'     => 15,
    ],
    15 => [
        'type_id'        => 15,
        'name'           => 'Porco van Hartsinck',
        'is_merchant'    => 1,
        'is_ship'        => 0,
        'reputation'     => 15,
        'rival_card_ids' => [1],
        'skill_type'     => 'reveal_money_and_give_two_coin_to_neighbours',
        'sprite_pos'     => 16,
    ],
    16 => [
        'type_id'        => 16,
        'name'           => 'Merchant Ship',
        'is_merchant'    => 0,
        'is_ship'        => 1,
        'reputation'     => 0,
        'rival_card_ids' => [],
        'skill_type'     => '',
        'sprite_pos'     => 1,
    ],
    17 => [
        'type_id'        => 17,
        'name'           => 'cardback',
        'is_merchant'    => 0,
        'is_ship'        => 0,
        'reputation'     => 0,
        'rival_card_ids' => [],
        'skill_type'     => '',
        'sprite_pos'     => 0,
    ],
];

$this->specialSkillTypes = [
    '11'   => ['skill_type' => 'gain_one_coin_and_name_card'],
    '12'   => ['skill_type' => 'pick_rps'],
    '13'   => ['skill_type' => 'play_rps'],
    '42'   => ['skill_type' => 'return_one_card'],
    '32'   => ['skill_type' => 'moveCardsToLeft'],
    '9999' => ['skill_type' => 'skill_tree_resolved'],
];

$this->langitem = [
    'coin'                       => clienttranslate('coin'),
    'coins'                      => clienttranslate('coins'),
    'discarding_cards'           => clienttranslate('Discarding selected cards'),
    'revealing_cards'            => clienttranslate('Revealing selected cards'),
    'highest_card_played'        => clienttranslate('${player_name} played the highest card (${reputation}) and gains ${coin_amount} coins'),
    'second_highest_card_played' => clienttranslate('${player_name} played the second highest card (${reputation}) and gains 1 coin'),
    'lowest_card_played'         => clienttranslate('${player_name} played the lowest card (${reputation}) and activates its skill:'),
    'one_ship_played'            => clienttranslate('${player_name} played the only Merchant Ship and gains 3 coins'),
    'two_ships_played'           => clienttranslate('${player_name} and ${player_name2} both played a trading ship and win 1 coin'),
    'more_than_two_ships_played' => clienttranslate('More than 2 Merchant Ships were played. Nobody gains coins'),
    'no_ships_played'            => clienttranslate('No Merchant Ships were played'),
    'reputation_set_zero'        => clienttranslate('${player_name}s card reputation is turned to 0'),
    'rps_r'                      => clienttranslate('Rock'),
    'rps_p'                      => clienttranslate('Paper'),
    'rps_s'                      => clienttranslate('Scissors'),
    'monopoly_rule'              => clienttranslate('<strong>Monopoly-Rule:</strong> The only merchant card is the highest card and also triggers the skill!'),
    'solo_rule'                  => clienttranslate('<strong>Solo-Rule:</strong> The only two merchant cards receive coins, but no skill is activated!'),
];

$this->skill_actions_langitems = [
    'give_one_coin_to_neighbours' => [
        'active'  => clienttranslate('You have to pick one of your neighbors to give them your coin'),
        'passive' => clienttranslate('${skill_player_name} has to pick a neighbor to give them their coin'),
    ],
    'steal_three_coins'           => [
        'active'  => clienttranslate('Pick a player to steal 3 coins from'),
        'passive' => clienttranslate('${skill_player_name} must pick a player to steal 3 coins from'),
    ],
    'steal_half_money'            => [
        'active'  => clienttranslate('Pick a player to steal half of their money'),
        'passive' => clienttranslate('${skill_player_name} must pick a player to steal half of their money'),
    ],  
    'steal_one_card'              => [
        'active'  => clienttranslate('Pick a player to steal a card from'),
        'passive' => clienttranslate('${skill_player_name} has to pick a player to steal a card from'),
    ],
    'return_one_card'             => [
        'active'  => clienttranslate('Select a card you return to ${selected_player_name}'),
        'passive' => clienttranslate('${skill_player_name} has to pick a card to return to ${selected_player_name}'),
    ],
    'rock_paper_scissor'          => [
        'active'  => clienttranslate('Choose an opponent to play Rock-Paper-Scissors'),
        'passive' => clienttranslate('${skill_player_name} must chose an opponent to play Rock-Paper-Scissors'),
    ],
    'pick_rps'                    => [
        'active'  => clienttranslate('Choose'),
        'passive' => clienttranslate('${skill_player_name} and ${selected_player_name} playing Rock-Paper-Scissors'),
    ],
    'all_players_pass_one_card'   => [
        'active'  => clienttranslate('Pick a card that you\'ll pass to ${left_player_name}'),
        'passive' => clienttranslate('Waiting for other players to pick a card to pass'),
    ],
    'two_players_switching_money' => [
        'active'  => clienttranslate('Choose two players who have to switch their money'),
        'passive' => clienttranslate('${skill_player_name} must choose two players who have to switch their money'),
    ],
    'copy_skill'                  => [
        'active'  => clienttranslate('Pick a skill to copy and activate'),
        'passive' => clienttranslate('${skill_player_name} must choose a skill to copy and activate'),
    ],
    'gain_one_coin_and_name_card' => [
        'active'  => clienttranslate('Pick a card that you want to name'),
        'passive' => clienttranslate('${skill_player_name} must pick a card to name'),
    ],
];

$this->cardSkills = [
    '1'  => clienttranslate('Challenge an opponent to Rock-Paper-Scissors. The winner takes half of the loser’s money, rounded down.'),
    '2'  => clienttranslate('Take 4 money from the bank'),
    '3'  => clienttranslate('Each opponent must pay you 1 money. If an opponent has a contract, they must pay you 1 extra money (total 2)'),
    '4'  => clienttranslate('Take 1 money from the bank. Name a merchant card; the player who holds that card must play it next turn.'),
    '5'  => clienttranslate('Name a merchant card. The player holding that card on hand must pay you 3 money.'),
    '6'  => clienttranslate('Play a merchant card from your hand and trigger its skill. Then, take a merchant card played by opponent to your hand.'),
    '7'  => clienttranslate('No player gains money this turn.'),
    '8'  => clienttranslate('Choose an opponent. Take half their money, rounded down.'),
    '9'  => clienttranslate('Choose an opponent. You and that opponent must reveal your money , then swap it.'),
    '10' => clienttranslate('All players must reveal their money. If you have less money than all your opponents, take 1 contract.'),
    '11' => clienttranslate('Choose a played merchant card. Trigger that card’s skill as if you had played it yourself.'),
    '12' => clienttranslate('Take a contract. Each opponenttakes 1 money from the bank.'),
    '13' => clienttranslate('This merchant has no skill.'),
    '14' => clienttranslate('Reveal your money , then pay 3 money to the bank.'),
    '15' => clienttranslate('Reveal your money , then pay 2 money to the opponents on your left and right.'),
    '16' => clienttranslate('1 card: you gain 3 coins<br>2 cards: each player gains 1 coin<br>3+ cards: no one gain coins'),
];