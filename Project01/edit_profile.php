<?php
session_start();
include("db.php");

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit();
}

$email = $_SESSION['email'];

// Fetch current user data
$query = "SELECT * FROM signed_users WHERE school_email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $fullname = $_POST['fullname'];
  $school_email = $_POST['school_email'];
  $campuses = $_POST['campuses'];
  $password = $_POST['password'];
  $avatar = $_POST['avatarInput'];

  if (!empty($password)) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update = "UPDATE signed_users SET fullname=?, school_email=?, campuses=?, password=?, avatar=? WHERE school_email=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("ssssss", $fullname, $school_email, $campuses, $hashed_password, $avatar, $email);
  } else {
    $update = "UPDATE signed_users SET fullname=?, school_email=?, campuses=?, avatar=? WHERE school_email=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("sssss", $fullname, $school_email, $campuses, $avatar, $email);
  }

  if ($stmt->execute()) {
    $_SESSION['name'] = $fullname;
    $_SESSION['email'] = $school_email;
    $_SESSION['campus'] = $campuses;
    $_SESSION['avatar'] = $avatar;

    echo "<script>alert('Profile updated successfully!'); window.location='edit_profile.php';</script>";
  } else {
    echo "<script>alert('Error updating profile. Please try again.');</script>";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Edit Profile</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body {
  margin: 0;
  font-family: 'Poppins', sans-serif;
  background: #f9f9f9;
  color: #333;
}
header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 15px;
  background: #001F3F;
  color: #fff;
  position: sticky;
  top: 0;
}
header h2 {
  flex: 1;
  text-align: center;
  margin: 0;
  font-size: 18px;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}
.header-left button {
  background: none;
  border: none;
  color: #fff;
  font-size: 20px;
  cursor: pointer;
  transition: .2s;
}
.header-left button:hover {
  color: #FF7F50;
  transform: scale(1.2);
}

/* Form container */
form {
  max-width: 400px;
  margin: 30px auto;
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
label {
  display: block;
  text-align: left;
  margin-bottom: 5px;
  font-weight: 600;
  color: #001F3F;
}
input, select {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-family: 'Poppins', sans-serif;
}

/* Avatar gallery */
.avatar-selection {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
  margin-bottom: 15px;
}
.avatar-selection img {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  border: 2px solid transparent;
  cursor: pointer;
  object-fit: cover;
  transition: 0.3s;
}
.avatar-selection img:hover {
  transform: scale(1.1);
  border-color: #FF7F50;
}
.avatar-selection img.selected {
  border: 2px solid #001F3F;
}

/* Current avatar preview */
#currentAvatarPreview {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  border: 2px solid #001F3F;
  object-fit: cover;
  display: block;
  margin: 0 auto 10px auto;
  transition: 0.3s;
}

/* Buttons */
button {
  width: 100%;
  padding: 10px;
  background: #001F3F;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: .3s;
}
button:hover {
  background: #FF7F50;
}
</style>
</head>
<body>

<header>
  <div class="header-left">
    <button onclick="history.back();"><i class="fas fa-arrow-left"></i></button>
  </div>
  <h2>Edit Profile</h2>
  <div style="width:35px;"></div>
</header>

<form method="POST" action="">
  <label for="fullname">Full Name</label>
  <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>

  <label for="school_email">School Email</label>
  <input type="email" id="school_email" name="school_email" value="<?php echo htmlspecialchars($user['school_email']); ?>" required>

  <label for="campuses">Campus</label>
  <select id="campuses" name="campuses" required>
    <option value="">Select your campus</option>
    <?php
    $campusOptions = ['BU Polangui', 'BU Guinobatan', 'BU Tabaco', 'BU Gubat', 'East Campus', 'Main Campus', 'Daraga Campus'];
    foreach ($campusOptions as $option) {
      $selected = ($user['campuses'] == $option) ? 'selected' : '';
      echo "<option value='$option' $selected>$option</option>";
    }
    ?>
  </select>

  <label for="password">New Password (leave blank to keep current)</label>
  <input type="password" id="password" name="password" placeholder="Enter new password">

  <label>Choose Avatar</label>
  <!-- Current Avatar Preview -->
  <img id="currentAvatarPreview" src="<?php echo htmlspecialchars($user['avatar']); ?>">

  <!-- Avatar Gallery -->
  <div class="avatar-selection">
    <?php
    for ($i = 1; $i <= 8; $i++) {
      $src = "assets/avatar$i.jpg";
      $selected = ($user['avatar'] == $src) ? 'selected' : '';
      echo "<img src='$src' class='$selected' onclick=\"selectAvatar('$src', this)\">";
    }
    ?>
  </div>

  <input type="hidden" id="avatarInput" name="avatarInput" value="<?php echo htmlspecialchars($user['avatar']); ?>">

  <button type="submit">Save Changes</button>
</form>

<script>
function selectAvatar(path, element) {
  document.getElementById('avatarInput').value = path;
  document.querySelectorAll('.avatar-selection img').forEach(img => img.classList.remove('selected'));
  element.classList.add('selected');
  document.getElementById('currentAvatarPreview').src = path;
}

// Highlight the saved avatar on page load
window.addEventListener('DOMContentLoaded', () => {
  const currentAvatar = document.getElementById('avatarInput').value;
  document.querySelectorAll('.avatar-selection img').forEach(img => {
    if (img.src.includes(currentAvatar)) {
      img.classList.add('selected');
    }
  });
});
</script>

</body>
</html>
