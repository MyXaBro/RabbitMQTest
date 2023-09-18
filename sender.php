<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->queue_declare('file_queue', false, false, false, false);

$message = "Меня записали в файл..."; //решил отправить сообщение в файле

$msg = new AMQPMessage($message);
$channel->basic_publish($msg, '', 'file_queue');

echo "Сообщение отправлено\n";

$channel->close();
$connection->close();
?>

