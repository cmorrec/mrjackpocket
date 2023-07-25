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

            for (const character of gamedatas.characters) {
                const taleId = this.getTaleIdByCharacterId(character.id);
                // TODO add picture for character
                const tale = $(taleId);
                tale.innerText = this.getCharacterImage(character);
                // TODO if we add it there we need to support it always. it is hard
                // const isVisible = gamedatas.visibleCharacters.some((e) => e.id === character.id);
                // if (isVisible) {
                //     dojo.addClass(taleId, 'is-visible-tale');
                // }
                this.rotateTale({
                    characterId: character.id,
                    oldWallSide: 'down',
                    newWallSide: character.wallSide,
                });
            }

            for (const detective of gamedatas.detectives) {
                const taleId = this.getTaleIdByDetectiveId(detective.id);
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
   
        getFEPosByBEpos(bePos) {
            return this.boardPos.find((pos) => pos.x === bePos.x && pos.y === bePos.y);
        },

        getCharacterById(characterId) {
            return this.currentData.characters.find((e) => e.id === characterId);
        },

        getMetaCharacterById(characterId) {
            return this.currentData.meta.characters.find(e => e.id === characterId)
        },

        getDetectiveById(characterId) {
            return this.currentData.detectives.find((e) => e.id === characterId);
        },

        getMetaDetectiveById(characterId) {
            return this.currentData.meta.detectives.find(e => e.id === characterId)
        },

        getTaleIdByCharacterId(characterId) {
            const character = this.getCharacterById(characterId);
            const bePos = this.currentData.meta.characterPos[character.pos];
            const fePos = this.getFEPosByBEpos(bePos);
            return `tale_${fePos.id}`;
        },

        getTaleIdByDetectiveId(detectiveId) {
            const detective = this.getDetectiveById(detectiveId);
            const bePos = this.currentData.meta.detectivePos[detective.pos];
            const fePos = this.getFEPosByBEpos(bePos);
            return `tale_${fePos.id}`;
        },

        getCharacterImage(character) {
            const metaCharacter = this.getMetaCharacterById(character.id);
            return  metaCharacter.name + ', opened = ' + character.isOpened + ', wallSide = ' + character.wallSide;
        },

        rotateTale({ characterId, oldWallSide, newWallSide }) {
            const taleId = this.getTaleIdByCharacterId(characterId);
            const degree = this.getDegree({ oldWallSide, newWallSide });
            dojo.rotateTo(taleId, degree);
        },

        getDegree({ oldWallSide, newWallSide }) {
            const sideDict = { up: 0, right: 1, down: 2, left: 3 };
            if (!newWallSide) {
                return 90;
            }
            return (sideDict[newWallSide] - sideDict[oldWallSide]) * 90;
        },
        
        exchangeTales({ characterId1, characterId2 }) {
            const taleId1 = this.getTaleIdByCharacterId(characterId1);
            const taleId2 = this.getTaleIdByCharacterId(characterId2);
            const tale1 = $(taleId1);
            const tale2 = $(taleId2);
            const text1 = tale1.innerText;
            const text2 = tale2.innerText;
            // TODO change tales with animation
            // TODO maybe not change it for player who already changed
            tale1.innerText = text2;
            tale2.innerText = text1;
        },

        moveDetective({ detectiveId, newPos }) {
            const newBEPos = this.currentData.meta.detectivePos[newPos];
            const newFEPos = this.getFEPosByBEpos(newBEPos);
            const newTaleId = `tale_${newFEPos.id}`;
            const oldTaleId = this.getTaleIdByDetectiveId(detectiveId);
            const newTale = $(newTaleId);
            const oldTale = $(oldTaleId);
            // TODO add animation and pictures
            newTale.innerText = oldTale.innerText;
            oldTale.innerText = '';
        },

        closeCharacter(characterId) {
            const character = this.getCharacterById(characterId);
            const taleId = this.getTaleIdByCharacterId(characterId);
            // TODO animate closing
            character.isOpened = false;
            $(taleId).innerText = this.getCharacterImage(character);
        },

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
            dojo.subscribe('rotateTale', this, 'notif_rotateTale');
            dojo.subscribe('exchangeTales', this, 'notif_exchangeTales');
            dojo.subscribe('jocker', this, 'notif_jocker');
            dojo.subscribe('detective', this, 'notif_detective');
            dojo.subscribe('alibiJack', this, 'notif_alibiJack');
            dojo.subscribe('alibiAllExceptJack', this, 'notif_alibiAllExceptJack');
            dojo.subscribe('alibiAll', this, 'notif_alibiAll');
            dojo.subscribe('roundEnd', this, 'notif_roundEnd');

            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            // 
        },

        // TODO: from this point and below, you can write your game notifications handling methods

        notif_rotateTale(notif) {
            console.log('notif_rotateTale');
            const { characterId, wallSide} = notif.args;
            const character = this.getCharacterById(characterId);
            this.rotateTale({
                characterId,
                oldWallSide: character.wallSide,
                newWallSide: wallSide,
            });
            character.wallSide = wallSide;
        },

        notif_exchangeTales(notif) {
            console.log('notif_exchangeTales');
            const { characterId1, characterId2 } = notif.args;
            const character1 = this.getCharacterById(characterId1);
            const character2 = this.getCharacterById(characterId2);
            const pos1 = character1.pos;
            const pos2 = character2.pos;
            this.exchangeTales({ characterId1, characterId2 });
            character1.pos = pos2;
            character2.pos = pos1;
        },

        notif_jocker(notif) {
            console.log('notif_jocker');
            const { detectiveId, newPos } = notif.args;
            if (!detectiveId || !newPos) {
                // TODO notify player what jack skip step by jocker
            } else {
                this.detective({ detectiveId, newPos });
            }
        },

        notif_detective(notif) {
            console.log('notif_detective');
            const { detectiveId, newPos } = notif.args;
            this.detective({ detectiveId, newPos });
        },

        detective({ detectiveId, newPos }) {
            const detective = this.getDetectiveById(detectiveId);
            this.moveDetective({ detectiveId, newPos });
            detective.pos = newPos;
        },

        notif_alibiJack(notif) {
            console.log('notif_alibiJack');
            const { alibiId, points } = notif.args;
            // TODO say player about alibi + points
            this.currentData.alibiCards.push(alibiId);
        },

        notif_alibiAllExceptJack(notif) {
            console.log('notif_alibiAllExceptJack');
            const {} = notif.args;
            // TODO say that jack took alibi card
        },

        notif_alibiAll(notif) {
            console.log('notif_alibiAll');
            const { alibiId, close } = notif.args;
            // TODO say player about alibi
            if (close) {
                this.closeCharacter(alibiId);
                const character = this.getCharacterById(alibiId);
                character.isOpened = false;
            }
        },

        notif_roundEnd(notif) {
            console.log('notif_alibiAll');
            // TODO
            const { } = notif.args;

        },
   });   
});
