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

async function delay(ms) {
    return new Promise((res, rej) => setTimeout(() => res(), ms));
};

const SHADOW_ALL_SCREEN = '0 0 0 max(100vh, 100vw) rgba(0, 0, 0, .3)';

define([
    "dojo","dojo/_base/declare",
    "dojo/_base/fx",
    "ebg/core/gamegui",
    "ebg/counter",
],
function (dojo, declare, baseFx) {
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
            this.sideDict = { down: 0, left: 1, up: 2, right: 3 };
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

            this.initOptions(
                gamedatas.currentOptions,
                gamedatas.nextOptions,
            );

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
                this.addImg({
                    id: roundId,
                    urls: `img/${roundId}.png`,
                });
            }

            // async
            this.updateGoal(gamedatas.currentRound.playUntilVisibility);

            for (const character of gamedatas.characters) {
                const taleId = this.getTaleIdByCharacterId(character.id);
                const characterDiv = document.createElement('div');
                const taleInnerId = this.getTaleIdByCharacterId(character.id, 'inner');
                characterDiv.id = taleInnerId;
                characterDiv.classList = 'tale character-field';
                $(taleId).appendChild(characterDiv);
                this.addImg({
                    id: taleInnerId,
                    isCharacter: true,
                    urls: this.getCharacterImage(character),
                });
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

            for (const pos of this.availableDetectivePos) {
                const taleId = `tale_${pos.id}`;
                dojo.setStyle(taleId, { 'flex-direction': `${!pos.y || pos.y === 4 ? 'column' : 'row'}${!pos.y || !pos.x ? '-reverse' : ''}` });
            }

            for (const detective of gamedatas.detectives) {
                // async
                this.moveDetective({ detectiveId: detective.id, newPos: detective.pos, oldPos: null });
            }

            this.updateAvailableAlibiCards();

            this.updateAlibiCards();

            this.setupNotifications();

            this.setPlayerPanels();

            this.addImg({ id: 'visible-status-card-front', urls: 'img/invisible_card.jpg' });
            this.addImg({ id: 'visible-status-card-back', urls: 'img/visible_card.png' });
            this.addImg({ id: 'alibi-deck-img', urls: 'img/alibi_back.png' });

            console.log( "Ending game setup" );
        },

        setPlayerPanels() {
            // TODO upsert this.removeTooltip( nodeId: string );
            try {
                this.removeTooltip('jack-character');
                this.removeTooltip('jack-winned-rounds');
                this.removeTooltip('detective-winned-rounds');
                this.removeTooltip('jack-alibi');
            } catch {}

            // jackId?, jackAlibiCards?
            // winnedRounds, jackAlibiCardsNum
            // jack -> jack: character, winned, alibi
            // jack -> dets: winned
            // 
            // dets -> jack: character(-), winned, alibi(only num)
            // dets -> dets: winned
            const playerIds = Object.keys(this.currentData.players);
            const oppositePlayerId = playerIds.find((playerId) => String(playerId) !== String(this.player_id));
            const playerBoardDiv = $(`player_board_${this.player_id}`);
            const oppositePlayerBoardDiv = $(`player_board_${oppositePlayerId}`);

            const [jackBoardDiv, detectiveBoardDiv] = this.currentData.jackId
                ? [playerBoardDiv, oppositePlayerBoardDiv]
                : [oppositePlayerBoardDiv, playerBoardDiv];

            if (!$('jack-character')) {
                dojo.place(
                    this.format_block('jstpl_jack_panel', {}),
                    jackBoardDiv,
                );
                dojo.place(
                    this.format_block('jstpl_detective_panel', {}),
                    detectiveBoardDiv,
                );
    
                this.addImg({
                    _class: 'time-label',
                    urls: 'img/time.png',
                });
    
                this.addImg({
                    id: 'jack-character',
                    urls: 'img/alibi_back.png',
                });
            }

            const jackWinnedRounds = this.currentData.previousRounds.filter((e) => !e.isCriminalVisible);
            const detectiveWinnedRounds = this.currentData.previousRounds.filter((e) => e.isCriminalVisible);
            $('jack-winned-rounds-num').innerText = jackWinnedRounds.length;
            this.addWinnedRoundsTooltip('jack-winned-rounds', jackWinnedRounds);
            $('detective-winned-rounds-num').innerText = detectiveWinnedRounds.length;
            this.addWinnedRoundsTooltip('detective-winned-rounds', detectiveWinnedRounds);

            this.addTooltipHtml(
                'jack-character',
                this.format_block('jstpl_jack_character_tooltip',
                {
                    styles: this.addImg({
                        urls: this.currentData.jackId
                            ? this.getMetaCharacterById(this.currentData.jackId).alibi_img
                            : 'img/alibi_back.png',
                    }).text,
                }),
            );

            if (this.currentData.jackId) {
                $('jack-alibi-num').innerHTML = this.getAlibiJackPoints();
            }

            this.addJackAlibiTooltip(this.currentData.jackAlibiCards, this.currentData.jackAlibiCardsNum);
        },

        getAlibiJackPoints() {
            return this.currentData.jackAlibiCards
                .map((characterId) => this.getMetaCharacterById(characterId))
                .reduce((acc, cur) => acc + cur.points, 0)
        },

        addWinnedRoundsTooltip(id, winnedRounds) {
            this.addTooltipHtml(
                id,
                this.format_block(
                    'jstpl_winned_rounds_tooltip',
                    {
                        rounds: winnedRounds
                            .map((e) => this.format_block(
                                'jstpl_winned_round_tooltip',
                                {
                                    styles: this.addImg({
                                        urls: `img/round_${e.num}.png`,
                                    }).text,
                                },
                            ))
                            .join(''),
                    },
                ),
            );
        },

        addJackAlibiTooltip(jackAlibiCards, jackAlibiCardsNum) {
            this.addTooltipHtml(
                'jack-alibi',
                this.format_block(
                    'jstpl_jack_alibi_cards_tooltip',
                    {
                        alibis: (jackAlibiCards ?? range(jackAlibiCardsNum))
                            .map((e) => this.format_block(
                                'jstpl_jack_alibi_card_tooltip',
                                {
                                    styles: this.addImg({
                                        urls: jackAlibiCards
                                            ? this.getMetaCharacterById(e).alibi_img
                                            : 'img/alibi_back.png',
                                    }).text,
                                },
                            ))
                            .join(''),
                    },
                ),
            );
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
            this.clearActionEventListeners(action);
            dojo.query(`.tale-to-choose`).removeClass('tale-to-choose');
            const cancelButton = $('cancel-button');
            if (!cancelButton) {
                this.addActionButton('cancel-button', _('Cancel'), 'clickOnCancelButton', null, false, 'red');
            }
        },

        clickOnCancelButton() {
            if (this.optionActions.rotation.taleId) {
                const character = this.getCharacterById(this.optionActions.rotation.taleId);
                if (this.optionActions.rotation.wallSide && this.optionActions.rotation.wallSide !== character.wallSide) {
                    this.rotateTale({
                        characterId: this.optionActions.rotation.taleId,
                        oldWallSide: this.optionActions.rotation.wallSide,
                        newWallSide: character.wallSide,
                    });
                }
                this.destroyRotationButtons();
            }

            this.clearActionEventListeners();
            this.clearCharacterEventListeners();
            this.clearDetectiveEventListeners();
            // - clear all css check TODO
            
            this.optionActions = {
                rotation: {},
                exchange: {},
                detective: {},
                jocker: {},
            };
            if (this.isCurrentPlayerActive()) {
                this.initOptions(
                    this.currentData.currentOptions,
                    this.currentData.nextOptions,
                );
            }
            this.removeActionButtons();
            this.setDescriptionState('must choose an action');
        },

        setDescriptionState(state = '') {
            // this.setClientState("client_playerPicksLocation", {
            //     descriptionmyturn : _("${you} " + state),
            // });
            $('pagemaintitletext').innerHTML = _(state ? "You " + state : 'Loading');
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

        clearActionEventListeners(actionToStayColor) {
            const options = this.eventListeners.options.map((e) => e.option);
            const optionToStayColor = actionToStayColor ? this.eventListeners.options.find((e) => e.option === actionToStayColor) : undefined;
            options.forEach((option) => this.removeOptionEventListener(option, actionToStayColor));
            if (optionToStayColor) {
                dojo.removeClass(optionToStayColor.id, 'option-was-used');
            }
        },

        removeOptionEventListener(option, actionToStayColor) {
            // TODO add card inactive by styles
            const e = this.eventListeners.options.find((e) => e.option === option);
            $(e.id).removeEventListener(e.type, e.listener);
            dojo.addClass(e.id, 'option-was-used');
            dojo.removeClass(e.id, 'option-is-ready');
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

            return this.detectiveListener.bind(this, option, false);
        },

        detectiveListener(detectiveId, isJocker = false) {
            this.clickOnAction(detectiveId);

            const detective = this.getDetectiveById(detectiveId);
            const metaDetective = this.getMetaDetectiveById(detectiveId);
            const currentPos = detective.pos;
            const availablePoses = this.getAvailablePoses(currentPos, isJocker ? 1 : 2);
            this.optionActions[isJocker ? 'jocker' : 'detective'].detectiveId = detectiveId;

            availablePoses.forEach(({ fePos, bePos }) => {
                const taleId = `tale_${fePos.id}`;
                dojo.addClass(taleId, 'tale-to-choose');
                const type = 'click';
                const listener = this.onNewPosClick(detectiveId, bePos.index, isJocker);
                $(taleId).addEventListener(type, listener);
                this.eventListeners.detectiveTales.push({ id: taleId, type, listener });
            });
            this.setDescriptionState(`must choose a new position for ${metaDetective.name}`);
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
                this.setDescriptionState();
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
                const listener = (event) => {
                    this.detectiveListener(e.id, true);
                };
                dojo.addClass(taleId, 'tale-to-choose');
                $(taleId).addEventListener(type, listener);
                this.eventListeners.detectiveTales.push({ id: taleId, type, listener });
            });
            this.setDescriptionState(`must choose a detective to move`);
        },

        skipByJockerIfJack() {
            this.clearDetectiveEventListeners();
            this.removeActionButtons(); // dojo.destroy('skip-jocker-way');
            this.optionActions.jocker.newPos = null;
            this.optionActions.jocker.detectiveId = null;
            this.action_jocker();
            this.setDescriptionState();
        },

        getAvailablePoses(currentPos, steps) {
            return range(steps)
                .map((n) => n + currentPos)
                .map((n) => (n % this.availableDetectivePos.length) + 1)
                .map((i) => {
                    const bePos = this.currentData.meta.detectivePos[i];
                    const fePos = this.availableDetectivePos.find((e) => e.x === bePos.x && e.y === bePos.y);
                    return { bePos, fePos };
                });
        },

        alibiListener() {
            this.clickOnAction('alibi');
            this.action_alibi();
        },

        rotateTaleListener() {
            this.clickOnAction('rotation');
            this.currentData.characters
                .filter((e) => e.lastRoundRotated !== this.currentData.currentRound.num)
                .forEach(
                    (e) => this.setTaleListener(e.id, 'rotateTaleListenerTale', 'inner')
                );
            this.setDescriptionState('must choose a character to rotate');
        },

        rotateTaleListenerTale(characterId) {
            return function (e) {
                // 'clockwise'
                // 'counter-clockwise'
                // 'rotate-approve'
                this.optionActions.rotation.taleId = characterId;
                // TODO clear the new after click on another tale
                this.clearCharacterEventListeners();
                const character = this.getCharacterById(characterId);
                const taleId = this.getTaleIdByCharacterId(characterId, 'outer');
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

                const metaCharacter = this.getMetaCharacterById(characterId);
                this.setDescriptionState(`must choose a rotation for ${metaCharacter.name}`);
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
            const temp = wallIndex + direction;
            const newWallIndex = temp === -1 ? 3 : temp % 4;

            const newWallSide = Object.entries(this.sideDict)
                .find(([_, v]) => v === newWallIndex)
                [0];
            // console.log(
            //     'oldWallSide', oldWallSide,
            //     '\nwallIndex', wallIndex,
            //     '\nnewWallIndex', newWallIndex,
            //     '\nnewWallSide', newWallSide,
            //     '\nthis.sideDict', this.sideDict,
            // );
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
                this.setDescriptionState()
            };
        },

        exchangeTalesListener() {
            this.clickOnAction('exchange');
            
            this.currentData.characters.forEach(
                (e) => this.setTaleListener(e.id, 'exchangeTalesListenerTale1'),
            );
            this.setDescriptionState('must choose a first character to exchange');
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
                const metaCharacter = this.getMetaCharacterById(characterId);
                this.setDescriptionState(`must choose a second character to exchange it with ${metaCharacter.name}`);
            };
        },

        exchangeTalesListenerTale2(characterId) {
            return function (e) {
                this.optionActions.exchange.taleId2 = characterId;
                this.clearCharacterEventListeners();
                this.action_exchangeTales();
                this.setDescriptionState();
            };
        },

        setTaleListener(characterId, funcName, layout) {
            const taleId = this.getTaleIdByCharacterId(characterId, layout);
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

        actionDone() {
            this.removeActionButtons();
        },

        action_exchangeTales() {
            this.actionDone();
            const { taleId1, taleId2 } = this.optionActions.exchange;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/exchange.html", {
                taleId1: taleId1,
                taleId2: taleId2,
            }, this, () => {});
        },

        action_rotateTale() {
            this.actionDone();
            const { taleId, wallSide } = this.optionActions.rotation;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/rotate.html", {
                taleId: taleId,
                wallSide: wallSide,
            }, this, () => {});
        },

        action_alibi() {
            this.actionDone();
            this.ajaxcall( "/mrjackpocket/mrjackpocket/alibi.html", {}, this, () => {});
        },

        action_jocker() {
            this.actionDone();
            const { detectiveId, newPos } = this.optionActions.jocker;
            this.ajaxcall( "/mrjackpocket/mrjackpocket/jocker.html", {
                detectiveId: detectiveId,
                newPos: newPos,
            }, this, () => {});
        },

        action_detective() {
            this.actionDone();
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

        updateAlibiCards() {
            const el = $('detective-alibi');
            el.innerHTML = '';
            this.currentData.detectiveAlibiCards.forEach(
                (e, i) => el.appendChild(
                    Object.assign(
                        document.createElement('div'),
                        {
                            id: `detective-alibi_${i}`,
                            innerText: `detective alibi: ${e}`,
                        },
                    ),
                ),
            );
            (this.currentData.jackAlibiCards ?? []).forEach(
                (e, i) => el.appendChild(
                    Object.assign(
                        document.createElement('div'),
                        {
                            id: `jack-alibi_${i}`,
                            innerText: `jack alibi ${e}`,
                        },
                    ),
                ),
            );
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        // onEnteringState: this method is called each time we are entering into a new game state.
        //                  You can use this method to perform some user interface changes at this moment.
        //
        onEnteringState: function( stateName, args )
        {
            console.log( 'Entering state: '+stateName );
            
            switch(stateName) {
            case 'playerTurn':
                setTimeout(() => {
                    this.initOptions(
                        this.currentData.currentOptions,
                        this.currentData.nextOptions,
                    );
                }, 1000);
                break;
            }
        },

        // onLeavingState: this method is called each time we are leaving a game state.
        //                 You can use this method to perform some user interface changes at this moment.
        //
        onLeavingState: function( stateName )
        {
            console.log( 'Leaving state: '+stateName );
            
            switch(stateName) {
            case 'playerTurn':
                this.clearActionEventListeners();
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

        getTaleIdByCharacterId(characterId, status = 'casual') {
            if (status === 'inner') {
                return `character_${characterId}`;
            }

            const character = this.getCharacterById(characterId);
            const bePos = this.currentData.meta.characterPos[character.pos];
            const fePos = this.getFEPosByBEpos(bePos);
            return status === 'outer' ? `tale_outer_${fePos.id}` : `tale_${fePos.id}`;
        },

        getTaleIdByDetectiveId(detectiveId) {
            return detectiveId;
            // const detective = this.getDetectiveById(detectiveId);
            // const bePos = this.currentData.meta.detectivePos[detective.pos];
            // const fePos = this.getFEPosByBEpos(bePos);
            // return `tale_inner_${fePos.id}`;
        },

        getCharacterImage(character) {
            const metaCharacter = this.getMetaCharacterById(character.id);
            return character.isOpened ? metaCharacter.tale_img : metaCharacter.closed_tale_img;
        },

        rotateTale({ characterId, oldWallSide, newWallSide }) {
            const taleId = this.getTaleIdByCharacterId(characterId, 'inner');
            const degree = this.getDegree({ oldWallSide, newWallSide });
            // const character = this.getCharacterById(characterId);
            this.rotateTo(taleId, degree);
        },

        getDegree({ oldWallSide, newWallSide }) {
            return this.sideDict[newWallSide] * 90;
            // return (this.sideDict[newWallSide] - this.sideDict[oldWallSide]) * 90;
        },

        async exchangeTales({ characterId1, characterId2 }) {
            const taleId1 = this.getTaleIdByCharacterId(characterId1);
            const taleId2 = this.getTaleIdByCharacterId(characterId2);
            const taleIdInner1 = this.getTaleIdByCharacterId(characterId1, 'inner');
            const taleIdInner2 = this.getTaleIdByCharacterId(characterId2, 'inner');
            const tale1 = $(taleId1);
            const tale2 = $(taleId2);
            this.slideToObject(taleIdInner1, taleId1, 1000).play();
            this.slideToObject(taleIdInner2, taleId2, 1000).play();
            await delay(1500);
            const children1 = tale1.innerHTML;
            const children2 = tale2.innerHTML;
            tale1.innerHTML = children2;
            tale2.innerHTML = children1;
            dojo.setStyle(taleIdInner1, { left: '0px', top: '0px' });
            dojo.setStyle(taleIdInner2, { left: '0px', top: '0px' });
        },

        async moveDetective({ detectiveId, newPos, oldPos }) {
            const oldTaleId = this.getTaleIdByDetectiveId(detectiveId);
            const newBEPos = this.currentData.meta.detectivePos[newPos];
            const newFEPos = this.getFEPosByBEpos(newBEPos);
            const newTaleId = `${detectiveId}_new`;
            const metaDetective = this.getMetaDetectiveById(detectiveId);
            if (oldPos) {
                const taleRoute = this.getTaleRoute({ newPos, oldPos });
                for (const fePos of taleRoute) {
                    this.slideToObject(oldTaleId, `tale_${fePos.id}`, 500).play();
                    await delay(450);
                }
            }
            dojo.create(
                'div',
                {
                    id: newTaleId,
                    class: 'tale-inner',
                },
                $(`tale_${newFEPos.id}`),
            );
            if ($(oldTaleId)) {
                dojo.destroy(oldTaleId);
            }
            $(newTaleId).id = detectiveId;
            this.addImg({
                id: detectiveId,
                isCharacter: true,
                urls: metaDetective.img,
            });
        },

        getTaleRoute({ oldPos, newPos }) {
            // console.log('input', oldPos, newPos);
            let currentPos = oldPos;
            let isEnd = false;
            const result = [];
            while (!isEnd) {
                if (currentPos % 3 === 0) {
                    const { x, y } = this.currentData.meta.detectivePos[currentPos];
                    const isDown = y === 4;
                    const isUp = y === 0;
                    const isLeft = x === 0;
                    const isRight = x === 4;
                    const newX = isLeft || isRight
                        ? x
                        : isUp
                        ? x + 1
                        : x - 1;
                    const newY = isUp || isDown
                        ? y
                        : isRight
                        ? y + 1
                        : y - 1;
                    const newFEpos = this.boardPos.find((pos) => pos.x === newX && pos.y === newY);
                    if (!newFEpos) {
                        alert('System error! Please write support');
                        console.log('getTaleRoute', x, y, newX, newY, currentPos, this.currentData.meta.detectivePos[currentPos]);
                    }
                    result.push(newFEpos);
                    // console.log('corner cycle', newFEpos);
                }

                const nextPos = currentPos + 1;
                currentPos = nextPos > 12 ? nextPos - 12 : nextPos;
                result.push(this.getFEPosByBEpos(this.currentData.meta.detectivePos[currentPos]));

                if (currentPos === newPos) {
                    isEnd = true;
                }
                // console.log('cycle', nextPos, currentPos, isEnd);
            }
            // console.log('result', result);

            return result;
        },

        getAllDetectivesAtFEPos(id) {
            return dojo.query(".tale-inner", $(`tale_${id}`))
                .map((e) => e.id)
                .map((e) => this.getDetectiveById(e));
        },

        async closeCharacter(characterId) {
            const character = this.getCharacterById(characterId);
            const taleId = this.getTaleIdByCharacterId(characterId, 'inner');
            const oldImage = this.addImg({ urls: this.getCharacterImage({ ...character, isOpened: true }) }).obj['background-image'];
            const newImage = this.addImg({ urls: this.getCharacterImage({ ...character, isOpened: false }) }).obj['background-image'];
            const keyFrameId = `close-${characterId}-character`;
            const { wallSide } = character;
            const axis = ['up', 'down'].includes(wallSide) ? 'Y' : 'X';
            const currentRotationDeg = wallSide === 'up'
                ? 180
                : wallSide === 'down'
                ? 0
                : wallSide === 'right'
                ? -90
                : 90;
            const currentRotation = `rotate(${currentRotationDeg}deg)`;
            const keyFrame = `
                @keyframes ${keyFrameId} {
                    from  {
                        transform: rotate${axis}(0deg) ${currentRotation};
                        transform-style: preserve-3d;
                    }
                    50% {
                        transform: rotate${axis}(90deg) ${currentRotation};
                        background-image: ${oldImage};
                        transform-style: preserve-3d;
                    }
                    51% {
                        transform: rotate${axis}(90deg) ${currentRotation};
                        background-image: ${newImage};
                        transform-style: preserve-3d;
                    }
                    to  {
                        transform: rotate${axis}(0deg) ${currentRotation};
                        background-image: ${newImage};
                        transform-style: preserve-3d;
                    }
                }
            `;
            document.styleSheets[document.styleSheets.length-1].insertRule(keyFrame, 0);
            dojo.setStyle(taleId, {
                animation: `${keyFrameId} 1s 1`,
                'animation-fill-mode': 'forwards',
            });

            await delay(1000);
            dojo.setStyle(taleId, {
                animation: '',
                'animation-fill-mode': '',
                'background-image': newImage,
                // transform: `${currentRotation}`,
            });
        },

        addImg({ id, _class, urls, isCharacter = false }) {
            const background = (typeof urls === 'string' ? [urls] : urls)
                .map((url) => `url('${g_gamethemeurl}${url}')`)
                .join(', ');

            const styles = {
                'background-image': background,
                'background-size': urls.length === 1 ? 'contain' : 'contain',
                'background-repeat': urls.length === 1 ? 'no-repeat' : 'no-repeat',
            };
            const textStyles = Object.entries(styles)
                .map(([key, value]) => `${key}:${value}`)
                .join(';');
            if (id) {
                if (isCharacter) {
                    document.styleSheets[document.styleSheets.length-1].insertRule(`#${id} { ${textStyles} }`, 0);
                } else {
                    dojo.setStyle(id, styles);
                }
            } else if (_class) {
                dojo.query(`.${_class}`).style(styles);
            }

            return {
                obj: styles,
                text: textStyles,
            };
        },

        async endRound({
            isVisible,
            playUntilVisibility, 
            newOptions,
            newNextOptions,
            characterIdsToClose,
            winPlayerId,
            newRoundNum,
        }) {
            await this.animateWinnerDetermination({ isVisible, newRoundNum });
            await Promise.all(
                this.currentData.characters
                    .filter(e => characterIdsToClose.includes(e.id))
                    .map(e => this.closeCharacter(e.id))
            );

            if (playUntilVisibility !== this.currentData.currentRound.playUntilVisibility) {
                await this.updateGoal(playUntilVisibility);
            }

            this.initOptions(
                newOptions,
                newNextOptions,
            );
        },

        async animateWinnerDetermination({ isVisible, newRoundNum }) {
            // as in JACK original
            // 1) card from the right side appears in front of players deck
            // 2) then it rotateY and we see visible or invisible
            // 3) card return where it was
            // alert(`isVisible = ${isVisible}`);
            const id = 'visible-status-card-inner';
            const wasVisible = this.currentData.previousRounds[this.currentData.previousRounds.length - 1]?.isCriminalVisible ?? false;

            this.slideToObject(id, 'container', 1400).play();
            dojo.setStyle(id, { 'box-shadow': SHADOW_ALL_SCREEN });
            await delay(1600);

            if (isVisible !== wasVisible) {
                dojo.toggleClass(id, 'is-visible');
                await delay(900);
            }

            this.slideToObject(id, 'visible-status-card-container', 1000).play();
            dojo.setStyle(id, { 'box-shadow': '' });
            await delay(1100);

            await this.animateNewRound({ isVisible, newRoundNum });
        },

        async animateNewRound({ isVisible, newRoundNum }) {
            // TODO increase counter current round to the winned person
            // 1) old round move to the winPlayer panel and destroy and increase counter
            const oldRoundId = `round_${newRoundNum - 1}`;
            const newRoundId = `round_${newRoundNum}`;
            const placeToMoveRound = isVisible ? 'detective-winned-rounds-pic' : 'jack-winned-rounds-pic';

            this.slideToObject(oldRoundId, 'container', 1400).play();
            dojo.setStyle(oldRoundId, { 'box-shadow': SHADOW_ALL_SCREEN, transform: 'scale(3)' });
            await delay(1600);

            this.slideToObject(oldRoundId, placeToMoveRound, 1000).play();
            dojo.setStyle(oldRoundId, { 'box-shadow': '', transform: 'scale(1)' });
            await delay(1100);

            dojo.destroy(oldRoundId);
            if ($(newRoundId)) {
                dojo.addClass(newRoundId, 'current-round');
            }
        },

        initOptions(currentOptions, nextOptions) {
            // TODO animate beauty
            // it should work like upsert
            $('available-options').children = '';
            for (const index in currentOptions) {
                const option = currentOptions[index];
                const nextOption = nextOptions?.[index];
                const availableId = `available_option_${index}`;
                const nextId = `next_option_${index}`;
                dojo.removeClass(availableId, 'option-was-used');
                dojo.removeClass(availableId, 'option-is-ready');
                if (option.wasUsed) {
                    dojo.addClass(availableId, 'option-was-used');
                }
                const available = $(availableId);
                this.addImg({
                    id: availableId,
                    urls: `img/${option.ability}_option.png`,
                });

                if (!nextOption) {
                    dojo.addClass(nextId, 'next-option-disable');
                } else {
                    dojo.removeClass(nextId, 'next-option-disable');
                    this.addImg({
                        id: nextId,
                        urls: `img/${nextOption.ability}_option.png`,
                    });
                }
                const hasListener = this.eventListeners.options.some((e) => e.id === availableId);

                if (!option.wasUsed && this.isCurrentPlayerActive()) {
                    dojo.addClass(availableId, 'option-is-ready');
                    if (!hasListener) {
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
            }
        },

        async updateGoal(playUntilVisibility) {
            // TODO animate for 1s
            const isJackPlayer = Boolean(this.currentData.jackId);
            const goalElement = $('goal-info-inner');
            // TODO change it to text and beautiful picture (maybe tooltip)
            goalElement.innerText = isJackPlayer && playUntilVisibility
                ? 'isJackPlayer && playUntilVisibility'
                : isJackPlayer && !playUntilVisibility
                ? 'isJackPlayer && !playUntilVisibility'
                : !isJackPlayer && playUntilVisibility
                ? '!isJackPlayer && playUntilVisibility'
                : '!isJackPlayer && !playUntilVisibility';
        },

        async alibiJack({ alibiId, points }) {
            await this.alibiUnified({ alibiId, points });
            this.updateAvailableAlibiCards();
        },

        async alibiAllExceptJack() {
            await this.alibiUnified();
            this.updateAvailableAlibiCards();
        },

        async alibiUnified({ alibiId, points } = {}) {
            const id = 'jack-alibi-opening-inner';
            const finalPlaceToMove = 'jack-winned-alibi-pic';
            dojo.place(
                this.format_block('jstpl_jack_alibi_opening', {}),
                'alibi-deck-opening',
            );
            this.addImg({ id: 'jack-alibi-opening-front' , urls: 'img/alibi_back.png' });
            const alibiSource = alibiId ? `img/alibi_${alibiId}.png` : undefined;
            if (alibiSource) {
                this.addImg({ id: 'jack-alibi-opening-back' , urls: alibiSource });
            }

            this.slideToObject(id, 'container', 1400).play();
            dojo.setStyle(id, { 'box-shadow': SHADOW_ALL_SCREEN });
            await delay(1600);

            if (alibiId) {
                dojo.toggleClass(id, 'is-visible');
                await delay(1100);
            }

            this.slideToObject(id, finalPlaceToMove, 1000).play();
            dojo.setStyle(id, { 'box-shadow': '', transform: 'scale(0.25)' });
            await delay(1100);
            dojo.destroy('jack-alibi-opening-container');
        },

        async alibiAll(alibiId) {
            // 1 создать там где колода
            // 2 назначить картинку небольшую
            // 3 поместить в центр
            // 4 красиво исчезнуть
            const id = 'jack-alibi-opening-inner';
            dojo.place(
                this.format_block('jstpl_jack_alibi_opening', {}),
                'alibi-deck-opening',
            );
            this.addImg({ id: 'jack-alibi-opening-front' , urls: 'img/alibi_back.png' });
            const alibiSource = `img/alibi_${alibiId}.png`;
            this.addImg({ id: 'jack-alibi-opening-back' , urls: alibiSource });

            this.slideToObject(id, 'container', 1400).play();
            dojo.setStyle(id, { 'box-shadow': SHADOW_ALL_SCREEN });
            await delay(1600);

            dojo.toggleClass(id, 'is-visible');
            await delay(1100);

            this.fadeOutAndDestroy('jack-alibi-opening-container', 1000);
            this.addImg({ id: 'jack-alibi-opening-front' , urls: alibiSource });
            await delay(1100);

            this.updateAvailableAlibiCards();
        },

        updateAvailableAlibiCards() {
            // TODO animate
            const deck = $('alibi-deck-counter');
            deck.innerText = 8 - ((this.currentData.jackAlibiCardsNum ?? 0) + this.currentData.detectiveAlibiCards.length);
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
            dojo.subscribe('roundEnd', this, 'notif_roundEnd');
            // this.notifqueue.setSynchronous('roundEnd', 3000);

            dojo.subscribe('rotateTale', this, 'notif_rotateTale');
            dojo.subscribe('exchangeTales', this, 'notif_exchangeTales');
            dojo.subscribe('jocker', this, 'notif_jocker');
            dojo.subscribe('detective', this, 'notif_detective');
            dojo.subscribe('alibiJack', this, 'notif_alibiJack');
            // todo     this.notifqueue.setIgnoreNotificationCheck( 'dealCard', (notif) => (notif.args.player_id == this.player_id) );
            dojo.subscribe('alibiAllExceptJack', this, 'notif_alibiAllExceptJack');
            dojo.subscribe('alibiAll', this, 'notif_alibiAll');            
        },

        // TODO: from this point and below, you can write your game notifications handling methods

        notif_rotateTale(notif) {
            console.log('notif_rotateTale');
            const { characterId, wallSide } = notif.args;
            console.log('characterId', characterId, 'wallSide', wallSide);

            this.optionWasUsed('rotation');
            const character = this.getCharacterById(characterId);
            if (wallSide !== character.wallSide) {
                this.rotateTale({
                    characterId,
                    oldWallSide: character.wallSide,
                    newWallSide: wallSide,
                });
            }
            character.wallSide = wallSide;
            character.lastRoundRotated = this.currentData.currentRound.num;
        },

        notif_exchangeTales(notif) {
            console.log('notif_exchangeTales');
            const { characterId1, characterId2 } = notif.args;
            console.log('characterId1', characterId1, 'characterId2', characterId2);

            this.optionWasUsed('exchange');
            const character1 = this.getCharacterById(characterId1);
            const character2 = this.getCharacterById(characterId2);
            const pos1 = character1.pos;
            const pos2 = character2.pos;
            character1.pos = pos2;
            character2.pos = pos1;
            this.exchangeTales({ characterId1, characterId2 }); // async
        },

        async notif_jocker(notif) {
            console.log('notif_jocker');
            const { detectiveId, newPos } = notif.args;
            console.log('detectiveId', detectiveId, 'newPos', newPos);

            this.optionWasUsed('jocker');
            if (!detectiveId || !newPos) {
                // TODO notify player what jack skip step by jocker
                alert('Jack skipped by jocker');
            } else {
                await this.detective({ detectiveId, newPos });
            }
        },

        async notif_detective(notif) {
            console.log('notif_detective');
            const { detectiveId, newPos } = notif.args;
            console.log('detectiveId', detectiveId, 'newPos', newPos);

            this.optionWasUsed(detectiveId);
            await this.detective({ detectiveId, newPos });
        },

        async detective({ detectiveId, newPos }) {
            const detective = this.getDetectiveById(detectiveId);
            await this.moveDetective({ detectiveId, newPos, oldPos: detective.pos });
            detective.pos = newPos;
        },

        async notif_alibiJack(notif) {
            console.log('notif_alibiJack');
            const { alibiId, points } = notif.args;
            console.log('alibiId', alibiId, 'points', points);

            this.optionWasUsed('alibi');
            await this.alibiJack({ alibiId, points });
            // TODO say player about alibi + points
            this.currentData.jackAlibiCards.push(alibiId);
            this.currentData.jackAlibiCardsNum = (this.currentData.jackAlibiCardsNum ?? 0) + 1;

            this.currentData.currentRound.availableALibiCards -= 1;
            this.updateAlibiCards();
            this.setPlayerPanels();
        },

        async notif_alibiAllExceptJack(notif) {
            console.log('notif_alibiAllExceptJack');
            const {} = notif.args;

            // TODO if jack ignore it
            const playerisJack = Boolean(this.currentData.jackId);
            if (playerisJack) {
                return;
            }
            this.optionWasUsed('alibi');
            await this.alibiAllExceptJack();
            // TODO say that jack took alibi card
            this.currentData.currentRound.availableALibiCards -= 1;
            this.updateAlibiCards();
            console.log(this.currentData.jackAlibiCardsNum);
            this.currentData.jackAlibiCardsNum = (this.currentData.jackAlibiCardsNum ?? 0) + 1;
            console.log(this.currentData.jackAlibiCardsNum);
            this.setPlayerPanels();
        },

        async notif_alibiAll(notif) {
            console.log('notif_alibiAll');
            const { alibiId, close } = notif.args;
            console.log('alibiId', alibiId, 'close', close);
            // TODO say player about alibi

            this.optionWasUsed('alibi');
            await this.alibiAll(alibiId);
            if (close) {
                await this.closeCharacter(alibiId); // async
                const character = this.getCharacterById(alibiId);
                character.isOpened = false;
            }
            this.currentData.detectiveAlibiCards.push(alibiId);
            // todo reduce it in the interface
            this.currentData.currentRound.availableALibiCards -= 1;
            this.updateAlibiCards();
        },

        optionWasUsed(option) {
            const currentOption = this.currentData.currentOptions.find((e) => e.ability === option && !e.wasUsed);
            if (currentOption) {
                currentOption.wasUsed = true;
            }
        },

        async notif_roundEnd(notif) {
            await delay(1000);
            console.log('notif_roundEnd');
            const {
                nextActivePlayerId,
                newRoundNum,
                newOptions,
                newNextOptions,
                characterIdsToClose: closeCharactersObj,
                isVisible,
                playUntilVisibility,
                winPlayerId,
            } = notif.args;
            const characterIdsToClose = Object.values(closeCharactersObj);
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

            const currentOptions = newOptions.map(e => ({ ability: e, wasUsed: false }));
            const nextOptions = newNextOptions?.map(e => ({ ability: e, wasUsed: false }));

            await this.endRound({
                isVisible,
                playUntilVisibility,
                newOptions: currentOptions,
                newNextOptions: nextOptions,
                characterIdsToClose,
                winPlayerId,
                newRoundNum,
            });

            this.currentData.previousRounds.push({
                num: newRoundNum - 1,
                winPlayerId,
                isCriminalVisible: isVisible,
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
            this.currentData.currentOptions = currentOptions;
            this.currentData.nextOptions = nextOptions;

            this.setPlayerPanels();
        },
   });   
});
