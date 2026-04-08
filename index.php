<?php
include 'functions.php'; // For set_message and redirect

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db_connect.php';
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];
            redirect($row['role'] === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php');
        }
    }
    
    set_message('error', 'Invalid username or password.');
    redirect('index.php');
}

$page_title = 'Login';
include 'templates/header.php';
?>

<h2>Login</h2>
<?php display_message(); ?>

<form action="index.php" method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" class="btn btn-primary">Login</button>
</form>
<p class="text-center" style="margin-top: 1.5rem; display: flex; justify-content: space-between;">
    <a href="register.php">Don't have an account?</a>
    <a href="forgot_password.php">Forgot Password?</a>
</p>

<?php include 'templates/footer.php'; ?>