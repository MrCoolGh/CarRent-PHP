<?php
session_start();
require_once '../config/db.php'; // Assumes db.php is in a 'config' folder one level up

// --- ENHANCED HELPER FUNCTION FOR PROFILE IMAGE HANDLING ---

/**
 * Enhanced helper function to get a valid profile image path for HTML display.
 * Ensures it always fetches from the registration photo directory.
 *
 * @param string|null $imagePath The path from the database.
 * @param string $defaultImage The path to the default image.
 * @return string The valid, sanitized image path.
 */
if (!function_exists('get_profile_image_path')) {
    function get_profile_image_path($imagePath, $defaultImage) {
        if (!empty($imagePath)) {
            // Multiple path possibilities to check, prioritizing the registration upload directory
            $possiblePaths = [
                // Direct registration upload path
                '../assets/uploads/profiles/' . basename($imagePath),
                // Full stored path from database
                $imagePath,
                // Alternative registration path construction
                '../assets/uploads/profiles/' . str_replace('../assets/uploads/profiles/', '', $imagePath),
                // Handle cases where only filename is stored
                '../assets/uploads/profiles/' . $imagePath
            ];

            foreach ($possiblePaths as $testPath) {
                if (file_exists($testPath)) {
                    return htmlspecialchars($testPath);
                }
            }
        }
        return $defaultImage;
    }
}

/**
 * Enhanced function to handle profile image upload to registration directory.
 *
 * @param array $fileData The $_FILES array data for the uploaded file.
 * @param int $userId User ID for unique filename generation.
 * @param string|null $oldImagePath Previous image path to delete.
 * @return string|null The new image path or null on failure.
 */
function handle_profile_image_upload($fileData, $userId, $oldImagePath = null) {
    if (!isset($fileData) || $fileData['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExtension = strtolower(pathinfo($fileData['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        return null;
    }

    // Ensure the registration upload directory exists
    $uploadDir = '../assets/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $fileName;

    // Move the uploaded file
    if (move_uploaded_file($fileData['tmp_name'], $uploadPath)) {
        // Delete old profile image if it exists
        if ($oldImagePath && file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
        return $uploadPath;
    }

    return null;
}

// --- SECURITY CHECK & SESSION SETUP ---
// Check if user is logged in and is manager
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'manager') {
    header("Location: ../public/login.php");
    exit();
}

// Get user information from database
$userId = $_SESSION['user_id'];
$userData = getUserById($userId);

if (!$userData) {
    // If user data can't be fetched, redirect to login
    header("Location: ../public/login.php");
    exit();
}

// Fetch logged-in manager's data for the navbar/sidebar
$adminData = [
    'first_name' => $userData['first_name'],
    'last_name'  => $userData['last_name'],
    'user_type'  => $userData['user_type'],
    'profile_image' => get_profile_image_path($userData['profile_image'] ?? null, '../assets/images/blog-5.jpg')
];

// --- AJAX REQUEST HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!$conn || $conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection error: ' . ($conn->connect_error ?? 'Unknown Error')]);
        exit();
    }

    $action = $_POST['action'];

    switch ($action) {
        case 'get_users':
            $search = $_POST['search'] ?? '';
            $query = "SELECT id, first_name, last_name, email, phone, date_of_birth, user_type, created_at, profile_image 
                      FROM users 
                      WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $searchTerm = "%{$search}%";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            $users = $result->fetch_all(MYSQLI_ASSOC);
            // Filter out users with user_type as admin
            $users = array_values(array_filter($users, function($user) {
                return strtolower($user['user_type']) !== 'admin';
            }));
            // Enhance user data with proper image paths
            foreach ($users as &$user) {
                $user['profile_image'] = get_profile_image_path($user['profile_image'], '../assets/images/blog-2.jpg');
            }
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'add_user':
            $firstName = trim($_POST['first_name']);
            $lastName  = trim($_POST['last_name']);
            $email     = trim($_POST['email']);
            $phone     = trim($_POST['phone']);
            $dob       = $_POST['date_of_birth'];
            $userType  = $_POST['user_type'];
            $password  = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // Enhanced profile image upload handling
            $profileImagePath = null;
            if (isset($_FILES['profile_image'])) {
                $profileImagePath = handle_profile_image_upload($_FILES['profile_image'], 0); // Temporary user ID
                if ($profileImagePath === null && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
                    exit();
                }
            }

            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, date_of_birth, user_type, password, profile_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssssssss", $firstName, $lastName, $email, $phone, $dob, $userType, $password, $profileImagePath);

            if ($stmt->execute()) {
                $newUserId = $conn->insert_id;
                // Update the image path with actual user ID if image was uploaded
                if ($profileImagePath) {
                    $newImagePath = handle_profile_image_upload($_FILES['profile_image'], $newUserId, $profileImagePath);
                    if ($newImagePath) {
                        $updateStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $updateStmt->bind_param("si", $newImagePath, $newUserId);
                        $updateStmt->execute();
                    }
                }
                echo json_encode(['success' => true, 'message' => 'User added successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add user. ' . $stmt->error]);
            }
            break;

        case 'get_single_user':
            $userId = (int)$_POST['id'];
            $user = getUserById($userId);
            if ($user) {
                // Enhance user data with proper image path
                $user['profile_image'] = get_profile_image_path($user['profile_image'], '../assets/images/blog-2.jpg');
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'message' => 'User not found.']);
            }
            break;

        case 'update_user':
            $userId    = (int)$_POST['id'];
            $firstName = trim($_POST['first_name']);
            $lastName  = trim($_POST['last_name']);
            $email     = trim($_POST['email']);
            $phone     = trim($_POST['phone']);
            $dob       = $_POST['date_of_birth'];
            $userType  = $_POST['user_type'];

            $currentUser = getUserById($userId);
            $profileImagePath = $currentUser['profile_image'];

            // Enhanced profile image upload handling
            if (isset($_FILES['profile_image'])) {
                $newImagePath = handle_profile_image_upload($_FILES['profile_image'], $userId, $profileImagePath);
                if ($newImagePath !== null) {
                    $profileImagePath = $newImagePath;
                } elseif ($_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
                    exit();
                }
            }

            // Update user information without modifying the password
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, date_of_birth=?, user_type=?, profile_image=? WHERE id=?");
            $stmt->bind_param("sssssssi", $firstName, $lastName, $email, $phone, $dob, $userType, $profileImagePath, $userId);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user. ' . $stmt->error]);
            }
            break;

        case 'delete_user':
            // This action is no longer used as delete button is removed.
            echo json_encode(['success' => false, 'message' => 'Delete function is disabled.']);
            break;
    }

    $conn->close();
    exit(); // IMPORTANT: Stop script execution after AJAX handling
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>DASHBOARD - Manage Users</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="../assets/img/favicon.ico" rel="icon">

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
            <a href="managerdashboard.php" class="navbar-brand mx-4 mb-3">
                <h3 class="text-primary"><i class="fa fa-hashtag me-2"></i>DASHBOARD</h3>
            </a>
            <div class="d-flex align-items-center ms-4 mb-4">
                <div class="position-relative">
                    <img class="rounded-circle" src="<?php echo $adminData['profile_image']; ?>" alt="Admin Profile" style="width: 40px; height: 40px; object-fit: cover;">
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($adminData['first_name'] . ' ' . $adminData['last_name']); ?></h6>
                    <span><?php echo htmlspecialchars(ucfirst($adminData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="managerdashboard.php" class="nav-item nav-link">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>

                <a href="managerbookings.php" class="nav-item nav-link ">
                    <i class="fa fa-calendar-check me-2"></i>Bookings
                </a>
                <a href="managermessage.php" class="nav-item nav-link " id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>

                <a href="managerform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Forms
                </a>

                <a href="managermanagecars.php" class="nav-item nav-link">
                    <i class="fa fa-car-side me-2"></i>Manage Cars
                </a>
                <a href="managermanageusers.php" class="nav-item nav-link active">
                    <i class="fa fa-users-cog me-2"></i>Manage Users
                </a>
                <a href="managerprofile.php" class="nav-item nav-link">
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
                        <img class="rounded-circle me-lg-2" src="<?php echo $adminData['profile_image']; ?>" alt="" style="width: 40px; height: 40px; object-fit: cover;">
                        <span class="d-none d-lg-inline-flex"><?php echo htmlspecialchars($adminData['first_name'] . ' ' . $adminData['last_name']); ?></span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end bg-light border-0 rounded-0 rounded-bottom m-0">
                        <a href="../Both/homepage.php" class="dropdown-item">Home Page</a>
                        <a href="../public/logout.php" class="dropdown-item">Log Out</a>
                    </div>
                </div>
            </div>
        </nav>
        <!-- Navbar End -->

        <!-- Manage Users Table Start -->
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light text-center rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Manage Users</h6>
                    <div class="input-group" style="max-width: 300px;">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="userSearchInput" placeholder="Search users..." onkeyup="searchUsers()">
                    </div>
                    <button class="btn btn-success btn-sm" onclick="showAddUserModal()">+ Add User</button>
                </div>
                <div id="globalSuccessMsg" class="alert alert-success py-2 px-3 d-none" role="alert"></div>
                <div id="globalErrorMsg" class="alert alert-danger py-2 px-3 d-none" role="alert"></div>
                <div class="table-responsive">
                    <table class="table text-start align-middle table-bordered table-hover mb-0" id="userTable">
                        <thead>
                        <tr class="text-dark">
                            <th scope="col"><input class="form-check-input" type="checkbox"></th>
                            <th scope="col">Profile</th>
                            <th scope="col">First Name</th>
                            <th scope="col">Last Name</th>
                            <th scope="col">Email</th>
                            <th scope="col">Phone</th>
                            <th scope="col">DOB</th>
                            <th scope="col">User Type</th>
                            <th scope="col">Joined</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody id="userTableBody">
                        <!-- JS will populate this -->
                        <tr>
                            <td colspan="10" class="text-center">Loading users...</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Manage Users Table End -->

        <!-- Add User Modal -->
        <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" id="addUserForm" onsubmit="saveNewUser(event)">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <img id="addUserImgPreview" src="../assets/images/blog-3.jpg" alt="Profile Preview" class="rounded-circle mb-2" style="width: 60px; height: 60px; object-fit:cover;">
                            <input type="file" class="form-control mt-2" id="addUserImg" name="profile_image" accept="image/*">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter user password" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">User Type</label>
                                <select name="user_type" class="form-select" required>
                                    <option value="customer">Customer</option>
                                    <option value="manager">Manager</option>
                                    <!-- Admin option removed from add user modal -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add User</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Add User Modal -->

        <!-- Edit User Modal -->
        <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" id="editUserForm" onsubmit="saveEditUser(event)">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit User Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="editUserId" name="id">
                        <div class="mb-3 text-center">
                            <img id="editUserImgPreview" src="../assets/images/car-1.jpg" alt="Profile Preview" class="rounded-circle mb-2" style="width: 60px; height: 60px; object-fit:cover;">
                            <input type="file" class="form-control mt-2" id="editUserImg" name="profile_image" accept="image/*">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" id="editUserFirstName" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" id="editUserLastName" name="last_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" id="editUserEmail" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" id="editUserPhone" name="phone" class="form-control" required>
                            </div>
                        </div>
                        <!-- Removed Password field from edit user modal -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" id="editUserDOB" name="date_of_birth" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">User Type</label>
                                <select id="editUserType" name="user_type" class="form-select" required>
                                    <option value="customer">Customer</option>
                                    <option value="manager">Manager</option>
                                    <!-- Admin option removed from edit user modal -->
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Edit User Modal -->

        <!-- View User Modal -->
        <div class="modal fade" id="viewUserModal" tabindex="-1" aria-labelledby="viewUserModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewUserModalLabel">
                            <i class="fa fa-user me-2"></i>User Details
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                <img id="viewUserImg" src="../assets/images/blog-4.jpg" alt="Profile" class="rounded-circle mb-3" style="width: 120px; height: 120px; object-fit:cover;">
                                <h5 id="viewUserFullName"></h5>
                                <span class="badge bg-info" id="viewUserType"></span>
                            </div>
                            <div class="col-md-8">
                                <h6 class="text-primary mb-3"><i class="fa fa-info-circle me-2"></i>Personal Information</h6>
                                <table class="table table-borderless">
                                    <tbody>
                                    <tr>
                                        <th style="width: 30%;"><i class="fa fa-envelope me-2 text-primary"></i>Email:</th>
                                        <td id="viewUserEmail"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fa fa-phone me-2 text-primary"></i>Phone:</th>
                                        <td id="viewUserPhone"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fa fa-birthday-cake me-2 text-primary"></i>Date of Birth:</th>
                                        <td id="viewUserDOB"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fa fa-calendar me-2 text-primary"></i>Joined Date:</th>
                                        <td id="viewUserJoined"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fa fa-clock me-2 text-primary"></i>Last Login:</th>
                                        <td id="viewUserLastLogin"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fa fa-id-badge me-2 text-primary"></i>User ID:</th>
                                        <td id="viewUserId"></td>
                                    </tr>
                                    <tr>
                                        <th><i class="fa fa-user-tag me-2 text-primary"></i>Account Status:</th>
                                        <td><span class="badge bg-success">Active</span></td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="showEditUserModalFromView()">
                            <i class="fa fa-edit me-1"></i>Edit User
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End View User Modal -->

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
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top">
        <i class="bi bi-arrow-up"></i>
    </a>
</div>

<!-- Main Consolidated Script -->
<script>
    const defaultProfileImg = "../assets/images/blog-2.jpg";
    let currentViewUserId = null;

    // ----- UTILITY FUNCTIONS -----
    function showGlobalMessage(msg, isSuccess = true) {
        const successMsgDiv = document.getElementById("globalSuccessMsg");
        const errorMsgDiv = document.getElementById("globalErrorMsg");
        const msgDiv = isSuccess ? successMsgDiv : errorMsgDiv;

        (isSuccess ? errorMsgDiv : successMsgDiv).classList.add('d-none');
        msgDiv.textContent = msg;
        msgDiv.classList.remove('d-none');
        setTimeout(() => {
            msgDiv.classList.add('d-none');
        }, 3000);
    }

    // ----- API CALLS -----
    async function fetchUsers(searchTerm = '') {
        const formData = new FormData();
        formData.append('action', 'get_users');
        formData.append('search', searchTerm);

        try {
            const response = await fetch('managermanageusers.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                renderUserTable(result.users);
            } else {
                showGlobalMessage(result.message || 'Could not fetch users.', false);
                document.getElementById("userTableBody").innerHTML = `<tr><td colspan="10" class="text-center text-danger">Error: ${result.message || 'Could not fetch users.'}</td></tr>`;
            }
        } catch (error) {
            showGlobalMessage('A critical error occurred while fetching users. Check the console for details.', false);
            console.error("Fetch Error:", error);
        }
    }

    // ----- TABLE & UI RENDERING -----
    function renderUserTable(users) {
        const tbody = document.getElementById("userTableBody");
        tbody.innerHTML = "";
        if (users.length === 0) {
            tbody.innerHTML = "<tr><td colspan='10' class='text-center'>No users found.</td></tr>";
            return;
        }
        users.forEach(u => {
            // Delete button and column removed (only View and Edit buttons remain)
            const profileImgPath = u.profile_image ? u.profile_image : defaultProfileImg;
            const userType = u.user_type || 'customer';
            const badgeClass = userType === 'admin' ? 'bg-danger' : userType === 'manager' ? 'bg-warning' : 'bg-primary';
            const typeText = userType.charAt(0).toUpperCase() + userType.slice(1);
            tbody.innerHTML += `
                <tr id="userRow${u.id}">
                    <td><input class="form-check-input" type="checkbox"></td>
                    <td><img src="${profileImgPath}" alt="Profile" class="rounded-circle" style="width: 40px; height: 40px; object-fit:cover;"></td>
                    <td>${u.first_name}</td>
                    <td>${u.last_name}</td>
                    <td>${u.email}</td>
                    <td>${u.phone}</td>
                    <td>${u.date_of_birth}</td>
                    <td><span class="badge ${badgeClass}">${typeText}</span></td>
                    <td>${new Date(u.created_at).toLocaleDateString()}</td>
                    <td>
                        <button class="btn btn-sm btn-info mb-1" onclick="showViewUserModal(${u.id})">View</button>
                        <button class="btn btn-sm btn-primary mb-1" onclick="showEditUserModal(${u.id})">Edit</button>
                    </td>
                </tr>
            `;
        });
    }

    // ----- SEARCH -----
    function searchUsers() {
        const searchTerm = document.getElementById('userSearchInput').value;
        fetchUsers(searchTerm);
    }

    // ----- MODAL HANDLING (ADD) -----
    function showAddUserModal() {
        const form = document.getElementById("addUserForm");
        form.reset();
        document.getElementById("addUserImgPreview").src = defaultProfileImg;
        var modal = new bootstrap.Modal(document.getElementById('addUserModal'));
        modal.show();
    }

    async function saveNewUser(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'add_user');

        try {
            const response = await fetch('managermanageusers.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showGlobalMessage(result.message);
                bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide();
                fetchUsers(); // Refresh the table
            } else {
                showGlobalMessage(result.message || 'Failed to add user.', false);
            }
        } catch (error) {
            showGlobalMessage('An error occurred.', false);
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

    // ----- MODAL HANDLING (EDIT) -----
    async function showEditUserModal(userId) {
        const formData = new FormData();
        formData.append('action', 'get_single_user');
        formData.append('id', userId);

        try {
            const response = await fetch('managermanageusers.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                const user = result.user;
                document.getElementById("editUserId").value = user.id;
                document.getElementById("editUserFirstName").value = user.first_name;
                document.getElementById("editUserLastName").value = user.last_name;
                document.getElementById("editUserEmail").value = user.email;
                document.getElementById("editUserPhone").value = user.phone;
                document.getElementById("editUserDOB").value = user.date_of_birth;
                document.getElementById("editUserType").value = user.user_type;
                document.getElementById("editUserImgPreview").src = user.profile_image ? user.profile_image : defaultProfileImg;

                var modal = new bootstrap.Modal(document.getElementById('editUserModal'));
                modal.show();
            } else {
                showGlobalMessage(result.message || 'Could not fetch user data.', false);
            }
        } catch (error) {
            showGlobalMessage('An error occurred while fetching user data.', false);
        }
    }

    function showEditUserModalFromView() {
        if (currentViewUserId) {
            bootstrap.Modal.getInstance(document.getElementById('viewUserModal')).hide();
            showEditUserModal(currentViewUserId);
        }
    }

    async function saveEditUser(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'update_user');

        try {
            const response = await fetch('managermanageusers.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                showGlobalMessage(result.message);
                bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                fetchUsers(); // Refresh the table
            } else {
                showGlobalMessage(result.message || 'Failed to update user.', false);
            }
        } catch (error) {
            showGlobalMessage('An error occurred.', false);
        }
    }

    // ----- MODAL HANDLING (VIEW) -----
    async function showViewUserModal(userId) {
        currentViewUserId = userId;
        const formData = new FormData();
        formData.append('action', 'get_single_user');
        formData.append('id', userId);

        try {
            const response = await fetch('managermanageusers.php', { method: 'POST', body: formData });
            const result = await response.json();
            if(result.success) {
                const user = result.user;
                const userType = user.user_type || 'customer';
                const badgeClass = userType === 'admin' ? 'bg-danger' : userType === 'manager' ? 'bg-warning' : 'bg-primary';

                document.getElementById("viewUserImg").src = user.profile_image ? user.profile_image : defaultProfileImg;
                document.getElementById("viewUserFullName").textContent = user.first_name + " " + user.last_name;
                document.getElementById("viewUserType").className = "badge " + badgeClass;
                document.getElementById("viewUserType").textContent = userType.charAt(0).toUpperCase() + userType.slice(1);
                document.getElementById("viewUserEmail").textContent = user.email;
                document.getElementById("viewUserPhone").textContent = user.phone || 'Not provided';
                document.getElementById("viewUserDOB").textContent = user.date_of_birth || 'Not provided';
                document.getElementById("viewUserJoined").textContent = new Date(user.created_at).toLocaleDateString();
                document.getElementById("viewUserId").textContent = user.id;
                document.getElementById("viewUserLastLogin").textContent = new Date().toLocaleDateString();

                var modal = new bootstrap.Modal(document.getElementById('viewUserModal'));
                modal.show();
            } else {
                showGlobalMessage(result.message || 'Could not fetch user data.', false);
            }
        } catch(error) {
            showGlobalMessage('An error occurred while fetching user data.', false);
        }
    }

    // ----- INIT -----
    document.addEventListener('DOMContentLoaded', function() {
        fetchUsers();
        // Handle file preview for modals
        document.getElementById('addUserImg').onchange = e => {
            if (e.target.files && e.target.files[0]) document.getElementById('addUserImgPreview').src = URL.createObjectURL(e.target.files[0]);
        };
        document.getElementById('editUserImg').onchange = e => {
            if (e.target.files && e.target.files[0]) document.getElementById('editUserImgPreview').src = URL.createObjectURL(e.target.files[0]);
        };
    });
</script>

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

<!-- Template Javascript -->
<script src="../assets/js/dashboard.js"></script>
<script>
    // Update unread badges every 15 seconds
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
                        if (userNameSpan) {
                            userNameSpan.style.fontWeight = "bold";
                        }
                    } else {
                        badge.textContent = "";
                        badge.style.display = "none";
                        if (userNameSpan) {
                            userNameSpan.style.fontWeight = "normal";
                        }
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
            .catch(error => {
                console.error("Error fetching unread counts:", error);
            });
    }
    setInterval(updateUnreadBadges, 15000);
    document.addEventListener("DOMContentLoaded", () => {
        updateUnreadBadges();
    });
</script>
</body>
</html>