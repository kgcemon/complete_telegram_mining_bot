<?php
// wallet.php

// Capture the Telegram ID and username from the URL
$telegram_id = isset($_GET['telegram_id']) ? htmlspecialchars($_GET['telegram_id']) : 'Unknown ID';
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : 'Unknown Username';

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
    // Initialize user data if not found
    $user_data = [
        'coins' => 0,
        'level' => 1,
        'tap_count' => 0, // Number of taps
        'last_cooldown_time' => 0, // Timestamp for the last cooldown
    ];
    saveUserData($telegram_id, $user_data);
}

$coins = $user_data['coins'];
$level = $user_data['level'];
$tap_count = $user_data['tap_count'];
$last_cooldown_time = $user_data['last_cooldown_time'];
$coins_needed = 100000; // Coins needed to level up
$current_time = time();

// Define cooldown period (e.g., 1 hour)
$cooldown = 3600; // in seconds
$time_since_last_cooldown = $current_time - $last_cooldown_time;
$can_tap = ($time_since_last_cooldown >= $cooldown);
$time_remaining = $cooldown - $time_since_last_cooldown;

// Check if user is in cooldown due to reaching 250 taps
$is_cooldown = false;
if ($tap_count >= 250) {
    if ($can_tap) {
        // Reset tap count after cooldown
        $tap_count = 0;
        $user_data['tap_count'] = $tap_count;
        saveUserData($telegram_id, $user_data);
    } else {
        $is_cooldown = true;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - Mining Bot Dashboard</title>
    <!-- External Stylesheet -->
    <link rel="stylesheet" href="https://codmshopbd.com/emon/style.css">
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          integrity="sha512-pXVJ7aCKUHOXVYpLeRk3C8GlD+WeHYcR4cc5jEIf6C+6b+l+K4sf7BYKOV0ugFqf71fg9gG1XotAE/0bWl3iIw=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Internal Styles for Bottom Navigation and Adjustments -->
    <style>
        /* Bottom Navigation Bar Styles */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background-color: #1e1e2f; /* Match the original navbar color */
            box-shadow: 0 -1px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
        }

        .bottom-nav .nav-item {
            flex: 1;
            text-align: center;
            color: #fff;
            text-decoration: none;
            font-size: 12px;
            transition: color 0.3s ease, transform 0.3s ease;
            padding: 5px 0;
            position: relative;
        }

        .bottom-nav .nav-item i {
            font-size: 20px;
            display: block;
            margin-bottom: 2px;
        }

        .bottom-nav .nav-item.active,
        .bottom-nav .nav-item:hover {
            color: #ffcc00; /* Highlight color */
        }

        .bottom-nav .nav-item.active i,
        .bottom-nav .nav-item:hover i {
            color: #ffcc00;
        }

        @media (min-width: 600px) {
            .bottom-nav {
                height: 70px;
            }

            .bottom-nav .nav-item {
                font-size: 14px;
            }

            .bottom-nav .nav-item i {
                font-size: 22px;
            }
        }

        /* Ensure content doesn't get hidden behind the navbar */
        .container {
            padding-bottom: 80px; /* Adjust based on navbar height */
        }

        /* Optional: Active Indicator (e.g., border-top) */
        .bottom-nav .nav-item.active {
            border-top: 2px solid #ffcc00;
        }

        /* Optional: Badge Notifications */
        .nav-item .badge {
            position: absolute;
            top: 5px;
            right: 25%;
            background-color: red;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 10px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">Your Wallet</div>
        
        <div class="wallet-info" style="margin: 20px 0;">
            <div class="stat-item">Current Balance<br><b><?php echo number_format($coins); ?> Coins</b></div>
            <!-- You can add more wallet-related stats here -->
        </div>
        
        <!-- Optional: Transaction History -->
        <div class="transaction-history" style="text-align: left; margin-top: 20px;">
            <h3>Transaction History</h3>
            <?php
            // Placeholder for transaction history
            // Since the current user data does not include transactions,
            // this section can be expanded in the future.
            echo "<p>No transactions available.</p>";
            ?>
        </div>
        
        <div class="footer">
            &copy; 2024 Mining Bot. All rights reserved.
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <footer class="bottom-nav">
        <a href="info.php?telegram_id=<?php echo $telegram_id; ?>&username=<?php echo $username; ?>" 
           class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == '/info.php' ? 'active' : ''; ?>" 
           aria-label="Home">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="wallet.php?telegram_id=<?php echo $telegram_id; ?>&username=<?php echo $username; ?>" 
           class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'wallet.php' ? 'active' : ''; ?>" 
           aria-label="Wallet">
            <i class="fas fa-wallet"></i>
            <span>Wallet</span>
            <!-- Example Badge (Uncomment if needed) -->
            <!-- <span class="badge">3</span> -->
        </a>
        <a href="mining.php?telegram_id=<?php echo $telegram_id; ?>&username=<?php echo $username; ?>" 
           class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'mining.php' ? 'active' : ''; ?>" 
           aria-label="Mining">
            <i class="fas fa-database"></i>
            <span>Mining</span>
        </a>
        <a href="packages.php?telegram_id=<?php echo $telegram_id; ?>&username=<?php echo $username; ?>" 
           class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'packages.php' ? 'active' : ''; ?>" 
           aria-label="Packages">
            <i class="fas fa-box-open"></i>
            <span>Packages</span>
        </a>
        <a href="referrals.php?telegram_id=<?php echo $telegram_id; ?>&username=<?php echo $username; ?>" 
           class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'referrals.php' ? 'active' : ''; ?>" 
           aria-label="Referrals">
            <i class="fas fa-user-friends"></i>
            <span>Referrals</span>
        </a>
    </footer>

    <!-- Super Level Animation Container -->
    <div id="superLevelAnimation"></div>

    <!-- Custom Modal -->
    <div id="customModal">
        <div id="customModalContent">
            <h2 id="modalTitle">Notification</h2>
            <p id="modalMessage">This is a custom popup message.</p>
            <button class="close-btn" id="modalCloseBtn">Close</button>
        </div>
    </div>

    <script>
        // Variables and Elements
        let coins = <?php echo $coins; ?>;
        let tapCount = <?php echo $tap_count; ?>;
        const coinsNeeded = <?php echo $coins_needed; ?>;
        const coinDisplay = document.getElementById('coinDisplay');
        const progressBar = document.getElementById('progressBar');
        const character = document.getElementById('character');
        const timerDisplay = document.getElementById('timerDisplay');
        const countdown = document.getElementById('countdown');
        const tapCountDisplay = document.getElementById('tapCountDisplay');

        let isCooldown = <?php echo $is_cooldown ? 'true' : 'false'; ?>;
        const cooldown = <?php echo $cooldown; ?>; // in seconds
        let timeRemaining = <?php echo $is_cooldown ? $time_remaining : 0; ?>;

        const superLevelAnimation = document.getElementById('superLevelAnimation');

        // Custom Modal Elements
        const customModal = document.getElementById('customModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalCloseBtn = document.getElementById('modalCloseBtn');

        // Function to generate confetti particles
        function generateConfetti() {
            const numberOfConfetti = 100;
            for (let i = 0; i < numberOfConfetti; i++) {
                const confetti = document.createElement('div');
                confetti.classList.add('confetti');
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 100%, 50%)`;
                confetti.style.animationDelay = Math.random() * 3 + 's';
                confetti.style.animationDuration = 3 + Math.random() * 2 + 's';
                superLevelAnimation.appendChild(confetti);

                // Remove confetti after animation
                confetti.addEventListener('animationend', () => {
                    confetti.remove();
                });
            }
        }

        // Function to trigger the super level animation
        function triggerSuperLevelAnimation() {
            generateConfetti();
            // Optional: Add more effects or sounds here
        }

        // Function to show the custom modal with a message
        function showModal(title, message) {
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            customModal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent background scroll
        }

        // Function to hide the custom modal
        function hideModal() {
            customModal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Restore background scroll
        }

        // Event listener for closing the modal
        modalCloseBtn.addEventListener('click', hideModal);

        // Close modal when clicking outside the modal content
        window.addEventListener('click', (event) => {
            if (event.target === customModal) {
                hideModal();
            }
        });

        // Function to update the user's data on the server
        function tapCoin() {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    const response = JSON.parse(this.responseText);
                    if (response.success) {
                        coins = response.new_coins;
                        tapCount = response.new_tap_count;
                        coinDisplay.textContent = new Intl.NumberFormat().format(coins);
                        tapCountDisplay.textContent = `Taps: ${tapCount}/250`;
                        const progress = (coins / coinsNeeded) * 100;
                        progressBar.style.width = progress + '%';
                        if (tapCount >= 250) {
                            triggerSuperLevelAnimation(); // Trigger animation
                            startCooldown();
                        }
                    } else {
                        // Replace alert with custom modal
                        showModal('Notice', response.message);
                        if (response.time_remaining) {
                            timeRemaining = response.time_remaining;
                            startCooldown();
                        }
                    }
                }
            };
            xhr.send(`telegram_id=<?php echo $telegram_id; ?>`);
        }

        // Function to start the cooldown timer
        function startCooldown() {
            isCooldown = true;
            timerDisplay.style.display = 'block';
            tapCountDisplay.textContent = `Taps: ${tapCount}/250`;
            updateTimerDisplay();

            const interval = setInterval(() => {
                timeRemaining--;
                if (timeRemaining <= 0) {
                    clearInterval(interval);
                    isCooldown = false;
                    timerDisplay.style.display = 'none';
                    tapCount = 0;
                    tapCountDisplay.textContent = `Taps: ${tapCount}/250`;
                }
                updateTimerDisplay();
            }, 1000);
        }

        // Function to update the countdown timer display
        function updateTimerDisplay() {
            const hours = Math.floor(timeRemaining / 3600);
            const minutes = Math.floor((timeRemaining % 3600) / 60);
            const seconds = timeRemaining % 60;
            countdown.textContent = 
                String(hours).padStart(2, '0') + ':' + 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
        }

        // Initialize the timer if in cooldown
        if (isCooldown) {
            startCooldown();
        }

        // Event listener for the character tap to mine coins
        character.addEventListener('click', () => {
            if (isCooldown) {
                // Replace alert with custom modal
                showModal('Cooldown', 'Mining is on cooldown. Please wait for the timer.');
                return;
            }

            tapCoin();

            // Create and animate the "+1" visual effect
            const plusOne = document.createElement('div');
            plusOne.textContent = '+1';
            plusOne.style.position = 'absolute';
            plusOne.style.fontSize = '20px';
            plusOne.style.color = '#ffd700';
            plusOne.style.top = '50%';
            plusOne.style.left = '50%';
            plusOne.style.transform = 'translate(-50%, -50%)';
            plusOne.style.opacity = '1';
            plusOne.style.transition = 'transform 0.6s, opacity 0.6s';
            character.appendChild(plusOne);

            // Trigger the animation
            setTimeout(() => {
                plusOne.style.transform = 'translate(-50%, -150px) scale(1.5)';
                plusOne.style.opacity = '0';
            }, 10);

            // Remove the element after animation completes
            plusOne.addEventListener('transitionend', () => {
                plusOne.remove();
            });
        });
    </script>
</body>
</html>
