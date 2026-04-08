<?php
include 'functions.php';
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db_connect.php';
    $email = $_POST['email'];

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        $conn->query("DELETE FROM password_resets WHERE email = '$email'");
        
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $reset_link = "{$protocol}://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token={$token}";
        set_message('success', 'A password reset link has been generated.');
    } else {
        set_message('success', 'If an account with that email exists, a reset link will be generated.');
    }
    $conn->close();
}

$page_title = 'Forgot Password';
include 'templates/header.php';
?>

<h2>Forgot Password</h2>
<p style="text-align: center; color: var(--text-secondary); margin-top: -1.5rem; margin-bottom: 2rem;">
    Enter your email to receive a password reset link.
</p>

<?php display_message(); ?>

<?php if ($reset_link): ?>
    <div class="message success">
        <p><strong>Copy this link to reset your password:</strong></p>
        <p style="word-wrap: break-word;"><?php echo htmlspecialchars($reset_link); ?></p>
    </div>
<?php endif; ?>

<form action="forgot_password.php" method="post">
    <input type="email" name="email" placeholder="Your Email Address" required>
    <button type="submit" class="btn btn-primary">Get Reset Link</button>
</form>
<p class="text-center" style="margin-top: 1rem;">
    <a href="index.php">Back to Login</a>
</p>
<?php include 'templates/footer.php'; ?>