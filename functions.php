<?php
// Start a session to manage user authentication
session_start();
// Function to register a new user
function register_user($username, $password, $conn) {
    // Sanitize the username and hash the password
    $username = mysqli_real_escape_string($conn, $username);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    // Insert the new user into the database
    $query = "INSERT INTO users (username, password) VALUES ('$username', '$hashed_password')";
    // Return true if the query is successful, otherwise false
    if (mysqli_query($conn, $query)) {
        return true;
    }
    return false;
}
// Function to log in a user
function login_user($username, $password, $conn) {
    // Query the database for the user with the given username
    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);
    // Check if the user exists
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Store user details in the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_image'] = $user['profile_image'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['github_link'] = $user['github_link'];
            $_SESSION['linkedin_link'] = $user['linkedin_link'];
            $_SESSION['bio'] = $user['bio'];
            return true;
        }
    }
    return false;
}
// Function to get all users except the current user
function get_users($conn) {
    $current_user = $_SESSION['user_id']; // Current logged-in user's ID
    $query = "SELECT id, username, profile_image, full_name FROM users WHERE id != $current_user";
    $result = mysqli_query($conn, $query);
    // Fetch all users and return them as an array
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }
    return $users;
}
// Function to get messages between two users
function get_messages($sender_id, $receiver_id, $conn) {
    // Query the database for messages between the sender and receiver
    $query = "SELECT * FROM messages 
              WHERE (sender_id = $sender_id AND receiver_id = $receiver_id) 
              OR (sender_id = $receiver_id AND receiver_id = $sender_id) 
              ORDER BY created_at ASC";
    $result = mysqli_query($conn, $query);
    // Fetch all messages and return them as an array
    $messages = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $messages[] = $row;
    }
    return $messages;
}
// Function to send a message
function send_message($sender_id, $receiver_id, $message, $conn) {
    // Sanitize the message text
    $message = mysqli_real_escape_string($conn, $message);
    // Insert the message into the database
    $query = "INSERT INTO messages (sender_id, receiver_id, message) 
              VALUES ($sender_id, $receiver_id, '$message')";
    // Return true if the query is successful, otherwise false
    return mysqli_query($conn, $query);
}
// Function to get a user's details by their ID
function get_user($user_id, $conn) {
    $user_id = (int)$user_id; // Ensure the user ID is an integer
    $result = mysqli_query($conn, "SELECT id, username, password, profile_image, full_name, phone, email, github_link, linkedin_link, bio FROM users WHERE id = $user_id");
    return mysqli_fetch_assoc($result); // Return the user's details as an associative array
}
// Function to clear chat history between two users
function clear_chat($user_id, $receiver_id, $conn) {
    $user_id = (int)$user_id; // Ensure user ID is an integer
    $receiver_id = (int)$receiver_id; // Ensure receiver ID is an integer
    // Delete all messages between the two users
    $query = "DELETE FROM messages 
              WHERE (sender_id = $user_id AND receiver_id = $receiver_id) 
              OR (sender_id = $receiver_id AND receiver_id = $user_id)";
    // Return true if the query is successful, otherwise false
    return mysqli_query($conn, $query);
}
// Handle GET request for clearing chat (non-AJAX version)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear_chat') {
    // Include database connection if not already included
    if (!isset($conn)) {
        require_once 'db.php';
    }
    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit;
    }
    // Check if required parameters exist
    if (!isset($_GET['user_id']) || !isset($_GET['receiver_id'])) {
        die("Error: Missing required parameters");
    }
    // Verify the user ID matches the logged-in user
    if ((int)$_SESSION['user_id'] !== (int)$_GET['user_id']) {
        die("Error: Unauthorized - user ID mismatch");
    }
    // Get the return URL
    $return_to = isset($_GET['return_to']) ? $_GET['return_to'] : 'chat.php';
    // Clear the chat
    $success = clear_chat($_GET['user_id'], $_GET['receiver_id'], $conn);
    // Redirect back to the chat page
    header("Location: $return_to");
    exit;
}
?>