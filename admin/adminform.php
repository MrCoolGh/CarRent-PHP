<?php
session_start();
require_once '../config/db.php'; // Ensure this file provides getConnection() and helper functions like getUserById()

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../public/login.php");
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$userData = getUserById($userId);
if (!$userData) {
    header("Location: ../public/login.php");
    exit();
}

// Global variable to store id to delete
$deleteId = 0;

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'fetch':
            try {
                $conn = getConnection();
                // Query the rental_forms table and join with users table to get user details
                $query = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone AS user_phone 
                          FROM rental_forms r 
                          LEFT JOIN users u ON r.user_id = u.id 
                          ORDER BY r.created_at DESC";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $result = $stmt->get_result();
                $forms = $result->fetch_all(MYSQLI_ASSOC);
                echo json_encode(['success' => true, 'data' => $forms]);
            } catch (mysqli_sql_exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        case 'view':
            // Return single form details
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                echo json_encode(['success' => false, 'error' => 'Invalid form ID']);
                exit;
            }
            try {
                $conn = getConnection();
                $query = "SELECT r.*, u.first_name, u.last_name, u.email, u.phone AS user_phone 
                          FROM rental_forms r 
                          LEFT JOIN users u ON r.user_id = u.id 
                          WHERE r.id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $form = $result->fetch_assoc();
                if ($form) {
                    echo json_encode(['success' => true, 'data' => $form]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Form not found']);
                }
            } catch (mysqli_sql_exception $e) {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            }
            exit;
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                // Allow status values 'approved', 'rejected', or 'pending'
                $status = $_POST['status'] ?? '';
                if (!$id || !in_array($status, ['approved', 'rejected', 'pending'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid input']);
                    exit;
                }
                try {
                    $conn = getConnection();
                    $stmt = $conn->prepare("UPDATE rental_forms SET status = ? WHERE id = ?");
                    $stmt->bind_param("si", $status, $id);
                    $stmt->execute();
                    echo json_encode(['success' => true]);
                } catch (mysqli_sql_exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                }
            }
            exit;
        case 'delete':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
                if (!$id) {
                    echo json_encode(['success' => false, 'error' => 'Invalid input']);
                    exit;
                }
                try {
                    $conn = getConnection();
                    $stmt = $conn->prepare("DELETE FROM rental_forms WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    echo json_encode(['success' => true]);
                } catch (mysqli_sql_exception $e) {
                    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
                }
            }
            exit;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
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
    <style>
        .status-badge { font-size: 0.9em; }
        .table td, .table th { vertical-align: middle; }
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
                <a href="adminform.php" class="nav-item nav-link active"><i class="fa fa-paper-plane me-2"></i>Forms</a>
                <a href="adminsubmitform.php" class="nav-item nav-link"><i class="fa fa-paper-plane me-2"></i>Submit Form</a>
                <a href="managecars.php" class="nav-item nav-link"><i class="fa fa-car-side me-2"></i>Manage Cars</a>
                <a href="manageusers.php" class="nav-item nav-link"><i class="fa fa-users-cog me-2"></i>Manage Users</a>
                <a href="adminprofile.php" class="nav-item nav-link"><i class="fa fa-user-circle me-2"></i>Profile</a>
            </div>
        </nav>
    </div>
    <!-- Sidebar End -->

    <!-- Content Start -->
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

        <div class="container py-5">
            <h2 class="mb-4 text-center">
                <i class="fa fa-id-card text-primary me-2"></i>Submitted Forms
            </h2>
            <div id="adminMessage" class="alert d-none"></div>
            <!-- Search Bar -->
            <div class="row mb-3">
                <div class="col-md-6 mx-auto">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="formsSearchInput" placeholder="Search forms..." onkeyup="searchForms()">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Purpose</th>
                        <th>Docs</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="formTableBody">
                    <!-- Data loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- View Modal -->
        <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewModalLabel">Form Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="formDetails"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of View Modal -->

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete this form? This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteButton">Delete</button>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of Delete Confirmation Modal -->

        <script>
            let allFormsData = [];
            let deleteFormId = 0; // Global variable to store id of form to delete

            function showAdminMsg(message, type = 'success') {
                const msgDiv = document.getElementById('adminMessage');
                msgDiv.className = `alert alert-${type}`;
                msgDiv.textContent = message;
                msgDiv.classList.remove('d-none');
                setTimeout(() => msgDiv.classList.add('d-none'), 3000);
            }

            function generateFormTableRow(form, index) {
                // If status is rejected, show "Rejected" and use a secondary background.
                const statusText = form.status === 'rejected' ? 'Rejected' : form.status.charAt(0).toUpperCase() + form.status.slice(1);
                const statusBadgeClass = {
                    'pending': 'warning',
                    'approved': 'success',
                    'rejected': 'secondary'
                }[form.status] || 'secondary';

                // Build docs HTML with three distinct sections:
                // 1. ID upload, 2. License upload, 3. Other supporting documents
                let docsHtml = '';
                docsHtml += form.id_upload
                    ? `<a href="../assets/uploads/forms/${form.id_upload}" target="_blank" class="btn btn-sm btn-info me-1">
                         <i class="fa fa-file-download"></i> ID
                       </a>`
                    : `<span class="text-muted me-1">No ID</span>`;

                docsHtml += form.license_upload
                    ? `<a href="../assets/uploads/forms/${form.license_upload}" target="_blank" class="btn btn-sm btn-info me-1">
                         <i class="fa fa-file-download"></i> License
                       </a>`
                    : `<span class="text-muted me-1">No License</span>`;

                if(form.other_docs) {
                    try {
                        const otherDocs = JSON.parse(form.other_docs);
                        docsHtml += otherDocs.length
                            ? `<a href="../assets/uploads/forms/${otherDocs[0]}" target="_blank" class="btn btn-sm btn-info me-1">
                                   <i class="fa fa-file-download"></i> Other (${otherDocs.length})
                               </a>`
                            : `<span class="text-muted">No Other Docs</span>`;
                    } catch(e) {
                        docsHtml += `<span class="text-muted">No Other Docs</span>`;
                    }
                } else {
                    docsHtml += `<span class="text-muted">No Other Docs</span>`;
                }

                return `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            ${form.first_name} ${form.last_name}<br>
                            <small class="text-muted">${form.email}</small>
                        </td>
                        <td>${form.user_phone || 'N/A'}</td>
                        <td>${form.purpose || 'N/A'}</td>
                        <td>${docsHtml}</td>
                        <td>
                            <span class="badge bg-${statusBadgeClass} status-badge">
                                ${statusText}
                            </span>
                        </td>
                        <td>${new Date(form.created_at).toLocaleDateString()}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <button onclick="viewForm(${form.id})" class="btn btn-sm btn-info">
                                    <i class="fa fa-eye"></i> View
                                </button>
                                <button onclick="setStatus(${form.id}, 'approved')" class="btn btn-sm btn-success" ${form.status === 'approved' ? 'disabled' : ''}>
                                    <i class="fa fa-check"></i>
                                </button>
                                <button onclick="setStatus(${form.id}, 'rejected')" class="btn btn-sm btn-danger" ${form.status === 'rejected' ? 'disabled' : ''}>
                                    <i class="fa fa-times"></i>
                                </button>
                                <button onclick="confirmDeleteForm(${form.id})" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }

            function searchForms() {
                const searchTerm = document.getElementById('formsSearchInput').value.toLowerCase();
                const filteredForms = allFormsData.filter(form =>
                    form.first_name?.toLowerCase().includes(searchTerm) ||
                    form.last_name?.toLowerCase().includes(searchTerm) ||
                    form.email?.toLowerCase().includes(searchTerm) ||
                    form.purpose?.toLowerCase().includes(searchTerm) ||
                    form.status?.toLowerCase().includes(searchTerm)
                );
                const tbody = document.getElementById('formTableBody');
                tbody.innerHTML = filteredForms.length ?
                    filteredForms.map((form, index) => generateFormTableRow(form, index)).join('') :
                    '<tr><td colspan="8" class="text-center">No matching forms found</td></tr>';
            }

            async function setStatus(id, status) {
                if (!id) return;
                try {
                    const formData = new FormData();
                    formData.append('id', id);
                    formData.append('status', status);
                    const response = await fetch(`?action=update`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Failed to update status');
                    showAdminMsg(`Form marked as "${status}"`);
                    fetchForms();
                } catch (error) {
                    console.error('Error updating status:', error);
                    showAdminMsg('Failed to update status', 'danger');
                }
            }

            // Instead of immediately deleting, show the confirm modal.
            function confirmDeleteForm(id) {
                deleteFormId = id;
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
                deleteModal.show();
            }

            // Delete the form once confirmed in the modal.
            async function deleteFormConfirmed() {
                try {
                    const formData = new FormData();
                    formData.append('id', deleteFormId);
                    const response = await fetch(`?action=delete`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Failed to delete form');
                    showAdminMsg('Form deleted successfully');
                    fetchForms();
                } catch (error) {
                    console.error('Error deleting form:', error);
                    showAdminMsg('Failed to delete form', 'danger');
                }
            }

            // Attach event listener to the Delete button in the modal.
            document.getElementById('confirmDeleteButton').addEventListener('click', function() {
                // Hide the confirmation modal
                const modalEl = document.getElementById('deleteConfirmModal');
                const modal = bootstrap.Modal.getInstance(modalEl);
                modal.hide();
                deleteFormConfirmed();
            });

            async function fetchForms() {
                const tbody = document.getElementById('formTableBody');
                tbody.innerHTML = '<tr><td colspan="8" class="text-center">Loading...</td></tr>';
                try {
                    const response = await fetch(`?action=fetch`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Failed to fetch forms');
                    allFormsData = result.data;
                    if (!Array.isArray(result.data) || result.data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No forms found</td></tr>';
                        return;
                    }
                    tbody.innerHTML = result.data.map((form, index) => generateFormTableRow(form, index)).join('');
                } catch (error) {
                    console.error('Error fetching forms:', error);
                }
            }

            async function viewForm(id) {
                if (!id) return;
                try {
                    const response = await fetch(`?action=view&id=${id}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const result = await response.json();
                    if (!result.success) throw new Error(result.error || 'Failed to fetch form details');
                    const form = result.data;
                    let detailsHtml = `<table class="table table-bordered">
                        <tr><th>ID</th><td>${form.id}</td></tr>
                        <tr><th>Name</th><td>${form.first_name} ${form.last_name}</td></tr>
                        <tr><th>Email</th><td>${form.email}</td></tr>
                        <tr><th>Phone</th><td>${form.user_phone || 'N/A'}</td></tr>
                        <tr><th>Purpose</th><td>${form.purpose || 'N/A'}</td></tr>
                        <tr><th>Status</th><td>${form.status.charAt(0).toUpperCase() + form.status.slice(1)}</td></tr>
                        <tr><th>Submitted</th><td>${new Date(form.created_at).toLocaleString()}</td></tr>`;
                    if(form.id_number) {
                        detailsHtml += `<tr><th>ID Number</th><td>${form.id_number}</td></tr>`;
                    }
                    if(form.dob) {
                        detailsHtml += `<tr><th>Date of Birth</th><td>${form.dob}</td></tr>`;
                    }
                    if(form.license_number) {
                        detailsHtml += `<tr><th>License Number</th><td>${form.license_number}</td></tr>`;
                    }
                    if(form.address) {
                        detailsHtml += `<tr><th>Address</th><td>${form.address}</td></tr>`;
                    }
                    if(form.emergency_name) {
                        detailsHtml += `<tr><th>Emergency Contact Name</th><td>${form.emergency_name}</td></tr>`;
                    }
                    if(form.emergency_phone) {
                        detailsHtml += `<tr><th>Emergency Contact Phone</th><td>${form.emergency_phone}</td></tr>`;
                    }
                    let docsHtml = '';
                    docsHtml += form.id_upload
                        ? `<a href="../assets/uploads/forms/${form.id_upload}" target="_blank" class="btn btn-sm btn-info me-1">
                               <i class="fa fa-file-download"></i> ID
                           </a>`
                        : `<span class="text-muted me-1">No ID</span>`;
                    docsHtml += form.license_upload
                        ? `<a href="../assets/uploads/forms/${form.license_upload}" target="_blank" class="btn btn-sm btn-info me-1">
                               <i class="fa fa-file-download"></i> License
                           </a>`
                        : `<span class="text-muted me-1">No License</span>`;
                    if(form.other_docs) {
                        try {
                            const otherDocs = JSON.parse(form.other_docs);
                            docsHtml += otherDocs.length
                                ? `<a href="../assets/uploads/forms/${otherDocs[0]}" target="_blank" class="btn btn-sm btn-info me-1">
                                       <i class="fa fa-file-download"></i> Other (${otherDocs.length})
                                   </a>`
                                : `<span class="text-muted">No Other Docs</span>`;
                        } catch(e) {
                            docsHtml += `<span class="text-muted">No Other Docs</span>`;
                        }
                    } else {
                        docsHtml += `<span class="text-muted">No Other Docs</span>`;
                    }
                    detailsHtml += `<tr><th>Documents</th><td>${docsHtml}</td></tr>`;
                    detailsHtml += `</table>`;
                    document.getElementById('formDetails').innerHTML = detailsHtml;
                    var viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
                    viewModal.show();
                } catch (error) {
                    console.error('Error viewing form details:', error);
                    showAdminMsg('Failed to load form details', 'danger');
                }
            }

            document.addEventListener('DOMContentLoaded', fetchForms);
        </script>

        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded-top p-4">
                <div class="row">
                    <div class="col-12 col-sm-6 text-center text-sm-start"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- Content End -->

    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top">
        <i class="bi bi-arrow-up"></i>
    </a>
</div>

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
</body>
</html>