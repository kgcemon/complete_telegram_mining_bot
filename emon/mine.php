<?php
// mine.php

header('Content-Type: application/json');

// Define the directory where user data will be stored
define('USERS_DIR', __DIR__ . '/users');

// Ensure the USERS_DIR exists and is writable
if (!is_dir(USERS_DIR)) {
    if (!mkdir(USERS_DIR, 0755, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Server error: Unable to create users directory.'
        ]);
        exit;
    }
}

// Function to retrieve user data based on Telegram ID
function getUserData($telegram_id) {
    $user_file = USERS_DIR . '/' . $telegram_id . '.json';
    if (file_exists($user_file)) {
        $json_data = file_get_contents($user_file);
        $data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $data;
        } else {
            error_log("JSON decode error for file: " . $user_file);
        }
    }
    return null;
}

// Function to save user data
function saveUserData($telegram_id, $data) {
    $user_file = USERS_DIR . '/' . $telegram_id . '.json';
    $json_data = json_encode($data, JSON_PRETTY_PRINT);

    if ($json_data === false) {
        error_log("JSON encode error for Telegram ID: " . $telegram_id);
        return false;
    }

    if (file_put_contents($user_file, $json_data) === false) {
        error_log("Failed to write to user file: " . $user_file);
        return false;
    }

    // Set file permissions to be readable and writable only by the owner
    chmod($user_file, 0600);
    return true;
}

// Retrieve the Telegram ID from GET parameters
$telegram_id = isset($_GET['telegram_id']) ? trim($_GET['telegram_id']) : null;

// Validate the Telegram ID
if (!$telegram_id || !preg_match('/^\d+$/', $telegram_id)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid Telegram ID.'
    ]);
    exit;
}

// Fetch existing user data or initialize new data
$user_data = getUserData($telegram_id);
if (!$user_data) {
    $user_data = [
        'telegram_id' => $telegram_id,
        'coins' => 0,
        'last_mine' => 0
    ];
}

$current_time = time();
$time_since_last_mine = $current_time - $user_data['last_mine'];
$cooldown = 60; // 1 minute cooldown in seconds

if ($time_since_last_mine < $cooldown) {
    $remaining = $cooldown - $time_since_last_mine;
    echo json_encode([
        'success' => false,
        'message' => 'You must wait ' . $remaining . ' seconds before mining again.'
    ]);
    exit;
}

// Define the range of coins a user can earn per mine
$coins_earned = rand(1, 10); // You can adjust the range as needed

// Update user data
$user_data['coins'] += $coins_earned;
$user_data['last_mine'] = $current_time;

// Save the updated user data
if (saveUserData($telegram_id, $user_data)) {
    echo json_encode([
        'success' => true,
        'message' => 'You have mined ' . $coins_earned . ' coins!',
        'total_coins' => $user_data['coins']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: Unable to save your data.'
    ]);
}
?>
