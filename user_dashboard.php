<?php
include 'functions.php'; // Includes session_start()
if (!isset($_SESSION['user_id'])) {
    redirect('index.php');
}
include 'db_connect.php';
$user_id = $_SESSION['user_id'];

// --- HANDLE POST ACTIONS ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add a new subscription
    if (isset($_POST['add_subscription'])) {
        $plan_type = $_POST['plan_type'];
        $renewal_date = new DateTime($_POST['subscription_date']);
        $interval_map = ['Monthly' => '+1 month', 'Quarterly' => '+3 months', 'Semiannually' => '+6 months', 'Annually' => '+1 year'];
        $renewal_date->modify($interval_map[$plan_type]);

        $stmt = $conn->prepare("INSERT INTO subscriptions (user_id, service_name, cost, category, subscription_date, renewal_date, plan_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdssss", $user_id, $_POST['service_name'], $_POST['cost'], $_POST['category'], $_POST['subscription_date'], $renewal_date->format('Y-m-d'), $plan_type);
        set_message($stmt->execute() ? 'success' : 'error', $stmt->execute() ? 'Subscription added!' : 'Error adding subscription.');
    }
    // Delete a subscription (securely via POST)
    elseif (isset($_POST['delete_id'])) {
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $_POST['delete_id'], $user_id);
        set_message($stmt->execute() ? 'success' : 'error', $stmt->execute() ? 'Subscription deleted!' : 'Error deleting subscription.');
    }
    // Delete the entire account
    elseif (isset($_POST['delete_account'])) {
        $conn->query("DELETE FROM subscriptions WHERE user_id = $user_id");
        $conn->query("DELETE FROM users WHERE id = $user_id");
        session_unset();
        session_destroy();
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Your account was successfully deleted.']; // Manual set before redirect
        redirect('index.php');
    }
    redirect('user_dashboard.php');
}

// --- FETCH DATA FOR DISPLAY ---
$stmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY renewal_date ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- CALCULATE STATISTICS ---
$total_monthly_expense = 0;
foreach ($subscriptions as $sub) {
    $divisor = ['Annually' => 12, 'Semiannually' => 6, 'Quarterly' => 3, 'Monthly' => 1];
    $total_monthly_expense += $sub['cost'] / ($divisor[$sub['plan_type']] ?? 1);
}
$conn->close();

$page_title = 'My Dashboard';
include 'templates/header.php';
?>

<div class="page-header">
    <h1>My Subscriptions</h1>
    <div class="header-actions">
        <a href="logout.php">Logout</a>
        <form action="user_dashboard.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete your account and all data?');">
            <input type="hidden" name="delete_account" value="1">
            <button type="submit" class="btn btn-danger">Delete Account</button>
        </form>
    </div>
</div>

<?php display_message(); ?>

<div class="stats-container">
    <div class="stat-card">
        <span class="stat-title">Total Subscriptions</span>
        <span class="stat-value"><?php echo count($subscriptions); ?></span>
    </div>
    <div class="stat-card">
        <span class="stat-title">Total Monthly Expense</span>
        <span class="stat-value">$<?php echo number_format($total_monthly_expense, 2); ?></span>
    </div>
</div>

<form class="subscription-form" action="user_dashboard.php" method="post">
    <h3>Add New Subscription</h3>
    <div class="form-grid">
        <input type="text" name="service_name" placeholder="Service Name" required>
        <input type="text" name="category" placeholder="Category" required>
        <input type="number" step="0.01" name="cost" placeholder="Cost ($)" required>
        <select name="plan_type" required>
            <option value="" disabled selected>Select Plan</option>
            <option>Monthly</option><option>Quarterly</option><option>Semiannually</option><option>Annually</option>
        </select>
        <div>
            <label for="subscription_date">Subscription Date</label>
            <input type="date" id="subscription_date" name="subscription_date" required>
        </div>
    </div>
    <button type="submit" name="add_subscription" class="btn btn-primary">Add Subscription</button>
</form>

<table>
    <thead>
        <tr><th>Service</th><th>Category</th><th>Plan</th><th>Cost</th><th>Subscribed On</th><th>Next Renewal</th><th>Action</th></tr>
    </thead>
    <tbody>
        <?php if (empty($subscriptions)): ?>
            <tr><td colspan="7" class="text-center">No subscriptions found.</td></tr>
        <?php else: foreach ($subscriptions as $sub): ?>
            <tr>
                <td><?php echo htmlspecialchars($sub['service_name']); ?></td>
                <td><?php echo htmlspecialchars($sub['category']); ?></td>
                <td><?php echo htmlspecialchars($sub['plan_type']); ?></td>
                <td>$<?php echo htmlspecialchars($sub['cost']); ?></td>
                <td><?php echo htmlspecialchars($sub['subscription_date']); ?></td>
                <td><?php echo htmlspecialchars($sub['renewal_date']); ?></td>
                <td>
                    <form action="user_dashboard.php" method="POST">
                        <input type="hidden" name="delete_id" value="<?php echo $sub['id']; ?>">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
    </tbody>
</table>

<?php include 'templates/footer.php'; ?>