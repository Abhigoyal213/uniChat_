<?php
session_start();
require_once 'db.php';
require_once 'functions.php';
date_default_timezone_set('Asia/Kolkata');

function format_time_kolkata($datetime_str, $format = 'g:i A') {
    if (empty($datetime_str)) return '';
    try {
        $dt = new DateTime($datetime_str, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone('Asia/Kolkata'));
        return $dt->format($format);
    } catch (Exception $e) {
        return date($format, strtotime($datetime_str));
    }
}

$typing_dir = __DIR__ . '/tmp_typing';
if (!is_dir($typing_dir)) { @mkdir($typing_dir, 0755, true); }

/* Typing handler */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['typing'])) {
    $sender = (int)$_SESSION['user_id'];
    $receiver = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $is_typing = (int)$_POST['typing'];
    $file = $typing_dir . "/typing_{$sender}.json";
    if ($is_typing) { @file_put_contents($file, json_encode(['to'=>$receiver,'ts'=>time()])); }
    else { if (file_exists($file)) @unlink($file); }
    exit;
}

/* Check typing */
if (isset($_GET['check_typing'])) {
    $check_for_user = (int)$_GET['check_typing'];
    $isSomeoneTyping = 0;
    if (is_dir($typing_dir)) {
        $files = glob($typing_dir . "/typing_*.json");
        if ($files !== false) {
            foreach ($files as $f) {
                $content = @file_get_contents($f);
                if ($content) {
                    $data = json_decode($content, true);
                    if (!empty($data['to']) && (int)$data['to'] === $check_for_user) {
                        if (!empty($data['ts']) && (time() - (int)$data['ts'] <= 6) ) { $isSomeoneTyping = 1; break; }
                    }
                }
            }
        }
    }
    echo $isSomeoneTyping ? "1":"0";
    exit;
}

/* Mark seen */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_seen'])) {
    $receiver = (int)$_SESSION['user_id'];
    $sender = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;
    if ($sender > 0) {
        $sql = "UPDATE messages SET seen = 1 WHERE sender_id = ? AND receiver_id = ? AND seen = 0";
        if ($stmt = $conn->prepare($sql)) { $stmt->bind_param("ii",$sender,$receiver); $stmt->execute(); $stmt->close(); }
        else { @$conn->query("UPDATE messages SET seen = 1 WHERE sender_id = ".intval($sender)." AND receiver_id = ".intval($receiver)." AND seen = 0"); }
    }
    exit;
}

/* Logged in check */
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }

/* Data */
$users = get_users($conn);
$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];

/* Logout */
if (isset($_GET['logout'])) { session_destroy(); header("Location: index.php"); exit(); }

/* Selected user / messages */
$selected_user = null; $messages = []; $viewed_user = null;
if (isset($_GET['user'])) {
    $receiver_id = (int)$_GET['user'];
    foreach ($users as $user) { if ($user['id'] == $receiver_id) { $selected_user = $user; break; } }
    if ($selected_user) $messages = get_messages($current_user_id, $receiver_id, $conn);
}

/* View user modal */
if (isset($_GET['view_user'])) {
    $view_user_id = (int)$_GET['view_user'];
    $viewed_user = get_user($view_user_id, $conn);
}

/* Send message */
if (isset($_POST['send_message']) && isset($_POST['receiver_id'])) {
    $receiver_id = (int)$_POST['receiver_id'];
    $message_text = trim($_POST['message'] ?? '');
    if (!empty($message_text) && $receiver_id > 0) {
        send_message($current_user_id, $receiver_id, $message_text, $conn);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') { exit(); }
        header("Location: home.php?user=".$receiver_id); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>uniChat</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; }
    .message-bubble{ transition: all .15s ease; }
    .typing-dots { font-size: 20px; letter-spacing: 6px; display:inline-block; }
    .typing-dots span { opacity: 0.2; display:inline-block; }
    @keyframes blink { 0%{opacity:0.2}50%{opacity:1}100%{opacity:0.2} }
    .dot1 { animation: blink 1s infinite; } .dot2 { animation: blink 1s infinite .2s; } .dot3 { animation: blink 1s infinite .4s; }

    /* Mobile: when body has class mobile-chat-open, hide sidebar and show chat full screen */
    @media (max-width: 767px) {
        body.mobile-chat-open .sidebar { display: none !important; }
        body.mobile-chat-open .chat-area { display: flex !important; width: 100% !important; }
        /* initial chat-area hidden on mobile until user selects it (we toggle with JS) */
        .chat-area { display: none; }
    }
    /* Desktop: show both */
    @media (min-width: 768px) {
        .sidebar { display: block; }
        .chat-area { display: flex; }
    }

    /* small aesthetic */
    .msg-incoming { background: #111827; color: #fff; } /* dark incoming bubble (if you want) */

    /* popup styles for + menu and emoji */
    .extra-options-popup{ position:absolute; bottom:64px; left:12px; background:white; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.12); display:none; z-index:100; overflow:hidden; min-width:180px; }
    .extra-options-popup.active{ display:block; }
    .popup-option{ display:flex; align-items:center; gap:10px; padding:10px 12px; cursor:pointer; color:#374151; }
    .emoji-picker{ position:absolute; bottom:64px; right:12px; background:white; border:1px solid #e5e7eb; border-radius:8px; padding:8px; box-shadow:0 6px 20px rgba(0,0,0,0.08); display:none; z-index:100; max-width:260px; flex-wrap:wrap; }
    .emoji-picker.active{ display:flex; }
    .emoji-item{ cursor:pointer; font-size:20px; padding:6px; }
</style>
</head>
<body class="<?php echo ($selected_user ? '' : ''); ?>">

<!-- HEADER -->
<header class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md rounded-lg">
  <div class="container mx-auto px-4 py-3 flex items-center justify-between">
    <!-- Left: Logo -->
    <div class="flex items-center space-x-3">
        <a href="home.php" id="logo-link" class="flex items-center space-x-2 hover:opacity-90 transition">
            <div class="bg-white bg-opacity-20 p-2 rounded-lg"><i class="fas fa-bolt text-xl text-white"></i></div>
            <span class="text-xl font-semibold tracking-wide">uniChat</span>
        </a>
    </div>

    <!-- Right: desktop icons (removed call/video per request) -->
    <div class="hidden md:flex items-center space-x-4">
        <a href="profile.php" class="flex items-center space-x-2 hover:opacity-90">
            <i class="fas fa-user-circle text-xl"></i>
            <span class="text-sm"><?php echo htmlspecialchars($current_username); ?></span>
        </a>
        <a href="about.php" class="hover:opacity-90"><i class="fas fa-info-circle text-xl"></i></a>
        <a href="?logout=1" class="hover:opacity-90"><i class="fas fa-sign-out-alt text-xl"></i></a>
    </div>

    <!-- Mobile: menu button -->
    <div class="md:hidden flex items-center space-x-2">
        <button id="mobileMenuBtn" class="text-2xl focus:outline-none text-white"><i class="fas fa-bars"></i></button>
        <a href="home.php" class="ml-1">
            <div class="bg-white bg-opacity-20 p-2 rounded-lg"><i class="fas fa-bolt text-xl text-white"></i></div>
        </a>
    </div>
  </div>

  <!-- Mobile dropdown content -->
  <div id="mobileMenu" class="hidden md:hidden px-4 pb-3">
      <div class="flex flex-col space-y-2">
          <a href="profile.php" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">
              <?php if (!empty($_SESSION['profile_image'])): ?><img src="<?php echo $_SESSION['profile_image']; ?>" class="w-8 h-8 rounded-full object-cover"><?php else: ?><div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-white font-bold"><?php echo strtoupper(substr($current_username,0,1)); ?></div><?php endif; ?>
              <span><?php echo htmlspecialchars($current_username); ?></span>
          </a>
          <a href="about.php" class="px-2 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">About</a>
          <a href="?logout=1" class="px-2 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition">Logout</a>
      </div>
  </div>
</header>

<!-- MAIN -->
<div class="flex flex-1 gap-4 mt-2 h-[calc(100vh-96px)] container mx-auto">

    <!-- SIDEBAR -->
    <aside class="sidebar w-full md:w-1/4 bg-white shadow rounded-lg flex flex-col overflow-hidden">

        <!-- SEARCH BAR ONLY (NO HEADER ABOVE) -->
        <div class="p-3 border-b">
            <div class="flex items-center bg-gray-100 rounded-lg px-3 py-2">
                <i class="fas fa-search text-gray-500 mr-2"></i>
                <input id="user-search" 
                       type="text" 
                       placeholder="Search contacts..."
                       class="w-full bg-transparent focus:outline-none text-gray-700">
            </div>
        </div>

        <!-- CONTACT LIST -->
        <div class="flex-1 overflow-y-auto">
            <ul class="users-list">
                <?php foreach ($users as $user): ?>
                <li class="border-b user-item" data-username="<?php echo strtolower($user['username']); ?>">
                    
                    <a href="?user=<?php echo $user['id']; ?>" 
                       class="flex items-center p-3 gap-3 hover:bg-gray-50">

                        <!-- Profile Image -->
                        <?php if (!empty($user['profile_image'])): ?>
                            <img src="<?php echo $user['profile_image']; ?>" 
                                 class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-12 h-12 rounded-full bg-indigo-500 text-white
                                        flex items-center justify-center font-semibold">
                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Username + status -->
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-800 truncate">
                                <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                            </div>

                            <div class="text-sm text-gray-500 truncate">
                                <?php echo htmlspecialchars($user['status'] ?? ''); ?>
                            </div>
                        </div>

                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </aside>

    <!-- CHAT AREA -->
    <main class="chat-area flex-1 flex flex-col bg-gray-50 rounded-lg shadow overflow-hidden">
        <?php if ($selected_user): ?>
        <!-- Mobile back + header -->
        <div class="bg-white border-b px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button id="backToList" class="md:hidden p-2 rounded-full hover:bg-gray-100"><i class="fas fa-arrow-left"></i></button>
                <?php if (!empty($selected_user['profile_image'])): ?>
                    <img src="<?php echo $selected_user['profile_image']; ?>" class="w-10 h-10 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold"><?php echo strtoupper(substr($selected_user['username'],0,1)); ?></div>
                <?php endif; ?>
                <div>
                    <div class="font-semibold"><?php echo !empty($selected_user['full_name']) ? htmlspecialchars($selected_user['full_name']) : htmlspecialchars($selected_user['username']); ?></div>
                    <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($selected_user['username']); ?></div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button title="View profile" onclick="window.location.href='?view_user=<?php echo $selected_user['id']; ?>'" class="p-2 rounded-full hover:bg-gray-100"><i class="fas fa-info-circle"></i></button>
            </div>
        </div>

        <!-- Messages -->
        <div id="messages-container" class="flex-1 p-4 overflow-y-auto">
            <?php if (empty($messages)): ?>
                <div class="text-center text-gray-600 py-20">No messages yet. Say hello 👋</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php
                    $date = '';
                    foreach ($messages as $message):
                        $messageDate = format_time_kolkata($message['created_at'],'Y-m-d');
                        if ($date != $messageDate) {
                            $date = $messageDate;
                            echo '<div class="flex justify-center my-4"><div class="text-xs text-gray-500 bg-white px-3 py-1 rounded-full">';
                            echo format_time_kolkata($message['created_at'],'F j, Y');
                            echo '</div></div>';
                        }
                        $is_my_message = $message['sender_id'] == $current_user_id;
                    ?>
                        <div class="flex <?php echo $is_my_message ? 'justify-end' : 'justify-start'; ?>">
                            <?php if (!$is_my_message): ?>
                                <?php if (!empty($selected_user['profile_image'])): ?>
                                    <img src="<?php echo $selected_user['profile_image']; ?>" class="w-8 h-8 rounded-full mr-2 object-cover">
                                <?php else: ?>
                                    <div class="w-8 h-8 rounded-full bg-indigo-500 text-white mr-2 flex items-center justify-center"><?php echo strtoupper(substr($selected_user['username'],0,1)); ?></div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="message-bubble <?php echo $is_my_message ? 'bg-indigo-600 text-white' : 'bg-white text-gray-800'; ?> px-4 py-2 rounded-2xl max-w-[70%] shadow">
                                <div><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                                <div class="text-xs mt-1 text-right <?php echo $is_my_message ? 'text-indigo-200' : 'text-gray-500'; ?>">
                                    <?php echo format_time_kolkata($message['created_at'],'g:i A'); ?>
                                    <?php if ($is_my_message): $seen = !empty($message['seen']) && $message['seen']==1; ?>
                                        <span class="ml-2 <?php echo $seen ? 'text-blue-400' : 'text-gray-400'; ?>">&#10003;&#10003;</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Input -->
        <div class="bg-white p-4 border-t relative">
            <form id="message-form" method="post" class="flex items-center gap-3">
                <input type="hidden" name="receiver_id" value="<?php echo $selected_user['id']; ?>">

                <!-- PLUS (extra) button -->
                <div class="relative">
                    <button type="button" id="extraOptions" class="p-2 rounded-full hover:bg-gray-100"><i class="fas fa-plus"></i></button>
                    <div id="extraOptionsPopup" class="extra-options-popup" aria-hidden="true">
                        <div id="sendLocation" class="popup-option"><i class="fas fa-map-marker-alt text-red-500"></i><span>Send location</span></div>
                        <!-- Removed send file / image options as requested -->
                    </div>
                </div>

                <div class="flex-1 relative">
                    <input id="message-input" name="message" type="text" autocomplete="off" placeholder="Type a message" class="w-full rounded-full border px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                    <button type="button" id="emoji-button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"><i class="fas fa-smile"></i></button>

                    <!-- Emoji picker (kept separate from + menu) -->
                    <div id="emoji-picker" class="emoji-picker" aria-hidden="true">
                        <!-- A small sample of emojis - extend as you like -->
                        <span class="emoji-item">😊</span>
                        <span class="emoji-item">😂</span>
                        <span class="emoji-item">😍</span>
                        <span class="emoji-item">👍</span>
                        <span class="emoji-item">🙏</span>
                        <span class="emoji-item">🎉</span>
                        <span class="emoji-item">🔥</span>
                        <span class="emoji-item">🤝</span>
                        <span class="emoji-item">👏</span>
                    </div>
                </div>

                <button type="submit" name="send_message" value="1" class="bg-indigo-600 text-white px-4 py-3 rounded-full">Send</button>
            </form>
        </div>

        <?php else: ?>
            <!-- No selected user: placeholder (on desktop both shown, on mobile this is default shown) -->
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <i class="fas fa-bolt text-6xl text-indigo-500 mb-6"></i>
                    <h2 class="text-2xl font-semibold">Welcome to uniChat</h2>
                    <p class="text-gray-600 mt-2">Select a contact to start chatting</p>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<!-- User Details Modal (WhatsApp-style dark) -->
<?php if ($viewed_user): ?>
<div id="userDetailsModal" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50">
  <div class="bg-gray-900 text-white rounded-xl w-full max-w-md mx-4 overflow-hidden shadow-xl">
    <div class="p-6 flex flex-col items-center relative">
      <?php if (!empty($viewed_user['profile_image'])): ?>
        <img src="<?php echo $viewed_user['profile_image']; ?>" class="w-32 h-32 rounded-full object-cover border-4 border-gray-700 mb-3">
      <?php else: ?>
        <div class="w-32 h-32 rounded-full bg-gray-700 text-4xl flex items-center justify-center mb-3"><?php echo strtoupper(substr($viewed_user['username'],0,1)); ?></div>
      <?php endif; ?>
      <button class="absolute top-4 right-4 text-gray-300 hover:text-white"><i class="fas fa-pencil-alt"></i></button>
      <h2 class="text-2xl font-semibold"><?php echo htmlspecialchars($viewed_user['full_name']); ?></h2>
      <p class="text-gray-400">@<?php echo htmlspecialchars($viewed_user['username']); ?></p>
      <?php if (!empty($viewed_user['bio'])): ?><p class="text-gray-300 text-center mt-3 px-6"><?php echo nl2br(htmlspecialchars($viewed_user['bio'])); ?></p><?php endif; ?>
    </div>
    <div class="border-t border-gray-700 p-5 space-y-4 text-gray-200">
      <div><h3 class="text-xs text-gray-400 uppercase mb-1">About</h3><p><?php echo !empty($viewed_user['bio']) ? nl2br(htmlspecialchars($viewed_user['bio'])) : 'No bio available'; ?></p></div>
      <div><h3 class="text-xs text-gray-400 uppercase mb-1">Phone</h3><p><?php echo !empty($viewed_user['phone']) ? htmlspecialchars($viewed_user['phone']) : 'Not provided'; ?></p></div>
      <div><h3 class="text-xs text-gray-400 uppercase mb-1">Email</h3><p><?php echo !empty($viewed_user['email']) ? htmlspecialchars($viewed_user['email']) : 'Not provided'; ?></p></div>
      <div><h3 class="text-xs text-gray-400 uppercase mb-1">Social</h3>
        <div class="flex gap-3"><?php if (!empty($viewed_user['github_link'])): ?><a href="<?php echo $viewed_user['github_link']; ?>" target="_blank" class="p-3 bg-gray-800 rounded-lg"><i class="fab fa-github"></i></a><?php endif; ?><?php if (!empty($viewed_user['linkedin_link'])): ?><a href="<?php echo $viewed_user['linkedin_link']; ?>" target="_blank" class="p-3 bg-gray-800 rounded-lg"><i class="fab fa-linkedin text-blue-400"></i></a><?php endif; ?></div>
      </div>
    </div>
    <div class="p-4 bg-gray-900 flex justify-end">
      <button onclick="document.getElementById('userDetailsModal').remove();" class="px-4 py-2 bg-indigo-600 rounded-lg">Close</button>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- SCRIPTS -->
<script>
/* Mobile menu toggle */
document.getElementById('mobileMenuBtn')?.addEventListener('click', function(){
    document.getElementById('mobileMenu').classList.toggle('hidden');
});

/* Back to list (mobile) */
document.getElementById('backToList')?.addEventListener('click', function(){
    // go to home.php without params to show contact list
    window.location.href = 'home.php';
});

/* Live user search */
document.getElementById('user-search')?.addEventListener('input', function(){
    const term = this.value.toLowerCase();
    document.querySelectorAll('.user-item').forEach(item => {
        const username = (item.dataset.username || '').toLowerCase();
        item.style.display = username.includes(term) ? 'block' : 'none';
    });
});

/* Logo behavior: if already on home.php (or when selected on mobile) scroll chat to bottom */
(function(){
    const logo = document.getElementById('logo-link');
    if (!logo) return;
    logo.addEventListener('click', function(e){
        const p = window.location.pathname.split('/').pop();
        if (p === '' || p === 'home.php') {
            // if on home, try to scroll messages
            const msg = document.getElementById('messages-container');
            if (msg) {
                e.preventDefault();
                msg.scrollTop = msg.scrollHeight;
            }
        }
    });
})();

/* If page loaded with selected user and screen small => show chat exclusively */
(function(){
    const isSelected = <?php echo ($selected_user ? 'true' : 'false'); ?>;
    if (window.innerWidth <= 767 && isSelected) {
        document.body.classList.add('mobile-chat-open');
        // ensure messages scroll to bottom
        setTimeout(()=>{ const m = document.getElementById('messages-container'); if(m) m.scrollTop = m.scrollHeight; },200);
    }
})();

/* Extra small UI: we kept chat JS minimal; re-attach other chat handlers if needed */
/* (Your existing chat JS/polling stays intact below) */
<?php if ($selected_user): ?>
// Polling + typing + send message logic
let messagesContainer = document.getElementById('messages-container');
if (messagesContainer) messagesContainer.scrollTop = messagesContainer.scrollHeight;

const emojiButton = document.getElementById('emoji-button'),
      emojiPicker = document.getElementById('emoji-picker'),
      messageInput = document.getElementById('message-input'),
      messageForm = document.getElementById('message-form');

if (emojiButton && typeof emojiPicker !== 'undefined') {
    emojiButton.addEventListener('click', ()=>{ if (emojiPicker) emojiPicker.classList.toggle('active'); });
    document.addEventListener('click', (e)=>{ if (!emojiButton.contains(e.target) && !(emojiPicker && emojiPicker.contains(e.target))) { emojiPicker && emojiPicker.classList.remove('active'); }});
}

if (document.querySelectorAll('.emoji-item').length > 0 && messageInput) {
    document.querySelectorAll('.emoji-item').forEach(emoji => {
        emoji.addEventListener('click', ()=>{ messageInput.value += emoji.textContent; messageInput.focus(); emojiPicker && emojiPicker.classList.remove('active'); });
    });
}

// Extra options (send location only)
const extraOptionsBtn = document.getElementById('extraOptions');
const extraOptionsPopup = document.getElementById('extraOptionsPopup');
if (extraOptionsBtn && extraOptionsPopup) {
    extraOptionsBtn.addEventListener('click', (e)=>{ e.preventDefault(); extraOptionsPopup.classList.toggle('active'); });
    document.addEventListener('click', (e)=>{ if (!extraOptionsBtn.contains(e.target) && !extraOptionsPopup.contains(e.target)) extraOptionsPopup.classList.remove('active'); });
}

const sendLocationBtn = document.getElementById('sendLocation');
if (sendLocationBtn && messageInput) {
    sendLocationBtn.addEventListener('click', ()=>{
        if (extraOptionsPopup) extraOptionsPopup.classList.remove('active');
        if (navigator.geolocation) {
            messageInput.value = "Loading location..."; messageInput.disabled = true;
            navigator.geolocation.getCurrentPosition(async (position)=>{
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${position.coords.latitude}&lon=${position.coords.longitude}`);
                    const data = await response.json();
                    let locationStr = data.display_name ? `📍 My location: ${data.display_name}` : `📍 My location: ${position.coords.latitude}, ${position.coords.longitude}`;
                    messageInput.value = locationStr; messageInput.disabled = false;
                } catch (err) { messageInput.value = `📍 My location: ${position.coords.latitude}, ${position.coords.longitude}`; messageInput.disabled = false; }
            }, (err)=>{ messageInput.value=''; messageInput.disabled=false; alert('Location blocked or unavailable'); });
        } else { alert('Geolocation not supported'); }
    });
}

// AJAX send
const currentUserId = <?php echo json_encode($current_user_id); ?>;
const receiverId = <?php echo json_encode($selected_user['id']); ?>;
if (messageForm) {
    messageForm.addEventListener('submit', function(e){
        e.preventDefault();
        const text = messageInput.value.trim();
        if (!text) return;
        const fd = new FormData(messageForm); fd.append('send_message','1');
        fetch('home.php?user='+receiverId, { method:'POST', body: fd, headers: {'X-Requested-With':'XMLHttpRequest'} })
            .then(()=>{ messageInput.value=''; messageInput.focus(); checkNewMessages(); })
            .catch(err=>console.error('send err',err));
    });
}

function markSeen() {
    fetch('home.php', { method:'POST', body: new URLSearchParams({ mark_seen:1, sender_id: receiverId }) }).catch(()=>{});
}
markSeen();

let messageCount = <?php echo count($messages); ?>;
function checkNewMessages() {
    fetch('home.php?user='+receiverId, { cache: 'no-store' }).then(r=>r.text()).then(html=>{
        const doc = new DOMParser().parseFromString(html,'text/html');
        const newMsgContainer = doc.getElementById('messages-container');
        if (newMsgContainer) {
            const newMessages = newMsgContainer.querySelectorAll('.message-bubble');
            if (newMessages.length > messageCount) {
                messagesContainer.innerHTML = newMsgContainer.innerHTML;
                messageCount = newMessages.length;
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                markSeen();
            }
        }
    }).catch(()=>{});
}

/* Typing indicator */
let typingTimerClient;
if (messageInput) {
    messageInput.addEventListener('input', ()=>{
        fetch('home.php', { method:'POST', body: new URLSearchParams({ typing:1, receiver_id: receiverId }) }).catch(()=>{});
        if (typingTimerClient) clearTimeout(typingTimerClient);
        typingTimerClient = setTimeout(()=>{ fetch('home.php', { method:'POST', body:new URLSearchParams({ typing:0, receiver_id: receiverId }) }).catch(()=>{}); }, 1000);
    });
}

function checkTyping(){
    fetch('home.php?check_typing=' + currentUserId, { cache: 'no-store' }).then(r=>r.text()).then(status=>{
        const typingBubble = document.getElementById('typing-bubble');
        if (!typingBubble) return;
        if (status.trim()==='1') { typingBubble.style.display='flex'; messagesContainer.scrollTop = messagesContainer.scrollHeight; }
        else { typingBubble.style.display='none'; }
    }).catch(()=>{});
}

setInterval(checkNewMessages, 1000);
setInterval(checkTyping, 700);
<?php endif; ?>
</script>

<footer class="text-center py-2 text-gray-500 text-xs border-t border-gray-200 bg-white rounded-b-lg mx-1 mb-1">
    © 2025 uniChat
</footer>
</body>
</html>
