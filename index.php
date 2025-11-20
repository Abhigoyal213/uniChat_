<?php
// Enable error reporting for debugging purposes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include databases connection and helper functions
require_once 'db.php';
require_once 'functions.php';

// Initialize error message variable
$error = '';

// Handle user registration
if (isset($_POST['register'])) {
    // Sanitize and retrieve form inputs
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Check if fields are empty
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill all fields";
    } 
    // Check if username contains spaces
    elseif (strpos($username, ' ') !== false) {
        $error = "Username cannot contain spaces";
    }
    // Check if passwords match
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    }
    // Password validation
    elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    }
    elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter";
    }
    elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter";
    }
    elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number";
    }
    elseif (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $error = "Password must contain at least one special character";
    }
    else {
        // Attempt to register the user
        if (register_user($username, $password, $conn)) {
            $success = "Registration successful! Please login.";
        } else {
            $error = "Username already exists";
        }
    }
}

// Handle user login
if (isset($_POST['login'])) {
    // Sanitize and retrieve form inputs
    $username = trim(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];
    
    // Check if fields are empty
    if (empty($username) || empty($password)) {
        $error = "Please fill all fields";
    } else {
        // Attempt to log in the user
        if (login_user($username, $password, $conn)) {
            // Redirect to chat page on successful login
            header("Location: chat.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    }
}

// Redirect to chat page if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: chat.php");
    exit();
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
        body{font-family:'Plus Jakarta Sans',sans-serif}
        
        /* Core animations for all pages */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-slide-up { animation: slideUp 0.5s ease-out; }
        
        /* Login-specific animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .float-icon { animation: float 3s ease-in-out infinite; }
        
        .input-focus-effect { transition: all 0.2s ease; }
        .input-focus-effect:focus { transform: scale(1.01); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2); }
        
        .btn-hover-effect { transition: all 0.3s ease; }
        .btn-hover-effect:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2); }
    </style>
</head>
<body class="bg-gradient-to-r from-indigo-100 to-purple-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-xl shadow-lg overflow-hidden animate-fade-in">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-5 text-center">
            <div class="flex items-center justify-center mb-2">
                <i class="fas fa-bolt text-3xl text-white mr-2 float-icon"></i>
                <h1 class="text-2xl font-bold text-white">uniChat</h1>
            </div>
            <p class="text-indigo-100 text-sm">Modern messaging platform</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mx-4 mt-3">
                <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 mx-4 mt-3">
                <p><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="p-0">
            <ul class="flex text-center border-b">
                <li class="flex-1"><a href="#login" id="loginTab" class="block py-3 font-medium">Sign In</a></li>
                <li class="flex-1"><a href="#register" id="registerTab" class="block py-3 font-medium text-gray-500">Register</a></li>
            </ul>
            
            <div class="p-6">
                <div id="loginForm">
                    <form method="post" class="space-y-4" id="loginFormElement">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" id="login-username" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-300" placeholder="Username">
                            <div class="text-red-500 text-xs mt-1 hidden" id="login-username-error"></div>
                        </div>
                        
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"><i class="fas fa-lock"></i></span>
                            <input type="password" name="password" id="login-password" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-300" placeholder="Password">
                            <div class="text-red-500 text-xs mt-1 hidden" id="login-password-error"></div>
                        </div>
                        
                        <button type="submit" name="login" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium flex items-center justify-center">
                            <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                        </button>
                    </form>
                </div>
                
                <div id="registerForm" class="hidden">
                    <form method="post" class="space-y-4" id="registerFormElement">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"><i class="fas fa-user"></i></span>
                            <input type="text" name="username" id="register-username" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-300" placeholder="Choose a username">
                            <div class="text-red-500 text-xs mt-1 hidden" id="register-username-error"></div>
                        </div>
                        
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"><i class="fas fa-lock"></i></span>
                            <input required type="password" name="password" id="register-password" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-300" placeholder="Create a password">
                            <div class="text-red-500 text-xs mt-1 hidden" id="register-password-error"></div>
                        </div>
                        
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500"><i class="fas fa-check-circle"></i></span>
                            <input required type="password" name="confirm_password" id="register-confirm-password" class="w-full pl-10 pr-4 py-3 rounded-lg border border-gray-200 bg-gray-50 focus:ring-2 focus:ring-indigo-300" placeholder="Confirm password">
                            <div class="text-red-500 text-xs mt-1 hidden" id="register-password-match-error"></div>
                        </div>
                        
                        <div class="bg-blue-50 p-3 rounded-lg text-xs text-blue-800 mb-2">
                            <p class="font-medium">Password must contain:</p>
                            <ul class="list-disc pl-4 mt-1 space-y-1">
                                <li id="length-check">At least 6 characters</li>
                                <li id="uppercase-check">At least 1 uppercase letter</li>
                                <li id="lowercase-check">At least 1 lowercase letter</li>
                                <li id="number-check">At least 1 number</li>
                                <li id="special-check">At least 1 special character</li>
                            </ul>
                        </div>
                        
                        <button type="submit" name="register" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-lg font-medium flex items-center justify-center">
                            <i class="fas fa-user-plus mr-2"></i>Create Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const tabs = {login: document.getElementById('loginTab'), register: document.getElementById('registerTab')};
        const forms = {login: document.getElementById('loginForm'), register: document.getElementById('registerForm')};
        
        for(let type in tabs) {
            tabs[type].addEventListener('click', e => {
                e.preventDefault();
                forms.login.classList.toggle('hidden', type !== 'login');
                forms.register.classList.toggle('hidden', type !== 'register');
                
                tabs.login.className = type === 'login' ? 'block py-3 font-medium text-indigo-600 border-b-2 border-indigo-600' : 'block py-3 font-medium text-gray-500';
                tabs.register.className = type === 'register' ? 'block py-3 font-medium text-indigo-600 border-b-2 border-indigo-600' : 'block py-3 font-medium text-gray-500';
            });
        }
        
        tabs.login.className = 'block py-3 font-medium text-indigo-600 border-b-2 border-indigo-600';
        
        // Form validation functions
        function validateUsername(username, errorElement) {
            if (!username.trim()) {
                errorElement.textContent = "Username cannot be empty";
                errorElement.classList.remove('hidden');
                return false;
            } else if (username.includes(' ')) {
                errorElement.textContent = "Username cannot contain spaces";
                errorElement.classList.remove('hidden');
                return false;
            }
            errorElement.classList.add('hidden');
            return true;
        }
        
        function validatePassword(password, errorElement) {
            if (!password) {
                errorElement.textContent = "Password cannot be empty";
                errorElement.classList.remove('hidden');
                return false;
            } else if (password.length < 6) {
                errorElement.textContent = "Password must be at least 6 characters long";
                errorElement.classList.remove('hidden');
                return false;
            } else if (!/[A-Z]/.test(password)) {
                errorElement.textContent = "Password must contain at least one uppercase letter";
                errorElement.classList.remove('hidden');
                return false;
            } else if (!/[a-z]/.test(password)) {
                errorElement.textContent = "Password must contain at least one lowercase letter";
                errorElement.classList.remove('hidden');
                return false;
            } else if (!/[0-9]/.test(password)) {
                errorElement.textContent = "Password must contain at least one number";
                errorElement.classList.remove('hidden');
                return false;
            } else if (!/[^A-Za-z0-9]/.test(password)) {
                errorElement.textContent = "Password must contain at least one special character";
                errorElement.classList.remove('hidden');
                return false;
            }
            errorElement.classList.add('hidden');
            return true;
        }
        
        function passwordsMatch(password, confirmPassword, errorElement) {
            if (password !== confirmPassword) {
                errorElement.textContent = "Passwords do not match";
                errorElement.classList.remove('hidden');
                return false;
            }
            errorElement.classList.add('hidden');
            return true;
        }
        
        // Live password strength indicator
        const registerPassword = document.getElementById('register-password');
        const lengthCheck = document.getElementById('length-check');
        const uppercaseCheck = document.getElementById('uppercase-check');
        const lowercaseCheck = document.getElementById('lowercase-check');
        const numberCheck = document.getElementById('number-check');
        const specialCheck = document.getElementById('special-check');
        
        registerPassword.addEventListener('input', function() {
            const password = this.value;
            
            // Update validation indicators
            lengthCheck.classList.toggle('text-green-600', password.length >= 6);
            uppercaseCheck.classList.toggle('text-green-600', /[A-Z]/.test(password));
            lowercaseCheck.classList.toggle('text-green-600', /[a-z]/.test(password));
            numberCheck.classList.toggle('text-green-600', /[0-9]/.test(password));
            specialCheck.classList.toggle('text-green-600', /[^A-Za-z0-9]/.test(password));
        });
        
        // Form validation
        document.getElementById('loginFormElement').addEventListener('submit', function(e) {
            const username = document.getElementById('login-username').value;
            const password = document.getElementById('login-password').value;
            
            const usernameValid = validateUsername(username, document.getElementById('login-username-error'));
            const passwordValid = password.trim() !== ''; // Simple check for login
            
            if (!usernameValid || !passwordValid) {
                e.preventDefault();
                if (!passwordValid) {
                    document.getElementById('login-password-error').textContent = "Password cannot be empty";
                    document.getElementById('login-password-error').classList.remove('hidden');
                }
            }
        });
        
        document.getElementById('registerFormElement').addEventListener('submit', function(e) {
            const username = document.getElementById('register-username').value;
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;
            
            const usernameValid = validateUsername(username, document.getElementById('register-username-error'));
            const passwordValid = validatePassword(password, document.getElementById('register-password-error'));
            const passwordsMatchValid = passwordsMatch(password, confirmPassword, document.getElementById('register-password-match-error'));
            
            if (!usernameValid || !passwordValid || !passwordsMatchValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
