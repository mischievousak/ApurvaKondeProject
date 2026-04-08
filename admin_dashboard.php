<?php
include 'functions.php'; // Includes session_start()
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('index.php');
}

include 'db_connect.php';
$admin_user_id = $_SESSION['user_id'];

// --- HANDLE POST ACTIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle deleting a user
    if (isset($_POST['delete_user_id'])) {
        $user_to_delete = $_POST['delete_user_id'];
        if ($user_to_delete != $admin_user_id) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_to_delete);
            set_message($stmt->execute() ? 'success' : 'error', $stmt->execute() ? 'User deleted!' : 'Error deleting user.');
        } else {
            set_message('error', 'You cannot delete your own admin account.');
        }
    }
    // Handle deleting a subscription
    elseif (isset($_POST['delete_sub_id'])) {
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->bind_param("i", $_POST['delete_sub_id']);
        set_message($stmt->execute() ? 'success' : 'error', $stmt->execute() ? 'Subscription deleted!' : 'Error deleting subscription.');
    }
    redirect('admin_dashboard.php');
}

// --- FETCH DATA FOR DISPLAY ---
// 1. Get stats
$total_users = $conn->query("SELECT COUNT(id) as total FROM users WHERE role != 'admin'")->fetch_assoc()['total'] ?? 0;
$total_revenue = $conn->query("SELECT SUM(cost) as total FROM subscriptions")->fetch_assoc()['total'] ?? 0.00;

// 2. Get all subscriptions with user details
$sql_subs = "SELECT s.id, s.service_name, s.cost, s.category, s.plan_type, u.username, u.email 
             FROM subscriptions s JOIN users u ON s.user_id = u.id 
             ORDER BY u.username, s.renewal_date ASC";
$subscriptions = $conn->query($sql_subs)->fetch_all(MYSQLI_ASSOC);

// 3. Get all non-admin users
$users = $conn->query("SELECT id, username, email FROM users WHERE role != 'admin' ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();

$page_title = 'Admin Dashboard';
include 'templates/header.php';
?>

<div class="page-header">
    <h1>Admin Dashboard</h1>
    <a href="logout.php">Logout</a>
</div>

<?php display_message(); ?>

<div class="stats-container">
    <div class="stat-card">
        <span class="stat-title">Total Subscribers</span>
        <span class="stat-value"><?php echo $total_users; ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-title">Total Revenue</span>
        <span class="stat-value">$<?php echo number_format($total_revenue, 2); ?></span>
    </div>
</div>

<div class="dashboard-section">
    <h2>Subscription Management</h2>
    <table>
        <thead>
            <tr><th>User</th><th>Email</th><th>Service</th><th>Category</th><th>Plan</th><th>Cost</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php if (empty($subscriptions)): ?>
                <tr><td colspan="7" class="text-center">No subscriptions found.</td></tr>
            <?php else: foreach ($subscriptions as $sub): ?>
                <tr>
                    <td><?php echo htmlspecialchars($sub['username']); ?></td>
                    <td><?php echo htmlspecialchars($sub['email']); ?></td>
                    <td><?php echo htmlspecialchars($sub['service_name']); ?></td>
                    <td><?php echo htmlspecialchars($sub['category']); ?></td>
                    <td><?php echo htmlspecialchars($sub['plan_type']); ?></td>
                    <td>$<?php echo htmlspecialchars($sub['cost']); ?></td>
                    <td>
                        <form action="admin_dashboard.php" method="POST">
                            <input type="hidden" name="delete_sub_id" value="<?php echo $sub['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="dashboard-section">
    <h2>User Management</h2>
    <table>
        <thead>
            <tr><th>Username</th><th>Registered Email</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="3" class="text-center">No users found.</td></tr>
            <?php else: foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <form action="admin_dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this user and all their subscriptions?');">
                            <input type="hidden" name="delete_user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-compact">Delete User</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>