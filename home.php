<?php
include('config.php');
session_start();

// Check if user is logged in
if (!isset($_SESSION["id"]) || empty($_SESSION["id"])) {
    header("location: index.php");
    exit;
}

$user_id = $_SESSION["id"];

// Fetch username
$sql = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

// Handle join group action
if (isset($_GET['join']) && is_numeric($_GET['join'])) {
    $group_id = intval($_GET['join']);

    // Check if group is public and user is not already a member
    $res = $conn->query("SELECT type FROM groups WHERE id = $group_id");
    if ($res && $row = $res->fetch_assoc()) {
        if ($row['type'] === 'public') {
            // Check membership
            $check = $conn->query("SELECT id FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
            if ($check->num_rows == 0) {
                $conn->query("INSERT INTO group_members (group_id, user_id) VALUES ($group_id, $user_id)");
            }
        }
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle request to join private group
if (isset($_GET['request_join']) && is_numeric($_GET['request_join'])) {
    $group_id = intval($_GET['request_join']);

    // Check if group is private and user is not already member or pending
    $res = $conn->query("SELECT type FROM groups WHERE id = $group_id");
    if ($res && $row = $res->fetch_assoc()) {
        if ($row['type'] === 'private') {
            // Check membership and request
            $checkMember = $conn->query("SELECT id FROM group_members WHERE group_id = $group_id AND user_id = $user_id");
            $checkRequest = $conn->query("SELECT id FROM join_requests WHERE group_id = $group_id AND user_id = $user_id AND status = 'pending'");

            if ($checkMember->num_rows == 0 && $checkRequest->num_rows == 0) {
                $conn->query("INSERT INTO join_requests (group_id, user_id, status) VALUES ($group_id, $user_id, 'pending')");
            }
        }
    }
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Handle filtering by type and search
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? ''; // 'public', 'private', or empty

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

$sql = "SELECT * FROM groups $where_sql ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);

if (count($params) > 0) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>StudyBuddy Dashboard</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <link rel="stylesheet" href="./css/style.css" />
    <style>
        .group-card {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .group-image {
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .card-body {
            padding: 15px;
            display: flex;
            flex-direction: column;
        }
        .card-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .card-subtitle {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }
        .card-text-description {
            font-size: 1rem;
            color: #343a40;
            height: 60px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .btn-primary, .btn-success, .btn-secondary, .btn-warning {
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-align: center;
            display: block;
            width: 100%;
            margin-top: 0.5rem;
        }
        .btn-primary {
            background-color: #007bff;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-primary:hover, .btn-success:hover, .btn-secondary:hover, .btn-warning:hover {
            opacity: 0.9;
        }
        .form-inline .form-control {
            border-radius: 5px;
            margin-right: 10px;
        }
        .form-inline button.btn {
            border-radius: 5px;
        }
        .form-inline a.btn {
            border-radius: 5px;
            text-decoration: none;
        }
        .search-container {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .search-container h1 {
            font-size: 1.75rem;
            margin-bottom: 15px;
            color: #343a40;
        }
        .search-container .form-inline {
            display: flex;
            align-items: center;
        }
        .search-container .form-control {
            flex-grow: 1;
        }
        .search-container button.btn {
            margin-left: 10px;
        }
        .search-container a.btn {
            margin-left: 10px;
        }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div class="container mt-4">
    <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>

    <div class="search-container">
        <h1>Find Groups</h1>
        <form method="get" class="form-inline mb-3">
            <input type="text" name="search" class="form-control mr-2 mb-2" placeholder="Search groups by title" value="<?php echo htmlspecialchars($search); ?>" />

            <select name="type" class="form-control mr-2 mb-2">
                <option value="">All Types</option>
                <option value="public" <?php if ($type_filter === 'public') echo 'selected'; ?>>Public</option>
                <option value="private" <?php if ($type_filter === 'private') echo 'selected'; ?>>Private</option>
            </select>

            <button type="submit" class="btn btn-primary mb-2">Filter</button>
            <?php if (!empty($search) || !empty($type_filter)) { ?>
                <a href="?" class="btn btn-secondary ml-2 mb-2">Clear</a>
            <?php } ?>
        </form>
    </div>

    <h2>Available Groups</h2>

    <div class="row">
        <?php if ($result->num_rows > 0):
            while ($group = $result->fetch_assoc()):
                $gid = $group['id'];

                // Check if user is member of this group
                $is_member = false;
                $is_pending = false;

                $mem_res = $conn->query("SELECT id FROM group_members WHERE group_id = $gid AND user_id = $user_id");
                if ($mem_res && $mem_res->num_rows > 0) {
                    $is_member = true;
                } else if ($group['type'] == 'private') {
                    $req_res = $conn->query("SELECT status FROM join_requests WHERE group_id = $gid AND user_id = $user_id AND status = 'pending'");
                    if ($req_res && $req_res->num_rows > 0) {
                        $is_pending = true;
                    }
                }

                // Get total members count
                $count_res = $conn->query("SELECT COUNT(*) as total FROM group_members WHERE group_id = $gid");
                $members_count = 0;
                if ($count_res) {
                    $row = $count_res->fetch_assoc();
                    $members_count = $row['total'];
                }

                $image_path = !empty($group['image']) ? 'images/' . htmlspecialchars($group['image']) : 'images/default-group.png';
                ?>
                <div class="col-md-4">
                    <div class="card group-card">
                        <img src="<?php echo $image_path; ?>" class="card-img-top group-image" alt="<?php echo htmlspecialchars($group['name']); ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($group['name']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted"><?php echo ucfirst($group['type']); ?> Group</h6>
                            <p class="card-text card-text-description"><?php echo htmlspecialchars($group['description']); ?></p>
                            <p class="mb-2"><small>Total Members: <?php echo $members_count; ?></small></p>

                            <div class="mt-auto">
                                <?php if ($is_member) { ?>
                                    <a href="chat.php?group_id=<?php echo $gid; ?>" class="btn btn-success btn-block">Chat Now</a>
                                <?php } else if ($group['type'] == 'public') { ?>
                                    <a href="?join=<?php echo $gid; ?>" class="btn btn-primary btn-block">Join Group</a>
                                <?php } else if ($group['type'] == 'private') {
                                    if ($is_pending) { ?>
                                        <button class="btn btn-warning btn-block" disabled>Request Pending</button>
                                    <?php } else { ?>
                                        <a href="?request_join=<?php echo $gid; ?>" class="btn btn-secondary btn-block">Request to Join</a>
                                    <?php }
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
        <?php endwhile;
        else: ?>
            <div class="col-12"><p>No groups found.</p></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>