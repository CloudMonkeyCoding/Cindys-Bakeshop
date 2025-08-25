<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../styles.css" />
</head>
<body class="edit-profile-page">
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
