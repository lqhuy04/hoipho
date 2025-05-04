CREATE TABLE IF NOT EXISTS `card` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_type` varchar(16) NOT NULL,
  `card_type_arg` int(11) NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_location_arg` int(11) NOT NULL,
    `revealed` tinyint(4) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `skill_queue` (
  `pos` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_id` int(10) NOT NULL,
  `player_id` int(11) NOT NULL,
  `card_type` int(11) NOT NULL,
  `reputation` int(11) NOT NULL,
   `done` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `player` ADD `coins_total` SMALLINT UNSIGNED NOT NULL DEFAULT '5';
ALTER TABLE `player` ADD `coins_this_turn` SMALLINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `contracts_won` SMALLINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `selected_card_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `selected_card_to_pass_id` SMALLINT UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `player` ADD `selected_rps` varchar(1) NULL;
ALTER TABLE `player` ADD `played_cards_this_round` varchar(20) DEFAULT '';
ALTER TABLE `player` ADD `top_contract_value` INT(10) UNSIGNED DEFAULT 5;
