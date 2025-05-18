<?php
include('config.php');
session_start();

if (!isset($_SESSION["id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$group_id = intval($_GET['id'] ?? 0);

// Fetch group to edit
$group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE id = $group_id AND created_by = $user_id"));
if (!$group) {
    die("Group not found or unauthorized.");
}

$success_message = $error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $groupName = trim($_POST["group_name"]);
    $description = trim($_POST["description"]);
    $type = $_POST["type"];
    $imageName = $group['image']; // default to existing image

    // New image upload
    if (isset($_FILES["group_image"]) && $_FILES["group_image"]["error"] == 0) {
        $imageTmp = $_FILES["group_image"]["tmp_name"];
        $imageExt = pathinfo($_FILES["group_image"]["name"], PATHINFO_EXTENSION);
        $imageName = uniqid("group_") . "." . $imageExt;
        $targetPath = "images/" . $imageName;
        move_uploaded_file($imageTmp, $targetPath);
    }

    $update = "UPDATE groups SET name=?, description=?, type=?, image=? WHERE id=? AND created_by=?";
    $stmt = mysqli_prepare($conn, $update);
    mysqli_stmt_bind_param($stmt, "ssssii", $groupName, $description, $type, $imageName, $group_id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Group updated successfully!";
        // Refresh data
        $group = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM groups WHERE id = $group_id"));
    } else {
        $error_message = "Update failed: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Group</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-5 mb-5">
    <div class="card mx-auto" style="max-width: 600px;">
        <div class="card-body">
            <h2>Edit Group</h2>

            <?php if (!empty($success_message)) { ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php } ?>
            <?php if (!empty($error_message)) { ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php } ?>

            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Group Name</label>
                    <input type="text" name="group_name" class="form-control" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($group['description']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Group Type</label>
                    <select name="type" class="form-control">
                        <option value="public" <?php if ($group['type'] == 'public') echo "selected"; ?>>Public</option>
                        <option value="private" <?php if ($group['type'] == 'private') echo "selected"; ?>>Private</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Group Image</label><br>
                    <?php if (!empty($group['image'])) { ?>
                        <img src="images/<?php echo $group['image']; ?>" width="80" height="60"><br><br>
                    <?php } ?>
                    <input type="file" name="group_image" class="form-control-file" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">Update Group</button>
                <a href="my_groups.php" class="btn btn-secondary">Back</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
