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
 * states.inc.php
 *
 * FaiFo game states description
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


if (!defined('STATE_END_GAME')) { // ensure this block is only invoked once, since it is included multiple times
    define("STATE_SETUP_NEW_ROUND", 2);
    define("STATE_SELECT_CARD_TO_DISCARD", 3);
    define("STATE_REVEAL_DISCARDED_CARDS", 4);
    define("STATE_SELECT_CARD", 10);
    define("STATE_RESOLVE_CARDS", 11);
    define("STATE_RESOLVE_ROUND", 12);
    define("STATE_END_OF_ROUND_CLEANUP", 13);
    define("STATE_MULTIACTIVE_SKILL", 14);
    define("STATE_MULTIACTIVE_SKILL_CHECK_TRANSITION", 15);
    define("STATE_ACTIVATE_SKILLS", 16);
    define("STATE_PREPARE_GAME_END", 90);
    //    define("BUFFER", 98);
    define("STATE_END_GAME", 99);
}

$machinestates = [

    // The initial state. Please do not modify.
    1 => [
        "name"        => "gameSetup",
        "description" => "",
        "type"        => "manager",
        "action"      => "stGameSetup",
        "transitions" => ["" => STATE_SETUP_NEW_ROUND],
    ],

    STATE_SETUP_NEW_ROUND => [
        "name"                  => "setupNewRound",
        "description"           => '',
        "type"                  => "game",
        "action"                => "stSetupNewRound",
        "updateGameProgression" => true,
        "transitions"           => [
            "discardOneCard" => STATE_SELECT_CARD_TO_DISCARD,
            "selectCard"     => STATE_SELECT_CARD,
        ],
    ],

    STATE_SELECT_CARD_TO_DISCARD => [
        "name"                  => "discardOneCard",
        "description"           => clienttranslate('Other players have to select a card to discard. You may change your selection.'),
        "descriptionmyturn"     => clienttranslate('${you} must select one card to DISCARD'),
        "type"                  => "multipleactiveplayer",
        'action'                => 'st_MultiPlayerInit',
        'args'                  => 'argDiscardOneCard',
        "updateGameProgression" => false,
        "possibleactions"       => ["selectCard"],
        "transitions"           => ["revealDiscardedCards" => STATE_REVEAL_DISCARDED_CARDS],
    ],

    STATE_REVEAL_DISCARDED_CARDS => [
        "name"                  => "revealDiscardedCards",
        "description"           => clienttranslate('Revealing discarded cards'),
        "type"                  => "game",
        "action"                => "stRevealDiscardedCards",
        "updateGameProgression" => true,
        "transitions"           => ["selectCard" => STATE_SELECT_CARD],
    ],

    STATE_SELECT_CARD => [
        "name"                  => "selectCard",
        "description"           => clienttranslate('Other players have to select a card to play. You may change your selection.'),
        "descriptionmyturn"     => clienttranslate('${you} must select a card to play'),
        "type"                  => "multipleactiveplayer",
        'action'                => 'stSelectCard',
        "updateGameProgression" => false,
        "possibleactions"       => ["selectCard"],
        "transitions"           => ["resolveCards" => STATE_RESOLVE_CARDS],
    ],

    STATE_RESOLVE_CARDS => [
        "name"                  => "resolveCards",
        "description"           => clienttranslate('Revealing the selected cards'),
        "type"                  => "game",
        "action"                => "stResolveSelectedCards",
        "updateGameProgression" => true,
        "transitions"           => [
            "endOfTurnCleanup" => STATE_END_OF_ROUND_CLEANUP,
            "activateSkills"   => STATE_ACTIVATE_SKILLS,
            "multiActiveSkill" => STATE_MULTIACTIVE_SKILL,
        ],
    ],

    STATE_ACTIVATE_SKILLS => [
        "name"                  => "activateSkills",
        "description"           => clienttranslate('Activating Skill'),
        "type"                  => "game",
        "action"                => "stActivateSkills",
        "updateGameProgression" => false,
        "transitions"           => [
            "endOfTurnCleanup" => STATE_END_OF_ROUND_CLEANUP,
            "activateSkills"   => STATE_ACTIVATE_SKILLS,
            "multiActiveSkill" => STATE_MULTIACTIVE_SKILL,
        ],
    ],

    STATE_MULTIACTIVE_SKILL => [
        "name"                  => "multiActiveSkill",
        "description"           => clienttranslate('Activating Skill'),
        "descriptionmyturn"     => clienttranslate('Activating Skill'),
        "type"                  => "multipleactiveplayer",
        'action'                => 'stMultiActiveSkill',
        'args'                  => 'argMultiActiveSkill',
        "updateGameProgression" => false,
        "possibleactions"       => [
            "selectCard",
            "stealThreeCoins",
            "stealHalfMoney",
            "giveOneCoin",
            "nameOneCard",
            "stealOneCard",
            "returnOneCard",
            "switchMoney",
            "selectCardToGiveLeft",
            "choseRPSOpponent",
            "selectRPS",
            "copySkill",
        ],
        "transitions"           => [
            "activateSkills"                  => STATE_ACTIVATE_SKILLS,
            "multiActiveSkill"                => STATE_MULTIACTIVE_SKILL,
            "multiActiveSkillCheckTransition" => STATE_MULTIACTIVE_SKILL_CHECK_TRANSITION,
            "endOfTurnCleanup"                => STATE_END_OF_ROUND_CLEANUP,
        ],
    ],

    STATE_MULTIACTIVE_SKILL_CHECK_TRANSITION => [
        "name"                  => "multiActiveSkillCheckTransition",
        "description"           => clienttranslate(''),
        "type"                  => "game",
        "action"                => "stMultiActiveSkillCheckTransition",
        "updateGameProgression" => true,
        "transitions"           => ["multiActiveSkill" => STATE_MULTIACTIVE_SKILL,],
    ],


    STATE_END_OF_ROUND_CLEANUP => [
        "name"                  => "endOfTurnCleanup",
        "description"           => clienttranslate('Cleaning up'),
        "type"                  => "game",
        "action"                => "stEndOfTurnCleanup",
        "updateGameProgression" => true,
        "transitions"           => ["selectCard" => STATE_SELECT_CARD, "resolveRound" => STATE_RESOLVE_ROUND],
    ],


    STATE_RESOLVE_ROUND => [
        "name"                  => "resolveRound",
        "description"           => clienttranslate('Determine the winner of this round'),
        "type"                  => "game",
        "action"                => "stResolveRound",
        "updateGameProgression" => true,
        "transitions"           => [
            "newRound"       => STATE_SETUP_NEW_ROUND,
            "prepareGameEnd" => STATE_PREPARE_GAME_END,
        ],
    ],

    STATE_PREPARE_GAME_END => [
        "name"                  => "resolveGame",
        "description"           => clienttranslate('Determine the winner of the game'),
        "type"                  => "game",
        "action"                => "stPrepareGameEnd",
        "updateGameProgression" => true,
        "transitions"           => ["gameEnd" => 99],
    ],

    //    BUFFER => [
    //        "name"                  => "selectbufferCard",
    //        "description"           => clienttranslate('BUFFER'),
    //        "descriptionmyturn"     => clienttranslate('BUFFER'),
    //        "type"                  => "multipleactiveplayer",
    //        'action'                => 'stBuffer',
    //        "updateGameProgression" => false,
    //        "transitions"           => ["end" => STATE_PREPARE_GAME_END],
    //    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    99                     => [
        "name"        => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type"        => "manager",
        "action"      => "stGameEnd",
        "args"        => "argGameEnd",
    ],

];



