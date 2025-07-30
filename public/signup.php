<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign Up</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Ionicons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</head>

<style>body {
        margin: 0;
        font-family: 'Nunito', 'Open Sans', Arial, sans-serif;
        background: #f2f5fa;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .modern-signup-section {
        width: 100vw;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: 100px;
    }
    .modern-signup-form {
        background: #f0f3f8;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(33,150,243,0.10), 0 1.5px 5px rgba(33,150,243,0.09);
        padding: 34px 34px 32px 34px;
        width: 90%;
        max-width: 410px;
        display: flex;
        flex-direction: column;
        align-items: stretch;
        position: relative;
        z-index: 2;
        border: 1.5px solid #ffffff;
    }
    .modern-signup-switch {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        font-size: 1rem;
        margin-bottom: 18px;
        color: #7b83a1;
        width: 100%;
        text-align: center;
    }
    .modern-signup-login-link {
        color: #2196f3;
        font-weight: 700;
        text-decoration: none;
        transition: color 0.22s;
    }
    .modern-signup-login-link:hover {
        color: #1565c0;
        text-decoration: underline;
    }
    .modern-signup-title {
        font-size: 2rem;
        color: #2c2c3c;
        font-weight: 800;
        text-align: center;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .modern-signup-form ion-icon {
        font-size: 1.3em;
    }
    .modern-signup-avatar-wrap {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 16px;
    }
    .modern-signup-avatar-label {
        cursor: pointer;
    }
    .modern-signup-avatar {
        width: 84px;
        height: 84px;
        border-radius: 50%;
        background: linear-gradient(135deg, #e3f1fe 70%, #bbdefb 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 2.2rem;
        box-shadow: 0 2px 12px #2196f315;
        color: #2196f3;
        position: relative;
        overflow: hidden;
        border: 2.5px solid #e3e8ef;
        transition: box-shadow 0.22s, border-color 0.22s;
    }
    .modern-signup-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    .modern-signup-avatar:hover, .modern-signup-avatar-label:focus .modern-signup-avatar {
        box-shadow: 0 8px 28px #2196f345;
        border-color: #2196f3;
    }
    .modern-signup-fields-row {
        display: flex;
        gap: 12px;
        margin-bottom: 0;
    }
    .modern-signup-field {
        display: flex;
        align-items: center;
        background: #f6f8fc;
        border-radius: 12px;
        margin-bottom: 20px;
        padding: 0 12px;
        border: 1.5px solid #e3e8ef;
        transition: border-color 0.22s, box-shadow 0.22s;
        box-shadow: 0 2px 8px #2196f315;
        position: relative;
        flex: 1 1 0;
    }
    .modern-signup-field:focus-within {
        border-color: #2196f3;
        box-shadow: 0 4px 16px #2196f325;
    }
    .modern-signup-field label {
        display: flex;
        align-items: center;
        color: #2196f3;
        font-size: 1.28rem;
        margin-right: 7px;
        min-width: 30px;
        max-width: 30px;
        user-select: none;
    }
    .modern-signup-field input {
        border: none;
        outline: none;
        background: transparent;
        font-size: 1.07rem;
        color: #2c2c3c;
        padding: 13px 0;
        width: 100%;
        font-family: inherit;
    }
    .modern-signup-field--password {
        position: relative;
    }
    .modern-signup-eye {
        display: flex;
        align-items: center;
        cursor: pointer;
        color: #b8c9de;
        margin-left: 8px;
        font-size: 1.22rem;
        transition: color 0.18s, transform 0.22s;
        user-select: none;
    }
    .modern-signup-eye:hover,
    .modern-signup-eye:focus {
        color: #2196f3;
        transform: scale(1.09);
    }
    .modern-signup-btn {
        width: 100%;
        background: linear-gradient(90deg, #2196f3 60%, #1565c0 100%);
        color: #fff;
        font-size: 1.19rem;
        font-weight: 700;
        padding: 13px 0;
        border: none;
        border-radius: 11px;
        box-shadow: 0 2px 12px #2196f320;
        cursor: pointer;
        margin-top: 7px;
        transition: background 0.22s, box-shadow 0.22s, transform 0.22s;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .modern-signup-btn:hover,
    .modern-signup-btn:focus {
        background: linear-gradient(90deg, #1565c0 60%, #2196f3 100%);
        box-shadow: 0 4px 20px #2196f335;
        transform: translateY(-2px) scale(1.026);
    }

    /* Message popups */
    .modern-signup-message {
        position: fixed;
        top: 30px;
        left: 50%;
        transform: translateX(-50%);
        min-width: 280px;
        max-width: 90vw;
        padding: 15px 22px;
        border-radius: 14px;
        font-size: 1.08rem;
        text-align: center;
        z-index: 99;
        background: #f7f7f7;
        box-shadow: 0 2px 24px #2196f345;
        font-weight: 600;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s, top 0.23s;
    }
    .modern-signup-message.show {
        opacity: 1;
        pointer-events: auto;
        top: 50px;
    }
    .modern-signup-message.success {
        background: linear-gradient(90deg,#d0f8ce 60%,#b2f7b9 100%);
        color: #176c0c;
        border: 1.5px solid #8ae68c;
    }
    .modern-signup-message.error {
        background: linear-gradient(90deg,#ffe0e0 60%,#ffc2c2 100%);
        color: #b71c1c;
        border: 1.5px solid #f38d8d;
    }
    @media (max-width: 600px) {
        .modern-signup-form {
            padding: 24px 6vw;
            max-width: 98vw;
        }
        .modern-signup-fields-row {
            flex-direction: column;
            gap: 0;
        }
    }</style>

<body>

<?php
// Include database connection
require_once '../config/db.php';

// Add missing functions if not defined in db.php
if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize input data
     * @param string $data Input data
     * @return string
     */
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

if (!function_exists('emailExists')) {
    /**
     * Check if email exists in users table
     * @param string $email Email to check
     * @param int|null $excludeId User ID to exclude from check
     * @return bool
     */
    function emailExists($email, $excludeId = null) {
        global $conn;
        $sql = "SELECT id FROM users WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $stmt = $conn->prepare($sql);
        if ($excludeId) {
            $stmt->bind_param("si", $email, $excludeId);
        } else {
            $stmt->bind_param("s", $email);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
}

if (!function_exists('createUser')) {
    /**
     * Create new user account
     * @param array $userData User data
     * @return int|false User ID or false on failure
     */
    function createUser($userData) {
        global $conn;
        $sql = "INSERT INTO users (first_name, last_name, email, phone, password, date_of_birth, user_type, profile_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        $phone = $userData['phone'] ?? '';

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss",
            $userData['first_name'],
            $userData['last_name'],
            $userData['email'],
            $phone,
            $hashedPassword,
            $userData['date_of_birth'],
            $userData['user_type'],
            $userData['profile_image']
        );

        if ($stmt->execute()) {
            return $conn->insert_id;
        }
        return false;
    }
}

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $firstName = sanitizeInput($_POST['firstName'] ?? '');
        $lastName = sanitizeInput($_POST['lastName'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
            throw new Exception('All fields are required.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }

        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long.');
        }

        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        // Validate phone number (basic validation)
        if (strlen($phone) < 10) {
            throw new Exception('Please enter a valid phone number.');
        }

        // Check if email already exists
        if (emailExists($email)) {
            throw new Exception('An account with this email already exists.');
        }

        // Handle profile image upload
        $profileImage = null;
        if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/profiles/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExtension = strtolower(pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = uniqid('profile_') . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $uploadPath)) {
                    $profileImage = '../assets/uploads/profiles/' . $fileName;
                }
            }
        }

        // Create user data array
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $phone,
            'password' => $password,
            'date_of_birth' => '1990-01-01', // Default value, can be updated later
            'user_type' => 'customer',
            'profile_image' => $profileImage
        ];

        // Create user account
        $userId = createUser($userData);

        if ($userId) {
            $success = 'Account created successfully! Redirecting to login...';
            echo "<script>
                setTimeout(function() {
                    window.location.href = '../public/login.php';
                }, 2000);
            </script>";
        } else {
            throw new Exception('Failed to create account. Please try again.');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<header class="header" data-header>
    <div class="container">

        <div class="overlay" data-overlay></div>

        <a href="#" class="logo">
            <img src="../assets/images/logo.svg" alt="Ridex logo">
        </a>

        <nav class="navbar" data-navbar>
            <ul class="navbar-list">

                <li>
                    <a href="index.php" class="navbar-link " data-nav-link>Home</a>
                </li>

                <li>
                    <a href="index.php" class="navbar-link" data-nav-link>About us</a>
                </li>

                <li>
                    <a href="index.php" class="navbar-link" data-nav-link>Contact Us</a>
                </li>

                <li>
                    <a href="login.php" class="navbar-link" data-nav-link>Log In</a>
                </li>


                <li>
                    <a href="signup.html" class="navbar-link active_link" data-nav-link>Sign Up</a>
                </li>

            </ul>
        </nav>

        <div class="header-actions">

            <button class="nav-toggle-btn" data-nav-toggle-btn aria-label="Toggle Menu">
                <span class="one"></span>
                <span class="two"></span>
                <span class="three"></span>
            </button>

        </div>

    </div>
</header>

<section class="modern-signup-section">
    <form class="modern-signup-form" id="signupForm" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="modern-signup-switch">
            <span>Already have an account?</span>
            <a href="login.php" class="modern-signup-login-link">Login</a>
        </div>
        <h2 class="modern-signup-title">
            <ion-icon name="person-add-outline"></ion-icon> Sign Up
        </h2>
        <div class="modern-signup-avatar-wrap">
            <label for="signup-photo" class="modern-signup-avatar-label" title="Click to select photo">
                <input type="file" id="signup-photo" name="profileImage" accept="image/*" style="display:none">
                <div class="modern-signup-avatar" id="avatarCircle">
                    <ion-icon name="camera-outline"></ion-icon>
                </div>
            </label>
        </div>
        <div class="modern-signup-fields-row">
            <div class="modern-signup-field">
                <label for="signup-fname">
                    <ion-icon name="person-outline"></ion-icon>
                </label>
                <input type="text" id="signup-fname" name="firstName" placeholder="First Name" required>
            </div>
            <div class="modern-signup-field">
                <label for="signup-lname">
                    <ion-icon name="person-outline"></ion-icon>
                </label>
                <input type="text" id="signup-lname" name="lastName" placeholder="Last Name" required>
            </div>
        </div>
        <div class="modern-signup-field">
            <label for="signup-email">
                <ion-icon name="mail-outline"></ion-icon>
            </label>
            <input type="email" id="signup-email" name="email" placeholder="Email" required>
        </div>
        <div class="modern-signup-field">
            <label for="signup-phone">
                <ion-icon name="call-outline"></ion-icon>
            </label>
            <input type="tel" id="signup-phone" name="phone" placeholder="Phone Number" required>
        </div>
        <div class="modern-signup-field modern-signup-field--password">
            <label for="signup-password">
                <ion-icon name="lock-closed-outline"></ion-icon>
            </label>
            <input type="password" id="signup-password" name="password" placeholder="Password" required>
            <span class="modern-signup-eye" data-target="signup-password" title="Show/Hide Password">
                    <ion-icon name="eye-outline"></ion-icon>
                </span>
        </div>
        <div class="modern-signup-field modern-signup-field--password">
            <label for="signup-password2">
                <ion-icon name="lock-closed-outline"></ion-icon>
            </label>
            <input type="password" id="signup-password2" name="confirmPassword" placeholder="Confirm Password" required>
            <span class="modern-signup-eye" data-target="signup-password2" title="Show/Hide Password">
                    <ion-icon name="eye-outline"></ion-icon>
                </span>
        </div>
        <button type="submit" class="modern-signup-btn">Sign Up</button>
    </form>
    <div id="signup-message" class="modern-signup-message" style="display:none;"></div>
</section>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showSignupMessage('<?php echo addslashes($error); ?>', 'error');
        });
    </script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showSignupMessage('<?php echo addslashes($success); ?>', 'success');
        });
    </script>
<?php endif; ?>

<script>// Handle avatar preview
    const signupPhotoInput = document.getElementById('signup-photo');
    const avatarCircle = document.getElementById('avatarCircle');

    signupPhotoInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                avatarCircle.innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            avatarCircle.innerHTML = `<ion-icon name="camera-outline"></ion-icon>`;
        }
    });

    // Handle password eyes
    document.querySelectorAll('.modern-signup-eye').forEach(eye => {
        eye.addEventListener('click', function() {
            const inputId = this.getAttribute('data-target');
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                this.querySelector('ion-icon').setAttribute('name', 'eye-off-outline');
            } else {
                input.type = "password";
                this.querySelector('ion-icon').setAttribute('name', 'eye-outline');
            }
        });
    });

    // Handle sign up form validation and messaging
    const signupForm = document.getElementById('signupForm');
    const signupMessage = document.getElementById('signup-message');

    signupForm.addEventListener('submit', function(e) {
        // Client-side validation before form submission
        const pw1 = document.getElementById('signup-password').value;
        const pw2 = document.getElementById('signup-password2').value;
        const email = document.getElementById('signup-email').value;
        const firstName = document.getElementById('signup-fname').value;
        const lastName = document.getElementById('signup-lname').value;
        const phone = document.getElementById('signup-phone').value;

        // Basic validation
        if (!firstName.trim() || !lastName.trim() || !email.trim() || !phone.trim() || !pw1 || !pw2) {
            e.preventDefault();
            showSignupMessage('All fields are required.', 'error');
            return;
        }

        if (pw1.length < 6) {
            e.preventDefault();
            showSignupMessage('Password must be at least 6 characters.', 'error');
            return;
        }

        if (pw1 !== pw2) {
            e.preventDefault();
            showSignupMessage('Passwords do not match.', 'error');
            return;
        }

        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showSignupMessage('Please enter a valid email address.', 'error');
            return;
        }

        // Phone validation
        if (phone.length < 10) {
            e.preventDefault();
            showSignupMessage('Please enter a valid phone number.', 'error');
            return;
        }

        // If all validations pass, form will be submitted normally
    });

    function showSignupMessage(text, type) {
        signupMessage.textContent = text;
        signupMessage.className = `modern-signup-message show ${type}`;
        signupMessage.style.display = 'block';
        setTimeout(() => {
            signupMessage.classList.remove("show");
            setTimeout(() => {
                signupMessage.style.display = 'none';
            }, 300);
        }, 3000);
    }</script>

<script>



    'use strict';

    /**
     * navbar toggle
     */

    const overlay = document.querySelector("[data-overlay]");
    const navbar = document.querySelector("[data-navbar]");
    const navToggleBtn = document.querySelector("[data-nav-toggle-btn]");
    const navbarLinks = document.querySelectorAll("[data-nav-link]");

    const navToggleFunc = function () {
        navToggleBtn.classList.toggle("active");
        navbar.classList.toggle("active");
        overlay.classList.toggle("active");
    }

    navToggleBtn.addEventListener("click", navToggleFunc);
    overlay.addEventListener("click", navToggleFunc);

    for (let i = 0; i < navbarLinks.length; i++) {
        navbarLinks[i].addEventListener("click", navToggleFunc);
    }


    /**
     * header active on scroll
     */

    const header = document.querySelector("[data-header]");

    window.addEventListener("scroll", function () {
        window.scrollY >= 10 ? header.classList.add("active")
            : header.classList.remove("active");
    });


    document.addEventListener('DOMContentLoaded', function() {
        const profileBtn = document.getElementById('userProfileBtn');
        const dropdownMenu = document.getElementById('userDropdownMenu');

        // Toggle dropdown on click
        profileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
            }
        });
    });</script>




<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>