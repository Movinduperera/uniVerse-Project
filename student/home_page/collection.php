<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    include "../../connection.php"; 

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $user_id = $_SESSION['user_id'];

    $sql = "SELECT s.student_id, u.firstname, u.lastname, s.profile_picture FROM Student s 
            JOIN Users u ON s.user_id = u.user_id WHERE u.user_id = ?";
    $student_id = 0;
    $firstname = '';
    $lastname = '';
    $profile_picture = '';
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $student_id, $firstname, $lastname, $profile_picture);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    }

    // Fetch videos uploaded by all users (for the Explore section)
    $sql = "SELECT c.content_id,c.title, c.description, c.file_url, u.firstname, u.lastname, s.profile_picture 
    FROM Content c
    JOIN Student s ON c.student_id = s.student_id
    JOIN Users u ON s.user_id = u.user_id
    WHERE c.content_type = 'Video'
    AND c.status = 'Approved'";

    $all_videos = [];
    if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $content_id,$title, $description, $file_url, $video_firstname, $video_lastname, $video_profile_picture);

    while (mysqli_stmt_fetch($stmt)) {
    // Generate thumbnail URL (this assumes the video file is stored under 'uploads/videos' and thumbnails are stored in 'uploads/thumbnails')
    $thumbnail_url = 'thumbnails/' . basename($file_url, '.mp4') . '.jpg'; // Assuming the thumbnail is named similarly to the video file

    $all_videos[] = [
        'content_id' => $content_id,
        'title' => $title,
        'description' => $description,
        'file_url' => $file_url,
        'thumbnail_url' => $thumbnail_url,  // Generated dynamically
        'firstname' => $video_firstname,
        'lastname' => $video_lastname,
        'profile_picture' => $video_profile_picture
    ];
    }
    mysqli_stmt_close($stmt);
    }


    $sql = "SELECT c.content_id, c.title, c.description, c.file_url, c.created_at, u.firstname, u.lastname, s.profile_picture 
            FROM Content c 
            JOIN Student s ON c.student_id = s.student_id 
            JOIN Users u ON s.user_id = u.user_id 
            WHERE c.content_type = 'Note'
            AND c.status = 'Approved'
            ORDER BY c.created_at DESC";
    $all_notes = [];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $all_notes[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    if (isset($_POST['like'])) {
        $content_id = $_POST['content_id'];
        
        $sql = "SELECT * FROM Likes WHERE content_id = ? AND student_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 0) {
                $sql = "INSERT INTO Likes (content_id, student_id) VALUES (?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
                    mysqli_stmt_execute($stmt);
                }
            } else {
                $sql = "DELETE FROM Likes WHERE content_id = ? AND student_id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $sql = "SELECT * FROM Content WHERE student_id = ? AND content_type = 'Post' ORDER BY created_at DESC";
    $posts = [];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $student_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $posts[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $sql = "SELECT c.content_id, c.title, c.description, c.file_url, c.created_at, u.firstname, u.lastname, s.profile_picture 
            FROM Content c 
            JOIN Student s ON c.student_id = s.student_id 
            JOIN Users u ON s.user_id = u.user_id 
            WHERE c.content_type = 'Post'
            ORDER BY c.created_at DESC";
    $all_posts = [];
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $all_posts[] = $row;
        }
        mysqli_stmt_close($stmt);
    }


    if (isset($_GET['delete'])) {
        // Sanitize the input
        $content_id = intval($_GET['delete']); // Ensures it is an integer

        // Check if the content exists in the database
        $check_sql = "SELECT * FROM Content WHERE content_id = ?";
        if ($stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "i", $content_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                // Proceed with deletion
                $delete_sql = "DELETE FROM Content WHERE content_id = ?";
                if ($delete_stmt = mysqli_prepare($conn, $delete_sql)) {
                    mysqli_stmt_bind_param($delete_stmt, "i", $content_id);
                    if (mysqli_stmt_execute($delete_stmt)) {
                        // Successfully deleted
                        echo "<script>
                            alert('Post deleted successfully.');
                            window.location.href = 'post.php'; // Redirect to the posts list
                        </script>";
                    } else {
                        echo "<script>
                            alert('Failed to delete the post. Please try again.');
                            window.history.back();
                        </script>";
                    }
                    mysqli_stmt_close($delete_stmt);
                }
            } else {
                // Post not found
                echo "<script>
                    alert('Post not found.');
                    window.history.back();
                </script>";
            }
            mysqli_stmt_close($stmt);
        } else {
            // Query preparation failed
            echo "<script>
                alert('An error occurred. Please try again.');
                window.history.back();
            </script>";
        }
    }

    if (isset($_POST['like'])) {
        $content_id = $_POST['content_id'];
        
        $sql = "SELECT * FROM Likes WHERE content_id = ? AND student_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 0) {
                $sql = "INSERT INTO Likes (content_id, student_id) VALUES (?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
                    mysqli_stmt_execute($stmt);
                }
            } else {
                $sql = "DELETE FROM Likes WHERE content_id = ? AND student_id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
                    mysqli_stmt_execute($stmt);
                }
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if (isset($_POST['toggle_collection'])) {
        $content_id = $_POST['content_id'];
        
        $sql = "SELECT * FROM Collection WHERE content_id = ? AND student_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
    
            if (mysqli_num_rows($result) == 0) {
                $sql = "INSERT INTO Collection (student_id, content_id) VALUES (?, ?)";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $student_id, $content_id);
                    mysqli_stmt_execute($stmt);
                    echo json_encode(['status' => 'added']); 
                }
            } else {
                $sql = "DELETE FROM Collection WHERE content_id = ? AND student_id = ?";
                if ($stmt = mysqli_prepare($conn, $sql)) {
                    mysqli_stmt_bind_param($stmt, "ii", $content_id, $student_id);
                    mysqli_stmt_execute($stmt);
                    echo json_encode(['status' => 'removed']); 
                }
            }
            mysqli_stmt_close($stmt);
        }
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit(); 
    }
    
    if (isset($_GET['fetch_comments'])) {
        $content_id = $_GET['fetch_comments'];
        
        $sql_student = "
            SELECT student_id 
            FROM Student 
            WHERE user_id = ?
        ";
        
        $student_id = null;
        if ($stmt_student = mysqli_prepare($conn, $sql_student)) {
            mysqli_stmt_bind_param($stmt_student, "i", $user_id);
            mysqli_stmt_execute($stmt_student);
            mysqli_stmt_bind_result($stmt_student, $student_id);
            mysqli_stmt_fetch($stmt_student);
            mysqli_stmt_close($stmt_student);
        }
    
        if ($student_id === null) {
            echo "Student ID not found.";
            exit();
        }
        
        $sql = "
            SELECT c.comment_id, c.text, c.created_at, u.firstname, u.lastname, c.student_id
            FROM Comments c
            JOIN Student s ON c.student_id = s.student_id
            JOIN Users u ON s.user_id = u.user_id
            WHERE c.content_id = ?
            ORDER BY c.created_at ASC
        ";
        
        $comments = [];
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind parameters: content_id
            mysqli_stmt_bind_param($stmt, "i", $content_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $comments[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        foreach ($comments as $comment) {
            echo '<div class="comment-details">';
            echo '<div class="comment-author">';
            echo '<strong>' . htmlspecialchars($comment['firstname'] . ' ' . $comment['lastname']);
            echo '</div>';
            echo '<div class="comment">';
            echo htmlspecialchars($comment['text']);
            if ($comment['student_id'] == $student_id) {
                echo ' <button onclick="deleteComment(' . $comment['comment_id'] . ')"><i class="fa-regular fa-trash-can"></i></button>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        exit();
    }
    
    
    if (isset($_POST['new_comment']) && isset($_POST['content_id'])) {
        $text = $_POST['new_comment'];
        $content_id = $_POST['content_id'];
    
        if (!empty($text)) {
            $sql = "INSERT INTO Comments (content_id, student_id, text) VALUES (?, ?, ?)";
            if ($stmt = mysqli_prepare($conn, $sql)) {
                mysqli_stmt_bind_param($stmt, "iis", $content_id, $student_id, $text);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
        header("Location: post.php");
        exit();
    }
        

    if (isset($_POST['delete_comment'])) {
        $comment_id = $_POST['comment_id'];
        $sql = "DELETE FROM Comments WHERE comment_id = ? AND student_id = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "ii", $comment_id, $student_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: post.php");
        exit();
    }
    
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Collections</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style type="text/css">
            <?php include '../../styles/notes.css'; ?>
            <?php include '../../styles/posts.css'; ?>
            <?php include '../../styles/videos.css'; ?>
        </style>
    </head>

    <body>
        <?php include("components/navbar.php"); ?>
        <div class="container">
            <div class="tabs">
                <button class="tab-btn active" onclick="showTab('all_notes', this)">Notes</button>
                <button class="tab-btn" onclick="showTab('all_videos', this)">Videos</button>
                <button class="tab-btn" onclick="showTab('all posts', this)">Posts</button>
            </div>


            <div id="all_notes" class="tab-content active">
                <ul class="notes-list">
                    <?php if (count($all_notes) > 0): ?>
                        <?php foreach ($all_notes as $note): ?>
                            <li class="note-item">
                                <div class="profile">
                                <img src="../../uploads/<?php echo htmlspecialchars($note['profile_picture']); ?>" alt="Profile Picture" class="profile-img">
                                    <div class="details">
                                        <span class="profile-name"><?php echo htmlspecialchars($note['firstname']) . ' ' . htmlspecialchars($note['lastname']); ?></span>
                                    </div>
                                </div>
                                <div class="note-title"><?php echo htmlspecialchars($note['title']); ?></div>
                                <div class="note-content"><?php echo nl2br(htmlspecialchars($note['description'])); ?></div>

                                <!-- Status and File -->
                                <div class="status">
                                    <!-- Like Button -->
                                    <form method="POST" action="notes.php">
                                        <button type="submit" name="like" class="like-btn">
                                            <input type="hidden" name="content_id" value="<?php echo $note['content_id']; ?>">
                                            <?php
                                            // Check if the logged-in user has already liked the note
                                            $liked = false;
                                            $sql = "SELECT * FROM Likes WHERE content_id = ? AND student_id = ?";
                                            if ($stmt = mysqli_prepare($conn, $sql)) {
                                                mysqli_stmt_bind_param($stmt, "ii", $note['content_id'], $student_id);
                                                mysqli_stmt_execute($stmt);
                                                $result = mysqli_stmt_get_result($stmt);
                                                if (mysqli_num_rows($result) > 0) {
                                                    $liked = true;
                                                }
                                                mysqli_stmt_close($stmt);
                                            }
                                            ?>
                                            <i class="fa<?php echo $liked ? '-solid' : '-regular'; ?> fa-heart"></i>
                                        </button>
                                    </form>


                                    <!-- Collection Button -->
                                    <form method="POST" action="notes.php">
                                        <button type="submit" name="toggle_collection" class="bookmark-btn">
                                            <input type="hidden" name="content_id" value="<?php echo $note['content_id']; ?>">

                                            <?php
                                            // Check if the note is already in the collection
                                            $is_in_collection = false;
                                            $sql = "SELECT * FROM Collection WHERE content_id = ? AND student_id = ?";
                                            if ($stmt = mysqli_prepare($conn, $sql)) {
                                                mysqli_stmt_bind_param($stmt, "ii", $note['content_id'], $student_id);
                                                mysqli_stmt_execute($stmt);
                                                $result = mysqli_stmt_get_result($stmt);
                                                if (mysqli_num_rows($result) > 0) {
                                                    $is_in_collection = true;
                                                }
                                                mysqli_stmt_close($stmt);
                                            }

                                            // Display appropriate bookmark icon based on collection status
                                            if ($is_in_collection) {
                                                echo '<i class="fa-solid fa-bookmark"></i>';
                                            } else {
                                                echo '<i class="fa-regular fa-bookmark"></i>';
                                            }
                                            ?>
                                        </button>
                                    </form>
                                    
                                    <!-- Comment Button -->
                                    <button type="button" onclick="showComments(<?php echo $note['content_id']; ?>)" class="btn-comment">
                                        <i class="fa-regular fa-comment"></i>
                                    </button>

                                    <!-- Report Button -->
                                    <form method="POST" action="notes.php">
                                        <button type="button" onclick="reportNote(<?php echo $note['content_id']; ?>)" class="btn-report">
                                            <i class="fa-regular fa-flag"></i> 
                                        </button>
                                    </form>

                                    <!-- Download Button -->
                                <?php if (!empty($note['file_url'])): ?>
                                    <a href="../../uploads/?php echo htmlspecialchars($note['file_url']); ?>" class="btn-download" download>
                                    <i class="fa-solid fa-download"></i>
                                    </a>
                                <?php endif; ?>

                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No notes found.</p>
                    <?php endif; ?>
                </ul>
            </div>
            
            <div id="all_videos" class="tab-content">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Search videos..." onkeyup="filterVideos()" />
        </div>
        <div class="video-grid" id="video-grid">
            <?php foreach ($all_videos as $video): ?>
                <div class="video-container">
                    <div class="video-thumbnail">
                        <!-- Use the dynamically generated thumbnail for the preview -->
                        <video class="video-preview-thumbnail" poster="../../uploads/thumbnails/<?= $video['thumbnail_url'] ?>" onclick="playVideo(this)">
                            <source src="../../uploads/videos/<?= $video['file_url'] ?>" type="video/mp4">
                        </video>
                    </div>

                    <div class="video-header">
                        <div class="video-info">
                            <img class="profile-img" src="../../uploads/<?= $profile_picture ?>" alt="Profile Picture">
                            <div class="profile-info">
                                <p class="video-title"><?= $video['title'] ?></p>
                                <p class="video-description"><?= $video['description'] ?></p>
                                <h4><?= $firstname . ' ' . $lastname ?></h4>
                            </div>        
                        </div>
                        <div class="actions">
                            <!-- Like Button -->
                            <form method="POST" action="videos.php">
                                <button type="submit" name="like" class="like-btn">
                                    <input type="hidden" name="content_id" value="<?php echo $video['content_id']; ?>">
                                    <?php
                                    $liked = false;
                                    $sql = "SELECT * FROM Likes WHERE content_id = ? AND student_id = ?";
                                    if ($stmt = mysqli_prepare($conn, $sql)) {
                                        mysqli_stmt_bind_param($stmt, "ii", $video['content_id'], $student_id);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if (mysqli_num_rows($result) > 0) {
                                            $liked = true;
                                        }
                                        mysqli_stmt_close($stmt);
                                    }
                                    ?>
                                    <i class="fa<?php echo $liked ? '-solid' : '-regular'; ?> fa-heart"></i>
                                </button>
                            </form>

                            <!-- Collection Button -->
                            <form method="POST" action="videos.php">
                                <button type="submit" name="toggle_collection" class="bookmark-btn">
                                    <input type="hidden" name="content_id" value="<?php echo $video['content_id']; ?>">

                                    <?php
                                    $is_in_collection = false;
                                    $sql = "SELECT * FROM Collection WHERE content_id = ? AND student_id = ?";
                                    if ($stmt = mysqli_prepare($conn, $sql)) {
                                        mysqli_stmt_bind_param($stmt, "ii", $video['content_id'], $student_id);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if (mysqli_num_rows($result) > 0) {
                                            $is_in_collection = true;
                                        }
                                        mysqli_stmt_close($stmt);
                                    }

                                    // Display appropriate bookmark icon based on collection status
                                    if ($is_in_collection) {
                                        echo '<i class="fa-solid fa-bookmark"></i>';
                                    } else {
                                        echo '<i class="fa-regular fa-bookmark"></i>';
                                    }
                                    ?>
                                </button>
                            </form>
                        
                            <form><!-- Comment Button -->
                            <button type="button" onclick="showComments(<?php echo $video['content_id']; ?>)" class="btn-comment">
                                <i class="fa-regular fa-comment"></i>
                            </button>
                            </form>
                            <!-- Report Button -->
                            <form method="POST" action="videos.php">
                                <button type="button" onclick="reportVideo(<?php echo $video['content_id']; ?>)" class="btn-report">
                                    <i class="fa-regular fa-flag"></i> 
                                </button>
                            </form>
                        </div>
                        
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="all_posts" class="tab-content">
                <ul class="posts-list">
                    <?php if (count($all_posts) > 0): ?>
                        <?php foreach ($all_posts as $post): ?>
                            <li class="post-item">
                                <div class="post-container">
                                    
                                    <div class="post-title"><?php echo nl2br(htmlspecialchars($post['title'])); ?></div>
                                    <div class="post-content"><?php echo nl2br(htmlspecialchars($post['description'])); ?></div>

                                    <!-- Status and File -->
                                    <div class="status">
                                        <div class="profile">
                                            <img src="../../uploads/<?php echo htmlspecialchars($post['profile_picture']); ?>" alt="Profile Picture" class="profile-img">
                                                <div class="details">
                                                    <span class="profile-name"><?php echo htmlspecialchars($post['firstname']) . ' ' . htmlspecialchars($post['lastname']); ?></span>
                                                </div>
                                    </div>
                                    <div class="actions">
                                        <!-- Like Button -->
                                        <form method="POST" action="post.php">
                                            <button type="submit" name="like" class="like-btn">
                                                <input type="hidden" name="content_id" value="<?php echo $post['content_id']; ?>">
                                                <?php
                                                $liked = false;
                                                $sql = "SELECT * FROM Likes WHERE content_id = ? AND student_id = ?";
                                                if ($stmt = mysqli_prepare($conn, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "ii", $post['content_id'], $student_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    if (mysqli_num_rows($result) > 0) {
                                                        $liked = true;
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                }
                                                ?>
                                                <i class="fa<?php echo $liked ? '-solid' : '-regular'; ?> fa-heart"></i>
                                            </button>
                                        </form>

                                        <!-- Collection Button -->
                                        <form method="POST" action="post.php">
                                            <button type="submit" name="toggle_collection" class="bookmark-btn">
                                                <input type="hidden" name="content_id" value="<?php echo $post['content_id']; ?>">

                                                <?php
                                                // Check if the post is already in the collection
                                                $is_in_collection = false;
                                                $sql = "SELECT * FROM Collection WHERE content_id = ? AND student_id = ?";
                                                if ($stmt = mysqli_prepare($conn, $sql)) {
                                                    mysqli_stmt_bind_param($stmt, "ii", $post['content_id'], $student_id);
                                                    mysqli_stmt_execute($stmt);
                                                    $result = mysqli_stmt_get_result($stmt);
                                                    if (mysqli_num_rows($result) > 0) {
                                                        $is_in_collection = true;
                                                    }
                                                    mysqli_stmt_close($stmt);
                                                }

                                                // Display appropriate bookmark icon based on collection status
                                                if ($is_in_collection) {
                                                    echo '<i class="fa-solid fa-bookmark"></i>';
                                                } else {
                                                    echo '<i class="fa-regular fa-bookmark"></i>';
                                                }
                                                ?>
                                            </button>
                                        </form>
                                    
                                        <form><!-- Comment Button -->
                                        <button type="button" onclick="showComments(<?php echo $post['content_id']; ?>)" class="btn-comment">
                                            <i class="fa-regular fa-comment"></i>
                                        </button>
                                        </form>
                                        <!-- Report Button -->
                                        <form method="POST" action="post.php">
                                            <button type="button" onclick="reportPost(<?php echo $post['content_id']; ?>)" class="btn-report">
                                                <i class="fa-regular fa-flag"></i> 
                                            </button>
                                        </form>

                                    </div>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No posts found.</p>
                        <?php endif; ?>
                    </div>
    </div>

        <script>
            function showModal() {
                document.getElementById("addPostModal").style.display = "block";
            }

            function closeModal() {
                document.getElementById("addPostModal").style.display = "none";
            }

            function showTab(tabId, button) {
                document.querySelectorAll(".tab-content").forEach(tab => tab.classList.remove("active"));
                document.getElementById(tabId).classList.add("active");
                document.querySelectorAll(".tab-btn").forEach(btn => btn.classList.remove("active"));
                button.classList.add("active");
            }

            function toggleLike(postId) {
                var icon = document.getElementById("like-icon-" + postId);

                // Toggle the class of the heart icon between solid and regular
                if (icon.classList.contains('fa-regular')) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                } else {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                }

                // Send AJAX request to toggle the like status
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "post.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        console.log("Like status updated successfully");
                    } else {
                        console.error("Error updating like status");
                    }
                };
                xhr.send("like=true&content_id=" + postId);
            }

            function toggleCollection(postId) {
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "post.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText); 

                    var collectionBtn = document.querySelector(`button[name="toggle_collection"][value="${postId}"]`);

                    if (response.status === 'added') {
                        collectionBtn.innerHTML = '<i class="fa-solid fa-bookmark"></i>';  
                        alert("Post added to collection!"); 
                    } 
                    else if (response.status === 'removed') {
                        collectionBtn.innerHTML = '<i class="fa-regular fa-bookmark"></i>';  
                        alert("Post removed from collection!"); 
                    }
                    } else {
                        console.error("Error toggling collection status");
                    }
                };
                xhr.send("toggle_collection=true&content_id=" + postId);
            }

            function reportPost(contentId) {
                document.getElementById("content_id").value = contentId;  
                document.getElementById("reportModal").style.display = "block"; 
            }

            function closeReportModal() {
                document.getElementById("reportModal").style.display = "none"; 
            }

            document.getElementById("reportForm").addEventListener("submit", function(event) {
                event.preventDefault();
                
                var contentId = document.getElementById("content_id").value; 
                var reportReason = document.getElementById("report_reason").value;  

                var xhr = new XMLHttpRequest();
                xhr.open("POST", "post.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        alert("Post reported successfully.");  
                        closeReportModal(); 
                    } else {
                        alert("Error reporting the post."); 
                    }
                };
                xhr.send("report=true&content_id=" + contentId + "&report_reason=" + encodeURIComponent(reportReason)); 
            });

            function showComments(contentId) {
                document.getElementById("comment_content_id").value = contentId;

                var xhr = new XMLHttpRequest();
                xhr.open("GET", "post.php?fetch_comments=" + contentId, true);
                xhr.onload = function () {
                    if (xhr.status === 200) {
                        document.getElementById("comments-container").innerHTML = xhr.responseText;
                        document.getElementById("commentModal").style.display = "block";
                    } else {
                        alert("Error fetching comments.");
                    }
                };
                xhr.send();
            }

            function closeCommentModal() {
                document.getElementById("commentModal").style.display = "none";
            }

            function deleteComment(commentId) {
                if (confirm("Are you sure you want to delete this comment?")) {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "post.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.onload = function () {
                        if (xhr.status === 200) {
                            alert("Comment deleted successfully.");
                            // Refresh comments
                            var contentId = document.getElementById("comment_content_id").value;
                            showComments(contentId);
                        } else {
                            alert("Error deleting comment.");
                        }
                    };
                    xhr.send("delete_comment=true&comment_id=" + commentId);
                }
            }

        </script>
    </body>
</html>

