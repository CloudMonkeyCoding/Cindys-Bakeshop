<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
 <style>
  * {
    box-sizing: border-box;
    font-family: 'Segoe UI', sans-serif;
  }

  body {
    margin: 0;
      background: url('../Images/cindyslogin.jpg') no-repeat center center fixed;
      background-size: cover;
  }
 

  .profile-container {
    max-width: 500px;
    margin: 50px auto;
    margin-right: 100px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(10px);
    padding: 35px 30px;
    border: 2px solid red;
    border-radius: 25px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
  }

  .profile-container h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #b30000;
    letter-spacing: 1px;
  }

  .back-arrow {
    font-size: 20px;
    cursor: pointer;
    color: red;
    margin-bottom: 10px;
  }

  .profile-pic {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
  }

  .profile-pic img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #ccc;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  }

  .form-row {
    display: flex;
    gap: 12px;
  }

  .form-row input {
    flex: 1;
  }

  input[type="text"],
  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 12px 16px;
    margin: 10px 0;
    border: 2px solid red;
    border-radius: 25px;
    outline: none;
    transition: 0.3s;
  }

  input:focus {
    border-color: orange;
    box-shadow: 0 0 8px rgba(255, 153, 0, 0.6);
  }

  button[type="submit"] {
    background: linear-gradient(to right, #ffd700, #ffcc00);
    border: none;
    padding: 12px 20px;
    width: 100%;
    border-radius: 30px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    color: #333;
    margin-top: 15px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.2);
  }

  button[type="submit"]:hover {
    background: linear-gradient(to right, #ffbf00, #ffc107);
  }

  @media (max-width: 550px) {
    .form-row {
      flex-direction: column;
    }
  }
</style>
</head>
<body>
  <?php include __DIR__ . '/../topbar.php'; ?>

  <div class="profile-container">
    <div class="back-arrow" onclick="window.history.back()">&larr; Back</div>
    <h2>EDIT PROFILE</h2>

    <div class="profile-pic">
      <img id="profilePic" src="../Images/default-avatar.png" alt="Profile Picture" />
    </div>

    <form id="editProfileForm">
      <div class="form-row">
        <input type="text" id="firstName" placeholder="First Name" required />
        <input type="text" id="lastName" placeholder="Last Name" required />
      </div>
      <input type="email" id="email" placeholder="Email" required />
      <input type="password" id="password" placeholder="New Password" required />
      <input type="password" id="confirmPassword" placeholder="Confirm Password" required />
      <button type="submit">Save Changes</button>
    </form>
  </div>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";


    const auth = getAuth();
    onAuthStateChanged(auth, user => {
      if (user) {
        fetch(`../../PHP/user_api.php?action=get_face&email=${encodeURIComponent(user.email)}`)
          .then(res => res.json())
          .then(data => {
            if (data.face_image_path) {
              document.getElementById('profilePic').src = data.face_image_path;
            }
          });
      }
    });

    document.getElementById("editProfileForm").addEventListener("submit", function(e) {
      e.preventDefault();

      const firstName = document.getElementById("firstName").value;
      const lastName = document.getElementById("lastName").value;
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;
      const confirmPassword = document.getElementById("confirmPassword").value;

      const strongPasswordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/;

      if (!strongPasswordRegex.test(password)) {
        alert("Password must be at least 8 characters long and include uppercase, lowercase, number, and special character.");
        return;
      }

      if (password !== confirmPassword) {
        alert("Passwords do not match!");
        return;
      }

      alert(`Profile updated successfully!\nName: ${firstName} ${lastName}\nEmail: ${email}`);
    });
  </script>

</body>
</html>
