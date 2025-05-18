<?php
include('config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$success_message = $error_message = "";

// Handle approve or reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // approve or reject

    // First, verify this request belongs to a group created by this user
    $verify_sql = "
        SELECT jr.id FROM join_requests jr
        JOIN groups g ON jr.group_id = g.id
        WHERE jr.id = ? AND g.created_by = ?
    ";
    $stmt = $conn->prepare($verify_sql);
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        if ($action === 'approve') {
            // Update request status to approved
            $update_sql = "UPDATE join_requests SET status = 'approved' WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $request_id);
            if ($update_stmt->execute()) {
                // Add user to group_members table
                $get_user_group_sql = "SELECT user_id, group_id FROM join_requests WHERE id = ?";
                $get_stmt = $conn->prepare($get_user_group_sql);
                $get_stmt->bind_param("i", $request_id);
                $get_stmt->execute();
                $get_stmt->bind_result($join_user_id, $join_group_id);
                $get_stmt->fetch();
                $get_stmt->close();

                // Insert into group_members if not already a member
                $check_member_sql = "SELECT id FROM group_members WHERE user_id = ? AND group_id = ?";
                $check_stmt = $conn->prepare($check_member_sql);
                $check_stmt->bind_param("ii", $join_user_id, $join_group_id);
                $check_stmt->execute();
                $check_stmt->store_result();

                if ($check_stmt->num_rows === 0) {
                    $insert_member_sql = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
                    $insert_stmt = $conn->prepare($insert_member_sql);
                    $insert_stmt->bind_param("ii", $join_group_id, $join_user_id);
                    $insert_stmt->execute();
                    $insert_stmt->close();
                }
                $check_stmt->close();

                $success_message = "Request approved and user added to the group.";
            } else {
                $error_message = "Failed to approve request.";
            }
            $update_stmt->close();

        } elseif ($action === 'reject') {
            // Delete the join request
            $delete_sql = "DELETE FROM join_requests WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $request_id);
            if ($delete_stmt->execute()) {
                $success_message = "Request rejected and deleted.";
            } else {
                $error_message = "Failed to reject request.";
            }
            $delete_stmt->close();
        }
    } else {
        $error_message = "Invalid request or unauthorized.";
    }
    $stmt->close();
}

// Fetch join requests for groups created by logged-in user
$sql = "
    SELECT jr.id AS request_id, jr.user_id, jr.group_id, jr.status, jr.requested_at,
           u.username, g.name AS group_name
    FROM join_requests jr
    JOIN users u ON jr.user_id = u.id
    JOIN groups g ON jr.group_id = g.id
    WHERE g.created_by = ?
    AND jr.status = 'pending'
    ORDER BY jr.requested_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Join Requests</title>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="./css/style.css" />

</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5">
    <h2>Pending Join Requests for Your Groups</h2>

    <?php if (!empty($success_message)) : ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <?php if (!empty($error_message)) : ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if ($result->num_rows > 0) : ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Group</th>
                    <th>Requested At</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><?php echo htmlspecialchars($row['group_name']); ?></td>
                    <td><?php echo $row['requested_at']; ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                            <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                            <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure to reject this request?')">Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No pending join requests.</p>
    <?php endif; ?>

</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
