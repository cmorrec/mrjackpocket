/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * MrJackPocket implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * mrjackpocket.js
 *
 * MrJackPocket user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

function range(length) {
    return [...Array(length).keys()];
};



define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter"
],
function (dojo, declare) {
    return declare("bgagame.mrjackpocket", ebg.core.gamegui, {
        constructor: function(){
            console.log('mrjackpocket constructor');
            this.boardPos = range(25).map((n) => ({
                id: String(n + 1),
                pos: n + 1,
                x: n % 5,
                y: Math.floor(n / 5),
            }));
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
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
        
        setup: function( gamedatas )
        {
            this.currentData = gamedatas;
            console.log( "Starting game setup" );

            console.log(gamedatas.currentOptions, gamedatas.nextOptions);
            for (const index in gamedatas.currentOptions) {
                const option = gamedatas.currentOptions[index];
                const nextOption = gamedatas.nextOptions?.[index];
                const availableId = `available_option_${index}`;
                const nextId = `next_option_${index}`;
                if (option.wasUsed) {
                    dojo.addClass(availableId, 'option-was-used');
                }
                // TODO add option picture
                $(availableId).innerText = option.ability;

                if (!nextOption) {
                    dojo.addClass(nextId, 'next-option-disable');
                } else {
                    // TODO add next option picture
                    $(nextId).innerText = nextOption.ability;
                }
            }

            const currentRoundNum = gamedatas.currentRound.num;
            const rounds = range(gamedatas.meta.roundNum).map((n) => n + 1);
            for (const round of rounds) {
                const roundId = `round_${round}`;
                if (round < currentRoundNum) {
                    dojo.destroy(roundId);
                    continue;
                } else if (round === currentRoundNum) {
                    dojo.addClass(roundId, 'current-round');
                }
                // TODO add round picture
                $(roundId).innerText = round;
            }

            const isJackPlayer = Boolean(gamedatas.jackId);
            const { playUntilVisibility } = gamedatas.currentRound;
            const goalElement = $('goal-info');
            // TODO change it to text and beautiful picture (maybe tooltip)
            goalElement.innerText = isJackPlayer && playUntilVisibility
                ? 'isJackPlayer && playUntilVisibility'
                : isJackPlayer && !playUntilVisibility
                ? 'isJackPlayer && !playUntilVisibility'
                : !isJackPlayer && playUntilVisibility
                ? '!isJackPlayer && playUntilVisibility'
                : '!isJackPlayer && !playUntilVisibility';

            console.log(gamedatas.characters)
            for (const character of gamedatas.characters) {
                const bePos = gamedatas.meta.characterPos[character.pos];
                const fePos = this.getFEPosByBEpos(bePos);
                const taleId = `tale_${fePos.id}`;
                const metaCharacter = gamedatas.meta.characters.find(({ id }) =>  id === character.id);
                // TODO add picture for character
                $(taleId).innerText = metaCharacter.name + ', opened = ' + character.isOpened + ', wallSide = ' + character.wallSide;
                // TODO add class for visibility
            }

            for (const detective of gamedatas.detectives) {
                const bePos = gamedatas.meta.detectivePos[detective.pos];
                const fePos = this.getFEPosByBEpos(bePos);
                const taleId = `tale_${fePos.id}`;
                const metaDetective = gamedatas.meta.detectives.find(({ id }) =>  id === detective.id);
                // TODO add picture for detective
                $(taleId).innerText = metaDetective.name;
            }

            this.setupNotifications();

            console.log( "Ending game setup" );
        },
       

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch( stateName )
            {
            
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
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch( stateName )
            {
            
            /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */
           
           
            case 'dummmy':
                break;
            }               
        }, 

        // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
        //                        action status bar (ie: the HTML links in the status bar).
        //        
        onUpdateActionButtons: function( stateName, args )
        {
            console.log( 'onUpdateActionButtons: '+stateName );
                      
            if( this.isCurrentPlayerActive() )
            {            
                switch( stateName )
                {
/*               
                 Example:
 
                 case 'myGameState':
                    
                    // Add 3 action buttons in the action status bar:
                    
                    this.addActionButton( 'button_1_id', _('Button 1 label'), 'onMyMethodToCall1' ); 
                    this.addActionButton( 'button_2_id', _('Button 2 label'), 'onMyMethodToCall2' ); 
                    this.addActionButton( 'button_3_id', _('Button 3 label'), 'onMyMethodToCall3' ); 
                    break;
*/
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Utility methods
        
        


        ///////////////////////////////////////////////////
        //// Player's action
        
        /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */
        
        /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/mrjackpocket/mrjackpocket/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your mrjackpocket.game.php file.
        
        */
        setupNotifications: function()
        {
            console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },  
   
        getFEPosByBEpos(bePos) {
            return this.boardPos.find((pos) => pos.x === bePos.x && pos.y === bePos.y);
        },
        
        // TODO: from this point and below, you can write your game notifications handling methods
        
        /*
        Example:
        
        notif_cardPlayed: function( notif )
        {
            console.log( 'notif_cardPlayed' );
            console.log( notif );
            
            // Note: notif.args contains the arguments specified during you "notifyAllPlayers" / "notifyPlayer" PHP call
            
            // TODO: play the card in the user interface.
        },    
        
        */
   });   
});
