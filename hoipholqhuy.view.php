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
 * faifo.view.php
 *
 * This is your "view" file.
 *
 * The method "build_page" below is called each time the game interface is displayed to a player, ie:
 * _ when the game starts
 * _ when a player refreshes the game page (F5)
 *
 * "build_page" method allows you to dynamically modify the HTML generated for the game interface. In
 * particular, you can set here the values of variables elements defined in faifo_faifo.tpl (elements
 * like {MY_VARIABLE_ELEMENT}), and insert HTML block elements (also defined in your HTML template file)
 *
 * Note: if the HTML of your game interface is always the same, you don't have to place anything here.
 *
 */
  
  require_once( APP_BASE_PATH."view/common/game.view.php" );
  
  class view_hoipholqhuy_hoipholqhuy extends game_view
  {
    function getGameName() {
        return "hoipholqhuy";
    }    
  	function build_page( $viewArgs )
  	{		
  	    // Get players & players number
        $players = $this->game->loadPlayersBasicInfos();
        $players_nbr = count( $players );

        /*********** Place your code below:  ************/
        $player_to_dir = $this->game->getPlayersToDirection();

        $this->tpl['MY_HAND'] = self::_("MY HAND");
        $this->tpl['DISCARD'] = self::_("DISCARD");
        $this->tpl['PICK_A_SKILL'] = self::_("PICK A SKILL");
        $this->tpl['LIST_OF_CARDS'] = self::_("CARD OVERVIEW");

        $this->tpl['SKILL1'] = self::_("Challenge an opponent to Rock-Paper-Scissors. The winner takes half of the loser’s money, rounded down.");
        $this->tpl['SKILL2'] = self::_("Take 4 money from the bank");
        $this->tpl['SKILL3'] = self::_("Each opponent must pay you 1 money. If an opponent has a contract, they must pay you 1 extra money (total 2)");
        $this->tpl['SKILL4'] = self::_("Take 1 money from the bank. Name a merchant card; the player who holds that card must play it next turn.");
        $this->tpl['SKILL5'] = self::_("Name a merchant card. The player holding that card on hand must pay you 3 money.");
        $this->tpl['SKILL6'] = self::_("Play a merchant card from your hand and trigger its skill. Then, take a merchant card played by opponent to your hand.");
        $this->tpl['SKILL7'] = self::_("No player gains money this turn.");
        $this->tpl['SKILL8'] = self::_("Choose an opponent. Take half their money, rounded down.");
        $this->tpl['SKILL9'] = self::_("Choose an opponent. You and that opponent must reveal your money , then swap it.");
        $this->tpl['SKILL10'] = self::_("All players must reveal their money. If you have less money than all your opponents, take 1 contract.");
        $this->tpl['SKILL11'] = self::_("Choose a played merchant card. Trigger that card’s skill as if you had played it yourself.");
        $this->tpl['SKILL12'] = self::_("Take a contract. Each opponenttakes 1 money from the bank.");
        $this->tpl['SKILL13'] = self::_("This merchant has no skill.");
        $this->tpl['SKILL14'] = self::_("Reveal your money , then pay 3 money to the bank.");
        $this->tpl['SKILL15'] = self::_("Reveal your money , then pay 2 money to the opponents on your left and right.");


        // Create the player tables
        $this->page->begin_block("hoipholqhuy_hoipholqhuy", "player_table");
        foreach ($player_to_dir as $player_id => $table_number) {
            $this->page->insert_block("player_table", [
                "PLAYER_NAME"  => $players[$player_id]['player_name'],
                "PLAYER_ID"    => $players[$player_id]['player_id'],
                "PLAYER_COLOR" => $players[$player_id]['player_color'],
            ]);
        }

        /*********** Do not change anything below this line  ************/
  	}
  }
  

