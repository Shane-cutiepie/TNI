<?php
session_start();

// --- BACKEND: Handles order saving to database ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  header("Content-Type: application/json");
  $data = json_decode(file_get_contents("php://input"), true);

  if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid data."]);
    exit;
  }

  // ✅ Database connection
  $servername = "127.0.0.1";
  $username   = "tniuser";
  $password   = "mypassword";
  $dbname     = "tni";
  $port       = 3308;

  $conn = new mysqli($servername, $username, $password, $dbname, $port);
  if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]);
    exit;
  }

  // ✅ Auto-get from session
  $name = $_SESSION["name"] ?? $data["name"] ?? "Unknown";
  $campus = $_SESSION["campus"] ?? $data["campus"] ?? "Unknown";

  $orders = $conn->real_escape_string($data["orders"]);
  $quantity = intval($data["quantity"]);
  $amount = floatval($data["amount"]);
  $orderDate = date("Y-m-d H:i:s");

  // ✅ Voucher system check
  $countQuery = "SELECT COUNT(*) AS total_orders FROM orders WHERE Name = ?";
  $stmt = $conn->prepare($countQuery);
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $orderCount = $row["total_orders"] + 1;

  $voucherOrders = [1, 15, 30];
  $discountRate = 0;
  if (in_array($orderCount, $voucherOrders)) {
    $discountRate = 0.15; // 15% discount
  }

  $discountAmount = $amount * $discountRate;
  $finalAmount = $amount - $discountAmount;

  // ✅ Save to DB (optional: you can add a Discount column if desired)
  $sql = "INSERT INTO orders (Name, Campus, Orders, Quantity, Amount, OrderDate)
          VALUES ('$name', '$campus', '$orders', '$quantity', '$finalAmount', '$orderDate')";

  if ($conn->query($sql) === TRUE) {
    echo json_encode([
      "success" => true,
      "discountApplied" => $discountRate > 0,
      "discountAmount" => $discountAmount,
      "finalAmount" => $finalAmount,
      "message" => $discountRate > 0
        ? "🎉 15% discount applied on your $orderCount" . "th purchase!"
        : "Order placed successfully!"
    ]);
  } else {
    echo json_encode(["success" => false, "message" => $conn->error]);
  }

  $conn->close();
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Checkout</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f8f8f8;
      margin: 0;
      padding: 0;
    }
    .checkout-container {
      width: 90%;
      max-width: 700px;
      margin: 40px auto;
      background: #fff;
      padding: 25px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    h2 {
      text-align: center;
      margin-bottom: 20px;
      color: #ff6f00;
    }
    .checkout-items {
      border-top: 1px solid #ddd;
      padding-top: 10px;
    }
    .checkout-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px solid #eee;
      padding: 10px 0;
    }
    .checkout-item img {
      width: 60px;
      height: 60px;
      object-fit: cover;
      border-radius: 10px;
      margin-right: 15px;
    }
    .item-details {
      display: flex;
      align-items: center;
    }
    .total {
      text-align: right;
      font-size: 1.2em;
      margin-top: 10px;
      font-weight: 600;
      color: #007bff;
    }
    .voucher {
      background-color: #d9fdd3;
      color: #146c2e;
      padding: 10px;
      border-radius: 8px;
      margin-top: 10px;
      text-align: center;
      font-weight: 500;
    }
    .buyer-info {
      margin-top: 20px;
    }
    .buyer-info p {
      margin: 8px 0;
    }
    .buyer-info span {
      font-weight: 500;
      color: #333;
    }
    .payment-options {
      margin-top: 20px;
    }
    .payment-options label {
      display: block;
      margin-bottom: 10px;
    }
    #ewallet-suboptions {
      display: none;
      margin-left: 20px;
    }
    .place-order-btn {
      width: 100%;
      background-color: #ff6f00;
      color: white;
      padding: 12px;
      border: none;
      font-size: 16px;
      font-weight: 600;
      border-radius: 8px;
      margin-top: 25px;
      cursor: pointer;
      transition: 0.3s;
    }
    .place-order-btn:hover {
      background-color: #e65c00;
    }
  </style>
</head>
<body>
  <div class="checkout-container">
    <h2>Checkout</h2>

    <div class="buyer-info">
      <p><strong>Name:</strong> <span id="display-name"></span></p>
      <p><strong>Campus:</strong> <span id="display-campus"></span></p>
    </div>

    <div class="checkout-items" id="checkout-items"></div>
    <p class="total">Total: ₱<span id="checkout-total">0.00</span></p>
    <div id="voucher-banner"></div>

    <div class="payment-options">
      <h3>Payment Method</h3>
      <label><input type="radio" name="payment" value="Cash on Delivery"> Cash on Delivery</label>
      <label><input type="radio" name="payment" value="GCash"> GCash</label>
      <label><input type="radio" name="payment" value="PayMaya"> PayMaya</label>
      <label><input type="radio" id="ewallet-option" name="payment" value="E-Wallet"> E-Wallet</label>
      <div id="ewallet-suboptions">
        <label><input type="radio" name="ewallet" value="GCash"> GCash</label>
        <label><input type="radio" name="ewallet" value="PayMaya"> PayMaya</label>
      </div>
    </div>

    <button class="place-order-btn" onclick="placeOrder()">Place Order</button>
  </div>

  <script>
    const userName = "<?php echo $_SESSION['name'] ?? ''; ?>";
    const userCampus = "<?php echo $_SESSION['campus'] ?? ''; ?>";

    function loadBuyerInfo() {
      document.getElementById("display-name").innerText = userName || "Not logged in";
      document.getElementById("display-campus").innerText = userCampus || "Unknown Campus";
    }

    function renderCheckout() {
      const cart = JSON.parse(localStorage.getItem("cart")) || [];
      const container = document.getElementById("checkout-items");
      container.innerHTML = "";
      let total = 0;
      cart.forEach(item => {
        const price = parseFloat(item.price.toString().replace(/[^0-9.-]+/g, "")) || 0;
        total += price * item.qty;
        container.innerHTML += `
          <div class="checkout-item">
            <div class="item-details">
              <img src="${item.img}" alt="${item.name}">
              <div class="item-info">
                <h4>${item.name}</h4>
                <p>₱${price.toFixed(2)} x ${item.qty}</p>
              </div>
            </div>
            <p><b>₱${(price * item.qty).toFixed(2)}</b></p>
          </div>`;
      });
      document.getElementById("checkout-total").innerText = total.toFixed(2);
    }

    async function placeOrder() {
      const cart = JSON.parse(localStorage.getItem("cart")) || [];
      if (!userName || !userCampus) {
        alert("User info missing. Please log in first.");
        return;
      }
      if (cart.length === 0) return alert("Your cart is empty!");

      const payment = document.querySelector('input[name="payment"]:checked');
      if (!payment) return alert("Please select a payment method!");
      const selectedPayment = payment.value;

      const totalAmount = parseFloat(document.getElementById("checkout-total").innerText);
      const totalQuantity = cart.reduce((sum, i) => sum + i.qty, 0);
      const orderNames = cart.map(i => i.name + " (x" + i.qty + ")").join(", ");

      const res = await fetch("checkout.php", {
        method: "POST",
        headers: {"Content-Type": "application/json"},
        body: JSON.stringify({
          name: userName,
          campus: userCampus,
          orders: orderNames,
          quantity: totalQuantity,
          amount: totalAmount
        })
      });

      const data = await res.json();
      if (data.success) {
        if (data.discountApplied) {
          document.getElementById("voucher-banner").innerHTML =
            `<div class='voucher'>${data.message}<br>Discount: ₱${data.discountAmount.toFixed(2)}<br>New Total: ₱${data.finalAmount.toFixed(2)}</div>`;
        } else {
          document.getElementById("voucher-banner").innerHTML = "";
        }
        setTimeout(() => {
          localStorage.removeItem("cart");
          window.location.href = "thankyoupage.html";
        }, 2000);
      } else {
        alert("Error saving order: " + data.message);
      }
    }

    document.querySelectorAll('input[name="payment"]').forEach(radio => {
      radio.addEventListener('change', () => {
        document.getElementById("ewallet-suboptions").style.display =
          document.getElementById("ewallet-option").checked ? "block" : "none";
      });
    });

    loadBuyerInfo();
    renderCheckout();
  </script>
</body>
</html>
