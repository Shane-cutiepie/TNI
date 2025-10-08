<?php
session_start();

// Database connection
$servername = "127.0.0.1";
$username   = "tniuser";
$password   = "mypassword";
$dbname     = "tni";
$port       = 3308;

$conn = new mysqli($servername, $username, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $campus   = trim($_POST['campus']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // Validation
    if (!preg_match("/^[A-Za-z0-9._%+-]+@bicol-u\.edu\.ph$/", $email)) {
        $message = "Please use your school email (example: student@bicol-u.edu.ph).";
    } elseif ($password !== $confirm) {
        $message = "Passwords do not match.";
    } elseif (empty($campus)) {
        $message = "Please select your campus.";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $member_since = date("Y-m-d H:i:s");

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO signed_users (fullname, school_email, campuses, password, member_since) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $fullname, $email, $campus, $hashedPassword, $member_since);

        if ($stmt->execute()) {
            // Save details in session so profile.php can display them later
            $_SESSION['name'] = $fullname;
            $_SESSION['email'] = $email;
            $_SESSION['campus'] = $campus;
            $_SESSION['member_since'] = $member_since;

            // ✅ Keep your existing redirect
            header("Location: terms.html");
            exit;
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { margin:0; font-family:'Poppins',sans-serif; background:#f1f1f1; display:flex; justify-content:center; align-items:center; height:100vh; }
.signup-container { background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.1); width:400px; text-align:center; }
.signup-container h2 { margin-bottom:20px; color:#001F3F; }
.form-group { margin-bottom:15px; text-align:left; }
label { font-weight:500; font-size:14px; display:block; margin-bottom:5px; }
input, select { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; font-family:'Poppins',sans-serif; }
button { width:100%; padding:12px; background:#001F3F; color:#fff; border:none; border-radius:6px; font-weight:600; cursor:pointer; transition: background 0.3s; }
button:hover { background:#FF7F50; }
.login-link { margin-top:15px; font-size:14px; }
.login-link a { color:#FF7F50; text-decoration:none; font-weight:500; }
.message { color:red; margin-bottom:15px; }
</style>
</head>
<body>
<div class="signup-container">
  <h2><i class="fas fa-user-plus"></i> Sign Up</h2>
  
  <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
  
  <form method="POST" action="">
    <div class="form-group">
      <label for="fullname">Full Name</label>
      <input type="text" name="fullname" id="fullname" placeholder="Enter your full name" required>
    </div>

    <div class="form-group">
      <label for="email">School Email</label>
      <input type="email" name="email" id="email" placeholder="e.g. student@bicol-u.edu.ph" required>
    </div>

    <div class="form-group">
      <label for="campus">Select Campus</label>
      <select name="campus" id="campus" required>
        <option value="">-- Select Campus --</option>
        <option value="BU Polangui">BU Polangui</option>
        <option value="BU Guinobatan">BU Guinobatan</option>
        <option value="BU Tabaco">BU Tabaco</option>
        <option value="BU Gubat">BU Gubat</option>
        <option value="East Campus">East Campus</option>
        <option value="Main Campus">Main Campus</option>
        <option value="Daraga Campus">Daraga Campus</option>
      </select>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input type="password" name="password" id="password" placeholder="Create a password" required>
    </div>

    <div class="form-group">
      <label for="confirm">Confirm Password</label>
      <input type="password" name="confirm" id="confirm" placeholder="Confirm password" required>
    </div>

    <button type="submit">Sign Up</button>
  </form>

  <div class="login-link">
    Already have an account? <a href="login.php">Login</a>
  </div>
</div>
</body>
</html>
