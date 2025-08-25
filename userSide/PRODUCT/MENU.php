<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Cindy's Menu</title>
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">

  <style>
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background: #e6e6e6;
    }

   .top {
  position: relative;
  background: url('../Images/bread/top.jpg') no-repeat center center;
  background-size: cover;
  text-align: center;
  padding: 200px 20px;
  color: #000;
  z-index: 0;
  overflow: hidden;
}

.top::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.112); /* light overlay */
  z-index: -1;
}

  .center {
  position: relative;
  background: url('../Images/menu/hq720.jpg') no-repeat center center;
  background-size: cover;
  padding: 5px 20px;
  color: #000;
  z-index: 0;
  overflow: hidden;
}

.center::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.392); /* light overlay */
  z-index: -1;
}
.buttom {
  position: relative;
  background: url('../Images/menu/n.jpg') no-repeat center center;
  background-size: cover;
  color: #000;
  z-index: 0;
  overflow: hidden;
}
  .order-now {
  display: inline-block;
  background: #d6e200;
  color: black;
  text-decoration: none;
  padding: 6px 15px;
  margin-top: 8px;
  border-radius: 5px;
  font-weight: bold;
  transition: background-color 0.3s, color 0.3s;
}

/* HOVER EFFECT */
.order-now:hover {
  background-color: black;
  color: white;
}

.buttom::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255, 255, 255, 0.338); /* light overlay */
  z-index: -1;
}
    .hero {
      background-color: white;
      text-align: center;
      padding: 80px 20px 20px;
      
    }

    .hero h2 {
      margin: 0;
      font-size: 24px;
    }

    .hero p {
      color: red;
      margin-top: 10px;
      font-weight: bold;
    }

    .category {
      margin: 80px auto;
      width: 90%;
      background: #f1f1f1;
      border: 2px solid red;
      border-radius: 20px;
      padding: 30px;
    }

    .category-title {
      text-align: center;
      font-size: 22px;
      font-weight: bold;
      margin-bottom: 25px;
    }

    .category-title a {
      color: black;
      text-decoration: none;
      transition: 0.3s;
    }

    .category-title a:hover {
      color: red;
    }

    .items {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 25px;
    }

   .item {
  width: 220px;
  background: red;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 15px 10px;
  gap: 8px;
  transition: transform 0.2s;
}
.item a {
  text-decoration: none;
  color: inherit;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.item img {
  max-width: 300px;
  max-height: 200px;
  object-fit: contain;
}
 .item:hover {
      transform: scale(1.03);
    }

.product-name {
  font-weight: bold;
  color: rgb(255, 255, 255);
  text-align: center;
  font-size: 21px;
}


    footer {
      background: #333;
      color: white;
      display: flex;
      justify-content: space-around;
      padding: 30px 20px;
      margin-top: 50px;
      flex-wrap: wrap;
    }

    footer div {
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-width: 250px;
    }

    footer b {
      font-size: 16px;
      margin-bottom: 10px;
    }

    footer a {
      color: #ccc;
      text-decoration: none;
      font-size: 14px;
    }

    footer a:hover {
      color: #fff;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/../topbar.php'; ?>
 <section class="top">
</section>

  <!-- HERO -->
  <section class="hero">
    <h2>Find all your<br>Favourite Bread and Cakes</h2>
    <p>Savor great moments</p>
  </section>

   <section class="center">
  <!-- BREAD CATEGORY -->
  <section class="category">
    <div class="category-title">
      <a href="bread.php">Bread</a>
    </div>
   <div class="items">
  <div class="item">
  <a href="product.php?id=2">
    <img src="../Images/bread/bread2.png" alt="Ubeng Ube Loaf">
    <div class="product-name">UBENG UBE LOAF</div>
  </a>
  <a href="" class="order-now">Order Now</a>
</div>

<div class="item">
  <a href="product.php?id=11">
    <img src="../Images/bread/bread11.png" alt="Pinoy Pandesal">
    <div class="product-name">PINOY PANDESAL</div>
  </a>
  <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
</div>

<div class="item">
  <a href="product.php?id=3">
    <img src="../Images/bread/bread3.png" alt="Pandecoconut">
    <div class="product-name">PANDECOCONUT</div>
  </a>
  <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
</div>

<div class="item">
  <a href="product.php?id=4">
    <img src="../Images/bread/bread4.png" alt="Pande Espana">
    <div class="product-name">PANDE ESPANA</div>
  </a>
  <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
</div>

<div class="item">
  <a href="product.php?id=5">
    <img src="../Images/bread/bread5.png" alt="Ube Cheese Pandesal">
    <div class="product-name">UBE PANDESAL</div>
  </a>
  <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
</div>

</div>

</

    </div>
  </section>

  <!-- CAKES CATEGORY -->
  <section class="category">
    <div class="category-title">
      <a href="cakes.php">Cakes</a>
    </div>
    <div class="items">
       <div class="item">
  <a href="product.php?id=1">
   <img src="../Images/cakes/cake1.png" alt="Ubeng Ube Loaf">
   <div class="product-name">UBENG UBE LOAF</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=16">
    <img src="../Images/cakes/cake16.png" alt="CHOCO CELEBRATION ON CAKE RECTANGLE">
    <div class="product-name">CHOCO CAKE RECTANGLE</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=3">
    <img src="../Images/cakes/cake3.png" alt="CHOCO CHERRY CAKE">
    <div class="product-name">CHOCO CHERRY CAKE</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=4">
    <img src="../Images/cakes/cake4.png" alt="PASTEL DELIGHT ROUND CAKE">
    <div class="product-name">PASTEL ROUND CAKE</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=5">
    <img src="../Images/cakes/cake5.png" alt="CHOCO CARAMEL CAKE">
    <div class="product-name">CHOCO CARAMEL CAKE</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>
    </div>
  </section>
</section>

<section class="buttom">

  <!-- PASTRY CATEGORY -->
  <section class="category">
    <div class="category-title">
      <a href="pastry.php">Pastry</a>
    </div>
    <div class="items">
       <div class="item">
        <a href="product.php?id=4">
    <img src="../Images/pastry/pastry3.png" alt="Custard Surprise">
    <div class="product-name">CUSTARD SURPRISE PIE</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=9">
    <img src="../Images/pastry/pastry8.png" alt="Cheesy Ensaymada">
    <div class="product-name">CHESSY ENSAYMADA</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=6">
    <img src="../Images/pastry/pastry5.png" alt="Egg Pie Leche Plan">
    <div class="product-name">EGG PIE LECHE PLAN</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=7">
    <img src="../Images/pastry/pastry6.png" alt="Brownie Bites">
    <div class="product-name">SPECIAL BROWNIE BITES</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>

  <div class="item">
    <a href="product.php?id=8">
    <img src="../Images/pastry/pastry7.png" alt="Cluster Ensaymada ">
    <div class="product-name">CLUSTER ENSAYMADA</div>
    </a>
    <a href="../LOGIN_SIGNUP/user_login.html" class="order-now">Order Now</a>
  </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div>
      <b>Get to Know Us</b>
      <a href="#">About Us</a>
      <a href="#">Our Services</a>
      <a href="#">Contact Us</a>
    </div>
    <div>
      <b>Let Us Help You</b>
      <a href="#">📘 Facebook</a>
      <a href="#">📸 Instagram</a>
      <a href="#">🐦 Twitter</a>
    </div>
    <div>
      <b>About Our Company</b>
      <a href="#">About Us</a>
      <a href="#">Head office</a>
      <a href="#">Telephone: 99838737363828</a>
      <a href="#">Email: chadhbasjkb@com</a>
    </div>
  </footer>
  </section>

  <script type="module" src="../firebase-init.js"></script>

</body>
</html>
