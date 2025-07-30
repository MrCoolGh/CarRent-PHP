<?php
session_start();
require_once '../config/db.php';

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

// Fetch all active users from the database
$query = "SELECT * FROM users WHERE status='active'";
$result = $conn->query($query);
$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
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
        .user-type-admin { color: #dc3545; font-weight: bold; }
        .user-type-manager { color: #e7c315; font-weight: bold; }
        .user-type-customer { color: #0d6efd; font-weight: bold; }
        .unread-badge {
            background: red;
            color: #fff;
            border-radius: 50%;
            padding: 2px 6px;
            margin-left: 5px;
            font-size: 0.8rem;
            display: none;
        }
    </style>
</head>

<body>
<div class="container-fluid position-relative bg-white d-flex p-0">
    <!-- Sidebar Start (reuse from your template) -->
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
                    if (!$imageFound): ?>
                        <img class="rounded-circle" src="../assets/images/blog-3.jpg" alt="" style="width: 40px; height: 40px;">
                    <?php endif; ?>
                    <div class="bg-success rounded-circle border border-2 border-white position-absolute end-0 bottom-0 p-1"></div>
                </div>
                <div class="ms-3">
                    <h6 class="mb-0"><?php echo htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']); ?></h6>
                    <span><?php echo ucfirst(htmlspecialchars($userData['user_type'])); ?></span>
                </div>
            </div>
            <div class="navbar-nav w-100">
                <a href="admindashboard.php" class="nav-item nav-link ">
                    <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="adminbookings.php" class="nav-item nav-link ">
                    <i class="fa fa-calendar-check me-2"></i>Bookings
                </a>
                <a href="adminmybookings.php" class="nav-item nav-link">
                    <i class="fa fa-calendar-check me-2"></i>My Bookings
                </a>
                <a href="adminmessage.php" class="nav-item nav-link active" id="navMessageLink">
                    <i class="fa fa-envelope me-2"></i>Messages
                    <span id="navUnreadBadge" class="badge bg-danger ms-2" style="display:none;"></span>
                </a>
                <a href="adminform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Forms
                </a>
                <a href="adminsubmitform.php" class="nav-item nav-link">
                    <i class="fa fa-paper-plane me-2"></i>Submit Form
                </a>
                <a href="managecars.php" class="nav-item nav-link">
                    <i class="fa fa-car-side me-2"></i>Manage Cars
                </a>
                <a href="manageusers.php" class="nav-item nav-link">
                    <i class="fa fa-users-cog me-2"></i>Manage Users
                </a>
                <a href="adminprofile.php" class="nav-item nav-link">
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
                            <img class="rounded-circle me-lg-2" src="../assets/images/blog-1.jpg" alt="" style="width: 40px; height: 40px;">
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

        <!-- Messenger Start -->
        <style>
            .user-list-sidebar {
                min-width: 220px;
                max-width: 260px;
                border-right: 1px solid #e4e4e4;
                background: #fff;
                border-radius: 1rem 0 0 1rem;
                height: 100%;
                overflow-y: auto;
            }
            @media (max-width: 991.98px) {
                .user-list-sidebar {
                    min-width: 180px;
                    max-width: 220px;
                }
            }
            @media (max-width: 767.98px) {
                .container-fluid > .bg-light.d-flex.flex-column.flex-md-row {
                    flex-direction: column !important;
                }
                .user-list-sidebar {
                    max-width: 100%;
                    min-width: 0;
                    border-right: none;
                    border-bottom: 1px solid #e4e4e4;
                    border-radius: 1rem 1rem 0 0;
                }
                .flex-grow-1.px-0.px-md-4 {
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }
                .messages-area {
                    min-height: 220px;
                    height: 220px;
                    font-size: 0.97rem;
                }
            }
            .user-list-item.active, .user-list-item:hover {
                background: #f1f1f1;
                cursor: pointer;
            }
            .message-bubble.sent {
                background: #d1e7dd;
                align-self: flex-end;
            }
            .message-bubble.received {
                background: #f8d7da;
                align-self: flex-start;
            }
            .message-bubble {
                border-radius: 1.1rem;
                padding: 0.6rem 1rem;
                margin-bottom: 0.5rem;
                max-width: 75%;
                word-break: break-word;
                position: relative;
            }
            .timestamp {
                font-size: 0.75rem;
                color: #6c757d;
                position: absolute;
                bottom: -18px;
                right: 0;
            }
            .messages-area {
                height: 350px;
                overflow-y: auto;
                background: #f9f9f9;
                border-radius: 1rem;
                padding: 1rem;
                display: flex;
                flex-direction: column;
            }
            .chat-header-user {
                display: flex;
                align-items: center;
                gap: 1rem;
                border-bottom: 1px solid #e4e4e4;
                padding-bottom: 0.8rem;
                margin-bottom: 1rem;
            }
            .chat-header-user img {
                width: 48px;
                height: 48px;
                object-fit: cover;
            }
            .badge-admin {
                background-color: red;
                color: #fff;
            }
            .badge-manager {
                background-color: yellow;
                color: #000;
            }
            .badge-customer {
                background-color: blue;
                color: #fff;
            }
            .default-user-image {
                width: 30px;
                height: 30px;
                border-radius: 50%;
            }
            .user-type-admin { color: #dc3545; font-weight: bold; }
            .user-type-manager { color: #e7c315; font-weight: bold; }
            .user-type-customer { color: #0d6efd; font-weight: bold; }
        </style>
        <div class="container-fluid pt-4 px-4">
            <div class="bg-light rounded p-4 d-flex flex-column flex-md-row" style="min-height:480px;">
                <!-- Left: User List -->
                <div class="user-list-sidebar px-3 py-2 mb-3 mb-md-0 flex-shrink-0" style="max-width:260px;min-width:220px;">
                    <h6 class="fw-bold mb-3">Users</h6>
                    <!-- Search Bar for Users -->
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                        <input type="text" class="form-control" id="usersMessageSearchInput" placeholder="Search users..." onkeyup="searchUsersMessage()">
                    </div>
                    <div id="userListMsg">
                        <?php foreach ($users as $user):
                            if ($user['id'] == $userId) continue;
                            $profileImg = (!empty($user['profile_image']) && file_exists('../assets/uploads/profiles/' . basename($user['profile_image'])))
                                ? '../assets/uploads/profiles/' . basename($user['profile_image'])
                                : '../assets/images/car-1.jpg';
                            ?>
                            <div class="user-list-item p-2 mb-1" data-id="<?php echo $user['id']; ?>"
                                 data-name="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                                 data-type="<?php echo $user['user_type']; ?>"
                                 data-img="<?php echo $profileImg; ?>">
                                <img src="<?php echo $profileImg; ?>" class="rounded-circle me-2 default-user-image" alt="User">
                                <span class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                                <span class="unread-badge"></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Right: Chat Area -->
                <div class="flex-grow-1 d-flex flex-column px-0 px-md-4 py-2">
                    <div id="chatHeader" class="chat-header-user d-none">
                        <img id="chatUserImg" src="../assets/images/car-1.jpg" class="rounded-circle" alt="User">
                        <div>
                            <span id="chatUserName" class="fw-semibold"></span>
                            <div><span class="badge" id="chatUserType"></span></div>
                        </div>
                    </div>
                    <div id="messagesArea" class="messages-area d-flex flex-column mb-3"></div>
                    <form id="messageForm" class="d-flex align-items-center gap-3 mt-auto d-none" onsubmit="return sendMessage();">
                        <input type="text" id="messageInput" class="form-control" placeholder="Type a message..." autocomplete="off" style="border-radius:1.5rem;">
                        <button type="submit" class="btn btn-primary px-4"><i class="fa fa-paper-plane"></i></button>
                    </form>
                    <div id="chatPlaceholder" class="text-secondary text-center my-auto">
                        Select a user to start chatting
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline JavaScript for Chat Functionality -->
        <script>
            document.addEventListener("DOMContentLoaded", () => {
                const userListItems = document.querySelectorAll(".user-list-item");
                userListItems.forEach(item => {
                    item.addEventListener("click", () => {
                        const user = {
                            id: item.getAttribute("data-id"),
                            name: item.getAttribute("data-name"),
                            type: item.getAttribute("data-type"),
                            img: item.getAttribute("data-img")
                        };
                        // Mark messages from this sender as read.
                        markConversationRead(user.id);
                        // Remove the badge from this user.
                        const badge = item.querySelector(".unread-badge");
                        if (badge) {
                            badge.textContent = "";
                            badge.style.display = "none";
                        }
                        selectUser(user);
                    });
                });
            });

            // Update unread badges and make the user's name bold if there are unread messages.
            function updateUnreadBadges() {
                fetch("get_unread_counts.php")
                    .then(response => response.json())
                    .then(data => {
                        // data is an object mapping sender_id to unread count.
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

                        // Also update the global nav unread badge.
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

            // Call updateUnreadBadges on page load and every 15 seconds.
            setInterval(updateUnreadBadges, 15000);
            document.addEventListener("DOMContentLoaded", () => {
                updateUnreadBadges();
            });

            // Mark messages from a sender as read.
            function markConversationRead(senderId) {
                fetch("mark_read.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `sender_id=${senderId}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            updateUnreadBadges();
                        }
                    })
                    .catch(error => {
                        console.error("Error marking conversation as read:", error);
                    });
            }

            function selectUser(user) {
                document.getElementById("chatHeader").classList.remove("d-none");
                document.getElementById("chatUserImg").src = user.img;
                document.getElementById("chatUserName").textContent = user.name;
                const chatUserType = document.getElementById("chatUserType");
                chatUserType.textContent = user.type.charAt(0).toUpperCase() + user.type.slice(1);
                chatUserType.classList.remove("badge-admin", "badge-manager", "badge-customer");
                if (user.type.toLowerCase() === "admin") {
                    chatUserType.classList.add("badge-admin");
                } else if (user.type.toLowerCase() === "manager") {
                    chatUserType.classList.add("badge-manager");
                } else {
                    chatUserType.classList.add("badge-customer");
                }
                document.getElementById("messageForm").classList.remove("d-none");
                document.getElementById("chatPlaceholder").classList.add("d-none");

                // Store selected user's ID globally.
                window.currentReceiverId = user.id;

                // Fetch conversation for the selected user.
                fetchMessages(user.id);
            }

            function fetchMessages(receiverId) {
                const messagesArea = document.getElementById("messagesArea");
                messagesArea.innerHTML = "";
                fetch(`get_messages.php?receiver_id=${receiverId}`)
                    .then(response => response.json())
                    .then(data => {
                        data.forEach(message => {
                            if (message.sender_id == <?php echo json_encode($userId); ?>) {
                                addMessage("sent", message.message_text, message.sent_at);
                            } else {
                                addMessage("received", message.message_text, message.sent_at);
                            }
                        });
                    })
                    .catch(error => {
                        console.error("Error fetching messages:", error);
                    });
            }

            function addMessage(type, text, sent_at) {
                const messagesArea = document.getElementById("messagesArea");
                const msgDiv = document.createElement("div");
                msgDiv.classList.add("message-bubble", type);
                const textDiv = document.createElement("div");
                textDiv.textContent = text;
                msgDiv.appendChild(textDiv);
                const timeSpan = document.createElement("span");
                timeSpan.classList.add("timestamp");
                const time = sent_at ? new Date(sent_at) : new Date();
                const hours = time.getHours().toString().padStart(2, '0');
                const minutes = time.getMinutes().toString().padStart(2, '0');
                timeSpan.textContent = `${hours}:${minutes}`;
                msgDiv.appendChild(timeSpan);
                messagesArea.appendChild(msgDiv);
                messagesArea.scrollTop = messagesArea.scrollHeight;
            }

            function sendMessage() {
                const messageInput = document.getElementById("messageInput");
                const text = messageInput.value;
                if (text.trim() === "") return false;

                // Ensure that a receiver is selected.
                if (!window.currentReceiverId) return false;

                fetch("send_message.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `receiver_id=${window.currentReceiverId}&message_text=${encodeURIComponent(text)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === "success") {
                            addMessage("sent", text);
                            messageInput.value = "";
                            updateUnreadBadges();
                        } else {
                            console.error("Error sending message:", data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error sending message:", error);
                    });

                return false;
            }

            function searchUsersMessage() {
                const input = document.getElementById("usersMessageSearchInput").value.toLowerCase();
                const items = document.querySelectorAll("#userListMsg .user-list-item");
                items.forEach(item => {
                    if (item.textContent.toLowerCase().indexOf(input) > -1) {
                        item.style.display = "block";
                    } else {
                        item.style.display = "none";
                    }
                });
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
<!-- Template Javascript -->
<script src="../assets/js/dashboard.js"></script>
</body>
</html>