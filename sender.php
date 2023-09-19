<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('file_queue', false, false, false, false);
$channel->queue_declare('reply_queue', false, false, false, false);

$callback_queue = $channel->queue_declare("", false, false, true, false)[0];

$correlation_id = uniqid();

$channel->basic_consume($callback_queue, '', false, true, false, false, function ($response) use ($correlation_id) {
    if ($response->get('correlation_id') == $correlation_id) {
        echo "Ответ получен: " . $response->body . "\n";
    }
});

$msg = new AMQPMessage(
    'Запрос на обработку файла',
    array('correlation_id' => $correlation_id, 'reply_to' => $callback_queue)
);

$channel->basic_publish($msg, '', 'file_queue');

echo "Запрос отправлен\n";

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
