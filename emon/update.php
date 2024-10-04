<?php
// update.php

// Set headers to allow CORS if needed and specify JSON response
header('Content-Type: application/json');

// Capture POST data
$telegram_id = isset($_POST['telegram_id']) ? htmlspecialchars($_POST['telegram_id']) : '';

if (empty($telegram_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

// Define the path to user data
define('USERS_DIR', __DIR__ . '/users');

// Function to retrieve user data from a JSON file
function getUserData($telegram_id) {
    $user_file = USERS_DIR . '/' . $telegram_id . '.json';
    if (file_exists($user_file)) {
        $json_data = file_get_contents($user_file);
        return json_decode($json_data, true);
    }
    return null;
}

// Function to save user data to a JSON file
function saveUserData($telegram_id, $data) {
    $user_file = USERS_DIR . '/' . $telegram_id . '.json';
    file_put_contents($user_file, json_encode($data, JSON_PRETTY_PRINT));
}

// Retrieve user data
$user_data = getUserData($telegram_id);
if (!$user_data) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$current_time = time();
$cooldown = 3600; // 1 hour in seconds
$last_cooldown_time = $user_data['last_cooldown_time'];
$time_since_last_cooldown = $current_time - $last_cooldown_time;

// Check if user is currently in cooldown
$in_cooldown = false;
if ($user_data['tap_count'] >= 250) {
    if ($time_since_last_cooldown < $cooldown) {
        $in_cooldown = true;
        $time_remaining = $cooldown - $time_since_last_cooldown;
        echo json_encode([
            'success' => false,
            'message' => 'You have reached the maximum number of taps. Please wait for the cooldown.',
            'time_remaining' => $time_remaining
        ]);
        exit;
    } else {
        // Reset tap count after cooldown
        $user_data['tap_count'] = 0;
    }
}

// Increment coins and tap count
$user_data['coins'] += 1;
$user_data['tap_count'] += 1;

// Check if tap count has reached 250 to initiate cooldown
if ($user_data['tap_count'] >= 250) {
    $user_data['last_cooldown_time'] = $current_time;
}

// Save updated user data
saveUserData($telegram_id, $user_data);

// Respond with success and updated data
echo json_encode([
    'success' => true,
    'message' => 'Tap successful!',
    'new_coins' => $user_data['coins'],
    'new_tap_count' => $user_data['tap_count']
]);
exit;
?>
