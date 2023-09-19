<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('file_queue', false, false, false, false);

echo "Ожидание сообщения...\n";

$callback = function ($msg) {
    $fileContent = $msg->body;
    $filename = 'received_file.txt'; //название файла для сохранения

    file_put_contents($filename, $fileContent);

    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);

    echo "Файл успешно записан как $filename\n";
};

$channel->basic_qos(null, 1, null);
$channel->basic_consume('file_queue', '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>
