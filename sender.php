<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//Подключение
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

//Здесь объявляются две очереди: 'file_queue' для отправки файлов и 'reply_queue' для получения ответов
$channel->queue_declare('reply_queue', false, false, false, false);

//Создается временная очередь, которая будет использоваться для получения ответов
$callback_queue = $channel->queue_declare("", false, false, true, false)[0];

//Генерируется уникальный идентификатор для сопоставления запроса и ответа
$correlation_id = uniqid();

//Настройка обработчика для получения ответов. Он проверяет соответствие correlation_id с ожидаемым
$channel->basic_consume($callback_queue, '', false, true, false, false, function ($response) use ($correlation_id) {
    if ($response->get('correlation_id') == $correlation_id) {
        echo "Ответ получен: " . $response->body . "\n";
    }
});

//Создаём сообщение для отправки в файле с указанием correlation_id и reply_to, чтобы сервер знал, куда отправить ответ
$msg = new AMQPMessage(
    'Что-то отправили',
    array('correlation_id' => $correlation_id, 'reply_to' => $callback_queue)
);

//Отправляем сообщение
$channel->basic_publish($msg, '', 'file_queue');

echo "Запрос отправлен\n";

// ожидает ответ и обрабатывает его в обработчике
while (count($channel->callbacks)) {
    $channel->wait();
}

//закрываем соединение
$channel->close();
$connection->close();
