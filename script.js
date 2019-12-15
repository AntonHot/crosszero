$(document).ready(function() {
    console.log('start');
    // let socket = new WebSocket("ws://echo.websocket.org");
    let socket = new WebSocket("ws://84.201.185.53:889");
    console.log(socket);
    socket.onopen = function() {
        message('Robot', 'Соединение установлено');
    }

    socket.onclose = function() {
        message('Robot', 'Соединение закрыто');
    }

    socket.onerror = function(error) {
        message('Robot', 'Ошибка');
    }

    socket.onmessage = function(event) {
        let data = JSON.parse(event.data);
        message(data.user, data.message);
    }
    
    $("#messenger").on('submit', function() {
        event.preventDefault();
        let message = {
            user: $("#chat-user").val(),
            text: $("#chat-message").val()
        }
        if (message.text !== '') {
            $("#chat-user").attr('type', 'hidden');
            socket.send(JSON.stringify(message));
            $("#chat-message").val('');
        }
    })
})

function message(author, text) {
    let classes = 'message';
    if (author === 'Robot') {
        classes += ' robot';
    }
    if (author !== null || text !== null) {
        $('#chat').append(
            '<div class="' + classes + '"><div class="message_username">' +
            author +
            '</div><div class="message_text">' +
            text +
            '</div></div>'
        );
    }
}
