<?php
include 'functions.php';
include 'db_connect.php';

$token_valid = false;
$token = $_GET['token'] ?? '';
$email = '';

if (!empty($token)) {
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (strtotime($row['expires_at']) > time()) {
            $token_valid = true;
            $email = $row['email'];
        } else {
            set_message('error', 'This reset token has expired. Please request a new one.');
        }
    } else {
        set_message('error', 'Invalid or used reset token.');
    }
} else {
    set_message('error', 'No reset token provided.');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    if ($_POST['password'] !== $_POST['confirm_password']) {
        set_message('error', 'Passwords do not match.');
    } elseif ($_POST['token'] !== $token) {
        set_message('error', 'Token mismatch. Please try again.');
    } else {
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $stmt_update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt_update->bind_param("ss", $hashed_password, $email);
        $stmt_update->execute();
        
        $conn->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
        
        set_message('success', 'Password has been reset successfully! Please log in.');
        redirect('index.php');
    }
}

$page_title = 'Reset Password';
include 'templates/header.php';
?>

<h2>Reset Your Password</h2>
<?php display_message(); ?>

<?php if ($token_valid): ?>
    <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="password" name="password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
        <button type="submit" class="btn btn-primary">Reset Password</button>
    </form>
<?php else: ?>
    <p class="text-center" style="margin-top: 1rem;">
        <a href="forgot_password.php">Request a new reset link</a>
    </p>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>