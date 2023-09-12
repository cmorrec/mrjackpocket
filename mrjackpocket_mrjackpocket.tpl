{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- MrJackPocket implementation : © <Your name here> <Your email address here>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->
<div id="container">

    <div id="available-options">
        <!-- BEGIN available_option -->
            <div id="available_option_{index}" class="available-option"></div>
            <div id="next_option_{index}" class="next-option"></div>
        <!-- END available_option -->
    </div>



    <div id="round-info">
        <!-- BEGIN round -->
            <div id="round_{round_num}" class="round"></div>
        <!-- END round -->
    </div>


    <div id="goal-info"></div>

    <div id="board">
        <!-- BEGIN tale -->
            <div id="tale_outer_{pos}" class="tale-outer">
                <div id="tale_{pos}" class="tale {status}"></div>
            </div>
        <!-- END tale -->
    </div>

    <div id="alibi-deck"></div>

    <div id="detective-alibi"></div>
</div>

<script type="text/javascript">

// Javascript HTML templates


var jstpl_jack_panel=`
    <div id="jack-panel">
        <div id="jack-character"></div>
        <div class="time-container" id="jack-winned-rounds">
            <div class="time-num" id="jack-winned-rounds-num">?</div>
            ×
            <div class="time-label" id="jack-winned-rounds-pic"></div>
        </div>
        <div id="jack-points-plus">+</div>
        <div class="time-container" id="jack-alibi">
            <div class="time-num" id="jack-alibi-num">?</div>
            ×
            <div class="time-label"></div>
        </div>
    </div>
`;
var jstpl_detective_panel=`
    <div id="detective-panel">
        <div class="time-container" id="detective-winned-rounds">
            <div class="time-num" id="detective-winned-rounds-num">?</div>
            ×
            <div class="time-label" id="detective-winned-rounds-pic"></div>
        </div>
    </div>
`;
var jstpl_jack_character_tooltip='<div id="jack-character-tooltip" style="${styles}"></div>';
var jstpl_winned_rounds_tooltip='<div class="winned-rounds-tooltip-container">${rounds}</div>';
var jstpl_winned_round_tooltip='<div class="winned-round-tooltip" style="${styles}"></div>';
var jstpl_jack_alibi_cards_tooltip='<div class="alibi-cards-tooltip-container">${alibis}</div>';
var jstpl_jack_alibi_card_tooltip='<div class="alibi-card-tooltip" style="${styles}"></div>';

</script>

{OVERALL_GAME_FOOTER}
