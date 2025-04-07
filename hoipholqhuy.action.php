<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * FaiFo implementation : © Daniel Süß <xcid@steinlaus.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 *
 * faifo.action.php
 *
 * FaiFo main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/faifo/faifo/myAction.html", ...)
 *
 */


class action_hoipholqhuy extends APP_GameAction
{
    // Constructor: please do not modify
    public function __default()
    {
        if (self::isArg('notifwindow')) {
            $this->view = "common_notifwindow";
            $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
        } else {
            $this->view = "hoipholqhuy_hoipholqhuy";
            self::trace("Complete reinitialization of board game");
        }
    }

    public function selectCard()
    {
        self::setAjaxMode();

        $card_id = self::getArg("card_id", AT_posint, true);
        $this->game->selectCard($card_id);

        self::ajaxResponse();
    }

    public function selectCardToGiveLeft()
    {
        self::setAjaxMode();

        $card_id = self::getArg("card_id", AT_posint, true);
        $this->game->selectCardToGiveLeft($card_id);

        self::ajaxResponse();
    }

    public function stealThreeCoins()
    {
        self::setAjaxMode();

        $target_player_id = self::getArg("target_player", AT_posint, true);
        $this->game->stealThreeCoins($target_player_id);

        self::ajaxResponse();
    }

    public function stealHalfMoney()
    {
        self::setAjaxMode();

        $target_player_id = self::getArg("target_player", AT_posint, true);
        $this->game->stealHalfMoney($target_player_id);

        self::ajaxResponse();
    }

    public function stealOneCard()
    {
        self::setAjaxMode();

        $target_player_id = self::getArg("target_player", AT_posint, true);
        $this->game->stealOneCard($target_player_id);

        self::ajaxResponse();
    }

    public function returnOneCard()
    {
        self::setAjaxMode();
        
        $card_id = self::getArg("card_id", AT_posint, true);
        $this->game->returnOneCard($card_id);

        self::ajaxResponse();
    }

    public function nameOneCard()
    {
        self::setAjaxMode();

        $card_id = self::getArg("card_id", AT_posint, true);
        $this->game->nameOneCard($card_id);

        self::ajaxResponse();
    }

    public function giveOneCoin()
    {
        self::setAjaxMode();

        $target_player_id = self::getArg("target_player", AT_posint, true);
        $this->game->giveOneCoin($target_player_id);

        self::ajaxResponse();
    }

    public function switchMoney()
    {
        self::setAjaxMode();

        $target_player1_id = self::getArg("target_player1", AT_posint, true);
        $target_player2_id = self::getArg("target_player2", AT_posint, true);
        $this->game->switchMoney($target_player1_id, $target_player2_id);

        self::ajaxResponse();
    }

    public function choseRPSOpponent()
    {
        self::setAjaxMode();

        $target_player = self::getArg("target_player", AT_posint, true);
        $this->game->choseRPSOpponent($target_player);

        self::ajaxResponse();
    }

    public function selectRPS()
    {
        self::setAjaxMode();

        $rps = self::getArg("rps", AT_alphanum, true);
        $this->game->selectRPS($rps);

        self::ajaxResponse();
    }

    public function copySkill()
    {
        self::setAjaxMode();

        $skill_id = self::getArg("skill_id", AT_alphanum, true);
        $this->game->copySkill($skill_id);

        self::ajaxResponse();
    }


    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

}
  

