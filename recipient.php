<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Устанавливаем соединение с RabbitMQ
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

// Объявляем очереди для отправки и получения сообщений
$channel->queue_declare('file_queue', false, false, false, false);
$channel->queue_declare('reply_queue', false, false, false, false);

echo "Ожидание сообщения...\n";

// Callback-функция для обработки полученного сообщения
$callback = function ($msg) use ($channel) {
    // Задаем имя файла, куда будем записывать содержимое
    $filename = 'received_file.txt';
    // Записываем содержимое сообщения в файл
    file_put_contents($filename, $msg->body);

    // Создаем ответное сообщение
    $response = new AMQPMessage(
        'Файл успешно обработан',
        array('correlation_id' => $msg->get('correlation_id'))
    );

    // Отправляем ответ
    $channel->basic_publish($response, '', $msg->get('reply_to'));
    echo "Файл успешно записан как received_file.txt\n";
};

// Подписываемся на очередь для получения сообщений
$channel->basic_consume('file_queue', '', false, true, false, false, $callback);

// Ожидаем сообщения и обрабатываем их
while (count($channel->callbacks)) {
    $channel->wait();
}

// Закрываем канал и соединение
$channel->close();
$connection->close();
