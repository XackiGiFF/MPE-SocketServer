<?php
// We specify the port and address for the server
$address = '127.0.0.1';
$port = 12345;

// Server Parameters
$serverName = "MPE-SocketServer";
$creator = "XackiGiFF";
$version = "1.0";
$creationDate = date("Y-m-d H:i:s");
$os = PHP_OS;
$ramUsage = memory_get_usage() / 1024 / 1024; // In MB
$cpuUsage = sys_getloadavg()[0]; // Average CPU usage per 1 minute

// Creating a server socket
$socket = stream_socket_server("tcp://$address:$port", $errno, $errstr);
if (!$socket) {
    die("Error: $errstr ($errno)\n");
}

echo "Server starts on $address:$port...\n";

$clients = []; // Array to hold connected clients
$clients[(int)$socket] = $socket; // Add server socket to clients array
$usernames = [];

while (true) {
    $read = $clients; // Copy clients array for stream_select
    // Wait for activity on any of the sockets
    $var = $write = null;
    $var1 = $except = null;
    if (stream_select($read, $var, $var1, 0) === false) {
        echo "Error in stream_select.\n";
        break;
    }

    foreach ($read as $conn) {
        // If the server socket is readable, a new client is connecting
        if ($conn === $socket) {
            $newConn = stream_socket_accept($socket);
            if ($newConn) {
                $clients[(int)$newConn] = $newConn; // Add new client to the clients array
                $clientInfo = stream_socket_get_name($newConn, true);
                echo "New client connected: $clientInfo\n";

                // Приветственное сообщение
                $wellcome = "🌟 Добро пожаловать на сервер! 🌟" . "\n" .
                    "👤 Ваш IP: $clientInfo\n" .
                    "💬 Используйте команду 'info' для получения информации о сервере.\n" .
                    "❌ Для отключения от сервера введите 'exit'.\n" .
                    "⏹️ Для остановки сервера введите 'stop'.\n" .
                    "🎉 Спасибо, что подключились! Приятного общения! 🎉" . "\n" .
                    "[" . $usernames[(int)$conn] . "] >";
                fwrite($newConn, json_encode( $wellcome ) ); // Send response to client
            }
        } else {
            // Processing data from the client
            $data = fread($conn, 1024);
            if ($data === false || $data === '') {
                // Client has disconnected
                echo "Client disconnected: " . stream_socket_get_name($conn, true) . "\n";
                fclose($conn);
                unset($clients[(int)$conn]); // Remove client from the list
                continue;
            }

            $cmd = json_decode( $data );

            if(isset($cmd->username)) {
                $usernames[(int)$conn] = $cmd->username;
            }

            if(isset($cmd->message)) {
                if ($cmd->message == 'exit') {
                    echo "The client has disconnected.\n";
                    fclose($conn);
                    unset($clients[(int)$conn]); // Remove client from the list
                    continue; // Exit the loop for this client
                }
                if ($cmd->message == 'stop') {
                    echo "Close sockets...\n";
                    foreach ($clients as $client) {
                        fclose($client); // Closing all client connections
                    }
                    fclose($socket); // Closing the server socket
                    echo "Server was stopped.\n";
                    exit; // Completing the script execution
                }
                if ($cmd->message == 'info') {
                    $nowtime = date("Y-m-d H:i:s");
                    global $serverName, $creator, $version, $creationDate, $os, $ramUsage, $cpuUsage;
                    $response = "[$serverName] $nowtime > \n - Creator: {$creator}, \n - Ver: {$version}, \n - Date: {$creationDate}, \n - OS: {$os}, \n - RAM: " . round($ramUsage, 2) . " MB, \n - CPU: " . round($cpuUsage, 2) . "%";
                    fwrite($conn, json_encode( $response . "\n") ); // Send response to client
                    continue; // Exit the loop for this client
                }
            }

            // Broadcast message to all clients except the sender
            foreach ($clients as $client) {
                // Проверяем, что клиент не является отправителем
                if ($client !== $conn ) {
                    // Отправляем сообщение другим клиентам
                    if(isset($cmd->message)) {
                        // Send message to other clients
                        if (is_resource($client)) {
                            fwrite($client, json_encode("\n[" . $usernames[(int)$conn] . '] > ' . $cmd->message . "\n"));
                        }
                    }
                }
            }

            fwrite($conn, json_encode( "[" . $usernames[(int)$conn] . '] > ' ) ); // Отправляем ответ клиенту
        }
    }
}