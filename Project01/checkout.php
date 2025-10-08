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
  $payment = $conn->real_escape_string($data["payment"] ?? "Unspecified");
  $orderDate = date("Y-m-d H:i:s");

  // ✅ Shipping fee logic
  $shippingFees = [
    'BU Polangui'   => 60,
    'BU Guinobatan' => 30,
    'BU Tabaco'     => 40,
    'BU Gubat'      => 150,
    'East Campus'   => 15,
    'Main Campus'   => 15,
    'Daraga Campus' => 15
  ];

  $shippingFee = $shippingFees[$campus] ?? 0;
  $amountWithShipping = $amount;

  // ✅ Voucher system check
  $countQuery = "SELECT COUNT(*) AS total_orders FROM orders WHERE Name = ?";
  $stmt = $conn->prepare($countQuery);
  $stmt->bind_param("s", $name);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $orderCount = $row["total_orders"] + 1;

  $voucherOrders = [1, 15, 30];
  $discountRate = in_array($orderCount, $voucherOrders) ? 0.15 : 0;

  $discountAmount = $amountWithShipping * $discountRate;
  $finalAmount = $amountWithShipping - $discountAmount;

  // ✅ Save order
  $sql = "INSERT INTO orders (Name, Campus, Orders, Quantity, ShippingFee, Amount, Payment, OrderDate)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("sssddsss", $name, $campus, $orders, $quantity, $shippingFee, $finalAmount, $payment, $orderDate);

  if ($stmt->execute()) {
    echo json_encode([
      "success" => true,
      "discountApplied" => $discountRate > 0,
      "discountAmount" => $discountAmount,
      "finalAmount" => $finalAmount,
      "shippingFee" => $shippingFee,
      "payment" => $payment,
      "message" => $discountRate > 0
        ? "🎉 15% discount applied on your $orderCount" . "th purchase!"
        : "Order placed successfully!"
    ]);
  } else {
    echo json_encode(["success" => false, "message" => $conn->error]);
  }

  $stmt->close();
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
      background: linear-gradient(180deg, #f2f5fc 0%, #e8effa 100%);
      margin: 0;
      padding: 0;
      color: #333;
    }

    .checkout-container {
      width: 90%;
      max-width: 720px;
      margin: 50px auto;
      background: #fff;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
      transition: all 0.3s ease;
    }

    h2 {
      text-align: center;
      margin-bottom: 25px;
      font-size: 26px;
      color: #003366;
      letter-spacing: 1px;
    }

    .buyer-info {
      background: #f9fbff;
      border: 2px solid #c9daf5;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .buyer-info p {
      margin: 6px 0;
      color: #003366;
      font-weight: 500;
    }

    .checkout-items {
      border-top: 2px solid #d0d9ea;
      padding-top: 10px;
    }

    .checkout-item {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 1px dashed #ddd;
      padding: 12px 0;
    }

    .checkout-item img {
      width: 65px;
      height: 65px;
      object-fit: cover;
      border-radius: 10px;
      margin-right: 15px;
    }

    .item-details {
      display: flex;
      align-items: center;
    }

    .item-info h4 {
      margin: 0;
      font-size: 16px;
      color: #003366;
    }

    .item-info p {
      font-size: 14px;
      color: #666;
    }

    .total {
      text-align: right;
      font-size: 1.3em;
      margin-top: 15px;
      font-weight: 600;
      color: #002244;
    }

    #voucher-banner {
      margin-top: 15px;
      text-align: center;
      background: #e7f8e9;
      color: #1c7c3d;
      padding: 10px;
      border-radius: 8px;
      font-weight: 500;
      display: none;
    }

    .payment-options {
      margin-top: 30px;
      background: #f9fbff;
      border-radius: 10px;
      padding: 20px;
      border: 1px solid #c9daf5;
    }

    .payment-options h3 {
      color: #003366;
      font-size: 18px;
      margin-bottom: 10px;
    }

    .payment-options label {
      display: block;
      margin-bottom: 8px;
      color: #444;
    }

    #ewallet-suboptions {
      display: none;
      margin-left: 25px;
    }

    .place-order-btn {
      width: 100%;
      background: #003366;
      color: white;
      padding: 14px;
      border: none;
      font-size: 17px;
      font-weight: 600;
      border-radius: 10px;
      margin-top: 25px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .place-order-btn:hover {
      background: #002244;
      transform: scale(1.03);
    }

    @media (max-width: 600px) {
      .checkout-container {
        padding: 25px;
      }
      h2 {
        font-size: 22px;
      }
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

    const shippingFees = {
      "BU Polangui": 60,
      "BU Guinobatan": 30,
      "BU Tabaco": 40,
      "BU Gubat": 150,
      "East Campus": 15,
      "Main Campus": 15,
      "Daraga Campus": 15
    };

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

      const shippingFee = shippingFees[userCampus] || 0;
      const totalWithShipping = total + shippingFee;
      document.getElementById("checkout-total").innerText = totalWithShipping.toFixed(2);

      if (shippingFee > 0) {
        container.innerHTML += `<p style="text-align:right;font-weight:500;">Shipping Fee: ₱${shippingFee.toFixed(2)}</p>`;
      }
    }

    async function placeOrder() {
      const cart = JSON.parse(localStorage.getItem("cart")) || [];
      if (!userName || !userCampus) {
        alert("User info missing. Please log in first.");
        return;
      }
      if (cart.length === 0) return alert("Your cart is empty!");

      const paymentRadio = document.querySelector('input[name="payment"]:checked');
      if (!paymentRadio) return alert("Please select a payment method!");
      let selectedPayment = paymentRadio.value;

      if (selectedPayment === "E-Wallet") {
        const ewallet = document.querySelector('input[name="ewallet"]:checked');
        if (!ewallet) return alert("Please select an e-wallet option!");
        selectedPayment = ewallet.value;
      }

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
          amount: totalAmount,
          payment: selectedPayment
        })
      });

      const data = await res.json();
      if (data.success) {
        let msg = `✅ Order placed successfully! Shipping Fee: ₱${data.shippingFee.toFixed(2)}\nPayment: ${data.payment}`;
        if (data.discountApplied) {
          msg += `\n🎉 ${data.message}\nDiscount: ₱${data.discountAmount.toFixed(2)}\nFinal Total: ₱${data.finalAmount.toFixed(2)}`;
        }
        alert(msg);

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
