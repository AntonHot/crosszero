<!DOCTYPE html>
<html lang="en">
<head>
    <title>Chat</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="../style.css?3">
</head>
<body>
    <div class="container">
        <div class="chat" id="chat">
            
        </div>
        <form class="messenger" id="messenger">
            <input class="form-control" type="text" name="chat-message" id="chat-message" placeholder="Message" autocomplete="off">
            <input class="btn btn-primary" type="submit" value="Send">
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="../script.js"></script>
    <script>
        $(document).ready(function() {
            let socket = new WebSocket("ws://84.201.185.53:889");
            socket.onopen = function() {
                message('🤖', 'Соединение установлено');
            }

            socket.onclose = function() {
                message('🤖', 'Соединение закрыто');
            }

            socket.onerror = function(error) {
                message('🤖', 'Ошибка подключения');
            }

            socket.onmessage = function(event) {
                let data = JSON.parse(event.data),
                    chat = $("#chat")[0];

                message(data.user, data.message);
                chat.scrollTop = chat.clientHeight;
            }
            
            $("#messenger").on('submit', function() {
                event.preventDefault();
                let message = {
                    username: getCookie('username'),
                    text: $("#chat-message").val()
                }
                if (message.text !== '') {
                    $("#chat-user").attr('type', 'hidden');
                    socket.send(JSON.stringify(message));
                    $("#chat-message").val('');
                }
            })
        });

        function message(author, text) {
            let classes = 'message';
            if (author === '🤖') {
                classes += ' robot';
            }
            if (author !== null || text !== null) {
                $('#chat').append(`<span class="${classes}"><strong>${author}: </strong>${text}</span>`);
            }
        }
    </script>
</body>
</html>
