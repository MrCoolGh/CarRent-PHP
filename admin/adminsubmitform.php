<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
if (!$userStmt) {
    die("User query prepare failed: " . $conn->error);
}
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    die("User data not found!");
}

// Define the uploads directory before use.
$uploadDir = '../assets/uploads/forms/';

// Process form submission via AJAX POST.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_form') {

    // Collect and sanitize inputs.
    $fullName       = htmlspecialchars($_POST['fullName']);
    $dob            = htmlspecialchars($_POST['dob']);
    $idNumber       = htmlspecialchars($_POST['idNumber']);
    $licenseNumber  = htmlspecialchars($_POST['licenseNumber']);
    $phone          = htmlspecialchars($_POST['phone']);
    $address        = htmlspecialchars($_POST['address']);
    $emergencyName  = htmlspecialchars($_POST['emergencyName']);
    $emergencyPhone = htmlspecialchars($_POST['emergencyPhone']);
    $purpose        = htmlspecialchars($_POST['purpose']);
    $otherPurpose   = isset($_POST['otherPurpose']) ? htmlspecialchars($_POST['otherPurpose']) : null;

    // Ensure the uploads directory exists.
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            die("Failed to create upload directory: " . $uploadDir);
        }
    }

    // Process ID upload.
    $idUpload = $_FILES['idUpload']['name'];
    if ($idUpload) {
        if (!move_uploaded_file($_FILES['idUpload']['tmp_name'], $uploadDir . $idUpload)) {
            die("Error moving ID file: " . $_FILES['idUpload']['error']);
        }
    }

    // Process License upload.
    $licenseUpload = $_FILES['licenseUpload']['name'];
    if ($licenseUpload) {
        if (!move_uploaded_file($_FILES['licenseUpload']['tmp_name'], $uploadDir . $licenseUpload)) {
            die("Error moving License file: " . $_FILES['licenseUpload']['error']);
        }
    }

    // Process Other Docs uploads.
    $otherDocsNames = [];
    if (isset($_FILES['otherDocs'])) {
        // Ensure the 'name' field is an array.
        $otherDocsNamesArray = is_array($_FILES['otherDocs']['name']) ? $_FILES['otherDocs']['name'] : [$_FILES['otherDocs']['name']];
        $totalFiles = count($otherDocsNamesArray);
        if ($totalFiles > 0 && !empty($otherDocsNamesArray[0])) {
            for ($i = 0; $i < $totalFiles; $i++) {
                $fileName = $otherDocsNamesArray[$i];
                $tmpName = is_array($_FILES['otherDocs']['tmp_name']) ? $_FILES['otherDocs']['tmp_name'][$i] : $_FILES['otherDocs']['tmp_name'];
                if ($fileName) {
                    if (move_uploaded_file($tmpName, $uploadDir . $fileName)) {
                        $otherDocsNames[] = $fileName;
                    } else {
                        die("Error moving otherDocs file at index $i: " .
                            (is_array($_FILES['otherDocs']['error']) ? $_FILES['otherDocs']['error'][$i] : $_FILES['otherDocs']['error']));
                    }
                }
            }
        }
    }
    // Encode the array of other docs as JSON.
    $otherDocsJson = json_encode($otherDocsNames);

    $query = "INSERT INTO rental_forms (
                    full_name, dob, id_number, license_number, phone, address, 
                    emergency_name, emergency_phone, purpose, other_purpose, 
                    id_upload, license_upload, other_docs, user_id, status, created_at
                  ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param(
        "sssssssssssssi",
        $fullName,
        $dob,
        $idNumber,
        $licenseNumber,
        $phone,
        $address,
        $emergencyName,
        $emergencyPhone,
        $purpose,
        $otherPurpose,
        $idUpload,
        $licenseUpload,
        $otherDocsJson,
        $userId
    );

    if ($stmt->execute()) {
        // Return a JSON response.
        echo json_encode(["success" => true, "message" => "Form submitted successfully!"]);
    } else {
        die("Statement execute failed: " . $stmt->error);
    }
    exit();
}

// Fetch submitted forms for the current user.
$submittedFormsQuery = "SELECT * FROM rental_forms WHERE user_id = ? ORDER BY created_at DESC";
$stmtForms = $conn->prepare($submittedFormsQuery);
$stmtForms->bind_param("i", $userId);
$stmtForms->execute();
$resultForms = $stmtForms->get_result();
$submittedForms = $resultForms->fetch_all(MYSQLI_ASSOC);
?>
<!-- The HTML below renders the form and lists the submitted forms with a "View" button -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rent Car Now</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <style>
        html, body {
            height: 100%;
            background: #ffffff;
        }
        body {
            height: 100vh;
        }
        #verificationFormContainer {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-section {
            max-width: 780px;
            margin: auto;
            background: #fff !important;
            border: none !important;
        }
        .form-control:disabled, .form-select:disabled {
            background: #e9ecef !important;
        }
        .hide {
            display: none;
        }
        .submitted-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .submitted-card h5 {
            margin-top: 0;
        }
        .status-badge {
            font-size: .85em;
        }
        /* Modal styling for a nicer appearance */
        .modal-header {
            background-color: #007bff;
            color: #fff;
        }
        .modal-body {
            font-size: 1rem;
        }
        .modal-label {
            font-weight: 600;
        }
        .modal-row {
            margin-bottom: 10px;
        }
        .preview-img {
            max-width: 150px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <!-- Sidebar Start -->
    <div class="sidebar pe-4 pb-3">
        <nav class="navbar bg-light navbar-light">
            <a href="admindashboard.php" class="navbar-brand mx-4 mb-3">
                <h3 class="text-primary"><i class="fa fa-hashtag me-2"></i>DASHBOARD</h3>
            </a>
            <div class="d-flex align-items-center ms-4 mb-4">
                <div class="position-relative">
                    <?php
                    $imageFound = false;
                    if (!empty($userData['profile_image'])) {
                        $possiblePaths = [
                            '../assets/uploads/profiles/' . basename($userData['profile_image']),
                            $userData['profile_image'],
                            '../assets/uploads/profiles/' . $userData['profile_image']
                        ];
                        foreach ($possiblePaths as $imagePath) {
                            if (file_exists($imagePath)) {
                                echo '<img class="rounded-circle" src="' . htmlspecialchars($imagePath) . '" alt="Profile" style="width: 40px; height: 40px;">';
                                $imageFound = true;
                                break;
                            }
                        }
                    }
                    if (!$imageFound):
                        ?>
                        <img class="rounded-circle" src="../assets/images/blog-2.jpg" alt="" style="width: 40px; height: 40px;">
                    <?php endif; ?>
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="admindashboard.php" class="nav-item nav-link"><i class="fa fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="adminbookings.php" class="nav-item nav-link"><i class="fa fa-calendar-check me-2"></i>Bookings</a>
                <a href="adminmybookings.php" class="nav-item nav-link"><i class="fa fa-calendar-check me-2"></i>My Bookings</a>
                <a href="adminmessage.php" class="nav-item nav-link" id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>
                <a href="adminform.php" class="nav-item nav-link"><i class="fa fa-paper-plane me-2"></i>Forms</a>
                <a href="adminsubmitform.php" class="nav-item nav-link active"><i class="fa fa-paper-plane me-2"></i>Submit Form</a>
                <a href="managecars.php" class="nav-item nav-link"><i class="fa fa-car-side me-2"></i>Manage Cars</a>
                <a href="manageusers.php" class="nav-item nav-link"><i class="fa fa-users-cog me-2"></i>Manage Users</a>
                <a href="adminprofile.php" class="nav-item nav-link"><i class="fa fa-user-circle me-2"></i>Profile</a>
            </div>
        </nav>
    </div>
    <!-- Sidebar End -->

    <div class="content">
        <!-- Navbar Start -->
        <nav class="navbar navbar-expand bg-light navbar-light sticky-top px-4 py-0">
            <a href="admindashboard.php" class="navbar-brand d-flex d-lg-none me-4">
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
                        <?php
                        // Enhanced profile image handling for navbar
                        $navImageFound = false;
                        if (!empty($userData['profile_image'])) {
                            $possiblePaths = [
                                '../assets/uploads/profiles/' . basename($userData['profile_image']),
                                $userData['profile_image'],
                                '../assets/uploads/profiles/' . $userData['profile_image']
                            ];

                            foreach ($possiblePaths as $imagePath) {
                                if (file_exists($imagePath)) {
                                    echo '<img class="rounded-circle me-lg-2" src="' . htmlspecialchars($imagePath) . '" alt="Profile" style="width: 40px; height: 40px;">';
                                    $navImageFound = true;
                                    break;
                                }
                            }
                        }

                        if (!$navImageFound): ?>
                            <img class="rounded-circle me-lg-2" src="../assets/images/blog-5.jpg" alt="" style="width: 40px; height: 40px;">
                        <?php endif; ?>
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

        <!-- Verification Form Section -->
        <div id="verificationFormContainer" class="w-100 p-0 m-0">
            <div class="form-section p-4 p-md-5 mb-5">
                <h3 class="mb-4 text-center">
                    <i class="fa fa-id-card text-primary me-2"></i>Verification Form
                </h3>
                <!-- Success Modal -->
                <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="successModalLabel">Success</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                Form submitted successfully!
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="rentalVerificationForm" method="POST" enctype="multipart/form-data" autocomplete="off">
                    <!-- Hidden input to specify action -->
                    <input type="hidden" name="action" value="submit_form">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="fullName" class="form-label">
                                <i class="fa fa-user me-1 text-primary"></i>Full Name
                            </label>
                            <input type="text" class="form-control" id="fullName" name="fullName" required placeholder="Enter your full name">
                        </div>
                        <div class="col-md-6">
                            <label for="dob" class="form-label">
                                <i class="fa fa-birthday-cake me-1 text-primary"></i>Date of Birth
                            </label>
                            <input type="date" class="form-control" id="dob" name="dob" required>
                        </div>
                        <div class="col-md-6">
                            <label for="idNumber" class="form-label">
                                <i class="fa fa-id-badge me-1 text-primary"></i>National ID / Passport Number
                            </label>
                            <input type="text" class="form-control" id="idNumber" name="idNumber" required placeholder="Enter ID or Passport Number">
                        </div>
                        <div class="col-md-6">
                            <label for="idUpload" class="form-label">
                                <i class="fa fa-upload me-1 text-primary"></i>Upload National ID / Passport (Front)
                            </label>
                            <input type="file" class="form-control" id="idUpload" name="idUpload" accept="image/*,application/pdf" required>
                        </div>
                        <div class="col-md-6">
                            <label for="licenseNumber" class="form-label">
                                <i class="fa fa-car me-1 text-primary"></i>Driver's License Number
                            </label>
                            <input type="text" class="form-control" id="licenseNumber" name="licenseNumber" required placeholder="Enter Driver's License Number">
                        </div>
                        <div class="col-md-6">
                            <label for="licenseUpload" class="form-label">
                                <i class="fa fa-address-card me-1 text-primary"></i>Upload Driver's License
                            </label>
                            <input type="file" class="form-control" id="licenseUpload" name="licenseUpload" accept="image/*,application/pdf" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">
                                <i class="fa fa-phone me-1 text-primary"></i>Phone Number
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone" required placeholder="+233 123 456 789">
                        </div>
                        <div class="col-md-6">
                            <label for="address" class="form-label">
                                <i class="fa fa-home me-1 text-primary"></i>Residential Address
                            </label>
                            <input type="text" class="form-control" id="address" name="address" required placeholder="Enter your address">
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="emergencyName" class="form-label">
                                <i class="fa fa-user-shield me-1 text-primary"></i>Emergency Contact Name
                            </label>
                            <input type="text" class="form-control" id="emergencyName" name="emergencyName" required placeholder="Name of emergency contact">
                        </div>
                        <div class="col-md-6">
                            <label for="emergencyPhone" class="form-label">
                                <i class="fa fa-phone-alt me-1 text-primary"></i>Emergency Contact Phone
                            </label>
                            <input type="tel" class="form-control" id="emergencyPhone" name="emergencyPhone" required placeholder="+233 987 654 321">
                        </div>
                        <div class="col-md-6">
                            <label for="purpose" class="form-label">
                                <i class="fa fa-info-circle me-1 text-primary"></i>Purpose of Rental
                            </label>
                            <select class="form-select" id="purpose" name="purpose" required>
                                <option value="">Select purpose</option>
                                <option value="business">Business</option>
                                <option value="personal">Personal</option>
                                <option value="travel">Travel</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 hide" id="otherPurposeBox">
                            <label for="otherPurpose" class="form-label">
                                <i class="fa fa-pen me-1 text-primary"></i>Please specify purpose
                            </label>
                            <input type="text" class="form-control" id="otherPurpose" name="otherPurpose" placeholder="Type your purpose">
                        </div>
                    </div>
                    <hr class="my-4">
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fa fa-folder-plus me-1 text-primary"></i>Other Supporting Documents (up to 10)
                        </label>
                        <input type="file" class="form-control" id="otherDocs" name="otherDocs" accept="image/*,application/pdf" multiple>
                        <div class="form-text">
                            You may upload utility bills, proof of address, insurance, or other relevant forms (max 10 files, images/PDF only).
                            These files will be saved to the same directory as your ID and driver's license.
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="consent" name="consent" required>
                        <label class="form-check-label" for="consent">
                            I agree to the <a href="#">terms and conditions</a> and authorize the company to verify my documents.
                        </label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa fa-paper-plane me-1"></i>Submit Verification
                        </button>
                    </div>
                </form>
            </div>

            <!-- Submitted Forms List (Edit button removed) -->
            <div class="container py-5">
                <h2 class="mb-4 text-center">
                    <i class="fa fa-id-card text-primary me-2"></i>Submitted Forms
                </h2>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody id="formTableBody">
                        <?php if (!empty($submittedForms)) : ?>
                            <?php $count = 1; ?>
                            <?php foreach ($submittedForms as $form) : ?>
                                <?php
                                // Prepare a JSON-encoded version of the full form details.
                                $formJson = htmlspecialchars(json_encode($form), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($form['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($form['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($form['purpose']); ?></td>
                                    <td>
                        <span class="badge <?php echo ($form['status'] === 'approved') ? 'bg-success' : 'bg-warning text-dark'; ?>">
                          <?php echo htmlspecialchars($form['status']); ?>
                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($form['created_at']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick='viewForm(<?php echo $formJson; ?>)'>View</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No submitted forms found.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Form Details Modal with Enhanced Styling and Image Previews -->
            <div class="modal fade" id="formDetailsModal" tabindex="-1" aria-labelledby="formDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content shadow">
                        <div class="modal-header">
                            <h5 class="modal-title" id="formDetailsModalLabel">Form Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="formDetailsContent">
                            <div class="container-fluid">
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Full Name:</div>
                                    <div class="col-md-8" id="viewFullName"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Date of Birth:</div>
                                    <div class="col-md-8" id="viewDob"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">ID/Passport No.:</div>
                                    <div class="col-md-8" id="viewIdNumber"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">License No.:</div>
                                    <div class="col-md-8" id="viewLicenseNumber"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Phone:</div>
                                    <div class="col-md-8" id="viewPhone"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Address:</div>
                                    <div class="col-md-8" id="viewAddress"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Emergency Contact:</div>
                                    <div class="col-md-8" id="viewEmergencyContact"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Purpose:</div>
                                    <div class="col-md-8" id="viewPurpose"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">ID Upload:</div>
                                    <div class="col-md-8" id="viewIdUpload"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">License Upload:</div>
                                    <div class="col-md-8" id="viewLicenseUpload"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Other Documents:</div>
                                    <div class="col-md-8" id="viewOtherDocs"></div>
                                </div>
                                <div class="row modal-row">
                                    <div class="col-md-4 modal-label">Status:</div>
                                    <div class="col-md-8" id="viewStatus"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Verification Form Section -->

        <script>
            // Toggle the "other purpose" input based on the selected value.
            document.getElementById('purpose').addEventListener('change', function() {
                const box = document.getElementById('otherPurposeBox');
                if (this.value === 'other') {
                    box.classList.remove('hide');
                    document.getElementById('otherPurpose').required = true;
                } else {
                    box.classList.add('hide');
                    document.getElementById('otherPurpose').required = false;
                    document.getElementById('otherPurpose').value = "";
                }
            });

            // Function to display the submitted form details in a nicely looking modal with image previews.
            function viewForm(formData) {
                document.getElementById('viewFullName').innerText = formData.full_name;
                document.getElementById('viewDob').innerText = formData.dob;
                document.getElementById('viewIdNumber').innerText = formData.id_number;
                document.getElementById('viewLicenseNumber').innerText = formData.license_number;
                document.getElementById('viewPhone').innerText = formData.phone;
                document.getElementById('viewAddress').innerText = formData.address;
                document.getElementById('viewEmergencyContact').innerText = formData.emergency_name + " (" + formData.emergency_phone + ")";
                let purposeContent = formData.purpose;
                if (formData.other_purpose) {
                    purposeContent += " - " + formData.other_purpose;
                }
                document.getElementById('viewPurpose').innerText = purposeContent;

                // Display ID Upload and License Upload as image previews or links.
                let uploadPath = "../assets/uploads/forms/";
                let idUploadHTML = "";
                if (formData.id_upload) {
                    let ext = formData.id_upload.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
                        idUploadHTML = '<a href="' + uploadPath + formData.id_upload + '" target="_blank"><img class="preview-img" src="' + uploadPath + formData.id_upload + '" alt="ID Upload"/></a>';
                    } else {
                        idUploadHTML = '<a href="' + uploadPath + formData.id_upload + '" target="_blank">' + formData.id_upload + '</a>';
                    }
                } else {
                    idUploadHTML = "No File Uploaded";
                }
                let licenseUploadHTML = "";
                if (formData.license_upload) {
                    let ext = formData.license_upload.split('.').pop().toLowerCase();
                    if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
                        licenseUploadHTML = '<a href="' + uploadPath + formData.license_upload + '" target="_blank"><img class="preview-img" src="' + uploadPath + formData.license_upload + '" alt="License Upload"/></a>';
                    } else {
                        licenseUploadHTML = '<a href="' + uploadPath + formData.license_upload + '" target="_blank">' + formData.license_upload + '</a>';
                    }
                } else {
                    licenseUploadHTML = "No File Uploaded";
                }
                document.getElementById('viewIdUpload').innerHTML = idUploadHTML;
                document.getElementById('viewLicenseUpload').innerHTML = licenseUploadHTML;

                // Process other documents stored as a JSON string.
                let otherDocsHTML = '';
                try {
                    let otherDocs = JSON.parse(formData.other_docs);
                    if (Array.isArray(otherDocs) && otherDocs.length > 0) {
                        for (let doc of otherDocs) {
                            let ext = doc.split('.').pop().toLowerCase();
                            if (['jpg', 'jpeg', 'png', 'gif', 'bmp'].includes(ext)) {
                                otherDocsHTML += '<a href="' + uploadPath + doc + '" target="_blank"><img class="preview-img" src="' + uploadPath + doc + '" alt="Other Doc" style="margin-bottom:5px;"/></a>';
                            } else {
                                otherDocsHTML += '<a href="' + uploadPath + doc + '" target="_blank">' + doc + '</a><br>';
                            }
                        }
                    } else {
                        otherDocsHTML = "None";
                    }
                } catch (e) {
                    otherDocsHTML = formData.other_docs;
                }
                document.getElementById('viewOtherDocs').innerHTML = otherDocsHTML;
                document.getElementById('viewStatus').innerText = formData.status;
                var modal = new bootstrap.Modal(document.getElementById('formDetailsModal'));
                modal.show();
            }

            // Submit the form via AJAX.
            document.getElementById('rentalVerificationForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                            successModal.show();
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            alert("Error: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error submitting form:", error);
                        alert("An error occurred while submitting the form.");
                    });
            });

            // Example search function for the search bar.
            function searchForms() {
                const input = document.getElementById("formsSearchInput").value.toLowerCase();
                const tableBody = document.getElementById("formTableBody");
                const rows = tableBody.getElementsByTagName("tr");
                for (let i = 0; i < rows.length; i++) {
                    let rowText = rows[i].textContent.toLowerCase();
                    rows[i].style.display = rowText.indexOf(input) > -1 ? "" : "none";
                }
            }
        </script>

        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">
                    <div class="col-12 col-sm-6 text-center text-sm-start"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- End Content -->

    <!-- Back to Top Button -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top">
        <i class="bi bi-arrow-up"></i>
    </a>
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
<script src="../assets/js/dashboard.js"></script>
<!-- HTML2PDF Library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    console.log("Page loaded, ready to submit form.");
</script>
<script>
    // Update unread badges (if any) as needed.
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
