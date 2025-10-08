<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
  exit();
}

$avatar = isset($_SESSION['avatar']) ? $_SESSION['avatar'] : 'assets/default_avatar.jpg';
$name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product Page</title>
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { margin:0; font-family:'Poppins',sans-serif; background:#f1f1f1; color:#333; line-height:1.5; }
    a { text-decoration:none; color:black; }

    header {
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:10px 15px;
      background:#001F3F;
      color:#fff;
      box-shadow:0 2px 5px rgba(0,0,0,0.1);
      position:sticky;
      top:0;
      z-index:1000;
    }
    header img.logo { width:40px; height:40px; }

    .header-right {
      display:flex;
      align-items:center;
      gap:15px;
      position:relative;
    }

    /* Avatar */
    .avatar {
      width:45px;
      height:45px;
      border-radius:50%;
      border:2px solid #fff;
      object-fit:cover;
      cursor:pointer;
      box-shadow:0 0 5px rgba(255,255,255,0.3);
      transition:transform 0.2s, box-shadow 0.3s;
    }
    .avatar:hover {
      transform:scale(1.1);
      box-shadow:0 0 8px rgba(255,127,80,0.7);
    }

    /* Cart Icon */
    .cart-icon {
      color:#fff;
      font-size:24px;
      transition:transform 0.2s, color 0.2s;
    }
    .cart-icon:hover {
      transform:scale(1.2);
      color:#FF7F50;
    }

    /* Dropdown Menu */
    .dropdown {
      position:absolute;
      top:60px;
      right:0;
      background:#fff;
      border-radius:10px;
      box-shadow:0 4px 10px rgba(0,0,0,0.15);
      width:180px;
      display:none;
      flex-direction:column;
      overflow:hidden;
      animation:fadeIn 0.25s ease;
      z-index:9999;
    }
    @keyframes fadeIn {
      from {opacity:0; transform:translateY(-5px);}
      to {opacity:1; transform:translateY(0);}
    }
    .dropdown a {
      color:#001F3F;
      padding:10px 15px;
      display:flex;
      align-items:center;
      gap:10px;
      transition:background 0.3s;
      font-size:14px;
    }
    .dropdown a:hover {
      background:#f2f2f2;
    }
    .dropdown i {
      color:#FF7F50;
      width:18px;
    }

    .search-bar {
      flex:1;
      margin:0 10px;
      display:flex;
      align-items:center;
      background:#fff;
      border-radius:20px;
      padding:5px 10px;
      border:1px solid #ccc;
    }
    .search-bar input { flex:1; padding:8px 10px; border:none; border-radius:20px; outline:none; }
    .search-bar i { color:#666; font-size:16px; margin-left:8px; cursor:pointer; }

    .tags { display:flex; gap:10px; padding:10px; flex-wrap:wrap; background:#fff; justify-content:center; border-bottom:2px solid #ddd; }
    .tag-button { background:#001F3F; color:#fff; padding:6px 14px; border-radius:20px; font-size:14px; border:none; cursor:pointer; transition:background 0.3s, transform 0.2s; display:flex; align-items:center; gap:6px; }
    .tag-button:hover { background:#FF7F50; transform:scale(1.05); }

    .video-section { width:100%; padding:15px; background:#fff; border-bottom:2px solid #ddd; }
    .video-section video { width:100%; border-radius:10px; display:block; }

    section { padding:0 15px 80px; }
    h2 { font-size:18px; margin:20px 0 10px; color:#001F3F; font-weight:600; letter-spacing:0.5px; }
    .product-list { display:flex; gap:12px; overflow-x:auto; scroll-behavior:smooth; padding-bottom:10px; }
    .product { min-width:140px; background:#fff; border-radius:10px; padding:8px; box-shadow:0 2px 5px rgba(0,0,0,0.08); transition:transform 0.2s; text-align:center; cursor:pointer; }
    .product img { width:100%; border-radius:8px; }
    .product:hover { transform:translateY(-3px); }
    .price { color:#555; font-weight:600; }

    footer { position:fixed; bottom:0; width:100%; background:#001F3F; display:flex; justify-content:space-around; padding:8px 0; box-shadow:0 -2px 5px rgba(0,0,0,0.1); }
    footer a { color:#fff; text-align:center; font-size:12px; display:flex; flex-direction:column; align-items:center; gap:3px; }
    footer i { font-size:22px; transition:transform 0.2s, color 0.2s; }
    footer a:hover i { transform:scale(1.2); color:#FF7F50; }
  </style>
</head>
<body>
  <!-- Top Navigation -->
  <header>
    <img src="logo.png" alt="Logo" class="logo">

    <div class="search-bar">
      <input type="text" id="searchInput" placeholder="Search...">
      <i class="fas fa-search" id="searchBtn"></i>
      <i class="fas fa-microphone" id="voiceBtn"></i>
    </div>

    <div class="header-right">
      <a href="cart.html"><i class="fas fa-shopping-cart cart-icon"></i></a>
      <img src="<?php echo htmlspecialchars($avatar); ?>" alt="User Avatar" class="avatar" id="avatarBtn" title="<?php echo htmlspecialchars($name); ?>">

      <!-- Dropdown -->
      <div class="dropdown" id="dropdownMenu">
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
        <a href="orderhistory.php"><i class="fas fa-history"></i> Order History</a>
        <a href="login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
  </header>

  <!-- Tags -->
  <div class="tags">
    <a href="lanyard.html" class="tag-button"><i class="fas fa-id-badge"></i> LANYARDS</a>
    <a href="uniform.html" class="tag-button"><i class="fas fa-tshirt"></i> UNIFORM</a>
    <a href="plates.html" class="tag-button"><i class="fas fa-certificate"></i> PLATES & SEALS</a>
    <a href="accessories.html" class="tag-button"><i class="fas fa-gem"></i> ACCESSORIES & MERCHANDISE</a>
  </div>

  <!-- Video Section -->
  <div class="video-section">
    <video autoplay muted loop playsinline>
      <source src="banner.mp4" type="video/mp4">
      Your browser does not support the video tag.
    </video>
  </div>

  <!-- Products -->
  <section>
    <h2>BEST SELLING PRODUCTS</h2>
    <div class="product-list" id="bestSellingProducts"></div>

    <h2>FEATURED PRODUCTS</h2>
    <div class="product-list" id="featuredProducts"></div>
  </section>

  <!-- Footer -->
  <footer>
    <a href="scan.html"><i class="fas fa-qrcode"></i><span>Scan</span></a>
    <a href="chat.html"><i class="fas fa-comment-dots"></i><span>Chat</span></a>
    <a href="homepage.php"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="alerts.html"><i class="fas fa-bell"></i><span>Alerts</span></a>
    <a href="bag.html"><i class="fas fa-bag-shopping"></i><span>Bag</span></a>
  </footer>

  <script>
    // Avatar dropdown
    const avatarBtn = document.getElementById('avatarBtn');
    const dropdown = document.getElementById('dropdownMenu');

    avatarBtn.addEventListener('click', () => {
      dropdown.style.display = dropdown.style.display === 'flex' ? 'none' : 'flex';
    });

    window.addEventListener('click', (e) => {
      if (!avatarBtn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });

    // Product rendering logic stays same
    const bestSelling = [
      { name: "Seals & Plates", price: 100, img: "seals1.jpg", detailPage: "product-details-plates.html" },
      { name: "Seals", price: 60, img: "seals2.jpg", detailPage: "product-details-plates.html" }
    ];
    const featured = [
      { name: "Uniform", price: 950, img: "uniform1.jpg", detailPage: "product-details-uniform.html" },
      { name: "Lanyard", price: 120, img: "lanyard1.jpg", detailPage: "product-details-lanyard.html" }
    ];

    function renderProducts(products, containerId) {
      const container = document.getElementById(containerId);
      container.innerHTML = "";
      if(products.length === 0){
        container.innerHTML = `<p style="color:#777; text-align:center; width:100%;">No results found</p>`;
        return;
      }
      products.forEach(product => {
        const div = document.createElement("div");
        div.className = "product";
        div.innerHTML = `
          <img src="${product.img}" alt="${product.name}">
          <p><strong>${product.name}</strong></p>
          <span class="price">₱${product.price.toFixed(2)}</span>
        `;
        div.addEventListener("click", () => {
          window.location.href = `${product.detailPage}?name=${encodeURIComponent(product.name)}&price=${product.price}&img=${encodeURIComponent(product.img)}`;
        });
        container.appendChild(div);
      });
    }

    renderProducts(bestSelling, "bestSellingProducts");
    renderProducts(featured, "featuredProducts");
  </script>
</body>
</html>
