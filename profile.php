<?php
// Include database connection and helper functions
require_once 'db.php';
require_once 'functions.php';

// Check if the user is logged in
if(!isset($_SESSION['user_id'])) {
    // Redirect to the login page if not logged in
    header("Location: index.php");
    exit();
}

// Get the current user's ID and details
$user_id = $_SESSION['user_id'];
$user = get_user($user_id, $conn);
$msg = ''; // Initialize a message variable for feedback

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile image update
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION); // Get the file extension
        $filename = 'profile_'.$user_id.'.'.$ext; // Create a unique filename
        $path = 'uploads/'.$filename; // Set the upload path
        
        // Create the uploads directory if it doesn't exist
        if(!file_exists('uploads')) mkdir('uploads', 0777, true);
        
        // Move the uploaded file to the uploads directory
        if(move_uploaded_file($_FILES['image']['tmp_name'], $path)) {
            // Update the user's profile image in the database
            mysqli_query($conn, "UPDATE users SET profile_image='$path' WHERE id=$user_id");
            $msg = 'Profile image updated!'; // Set a success message
            $user['profile_image'] = $path; // Update the user's profile image in the session
            $_SESSION['profile_image'] = $path;
        }
    }
    
    // Handle profile information update
    if(isset($_POST['update_profile'])) {
        $full_name = mysqli_real_escape_string($conn, trim($_POST['full_name']));
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $github_link = mysqli_real_escape_string($conn, $_POST['github_link']);
        $linkedin_link = mysqli_real_escape_string($conn, $_POST['linkedin_link']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        
        // Validate full name
        if(empty($full_name)) {
            $msg = 'Full name is required!';
        }
        // Check name length (2-50 characters)
        elseif(strlen($full_name) < 2 || strlen($full_name) > 50) {
            $msg = 'Full name must be between 2 and 50 characters!';
        }
        // Check for valid characters (letters, spaces, and basic punctuation)
        elseif(!preg_match('/^[A-Za-z\s\'\-\.]+$/', $full_name)) {
            $msg = 'Full name can only contain letters, spaces, and basic punctuation!';
        }
        // Check for multiple consecutive spaces
        elseif(strpos($full_name, '  ') !== false) {
            $msg = 'Full name cannot contain multiple consecutive spaces!';
        }
        // Validate phone number (basic validation for 10 digits)
        elseif(!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
            $msg = 'Invalid phone number! Please enter 10 digits.';
        }
        // Validate social links
        elseif(!empty($github_link) && !filter_var($github_link, FILTER_VALIDATE_URL)) {
            $msg = 'Invalid GitHub URL!';
        }
        elseif(!empty($linkedin_link) && !filter_var($linkedin_link, FILTER_VALIDATE_URL)) {
            $msg = 'Invalid LinkedIn URL!';
        }
        elseif(!empty($github_link) && !empty($linkedin_link) && $github_link === $linkedin_link) {
            $msg = 'GitHub and LinkedIn URLs cannot be the same!';
        }
        else {
            // Update the user's profile information
            $query = "UPDATE users SET 
                     full_name='$full_name',
                     phone='$phone',
                     email='$email',
                     github_link='$github_link',
                     linkedin_link='$linkedin_link',
                     bio='$bio'
                     WHERE id=$user_id";
            
            if(mysqli_query($conn, $query)) {
                $msg = 'Profile information updated successfully!';
                // Update the user data in the session
                $user = get_user($user_id, $conn);
            } else {
                $msg = 'Error updating profile information!';
            }
        }
    }
    
    // Handle password update
    if(isset($_POST['current_pwd']) && isset($_POST['new_pwd'])) {
        $current = $_POST['current_pwd']; // Get the current password
        $new = $_POST['new_pwd']; // Get the new password
        
        // Verify the current password
        if(password_verify($current, $user['password'])) {
            $hashed = password_hash($new, PASSWORD_DEFAULT); // Hash the new password
            mysqli_query($conn, "UPDATE users SET password='$hashed' WHERE id=$user_id"); // Update the password in the database
            $msg = 'Password updated!'; // Set a success message
        } else {
            $msg = 'Current password incorrect!'; // Set an error message
        }
    }
    
    // Handle account deletion
    if(isset($_POST['delete_account'])) {
        $confirm = $_POST['confirm_delete'];
        $expected_text = 'DELETE/' . $_SESSION['username'];
        if($confirm === $expected_text) {
            // Delete user's profile image if exists
            if(!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                unlink($user['profile_image']);
            }
            
            // Delete user's messages
            mysqli_query($conn, "DELETE FROM messages WHERE sender_id = $user_id OR receiver_id = $user_id");
            
            // Delete user account
            if(mysqli_query($conn, "DELETE FROM users WHERE id = $user_id")) {
                session_destroy();
                header("Location: index.php");
                exit();
            }
        } else {
            $error_msg = 'Please type DELETE/' . $_SESSION['username'] . ' to confirm account deletion';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - uniChat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Core animations for all pages */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); } }
        
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-slide-up { animation: slideUp 0.5s ease-out; }
        .hover-lift { transition: transform 0.2s ease-out; }
        .hover-lift:hover { transform: translateY(-3px); }
        
        .input-focus-effect { transition: all 0.2s ease; }
        .input-focus-effect:focus { transform: scale(1.01); }
        
        .pulse-effect:hover { animation: pulse 2s infinite; }
    </style>
</head>
<body class="bg-gray-50 font-['Plus_Jakarta_Sans']">
    <div class="max-w-3xl mx-auto my-8 bg-white rounded-xl shadow-md overflow-hidden animate-fade-in">
        <div class="flex justify-between items-center p-6 border-b border-gray-100">
            <h1 class="text-2xl font-bold text-gray-800">Profile Settings</h1>
            <a href="home.php" class="text-indigo-600 hover:text-indigo-800 flex items-center hover-lift group">
                <span class="mr-2 group-hover:mr-3 transition-all">Back to Chat</span>
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <?php if(isset($msg) && $msg): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 mx-4 mt-4 animate-slide-up">
                <p><i class="fas fa-check-circle mr-2"></i><?php echo $msg; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error_msg)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mx-4 mt-4 animate-slide-up">
                <p><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_msg; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="p-6">
            <div class="text-center mb-8 animate-slide-up" style="animation-delay: 0.1s">
                <form method="post" enctype="multipart/form-data" id="profile-pic-form">
                    <div class="relative w-28 h-28 mx-auto pulse-effect">
                        <?php if(!empty($user['profile_image'])): ?>
                            <img src="<?php echo $user['profile_image']; ?>" class="w-full h-full rounded-full object-cover border-4 border-indigo-100 hover:border-indigo-300 transition-colors">
                        <?php else: ?>
                            <div class="w-full h-full rounded-full bg-indigo-100 flex items-center justify-center text-3xl font-bold text-indigo-600 hover:bg-indigo-200 transition-colors">
                                <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <label for="profile-image" class="absolute -bottom-1 -right-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full w-9 h-9 flex items-center justify-center cursor-pointer shadow-md hover:shadow-lg transform hover:scale-110 transition-all">
                            <i class="fas fa-camera"></i>
                        </label>
                        <input type="file" name="image" id="profile-image" accept="image/jpeg, image/png" class="hidden" onchange="document.getElementById('profile-pic-form').submit()">
                    </div>
                    <p class="mt-2 text-gray-500">@<?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    <p class="text-sm text-gray-400 mt-1">Upload a new profile picture (JPG, PNG, max 2MB)</p>
                </form>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="animate-slide-up" style="animation-delay: 0.2s">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Personal Information</h2>
                    <form method="post">
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Full Name</label>
                            <input type="text" 
                                name="full_name" 
                                value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" 
                                placeholder="Enter your full name"
                                pattern="^[A-Za-z\s'\-\.]{2,50}$"
                                required
                                oninput="validateName(this)"
                                title="Name must be 2-50 characters long and can only contain letters, spaces, and basic punctuation">
                            <span id="nameError" class="text-red-500 text-sm hidden"></span>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Username</label>
                            <input type="text" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50" value="<?php echo htmlspecialchars($_SESSION['username']); ?>" readonly>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="Enter your phone number">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="Enter your email">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">About Me</label>
                            <textarea name="bio" rows="4" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Social Links</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <i class="fab fa-github text-gray-800 mr-2 w-5"></i>
                                    <input type="url" name="github_link" value="<?php echo htmlspecialchars($user['github_link'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="GitHub profile URL">
                                </div>
                                <div class="flex items-center">
                                    <i class="fab fa-linkedin text-blue-700 mr-2 w-5"></i>
                                    <input type="url" name="linkedin_link" value="<?php echo htmlspecialchars($user['linkedin_link'] ?? ''); ?>" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="LinkedIn profile URL">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-medium transition-transform hover:scale-105 shadow-md hover:shadow-lg transform">Save Changes</button>
                    </form>
                </div>
                
                <div class="animate-slide-up" style="animation-delay: 0.3s">
                    <h2 class="text-xl font-semibold mb-4 text-gray-800">Change Password</h2>
                    <form method="post">
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Current Password</label>
                            <input type="password" name="current_pwd" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="Enter current password">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">New Password</label>
                            <input type="password" name="new_pwd" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="Enter new password">
                        </div>
                        <div class="mb-4">
                            <label class="block mb-1 font-medium text-gray-700">Confirm New Password</label>
                            <input type="password" name="confirm_pwd" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 input-focus-effect" placeholder="Confirm new password">
                        </div>
                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg font-medium transition-transform hover:scale-105 shadow-md hover:shadow-lg transform">Save Changes</button>
                    </form>

                    <div class="border-t border-red-200 pt-4 mt-8">
                        <h3 class="text-lg font-semibold mb-3 text-red-800">Danger Zone</h3>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                            <div class="mb-3">
                                <label class="block mb-2 font-medium text-gray-700">Delete Account</label>
                                <p class="text-sm text-gray-600 mb-2">Once you delete your account, there is no going back. Please be certain.</p>
                                <input type="text" name="confirm_delete" class="w-full px-4 py-2 rounded-lg border border-gray-200 bg-gray-50 focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400" placeholder="Type DELETE/<?php echo $_SESSION['username']; ?> to confirm">
                            </div>
                            <button type="submit" name="delete_account" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg font-medium transition-transform hover:scale-105 shadow-md hover:shadow-lg transform">Delete Account</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="text-center py-4 text-gray-500 text-sm animate-fade-in" style="animation-delay: 0.4s">
        © 2025 uniChat App
    </footer>

    <script>
        function validateName(input) {
            const name = input.value.trim();
            const errorSpan = document.getElementById('nameError');
            
            // Check if empty
            if (!name) {
                errorSpan.textContent = 'Full name is required!';
                errorSpan.classList.remove('hidden');
                return false;
            }
            
            // Check length
            if (name.length < 2 || name.length > 50) {
                errorSpan.textContent = 'Full name must be between 2 and 50 characters!';
                errorSpan.classList.remove('hidden');
                return false;
            }
            
            // Check for valid characters
            if (!/^[A-Za-z\s'\-\.]+$/.test(name)) {
                errorSpan.textContent = 'Full name can only contain letters, spaces, and basic punctuation!';
                errorSpan.classList.remove('hidden');
                return false;
            }
            
            // Check for multiple consecutive spaces
            if (name.includes('  ')) {
                errorSpan.textContent = 'Full name cannot contain multiple consecutive spaces!';
                errorSpan.classList.remove('hidden');
                return false;
            }
            
            // If all validations pass
            errorSpan.classList.add('hidden');
            return true;
        }
    </script>
</body>
</html>
