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
            this.eventListeners = {
                // { id, type, listener }
                characterTales: [],
                detectiveTales: [],
                // { id, type, listener, option }
                options: [],
            };
            this.optionActions = {
                rotation: {},
                exchange: {},
                detective: {},
                jocker: {},
            };
            this.sideDict = { up: 0, right: 1, down: 2, left: 3 };
            this.availableDetectivePos = this.boardPos.filter((e) => {
                const isRowCorner = Number(e.y === 0 || e.y === 4);
                const isColumnCorner = Number(e.x === 0 || e.x === 4);
                return isRowCorner + isColumnCorner === 1;
            });
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

            this.initOptions(gamedatas.currentOptions, gamedatas.nextOptions);

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

            this.updateGoal(gamedatas.currentRound.playUntilVisibility);

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

            this.reduceAvailableAlibiCards();

            this.setupNotifications();

            console.log( "Ending game setup" );
        },

        clickOnAction(action) {
            this.optionActions = {
                rotation: {},
                exchange: {},
                detective: {},
                jocker: {},
            };
            this.clearCharacterEventListeners();
            this.clearDetectiveEventListeners();
            dojo.query(`.tale-to-choose`).removeClass('tale-to-choose');
        },

        clearCharacterEventListeners() {
            this.eventListeners.characterTales.forEach((e) => {
                $(e.id).removeEventListener(e.type, e.listener);
                dojo.removeClass(e.id, 'tale-to-choose');
            });
            this.eventListeners.characterTales = [];
        },

        clearDetectiveEventListeners() {
            this.eventListeners.detectiveTales.forEach((e) => {
                $(e.id).removeEventListener(e.type, e.listener);
                dojo.removeClass(e.id, 'tale-to-choose');
            });
            this.eventListeners.detectiveTales = [];
        },

        removeOptionEventListener(option) {
            // TODO add card inactive by styles
            const e = this.eventListeners.options.find((e) => e.option = option);
            $(e.id).removeEventListener(e.type, e.listener);
            dojo.addClass(e.id, 'option-was-used');
            this.eventListeners.options = this.eventListeners.options.filter((item) => item.id !== e.id);
        },

        getListenerByOption(option) {
            if (option === 'exchange') {
                return this.exchangeTalesListener.bind(this);
            }

            if (option === 'rotation') {
                return this.rotateTaleListener.bind(this);
            }

            if (option === 'alibi') {
                return this.alibiListener.bind(this);
            }

            if (option === 'jocker') {
                return this.jockerListener.bind(this);
            }

            return this.detectiveListener.bind(this, option);
        },

        detectiveListener(detectiveId, isJocker = false) {
            if (!isJocker) {
                this.clickOnAction(detectiveId);
            }

            const detective = this.getDetectiveById(detectiveId);
            const currentPos = detective.pos;
            const availablePoses = this.getAvailablePoses(currentPos, isJocker ? 1 : 2);
            this.optionActions[isJocker ? 'jocker' : 'detective'].detectiveId = detectiveId;

            availablePoses.forEach((e, index) => {
                const taleId = `tale_${e.id}`;
                dojo.addClass(taleId, 'tale-to-choose');
                const type = 'click';
                const listener = this.onNewPosClick(detectiveId, index + 1, isJocker);
                $(taleId).addEventListener(type, listener);
                this.eventListeners.detectiveTales.push({ id: taleId, type, listener });
            });
            if (isJocker) {
                this.clearDetectiveEventListeners();
            }
        },

        onNewPosClick(detectiveId, pos, isJocker) {
            return (e) => {
                this.optionActions[isJocker ? 'jocker' : 'detective'].newPos = pos;
                if (isJocker) {
                    this.action_jocker();
                } else {
                    this.action_detective();
                }

                this.clearDetectiveEventListeners();
            };
        },

        jockerListener() {
            this.clickOnAction('jocker');

            const playerisJack = Boolean(this.currentData.jackId);
            if (playerisJack) {
                this.addActionButton('skip-jocker-way', _('Skip'), 'skipByJockerIfJack', null, false, 'none');
            }

            this.currentData.detectives.forEach((e) => {
                const taleId = this.getTaleIdByDetectiveId(e.id);
                const type = 'click';
                const listener = (e) => {
                    this.detectiveListener(e.id, true);
                };
                $(taleId).addEventListener(type, listener);
                this.eventListeners.detectiveTales.push({ id: taleId, type, listener });
            });
        },

        skipByJockerIfJack() {
            this.clearDetectiveEventListeners();
            this.removeActionButtons(); // dojo.destroy('skip-jocker-way');
            this.optionActions.jocker.newPos = null;
            this.optionActions.jocker.detectiveId = null;
            this.action_jocker();
        },

        getAvailablePoses(currentPos, steps) {
            return range(steps)
                .map((n) => n + 1)
                .map((n) => (n + currentPos) % this.availableDetectivePos.length)
                .map((i) => this.availableDetectivePos[i]);
        },

        alibiListener() {
            this.clickOnAction('alibi');
            this.action_alibi();
        },

        rotateTaleListener() {
            this.clickOnAction('rotation');
            this.currentData.characters
                .filter((e) => e.lastRoundRotated !== this.currentRound.num)
                .forEach(
                    (e) => this.setTaleListener(e.id, 'rotateTaleListenerTale')
                );
        },

        rotateTaleListenerTale(characterId) {
            return function (e) {
                // 'clockwise'
                // 'counter-clockwise'
                // 'rotate-approve'
                this.optionActions.rotation.taleId = characterId;
                this.clearCharacterEventListeners();
                const character = this.getCharacterById(characterId);
                const taleId = this.getTaleIdByCharacterId(characterId);
                const tale = $(taleId);

                this.optionActions.rotation.wallSide = character.wallSide;
                [
                    {
                        id: 'clockwise',
                        name: 'clockwise',
                        listener: this.rotateTaleListenerClockwise(characterId),
                    },
                    {
                        id: 'counter-clockwise',
                        name: 'counter-clockwise',
                        listener: this.rotateTaleListenerCounterClockwise(characterId),
                    },
                    {
                        id: 'rotate-approve',
                        name: 'rotate-approve',
                        listener: this.rotateTaleListenerApprove(characterId),
                    },
                ].forEach((e) => this.createButton({
                    id: e.id,
                    listener: e.listener,
                    name: e.name,
                    parent: tale,
                }));

                this.updateRotateApproveButtonStatus();
            };
        },

        createButton({
            id,
            listener,
            name,
            parent,
        }) {
            const btn = document.createElement("button");
            btn.innerHTML = name; // TODO change it to the picture arrows
            btn.id = id;
            btn.addEventListener("click", listener);
            parent.appendChild(btn);
        },

        destroyRotationButtons() {
            [
                'clockwise',
                'counter-clockwise',
                'rotate-approve',
            ].forEach((e) => dojo.destroy(e));
        },

        updateRotateApproveButtonStatus() {
            const { wallSide, taleId } = this.optionActions.rotation;
            const character = this.getCharacterById(taleId);
            $('rotate-approve').className = character.wallSide === wallSide
                ? 'rotate-approve-disable'
                : '';
        },

        rotateTaleListenerClockwise(characterId) {
            return (e) => this.updateNewWallSide(1, characterId);
        },

        rotateTaleListenerCounterClockwise(characterId) {
            return (e) => this.updateNewWallSide(-1, characterId);
        },

        updateNewWallSide(direction, characterId) {
            const { wallSide: oldWallSide } = this.optionActions.rotation;
            const wallIndex = this.sideDict[oldWallSide];
            const newWallIndex = (wallIndex + direction) % 4;

            const newWallSide = Object.entries(this.sideDict)
                .find(([_, v]) => v === newWallIndex)
                [0];
            this.updateRotateApproveButtonStatus();
            this.rotateTale({ characterId, oldWallSide, newWallSide });
            this.optionActions.rotation.wallSide = newWallSide;
        },

        rotateTaleListenerApprove(characterId) {
            return (e) => {
                const { wallSide, taleId } = this.optionActions.rotation;
                if (taleId !== characterId) {
                    console.log(`Something is broken. Player trying to update ${taleId}, but callback is called for ${characterId}`);
                    return;
                }
                const character = this.getCharacterById(characterId);
                if (character.wallSide === wallSide) {
                    return;
                }
                character.wallSide = wallSide;
                this.action_rotateTale();
                this.destroyRotationButtons();
            };
        },

        exchangeTalesListener() {
            this.clickOnAction('exchange');
            
            this.currentData.characters.forEach(
                (e) => this.setTaleListener(e.id, 'exchangeTalesListenerTale1'),
            );
        },

        exchangeTalesListenerTale1(characterId) {
            return function (e) {
                this.optionActions.exchange.taleId1 = characterId;
                this.clearCharacterEventListeners();

                this.currentData.characters
                    .filter((e) => e.id !== characterId)
                    .forEach(
                        (e) => this.setTaleListener(e.id, 'exchangeTalesListenerTale2'),
                    );
            };
        },

        exchangeTalesListenerTale2(characterId) {
            return function (e) {
                this.optionActions.exchange.taleId2 = characterId;
                this.clearCharacterEventListeners();
                this.action_exchangeTales();
            };
        },

        setTaleListener(characterId, funcName) {
            const taleId = this.getTaleIdByCharacterId(characterId);
            const tale = $(taleId);
            const type = 'click';
            const listener = this[funcName](characterId).bind(this);
            tale.addEventListener(type, listener);
            this.eventListeners.characterTales.push({
                id: taleId,
                type,
                listener,
            });
            dojo.addClass(taleId, 'tale-to-choose');
        },

        action_exchangeTales() {
            const { taleId1, taleId2 } = this.optionActions.exchange;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/exchange.html", {
                taleId1: taleId1,
                taleId2: taleId2,
            }, this, () => {});
        },

        action_rotateTale() {
            const { taleId, wallSide } = this.optionActions.rotation;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/rotate.html", {
                taleId: taleId,
                wallSide: wallSide,
            }, this, () => {});
        },

        action_alibi() {
            this.ajaxcall( "/mrjackpocket/mrjackpocket/alibi.html", {}, this, () => {});
        },

        action_jocker() {
            const { detectiveId, newPos } = this.optionActions.jocker;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/jocker.html", {
                detectiveId: detectiveId,
                newPos: newPos,
            }, this, () => {});
        },

        action_detective() {
            const { detectiveId, newPos } = this.optionActions.detective;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/detective.html", {
                detectiveId: detectiveId,
                newPos: newPos,
            }, this, () => {});
        },

        clickOnOption(option) {
            this.clearCharacterEventListeners();
            this.clearDetectiveEventListeners();
            this.optionActions = {
                rotation: {},
                exchange: {},
                detective: {},
                jocker: {},
            };
            // TODO add styles to active and inactive options
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
            const character = this.getCharacterById(characterId);
            $(taleId).innerText = this.getCharacterImage({ ...character, wallSide: newWallSide });
            dojo.rotateTo(taleId, degree);
        },

        getDegree({ oldWallSide, newWallSide }) {
            if (!newWallSide) {
                return 90;
            }
            return (this.sideDict[newWallSide] - this.sideDict[oldWallSide]) * 90;
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

        endRound({
            isVisible,
            playUntilVisibility, 
            newOptions,
            newNextOptions,
            characterIdsToClose,
            winPlayerId,
        }) {
            // TODO move current round to the winned person
            const oldRound = this.currentData.currentRound.num;
            const oldRoundId = `round_${oldRound}`;
            const newRoundId = `round_${oldRound + 1}`;
            dojo.destroy(oldRoundId);
            dojo.addClass(newRoundId, 'current-round');

            // TODO present isVisible
            alert(`isVisible = ${isVisible}`);

            // TODO increase winners rounds
            alert(`winPlayerId = ${winPlayerId}`);

            // TODO close characters to close simultaneously
            this.currentData.characters
                .filter(e => characterIdsToClose.includes(e.id))
                .forEach(e => this.closeCharacter(e.id));

            if (playUntilVisibility !== this.currentData.currentRound.playUntilVisibility) {
                this.updateGoal(playUntilVisibility);
            }

            this.initOptions(newOptions, newNextOptions);
        },

        initOptions(currentOptions, nextOptions) {
            // TODO animate beauty
            for (const index in currentOptions) {
                const option = currentOptions[index];
                const nextOption = nextOptions?.[index];
                const availableId = `available_option_${index}`;
                const nextId = `next_option_${index}`;
                if (option.wasUsed) {
                    dojo.addClass(availableId, 'option-was-used');
                }
                const available = $(availableId);
                // TODO add option picture
                available.innerText = option.ability;

                if (!nextOption) {
                    dojo.addClass(nextId, 'next-option-disable');
                } else {
                    // TODO add next option picture
                    $(nextId).innerText = nextOption.ability;
                }

                if (!option.wasUsed) {
                    const type = 'click';
                    const listener = this.getListenerByOption(option.ability);
                    available.addEventListener(type, listener);
                    this.eventListeners.options.push({
                        id: availableId,
                        type,
                        listener,
                        option: option.ability,
                    });
                }
            }
        },

        updateGoal(playUntilVisibility) {
            // TODO animate
            const isJackPlayer = Boolean(this.currentData.jackId);
            const goalElement = $('goal-info');
            // TODO change it to text and beautiful picture (maybe tooltip)
            goalElement.innerText = isJackPlayer && playUntilVisibility
                ? 'isJackPlayer && playUntilVisibility'
                : isJackPlayer && !playUntilVisibility
                ? 'isJackPlayer && !playUntilVisibility'
                : !isJackPlayer && playUntilVisibility
                ? '!isJackPlayer && playUntilVisibility'
                : '!isJackPlayer && !playUntilVisibility';
        },

        alibiJack({ alibiId, points }) {
            // TODO animate nicely
            alert(`You are Jack and you got alibi ${alibiId}, points ${points}`);

            this.reduceAvailableAlibiCards();
        },

        alibiAllExceptJack() {
            // TODO animate nicely
            alert(`Jack got alibi`);

            this.reduceAvailableAlibiCards();
        },

        alibiAll(alibiId) {
            // TODO animate nicely
            alert(`Detective got alibi = ${alibiId}`);

            this.reduceAvailableAlibiCards();
        },

        reduceAvailableAlibiCards() {
            // TODO animate + picture
            const deck = $('alibi-deck');
            deck.innerText = Number(deck.innerText || 9) - 1;
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
            const { characterId, wallSide } = notif.args;
            console.log('characterId', characterId, 'wallSide', wallSide);

            this.removeOptionEventListener('rotation');
            const character = this.getCharacterById(characterId);
            if (wallSide !== character.wallSide) {
                this.rotateTale({
                    characterId,
                    oldWallSide: character.wallSide,
                    newWallSide: wallSide,
                });
            }
            character.wallSide = wallSide;
            character.lastRoundRotated = this.currentRound.num;
        },

        notif_exchangeTales(notif) {
            console.log('notif_exchangeTales');
            const { characterId1, characterId2 } = notif.args;
            console.log('characterId1', characterId1, 'characterId2', characterId2);

            this.removeOptionEventListener('exchange');
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
            console.log('detectiveId', detectiveId, 'newPos', newPos);

            this.removeOptionEventListener('jocker');
            if (!detectiveId || !newPos) {
                // TODO notify player what jack skip step by jocker
            } else {
                this.detective({ detectiveId, newPos });
            }
        },

        notif_detective(notif) {
            console.log('notif_detective');
            const { detectiveId, newPos } = notif.args;
            console.log('detectiveId', detectiveId, 'newPos', newPos);

            this.removeOptionEventListener(detectiveId);
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
            console.log('alibiId', alibiId, 'points', points);

            this.removeOptionEventListener('alibi');
            this.alibiJack({ alibiId, points });
            // TODO say player about alibi + points
            this.currentData.alibiCards.push(alibiId);

            this.currentData.currentRound.availableALibiCards -= 1;
        },

        notif_alibiAllExceptJack(notif) {
            console.log('notif_alibiAllExceptJack');
            const {} = notif.args;

            // TODO if jack ignore it
            const playerisJack = Boolean(this.currentData.jackId);
            this.removeOptionEventListener('alibi');
            this.alibiAllExceptJack();
            // TODO say that jack took alibi card
            this.currentData.currentRound.availableALibiCards -= 1;
        },

        notif_alibiAll(notif) {
            console.log('notif_alibiAll');
            const { alibiId, close } = notif.args;
            console.log('alibiId', alibiId, 'close', close);
            // TODO say player about alibi

            this.removeOptionEventListener('alibi');
            this.alibiAll(alibiId);
            if (close) {
                this.closeCharacter(alibiId);
                const character = this.getCharacterById(alibiId);
                character.isOpened = false;
            }
            // todo reduce it in the interface
            this.currentData.currentRound.availableALibiCards -= 1;
        },

        notif_roundEnd(notif) {
            console.log('notif_roundEnd');
            const {
                nextActivePlayerId,
                newRoundNum,
                newOptions,
                newNextOptions,
                characterIdsToClose,
                isVisible,
                playUntilVisibility,
                winPlayerId,
            } = notif.args;
            console.log(
                'nextActivePlayerId =', nextActivePlayerId, '\n',
                'newRoundNum =', newRoundNum, '\n',
                'newOptions =', newOptions, '\n',
                'newNextOptions =', newNextOptions, '\n',
                'characterIdsToClose =', characterIdsToClose, '\n',
                'isVisible =', isVisible, '\n',
                'playUntilVisibility =', playUntilVisibility, '\n',
                'winPlayerId =', winPlayerId, '\n',
            );

            this.endRound({
                isVisible,
                playUntilVisibility,
                newOptions,
                newNextOptions,
                characterIdsToClose,
                winPlayerId,
            });

            this.currentData.previousRounds.push({
                num: newRoundNum - 1,
                winPlayerId,
            });
            this.currentData.currentRound = {
                ...this.currentData.currentRound,
                num: newRoundNum,
                playUntilVisibility,
                activePlayerId: nextActivePlayerId,
            };
            this.currentData.characters
                .filter(e => characterIdsToClose.includes(e.id))
                .forEach((e) => { e.isOpened = false; });
            this.currentData.currentOptions = newOptions.map(e => ({ ability: e, wasUsed: false }));
            this.currentData.nextOptions = newNextOptions?.map(e => ({ ability: e, wasUsed: false }));
        },
   });   
});
