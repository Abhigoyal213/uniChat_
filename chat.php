<?php
// Include database connection and helper functions
require_once 'db.php';
require_once 'functions.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: index.php");
    exit();
}
// Fetch all users except the current user
$users = get_users($conn);
$current_user_id = $_SESSION['user_id']; // Current logged-in user's ID
$current_username = $_SESSION['username']; // Current logged-in user's username
// Handle logout functionality
if (isset($_GET['logout'])) {
    // Destroy the session and redirect to the login page
    session_destroy();
    header("Location: index.php");
    exit();
}
// Initialize variables for selected user and messages
$selected_user = null;
$messages = [];
$viewed_user = null;
// Check if a user is selected for chat
if (isset($_GET['user'])) {
    $receiver_id = (int)$_GET['user']; // Get the selected user's ID
    foreach ($users as $user) {
        if ($user['id'] == $receiver_id) {
            $selected_user = $user; // Set the selected user
            break;
        }
    }
    // Fetch messages between the current user and the selected user
    if ($selected_user) {
        $messages = get_messages($current_user_id, $receiver_id, $conn);
    }
}
// Handle viewing user details
if (isset($_GET['view_user'])) {
    $view_user_id = (int)$_GET['view_user'];
    $viewed_user = get_user($view_user_id, $conn);
}
// Handle sending a new message
if (isset($_POST['send_message']) && $selected_user) {
    $message_text = trim($_POST['message']); // Get the message text
    $receiver_id = (int)$_POST['receiver_id']; // Get the receiver's ID
    if (!empty($message_text)) {
        // Send the message
        send_message($current_user_id, $receiver_id, $message_text, $conn);
        // If it's an AJAX request, just respond with success
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            exit();
        }
        // Otherwise redirect to avoid form resubmission
        header("Location: chat.php?user=" . $receiver_id);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>uniChat - Messaging Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif
        }

        .emoji-picker {
            position: absolute;
            bottom: 45px;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: none
        }

        .emoji-picker.active {
            display: flex
        }

        .emoji-item {
            cursor: pointer;
            font-size: 20px;
            padding: 5px
        }

        /* Extra options popup styling */
        .extra-options-btn {
            padding: 10px;
            background: none;
            border: none;
            cursor: pointer;
            color: #6366f1;
            transition: all 0.2s;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .extra-options-btn:hover {
            background: #f3f4f6
        }

        .extra-options-popup {
            position: fixed;
            bottom: 120px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            display: none;
            z-index: 100;
            overflow: hidden;
            min-width: 150px
        }

        .extra-options-popup.active {
            display: block;
            animation: fadeIn 0.2s ease-out
        }

        .popup-option {
            display: flex;
            align-items: center;
            width: 100%;
            text-align: left;
            padding: 10px 15px;
            background: none;
            border: none;
            cursor: pointer;
            color: #4b5563;
            transition: all 0.2s
        }

        .popup-option:hover {
            background: #f3f4f6
        }

        .popup-option i {
            margin-right: 8px;
            width: 16px
        }

        /* Core animations for all pages */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(99, 102, 241, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(99, 102, 241, 0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }

        .hover-lift {
            transition: transform 0.2s ease-out;
        }

        .hover-lift:hover {
            transform: translateY(-3px);
        }

        /* Chat specific animations */
        .message-bubble {
            transition: all 0.2s ease;
        }

        .message-bubble:hover {
            transform: scale(1.02);
        }

        .user-item {
            transition: all 0.2s ease;
        }

        /* Message send animation */
        @keyframes sendMessage {
            0% {
                transform: translateY(20px);
                opacity: 0;
            }

            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .message-send {
            animation: sendMessage 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100 h-screen flex flex-col p-1">
    <header class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md animate-fade-in rounded-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <!-- Logo section - added more left padding -->
                <div class="flex items-center space-x-1 pl-2">
                    <div class="bg-white bg-opacity-20 p-2 rounded-lg">
                        <i class="fas fa-bolt text-xl text-white"></i>
                    </div>
                    <h1 class="text-xl font-bold ml-2 pl-2">uniChat</h1>
                </div>

                <!-- Navigation section - added more right padding -->
                <div class="flex items-center space-x-4 pr-2">
                    <!-- Profile with image -->
                    <a href="profile.php" class="flex items-center hover:bg-white hover:bg-opacity-10 px-3 py-2 rounded-lg transition-all duration-300 group">
                        <div class="relative mr-3">
                            <?php if (!empty($_SESSION['profile_image'])): ?>
                                <img src="<?php echo $_SESSION['profile_image']; ?>" class="w-8 h-8 rounded-full object-cover border-2 border-white border-opacity-30 group-hover:border-opacity-70 transition-all">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>

                    <!-- About link -->
                    <a href="about.php" class="flex items-center hover:bg-white hover:bg-opacity-10 px-3 py-2 rounded-lg transition-all duration-300 group">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span>About</span>
                    </a>

                    <!-- Logout button with hover effect -->
                    <a href="?logout=1" class="group flex items-center bg-white bg-opacity-0 hover:bg-opacity-10 px-3 py-2 rounded-lg transition-all">
                        <i class="fas fa-sign-out-alt mr-2 group-hover:translate-x-1 transition-transform"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <div class="flex flex-1 overflow-hidden mt-1 mb-1 rounded-lg">
        <!-- User list sidebar -->
        <div class="w-1/4 bg-white shadow overflow-y-auto rounded-lg">
            <div class="p-3 border-b">
                <h2 class="font-semibold text-gray-700 px-2"><i class="fas fa-users mr-2 text-indigo-500"></i>Contacts</h2>
                <div class="mt-2 relative px-2">
                    <span class="absolute inset-y-0 left-0 pl-5 flex items-center text-gray-500"><i class="fas fa-search"></i></span>
                    <input type="text" id="user-search" placeholder="Search..." class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="flex-1 overflow-y-auto">
                <ul class="users-list w-full">
                    <?php foreach ($users as $user): ?>
                        <li class="user-item w-full" data-username="<?php echo strtolower($user['username']); ?>">
                            <div class="flex items-center p-3 border-b hover:bg-indigo-50 rounded-lg my-1 <?php echo $selected_user && $selected_user['id'] == $user['id'] ? 'bg-indigo-50' : ''; ?>">
                                <a href="?user=<?php echo $user['id']; ?>" class="flex-1 flex items-center min-w-0">
                                    <?php if (!empty($user['profile_image'])): ?>
                                        <img src="<?php echo $user['profile_image']; ?>" class="w-10 h-10 rounded-full mr-3 object-cover flex-shrink-0">
                                    <?php else: ?>
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold mr-3 flex-shrink-0">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="truncate">
                                        <?php 
                                        if (!empty($user['full_name'])) {
                                            // If full name exists, show the first name (part before space)
                                            $nameParts = explode(' ', $user['full_name'], 2);
                                            echo htmlspecialchars($nameParts[0]);
                                        } else {
                                            // If no full name, show first part of username (before space)
                                            $usernameParts = explode(' ', $user['username'], 2);
                                            echo htmlspecialchars($usernameParts[0]);
                                        }
                                        ?>
                                    </div>
                                </a>
                                <button onclick="window.location.href='?view_user=<?php echo $user['id']; ?>'" class="p-2 text-gray-400 hover:text-indigo-600 flex-shrink-0">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <!-- Chat area -->
        <div class="flex-1 flex flex-col">
            <?php if ($selected_user): ?>
                <div class="bg-white p-3 border-b flex items-center justify-between rounded-t-lg px-4">
                    <div class="flex items-center">
                        <?php if (!empty($selected_user['profile_image'])): ?>
                            <img src="<?php echo $selected_user['profile_image']; ?>" class="w-10 h-10 rounded-full mr-3 object-cover">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold mr-3">
                                <?php echo strtoupper(substr($selected_user['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="font-semibold">
                                <?php echo !empty($selected_user['full_name']) ? htmlspecialchars($selected_user['full_name']) : htmlspecialchars($selected_user['username']); ?>
                            </h2>
                            <div class="text-xs text-gray-500">
                                (@<?php echo htmlspecialchars($selected_user['username']); ?>)
                            </div>
                        </div>
                    </div>
                    <button id="clearChatBtn" class="px-3 py-1 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors text-sm flex items-center">
                        <i class="fas fa-trash-alt mr-1"></i> Clear Chat
                    </button>
                </div>
                <div class="flex-1 p-4 px-5 overflow-y-auto bg-gray-50" id="messages-container">
                    <?php if (empty($messages)): ?>
                        <div class="text-center py-20 cursor-pointer hover:opacity-80 transition-opacity" onclick="window.location.reload();" title="Click to refresh">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 text-indigo-500 mb-4 hover:bg-indigo-200 transition-colors">
                                <i class="fas fa-comment-dots text-2xl"></i>
                            </div>
                            <p class="text-gray-600">No messages yet. Start chatting!</p>
                            <p class="text-xs text-indigo-400 mt-2"><i class="fas fa-sync-alt mr-1"></i>Click to refresh</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php
                            $date = '';
                            foreach ($messages as $message):
                                $messageDate = date('Y-m-d', strtotime($message['created_at']));
                                if ($date != $messageDate) {
                                    $date = $messageDate;
                                    echo '<div class="flex justify-center my-4"><div class="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">';
                                    echo date('F j, Y', strtotime($message['created_at']));
                                    echo '</div></div>';
                                }
                                $is_my_message = $message['sender_id'] == $current_user_id;
                            ?>
                                <div class="flex <?php echo $is_my_message ? 'justify-end' : 'justify-start'; ?>" data-message-id="<?php echo $message['id']; ?>">
                                    <?php if (!$is_my_message): ?>
                                        <?php if (!empty($selected_user['profile_image'])): ?>
                                            <img src="<?php echo $selected_user['profile_image']; ?>" class="w-8 h-8 rounded-full mr-2 self-end object-cover">
                                        <?php else: ?>
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold mr-2 self-end">
                                                <?php echo strtoupper(substr($selected_user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="message-bubble <?php echo $is_my_message ? 'bg-indigo-600 text-white' : 'bg-blue-100 text-gray-800'; ?> rounded-2xl px-4 py-2 mb-2 max-w-xs shadow-sm">
                                        <p><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        <p class="text-xs mt-1 text-right <?php echo $is_my_message ? 'text-indigo-200' : 'text-gray-500'; ?>">
                                            <?php echo date('g:i A', strtotime($message['created_at'])); ?>

                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="bg-white p-4 px-5 border-t rounded-b-lg">
                    <form method="post" class="flex items-center gap-3">
                        <button type="button" id="extraOptions" class="extra-options-btn">
                            <i class="fas fa-plus"></i>
                        </button>
                        <div id="extraOptionsPopup" class="extra-options-popup">
                            <button type="button" id="sendLocation" class="popup-option">
                                <i class="fas fa-map-marker-alt"></i> Send Location
                            </button>
                            <!-- Add more options here -->
                        </div>
                        <input type="hidden" name="receiver_id" value="<?php echo $selected_user['id']; ?>">
                        <div class="flex-1 relative">
                            <input type="text" name="message" id="message-input" placeholder="Type a message..."
                                class="w-full pl-4 pr-10 py-3 border border-gray-200 rounded-full bg-gray-50 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 focus:outline-none transition-all" autocomplete="off">
                            <button type="button" id="emoji-button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-smile"></i>
                            </button>
                            <div class="emoji-picker" id="emoji-picker">
                                <span class="emoji-item">😊</span><span class="emoji-item">👍</span><span class="emoji-item">❤️</span>
                                <span class="emoji-item">😂</span><span class="emoji-item">🎉</span><span class="emoji-item">👋</span>
                                <span class="emoji-item">🙏</span><span class="emoji-item">🔥</span><span class="emoji-item">😍</span>
                            </div>
                        </div>
                        <button type="submit" name="send_message" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-3 rounded-full font-medium transition-all hover:shadow-lg flex items-center">
                            <span class="mr-2">Send</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="flex-1 flex items-center justify-center bg-gray-50 rounded-lg">
                    <div class="text-center p-6 bg-white rounded-xl shadow-lg mx-4">
                        <i class="fas fa-bolt text-5xl text-indigo-500 mb-4"></i>
                        <h2 class="text-2xl font-bold mb-2">Welcome to uniChat</h2>
                        <p class="text-gray-600 mb-4">Select a contact to start messaging</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- User Details Modal -->
    <?php if ($viewed_user): ?>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" id="userDetailsModal">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 animate-slide-up">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">User Details</h2>
                <button onclick="document.getElementById('userDetailsModal').remove();" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="text-center mb-6">
                <?php if (!empty($viewed_user['profile_image'])): ?>
                    <img src="<?php echo $viewed_user['profile_image']; ?>" alt="<?php echo $viewed_user['username']; ?>" class="w-24 h-24 rounded-full object-cover mx-auto mb-2">
                <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-indigo-100 flex items-center justify-center text-3xl font-bold text-indigo-600 mx-auto mb-2">
                        <?php echo strtoupper(substr($viewed_user['username'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <h3 class="text-lg font-medium text-gray-900">@<?php echo $viewed_user['username']; ?></h3>
            </div>

            <div class="space-y-4">
                <?php if (!empty($viewed_user['full_name'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($viewed_user['full_name']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($viewed_user['phone'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Phone</label>
                        <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($viewed_user['phone']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($viewed_user['email'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($viewed_user['email']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($viewed_user['bio'])): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">About</label>
                        <p class="mt-1 text-gray-900"><?php echo htmlspecialchars($viewed_user['bio']); ?></p>
                    </div>
                <?php endif; ?>

                <div class="flex space-x-4">
                    <?php if (!empty($viewed_user['github_link'])): ?>
                        <a href="<?php echo $viewed_user['github_link']; ?>" target="_blank" class="text-gray-600 hover:text-gray-900">
                            <i class="fab fa-github text-2xl"></i>
                        </a>
                    <?php endif; ?>

                    <?php if (!empty($viewed_user['linkedin_link'])): ?>
                        <a href="<?php echo $viewed_user['linkedin_link']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                            <i class="fab fa-linkedin text-2xl"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-6 flex justify-end">
                <a href="?user=<?php echo $viewed_user['id']; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                    Message
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script>
        // Search functionality - always available regardless of chat selection
        document.getElementById('user-search').addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.user-item').forEach(item => {
                if (item.dataset.username && term) {
                    item.style.display = item.dataset.username.includes(term) ? 'block' : 'none';
                } else {
                    item.style.display = 'block';
                }
            });
        });
        <?php if ($selected_user): ?>
        // Chat related functionality - only when a chat is selected
        const messagesContainer = document.getElementById('messages-container');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        const emojiButton = document.getElementById('emoji-button'),
            emojiPicker = document.getElementById('emoji-picker'),
            messageInput = document.getElementById('message-input');

        if (emojiButton && emojiPicker) {
            emojiButton.addEventListener('click', () => emojiPicker.classList.toggle('active'));
            
            document.addEventListener('click', (e) => {
                if (!emojiButton.contains(e.target) && !emojiPicker.contains(e.target)) {
                    emojiPicker.classList.remove('active');
                }
            });
        }
        if (document.querySelectorAll('.emoji-item').length > 0 && messageInput) {
            document.querySelectorAll('.emoji-item').forEach(emoji => {
                emoji.addEventListener('click', () => {
                    messageInput.value += emoji.textContent;
                    messageInput.focus();
                    emojiPicker.classList.remove('active');
                });
            });
        }
        // Extra options popup functionality
        const extraOptionsBtn = document.getElementById('extraOptions');
        const extraOptionsPopup = document.getElementById('extraOptionsPopup');
        if (extraOptionsBtn && extraOptionsPopup) {
            extraOptionsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                extraOptionsPopup.classList.toggle('active');
            });

            // Hide popup when clicking outside
            document.addEventListener('click', (e) => {
                if (!extraOptionsBtn.contains(e.target) && !extraOptionsPopup.contains(e.target)) {
                    extraOptionsPopup.classList.remove('active');
                }
            });
        }
        // Location sending functionality
        const sendLocationBtn = document.getElementById('sendLocation');
        const messageForm = document.querySelector('form');
        if (sendLocationBtn && messageInput) {
            sendLocationBtn.addEventListener('click', () => {
                if (extraOptionsPopup) {
                    extraOptionsPopup.classList.remove('active');
                }

                if (navigator.geolocation) {
                    // Show loading state
                    messageInput.value = "Loading location...";
                    messageInput.disabled = true;

                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                                try {
                                    // Get human-readable address from coordinates
                                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`);
                                    const data = await response.json();
                                    let locationStr = '';

                                    if (data.display_name) {
                                        locationStr = `📍 My location: ${data.display_name}`;
                                    } else {
                                        locationStr = `📍 My location: ${position.coords.latitude}, ${position.coords.longitude}`;
                                    }

                                    // Set the location in the input field
                                    messageInput.value = locationStr;
                                    messageInput.disabled = false;

                                } catch (error) {
                                    messageInput.value = `📍 My location: ${position.coords.latitude}, ${position.coords.longitude}`;
                                    messageInput.disabled = false;
                                }
                            },
                            (error) => {
                                // Handle errors
                                messageInput.value = "";
                                messageInput.disabled = false;
                                alert("Unable to retrieve your location. Please check your permissions.");
                                console.error("Geolocation error:", error);
                            }
                    );
                } else {
                    alert("Geolocation is not supported by this browser.");
                }
            });
        }
        // Define variables needed for chat functionality
        const currentUserId = <?php echo $current_user_id; ?>;
        const receiverId = <?php echo $selected_user['id']; ?>;
        // Handle form submission without page refresh
        if (messageForm) {
            messageForm.addEventListener('submit', function(e) {
                // Do not prevent default form submission
                // Let the form submit naturally to refresh the page
                // This removes the AJAX functionality
                const messageText = messageInput.value.trim();
                if (!messageText) {
                    e.preventDefault(); // Only prevent if empty message
                    return;
                }
            });
        }
        // Clear Chat functionality
        const clearChatBtn = document.getElementById('clearChatBtn');
        if (clearChatBtn) {
            clearChatBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to clear this chat? This action cannot be undone.')) {
                    // Redirect to a server-side clear chat function with a return URL
                    window.location.href = 'functions.php?action=clear_chat&user_id=' + currentUserId + 
                        '&receiver_id=' + receiverId + '&return_to=chat.php?user=' + receiverId;
                }
            });
        }
        // Check for new messages
        let messageCount = <?php echo count($messages); ?>;
        function checkNewMessages() {
            fetch('chat.php?user=' + receiverId)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    // Get all messages from the returned HTML
                    const newMsgContainer = doc.getElementById('messages-container');
                    if (newMsgContainer) {
                        // Count messages in fetched content
                        const newMessages = newMsgContainer.querySelectorAll('.message-bubble');
                        // If there are new messages, update the container
                        if (newMessages.length > messageCount) {
                            messagesContainer.innerHTML = newMsgContainer.innerHTML;
                            messageCount = newMessages.length;
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                    }
                });
        }
        // Check for new messages every second
        setInterval(checkNewMessages, 1000);
        <?php endif; ?>
    </script>
    <!-- Footer -->
    <footer class="text-center py-2 text-gray-500 text-xs border-t border-gray-200 bg-white rounded-b-lg mx-1 mb-1">
        © 2025 uniChat
    </footer>
</body>
</html>