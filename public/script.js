$(document).ready(function() {
    console.log('start');
    // let socket = new WebSocket("ws://echo.websocket.org");
    let socket = new WebSocket("ws://84.201.185.4:889");
    console.log(socket);
    socket.onopen = function() {
        message('Соединение установлено');
        socket.send('Hello!');
    }

    socket.onclose = function() {
        message('Соединение зарыто');
    }

    socket.onerror = function(error) {
        message('Ошибка');
    }

    socket.onmessage = function(event) {

        message(event.data);
    }
})

function message(text) {
    $('#chat-result').append('<div>' + text + '</div>');
}
