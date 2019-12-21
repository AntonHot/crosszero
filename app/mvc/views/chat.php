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
    <div class="container">
        <div class="container-chat">
            <div class="chat" id="chat">
                <div class="chat_messages" id="chat_messages"></div>
            </div>
            <form class="messenger" id="messenger">
                <input class="form-control" type="text" name="chat-message" id="chat-message" placeholder="Сообщение" autocomplete="off">
                <input class="btn btn-primary" type="submit" value="Отправить">
            </form>
        </div>
        <div class="chat_members" id="chat_members"></div>
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="../js/script.js"></script>
    <script>
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
                        printMessage(data.sender.name, 'Давай играть!');
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
                if (element.className === 'button-invite') {
                    let message = {
                        receivers: element.getAttribute('memberid'),
                        type: 300
                    }
                    socket.send(JSON.stringify(message));
                }
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
                    $("#chat_members").append(`<span><button class="button-invite" memberid="${value.id}">invite</button><div class="member-name">${value.name}</div></span>`);
                } else {
                    $("#chat_members").append(`<span><div class="member-name"><strong>${myname}</strong></div></span>`);
                }
            });
        }
    </script>
</body>
</html>
