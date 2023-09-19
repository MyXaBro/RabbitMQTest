<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('file_queue', false, false, false, false);
$channel->queue_declare('reply_queue', false, false, false, false);

echo "Ожидание сообщения...\n";

$callback = function ($msg) use ($channel) {
    $filename = 'received_file.txt';
    file_put_contents($filename, $msg->body);

    $response = new \PhpAmqpLib\Message\AMQPMessage(
        'Файл успешно обработан',
        array('correlation_id' => $msg->get('correlation_id'))
    );
    $channel->basic_publish($response, '', $msg->get('reply_to'));
    echo "Файл успешно записан как received_file.txt\n";
};

$channel->basic_consume('file_queue', '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
