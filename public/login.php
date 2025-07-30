<?php
session_start();
require_once '../config/db.php';

// Include functions for login verification as needed...
if (!function_exists('getUserByEmail')) {
    function getUserByEmail($email) {
        global $conn;
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
if (!function_exists('verifyLogin')) {
    function verifyLogin($email, $password) {
        $user = getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            return $user;
        }
        return false;
    }
}

// Initialize variables
$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    try {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new Exception('Email and password are required.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Please enter a valid email address.');
        }
        $user = verifyLogin($email, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['logged_in'] = true;
            $success = 'Login successful! Redirecting...';

            if ($user['user_type'] === 'admin' || $user['user_type'] === 'manager' || $user['user_type'] === 'customer') {
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '../Both/homepage.php';
                    }, 2000);
                </script>";
            }
        } else {
            throw new Exception('Invalid email or password.');
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Fonts & Ionicons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/main.css">
    <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Nunito', 'Open Sans', Arial, sans-serif;
            background: #f2f5fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modern-login-section {
            width: 100vw;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 5rem;
        }
        .modern-login-form {
            background: #f0f3f8;
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(33,150,243,0.10), 0 1.5px 5px rgba(33,150,243,0.09);
            padding: 40px 34px 32px 34px;
            width: 90%;
            max-width: 370px;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            position: relative;
            z-index: 2;
            border: 1.5px solid #ffffff;
        }
        .modern-login-switch {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            font-size: 0.98rem;
            margin-bottom: 7px;
            color: #7b83a1;
        }
        .modern-login-signup-link {
            color: #2196f3;
            font-weight: 700;
            text-decoration: none;
            transition: color 0.22s;
        }
        .modern-login-signup-link:hover {
            color: #1565c0;
            text-decoration: underline;
        }
        .modern-login-title {
            font-size: 2rem;
            color: #2c2c3c;
            font-weight: 800;
            text-align: center;
            margin-bottom: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .modern-login-field {
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
        }
        .modern-login-field:focus-within {
            border-color: #2196f3;
            box-shadow: 0 4px 16px #2196f325;
        }
        .modern-login-field label {
            display: flex;
            align-items: center;
            color: #2196f3;
            font-size: 1.28rem;
            margin-right: 7px;
            min-width: 30px;
            max-width: 30px;
            user-select: none;
        }
        .modern-login-field input {
            border: none;
            outline: none;
            background: transparent;
            font-size: 1.07rem;
            color: #2c2c3c;
            padding: 13px 0;
            width: 100%;
            font-family: inherit;
        }
        .modern-login-eye {
            display: flex;
            align-items: center;
            cursor: pointer;
            color: #b8c9de;
            margin-left: 8px;
            font-size: 1.22rem;
            transition: color 0.18s, transform 0.22s;
            user-select: none;
        }
        .modern-login-eye:hover,
        .modern-login-eye:focus {
            color: #2196f3;
            transform: scale(1.09);
        }
        .modern-login-forgot {
            display: flex;
            justify-content: center;
            margin-top: -12px;
            margin-bottom: 18px;
        }
        .modern-login-forgot-link {
            color: #2196f3;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.22s;
            cursor: pointer;
        }
        .modern-login-forgot-link:hover {
            color: #1565c0;
            text-decoration: underline;
        }
        .modern-login-btn {
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
            margin-top: 10px;
            transition: background 0.22s, box-shadow 0.22s, transform 0.22s;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .modern-login-btn:hover,
        .modern-login-btn:focus {
            background: linear-gradient(90deg, #1565c0 60%, #2196f3 100%);
            box-shadow: 0 4px 20px #2196f335;
            transform: translateY(-2px) scale(1.026);
        }
        .modern-login-message {
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
            margin: 3rem auto;
        }
        .modern-login-message.show {
            opacity: 1;
            pointer-events: auto;
            top: 50px;
        }
        .modern-login-message.success {
            background: linear-gradient(90deg,#d0f8ce 60%,#b2f7b9 100%);
            color: #176c0c;
            border: 1.5px solid #8ae68c;
        }
        .modern-login-message.error {
            background: linear-gradient(90deg,#ffe0e0 60%,#ffc2c2 100%);
            color: #b71c1c;
            border: 1.5px solid #f38d8d;
        }
        /* Reset Password Modal Styling */
        .reset-password-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .reset-password-modal.show {
            display: flex;
        }
        .reset-password-modal-content {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            text-align: center;
            position: relative;
        }
        .reset-password-modal-content h3 {
            margin-bottom: 16px;
            color: #2c2c3c;
            font-size: 1.5rem;
            font-weight: 700;
        }
        .reset-password-modal-content input[type="email"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1.5px solid #e3e8ef;
            border-radius: 8px;
            font-size: 1rem;
        }
        .reset-password-modal-content button {
            background: linear-gradient(90deg, #2196f3 60%, #1565c0 100%);
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.22s;
        }
        .reset-password-modal-content button:hover {
            background: linear-gradient(90deg, #1565c0 60%, #2196f3 100%);
        }
        .reset-password-close {
            position: absolute;
            top: 10px;
            right: 14px;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
<header class="header" data-header>
    <div class="container">
        <div class="overlay" data-overlay></div>
        <a href="#" class="logo">
            <img src="../assets/images/logo.svg" alt="Ridex logo">
        </a>
        <nav class="navbar" data-navbar>
            <ul class="navbar-list">
                <li>
                    <a href="index.php" class="navbar-link" data-nav-link>Home</a>
                </li>
                <li>
                    <a href="index.php" class="navbar-link" data-nav-link>About Us</a>
                </li>
                <li>
                    <a href="index.php" class="navbar-link" data-nav-link>Contact Us</a>
                </li>
                <li>
                    <a href="login.php" class="navbar-link active_link" data-nav-link>Log In</a>
                </li>
                <li>
                    <a href="signup.php" class="navbar-link" data-nav-link>Sign Up</a>
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
<section class="modern-login-section">
    <form class="modern-login-form" id="loginForm" method="POST">
        <input type="hidden" name="action" value="login">
        <div class="modern-login-switch">
            <span>Don't have an account?</span>
            <a href="signup.php" class="modern-login-signup-link">Sign Up</a>
        </div>
        <h2 class="modern-login-title">
            <ion-icon name="log-in-outline"></ion-icon> Login
        </h2>
        <div class="modern-login-field">
            <label for="login-email">
                <ion-icon name="mail-outline"></ion-icon>
            </label>
            <input type="email" id="login-email" name="email" placeholder="Email" required autocomplete="email">
        </div>
        <div class="modern-login-field">
            <label for="login-password">
                <ion-icon name="lock-closed-outline"></ion-icon>
            </label>
            <input type="password" id="login-password" name="password" placeholder="Password" required autocomplete="current-password">
            <span class="modern-login-eye" id="togglePassword" title="Show/Hide Password">
                <ion-icon name="eye-outline" id="eyeIcon"></ion-icon>
            </span>
        </div>
        <div class="modern-login-forgot">
            <a href="#" class="modern-login-forgot-link" id="forgotPasswordLink">Forgot Password?</a>
        </div>
        <button type="submit" class="modern-login-btn">Login</button>
    </form>
    <div id="login-message" class="modern-login-message" style="display:none;"></div>
</section>
<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="reset-password-modal">
    <div class="reset-password-modal-content">
        <button class="reset-password-close" id="resetPasswordClose">&times;</button>
        <h3>Reset Password</h3>
        <p>Please enter your email address to request a password reset link.</p>
        <input type="email" id="resetEmail" placeholder="Enter your email" required>
        <button id="resetSubmitBtn">Submit</button>
    </div>
</div>

<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showMessage('<?php echo addslashes($error); ?>', 'error');
        });
    </script>
<?php endif; ?>
<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            showMessage('<?php echo addslashes($success); ?>', 'success');
        });
    </script>
<?php endif; ?>

<script>
    'use strict';

    // Navbar toggle
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
    navbarLinks.forEach(link => link.addEventListener("click", navToggleFunc));

    // Header active on scroll
    const header = document.querySelector("[data-header]");
    window.addEventListener("scroll", function () {
        window.scrollY >= 10 ? header.classList.add("active")
            : header.classList.remove("active");
    });

    // Login Form and Password Toggle
    const loginForm = document.getElementById('loginForm');
    const passwordInput = document.getElementById('login-password');
    const togglePassword = document.getElementById('togglePassword');
    const eyeIcon = document.getElementById('eyeIcon');
    const messageBox = document.getElementById('login-message');

    togglePassword.addEventListener('click', () => {
        const type = passwordInput.type === "password" ? "text" : "password";
        passwordInput.type = type;
        eyeIcon.setAttribute('name', type === "password" ? "eye-outline" : "eye-off-outline");
    });
    loginForm.addEventListener('submit', function(e) {
        const email = loginForm.email.value.trim();
        const password = loginForm.password.value;
        if (!email || !password) {
            e.preventDefault();
            showMessage('Email and password are required.', 'error');
            return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            showMessage('Please enter a valid email address.', 'error');
            return;
        }
    });
    function showMessage(text, type) {
        messageBox.textContent = text;
        messageBox.className = `modern-login-message show ${type}`;
        messageBox.style.display = 'block';
        setTimeout(() => {
            messageBox.classList.remove("show");
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 300);
        }, 3000);
    }

    // Reset Password Modal Functionality
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const resetPasswordModal = document.getElementById('resetPasswordModal');
    const resetPasswordClose = document.getElementById('resetPasswordClose');
    const resetSubmitBtn = document.getElementById('resetSubmitBtn');
    const resetEmailInput = document.getElementById('resetEmail');

    forgotPasswordLink.addEventListener('click', function(e) {
        e.preventDefault();
        resetPasswordModal.classList.add('show');
    });
    resetPasswordClose.addEventListener('click', function() {
        resetPasswordModal.classList.remove('show');
    });
    resetPasswordModal.addEventListener('click', function(e) {
        if (e.target === resetPasswordModal) {
            resetPasswordModal.classList.remove('show');
        }
    });
    resetSubmitBtn.addEventListener('click', function() {
        const resetEmail = resetEmailInput.value.trim();
        if (!resetEmail) {
            alert('Please enter your email address.');
            return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(resetEmail)) {
            alert('Please enter a valid email address.');
            return;
        }
        // Submit reset request via AJAX or form submission.
        // For an AJAX call, you could do something like:
        fetch('../config/reset_password_request.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `email=${encodeURIComponent(resetEmail)}`
        })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('A password reset link has been sent to your email.');
                } else {
                    alert(data.error || 'Failed to send reset link.');
                }
                resetPasswordModal.classList.remove('show');
                resetEmailInput.value = '';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                resetPasswordModal.classList.remove('show');
            });
    });
</script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
<script src="../assets/js/main.js"></script>
</body>
</html>