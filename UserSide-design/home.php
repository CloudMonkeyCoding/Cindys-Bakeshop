<?php
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cindy’s Bakeshop Hagonoy</title>
    <style>
      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      }

      html {
        scroll-behavior: smooth;
      }

      body {
        background: linear-gradient(180deg, #fffdfa 0%, #fef3e7 60%, #fff 100%);
        color: #333;
        line-height: 1.6;
      }

      header {
        background: #fff;
        padding: 1rem 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 0;
        z-index: 10;
      }

      header .logo {
        display:flex;
        align-items:center;
        gap:.6rem;
        font-weight:600;
        color:#d2691e;
      }

      header .logo img {
        height: 48px;
      }

      header nav {
        flex: 1;
        display: flex;
        justify-content: center;
      }

      nav ul {
        list-style: none;
        display: flex;
        gap: 2rem;
        align-items: center;
      }

      nav ul li a {
        color: #333;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
      }

      nav ul li a:hover {
        color: #d2691e;
      }

      .download-btn {
        background: #d2691e;
        color: #fff !important;
        padding: 0.6rem 1.3rem;
        border-radius: 6px;
        font-weight: 600;
        transition: background 0.3s ease;
      }

      .download-btn:hover {
        background: #b85c16;
      }

      .hamburger {
        display: none;
        font-size: 1.8rem;
        cursor: pointer;
      }

      .hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        padding: 4rem 8%;
        background: #fafafa;
        gap:2rem;
      }

      .hero-text {
        max-width: 500px;
      }

      .hero-text h1 {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #222;
      }

      .hero-text p {
        font-size: 1.2rem;
        color: #666;
        margin-bottom: 2rem;
      }

      .hero-text a,
      .hero-text button {
        border: none;
        padding: 0.9rem 1.8rem;
        border-radius: 6px;
        font-size: 1rem;
        cursor: pointer;
        margin-right: 1rem;
        display: inline-block;
        text-decoration:none;
      }

      .hero-text a.primary {
        background: #d2691e;
        color: #fff;
        transition: background 0.3s ease;
      }

      .hero-text a.primary:hover {
        background: #b85c16;
      }

      .hero-text a.secondary {
        background: transparent;
        color: #d2691e;
        border:1px solid #d2691e;
      }

      .hero img {
        max-width: 420px;
        border-radius: 12px;
        box-shadow:0 10px 30px rgba(210,105,30,0.15);
      }

      .categories {
        display: flex;
        justify-content: center;
        gap: 2rem;
        padding: 3rem 8%;
        text-align: center;
        flex-wrap: wrap;
      }

      .category {
        flex: 1 1 200px;
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
        transition: transform 0.2s ease;
      }

      .category:hover {
        transform: translateY(-5px);
      }

      .category img {
        height: 60px;
        margin-bottom: 1rem;
      }

      .category h3 {
        margin-bottom: 0.5rem;
        color: #d2691e;
      }

      .menu {
        padding: 4rem 8%;
        text-align: center;
        background:#fff9f3;
      }

      .menu h2 {
        font-size: 2rem;
        margin-bottom: 2rem;
        color: #222;
      }

      .products {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 2rem;
      }

      .product-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.2s ease;
      }

      .product-card:hover {
        transform: translateY(-8px);
      }

      .product-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
      }

      .product-card h3 {
        padding: 1rem;
        font-size: 1.2rem;
        color: #d2691e;
      }

      .about,
      .visit {
        padding: 4rem 8%;
        background: #fff;
      }

      .about h2,
      .visit h2 {
        font-size: 2rem;
        color: #222;
        margin-bottom: 1rem;
      }

      .about p,
      .visit p {
        color: #555;
        max-width: 700px;
      }

      footer {
        text-align: center;
        padding: 1.5rem;
        background: #fff3e0;
        color: #333;
      }

      @media (max-width: 768px) {
        header nav {
          position: absolute;
          top: 70px;
          left: 0;
          right: 0;
          background: #fff;
          display: none;
          flex-direction: column;
          text-align: center;
          border-bottom: 1px solid #eee;
          padding: 1rem 0;
        }
        header nav.active { display:flex; }
        nav ul { flex-direction: column; gap: 1.5rem; }
        .hamburger { display:block; }
        .hero { flex-direction: column; text-align:center; }
        .hero img { width: 100%; max-width:320px; }
      }
    </style>
  </head>
  <body>
    <header>
      <div class="logo">
        <img src="../Kehnt_admin_Design/Cindys.png" alt="Cindy’s Logo" />
        Cindy’s Bakeshop
      </div>
      <div class="hamburger" onclick="toggleMenu()">☰</div>
      <nav id="navMenu">
        <ul>
          <li><a href="home.php">Home</a></li>
          <li><a href="menu.php">Menu</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#visit">Contacts</a></li>
          <li><a href="login.php">Login</a></li>
          <li><a href="signup.php" class="download-btn">Sign up</a></li>
        </ul>
      </nav>
    </header>

    <section class="hero">
      <div class="hero-text">
        <h1>Handcrafted breads baked fresh every morning.</h1>
        <p>Order your favorite breads, cakes, and pastries from Cindy’s Bakeshop and pick them up or have them delivered straight to your table.</p>
        <a href="menu.php" class="primary">Browse Menu</a>
        <a href="orders.php" class="secondary">View My Orders</a>
      </div>
      <img src="../userSide/Images/cakes/cake3.png" alt="Freshly baked cake" />
    </section>

    <section class="categories">
      <div class="category">
        <img src="../userSide/Images/bread/bread3.png" alt="Bread" />
        <h3>Fresh Bread</h3>
        <p>Soft and fluffy loaves baked daily using premium ingredients.</p>
      </div>
      <div class="category">
        <img src="../userSide/Images/cakes/cake1.png" alt="Cake" />
        <h3>Celebration Cakes</h3>
        <p>Make every occasion sweeter with our signature cakes.</p>
      </div>
      <div class="category">
        <img src="../userSide/Images/pastry/pastry6.png" alt="Pastry" />
        <h3>Pastries</h3>
        <p>Crisp, buttery pastries perfect for coffee breaks and snacks.</p>
      </div>
    </section>

    <section class="menu">
      <h2>Customer Favorites</h2>
      <div class="products">
        <div class="product-card">
          <img src="../userSide/Images/bread/bread2.png" alt="Pandesal" />
          <h3>Classic Pandesal</h3>
        </div>
        <div class="product-card">
          <img src="../userSide/Images/cakes/cake5.png" alt="Choco Cake" />
          <h3>Choco Caramel Cake</h3>
        </div>
        <div class="product-card">
          <img src="../userSide/Images/pastry/pastry5.png" alt="Egg Pie" />
          <h3>Egg Pie Leche Plan</h3>
        </div>
      </div>
    </section>

    <section class="about" id="about">
      <h2>About Us</h2>
      <p>
        Cindy’s Bakeshop Hagonoy is a family-owned bakery passionate about bringing warmth and comfort through artisanal breads and pastries. Every item is crafted with care—from kneading the dough to the finishing touches—ensuring you enjoy the freshness in every bite.
      </p>
    </section>

    <section class="visit" id="visit">
      <h2>Visit or Contact Us</h2>
      <p>
        📍 Poblacion, Hagonoy, Bulacan<br />
        📞 +63 912 345 6789<br />
        📧 hello@cindysbakeshop.ph
      </p>
    </section>

    <footer>
      © <?= date('Y') ?> Cindy’s Bakeshop Hagonoy • Freshness Guaranteed
    </footer>

    <script>
      function toggleMenu() {
        document.getElementById("navMenu").classList.toggle("active");
      }
    </script>
  </body>
</html>
