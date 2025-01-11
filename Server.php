<?php

/**
 * MPE Socket Server
 *
 * This script implements a TCP server that handles client connections.
 * The server can send information about itself and process commands from clients.
 *
 * Server Parameters:
 * - Server Name: MPE-SocketServer
 * - Creator: XackiGiFF
 * - Version: 1.0
 * - Creation Date: [creation date]
 * - Operating System: [OS information]
 *
 * Usage:
 * - Command 'info' to get information about the server.
 * - Command 'exit' to disconnect the client.
 * - Command 'stop' to stop the server.
 *
 * Running the Server:
 * - Ensure that port 12345 is free and available.
 * - Run this script in the terminal.
 *
 * @author XackiGiFF
 * @version 1.0
 * @date [2025/01/11]
 */


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

while (true) {
    // Waiting clients
    $conn = stream_socket_accept($socket, -1);
    if ($conn === false) {
        echo "Error while get connection handshake.\n";
        continue; //Skip the iteration if the connection could not be accepted.
    }

    // Get IP of client
    $clientInfo = stream_socket_get_name($conn, true);
    echo "New client connected: $clientInfo\n";

    // Processing data from the client
    while ($data = fread($conn, 1024)) {
        $data = trim($data);
        if ($data === 'exit') {
            echo "The client has disconnected.\n";
            break; // Exiting the inner loop with the 'exit' command
        }
        if ($data === 'stop') {
            echo "Close sockets...\n";
            fclose($conn); // Closing the connection with the client before stopping the server
            fclose($socket); // Closing the server socket
            echo "Server was stopped.\n";
            exit; // Completing the script execution
        }
        if ($data === 'info') {
            $nowtime = date("Y-m-d H:i:s");
            $response = "[$serverName] $nowtime > \n - Creator: {$creator}, \n - Ver: {$version}, \n - Date: {$creationDate}, \n - OS: {$os}, \n - RAM: " . round($ramUsage, 2) . " MB, \n - CPU: " . round($cpuUsage, 2) . "%";
            fwrite($conn, $response . "\n"); // Send response to client
        }
        echo "Msg from client: $data\n"; // Print client data to console
    }

    // Close connection
    fclose($conn);
}
