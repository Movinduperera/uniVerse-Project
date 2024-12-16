<?php

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();

    echo "<script>
              alert('Successfully Logged Out!');
              window.location.href = '../admin/login.php';
            </script>";
    session_destroy();
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniVerse Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style type="text/css">
        <?php include '../styles/sidebar.css'; ?>
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <h1>Uni<span class="logo-span">Verse</span></h1>
            </div>
        </div>
        <nav class="menu">
            <ul>
                <li class="menu-item"><a href="../admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li class="menu-item"><a href="../admin/users.php"><i class="fas fa-users"></i> Users</a></li>
                <li class="menu-item"><a href="../admin/content.php"><i class="fas fa-file-alt"></i> Content</a></li>
                <li class="menu-item"><a href="../admin/mentorships.php"><i class="fas fa-user-friends"></i> Mentorship</a></li>
                <li class="menu-item"><a href="../admin/analytics.php"><i class="fas fa-chart-pie"></i> Analytics</a></li>
                <li class="menu-item"><a href="#"><i class="fas fa-clipboard-list"></i> Logs</a></li>
            </ul>
        </nav>
        <!-- <hr> -->
        <div class="sidebar-footer">
            <ul>
                <li class="menu-item"><a href="?action=logout"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <script>
        // todo : a script to load a default page should be added
    </script>
</body>

</html>