<?php
include('config.php');
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['id'];

// Get group_id from query string
if (!isset($_GET['group_id']) || !is_numeric($_GET['group_id'])) {
    die("Invalid group ID.");
}
$group_id = intval($_GET['group_id']);

// Fetch group details
$sql_group = "
    SELECT g.*, u.username AS creator_username
    FROM groups g
    JOIN users u ON g.created_by = u.id
    WHERE g.id = ?
";
$stmt = $conn->prepare($sql_group);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Group not found.");
}

$group = $result->fetch_assoc();
$stmt->close();

// Count total users in group_members
$sql_count_members = "SELECT COUNT(*) AS total_members FROM group_members WHERE group_id = ?";
$stmt = $conn->prepare($sql_count_members);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$stmt->bind_result($total_members);
$stmt->fetch();
$stmt->close();

// Handle new message submission
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message === '') {
        $error_message = "Message cannot be empty.";
    } else {
        // Check if user is member of group (only members can send message)
        $check_member_sql = "SELECT id FROM group_members WHERE user_id = ? AND group_id = ?";
        $check_stmt = $conn->prepare($check_member_sql);
        $check_stmt->bind_param("ii", $user_id, $group_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows === 0) {
            $error_message = "You are not a member of this group, cannot send messages.";
        } else {
            $insert_sql = "INSERT INTO group_chats (group_id, user_id, message) VALUES (?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("iis", $group_id, $user_id, $message);
            if (!$insert_stmt->execute()) {
                $error_message = "Failed to send message.";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

// Fetch all chat messages for the group
$sql_messages = "
    SELECT gc.*, u.username
    FROM group_chats gc
    JOIN users u ON gc.user_id = u.id
    WHERE gc.group_id = ?
    ORDER BY gc.sent_at ASC
";
$stmt = $conn->prepare($sql_messages);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$messages_result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Chat - <?php echo htmlspecialchars($group['name']); ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="./css/style.css" />
    <style>
        body {
            background-color: #f8f9fa;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            margin-top: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #343a40;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        h4 {
            color: #343a40;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        p strong {
            font-weight: 500;
            color: #555;
        }
        .chat-container {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .chat-message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 6px;
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
        }
        .my-message {
            background-color: #d1ecf1;
            border-color: #bee5eb;
        }
        .chat-username {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .chat-time {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        .form-group label {
            font-weight: bold;
            color: #343a40;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .alert-danger {
            margin-top: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2><?php echo htmlspecialchars($group['name']); ?> Chat</h2>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($group['description'])); ?></p>
    <p><strong>Type:</strong> <span class="badge badge-<?php echo ($group['type'] === 'public' ? 'success' : 'warning'); ?>"><?php echo ucfirst($group['type']); ?></span></p>
    <p><strong>Created by:</strong> <span class="text-info"><?php echo htmlspecialchars($group['creator_username']); ?></span></p>
    <p><strong>Members:</strong> <span class="badge badge-pill badge-secondary"><?php echo $total_members; ?></span></p>

    <hr class="my-4" />

    <h4>Group Chat</h4>
    <div class="chat-container" id="chat-box">
        <?php if ($messages_result->num_rows === 0): ?>
            <p class="text-muted">No messages yet. Be the first to say something!</p>
        <?php else: ?>
            <?php while ($msg = $messages_result->fetch_assoc()): ?>
                <div class="chat-message <?php echo ($msg['user_id'] == $user_id) ? 'my-message' : ''; ?>">
                    <div class="chat-username"><?php echo htmlspecialchars($msg['username']); ?></div>
                    <div class="chat-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                    <div class="chat-time text-muted small"><?php echo date('Y-m-d H:i:s', strtotime($msg['sent_at'])); ?></div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <form method="post" class="mt-3">
        <div class="form-group">
            <label for="message">Your message:</label>
            <textarea name="message" id="message" class="form-control" rows="3" placeholder="Type your message here" required></textarea>
        </div>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script>
    // Scroll chat to bottom on load and on new messages (basic implementation)
    window.onload = function () {
        var chatBox = document.getElementById('chat-box');
        chatBox.scrollTop = chatBox.scrollHeight;
    };

    // You might want to implement a more robust way to automatically scroll
    // when new messages are added dynamically (e.g., with AJAX polling).
    $('form').on('submit', function() {
        setTimeout(function() {
            var chatBox = $('#chat-box');
            chatBox.scrollTop(chatBox[0].scrollHeight);
        }, 500); // Adjust delay as needed
    });
</script>
</body>
</html>

<?php
$conn->close();
?>