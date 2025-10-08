<?php
session_start();
include("db.php");

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit();
}

$name = $_SESSION['name']; // from login.php session

// ✅ Fetch only valid orders (exclude null, empty, or undefined entries)
$stmt = $conn->prepare("
  SELECT Orders, Quantity, Amount, Payment, OrderDate, Campus
  FROM orders
  WHERE Name = ?
    AND Orders IS NOT NULL
    AND Orders <> ''
    AND Orders <> 'undefined'
  ORDER BY OrderDate DESC
");
$stmt->bind_param("s", $name);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order History</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f1f1f1;
      margin: 0;
      padding: 0;
    }
    .header {
      background-color: #001F3F;
      color: white;
      padding: 15px;
      text-align: center;
      font-size: 20px;
      font-weight: 600;
      position: relative;
    }
    .header i {
      position: absolute;
      left: 15px;
      top: 15px;
      cursor: pointer;
      color: white;
    }
    .container {
      width: 90%;
      max-width: 700px;
      margin: 40px auto;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      padding: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      text-align: left;
      padding: 12px;
      border-bottom: 1px solid #ddd;
    }
    th {
      background-color: #001F3F;
      color: white;
    }
    tr:hover {
      background-color: #f9f9f9;
    }
    .no-orders {
      text-align: center;
      color: #555;
      font-size: 16px;
      margin: 20px 0;
    }
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      padding: 10px 20px;
      background-color: #001F3F;
      color: white;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 500;
      transition: background 0.3s;
    }
    .back-btn:hover {
      background-color: #FF7F50;
    }
    @media (max-width: 600px) {
      .container {
        width: 95%;
        padding: 15px;
      }
      table, th, td {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>
  <div class="header">
    <i class="fas fa-arrow-left" onclick="window.location.href='profile.php'"></i>
    Order History
  </div>

  <div class="container">
    <?php if ($result->num_rows > 0): ?>
      <table>
        <thead>
          <tr>
            <th>Product Ordered</th>
            <th>Quantity</th>
            <th>Amount (₱)</th>
            <th>Payment</th>
            <th>Campus</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['Orders']); ?></td>
              <td><?php echo htmlspecialchars($row['Quantity']); ?></td>
              <td><?php echo number_format($row['Amount'], 2); ?></td>
              <td><?php echo htmlspecialchars($row['Payment']); ?></td>
              <td><?php echo htmlspecialchars($row['Campus']); ?></td>
              <td><?php echo date("M d, Y", strtotime($row['OrderDate'])); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    <?php else: ?>
      <p class="no-orders">You have no valid orders yet.</p>
    <?php endif; ?>

    <a href="homepage.php" class="back-btn"><i class="fas fa-home"></i> Back to Home</a>
  </div>
</body>
</html>
