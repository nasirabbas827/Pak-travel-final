<?php
include('config.php');
session_start();

// Check login
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];
$success_message = $error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $groupName = trim($_POST["group_name"]);
    $description = trim($_POST["description"]);
    $type = $_POST["type"];
    $imageName = "";

    // Handle image upload
    if (isset($_FILES["group_image"]) && $_FILES["group_image"]["error"] == 0) {
        $imageTmp = $_FILES["group_image"]["tmp_name"];
        $imageExt = pathinfo($_FILES["group_image"]["name"], PATHINFO_EXTENSION);
        $imageName = uniqid("group_") . "." . $imageExt;
        $targetPath = "images/" . $imageName;

        if (!move_uploaded_file($imageTmp, $targetPath)) {
            $error_message = "Failed to upload image.";
        }
    }

    if (empty($error_message)) {
        // Insert into groups table
        $insert_group = "INSERT INTO groups (name, description, type, created_by, image) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_group);
        mysqli_stmt_bind_param($stmt, "sssss", $groupName, $description, $type, $user_id, $imageName);

        if (mysqli_stmt_execute($stmt)) {
            $group_id = mysqli_insert_id($conn);

            // Add creator as first group member
            $add_member = "INSERT INTO group_members (group_id, user_id) VALUES (?, ?)";
            $member_stmt = mysqli_prepare($conn, $add_member);
            mysqli_stmt_bind_param($member_stmt, "ii", $group_id, $user_id);
            mysqli_stmt_execute($member_stmt);
            mysqli_stmt_close($member_stmt);

            $success_message = "Group created successfully!";
        } else {
            $error_message = "Group creation failed: " . mysqli_error($conn);
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Group</title>
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
                <h2>Create a New Group</h2>

                <?php if (!empty($success_message)) { ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php } ?>

                <?php if (!empty($error_message)) { ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php } ?>

                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Group Name</label>
                        <input type="text" name="group_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Group Type</label>
                        <select name="type" class="form-control">
                            <option value="public">Public</option>
                            <option value="private">Private</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Group Image</label>
                        <input type="file" name="group_image" class="form-control-file" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                    <a class="btn btn-dark" href="my_groups.php">View Groups</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

