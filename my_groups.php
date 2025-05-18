<?php
include('config.php');
session_start();

if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$success_message = "";

// Handle delete request
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $group_id = intval($_GET['delete']);

    // Only delete if group belongs to current user
    $check = mysqli_query($conn, "SELECT id FROM groups WHERE id = $group_id AND created_by = $user_id");
    if (mysqli_num_rows($check) == 1) {
        mysqli_query($conn, "DELETE FROM groups WHERE id = $group_id");
        $success_message = "Group deleted successfully!";
    }
}

// Fetch user's groups
$query = "SELECT * FROM groups WHERE created_by = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Groups</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <h2>My Created Groups</h2>

    <?php if (!empty($success_message)) { ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php } ?>

    <table class="table table-bordered table-striped">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Group Name</th>
                <th>Type</th>
                <th>Image</th>
                <th>Description</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (mysqli_num_rows($result) > 0) {
            $i = 1;
            while ($row = mysqli_fetch_assoc($result)) { ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo $row['type']; ?></td>
                    <td>
                        <?php if (!empty($row['image'])) { ?>
                            <img src="images/<?php echo $row['image']; ?>" width="80" height="60">
                        <?php } else { echo "No Image"; } ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo date("d M, Y", strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="edit_group.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this group?');">Delete</a>
                    </td>
                </tr>
            <?php } 
        } else { ?>
            <tr><td colspan="7" class="text-center">No groups found.</td></tr>
        <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
