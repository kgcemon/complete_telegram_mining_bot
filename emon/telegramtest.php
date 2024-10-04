<?php
// Telegram Bot API token
$botToken = '7087742437:AAFjTz33XFXn90HfZPD-yeSneCkAD5mP-po';
// Webhook URL
$webhookUrl = 'https://codmshopbd.com/emon/telegramtest.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to log messages
function logMessage($message) {
    file_put_contents('bot_log.txt', date('Y-m-d H:i:s') . ': ' . $message . "\n", FILE_APPEND);
}

// Function to send message to Telegram
function sendTelegramMessage($url, $postFields = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    }
    $response = curl_exec($ch);
    if ($response === false) {
        logMessage('Curl error: ' . curl_error($ch));
    }
    curl_close($ch);
    return $response;
}

// Get updates
$update = json_decode(file_get_contents('php://input'), true);
logMessage('Received update: ' . json_encode($update));

// Check if it's a message
if (isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    
    // Check if it's a /start command
    if (isset($message['text']) && $message['text'] == '/start') {
        // Set webhook
        $setWebhookResult = sendTelegramMessage("https://api.telegram.org/bot$botToken/setWebhook?url=$webhookUrl");
        logMessage('Webhook set result: ' . $setWebhookResult);
        
        $responseText = "Welcome to the User Info Mini App! Click the button below to see your information.";
        
        // Send welcome message with button
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Show User Info', 'callback_data' => 'show_info']
                ]
            ]
        ];
        
        $postFields = [
            'chat_id' => $chatId,
            'text' => $responseText,
            'reply_markup' => $keyboard
        ];
        
        $sendMessageResult = sendTelegramMessage("https://api.telegram.org/bot$botToken/sendMessage", $postFields);
        logMessage('Send message result: ' . $sendMessageResult);
    } elseif (isset($message['contact'])) {
        $phoneNumber = $message['contact']['phone_number'];
        $responseText = "Thank you for sharing your phone number: $phoneNumber";
        $postFields = [
            'chat_id' => $chatId,
            'text' => $responseText
        ];
        sendTelegramMessage("https://api.telegram.org/bot$botToken/sendMessage", $postFields);
    } else {
        // Handle other types of messages if needed
        logMessage('Received non-command message: ' . json_encode($message));
    }
} elseif (isset($update['callback_query'])) {
    $callbackQuery = $update['callback_query'];
    $chatId = $callbackQuery['message']['chat']['id'];
    $messageId = $callbackQuery['message']['message_id'];
    $data = $callbackQuery['data'];

    if ($data == 'show_info') {
        $userId = $callbackQuery['from']['id'];
        $username = $callbackQuery['from']['username'] ?? 'Not set';
        
        $responseText = "User Info:\nUser ID: $userId\nUsername: $username\n\nTo share your phone number, please use the 'Share Phone Number' button below.";
        
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Share Phone Number', 'request_contact' => true]
                ]
            ]
        ];
        
        $postFields = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $responseText,
            'reply_markup' => $keyboard
        ];
        
        $editMessageResult = sendTelegramMessage("https://api.telegram.org/bot$botToken/editMessageText", $postFields);
        logMessage('Edit message result: ' . $editMessageResult);
    }
}

// Simple registration system (you would typically use a database for this)
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $_SESSION['user'] = [
            'username' => $_POST['username'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
        ];
        echo "Registration successful!";
    }
}

// HTML form for registration
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Registration</title>
</head>
<body>
    <h2>User Registration</h2>
    <form method="post">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>
        <input type="submit" value="Register">
    </form>
</body>
</html>