<?php
session_start();

function set_message($type, $text) {
    $_SESSION['message'] = ['type' => $type, 'text' => $text];
}

function display_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        echo "<p class=\"message {$message['type']}\">" . htmlspecialchars($message['text']) . "</p>";
        unset($_SESSION['message']);
    }
}

function redirect($location) {
    header("Location: $location");
    exit();
}