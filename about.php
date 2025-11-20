<?php
require_once 'db.php';
require_once 'functions.php';
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit();
}


if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>About - uniChat Messaging Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

        @keyframes fadeIn { from { opacity:0 } to { opacity:1 } }
        @keyframes slideUp { from{transform:translateY(20px);opacity:0} to{transform:translateY(0);opacity:1} }

        .animate-fade-in { animation: fadeIn .5s ease-out; }
        .animate-slide-up { animation: slideUp .5s ease-out; }
    </style>
</head>

<body class="bg-gray-100 min-h-screen flex flex-col p-1">

<!-- HEADER -->
<header class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-md rounded-lg">
  <div class="flex items-center justify-between px-4 py-3">

    <!-- LEFT: LOGO -->
    <a href="home.php" class="flex items-center space-x-2 hover:opacity-90 transition">
        <div class="bg-white bg-opacity-20 p-2 rounded-lg">
            <i class="fas fa-bolt text-xl text-white"></i>
        </div>
        <span class="text-xl font-semibold tracking-wide">uniChat</span>
    </a>

    <!-- RIGHT: NAV ICONS -->
    <div class="flex items-center space-x-5 text-xl">
        <a href="profile.php" class="hover:text-gray-200 transition">
            <i class="fas fa-user-circle"></i>
        </a>
        <a href="about.php" class="hover:text-gray-200 transition">
            <i class="fas fa-info-circle"></i>
        </a>
        <a href="?logout=1" class="hover:text-gray-200 transition">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

  </div>
</header>

<div class="flex-1 overflow-auto mt-2 mb-2">
    <div class="container mx-auto px-4 py-8">

        <!-- PAGE TITLE -->
        <div class="text-center mb-10 animate-fade-in">
            <h1 class="text-3xl font-bold text-gray-800 mb-3">About uniChat</h1>
            <p class="text-gray-600 max-w-2xl mx-auto">
                uniChat is a modern, fast and clean messaging platform built for smooth communication, privacy and simplicity.
            </p>
        </div>

        <!-- SINGLE DEVELOPER CARD -->
        <div class="bg-white rounded-xl shadow-lg p-8 animate-slide-up max-w-3xl mx-auto">

            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Meet the Developer</h2>

            <div class="flex flex-col md:flex-row items-center md:items-start gap-8">

                <!-- Developer Image -->
                <div>
                    <img src="https://media.licdn.com/dms/image/v2/D4D03AQGZuArIuZIMvw/profile-displayphoto-shrink_200_200/profile-displayphoto-shrink_200_200/0/1700493332721?e=2147483647&v=beta&t=Pow7RnINoQmNyEzLgbnC9vvpNaOndtvGXgpD08E0FA4"
                         class="w-32 h-32 md:w-40 md:h-40 rounded-full object-cover border-4 border-indigo-200 shadow">
                </div>

                <!-- Developer Info -->
                <div class="flex-1">
                    <h3 class="text-xl font-semibold text-gray-900">Abhishek Goyal</h3>
                    <p class="text-indigo-600 font-medium mb-4">Full Stack Developer</p>

                    <p class="text-gray-700 leading-relaxed mb-4">
                       uniChat is developed and maintained with a strong focus on clean design, reliable performance, and user-centric functionality. The platform’s architecture, interface, and core features are built with attention to detail to ensure a smooth and consistent messaging experience across devices. 
                    </p>

                    <div class="flex space-x-4 mt-4">
                        <a href="https://linkedin.com/in/abhishekgoyal213" target="_blank"
                           class="px-4 py-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-200 transition font-medium flex items-center gap-2">
                            <i class="fab fa-linkedin"></i> LinkedIn
                        </a>

                        <a href="https://github.com/Abhigoyal213" target="_blank"
                           class="px-4 py-2 rounded-lg bg-gray-100 text-gray-800 hover:bg-gray-200 transition font-medium flex items-center gap-2">
                            <i class="fab fa-github"></i> GitHub
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- PROJECT DETAILS -->
        <div class="bg-white rounded-xl shadow-lg p-8 mt-10 animate-fade-in">

            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">About the Project</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="bg-indigo-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-indigo-900 mb-2">Mission</h3>
                    <p class="text-gray-700">
                        To provide a fast, secure and elegant real-time chatting experience with a clean user interface and seamless performance.
                    </p>
                </div>

                <div class="bg-purple-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-purple-900 mb-2">Technology</h3>
                    <p class="text-gray-700">
                        Built using PHP, MySQL, JavaScript, AJAX polling, Tailwind CSS & responsive UI components.
                    </p>
                </div>

                <div class="bg-blue-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-blue-900 mb-2">Features</h3>
                    <ul class="list-disc ml-5 text-gray-700 space-y-1">
                        <li>Real-time messaging</li>
                        <li>Seen & typing indicators</li>
                        <li>Emoji support</li>
                        <li>Clean & modern UI</li>
                        <li>Fully responsive layout</li>
                    </ul>
                </div>

                <div class="bg-green-100 p-6 rounded-lg">
                    <h3 class="text-xl font-semibold text-green-900 mb-2">Future Plans</h3>
                    <p class="text-gray-700">
                        Group chats, chat media sharing, voice messages, and optimized real-time WebSocket support.
                    </p>
                </div>

            </div>
        </div>

    </div>
</div>

<footer class="text-center py-2 text-gray-500 text-xs border-t bg-white rounded-b-lg mx-1 mb-1">
    © 2025 uniChat — Developed by Abhishek Goyal
</footer>

</body>
</html>
