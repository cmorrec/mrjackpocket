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
            <div id="tale_{pos}" class="tale {status}"></div>
        <!-- END tale -->
    </div>

    <div id="alibi-deck"></div>

    <div id="detective-alibi"></div>
</div>

<script type="text/javascript">

// Javascript HTML templates

/*
// Example:
var jstpl_some_game_item='<div class="my_game_item" id="my_game_item_${MY_ITEM_ID}"></div>';

*/

</script>  

{OVERALL_GAME_FOOTER}
