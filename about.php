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

$current_user_id = $_SESSION['user_id']; // Current logged-in user's ID
$current_username = $_SESSION['username']; // Current logged-in user's username

// Handle logout functionality
if (isset($_GET['logout'])) {
    // Destroy the session and redirect to the login page
    session_destroy();
    header("Location: index.php");
    exit();
}

// Team members data
$team_members = [
    [
        'id' => 1,
        'name' => 'Manpreet Singh Sidhu',
        'role' => 'Full Stack Developer',
        'image' => 'https://avatars.githubusercontent.com/u/127300897?v=4',
        'linkedin' => 'https://linkedin.com/in/manpreetsinghsidhu',
        'github' => 'https://github.com/manpreetsidhhu',
        'portfolio' => 'https://manpreetsidhu.me',
        'bio' => 'Manpreet is a detail-oriented Full Stack Developer with a focus on creating efficient and maintainable code. He has a strong foundation in web development fundamentals and stays current with emerging technologies.',
        'education' => [
            [
                'institution' => 'Lovely Professional University',
                'degree' => 'B.Tech in Computer Science and Engineering',
                'year' => '2023-2027'
            ]
        ],
        'experience' => [
            [
                'position' => 'Junior Developer',
                'company' => 'WebTech Solutions',
                'duration' => 'January 2023 - Present',
                'description' => 'Working on full-stack web application development and maintenance'
            ]
        ],
        'projects' => [
            [
                'name' => 'Healthcare Management System',
                'description' => 'Developed a platform for patient records management and scheduling',
                'tech' => 'React, Express, MongoDB, Socket.io'
            ],
            [
                'name' => 'Budget Tracker',
                'description' => 'Created a personal finance application with expense categorization',
                'tech' => 'Angular, Firebase, Chart.js'
            ]
        ],
        'skills' => ['JavaScript', 'React', 'PHP', 'Angular', 'Node.js', 'MongoDB', 'Express', 'Firebase', 'RESTful APIs', 'Git', 'Agile Methodologies']
    ],
    [
        'id' => 2,
        'name' => 'Amey Sharma',
        'role' => 'Full Stack Developer',
        'image' => 'https://media.licdn.com/dms/image/v2/D5603AQEPjFhoT1K9Dg/profile-displayphoto-shrink_400_400/profile-displayphoto-shrink_400_400/0/1704394043641?e=1749686400&v=beta&t=-Musavd0k1bbHaRJ7h5NA-EKd8O9iqn0B56eYrURF3s',
        'linkedin' => 'https://linkedin.com/in/ameysh',
        'github' => 'https://github.com/ameysharma1',
        'bio' => 'Amey is a versatile Full Stack Developer with a strong background in both frontend and backend technologies. He has experience developing scalable web applications and has contributed to various open-source projects.',
        'education' => [
            [
                'institution' => 'Lovely Professional University',
                'degree' => 'B.Tech in Computer Science and Engineering',
                'year' => '2023-2027'
            ]
        ],
        'experience' => [
            [
                'position' => 'Web Developer',
                'company' => 'Digital Innovations',
                'duration' => 'June 2023 - December 2023',
                'description' => 'Developed responsive websites and web applications for clients across various industries'
            ]
        ],
        'projects' => [
            [
                'name' => 'Social Media Dashboard',
                'description' => 'Created an analytics dashboard for social media account management',
                'tech' => 'React, Firebase, Redux, Chart.js'
            ],
            [
                'name' => 'Restaurant Ordering System',
                'description' => 'Built a digital menu and ordering system for restaurants',
                'tech' => 'Vue.js, Node.js, PostgreSQL'
            ]
        ],
        'skills' => ['JavaScript', 'TypeScript', 'PHP', 'React', 'Vue.js', 'Node.js', 'Express', 'MongoDB', 'Firebase', 'AWS', 'Docker']
    ],
    [
        'id' => 3,
        'name' => 'Abhishek Goyal',
        'role' => 'Full Stack Developer',
        'image' => 'https://media.licdn.com/dms/image/v2/D4D03AQGZuArIuZIMvw/profile-displayphoto-shrink_200_200/profile-displayphoto-shrink_200_200/0/1700493332721?e=2147483647&v=beta&t=Pow7RnINoQmNyEzLgbnC9vvpNaOndtvGXgpD08E0FA4',
        'linkedin' => 'https://linkedin.com/in/abhishekgoyal213',
        'github' => 'https://github.com/abhishekgoyal1',
        'bio' => 'Abhishek is a passionate Full Stack Developer with expertise in building modern web applications. He specializes in creating responsive and user-friendly interfaces while implementing robust backend systems.',
        'education' => [
            [
                'institution' => 'Lovely Professional University',
                'degree' => 'B.Tech in Computer Science and Engineering',
                'year' => '2023-2027'
            ]
        ],
        'experience' => [
            [
                'position' => 'Full Stack Developer Intern',
                'company' => 'Tech Solutions Ltd',
                'duration' => 'May 2023 - August 2023',
                'description' => 'Worked on developing and maintaining web applications using React and Node.js'
            ]
        ],
        'projects' => [
            [
                'name' => 'E-Commerce Platform',
                'description' => 'Built a full-featured online shopping platform with payment integration',
                'tech' => 'React, Node.js, MongoDB, Stripe'
            ],
            [
                'name' => 'Task Management System',
                'description' => 'Developed a collaborative task tracking application',
                'tech' => 'Vue.js, Express, MySQL'
            ]
        ],
        'skills' => ['JavaScript', 'React', 'Node.js', 'PHP', 'Express', 'MongoDB', 'SQL', 'HTML/CSS', 'Tailwind CSS', 'Git']
    ],
    [
        'id' => 4,
        'name' => 'Niraj Kumar',
        'role' => 'Full Stack Developer',
        'image' => 'https://media.licdn.com/dms/image/v2/D4E03AQFi1tVTVn8Hlw/profile-displayphoto-shrink_200_200/profile-displayphoto-shrink_200_200/0/1702109661491?e=2147483647&v=beta&t=YtexqvhWDh-pQZKtLk97jYGfHvo58nI-3WIT9hF2z9g',
        'linkedin' => 'https://linkedin.com/in/nirajkr26',
        'github' => 'https://github.com/nirajkr26',
        'bio' => 'Niraj is a creative Full Stack Developer skilled in developing modern web applications. He has a strong passion for user experience and performance optimization, creating elegant solutions to complex problems.',
        'education' => [
            [
                'institution' => 'Lovely Professional University',
                'degree' => 'B.Tech in Computer Science and Engineering',
                'year' => '2023-2027'
            ]
        ],
        'experience' => [
            [
                'position' => 'Software Developer Intern',
                'company' => 'TechSprint',
                'duration' => 'May 2023 - August 2023',
                'description' => 'Worked on developing features for a SaaS product using modern web technologies'
            ]
        ],
        'projects' => [
            [
                'name' => 'Real-time Chat Application',
                'description' => 'Built a messaging platform with real-time communication features',
                'tech' => 'React, Node.js, Socket.io, MongoDB'
            ],
            [
                'name' => 'Portfolio Generator',
                'description' => 'Created a tool for developers to quickly build professional portfolios',
                'tech' => 'Vue.js, Express, PostgreSQL'
            ]
        ],
        'skills' => ['JavaScript', 'React', 'PHP', 'Vue.js', 'Node.js', 'Express', 'MongoDB', 'PostgreSQL', 'AWS', 'Docker', 'CI/CD', 'Unit Testing']
    ]
];

// Remove the selected member logic since we'll handle it with JavaScript
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - uniChat Messaging Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body{font-family:'Plus Jakarta Sans',sans-serif}
        
        /* Core animations for all pages */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); } 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); } }
        
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        .animate-slide-up { animation: slideUp 0.5s ease-out; }
        .hover-lift { transition: transform 0.2s ease-out; }
        .hover-lift:hover { transform: translateY(-3px); }
        
        /* Team member card animation */
        .team-card { transition: all 0.3s ease; }
        .team-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        
        .social-icon { transition: all 0.2s ease; }
        
        /* Modal animations */
        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes modalPopIn {
            0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
            70% { transform: translate(-50%, -50%) scale(1.05); }
            100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
        }
        
        .modal-backdrop {
            animation: modalFadeIn 0.3s ease-out forwards;
        }
        
        .modal-content {
            animation: modalPopIn 0.4s ease-out forwards;
            position: fixed;
            top: 50%;
            left: 50%;
            width: 90%;
            max-width: 4xl;
            max-height: 90vh;
            overflow-y: auto;
            border-radius: 0.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            background-color: white;
            z-index: 50;
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
                            <?php if(!empty($_SESSION['profile_image'])): ?>
                                <img src="<?php echo $_SESSION['profile_image']; ?>" class="w-8 h-8 rounded-full object-cover border-2 border-white border-opacity-30 group-hover:border-opacity-70 transition-all">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </a>
                    
                    <!-- Chat link -->
                    <a href="chat.php" class="flex items-center hover:bg-white hover:bg-opacity-10 px-3 py-2 rounded-lg transition-all duration-300 group">
                        <i class="fas fa-comments mr-2"></i>
                        <span>Chat</span>
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
    
    <div class="flex-1 overflow-auto mt-1 mb-1">
        <div class="container mx-auto px-4 py-8">
            <!-- Page title -->
            <div class="text-center mb-12 animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-800 mb-4">Meet Our Team</h1>
                <p class="text-gray-600 max-w-2xl mx-auto">Get to know the talented individuals behind the uniChat Messaging Platform. Our team is dedicated to creating the best messaging experience for users around the world.</p>
            </div>
            
            <!-- Team members grid (always show this section now) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-16">
                <?php foreach ($team_members as $member): ?>
                    <div class="bg-white rounded-lg shadow-md team-card animate-slide-up p-4">
                        <!-- Circular image at top center -->
                        <div class="flex justify-center mb-4">
                            <img src="<?php echo htmlspecialchars($member['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($member['name']); ?>" 
                                 class="w-24 h-24 rounded-full object-cover border-2 border-indigo-100">
                        </div>
                        
                        <!-- Content below image -->
                        <div class="text-center">
                            <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo htmlspecialchars($member['name']); ?></h3>
                            <p class="text-indigo-600 font-medium text-sm mb-3"><?php echo htmlspecialchars($member['role']); ?></p>
                            
                            <!-- Social links with text labels -->
                            <div class="flex justify-center space-x-2 mb-3">
                                <a href="<?php echo htmlspecialchars($member['linkedin']); ?>" target="_blank" 
                                   class="text-blue-600 hover:text-blue-800 flex items-center px-3 py-1 rounded-lg bg-blue-50 hover:bg-blue-200 transition-all text-xs">
                                    <span class="font-medium mr-2">LinkedIn</span>
                                    <i class="fab fa-linkedin"></i>
                                </a>
                                <a href="<?php echo htmlspecialchars($member['github']); ?>" target="_blank" 
                                   class="text-gray-800 hover:text-black flex items-center px-3 py-1 rounded-lg bg-gray-50 hover:bg-gray-200 transition-all text-xs">
                                    <span class="font-medium mr-2">GitHub</span>
                                    <i class="fab fa-github"></i>
                                </a>
                            </div>
                            
                            <!-- Changed to button that triggers modal -->
                            <button onclick="openMemberModal(<?php echo $member['id']; ?>)" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium inline-flex items-center">
                                View More <i class="fas fa-arrow-right ml-1"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- About the project section -->
            <div class="bg-white rounded-lg shadow-lg p-8 animate-fade-in">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">About uniChat Messaging Platform</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <div class="bg-indigo-100 p-6 rounded-lg mb-6">
                            <h3 class="text-xl font-semibold text-indigo-800 mb-3">Our Mission</h3>
                            <p class="text-gray-700">uniChat aims to connect people through seamless, real-time communication. We believe in creating a platform that prioritizes user privacy, ease of use, and reliable message delivery.</p>
                        </div>
                        <div class="bg-purple-100 p-6 rounded-lg">
                            <h3 class="text-xl font-semibold text-purple-800 mb-3">Technology Stack</h3>
                            <p class="text-gray-700">uniChat is built using modern web technologies including PHP, MySQL, JavaScript, and Tailwind CSS. We focus on responsive design, accessibility, and performance optimization.</p>
                        </div>
                    </div>
                    <div>
                        <div class="bg-blue-100 p-6 rounded-lg mb-6">
                            <h3 class="text-xl font-semibold text-blue-800 mb-3">Key Features</h3>
                            <ul class="list-disc pl-5 text-gray-700 space-y-2">
                                <li>Real-time messaging with instant delivery</li>
                                <li>User-friendly interface with modern design</li>
                                <li>Emoji support and rich media sharing</li>
                                <li>Responsive design for all devices</li>
                                <li>User profiles with customization options</li>
                            </ul>
                        </div>
                        <div class="bg-green-100 p-6 rounded-lg">
                            <h3 class="text-xl font-semibold text-green-800 mb-3">Future Roadmap</h3>
                            <p class="text-gray-700">We're constantly improving uniChat with new features and enhancements. Upcoming additions include group chats, end-to-end encryption, voice messages, and more interactive elements.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="text-center py-2 text-gray-500 text-xs border-t border-gray-200 bg-white rounded-b-lg mx-1 mb-1">
        © 2025 uniChat
    </footer>

    <!-- Modal Container (hidden by default) -->
    <div id="memberModal" class="fixed inset-0 hidden z-50">
        <!-- Modal Backdrop -->
        <div class="modal-backdrop fixed inset-0 bg-gray-900 bg-opacity-75" onclick="closeMemberModal()"></div>
        
        <!-- Modal Content -->
        <div class="modal-content">
            <div class="p-6">
                <!-- Close button -->
                <button onclick="closeMemberModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
                
                <!-- Modal content will be dynamically populated -->
                <div id="modalContent"></div>
            </div>
        </div>
    </div>

    <script>
        // Store team member data in JavaScript
        const teamMembers = <?php echo json_encode($team_members); ?>;
        
        // Function to open modal with member details
        function openMemberModal(memberId) {
            // Find the selected member
            const member = teamMembers.find(m => m.id === memberId);
            if (!member) return;
            
            // Populate modal content
            const modalContent = document.getElementById('modalContent');
            
            let educationHTML = '';
            member.education.forEach(edu => {
                educationHTML += `
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="font-medium">${edu.institution}</div>
                        <div class="text-gray-700">${edu.degree}</div>
                        <div class="text-sm text-gray-500">${edu.year}</div>
                    </div>
                `;
            });
            
            let projectsHTML = '';
            member.projects.forEach(project => {
                projectsHTML += `
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="font-medium">${project.name}</div>
                        <p class="text-gray-600 text-sm mb-2">${project.description}</p>
                        <div class="text-xs text-indigo-600 font-medium">Tech Stack: ${project.tech}</div>
                    </div>
                `;
            });
            
            let skillsHTML = '';
            member.skills.forEach(skill => {
                skillsHTML += `<span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full text-sm">${skill}</span>`;
            });
            
            modalContent.innerHTML = `
                <div class="flex flex-col md:flex-row">
                    <div class="md:w-1/3 mb-6 md:mb-0">
                        <!-- Circular profile image -->
                        <div class="flex justify-center">
                            <img src="${member.image}" 
                                 alt="${member.name}" 
                                 class="w-48 h-48 rounded-full object-cover border-4 border-indigo-100 shadow-md">
                        </div>
                        <div class="mt-6 flex flex-col space-y-3">
                            <a href="${member.linkedin}" target="_blank" class="text-blue-600 hover:text-blue-800 social-icon flex items-center justify-center bg-blue-50 hover:bg-blue-200 px-4 py-2 rounded-lg transition-all">
                                <span class="mr-2 font-medium">LinkedIn</span>
                                <i class="fab fa-linkedin text-xl"></i>
                            </a>
                            <a href="${member.github}" target="_blank" class="text-gray-800 hover:text-black social-icon flex items-center justify-center bg-gray-50 hover:bg-gray-200 px-4 py-2 rounded-lg transition-all">
                                <span class="mr-2 font-medium">GitHub</span>
                                <i class="fab fa-github text-xl"></i>
                            </a>
                            ${member.id === 1 ? `
                            <a href="${member.portfolio}" target="_blank" class="text-purple-600 hover:text-purple-800 social-icon flex items-center justify-center bg-purple-50 hover:bg-purple-200 px-4 py-2 rounded-lg transition-all">
                                <span class="mr-2 font-medium">Portfolio</span>
                                <i class="fas fa-globe text-xl"></i>
                            </a>
                            ` : ''}
                        </div>
                    </div>
                    <div class="md:w-2/3 md:pl-8">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">${member.name}</h2>
                        <p class="text-indigo-600 font-medium mb-4">${member.role}</p>
                        <div class="border-l-4 border-indigo-500 pl-4 mb-6">
                            <p class="text-gray-700 italic">${member.bio}</p>
                        </div>
                        
                        <!-- Education Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <i class="fas fa-graduation-cap mr-2 text-indigo-500"></i>
                                Education
                            </h3>
                            <div class="space-y-3">
                                ${educationHTML}
                            </div>
                        </div>
                        
                        <!-- Projects Section -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <i class="fas fa-code-branch mr-2 text-indigo-500"></i>
                                Projects
                            </h3>
                            <div class="space-y-3">
                                ${projectsHTML}
                            </div>
                        </div>
                        
                        <!-- Skills Section -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 flex items-center">
                                <i class="fas fa-tools mr-2 text-indigo-500"></i>
                                Skills & Expertise
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                ${skillsHTML}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Show the modal
            document.getElementById('memberModal').classList.remove('hidden');
        }
        
        // Function to close modal
        function closeMemberModal() {
            document.getElementById('memberModal').classList.add('hidden');
        }
        
        // Close modal when clicking Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeMemberModal();
            }
        });
        
        // Animations for cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.team-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html> 