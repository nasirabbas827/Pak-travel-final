<?php
include('config.php');
session_start();

// Handle search query
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';

$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($search)) {
    $where_clauses[] = "name LIKE ?";
    $params[] = "%$search%";
    $param_types .= "s";
}

if ($type_filter === 'public' || $type_filter === 'private') {
    $where_clauses[] = "type = ?";
    $params[] = $type_filter;
    $param_types .= "s";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Fetch the latest 6 public groups based on search criteria
$sql_latest_groups = "SELECT * FROM groups $where_sql ORDER BY created_at DESC LIMIT 6";
$stmt_latest_groups = $conn->prepare($sql_latest_groups);
if (count($params) > 0) {
    $stmt_latest_groups->bind_param($param_types, ...$params);
}
$stmt_latest_groups->execute();
$result_latest_groups = $stmt_latest_groups->get_result();
$latest_groups = $result_latest_groups->fetch_all(MYSQLI_ASSOC);
$stmt_latest_groups->close();

// Fetch total number of groups based on search criteria
$sql_total_groups = "SELECT COUNT(*) AS total FROM groups $where_sql";
$stmt_total_groups = $conn->prepare($sql_total_groups);
if (count($params) > 0) {
    $stmt_total_groups->bind_param($param_types, ...$params);
}
$stmt_total_groups->execute();
$result_total_groups = $stmt_total_groups->get_result();
$total_groups = $result_total_groups->fetch_assoc()['total'];
$stmt_total_groups->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pak Travel Buddy - Explore, Connect, Travel</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="./css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }
        .jumbotron {
            background-image: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('./images/hotel.jpg');
            background-size: cover;
            color: white;
            padding: 100px 0;
            text-align: center;
            margin-bottom: 0;
        }
        .jumbotron h1 {
            font-size: 4rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .jumbotron p {
            font-size: 1.5rem;
            margin-bottom: 30px;
        }
        .features-section {
            padding: 60px 0;
            background-color: #fff;
        }
        .feature-item {
            text-align: center;
            padding: 20px;
        }
        .feature-icon {
            font-size: 3rem;
            color: #28a745;
            margin-bottom: 15px;
        }
        .feature-item h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #343a40;
        }
        .feature-item p {
            color: #6c757d;
        }
        .explore-groups-section {
            padding: 60px 0;
            background-color: #f2f2f2;
        }
        .group-card {
            border: none;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        .group-card img {
            height: 200px;
            object-fit: cover;
        }
        .group-card-body {
            padding: 20px;
            text-align: center;
        }
        .group-card-title {
            font-size: 1.25rem;
            margin-bottom: 10px;
            color: #343a40;
        }
        .group-card-type {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 15px;
        }
        .call-to-action {
            padding: 80px 0;
            background-image: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('./images/travel-pakistan.jpg');
            background-size: cover;
            color: white;
            text-align: center;
        }
        .call-to-action h2 {
            font-size: 3rem;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .call-to-action p {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .footer {
            background-color: #343a40;
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
        .footer h5 {
            color: white;
            margin-bottom: 15px;
        }
        .footer a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .footer a:hover {
            color: white;
        }
        .search-bar {
            padding: 40px 0;
            background-color: #e9ecef;
            text-align: center;
        }
        .search-bar h2 {
            margin-bottom: 20px;
            color: #343a40;
        }
        .search-bar form {
            display: inline-flex;
            width: 80%;
            max-width: 600px;
        }
        .search-bar .form-control {
            border-radius: 5px 0 0 5px;
            border: 1px solid #ced4da;
        }
        .search-bar .btn {
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

    <div class="jumbotron text-center">
        <h1>Discover the Beauty of Pakistan with Fellow Travelers</h1>
        <p>Connect with like-minded explorers, plan your adventures, and share your experiences.</p>
        <a href="register.php" class="btn btn-primary btn-lg">Join the Community</a>
    </div>

    <div class="features-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4 feature-item">
                    <i class="fas fa-users feature-icon"></i>
                    <h3>Connect with Travelers</h3>
                    <p>Find and join groups based on your travel interests and destinations within Pakistan.</p>
                </div>
                <div class="col-md-4 feature-item">
                    <i class="fas fa-map-marked-alt feature-icon"></i>
                    <h3>Explore Destinations</h3>
                    <p>Discover popular and hidden gems across Pakistan, from the mountains to the beaches.</p>
                </div>
                <div class="col-md-4 feature-item">
                    <i class="fas fa-comments feature-icon"></i>
                    <h3>Share Your Adventures</h3>
                    <p>Discuss your travel plans, share tips, and post photos within your travel groups.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="search-bar">
        <div class="container">
            <h2>Find Your Perfect Travel Group</h2>
            <form class="form-inline justify-content-center" action="" method="get">
                <input class="form-control mr-sm-2" type="search" name="search" placeholder="Search groups by name" aria-label="Search" value="<?php echo htmlspecialchars($search); ?>">
                <select name="type" class="form-control mr-sm-2">
                    <option value="">All Types</option>
                    <option value="public" <?php if ($type_filter === 'public') echo 'selected'; ?>>Public</option>
                    <option value="private" <?php if ($type_filter === 'private') echo 'selected'; ?>>Private</option>
                </select>
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                <?php if (!empty($search) || !empty($type_filter)): ?>
                    <a href="index.php" class="btn btn-outline-secondary my-2 my-sm-0 ml-2">Clear</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="explore-groups-section">
        <div class="container">
            <h2 class="text-center mb-4">Explore Popular Travel Groups</h2>
            <p class="text-center mb-5">Join existing groups or <a href="create_group.php">create your own</a> to start your adventure!</p>
            <div class="row">
                <?php if (!empty($latest_groups)): ?>
                    <?php foreach ($latest_groups as $group): ?>
                        <div class="col-md-4">
                            <div class="card group-card">
                                <img src="<?php echo !empty($group['image']) ? 'images/' . htmlspecialchars($group['image']) : 'images/default-group.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($group['name']); ?>">
                                <div class="card-body group-card-body">
                                    <h5 class="card-title group-card-title"><?php echo htmlspecialchars($group['name']); ?></h5>
                                    <p class="card-text group-card-type"><span class="badge badge-<?php echo ($group['type'] === 'public' ? 'success' : 'warning'); ?>"><?php echo ucfirst($group['type']); ?></span></p>
                                    <p class="card-text"><?php echo substr(htmlspecialchars($group['description']), 0, 50); ?>...</p>
                                    <a href="login.php" class="btn btn-outline-primary btn-sm">View & Join</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p>No groups found based on your search criteria.</p>
                        <?php if (!empty($search) || !empty($type_filter)): ?>
                            <p><a href="index.php">View All Groups</a></p>
                        <?php else: ?>
                            <p>Be the first to create one!</p>
                            <a href="create_group.php" class="btn btn-success">Create New Group</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="text-center mt-3">
                <p>Total Groups: <span class="badge badge-info"><?php echo $total_groups; ?></span></p>
                <a href="login.php" class="btn btn-secondary">See All Groups</a>
            </div>
        </div>
    </div>

    <div class="call-to-action">
        <h2>Ready to Explore Pakistan?</h2>
        <p>Join our vibrant community of travelers and start planning your dream adventure today!</p>
        <a href="register.php" class="btn btn-light btn-lg">Sign Up Now</a>
    </div>

    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Pak Travel Buddy</h5>
                    <p>Connecting travelers to explore the beauty of Pakistan together.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">About Us</a></li>
                        <li><a href="#" class="text-white">Explore Destinations</a></li>
                        <li><a href="#" class="text-white">Community Guidelines</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Follow Us</h5>
                    <div class="mt-2">
                        <a href="#" class="text-white mr-2"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white mr-2"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-light mt-3 mb-2">
            <div class="text-center">
                <p>&copy; 2025 Pak Travel Buddy. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>