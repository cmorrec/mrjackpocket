<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MrJackPocket implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * mrjackpocket.action.php
 *
 * MrJackPocket main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/mrjackpocket/mrjackpocket/myAction.html", ...)
 *
 */


class action_mrjackpocket extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
    } else {
      $this->view = "mrjackpocket_mrjackpocket";
      self::trace("Complete reinitialization of board game");
    }
  }

  public function detective()
  {
    self::setAjaxMode();

    $playerId = self::getArg("playerId", AT_int, true);
    $detectiveId = self::getArg("detectiveId", AT_enum, true, null, ['holmes', 'watson', 'dog']);
    $newPos = self::getArg("newPos", AT_posint, true);

    $this->game->detective($playerId, $detectiveId, $newPos);

    self::ajaxResponse();
  }

  public function jocker()
  {
    self::setAjaxMode();

    $playerId = self::getArg("playerId", AT_int, true);
    $detectiveId = self::getArg("detectiveId", AT_enum, false, null, ['holmes', 'watson', 'dog']);
    $newPos = self::getArg("newPos", AT_posint, false, null);

    $this->game->jocker($playerId, $detectiveId, $newPos);

    self::ajaxResponse();
  }

  public function alibi()
  {
    self::setAjaxMode();

    $playerId = self::getArg("playerId", AT_int, true);

    $this->game->alibi($playerId);

    self::ajaxResponse();
  }

  public function exchange()
  {
    self::setAjaxMode();

    $playerId = self::getArg("playerId", AT_int, true);
    $taleId1 = self::getArg("taleId1", AT_alphanum, true);
    $taleId2 = self::getArg("taleId2", AT_alphanum, true);

    $this->game->exchangeTales($playerId, $taleId1, $taleId2);

    self::ajaxResponse();
  }

  public function rotate()
  {
    self::setAjaxMode();

    $playerId = self::getArg("playerId", AT_int, true);
    $taleId = self::getArg("taleId", AT_alphanum, true);
    $wallSide = self::getArg("wallSide", AT_alphanum, false, null);

    $this->game->rotateTale($playerId, $taleId, $wallSide);

    self::ajaxResponse();
  }
}