<!DOCTYPE html>
<html lang="en">
<head>
    <title>CrossZero</title>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../img/favicon.ico" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <div class="container-chat">
        <div class="chat" id="chat">
            <div class="chat_messages" id="chat_messages"></div>
        </div>
        <form class="messenger" id="messenger">
            <input class="form-control" type="text" name="chat-message" id="chat-message" placeholder="Сообщение" autocomplete="off">
            <input class="btn btn-primary" type="submit" value="Отправить">
        </form>
        <div class="chat_members" id="chat_members"></div>
    </div>

    <div class="modal fade" id="exampleModalLong" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">Game: Anton vs Stas</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="game-field" id="game-field">
                        <tr>
                            <td class="game-field__cell" row="0" column="0"></td>
                            <td class="game-field__cell" row="0" column="1"></td>
                            <td class="game-field__cell" row="0" column="2"></td>
                        </tr>
                        <tr>
                            <td class="game-field__cell" row="1" column="0"></td>
                            <td class="game-field__cell" row="1" column="1"></td>
                            <td class="game-field__cell" row="1" column="2"></td>
                        </tr>
                        <tr>
                            <td class="game-field__cell" row="2" column="0"></td>
                            <td class="game-field__cell" row="2" column="1"></td>
                            <td class="game-field__cell" row="2" column="2"></td>
                        </tr>
                    </table>
                    <p class="game-status" id="game-status"></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="../js/script.js"></script>
    <script>

        let game;
        let myid = getCookie('PHPSESSID');
        let myname = getCookie('username');

        $(document).ready(function() {
            let socket = new WebSocket("ws://84.201.185.53:889");
            socket.onopen = function() {
                printMessage('🤖', 'Соединение установлено');
                let message = {
                    sender: getCookie('username'),
                    phpsessid: getCookie('PHPSESSID'),
                    type: 100
                };
                socket.send(JSON.stringify(message));
            }

            socket.onclose = function() {
                printMessage('🤖', 'Соединение закрыто');
            }

            socket.onerror = function(error) {
                printMessage('🤖', 'Ошибка подключения');
            }

            socket.onmessage = function(event) {
                const STATE_MEMBERS = 101;
                const TEXT_MESSAGE = 200;
                const INVITE_GAME = 300;
                const NEW_GAME = 400;
                const GAME_MOVE = 401;

                let data = JSON.parse(event.data);
                let type = data.type;
                switch (type) {
                    case (TEXT_MESSAGE):
                        printMessage(data.sender.name, data.text);
                        break;
                    case (STATE_MEMBERS):
                        updateMembers(data.members);
                        break;
                    case (INVITE_GAME):
                        printMessage(
                            data.sender.name,
                            `Давай играть?
                            <button gameid="${data.game.id}" memberid="${data.sender.id}" class="small-btn accept"></button>
                            <button gameid="${data.game.id}" memberid="${data.sender.id}" class="small-btn decline"></button>`
                        );
                        break;
                    case (NEW_GAME):
                        startGame(data);
                        break;
                    case (GAME_MOVE):
                        updateGame(data);
                        break;
                    default:
                        break;
                }
            }

            $("#messenger").on('submit', function() {
                event.preventDefault();
                let message = {
                    receivers: 'all',
                    text: $("#chat-message").val(),
                    type: 200
                }
                if (message.text !== '') {
                    $("#chat-user").attr('type', 'hidden');
                    socket.send(JSON.stringify(message));
                    $("#chat-message").val('');
                }
            })

            $("#chat_members").on('click', function() {
                let element = event.target;
                if (element.classList.contains('invite')) {
                    let message = {
                        receivers: element.getAttribute('memberid'),
                        type: 300
                    }
                    socket.send(JSON.stringify(message));
                }
            })

            $("#chat_messages").on('click', function() {
                let element = event.target;
                let message;
                if (element.classList.contains('accept')) {
                    message = {
                        receivers: element.getAttribute('memberid'),
                        gameid: element.getAttribute('gameid'),
                        type: 301
                    }
                } else if (element.classList.contains('decline')) {
                    message = {
                        receivers: element.getAttribute('memberid'),
                        gameid: element.getAttribute('gameid'),
                        type: 302
                    }
                }
                socket.send(JSON.stringify(message));
            })

            $("#game-field").on('click', function() {
                let element = event.target;
                let row = element.getAttribute('row');
                let column = element.getAttribute('column');
                let modal = $("#exampleModalLong");
                let message;
                if (game.whoseMove.id !== myid) {
                    return;
                }
                game.state[row][column] = game.whoseMove.figure;
                updateGameField();
                message = {
                    type: 401,
                    receivers: [game.players[0].id, game.players[1].id],
                    state: game.state,
                    gameid: game.id
                }
                socket.send(JSON.stringify(message));
            })

            $('#exampleModalLong').on('hidden.bs.modal', function (e) {
                debugger;
            })
        });

        function printMessage(sender, text) {
            let chat = $("#chat_messages")[0];
            let classes = 'message';
            if (sender === '🤖') {
                classes += ' robot';
            }
            if (sender !== null || text !== null) {
                $('#chat_messages').append(`<span class="${classes}"><strong>${sender}: </strong>${text}</span>`);
            }
            chat.scrollTop = chat.scrollHeight;
        }

        function updateMembers(members) {
            $("#chat_members").empty();
            myid = getCookie('PHPSESSID');
            myname = getCookie('username');
            $.each(members, function(index, value){
                if (value.id !== myid && value.id !== null) {
                    $("#chat_members").append(`<span><button class="small-btn invite" memberid="${value.id}">invite</button><div class="member-name">${value.name}</div></span>`);
                }
            });
            $("#chat_members").prepend(`<span><div class="member-name"><strong>${myname}</strong></div></span>`);
        }

        function startGame(data) {
            let modal = $('#exampleModalLong');
            modal.modal('show');
            game = data.game;
            $("#exampleModalLongTitle").text(`Game: ${game.players[0].name} vs ${game.players[1].name}`);
            updateGameField();
        }

        function updateGame(data) {
            let modal = $('#exampleModalLong');
            game = data.game;
            updateGameField();
        }

        function updateGameField() {
            let field = $("#game-field")[0];
            let status = $("#game-status")[0];
            status.innerText = 'Ход ' + game.whoseMove.name;
            for (let i = 0; i <= 2; i++) {
                for (let j = 0; j <= 2; j++) {
                    field.rows[i].cells[j].innerText = game.state[i][j];
                }
            }
        }

    </script>
</body>
</html>
