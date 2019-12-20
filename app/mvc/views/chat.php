<!DOCTYPE html>
<html lang="en">
<head>
    <title>CrossZero</title>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../favicon.ico" type="image/png">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="chat" id="chat">
            <div class="chat_messages" id="chat_messages"></div>
            <div class="chat_members" id="chat_members"></div>
        </div>
        <form class="messenger" id="messenger">
            <input class="form-control" type="text" name="chat-message" id="chat-message" placeholder="Сообщение" autocomplete="off">
            <input class="btn btn-primary" type="submit" value="Отправить">
            <input class="btn btn-success" type="button" id="start" value="Начать игру">
        </form>
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
                    from: getCookie('username'),
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
                let data = JSON.parse(event.data),
                    chat = $("#chat_messages")[0];
                printMessage(data.user, data.message);
                $("#chat_members").empty();
                $.each(data.members, function(index, value){
                    $("#chat_members").append(`<span><button class='btn-invite' id="${value.id}" onclick="pr(this)"></button>${value.username}</span>`);
                });
                chat.scrollTop = chat.scrollHeight;
            }

            $("#messenger").on('submit', function() {
                event.preventDefault();
                let message = {
                    to: 'all',
                    text: $("#chat-message").val(),
                    type: 200
                }
                if (message.text !== '') {
                    $("#chat-user").attr('type', 'hidden');
                    socket.send(JSON.stringify(message));
                    $("#chat-message").val('');
                }
            })

            $("#start").on('click', function() {
                let message = {
                    username: getCookie('username'),
                    text: 'Хочу играть!',
                    type: 101
                }
                socket.send(JSON.stringify(message));
            });
        });

        function printMessage(author, text) {
            let classes = 'message';
            if (author === '🤖') {
                classes += ' robot';
            }
            if (author !== null || text !== null) {
                $('#chat_messages').append(`<span class="${classes}"><strong>${author}: </strong>${text}</span>`);
            }
        }
    </script>
</body>
</html>
