<?php
include 'functions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db_connect.php';
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        set_message('error', 'Username or email already taken.');
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $email);
        if ($stmt->execute()) {
            set_message('success', 'Registration successful! Please log in.');
            redirect('index.php');
        } else {
            set_message('error', 'An error occurred. Please try again.');
        }
    }
    $conn->close();
    redirect('register.php');
}

$page_title = 'Register';
include 'templates/header.php';
?>
<h2>Register</h2>

<?php display_message(); ?>

<form action="register.php" method="post">
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email Address" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" class="btn btn-primary">Register</button>
</form>
<p class="text-center" style="margin-top: 1rem;">
    <a href="index.php">Already have an account? Login</a>
</p>
<?php include 'templates/footer.php'; ?>