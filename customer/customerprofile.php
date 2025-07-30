<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../public/login.php");
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$userData = getUserById($userId);

if (!$userData) {
    // If user data can't be fetched, redirect to login
    header("Location: ../public/login.php");
    exit();
}

/**
 * Enhanced helper function to get a valid profile image path.
 * Ensures it always fetches from the registration photo directory.
 *
 * @param array $userData The user's data array.
 * @param string $defaultImage The path to the default image.
 * @return string The valid image path.
 */
function get_profile_image_path($userData, $defaultImage) {
    if (!empty($userData['profile_image'])) {
        // Multiple path possibilities to check, prioritizing the registration upload directory
        $possiblePaths = [
            // Direct registration upload path
            '../assets/uploads/profiles/' . basename($userData['profile_image']),
            // Full stored path from database
            $userData['profile_image'],
            // Alternative registration path construction
            '../assets/uploads/profiles/' . str_replace('../assets/uploads/profiles/', '', $userData['profile_image'])
        ];

        foreach ($possiblePaths as $imagePath) {
            if (file_exists($imagePath)) {
                return htmlspecialchars($imagePath);
            }
        }
    }
    // Fallback to default image if no valid profile image found
    return $defaultImage;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = $_POST['date_of_birth'];

        // Handle profile image upload
        $profileImagePath = $userData['profile_image']; // Keep existing image by default

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                    // Delete old profile image if it exists and is not a default image
                    if ($userData['profile_image'] && file_exists($userData['profile_image'])) {
                        unlink($userData['profile_image']);
                    }
                    $profileImagePath = $uploadPath;
                }
            }
        }

        // Update user profile using your updateUserProfile() function.
        // Make sure your updateUserProfile function is defined in your included db.php or elsewhere.
        if (updateUserProfile($userId, $firstName, $lastName, $email, $phone, $dob, $profileImagePath)) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating profile. Please try again.']);
        }
        exit();
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validate passwords
        if (strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
            exit();
        }

        // Verify current password
        if (!password_verify($currentPassword, $userData['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
            exit();
        }

        // Update password using your updateUserPassword() function.
        if (updateUserPassword($userId, $newPassword)) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error changing password. Please try again.']);
        }
        exit();
    }

    if ($action === 'delete_account') {
        // Delete user account using your deleteUser() function.
        if (deleteUser($userId)) {
            // Destroy session
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Account deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting account. Please try again.']);
        }
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>DASHBOARD</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="assets/img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
    <link href="../assets/lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">

    <!-- Sidebar Start -->
    <div class="sidebar pe-4 pb-3">
        <nav class="navbar bg-light navbar-light">
            <a href="customerdashboard.php" class="navbar-brand mx-4 mb-3">
                <h3 class="text-primary"><i class="fa fa-hashtag me-2"></i>DASHBOARD</h3>
            </a>
            <div class="d-flex align-items-center ms-4 mb-4">
                <div class="position-relative">
                    <img class="rounded-circle" src="<?php echo get_profile_image_path($userData, '../assets/images/car-5.jpg'); ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover;">
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="customerdashboard.php" class="nav-item nav-link">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="customerbookings.php" class="nav-item nav-link ">
                    <i class="fa fa-calendar-check me-2"></i>My Bookings
                </a>
                <a href="customermessage.php" class="nav-item nav-link" id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>
                <a href="customerform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Submit Form
                </a>
                <a href="customerprofile.php" class="nav-item nav-link active">
                    <i class="fa fa-user-circle me-2"></i>Profile
                </a>
            </div>
        </nav>
    </div>
    <!-- Sidebar End -->

    <!-- Content Start -->
    <div class="content">
        <!-- Navbar Start -->
        <nav class="navbar navbar-expand bg-light navbar-light sticky-top px-4 py-0">
            <a href="managerdashboard.php" class="navbar-brand d-flex d-lg-none me-4">
                <h2 class="text-primary mb-0"><i class="fa fa-hashtag"></i></h2>
            </a>
            <a href="#" class="sidebar-toggler flex-shrink-0">
                <i class="fa fa-bars"></i>
            </a>
            <form class="d-none d-md-flex ms-4">
                <input class="form-control border-0" type="search" placeholder="Search">
            </form>
            <div class="navbar-nav align-items-center ms-auto">

                <div class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img class="rounded-circle me-lg-2" src="<?php echo get_profile_image_path($userData, '../assets/images/car-1.jpg'); ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover;">
                        <span class="d-none d-lg-inline-flex"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                        <a href="../Both/homepage.php" class="dropdown-item">Home Page</a>
                        <a href="../public/logout.php" class="dropdown-item">Log Out</a>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Navbar End -->

        <div class="container-fluid pt-4 px-4">
            <div class="row justify-content-center">
                <!-- Profile Edit (Left) -->
                <div class="col-12 col-md-5 mb-4">
                    <div class="bg-light rounded p-4 h-100">
                        <h4 class="mb-4 text-center text-primary">
                            <i class="fa fa-user-edit me-2"></i>Edit Profile
                        </h4>
                        <div class="d-flex flex-column align-items-center mb-4">
                            <div class="position-relative mb-2">
                                <img id="profileEditImgPreview" src="<?php echo get_profile_image_path($userData, '../assets/images/blog-4.jpg'); ?>" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;" alt="Profile">
                                <label class="btn btn-sm btn-outline-primary position-absolute bottom-0 end-0 m-1" style="border-radius:50%;" title="Change photo">
                                    <i class="fa fa-camera"></i>
                                    <input type="file" accept="image/*" class="d-none" id="profileEditImgInput" onchange="handleProfileImgChange(event)">
                                </label>
                            </div>
                            <div>
                                <span class="badge <?php echo $userData['user_type'] === 'admin' ? 'bg-danger' : ($userData['user_type'] === 'manager' ? 'bg-warning' : 'bg-primary'); ?>" id="profileEditType" style="font-size: 1rem;"><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                            </div>
                        </div>
                        <form id="profileEditForm" onsubmit="saveProfileEdit(); return false;">
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-user me-1 text-primary"></i>First Name</label>
                                <input type="text" class="form-control" id="profileEditFirst" value="<?php echo htmlspecialchars($userData['first_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-user me-1 text-primary"></i>Last Name</label>
                                <input type="text" class="form-control" id="profileEditLast" value="<?php echo htmlspecialchars($userData['last_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-envelope me-1 text-primary"></i>Email</label>
                                <input type="email" class="form-control" id="profileEditEmail" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-phone me-1 text-primary"></i>Phone Number</label>
                                <input type="text" class="form-control" id="profileEditPhone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-birthday-cake me-1 text-primary"></i>Date of Birth</label>
                                <input type="date" class="form-control" id="profileEditDob" value="<?php echo htmlspecialchars($userData['date_of_birth'] ?? ''); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="fa fa-save me-2"></i>Save Changes
                            </button>
                            <div id="profileEditSuccessMsg" class="alert alert-success py-2 px-3 d-none mt-2" role="alert"></div>
                            <div id="profileEditErrorMsg" class="alert alert-danger py-2 px-3 d-none mt-2" role="alert"></div>
                        </form>
                    </div>
                </div>
                <!-- Change Password and Delete Account (Right) -->
                <div class="col-12 col-md-7">
                    <div class="bg-light rounded p-4 mb-4">
                        <h5 class="mb-4 text-primary">
                            <i class="fa fa-lock me-2"></i>Change Password
                        </h5>
                        <form id="passwordChangeForm" onsubmit="changePassword(); return false;">
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-lock me-1"></i>Current Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="currentPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" tabindex="-1" onclick="togglePassword('currentPassword', this)">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-lock me-1"></i>New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="newPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" tabindex="-1" onclick="togglePassword('newPassword', this)">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><i class="fa fa-lock me-1"></i>Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmPassword" required>
                                    <button class="btn btn-outline-secondary" type="button" tabindex="-1" onclick="togglePassword('confirmPassword', this)">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="fa fa-key me-2"></i>Change Password
                            </button>
                            <div id="passwordChangeSuccessMsg" class="alert alert-success py-2 px-3 d-none mt-2" role="alert"></div>
                            <div id="passwordChangeErrorMsg" class="alert alert-danger py-2 px-3 d-none mt-2" role="alert"></div>
                        </form>
                    </div>
                    <div class="bg-light rounded p-4 mt-4">
                        <h5 class="mb-3 text-danger">
                            <i class="fa fa-trash me-2"></i>Delete Account
                        </h5>
                        <p class="mb-2 small">
                            Type <b>DELETE</b> below to confirm. This action cannot be undone.
                        </p>
                        <input type="text" class="form-control mb-2" id="deleteConfirmInput" placeholder="Type DELETE to confirm" oninput="checkDeleteInput()">
                        <button id="deleteProfileBtn" class="btn btn-outline-danger w-100 mb-2" onclick="showDeleteConfirmModal()" disabled>
                            <i class="fa fa-trash me-2"></i>Delete My Account
                        </button>
                        <div id="profileDeleteSuccessMsg" class="alert alert-success py-2 px-3 d-none mt-2" role="alert"></div>
                        <div id="profileDeleteErrorMsg" class="alert alert-danger py-2 px-3 d-none mt-2" role="alert"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal for Account -->
        <div class="modal fade" id="deleteConfirmModalAccount" tabindex="-1" aria-labelledby="deleteConfirmModalAccountLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title" id="deleteConfirmModalAccountLabel">
                            <i class="fa fa-exclamation-triangle text-warning me-2"></i>Confirm Account Deletion
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <p class="mb-0">Are you sure you want to delete your account? This action cannot be undone and will permanently delete all your data.</p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtnAccount">Yes, Delete My Account</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Profile data from PHP
            const myProfile = {
                firstName: "<?php echo htmlspecialchars($userData['first_name']); ?>",
                lastName: "<?php echo htmlspecialchars($userData['last_name']); ?>",
                email: "<?php echo htmlspecialchars($userData['email']); ?>",
                phone: "<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>",
                dob: "<?php echo htmlspecialchars($userData['date_of_birth'] ?? ''); ?>",
                userType: "<?php echo htmlspecialchars($userData['user_type']); ?>",
                img: "<?php echo get_profile_image_path($userData, '../assets/images/blog-4.jpg'); ?>"
            };

            let selectedProfileImage = null;

            // --- Profile Edit ---
            function handleProfileImgChange(e) {
                if (e.target.files && e.target.files[0]) {
                    selectedProfileImage = e.target.files[0];
                    document.getElementById('profileEditImgPreview').src = URL.createObjectURL(e.target.files[0]);
                }
            }

            function updateUnreadNavBadge() {
                fetch('fetch_unread_total.php')
                    .then(res => res.text())
                    .then(count => {
                        count = parseInt(count, 10);
                        let badge = document.getElementById('navUnreadBadge');
                        if (!badge) return;
                        badge.style.display = count > 0 ? 'inline-block' : 'none';
                        badge.textContent = count;
                    });
            }
            window.addEventListener('DOMContentLoaded', updateUnreadNavBadge);

            async function saveProfileEdit() {
                const formData = new FormData();
                formData.append('action', 'update_profile');
                formData.append('first_name', document.getElementById('profileEditFirst').value);
                formData.append('last_name', document.getElementById('profileEditLast').value);
                formData.append('email', document.getElementById('profileEditEmail').value);
                formData.append('phone', document.getElementById('profileEditPhone').value);
                formData.append('date_of_birth', document.getElementById('profileEditDob').value);

                if (selectedProfileImage) {
                    formData.append('profile_image', selectedProfileImage);
                }

                try {
                    // Use current file name "customerprofile.php" for the AJAX request
                    const response = await fetch('customerprofile.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage('profileEditSuccessMsg', true, result.message);
                        showMessage('profileEditErrorMsg', false);
                        // Reload page to update all profile images and data everywhere
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showMessage('profileEditSuccessMsg', false);
                        showMessage('profileEditErrorMsg', true, result.message || 'An unknown error occurred.');
                    }
                } catch (error) {
                    showMessage('profileEditSuccessMsg', false);
                    showMessage('profileEditErrorMsg', true, 'Error updating profile. Please try again.');
                }
            }

            function showMessage(elemId, show, msg = '') {
                const el = document.getElementById(elemId);
                if (show) {
                    el.textContent = msg;
                    el.classList.remove('d-none');
                    setTimeout(() => el.classList.add('d-none'), 3000);
                } else {
                    el.classList.add('d-none');
                }
            }

            // --- Password Change ---
            function togglePassword(id, btn) {
                const input = document.getElementById(id);
                const icon = btn.querySelector('i');
                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.remove("fa-eye");
                    icon.classList.add("fa-eye-slash");
                } else {
                    input.type = "password";
                    icon.classList.remove("fa-eye-slash");
                    icon.classList.add("fa-eye");
                }
            }

            async function changePassword() {
                const currentPassword = document.getElementById('currentPassword').value;
                const newPassword = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;

                if (newPassword.length < 6) {
                    showMessage('passwordChangeSuccessMsg', false);
                    showMessage('passwordChangeErrorMsg', true, "Password must be at least 6 characters.");
                    return;
                }
                if (newPassword !== confirmPassword) {
                    showMessage('passwordChangeSuccessMsg', false);
                    showMessage('passwordChangeErrorMsg', true, "Passwords do not match.");
                    return;
                }
                if (!currentPassword) {
                    showMessage('passwordChangeSuccessMsg', false);
                    showMessage('passwordChangeErrorMsg', true, "Please enter your current password.");
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'change_password');
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                formData.append('confirm_password', confirmPassword);

                try {
                    // Use current file "customerprofile.php"
                    const response = await fetch('customerprofile.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage('passwordChangeSuccessMsg', true, result.message);
                        showMessage('passwordChangeErrorMsg', false);
                        document.getElementById('passwordChangeForm').reset();
                    } else {
                        showMessage('passwordChangeSuccessMsg', false);
                        showMessage('passwordChangeErrorMsg', true, result.message || 'An unknown error occurred.');
                    }
                } catch (error) {
                    showMessage('passwordChangeSuccessMsg', false);
                    showMessage('passwordChangeErrorMsg', true, 'Error changing password. Please try again.');
                }
            }

            // --- Delete Profile ---
            function checkDeleteInput() {
                const v = document.getElementById('deleteConfirmInput').value;
                document.getElementById('deleteProfileBtn').disabled = (v !== "DELETE");
            }

            function showDeleteConfirmModal() {
                if (document.getElementById('deleteConfirmInput').value !== "DELETE") {
                    showMessage('profileDeleteErrorMsg', true, "Please type DELETE to confirm.");
                    return;
                }
                var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModalAccount'));
                deleteModal.show();
            }

            document.getElementById('confirmDeleteBtnAccount').addEventListener('click', function() {
                deleteProfile();
                bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModalAccount')).hide();
            });

            async function deleteProfile() {
                const formData = new FormData();
                formData.append('action', 'delete_account');

                try {
                    // Use current file "customerprofile.php"
                    const response = await fetch('customerprofile.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage('profileDeleteSuccessMsg', true, result.message);
                        showMessage('profileDeleteErrorMsg', false);
                        document.getElementById('deleteProfileBtn').disabled = true;
                        // Redirect to signup page after successful deletion
                        setTimeout(() => {
                            window.location.href = '../public/signup.php';
                        }, 2000);
                    } else {
                        showMessage('profileDeleteSuccessMsg', false);
                        showMessage('profileDeleteErrorMsg', true, result.message || 'An unknown error occurred.');
                    }
                } catch (error) {
                    showMessage('profileDeleteSuccessMsg', false);
                    showMessage('profileDeleteErrorMsg', true, 'Error deleting account. Please try again.');
                }
            }
        </script>

        <!-- Footer Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">
                    <div class="col-12 col-sm-6 text-center text-sm-start"></div>
                </div>
            </div>
        </div>
        <!-- Footer End -->
    </div>
    <!-- Content End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="bi bi-arrow-up"></i></a>
</div>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/lib/chart/chart.min.js"></script>
<script src="../assets/lib/easing/easing.min.js"></script>
<script src="../assets/lib/waypoints/waypoints.min.js"></script>
<script src="../assets/lib/owlcarousel/owl.carousel.min.js"></script>
<script src="../assets/lib/tempusdominus/js/moment.min.js"></script>
<script src="../assets/lib/tempusdominus/js/moment-timezone.min.js"></script>
<script src="../assets/lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
<script>
    function updateUnreadBadges() {
        fetch("get_unread_counts.php")
            .then(response => response.json())
            .then(data => {
                document.querySelectorAll("#userListMsg .user-list-item").forEach(item => {
                    const senderId = item.getAttribute("data-id");
                    const badge = item.querySelector(".unread-badge");
                    const userNameSpan = item.querySelector(".user-name");
                    if (data[senderId] && data[senderId] > 0) {
                        badge.textContent = data[senderId];
                        badge.style.display = "inline-block";
                        if (userNameSpan) userNameSpan.style.fontWeight = "bold";
                    } else {
                        badge.textContent = "";
                        badge.style.display = "none";
                        if (userNameSpan) userNameSpan.style.fontWeight = "normal";
                    }
                });
                let totalUnread = Object.values(data).reduce((a, b) => a + b, 0);
                const navBadge = document.getElementById("navUnreadBadge");
                if (totalUnread > 0) {
                    navBadge.textContent = totalUnread;
                    navBadge.style.display = "inline-block";
                    navBadge.style.backgroundColor = "green";
                } else {
                    navBadge.textContent = "";
                    navBadge.style.display = "none";
                }
            })
            .catch(error => console.error("Error fetching unread counts:", error));
    }
    setInterval(updateUnreadBadges, 15000);
    document.addEventListener("DOMContentLoaded", () => {
        updateUnreadBadges();
    });
</script>

<!-- Template Javascript -->
<script src="../assets/js/dashboard.js"></script>
</body>
</html>