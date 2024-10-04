<?php
// bot.php

// IMPORTANT: Do NOT expose your bot token publicly.
define('BOT_TOKEN', '7087742437:AAFjTz33XFXn90HfZPD-yeSneCkAD5mP-po');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Define paths
define('USERS_DIR', __DIR__ . '/users');
define('LOGS_DIR', __DIR__ . '/logs');
define('ERROR_LOG_FILE', LOGS_DIR . '/error.log');

// Initialize error logging
setupLogging();

// Ensure required directories exist
initializeDirectories();

/**
 * Sets up error logging by creating the logs directory and error.log file if they don't exist.
 */
function setupLogging() {
    if (!is_dir(LOGS_DIR) && !mkdir(LOGS_DIR, 0755, true)) {
        error_log("Failed to create logs directory: " . LOGS_DIR);
        exit;
    }

    if (!file_exists(ERROR_LOG_FILE) && !touch(ERROR_LOG_FILE)) {
        error_log("Failed to create error log file: " . ERROR_LOG_FILE);
        exit;
    }

    chmod(ERROR_LOG_FILE, 0600);

    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ERROR_LOG_FILE);
    error_reporting(E_ALL);
}

/**
 * Initializes required directories by creating them if they don't exist.
 */
function initializeDirectories() {
    if (!is_dir(USERS_DIR) && !mkdir(USERS_DIR, 0755, true)) {
        error_log("Failed to create users directory: " . USERS_DIR);
        exit;
    }
}

/**
 * Send a message to the user.
 */
function sendMessage($chat_id, $text, $reply_markup = null) {
    $url = API_URL . "sendMessage";

    $post_fields = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];

    if ($reply_markup) {
        $post_fields['reply_markup'] = json_encode($reply_markup);
    }

    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url); 
    curl_setopt($ch, CURLOPT_POST, 1); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 

    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log('Curl error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    return true;
}

/**
 * Create or update a user's JSON file.
 */
function saveUserData($telegram_id, $username) {
    $user_file = USERS_DIR . '/' . $telegram_id . '.json';

    $user_data = [
        'telegram_id' => $telegram_id,
        'username' => $username,
        'registration_date' => date('Y-m-d H:i:s')
    ];

    $json_data = json_encode($user_data, JSON_PRETTY_PRINT);

    if (file_put_contents($user_file, $json_data) === false) {
        error_log("Failed to write to user file: " . $user_file);
        return false;
    }

    chmod($user_file, 0600);
    return true;
}

// Read incoming update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    error_log("Invalid update received");
    exit;
}

// Extract message details
$message = isset($update['message']) ? $update['message'] : null;

if ($message) {
    $chat_id = $message['chat']['id'];
    $telegram_id = $message['from']['id'];
    $username = isset($message['from']['username']) ? $message['from']['username'] : 'No Username';

    if (isset($message['text'])) {
        $text = trim($message['text']);

        if ($text === '/start') {
            // Send Welcome Message with Web App Button
            $keyboard = [
                'inline_keyboard' => [
                    [['text' => 'START', 'web_app' => ['url' => "https://codmshopbd.com/emon/info.php?telegram_id={$telegram_id}&username={$username}"]]]
                ]
            ];

            $welcome_message = "Welcome {$username} To Our Testing Bot. Please Click Start to Open the Web App.";
            
            if (!sendMessage($chat_id, $welcome_message, $keyboard)) {
                error_log("Failed to send welcome message to user: {$telegram_id}");
            }
        } else {
            // Handle other text messages
            $info_message = "Please use the /start command to begin.";
            
            if (!sendMessage($chat_id, $info_message)) {
                error_log("Failed to send info message to user: {$telegram_id}");
            }
        }
    }
}
?>
