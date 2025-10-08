<?php
session_start();
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit();
}

$name = $_SESSION['name'] ?? 'Unknown User';
$email = $_SESSION['email'] ?? 'No Email';
$campus = $_SESSION['campus'] ?? 'Not specified';
$member_since = $_SESSION['member_since'] ?? 'N/A';
$avatar = $_SESSION['avatar'] ?? 'avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: #f4f6fb;
  color: #333;
}

/* HEADER */
header {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  background: #001F3F;
  color: #fff;
  padding: 12px 16px;
  border-bottom: 4px solid transparent;
  position: sticky;
  top: 0;
}
header::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: -4px;
  height: 4px;
  background: linear-gradient(to bottom, white 2px, transparent 2px, transparent 4px, white 4px);
}
.header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.header-left a {
  background: white;
  border-radius: 50%;
  width: 38px;
  height: 38px;
  display: flex;
  justify-content: center;
  align-items: center;
  text-decoration: none;
  box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}
.header-left i {
  color: #F4A300;
  font-size: 18px;
}

/* 🟦 Added “PROFILE” text beside back button */
.header-left span {
  font-size: 18px;
  font-weight: 600;
  color: #fff;
  margin-left: 5px;
  letter-spacing: 0.5px;
}

/* PROFILE SECTION */
section {
  margin: 40px auto;
  width: 90%;
  max-width: 420px;
  background: #fff;
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  text-align: center;
}
.avatar {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 3px solid #001F3F;
  margin-bottom: 12px;
  object-fit: cover;
}
h2 {
  color: #001F3F;
  margin: 10px 0;
  font-size: 20px;
}
p {
  margin: 5px 0;
  color: #555;
  font-size: 15px;
}
.info-label {
  font-weight: 600;
  color: #001F3F;
}
.btn {
  display: block;
  width: 80%;
  margin: 12px auto;
  padding: 10px;
  background: #001F3F;
  color: #fff;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  transition: 0.3s;
}
.btn i {
  margin-right: 6px;
}
.btn:hover {
  background: #F4A300;
  transform: scale(1.02);
}
</style>
</head>
<body>
  <header>
    <div class="header-left">
      <a href="homepage.php"><i class="fas fa-chevron-left"></i></a>
      <span>PROFILE</span>
    </div>
  </header>

  <section>
    <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="avatar">
    <h2><?php echo htmlspecialchars($name); ?></h2>
    <p><span class="info-label">Email:</span> <?php echo htmlspecialchars($email); ?></p>
    <p><span class="info-label">Campus:</span> <?php echo htmlspecialchars($campus); ?></p>
    <p><span class="info-label">Member since:</span> <?php echo htmlspecialchars($member_since); ?></p>

    <a href="edit_profile.php" class="btn"><i class="fas fa-user-edit"></i>Edit Profile</a>
    <a href="orderhistory.php" class="btn"><i class="fas fa-history"></i>Order History</a>
    <a href="login.php" class="btn"><i class="fas fa-sign-out-alt"></i>Logout</a>
  </section>
</body>
</html>
