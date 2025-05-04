{OVERALL_GAME_HEADER}

<style>
    @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@700&display=swap');
    @import url('https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@700&family=Noto+Sans+JP:wght@500&display=swap');
</style>

<div id="game_main_wrapper">
    <div class="game-table table-shadow">
        <div class="center-flex">
            <div id="my_hand_wrapper" class="outer-block">
                <div style="text-align: center; margin-bottom: 5px; color:black">{MY_HAND}</div>
                <div id="my_hand_stock"></div>
            </div>
        </div>

        <div class="center-flex">
            <div id="name_card_wrapper" class="outer-block element-hidden">
                <div style="text-align: center; color:black">{NAME_A_CARD}</div>
                <div id="cards_to_copy"></div>
            </div>
        </div>

        <div class="center-flex">
            <div id="copy_card_wrapper" class="outer-block element-hidden">
                <div style="text-align: center; color: black">{PICK_A_SKILL}</div>
                <div id="cards_to_copy"></div>
            </div>
        </div>

        <div class="center-flex">
            <div id="player_tables" class="bg-light player-tables-shadow">
                <!-- BEGIN player_table -->
                <div id="table_player_{PLAYER_ID}" class="player-table outer-block table-shadow">
                    <div id="player_{PLAYER_ID}_table_click_space" class="player-table-click-space"></div>
                    <div id="player_table_head_{PLAYER_ID}" class="player-table-head center-center-flex"
                         style="color:#{PLAYER_COLOR}">
                        <div style="text-align: center; font-weight: bold">{PLAYER_NAME}</div>

                    </div>
                    <div class="left-flex" style="height: 45px">
                        <div id="amount_player_coins_{PLAYER_ID}" style="width: 82px; position: relative"></div>
                        <div id="amount_player_contracts_{PLAYER_ID}" style="position: relative"></div>
                        <div id="contract_placement_{PLAYER_ID}" class="contract_placement"></div>
                    </div>
                    <!-- <div id="table_stock_player_{PLAYER_ID}"></div> -->
                    <div class="center-flex">
                        <div id="player_{PLAYER_ID}_card_space" class="player-card-space center-flex"></div>
                    </div>
                    <div id="played_cards_wrapper_{PLAYER_ID}" class="played_cards_wrapper">
                    </div>
                </div>
                <!-- END player_table -->
            </div>
        </div>

        <div class="center-flex">
            <div id="discard_wrapper" class="outer-block">
                <div style="text-align: center; margin-bottom: 5px; color:black">{DISCARD}</div>
                <div id="discard_stock" style="left: -35px"></div>
            </div>
        </div>

        <hr>
        <div style="text-align: center; margin-bottom: 5px; color:black">{LIST_OF_CARDS}</div>

        <div class="card-list-wrapper">
        <!-- Row 1 -->
        <div class="card-list-row">
            <div class="card-list-item">
                <div class="card-list-number numbers number_1"></div>
                <div class="card-list-skill">{SKILL1}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_2"></div>
                <div class="card-list-skill">{SKILL2}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_3"></div>
                <div class="card-list-skill">{SKILL3}</div>
            </div>
        </div>
    
        <!-- Row 2 -->
        <div class="card-list-row">
            <div class="card-list-item">
                <div class="card-list-number numbers number_4"></div>
                <div class="card-list-skill">{SKILL4}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_5"></div>
                <div class="card-list-skill">{SKILL5}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_6"></div>
                <div class="card-list-skill">{SKILL6}</div>
            </div>
        </div>
    
        <!-- Row 3 -->
        <div class="card-list-row">
            <div class="card-list-item">
                <div class="card-list-number numbers number_7"></div>
                <div class="card-list-skill">{SKILL7}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_8"></div>
                <div class="card-list-skill">{SKILL8}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_9"></div>
                <div class="card-list-skill">{SKILL9}</div>
            </div>
        </div>
    
        <!-- Row 4 -->
        <div class="card-list-row">
            <div class="card-list-item">
                <div class="card-list-number numbers number_10"></div>
                <div class="card-list-skill">{SKILL10}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_11"></div>
                <div class="card-list-skill">{SKILL11}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_12"></div>
                <div class="card-list-skill">{SKILL12}</div>
            </div>
        </div>
    
        <!-- Row 5 -->
        <div class="card-list-row">
            <div class="card-list-item">
                <div class="card-list-number numbers number_9"></div>
                <div class="card-list-skill">{SKILL13}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_10"></div>
                <div class="card-list-skill">{SKILL14}</div>
            </div>
            <div class="card-list-item">
                <div class="card-list-number numbers number_11"></div>
                <div class="card-list-skill">{SKILL15}</div>
            </div>
        </div>
    </div>

    </div>

</div>


<script type="text/javascript">

    // Javascript HTML templates

    /*
    // Example:
    var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/


    const jstpl_flip_card = ' <div id="flip-card_${player_id}" class="flip-card card-on-table">\n' +
        '  <div id="flip-card-inner_${player_id}" class="flip-card-inner card-shadow">\n' +
        '    <div class="flip-card-front card-shadow" style="background-position: -${img_pos}px 0px;">\n' +
        '    <div id=\"text_${card_id}\" class=\"card-text\"></div>\n' +
        '    <div id=\"text_border_${card_id}\" class=\"card-text\"></div>\n' +
        '    </div>\n' +
        '    <div class="flip-card-back">\n' +
        '    </div>\n' +
        '  </div>\n' +
        '</div> ';

    const jstpl_flip_card_nofront = ' <div id="flip-card_${player_id}" class="flip-card card-shadow">\n' +
        '  <div id="flip-card-inner_${player_id}" class="flip-card-inner card-shadow">\n' +
        '    <div class="flip-card-back">\n' +
        '    </div>\n' +
        '  </div>\n' +
        '</div> ';

    const jstpl_flip_card_noback = ' <div id="flip-card_${player_id}" class="flip-card card-shadow">\n' +
        '  <div class="flip-card-inner card-shadow">\n' +
        '    <div class="flip-card-front card-shadow" style="background-position: -150px 0px;">\n' +
        '    <div id=\"text_${card_id}\" class=\"card-text\"></div>\n' +
        '    <div id=\"text_border_${card_id}\" class=\"card-text\"></div>\n' +
        '    </div>\n' +
        '  </div>\n' +
        '</div> ';

    const jstpl_flip_card_frontonly = '<div class="flip-card-front card-shadow" style="background-position: -${img_pos}px 0px;">\n' +
        '    <div id=\"text_${card_id}\" class=\"card-text\"></div>\n' +
        '    <div id=\"text_border_${card_id}\" class=\"card-text\"></div>\n' +
        '    </div>\n';

    const jstpl_open_card = '<div id="card_${card_id}" class="card card-on-table card-shadow ${additional_classes}" style="background-position: -${img_pos}px 0px;">\n' +
        '    <div id=\"text_${card_id}\" class=\"card-text\"></div>\n' +
        '    <div id=\"text_border_${card_id}\" class=\"card-text\"></div>\n' +
        '    </div>\n';


    const jstpl_overlay_number = '<div class="overlay_number number_0"></div>';

    const jstpl_number = '<div class="numbers number_${value}"></div>';

    const jstpl_coin     = '<div id="coin_${player_id}_${count}" class="coin coin_${player_id} ${class}" style="top:${top}px; left:${left}px"></div>';
    const jstpl_contract = '<div id="contract_${player_id}_${count}" class="contract contract_${player_id} ${class} contract-shadow" style="top:${top}px; left:${left}px"></div>';

    const jstpl_copy_skill               = '<div id="copy_skill_${skill_type}" class="copy-skill-panel center-center-flex"><div id="copy_skilltext_${skill_type}" style="text-align: center">${skill_text}</div></div>';
    const jstpl_copy_skill_panel_on_card = '<div id="copy_skill_panel_on_card" class="copy-skill-panel-on-card center-center-flex"><div id="copy_skilltext_on_card" style="text-align: center">${skill_text}</div></div><div class="generatecssdotcom_arrow"></div>';

    const jstpl_name_card                = '<div id="copy_skill_${skill_type}" class="copy-skill-panel center-center-flex"><div id="copy_skilltext_${skill_type}" style="text-align: center">${skill_text}</div></div>'

    const jstpl_player_board = '<div id="player_board_${player_id}_row1" class="player-board-row">' +
        '<div class="player-board-info-row"><div>${str_coins}: </div><div id="player_board_${player_id}_coins"></div></div>'+
        '<div class="player-board-info-row"><div>${str_contracts}: </div><div id="player_board_${player_id}_contracts"></div></div>'+
        '</div>';

</script>

{OVERALL_GAME_FOOTER}
