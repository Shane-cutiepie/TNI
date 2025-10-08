<?php
session_start();
include("db.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill all fields.";
    } elseif (!preg_match('/@bicol-u\.edu\.ph$/', $email)) {
        // ✅ School email validation
        $error = "Please use your school email (example: student@bicol-u.edu.ph).";
    } else {
        $sql = "SELECT * FROM signed_users WHERE school_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // ✅ Store values in session using SAME keys as profile.php
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['fullname'];          // ✅ renamed for profile.php
                $_SESSION['email'] = $user['school_email'];
                $_SESSION['campus'] = $user['campuses'];        // ✅ renamed for profile.php
                $_SESSION['member_since'] = $user['member_since'];
                $_SESSION['avatar'] = $user['avatar'] ?? 'avatar.png'; // optional default

                // ✅ Redirect to homepage
                header("Location: homepage.php");
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:#f1f1f1; display:flex; justify-content:center; align-items:center; height:100vh; }
.login-container { background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:350px; text-align:center; }
.login-container h2 { margin-bottom:20px; color:#001F3F; }
.form-group { margin-bottom:15px; text-align:left; }
label { font-weight:500; font-size:14px; display:block; margin-bottom:5px; }
input { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-family:'Poppins',sans-serif; }
button { width:100%; padding:12px; background:#001F3F; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer; transition: background 0.3s; }
button:hover { background:#FF7F50; }
.signup-link { margin-top:15px; font-size:14px; }
.signup-link a { color:#FF7F50; text-decoration:none; font-weight:500; }
.error { color:red; font-size:14px; margin-bottom:10px; }
</style>
</head>
<body>
<div class="login-container">
  <h2><i class="fas fa-lock"></i> Login</h2>
  <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
  <form method="POST" action="login.php">
    <div class="form-group">
      <label for="email">School Email</label>
      <input type="email" id="email" name="email" placeholder="e.g. student@bicol-u.edu.ph" required>
    </div>
    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit">Login</button>
  </form>
  <div class="signup-link">
    Don’t have an account? <a href="signup.php">Sign Up</a>
  </div>
</div>
</body>
</html>
