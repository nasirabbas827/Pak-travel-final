<?php
include('config.php');

// define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = "";

// check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        $email = trim($_POST["email"]);
    }

    // validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // if no errors, check credentials and log in user
    if (empty($email_err) && empty($password_err)) {
        $sql = "SELECT id, email, password, status FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $param_email);
        $param_email = $email;
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) == 1) {
            mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password, $status);
            if (mysqli_stmt_fetch($stmt)) {
                if ($status != 'approved') {
                    $email_err = "Your account is not approved yet.";
                } elseif (password_verify($password, $hashed_password)) {
                    // password is correct, start session and log in user
                    session_start();
                    $_SESSION["id"] = $id;
                    $_SESSION["email"] = $email;
                    header("location: home.php");
                    exit;
                } else {
                    // password is incorrect
                    $password_err = "The password you entered is incorrect.";
                }
            }
        } else {
            // email not found in database
            $email_err = "No account found with that email.";
        }

        mysqli_stmt_close($stmt);
    }

    mysqli_close($conn);
}
?>


<!DOCTYPE html>
<html>

<head>
    <title>User Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/style.css">

    <style>
        body {
            background-color: #f7f7f7;
            font-family: 'Arial', sans-serif;
        }

        .login-container {
            max-width: 400px;
            margin: 80px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            font-size: 1.8em;
            margin-bottom: 20px;
        }

        .login-container p {
            font-size: 0.9em;
            color: #555;
        }

        .form-group label {
            font-weight: bold;
        }

        .invalid-feedback {
            color: #e74c3c;
            font-size: 0.9em;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
        }

        .form-group a {
            font-size: 0.9em;
            color: #007bff;
        }

        .form-group a:hover {
            text-decoration: underline;
        }

        .alert {
            margin-bottom: 20px;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>

<body>
<?php include('navbar.php'); ?>

<div class="container">
    <div class="login-container">
        <h2 class="text-center">User Login</h2>
        <p class="text-center">Please enter your credentials to log in.</p>

        <!-- Display error or success messages -->
        <?php if (!empty($email_err) || !empty($password_err)): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Error!</strong> <?php echo $email_err ?: $password_err; ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">

            <div class="form-group">
                <label for="email">Email</label>
                <input type="text" id="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                <span class="invalid-feedback"><?php echo $email_err; ?></span>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                <span class="invalid-feedback"><?php echo $password_err; ?></span>
            </div>

            <div class="form-group text-center">
                <input type="submit" value="Log In" class="btn btn-primary login-btn">
            </div>

        </form>
        
        <p class="text-center">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</div>

<!-- Bootstrap JS & dependencies -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
