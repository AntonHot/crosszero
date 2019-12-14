$(document).ready(function() {
    console.log('start');
    // let socket = new WebSocket("ws://echo.websocket.org");
    let socket = new WebSocket("ws://84.201.185.53:889");
    console.log(socket);
    socket.onopen = function() {
        message('Соединение установлено');
    }

    socket.onclose = function() {
        message('Соединение закрыто');
    }

    socket.onerror = function(error) {
        message('Ошибка');
    }

    socket.onmessage = function(event) {
        let data = JSON.parse(event.data);
        message(data.type + ': ' + data.message);
    }
    
    $("#chat").on('submit', function() {
        event.preventDefault();
        let message = {
            chat_message: $("#chat-message").val(),
            chat_user: $("#chat-user").val()
        }
        $("#chat-user").attr('type', 'hidden');
        socket.send(JSON.stringify(message));
        return false;
    })
})

function message(text) {
    $('#chat-result').append('<div>' + text + '</div>');
}
