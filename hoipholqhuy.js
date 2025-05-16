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
 * faifo.js
 *
 * FaiFo user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
        "dojo", "dojo/_base/declare",
        "ebg/core/gamegui",
        "ebg/counter",
        "ebg/stock",
        // g_gamethemeurl + "modules/js/vue.js",

    ],
    function (dojo, declare) {
        return declare("bgagame.hoipholqhuy", ebg.core.gamegui, {
            constructor: function () {
                console.log('hoipholqhuy constructor');

                // Here, you can init the global variables of your user interface
                // Example:
                // this.myGlobalValue = 0;

                this.playerTableStock = [];

            },

            /*
                setup:

                This method must set up the game user interface according to current game situation specified
                in parameters.

                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)

                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gamedatas) {
                console.log("Starting game setup");

                this.players = gamedatas.players;

                // Create a new div for buttons to avoid BGA auto clearing it
                dojo.place("<div id='customActions' style='display:inline-block'></div>", $('generalactions'), 'after');

                // Setting up player boards
                for (let player_id in gamedatas.players) {
                    let player = gamedatas.players[player_id];

                    // Setting up players boards if needed
                    let player_board_div = $('player_board_' + player_id);
                    dojo.place(this.format_block('jstpl_player_board', {
                        player_id:     player_id,
                        str_contracts: _('Contracts'),
                    }), player_board_div);
                }

                // Hide elements if spectator
                if (this.isSpectator) {
                    dojo.addClass('my_hand_wrapper', 'element-hidden');
                }

                // PLAYERS ASSETS
                this.players_assets = gamedatas.players_assets;

                // PLAYER NEIGHBORS
                this.neighbors     = gamedatas.neighbors;
                this.all_neighbors = gamedatas.all_neighbors;

                // MATERIAL /////////////////
                this.card_size  = gamedatas.card_size;
                this.card_types = gamedatas.card_types;
        
                this.card_text = {
                    1: {'text': _("1. Challenge an opponent to Rock-Paper-Scissors. The winner takes half of the loser’s money, rounded down.")},
                    2: {'text': _("2. Take 4 money from the bank")},
                    3: {'text': _("3. Each opponent must pay you 1 money. If an opponent has a contract, they must pay you 1 extra money (total 2)")},
                    4: {'text': _("4. Take 1 money from the bank. Name a merchant card; the player who holds that card must play it next turn.")},
                    5: {'text': _("5. Name a merchant card. The player holding that card on hand must pay you 3 money.")},
                    6: {'text': _("6. Play a merchant card from your hand and trigger its skill. Then, take a merchant card played by opponent to your hand.")},
                    7: {'text': _("7. No player gains money this turn.")},
                    8: {'text': _("8. Choose an opponent. Take half their money, rounded down.")},
                    9: {'text': _("9. Choose an opponent. You and that opponent must reveal your money , then swap it.")},
                    10: {'text': _("10. All players must reveal their money. If you have less money than all your opponents, take 1 contract.")},
                    11: {'text': _("11. Choose a played merchant card. Trigger that card’s skill as if you had played it yourself.")},
                    12: {'text': _("12. Take a contract. Each opponent takes 1 money from the bank.")},
                    13: {'text': _("13. This merchant has no skill.")},
                    14: {'text': _("14. Reveal your money , then pay 3 money to the bank.")},
                    15: {'text': _("15. Reveal your money , then pay 2 money to the opponents on your left and right.")},
                    16: {'text': _("1 card: you gain 3 coins<br>2 cards: each player gains 1 coin<br>3+ cards: no one gain coins")},
                };


                // CARDS
                this.card_id_to_type          = gamedatas.card_id_to_type;
                this.player_hand              = gamedatas.player_hand_cards;
                this.discard_pile             = gamedatas.discarded_cards;
                this.selected_cards_info      = gamedatas.selected_cards_info;
                this.selected_card_to_pass_id = gamedatas.selected_card_to_pass_id;
                this.played_cards             = gamedatas.played_cards;

                // SKILLS
                this.skill_to_play          = '';
                this.skill_target_player    = '';
                this.amt_selectable_players = '';
                this.selected_player_tables = [];

                // STOCK COMPONENTS ///////////////////////////////
                // PLAYER HAND STOCK //////////
                this.myHand                  = new ebg.stock();
                this.myHand.jstpl_stock_item = "<div id=\"${id}\" class=\"stockitem handstockitem card-shadow card-rounded is-selectable\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;z-index:${position};background-image:url('${image}');\"></div></div>";
                this.myHand.create(this, $('my_hand_stock'), this.card_size.width, this.card_size.height);
                this.myHand.image_items_per_row = 17;
                // this.myHand.setOverlap(this.calculateOverlapPercentage(this.card_size.width, document.getElementById("my_hand_stock").offsetWidth, this.getObjectsize(this.player_hand_research_cards)));
                this.myHand.centerItems         = true;
                this.myHand.setSelectionAppearance('class');
                dojo.connect(this.myHand, 'onChangeSelection', this, 'manageMyHandStockSelection');
                this.makeHandCardsSelectable();


                // DISCARD STOCK //////////
                this.discardStock                  = new ebg.stock();
                this.discardStock.jstpl_stock_item = "<div id=\"${id}\" class=\"stockitem card-shadow card-rounded\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;z-index:${position};background-image:url('${image}');\"></div></div>";
                this.discardStock.create(this, $('discard_stock'), this.card_size.width, this.card_size.height);
                this.discardStock.image_items_per_row = 17;
                this.discardStock.setOverlap(42);
                this.discardStock.setSelectionMode(0);
                this.discardStock.centerItems = true;
                // this.myHand.setSelectionAppearance('class');
                // dojo.connect(this.myHand, 'onChangeSelection', this, 'manageMyHandStockSelection');

                // Table stocks for each player
                // for (let player_id in this.players) {
                //     this.playerTableStock[player_id]                  = new ebg.stock();
                //     this.playerTableStock[player_id].jstpl_stock_item = "<div id=\"${id}\" class=\"stockitem card-shadow card-rounded\" style=\"top:${top}px;left:${left}px;width:${width}px;height:${height}px;z-index:${position};background-image:url('${image}');\"><div id=\"text_${id}\" class=\"card-text\"></div></div>";
                //     this.playerTableStock[player_id].create(this, $('table_stock_player_' + player_id), this.card_size.width, this.card_size.height);
                //     this.playerTableStock[player_id].image_items_per_row = 14;
                //     this.playerTableStock[player_id].centerItems         = true;
                //     this.playerTableStock[player_id].setSelectionMode(0);
                // }


                for (let i in this.card_types) {
                    let card_type = this.card_types[i];
                    let weight    = card_type['type_id'];
                    this.myHand.addItemType(card_type['type_id'], weight, g_gamethemeurl + this.card_size.img_url, card_type['sprite_pos']);
                    this.discardStock.addItemType(card_type['type_id'], weight, g_gamethemeurl + this.card_size.img_url, card_type['sprite_pos']);
                    for (let player_id in gamedatas.players) {
                        // this.playerTableStock[player_id].addItemType(card_type['type_id'], weight, g_gamethemeurl + this.card_size.img_url, card_type['sprite_pos']);
                    }
                }

                for (let card_id in this.player_hand) {
                    let player_hand = this.player_hand[card_id];

                    let card_type_id = this.card_types[player_hand['type']].type_id;

                    if (this.gamedatas.gamestate.name == 'discardOneCard') {
                        this.myHand.addToStockWithId(card_type_id, card_id);
                        this.addTooltip('my_hand_stock_item_' + card_id, this.cardTooltip(card_type_id), '');
                    } else {
                        if (this.selected_cards_info[this.player_id]['card_selected'] == true) {
                            if (this.selected_cards_info[this.player_id]['card_id'] != card_id) {
                                this.myHand.addToStockWithId(card_type_id, card_id);
                                this.addTooltip('my_hand_stock_item_' + card_id, this.cardTooltip(card_type_id), '');
                            }
                        } else {
                            this.myHand.addToStockWithId(card_type_id, card_id);
                            this.addTooltip('my_hand_stock_item_' + card_id, this.cardTooltip(card_type_id), '');
                        }
                    }
                }

                for (let card_id in this.discard_pile) {
                    let discard_pile = this.discard_pile[card_id];
                    let card_type_id = this.card_types[discard_pile['type']].type_id;
                    this.discardStock.addToStockWithId(this.card_types[discard_pile['type']].type_id, card_id);
                    this.addTooltip('discard_stock_item_' + card_id, this.cardTooltip(card_type_id), '');
                }

                // ACTION RELATED
                this.skill_to_resolve_player = gamedatas.skill_to_resolve_player;
                this.skill_player_id         = gamedatas.skill_to_resolve_player;
                this.return_card_to_player   = gamedatas.return_card_to_player;
                this.active_card_id          = gamedatas.active_card_id;
                this.stolen_card_id          = 0;
                this.skills_to_copy          = gamedatas.skills_to_copy;
                this.all_cards               = gamedatas.all_cards;
                this.copy_skill_id           = gamedatas.copied_skill_type_id;
                this.named_card_id           = gamedatas.named_card_id;

                // Setup game notifications to handle (see "setupNotifications" method below)
                this.setupNotifications();

                console.log(this.selected_cards_info);
                this.renderPlayerTables(this.selected_cards_info);
                this.renderPlayedCards();
                this.markSelectedHandCards();

                this.renderCardTexts("text_my_hand_stock_item_");
                this.renderCardTexts("text_discard_stock_item_");


                this.renderActiveCardBorder();
                this.renderCopySkillPanel(this.copy_skill_id);

                this.refreshPlayersAssets();
                // this.refreshPlayerBoards();

                console.log("Ending game setup");
            },


            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName);

                switch (stateName) {

                    case 'actSelectCard':
                    case 'discardOneCard':
                        // if (this.isCurrentPlayerActive()) {
                        //     this.makeHandCardsSelectable();
                        // } else {
                        //     console.log('X5');
                        //     this.makeHandCardsNotSelectable();
                        // }
                        this.makeHandCardsSelectable();
                        break;

                    case 'multiActiveSkill':
                        this.enableMultiActiveSkill(args.args);
                        break;

                    /* Example:

                    case 'myGameState':

                        // Show some HTML block at this game state
                        dojo.style( 'my_html_block_id', 'display', 'block' );

                        break;
                   */


                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                this.disablePlayerSelect();
                this.selected_player_tables = [];

                switch (stateName) {
                    case 'multiActiveSkill':
                        break;
                }

                this.removeActionButton('btn_rock');
                this.removeActionButton('btn_paper');
                this.removeActionButton('btn_scissors');
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);
                if (this.isCurrentPlayerActive()) {
                    console.log(this.skill_to_play);
                    switch (stateName) {
                        case 'actSelectCard':
                        case 'discardOneCard':
                            this.makeHandCardsSelectable();
                            break;
                        case 'multiActiveSkill':
                            switch (this.skill_to_play) {
                                // case 'return_one_card':
                                // let button_txt = dojo.string.substitute(_('Confirm: give this card to ${name}'), {name: this.players[this.return_card_to_player].name});
                                // this.addActionButton('button_select', button_txt, 'onSelectCardToGiveToPlayer');
                                // break;
                                case 'all_players_pass_one_card':
                                    this.makeHandCardsSelectable();
                                    break;
                                case 'steal_one_card':
                                    this.makeHandCardsNotSelectable();
                                    break;
                                case 'return_one_card':
                                    this.makeHandCardsSelectable();
                                    break;
                                case 'gain_one_coin_and_name_card':
                                    break;
                                case 'name_card_and_take_three_coins':
                                    break;
                                case 'copy_skill':
                                    //this.renderCardsToCopy();
                                    break;
                            }
                            break;
                    }
                } else {
                    switch (stateName) {
                        case 'actSelectCard':
                        case 'discardOneCard':
                            break;
                        default:
                            this.makeHandCardsNotSelectable();
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            markSelectedCard: function (card_id) {
                dojo.addClass('my_hand_stock_item_' + card_id, 'card-selected');
            },

            fillDynamicPlaceholders: function (text, placeholders) {
                if (!this.isSpectator) {
                    text = dojo.string.substitute(text, {
                        skill_player_name:    placeholders.skill_player_name,
                        selected_player_name: placeholders.selected_player_name,
                        left_player_name:     this.players[this.all_neighbors[this.player_id][0]].name
                    });
                }

                return text;
            },

            addPrimaryActionButton: function (id, text, callback) {
                if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'blue');
            },

            addRedPrimaryActionButton: function (id, text, callback) {
                if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'red');
            },

            removeActionButton: function (id) {
                dojo.destroy(id);
            },

            makeHandCardsSelectable: function () {
                console.log('makeHandCardsSelectable');
                this.myHand.setSelectionMode(1);
                dojo.query('.handstockitem').addClass('is-selectable');
            },

            makeHandCardsNotSelectable: function () {
                console.log('makeHandCardsNotSelectable');
                this.myHand.setSelectionMode(0);
                dojo.query('.stockitem').removeClass('is-selectable');
            },

            markSelectedHandCards: function () {
                if (!this.isSpectator) {
                    switch (this.gamedatas.gamestate.name) {
                        case 'discardOneCard':
                            let selected_card_id = this.selected_cards_info[this.player_id].card_id;
                            if (selected_card_id != 0) {
                                this.makeHandCardsNotSelectable();
                                dojo.addClass('my_hand_stock_item_' + selected_card_id, 'card-selected');
                            }
                            break;
                        case 'multiActiveSkill':
                            if (this.selected_card_to_pass_id > 0) {
                                this.makeHandCardsNotSelectable();
                                dojo.addClass('my_hand_stock_item_' + this.selected_card_to_pass_id, 'card-selected');
                            }
                            break;
                    }
                }
            },

            cardTooltip: function (card_type_id) {
                let card_type = this.card_types[card_type_id];

                let name        = card_type.name;
                let skill       = this.card_text[card_type_id].text;
                let reputation  = card_type.reputation;
                let rival_ids   = card_type.rival_card_ids;
                let rivals_html = '';

                for (let i in rival_ids) {
                    let rival_card_id    = rival_ids[i];
                    let rival_card_type  = this.card_types[rival_card_id];
                    let rival_reputation = rival_card_type.reputation;
                    rivals_html += _('Rival: ') + rival_card_type.name + ' (' + rival_reputation + ')<br>';
                }

                let html = '<div class="tooltip-wrapper">\n';
                if (reputation > 0) {
                    html += '    <div class="tooltip-title">' + name + ' (' + reputation + ')</div>\n';
                } else {
                    html += '    <div class="tooltip-title">' + name + '</div>\n';
                }
                html += '    <hr>\n';
                html += '    <div class="tooltip-rivals">' + rivals_html + '</div>\n';
                html += '    <div class="tooltip-skill">' + skill + '</div>\n';
                html += '</div>';


                return html;
            },

            manageMyHandStockSelection: function (control_name, selected_item_id) {

                let items = this.myHand.getSelectedItems();

                if (items.length > 0) {

                    let selected_item = this.myHand.getItemById(selected_item_id);
                    switch (this.gamedatas.gamestate.name) {
                        case 'discardOneCard':
                            this.addRedPrimaryActionButton('button_select', _('Confirm selection (DISCARD!)'), 'onSelectCard');
                            break;
                        case 'actSelectCard':
                            this.addPrimaryActionButton('button_select', _('Confirm selection'), 'onSelectCard');
                            break;
                    }

                    switch (this.skill_to_play) {
                        case 'all_players_pass_one_card':
                            this.addPrimaryActionButton('action_button_confirm_card_selection', _('Confirm: Give card to ' + this.players[this.neighbors[0]].name), 'onClickButtonGiveCardToLeft');
                            break;
                        case 'return_one_card':
                            let button_txt = dojo.string.substitute(_('Confirm: give this card to ${name}'), {name: this.players[this.return_card_to_player].name});
                            this.addActionButton('button_select', button_txt, 'onSelectCardToGiveToPlayer');
                            break;
                    }
                } else {
                    this.removeActionButton('button_select');
                    this.removeActionButton('action_button_confirm_card_selection');
                }


            },

            enablePlayerSelect: function (player_ids) {
                this.disconnectAll();
                for (let i in player_ids) {
                    let player_id = player_ids[i];
                    dojo.addClass('table_player_' + player_id, 'table-selectable');
                }
                this.connectClass('table-selectable', 'onclick', 'onClickPlayerTable');
            },

            disablePlayerSelect: function () {
                console.log('disablePlayerSelect');
                this.disconnectAll();
                dojo.query('.table-selectable').removeClass('table-selectable');
                dojo.query('.table-selected').removeClass('table-selected');
            },

            renderPlayerCoins(player_id, coins_to_render) {
                let coins_html              = ''
                let player_board_coins_html = ''
                let pos_left                = 0;

                // Get reveal_money status for this player
                let reveal_money = this.players[player_id].reveal_money;
                let forced_reveal = this.players[player_id].forced_reveal;
                
                if(player_id == this.player_id || reveal_money == 1 || forced_reveal == 1){
                    while (coins_to_render >= 5) {
                        coins_html += '<span class="gold-coin coin-shadow" style="left: ' + pos_left + 'px"></span>';
                        player_board_coins_html += '<div class="gold-coin player-board-coins coin-shadow" style="left: ' + pos_left + 'px"></div>';
                        coins_to_render -= 5;
                        pos_left += 15;
                    }
                    for (let i = 0; i < coins_to_render; i++) {
                        coins_html += '<span class="silver-coin coin-shadow" style="top: 4px; left: ' + pos_left + 'px"></span>';
                        player_board_coins_html += '<div class="silver-coin player-board-coins coin-shadow" style="top: 4px; left: ' + pos_left + 'px"></div>';
                        pos_left += 10;
                    }
                }
                else {
                    // If reveal_money is 0, show a placeholder or hide coins
                    coins_html = '<span class="coin-hidden">?</span>';
                    player_board_coins_html = '<span class="coin-hidden">?</span>';
                }

                $('amount_player_coins_' + player_id).innerHTML     = coins_html;
            },

            refreshPlayersAssets: function () {
                let coins_html    = '';
                let contract_html = '';
                let pos_left      = 0;
                for (let player_id in this.players_assets) {

                    this.renderPlayerCoins(player_id, this.players_assets[player_id].coins_total,);


                    contract_html           = ''
                    pos_left                = 0;
                    let contracts_to_render = this.players_assets[player_id].contracts;
                    if (contracts_to_render == 1) {
                        contract_html += '<span class="single-contract contract-shadow" style="left: 45px"></span>';
                    }
                    if (contracts_to_render == 2) {
                        contract_html += '<span class="double-contract contract-shadow" style="left: 45px"></span>';
                    }
                    if (contracts_to_render == 3) {
                        contract_html += '<span class="double-contract contract-shadow" style="left: 0px"></span>';
                        contract_html += '<span class="single-contract contract-shadow" style="left: 45px"></span>';
                    }
                    if (contracts_to_render == 4) {
                        contract_html += '<span class="double-contract contract-shadow" style="left: 0px"></span>';
                        contract_html += '<span class="double-contract contract-shadow" style="left: 45px"></span>';
                    }

                    $('amount_player_contracts_' + player_id).innerHTML     = contract_html;
                    $('player_board_' + player_id + '_contracts').innerHTML = contracts_to_render;

                    // this.addTooltip('amount_player_coins_' + player_id, _('Coins'), '', 0);
                }
            },

            refreshPlayerBoards: function () {
                for (let player_id in this.players) {
                    $('player_board_' + player_id).innerHTML = 'XXX';
                }
            },

            renderCopySkillPanel: function (skill_type_id) {
                console.log('XXX');
                if (skill_type_id > 0) {
                    console.log('XX1');
                    let text   = this.card_text[skill_type_id].text;
                    let target = 'card_' + this.active_card_id;
                    target     = 'player_' + this.skill_player_id + '_card_space';
                    console.log(target);
                    dojo.place(
                        this.format_block('jstpl_copy_skill_panel_on_card', {
                            skill_text: text,
                        }), target);
                }
            },

            renderCardsToCopy: function () {
                for (let card_id in this.skills_to_copy) {
                    let card = this.skills_to_copy[card_id];
                    let text = this.card_text[card_id].text;

                    let target = 'cards_to_copy';
                    dojo.place(
                        this.format_block('jstpl_copy_skill', {
                            skill_type: card_id,
                            skill_text: text,
                        }), target);
                }
                dojo.query('.copy-skill-panel').connect('onclick', this, 'onClickCopySkillPanel');
                this.addTooltipToClass('copy-skill-panel', '', _('Activate this skill'), 0);
                dojo.removeClass('copy_card_wrapper', 'element-hidden');
            },
            
            renderCardsToName: function () {
                console.log('renderCardsToName');
                console.log(this.all_cards);
                console.log(this.card_text);

                // Convert object to array and sort by card_type
                const cardsArray = Object.values(this.all_cards);
                cardsArray.sort((a, b) => a.card_type - b.card_type);

                // Clear existing content
                dojo.empty('cards_to_name');

                // Render sorted cards
                cardsArray.forEach(card => {
                    let text = this.card_text[card.card_type].text;
                    let target = 'cards_to_name';
                    
                    dojo.place(
                        this.format_block('jstpl_name_card', {
                            skill_type: card.card_type,
                            named_card_id: card.card_id,  // Changed from card_id to card.card_id
                            skill_text: text,
                        }), 
                        target
                    );
                });

                // Reattach event handlers
                dojo.query('.copy-skill-panel').connect('onclick', this, 'onClickNameOneCard');
                this.addTooltipToClass('copy-skill-panel', '', _('Name this card'), 0);
                dojo.removeClass('name_card_wrapper', 'element-hidden');
            },

            // renderCardsToName: function() {
            //     // 1. Clear container first
            //     dojo.empty('cards_to_name');
                
            //     // 2. Convert to array and sort by card_type
            //     const sortedCards = Object.keys(this.all_cards)
            //         .map(card_id => ({
            //             card_id: card_id,
            //             card_type: this.all_cards[card_id].card_type,
            //             data: this.all_cards[card_id]
            //         }))
            //         .sort((a, b) => a.card_type - b.card_type); // Sort by card_type

            //     // 3. Render in sorted order
            //     sortedCards.forEach(card => {
            //         // Get the card name from merchant_card array using card_type
            //         const cardName = this.card_types[card.data.card_type].name;
            //         const text = this.card_text[card.data.card_type].text;
                    
            //         dojo.place(
            //             this.format_block('jstpl_name_card', {
            //                 skill_type: card.data.card_type,
            //                 named_card_id: card.card_id,
            //                 skill_text: text,
            //                 card_name: cardName // Add card name to template
            //             }), 
            //             'cards_to_name'
            //         );
            //     });
                
            //     // 4. Set up interactions
            //     dojo.query('.copy-skill-panel').connect('onclick', this, 'onClickNameOneCard');
            //     this.addTooltipToClass('copy-skill-panel', '', _('Name this card'), 0);
            //     dojo.removeClass('name_card_wrapper', 'element-hidden');
            // },


            // renderCardTexts: function (element_prefix) {
            //     let maxheight = 48;
            //     for (let i = 1; i <= 20; i++) {
            //
            //         let element = document.getElementById(element_prefix + i);
            //         if (element) {
            //             let fontsize                    = 11;
            //             let card_type                   = this.card_id_to_type[i].card_type;
            //             let text                        = this.card_text[card_type].text;
            //             $(element_prefix + i).innerHTML = text;
            //             element.style.fontSize = fontsize.toString() + 'px';
            //
            //             if(element_prefix + i == 'text_my_hand_stock_item_4') {
            //                 // alert(document.getElementById('text_my_hand_stock_item_4').clientHeight + ' ' + fontsize);
            //             }
            //
            //             while (element.clientHeight > maxheight) {
            //                 fontsize               = fontsize - 1;
            //                 element.style.fontSize = fontsize.toString() + 'px';
            //
            //                 if(element_prefix + i == 'text_my_hand_stock_item_4') {
            //                     // alert(document.getElementById('text_my_hand_stock_item_4').clientHeight + ' ' + fontsize);
            //                 }
            //             }
            //             element.style.bottom = (53 - element.clientHeight).toString() + 'px';
            //         }
            //     }
            // },

            renderCardTexts: function (element_prefix) {
                for (let i = 1; i <= 20; i++) {
                    let element = document.getElementById(element_prefix + i);
                    if (element) {
                        let card_type                   = this.card_id_to_type[i].card_type;
                        let text                        = this.card_text[card_type].text;
                        $(element_prefix + i).innerHTML = text;
                    }
                }

                // this.autoSizeText();
            },

            autoSizeText: function () {
                let el, elements, _i, _len, _results;
                elements = dojo.query('.card-text');
                console.log(elements);
                if (elements.length < 0) {
                    return;
                }
                _results = [];
                for (_i = 0, _len = elements.length; _i < _len; _i++) {
                    el = elements[_i];
                    _results.push((function (el) {
                        let resizeText, _results1;
                        resizeText = function () {
                            let elNewFontSize;
                            elNewFontSize = (parseInt($(el).css('font-size').slice(0, -2)) - 1) + 'px';
                            return $(el).css('font-size', elNewFontSize);
                        };
                        _results1  = [];
                        while (el.scrollHeight > el.offsetHeight) {
                            _results1.push(resizeText());
                        }
                        return _results1;
                    })(el));
                }
                return _results;
            },


            renderActiveCardBorder: function () {
                dojo.query('.active-skill-border').removeClass('active-skill-border');
                if (this.active_card_id > 0) {
                    dojo.addClass('text_border_' + this.active_card_id, 'active-skill-border');
                } else {
                    dojo.query('.active-skill-border').removeClass('active-skill-border');
                }
            },

            markPlayerTableAsSelected: function (player_id) {
                dojo.query('.table-selected').removeClass('table-selected');
                dojo.addClass('table_player_' + player_id, 'table-selected');
                // dojo.addClass('table_player_' + player_id, 'table-selectable');
            },

            markMultiplePlayerTablesAsSelected: function (player_id) {
                if (this.inArray(player_id, this.selected_player_tables)) {
                    dojo.removeClass('table_player_' + player_id, 'table-selected');
                    this.removeItemOnce(this.selected_player_tables, player_id);
                } else {
                    if (this.selected_player_tables.length < this.amt_selectable_players) {
                        this.selected_player_tables.push(player_id);
                        dojo.addClass('table_player_' + player_id, 'table-selected');
                    }
                }
            },

            onClickCopySkillPanel: function (evt) {
                let target    = evt.currentTarget.id;
                let split     = target.split('_');
                let target_id = split[2];

                this.copy_skill_id = target_id;

                dojo.query('.copy-skill-panel').removeClass('copy-skill-panel-selected');
                dojo.addClass('copy_skill_' + target_id, 'copy-skill-panel-selected');

                this.removeActionButtons();
                this.addActionButton('action_button_confirm_copy_skill', _('Confirm: Activate selected skill'), 'onClickButtonCopySkill');

            },

            onClickNameOneCard: function (evt) {
                let target    = evt.currentTarget.id;
                let split     = target.split('_');
                let target_id = split[2];

                console.log('onClickNameOneCard');
                console.log(target);
                console.log(target_id);

                this.named_card_id = target_id;

                dojo.query('.copy-skill-panel').removeClass('copy-skill-panel-selected');
                dojo.addClass('copy_skill_' + target_id, 'copy-skill-panel-selected');

                this.removeActionButtons();
                this.addActionButton('action_button_confirm_copy_skill', _('Confirm: Name this merchant'), 'onClickButtonNameOneCard');
            },

            onClickPlayerTable: function (evt) {
                let target           = evt.target.id;
                let split            = target.split('_');
                let target_player_id = split[1];

                switch (this.skill_to_play) {
                    case 'steal_three_coins':
                        this.markPlayerTableAsSelected(target_player_id);
                        this.removeActionButtons();
                        this.addActionButton('action_button_confirm_steal_three_coins', _('Confirm: Steal 3 coins from ' + this.players[target_player_id].name), 'onClickButtonStealThreeCoins');
                        this.skill_target_player = target_player_id;
                        break;
                    case 'steal_half_money':
                        this.markPlayerTableAsSelected(target_player_id);
                        this.removeActionButtons();
                        this.addActionButton('action_button_confirm_steal_three_coins', _('Confirm: Steal half money from ' + this.players[target_player_id].name), 'onClickButtonStealHalfMoney');
                        this.skill_target_player = target_player_id;
                        break;
                    case 'rock_paper_scissor':
                        this.markPlayerTableAsSelected(target_player_id);
                        this.skill_target_player = target_player_id;
                        this.removeActionButtons();
                        this.addActionButton('action_button_confirm_steal_three_coins', _('Confirm: Play Rock-Paper-Scissors against ' + this.players[target_player_id].name), 'onClickButtonChoseRPSOpponent');
                        break;
                    case 'give_one_coin_to_neighbours':
                        this.markPlayerTableAsSelected(target_player_id);
                        this.removeActionButtons();
                        this.addActionButton('action_button_confirm_give_one_coin', _('Confirm: Give 1 coin to ' + this.players[target_player_id].name), 'onClickButtonGiveOneCoin');
                        this.skill_target_player = target_player_id;
                        break;
                    case 'steal_one_card':
                        this.markPlayerTableAsSelected(target_player_id);
                        this.removeActionButtons();
                        this.addActionButton('action_button_confirm_steal_one_card', _('Confirm: Steal 1 card from ' + this.players[target_player_id].name), 'onClickButtonStealACard');
                        this.skill_target_player = target_player_id;
                        break;
                    case 'reveal_and_switch_money':
                        this.markPlayerTableAsSelected(target_player_id);
                        this.skill_target_player = target_player_id;
                        this.removeActionButtons();
                        this.addActionButton('action_button_confirm_steal_one_card', _('Confirm: Switch money between you and ' + this.players[target_player_id].name), 'onClickButtonSwitchMoney');
                        break;
                }

            },

            renderPlayedCards: function () {
                console.log('renderPlayedCards');

                for (let player_id in this.played_cards) {
                    let played_cards_of_player = this.played_cards[player_id];
                    let destination_element    = 'played_cards_wrapper_' + player_id;
                    dojo.empty(destination_element);
                    for (let i in played_cards_of_player) {
                        let card_value = played_cards_of_player[i].value;
                        dojo.place(this.format_block('jstpl_number', {
                            value: card_value,
                        }), destination_element);
                    }
                }
            },

            renderPlayerTables: function (selected_cards_info) {
                console.log('renderPlayerTables');

                if (this.gamedatas.gamestate.name != 'discardOneCard') {
                    for (let player_id in selected_cards_info) {
                        console.log(selected_cards_info);
                        let card_info           = selected_cards_info[player_id];
                        let destination_element = 'player_' + player_id + '_card_space';
                        if (card_info['card_selected'] === true) {
                            if (card_info['card_id'] == 0) {
                                this.renderCardBack(player_id, destination_element);
                            } else {
                                let card_id = card_info['card_id'];
                                this.renderCardFront(card_id, player_id, destination_element, '');
                                this.addTooltip('card_' + card_id, this.cardTooltip(this.card_id_to_type[card_id]['card_type']), '');
                            }
                        }
                    }
                }
            },

            renderCardBack: function (player_id, destination_element) {
                dojo.place(this.format_block('jstpl_flip_card_nofront', {player_id: player_id}), destination_element);
                dojo.addClass('flip-card_' + player_id, 'flip-card-spawn-animation');
            },

            renderCardFront: function (card_id, player_id, destination_element, additional_classes) {
                let card_type = this.card_id_to_type[card_id]['card_type'];
                let img_pos   = this.card_types[card_type]['sprite_pos'] * this.card_size['width'];
                dojo.place(this.format_block('jstpl_open_card', {
                    card_id:            card_id,
                    img_pos:            img_pos,
                    additional_classes: additional_classes
                }), destination_element);
            },

            discardCards: function (cards) {
                let from;

                for (let card_id in cards) {
                    let card      = cards[card_id];
                    let card_type = card['card_type'];
                    let player_id = card['player_id'];
                    if (player_id == this.player_id) {
                        from = 'my_hand_stock_item_' + card_id;
                    } else {
                        from = 'player_board_' + player_id;
                    }
                    this.discardStock.addToStockWithId(card_type, card_id, from);
                    this.myHand.removeFromStockById(card_id);
                    //this.renderCardTexts("text_discard_stock_item_");
                    this.addTooltip('discard_stock_item_' + card_id, this.cardTooltip(card_type), '');
                    this.discardStock.resetItemsPosition();
                }
            },

            selectCard: function (player_id, card_id) {
                let destination_element = 'player_' + player_id + '_card_space';

                if (card_id != 0) {
                    let source_element = 'my_hand_stock_item_' + card_id;

                    this.renderCardFront(card_id, player_id, destination_element, '');
                    this.placeOnObject('card_' + card_id, source_element);
                    this.attachToNewParent('card_' + card_id, destination_element);
                    this.slideToObject('card_' + card_id, destination_element, 500, 0).play();
                    this.addTooltip('card_' + card_id, this.cardTooltip(this.card_id_to_type[card_id].card_type), '');
                    this.myHand.removeFromStockById(card_id);

                } else {
                    if (player_id != this.player_id) {
                        this.renderCardBack(player_id, destination_element);
                    }
                }
            },

            changeSelectedCard: function (player_id, card_id, old_card_id, old_card_type) {
                let destination_element = 'player_' + player_id + '_card_space';

                if (card_id != 0) {
                    let source_element = 'my_hand_stock_item_' + card_id;
                    dojo.destroy('card_' + old_card_id);

                    this.renderCardFront(card_id, player_id, destination_element, '');

                    this.placeOnObject('card_' + card_id, source_element);
                    this.attachToNewParent('card_' + card_id, destination_element);
                    this.slideToObject('card_' + card_id, destination_element, 500, 0).play();
                    this.myHand.removeFromStockById(card_id);

                    this.myHand.addToStockWithId(old_card_type, old_card_id);
                    this.addTooltip('my_hand_stock_item_' + old_card_id, this.cardTooltip(old_card_type), '');

                    //this.renderCardTexts('text_my_hand_stock_item_');


                } else {
                    if (player_id != this.player_id) {
                        // this.renderCardBack(player_id, destination_element);
                    }
                }
            },

            revealCards: function (cards) {
                let from;

                for (let card_id in cards) {
                    let card      = cards[card_id];
                    let card_type = card['card_type'];
                    let player_id = card['player_id'];
                    if (player_id != this.player_id) {

                        let card_type = this.card_id_to_type[card_id]['card_type'];
                        let img_pos   = this.card_types[card_type]['sprite_pos'] * this.card_size['width'];

                        let destination_element = 'flip-card_' + player_id;
                        dojo.place(this.format_block('jstpl_flip_card', {
                            card_id:   card_id,
                            player_id: player_id,
                            img_pos:   img_pos,
                        }), destination_element, "replace");
                        //this.renderCardTexts("text_");

                        // this.playerTableStock[player_id].addToStockWithId(card_type, card_id, from);
                        // this.myHand.removeFromStockById(card_id);
                        // this.renderCardTexts("text_table_stock_player_" + player_id + "_item_");
                        // this.discardStock.resetItemsPosition();
                    }
                }
            },

            flipCards: function (cards) {
                for (let card_id in cards) {
                    let card      = cards[card_id];
                    let player_id = card['player_id'];
                    if (player_id != this.player_id) {
                        dojo.addClass('flip-card-inner_' + player_id, 'flipped-card');
                    }
                }
            },

            addCoinsAnimation: function (player_id, counter) {
                let destination_element = 'player_' + player_id + '_card_space';
                let origin_top          = 15;
                let origin_left         = 45;

                let offset = 20;

                dojo.place(this.format_block('jstpl_coin', {
                    player_id: player_id,
                    count:     counter,
                    class:     'coin_1 coin-shadow',
                    top:       origin_top + (offset * (counter - 1)),
                    left:      origin_left + (offset * (counter - 1)),
                }), destination_element);
            },

            subCoinsAnimation: function (player_id, counter) {
                let origin_element      = 'player_table_head_' + player_id;
                let destination_element = 'player_' + player_id + '_card_space';

                let target_origin_top  = 15;
                let target_origin_left = 45;

                let offset = 20;

                let element_id = 'coin_' + player_id + '_' + counter;

                dojo.place(this.format_block('jstpl_coin', {
                    player_id: player_id,
                    count:     counter,
                    // class:     'coin_1_nofade',
                    class: 'coin_1_fadeout coin-shadow',
                    top:   0,
                    left:  0,
                }), origin_element);
                this.attachToNewParent(element_id, 'player_tables');

                this.slideToObjectPos(element_id, destination_element, target_origin_top + (offset * (counter - 1)), target_origin_left + (offset * (counter - 1), 1)).play();

            },

            switchCoinsAnimation: function (player_from_id, player_to_id, counter, player_from_coins_amount, player_to_coins_amount) {
                let origin_element      = 'amount_player_coins_' + player_from_id;
                let destination_element = 'amount_player_coins_' + player_to_id;

                this.renderPlayerCoins(player_from_id, player_from_coins_amount);
                let animation_id = this.slideTemporaryObject(this.format_block('jstpl_coin', {
                    player_id: player_to_id,
                    count:     counter,
                    class:     'coin_1_nofade coin-shadow',
                    // class: 'coin_1_fadeout',
                    top:  0,
                    left: 0,
                }), 'player_tables', origin_element, destination_element, 1000);
                dojo.connect(animation_id, 'onEnd', dojo.hitch(this, 'renderPlayerCoins', player_to_id, player_to_coins_amount));
                animation_id.play();
            },

            addContractAnimation: function (player_id, counter) {
                let destination_element = 'player_' + player_id + '_card_space';
                let origin_top          = 56;
                let origin_left         = 50;

                dojo.place(this.format_block('jstpl_contract', {
                    player_id: player_id,
                    count:     counter,
                    class:     'contract_1',
                    top:       origin_top,
                    left:      origin_left,
                }), destination_element);
            },

            giveCardAnimation: function (player_from_id, player_to_id) {
                let origin_element      = 'player_' + player_from_id + '_card_space';
                let destination_element = 'player_' + player_to_id + '_card_space';

                console.log('From: ' + origin_element);
                console.log('To: ' + destination_element);

                this.slideTemporaryObject('<div class="card card-shadow"></div>', 'player_tables', origin_element, destination_element, 1000).play();

            },

            clearTables: function (cards) {

                dojo.addClass('copy_card_wrapper', 'element-hidden');
                dojo.addClass('name_card_wrapper', 'element-hidden');
                this.skill_to_play = '';

                for (let card_id in cards) {
                    let card      = cards[card_id];
                    let card_type = card['card_type'];
                    let player_id = card['player_id'];
                    let from      = 'player_' + player_id + '_card_space';
                    this.discardStock.addToStockWithId(card_type, card_id, from);
                    // this.playerTableStock[player_id].removeFromStockById(card_id);
                    this.renderCardTexts("text_discard_stock_item_");
                    this.addTooltip('discard_stock_item_' + card_id, this.cardTooltip(card_type), '');
                    this.discardStock.resetItemsPosition();

                    // Get all cards on tables
                    let cards_on_tables = dojo.query('.card-on-table');
                    console.log(cards_on_tables);
                    for (let i in cards_on_tables) {
                        let card_on_table_id = cards_on_tables[i].id;

                        if (card_on_table_id !== undefined) {
                            this.fadeOutAndDestroy(card_on_table_id, 0);
                        }
                    }
                }
            },

            drawNewCards: function (player_id, new_cards) {

                for (let card_id in new_cards) {
                    let card = new_cards[card_id];
                    this.myHand.addToStockWithId(this.card_types[card['type']].type_id, card_id);
                    this.addTooltip('my_hand_stock_item_' + card_id, this.cardTooltip(this.card_types[card['type']].type_id), '');
                }

                this.renderCardTexts("text_my_hand_stock_item_");
            },

            enableMultiActiveSkill: function (args) {
                console.log('...............');
                console.log(args);

                this.skill_to_play          = args.skill_type;
                this.amt_selectable_players = args.amt_selectable_players;
                let status_bar_texts        = args.status_bar_texts;
                let skill_player_id         = args.skill_player_id;
                this.skills_to_copy         = args.skills_to_copy;
                this.all_cards              = args.all_cards;
                this.skill_player_id        = args.skill_player_id;
                let placeholders            = args.placeholders;

                console.log(status_bar_texts);
                console.log(placeholders);


                if (status_bar_texts.active) {
                    this.gamedatas.gamestate.descriptionmyturn = this.fillDynamicPlaceholders(status_bar_texts.active, placeholders);
                }
                if (status_bar_texts.passive) {
                    this.gamedatas.gamestate.description = this.fillDynamicPlaceholders(status_bar_texts.passive, placeholders);
                }
                this.updatePageTitle();

                for (let for_player_id in args.selectable_players) {
                    if (for_player_id == this.player_id) {
                        this.enablePlayerSelect(args.selectable_players[for_player_id]);
                    }
                }

                console.log('XXX: ' + this.skill_to_play);
                if (skill_player_id == this.player_id) {
                    switch (this.skill_to_play) {
                        case 'copy_skill':
                            this.renderCardsToCopy();
                            break;
                        case 'gain_one_coin_and_name_card':
                            this.renderCardsToName();
                            break;
                        case 'name_card_and_take_three_coins':
                            this.renderCardsToName();
                            break;
                    }
                }

                if (this.isCurrentPlayerActive()) {
                    switch (this.skill_to_play) {
                        case 'return_one_card':
                            this.myHand.setSelectionMode(1);
                            break;
                        case 'all_players_pass_one_card':
                            this.myHand.setSelectionMode(1);
                            break;
                        case 'pick_rps':
                            if (this.isCurrentPlayerActive()) {
                                this.addPrimaryActionButton('btn_rock', 'Rock', 'onClickRpsButton');
                                this.addPrimaryActionButton('btn_paper', 'Paper', 'onClickRpsButton');
                                this.addPrimaryActionButton('btn_scissors', 'Scissors', 'onClickRpsButton');
                            }
                            break;
                    }
                }
            },

            inArray: function (needle, haystack) {
                let length = haystack.length;
                for (let i = 0; i < length; i++) {
                    if (haystack[i] == needle) return true;
                }
                return false;
            },

            removeItemOnce: function (arr, value) {
                let index = arr.indexOf(value);
                if (index > -1) {
                    arr.splice(index, 1);
                }
                return arr;
            },

            ///////////////////////////////////////////////////
            //// Player's action

            onSelectCard: function (evt) {
                console.log('onSelectCard');

                dojo.stopEvent(evt);

                let action = 'selectCard';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                let items = this.myHand.getSelectedItems();
                if (items.length !== 1) {
                    return
                }

                let selected_card_id = items[0].id;

                // Check if player has a forced card
                if (this.gamedatas.forced_card_id > 0) {
                    // Check if selected card matches forced card
                    if (selected_card_id != this.gamedatas.forced_card_id) {
                        // Show error message but don't proceed with selection
                        this.showMessage(_('You must play the named card!'), 'error');
                        this.myHand.unselectAll();
                        return;
                    }
                }
                // this.makeHandCardsNotSelectable();
                this.removeActionButton('button_select');
                dojo.query('.card-selected').removeClass('card-selected');
                dojo.addClass('my_hand_stock_item_' + selected_card_id, 'card-selected');

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:    true,
                //     card_id: selected_card_id
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actSelectCard', {
                    cardId: selected_card_id
                });
            },

            onSelectCardToGiveToPlayer: function (evt) {
                console.log('onSelectCardToGiveToPlayer');

                dojo.stopEvent(evt);

                // let action = 'returnOneCard';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                let items = this.myHand.getSelectedItems();
                if (items.length !== 1) {
                    return;
                }

                let selected_card_id = items[0].id;

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:    true,
                //     card_id: selected_card_id
                // }, this, function (result) {
                // }, function (is_error) {
                // });
                this.bgaPerformAction('actReturnOneCard', {
                    cardId: selected_card_id
                });
            },

            onClickRpsButton: function (evt) {
                console.log('onClickRpsButton');
                dojo.stopEvent(evt);

                // let action = 'selectRPS';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                let split = evt.target.id.split('_');
                let rps   = split[1];

                this.removeActionButton('btn_rock');
                this.removeActionButton('btn_paper');
                this.removeActionButton('btn_scissors');

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock: true,
                //     rps:  rps
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actSelectRPS', {
                    rps: rps
                });
            },

            onClickButtonGiveCardToLeft: function (evt) {
                console.log('onClickButtonGiveCardToLeft');

                dojo.stopEvent(evt);

                this.removeActionButton('action_button_confirm_card_selection');

                let action = 'selectCardToGiveLeft';
                if (!this.checkAction(action)) {
                    return;
                }

                let items = this.myHand.getSelectedItems();
                if (items.length !== 1) {
                    return
                }

                let selected_card_id = items[0].id;
                this.makeHandCardsNotSelectable();
                this.markSelectedCard(selected_card_id);

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:    true,
                //     card_id: selected_card_id
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actSelectCardToGiveLeft', {
                    cardId: selected_card_id
                });
            },
            
            onClickButtonStealHalfMoney: function (evt) {
                console.log('onClickButtonStealHalfMoney');

                // let action = 'stealHalfMoney';
                // if (!this.checkAction(action)) {
                //     return;
                // }
                
                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:          true,
                //     target_player: this.skill_target_player
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actStealHalfMoney', {
                    targetPlayer: this.skill_target_player
                });
            },
            onClickButtonStealThreeCoins: function (evt) {
                console.log('onClickButtonStealThreeCoins');

                // let action = 'stealThreeCoins';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:          true,
                //     target_player: this.skill_target_player
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actStealThreeCoins', {
                    targetPlayer: this.skill_target_player
                });
            },

            onClickButtonChoseRPSOpponent: function (evt) {
                console.log('onClickButtonStealThreeCoins');

                // let action = 'choseRPSOpponent';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:          true,
                //     target_player: this.skill_target_player
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actChoseRPSOpponent', {
                    targetPlayer: this.skill_target_player
                });
            },

            onClickButtonStealACard: function (evt) {
                console.log('onClickButtonStealACard');


                // let action = 'stealOneCard';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                this.return_card_to_player = this.skill_target_player;

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:          true,
                //     target_player: this.skill_target_player
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actStealOneCard', {
                    targetPlayer: this.skill_target_player
                });
            },

            onClickButtonGiveOneCoin: function (evt) {
                console.log('onClickButtonGiveOneCoin');

                // let action = 'giveOneCoin';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:          true,
                //     target_player: this.skill_target_player
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actGiveOneCoin', {
                    targetPlayer: this.skill_target_player
                });
            },

            onClickButtonSwitchMoney: function (evt) {
                console.log('onClickButtonSwitchMoney');

                // let action = 'switchMoney';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:           true,
                //     target_player: this.skill_target_player
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actSwitchMoney', {
                    targetPlayer: this.skill_target_player
                });
            },

            onClickButtonNameOneCard: function (evt) {
                console.log('onClickButtonNameOneCard');
                
                let action;
                if (this.skill_to_play == 'name_card_and_take_three_coins') {
                    this.bgaPerformAction('actNameCardToTakeMoney', {
                        cardType: this.named_card_id
                    });
                } else {
                    this.bgaPerformAction('actNameCardToForcePlay', {
                        cardType: this.named_card_id
                    });
                }

                // if (!this.checkAction(action)) {
                //     return;
                // }

                if (this.named_card_id == 0) {
                    return;
                }

                // dojo.addClass('name_card_wrapper', 'element-hidden');
                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:     true,
                //     card_type: this.named_card_id,
                // }, this, function (result) {

                // }, function (is_error) {
                // });

            },

            // onClickButtonNameOneCardToTakeMoney: function (evt) {
            //     console.log('onClickButtonNameOneCardToTakeMoney');
                
            //     let action = 'nameCardToTakeMoney';
            //     if (!this.checkAction(action)) {
            //         return;
            //     }

            //     if (this.named_card_id == 0) {
            //         return;
            //     }

            //     dojo.addClass('name_card_wrapper', 'element-hidden');
            //     this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
            //         lock:     true,
            //         card_type: this.named_card_id,
            //     }, this, function (result) {

            //     }, function (is_error) {
            //     });
            // },

            onClickButtonCopySkill: function (evt) {
                console.log('onClickButtonCopySkill');

                // let action = 'copySkill';
                // if (!this.checkAction(action)) {
                //     return;
                // }

                if (this.copy_skill_id == 0) {
                    return;
                }

                dojo.addClass('copy_card_wrapper', 'element-hidden');
                // this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", {
                //     lock:     true,
                //     skill_id: this.copy_skill_id,
                // }, this, function (result) {

                // }, function (is_error) {
                // });
                this.bgaPerformAction('actCopySkill', {
                    skillId: this.copy_skill_id
                });
            },



            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:

                In this method, you associate each of your game notifications with your local method to handle it.

                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your faifo.game.php file.

            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                // TODO: here, associate your game notifications with local methods

                // Example 1: standard notification handling
                dojo.subscribe('discardCards_all', this, "notif_discardCards_all");
                dojo.subscribe('selectCard', this, "notif_selectCard");
                dojo.subscribe('changeSelectedCard', this, "notif_changeSelectedCard");
                dojo.subscribe('revealCards_all', this, "notif_revealCards_all");
                dojo.subscribe('flipCards_all', this, "notif_flipCards_all");
                dojo.subscribe('removeCoinsAndContractsFromTables', this, "notif_removeCoinsAndContractsFromTables");
                dojo.subscribe('clearTables', this, "notif_clearTables");
                dojo.subscribe('addClass', this, "notif_addClass");
                dojo.subscribe('removeClass', this, "notif_removeClass");
                dojo.subscribe('addCoins', this, "notif_addCoins");
                dojo.subscribe('subCoins', this, "notif_subCoins");
                dojo.subscribe('switchCoins', this, "notif_switchCoins");
                dojo.subscribe('addContract', this, "notif_addContract");
                dojo.subscribe('resetAllCards', this, "notif_resetAllCards");
                dojo.subscribe('drawNewCards', this, "notif_drawNewCards");
                dojo.subscribe('refreshPlayerAssets', this, "notif_refreshPlayerAssets");
                dojo.subscribe('enablePlayerSelect', this, "notif_enablePlayerSelect");
                dojo.subscribe('getCardFromPlayer', this, "notif_getCardFromPlayer");
                dojo.subscribe('giveCardToPlayer', this, "notif_giveCardToPlayer");
                dojo.subscribe('showSpeechBubble', this, "notif_showSpeechBubble");
                dojo.subscribe('enableRPSButton', this, "notif_enableRPSButton");
                dojo.subscribe('giveCardAnimation', this, "notif_giveCardAnimation");
                dojo.subscribe('setSkillCardActive', this, "notif_setSkillCardActive");
                dojo.subscribe('setSkillCardNonActive', this, "notif_setSkillCardNonActive");
                dojo.subscribe('switchCardText', this, "notif_switchCardText");
                dojo.subscribe('namedCardText', this, "notif_namedCardText");
                dojo.subscribe('revealFinalHandCards', this, "notif_revealFinalHandCards");
                dojo.subscribe('removeEverythingFromTables', this, "notif_removeEverythingFromTables");
                dojo.subscribe('refreshPlayedCards', this, "notif_refreshPlayedCards");
                dojo.subscribe('reputationToZero', this, "notif_reputationToZero");
                dojo.subscribe('updatePlayersScore', this, "notif_updatePlayersScore");
                dojo.subscribe('lightUpPlayerCards', this, "notif_lightUpPlayerCards");
                dojo.subscribe('removeLitUpPlayerCards', this, "notif_removeLitUpPlayerCards");
                dojo.subscribe('revealMoney', this, "notif_revealMoney");
                dojo.subscribe('revealAllMoney', this, "notif_revealAllMoney");
                dojo.subscribe('revealAllMoneyForComparison', this, "notif_revealAllMoneyForComparison");


                this.notifqueue.setSynchronous('addCoins', 200);
                this.notifqueue.setSynchronous('subCoins', 200);

                this.notifqueue.setSynchronous('pause5000', 5000);
                this.notifqueue.setSynchronous('pause4000', 4000);
                this.notifqueue.setSynchronous('pause3000', 3000);
                this.notifqueue.setSynchronous('pause2000', 2000);
                this.notifqueue.setSynchronous('pause1000', 1000);
                this.notifqueue.setSynchronous('pause500', 500);
                this.notifqueue.setSynchronous('pause250', 250);
            },


            notif_lightUpPlayerCards: function (notif) {
                console.log(notif.args.player_ids);
                for (let i in notif.args.player_ids) {
                    let player_id = notif.args.player_ids[i];
                    dojo.addClass('player_' + player_id + '_card_space', 'card-lit');
                }
            },


            notif_removeLitUpPlayerCards: function (notif) {
                let player_id = notif.args.player_id;

                if (player_id == null) {
                    dojo.query('.card-lit').removeClass('card-lit');
                } else {
                    dojo.removeClass('player_' + player_id + '_card_space', 'card-lit');
                }
            },

            notif_updatePlayersScore: function (notif) {
                for (let player_id in notif.args.scores) {
                    this.scoreCtrl[player_id].setValue(notif.args.scores[player_id]);
                }
            },

            notif_reputationToZero: function (notif) {
                let args          = notif.args;
                let player_id     = args.player_id;
                let rival_card_id = args.rival_card_id;

                let target = 'player_' + player_id + '_card_space';
                dojo.place(
                    this.format_block('jstpl_overlay_number', {}), target);

            },

            notif_removeEverythingFromTables: function () {
                for (let player_id in this.players) {
                    dojo.empty('player_' + player_id + '_card_space');
                }
            },

            notif_revealFinalHandCards: function (notif) {
                let args    = notif.args;
                let players = args.players;

                for (let i in players) {
                    let card_info           = players[i];
                    let player_id           = card_info.player_id;
                    let card_id             = card_info.id;
                    let destination_element = 'player_' + player_id + '_card_space';


                    if (this.player_id == player_id) {
                        let source_element = 'my_hand_stock_item_' + card_id;
                        this.renderCardFront(card_id, player_id, destination_element, '');
                        this.placeOnObject('card_' + card_id, source_element);
                        this.slideToObject('card_' + card_id, destination_element, 500, 0).play();
                        this.myHand.removeFromStockById(card_id);
                    } else {
                        this.renderCardFront(card_id, player_id, destination_element, 'flip-card-spawn-animation');
                    }
                }

            },

            notif_refreshPlayedCards: function (notif) {
                let args          = notif.args;
                this.played_cards = args.played_cards;

                this.renderPlayedCards();
            },

            notif_setSkillCardActive: function (notif) {
                let args            = notif.args;
                let card_id         = args.card_id;
                this.active_card_id = card_id;

                this.renderActiveCardBorder();
            },

            notif_setSkillCardNonActive: function (notif) {
                this.active_card_id = 0;

                this.renderActiveCardBorder();
            },

            notif_switchCardText: function (notif) {
                let args          = notif.args;
                let skill_type_id = args.skill_type_id;

                this.renderCopySkillPanel(skill_type_id);
            },


            notif_namedCardText: function (notif) {
                let args          = notif.args;
                let card_id       = args.card_id;

                console.log('card_id: ' + card_id);

            },

            notif_showSpeechBubble: function (notif) {
                let args          = notif.args;
                let player_id     = args.player_id;
                let text          = args.text;
                let speech_length = 3000;

                if (args.length) {
                    speech_length = args.length;
                }

                this.showBubble('player_table_head_' + player_id, text, 0, speech_length);
            },

            notif_enableRPSButton:   function (notif) {
                console.log('notif_enableRPSButton');
                if (this.isCurrentPlayerActive()) {
                    this.addPrimaryActionButton('btn_rock', 'Rock', 'onClickRpsButton');
                    this.addPrimaryActionButton('btn_paper', 'Paper', 'onClickRpsButton');
                    this.addPrimaryActionButton('btn_scissors', 'Scissors', 'onClickRpsButton');
                }
            },
            notif_getCardFromPlayer: function (notif) {
                console.log('notif_getCardFromPlayer');
                let args            = notif.args;
                let from_player     = args.from_player_id;
                let card_id         = args.card_id;
                let card_type       = args.card_type;
                let from            = 'player_table_head_' + from_player;
                this.stolen_card_id = args.card_id;

                this.myHand.addToStockWithId(card_type, card_id, from);
                this.addTooltip('my_hand_stock_item_' + card_id, this.cardTooltip(card_type), '');
                this.renderCardTexts("text_my_hand_stock_item_");
            },

            notif_giveCardToPlayer: function (notif) {
                console.log('notif_giveCardToPlayer');
                let args      = notif.args;
                let to_player = args.to_player_id;
                let card_id   = args.card_id;
                let to        = 'player_table_head_' + to_player;

                this.myHand.removeFromStockById(card_id, to);
            },

            notif_enablePlayerSelect: function (notif) {
                console.log('notif_enablePlayerSelect');
                let args              = notif.args;
                let selectable_player = args.selectable_player;
                let status            = args.status;

                if (status == true) {
                    this.enablePlayerSelect(selectable_player);
                }
            },

            notif_refreshPlayerAssets: function (notif) {
                console.log('notif_refreshPlayerAssets');
                let args            = notif.args;
                this.players_assets = args.player_assets;
                this.refreshPlayersAssets();
            },

            notif_drawNewCards: function (notif) {
                let args      = notif.args;
                let player_id = args.player_id;
                let cards     = args.new_cards;

                this.drawNewCards(player_id, cards);
            },

            notif_resetAllCards: function (notif) {
                console.log('notif_resetAllCards');
                let args        = notif.args;
                let element_ids = args.element_ids;
                let class_name  = args.class_name;

                this.myHand.removeAll();
                this.discardStock.removeAll();

                //
                // dojo.query('.coin_' + player_id).forEach(function (node) {
                //     let destination = 'player_table_head_' + player_id;
                //     this.slideToObjectAndDestroy(node.id, destination);
                // }, this);
            },

            notif_addClass: function (notif) {
                console.log('notif_addClass');
                let args        = notif.args;
                let element_ids = args.element_ids;
                let class_name  = args.class_name;

                for (let i in element_ids) {
                    let element_id = element_ids[i];
                    let element    = document.getElementById(element_id);
                    if (element) {
                        dojo.addClass(element_id, class_name);
                    }
                }
            },

            notif_removeClass: function (notif) {
                console.log('notif_removeClass');
                let args        = notif.args;
                let element_ids = args.element_ids;
                let class_name  = args.class_name;

                for (let i in element_ids) {
                    let element_id = element_ids[i];
                    let element    = document.getElementById(element_id);
                    if (element) {
                        dojo.removeClass(element_id, class_name);
                    }
                }
            },


            notif_addCoins: function (notif) {
                console.log('notif_addCoins');
                let args      = notif.args;
                let player_id = args.player_id;
                let amount    = args.amount;
                let counter   = args.counter;

                this.addCoinsAnimation(player_id, counter);
            },

            notif_subCoins: function (notif) {
                console.log('notif_subCoins');
                let args      = notif.args;
                let player_id = args.player_id;
                let amount    = args.amount;
                let counter   = args.counter;

                this.players_assets = args.player_assets;
                this.refreshPlayersAssets();
                this.subCoinsAnimation(player_id, counter);
            },

            notif_switchCoins: function (notif) {
                console.log('notif_subCoins');
                let args           = notif.args;
                let player_from_id = args.player_from_id;
                let player_to_id   = args.player_to_id;
                let counter        = args.counter;

                let player_from_coins_amount = args.player_from_coins_amount;
                let player_to_coins_amount   = args.player_to_coins_amount;

                this.switchCoinsAnimation(player_from_id, player_to_id, counter, player_from_coins_amount, player_to_coins_amount);
            },

            notif_addContract: function (notif) {
                console.log('notif_addContract');
                let args      = notif.args;
                let player_id = args.player_id;
                let amount    = args.amount;
                let counter   = args.counter;

                this.addContractAnimation(player_id, counter);
            },

            notif_giveCardAnimation: function (notif) {
                console.log('notif_subCoins');
                let args           = notif.args;
                let player_from_id = args.from;
                let player_to_id   = args.to;

                this.giveCardAnimation(player_from_id, player_to_id);
            },


            notif_discardCards_all: function (notif) {
                console.log('notif_discardCards_all');
                let args     = notif.args;
                let card_ids = args.selected_cards;

                this.discardCards(card_ids);
            },

            notif_selectCard: function (notif) {
                console.log('notif_selectCard');
                let card_id;
                let args = notif.args;

                let player_id = args.player_id;

                if (args.card_id === undefined) {
                    card_id = 0;
                } else {
                    card_id = args.card_id;
                }

                this.selectCard(player_id, card_id);
            },

            notif_changeSelectedCard: function (notif) {
                console.log('notif_selectCard');
                let card_id;
                let old_card_id;
                let old_card_type;
                let args = notif.args;

                let player_id = args.player_id;

                if (args.card_id === undefined) {
                    card_id = 0;
                } else {
                    card_id       = args.card_id;
                    old_card_id   = args.old_card_id;
                    old_card_type = args.old_card_type;
                }

                this.changeSelectedCard(player_id, card_id, old_card_id, old_card_type);
            },

            notif_revealCards_all: function (notif) {
                console.log('notif_revealCards_all');
                let args     = notif.args;
                let card_ids = args.selected_cards;

                this.revealCards(card_ids);
            },

            notif_revealAllMoneyForComparison: function (notif) {
                // Update reveal_money for all players
                let all_players = notif.args.all_players;
                for (let i in all_players) {
                    let player_id = all_players[i];
                    this.players[player_id].forced_reveal = 1;
                }
                // Refresh the display
                this.refreshPlayersAssets();
            },


            notif_revealMoney: function(notif) {
                let player_id = notif.args.player_id;
                // Update local player data
                this.players[player_id].reveal_money = 1;
                // Refresh the display
                this.refreshPlayersAssets();
            },

            notif_revealAllMoney: function(notif) {
                // Update reveal_money for all players
                let all_players = notif.args.all_players;
                for (let i in all_players) {
                    let player_id = all_players[i];
                    this.players[player_id].reveal_money = 1;
                }
                // Refresh the display
                this.refreshPlayersAssets();
            },

            notif_flipCards_all: function (notif) {
                console.log('notif_revealCards_all');
                let args     = notif.args;
                let card_ids = args.selected_cards;

                this.flipCards(card_ids);
            },

            notif_removeCoinsAndContractsFromTables: function (notif) {
                console.log('notif_removeCoinsAndContractsFromTables');
                let args = notif.args;

                this.players_assets = args.player_assets;

                for (let player_id in this.players) {
                    dojo.query('.coin_' + player_id).forEach(function (node) {
                        let destination = 'amount_player_coins_' + player_id;
                        this.slideToObjectAndDestroy(node.id, destination, 500);
                    }, this);

                    dojo.query('.contract_' + player_id).forEach(function (node) {
                        let destination = 'contract_placement_' + player_id;
                        this.slideToObjectAndDestroy(node.id, destination, 500);
                    }, this);
                }

                // let animation_id = this.slideTemporaryObject(this.format_block('jstpl_coin', {
                //     player_id: player_to_id,
                //     count:     counter,
                //     class:     'coin_1_nofade coin-shadow',
                //     // class: 'coin_1_fadeout',
                //     top:  0,
                //     left: 0,
                // }), 'player_tables', origin_element, destination_element, 1000);
                // dojo.connect(animation_id, 'onEnd', dojo.hitch(this, 'renderPlayerCoins', player_to_id, player_to_coins_amount));
                // animation_id.play();

                // this.refreshPlayersAssets();
            },

            notif_clearTables: function (notif) {
                console.log('notif_clearTables');
                let args     = notif.args;
                let card_ids = args.selected_cards;

                console.log(card_ids);

                this.clearTables(card_ids);
            },

        });
    });

// let vjs = new Vue({
//     el:      '#vjs_test',
//     data: {
//         test: 'ABC',
//     }
// });