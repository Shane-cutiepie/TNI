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

    // Database connection
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

    $name = $_SESSION["name"] ?? $data["name"] ?? "Unknown";
    $campus = $_SESSION["campus"] ?? $data["campus"] ?? "Unknown";

    $orders = $data["orders"];
    $quantity = intval($data["quantity"]);
    $amount = floatval($data["amount"]);
    $payment = $data["payment"] ?? "";
    $orderDate = date("Y-m-d H:i:s");

    // 🔹 STEP 1: Count user's existing orders
    $countQuery = $conn->prepare("SELECT COUNT(*) as total FROM orders WHERE Name = ?");
    $countQuery->bind_param("s", $name);
    $countQuery->execute();
    $result = $countQuery->get_result()->fetch_assoc();
    $orderCount = intval($result["total"]) + 1;
    $countQuery->close();

    // 🔹 STEP 2: Check if voucher applies
    $discountApplied = false;
    if (in_array($orderCount, [1, 15, 30])) {
        $amount = $amount * 0.85; // 15% off
        $discountApplied = true;
    }

    // 🔹 STEP 3: Insert order (fix: use prepared statement to ensure payment saves)
    $insert = $conn->prepare("INSERT INTO orders (Name, Campus, Orders, Quantity, Amount, Payment, OrderDate)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("sssisss", $name, $campus, $orders, $quantity, $amount, $payment, $orderDate);

    if ($insert->execute()) {
        echo json_encode([
            "success" => true,
            "discountApplied" => $discountApplied,
            "finalAmount" => number_format($amount, 2),
            "orderCount" => $orderCount
        ]);
    } else {
        echo json_encode(["success" => false, "message" => $conn->error]);
    }

    $insert->close();
    $conn->close();
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Order Information</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f8f8f8; margin: 0; padding: 0; }
.checkout-container { width: 90%; max-width: 700px; margin: 40px auto; background: #fff; padding: 25px 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
h2 { text-align: center; margin-bottom: 20px; color: #ff6f00; }
.checkout-items { border-top: 1px solid #ddd; padding-top: 10px; }
.checkout-item { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding: 10px 0; }
.checkout-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 10px; margin-right: 15px; }
.item-details { display: flex; align-items: center; }
.item-info h4 { margin:0; font-size:16px; }
.qty-controls { margin-top: 5px; display:flex; align-items:center; gap:5px; }
.qty-controls button { width:28px; height:28px; border:none; background:#f1f1f1; cursor:pointer; border-radius:4px; font-size:16px; }
.total { text-align: right; font-size: 1.2em; margin-top: 10px; font-weight: 600; color: #007bff; }
.buyer-info { margin-top: 20px; }
.buyer-info p { margin: 8px 0; }
.buyer-info span { font-weight: 500; color: #333; }
.payment-options { margin-top: 20px; }
.payment-options label { display: block; margin-bottom: 10px; }
#ewallet-suboptions { display: none; margin-left: 20px; }
.place-order-btn { width: 100%; background-color: #ff6f00; color: white; padding: 12px; border: none; font-size: 16px; font-weight: 600; border-radius: 8px; margin-top: 25px; cursor: pointer; transition: 0.3s; }
.place-order-btn:hover { background-color: #e65c00; }
</style>
</head>
<body>
<div class="checkout-container">
  <h2>Order Information</h2>

  <div class="buyer-info">
    <p><strong>Name:</strong> <span id="display-name"></span></p>
    <p><strong>Campus:</strong> <span id="display-campus"></span></p>
  </div>

  <div class="checkout-items" id="checkout-items"></div>
  <p class="total">Total: ₱<span id="checkout-total">0.00</span></p>

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

let buyNowItem = JSON.parse(localStorage.getItem("buyNowItem"));

function loadBuyerInfo() {
  document.getElementById("display-name").innerText = userName || "Not logged in";
  document.getElementById("display-campus").innerText = userCampus || "Unknown Campus";
}

function renderCheckout() {
  const container = document.getElementById("checkout-items");
  container.innerHTML = "";
  if(!buyNowItem) {
    container.innerHTML = "<p style='text-align:center;'>No item selected for Buy Now.</p>";
    document.getElementById("checkout-total").innerText = "0.00";
    return;
  }

  const price = parseFloat(buyNowItem.price) || 0;
  const qty = buyNowItem.qty || 1;
  const total = price * qty;

  container.innerHTML = `
    <div class="checkout-item">
      <div class="item-details">
        <img src="${buyNowItem.img}" alt="${buyNowItem.name}">
        <div class="item-info">
          <h4>${buyNowItem.name}</h4>
          <div class="qty-controls">
            <button onclick="changeQty(-1)">-</button>
            <span id="qty-display">${qty}</span>
            <button onclick="changeQty(1)">+</button>
          </div>
          <p>₱${price.toFixed(2)} each</p>
        </div>
      </div>
      <p><b>₱<span id="total-display">${total.toFixed(2)}</span></b></p>
    </div>
  `;
  document.getElementById("checkout-total").innerText = total.toFixed(2);
}

function changeQty(delta) {
  buyNowItem.qty = Math.max(1, (buyNowItem.qty || 1) + delta);
  const total = parseFloat(buyNowItem.price) * buyNowItem.qty;
  document.getElementById("qty-display").innerText = buyNowItem.qty;
  document.getElementById("total-display").innerText = total.toFixed(2);
  document.getElementById("checkout-total").innerText = total.toFixed(2);
}

async function placeOrder() {
  if (!userName || !userCampus) {
    alert("User info missing. Please log in first.");
    return;
  }
  if (!buyNowItem) return alert("No item selected for Buy Now!");

  let payment = document.querySelector('input[name="payment"]:checked');
  if (!payment) return alert("Please select a payment method!");
  let selectedPayment = payment.value;

  if(selectedPayment === "E-Wallet") {
    const ewallet = document.querySelector('input[name="ewallet"]:checked');
    if (!ewallet) return alert("Please select an e-wallet option!");
    selectedPayment = ewallet.value;
  }

  const totalAmount = parseFloat(document.getElementById("checkout-total").innerText);
  const orderNames = buyNowItem.name + " (x" + buyNowItem.qty + ")";

  try {
    const res = await fetch("checkout.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({
        name: userName,
        campus: userCampus,
        orders: orderNames,
        quantity: buyNowItem.qty,
        amount: totalAmount,
        payment: selectedPayment
      })
    });

    const data = await res.json();
    if (data.success) {
      if (data.discountApplied) {
        alert(`🎉 Congrats! You received a 15% discount on your ${data.orderCount}th order!\nFinal Total: ₱${data.finalAmount}`);
      }
      localStorage.removeItem("buyNowItem");
      window.location.href = "thankyoupage.html";
    } else {
      alert("Error saving order: " + data.message);
    }
  } catch (err) {
    alert("An error occurred: " + err.message);
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
