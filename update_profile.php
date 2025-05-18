<?php
include('config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];

// Fetch user details
$sql = "SELECT id, username, email, phone, age, full_name, bio FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $fetched_id, $username, $email, $phone, $age, $full_name, $bio);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newUsername = $_POST["username"];
    $newEmail = $_POST["email"];
    $newPhone = $_POST["phone"];
    $newAge = $_POST["age"];
    $newFullName = $_POST["full_name"];
    $newBio = $_POST["bio"];
    $newPassword = trim($_POST["password"]);

    if (!empty($newPassword)) {
        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $update_query = "UPDATE users 
                         SET username = ?, email = ?, phone = ?, age = ?, full_name = ?, bio = ?, password = ? 
                         WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "sssssssi", $newUsername, $newEmail, $newPhone, $newAge, $newFullName, $newBio, $hashedPassword, $user_id);
    } else {
        $update_query = "UPDATE users 
                         SET username = ?, email = ?, phone = ?, age = ?, full_name = ?, bio = ? 
                         WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, "ssssssi", $newUsername, $newEmail, $newPhone, $newAge, $newFullName, $newBio, $user_id);
    }

    if (mysqli_stmt_execute($update_stmt)) {
        $success_message = "Profile updated successfully!";
        $_SESSION["username"] = $newUsername;
    } else {
        $error_message = "Error updating profile: " . mysqli_error($conn);
    }

    mysqli_stmt_close($update_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Update Profile</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <?php include('navbar.php'); ?>

    <div class="container mt-4 mb-4">
        <div class="card mx-auto" style="max-width: 500px;">
            <div class="card-body">
                <h2>Update Profile</h2>

                <?php if (isset($success_message)) { ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php } ?>

                <?php if (isset($error_message)) { ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php } ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" class="form-control" name="username" value="<?php echo $username; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" class="form-control" name="email" value="<?php echo $email; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone:</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo $phone; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="number" class="form-control" name="age" value="<?php echo $age; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo $full_name; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="bio">Bio:</label>
                        <textarea class="form-control" name="bio" rows="4"><?php echo $bio; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="password">New Password (leave blank to keep current):</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS CDN -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script> 
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script> 
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> 
</body>
</html>
