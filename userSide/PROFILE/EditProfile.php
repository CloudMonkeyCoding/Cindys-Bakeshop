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
      <input type="file" id="profilePicInput" accept="image/*" />
      <button type="submit">Save Changes</button>
    </form>
    <div id="serverMessage"></div>
    <p><a href="Settings.php">Change Password</a></p>
  </div>

  <script type="module">
    import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";
    import "../firebase-init.js";


    const auth = getAuth();
    onAuthStateChanged(auth, user => {
      if (user) {
        document.getElementById('email').value = user.email;
        fetch(`../../PHP/user_api.php?action=get_profile&email=${encodeURIComponent(user.email)}`)
          .then(res => res.json())
          .then(data => {
            if (data.first_name) {
              document.getElementById('firstName').value = data.first_name;
            }
            if (data.last_name) {
              document.getElementById('lastName').value = data.last_name;
            }
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
      const profilePicFile = document.getElementById("profilePicInput").files[0];
      const msgEl = document.getElementById('serverMessage');

      if (profilePicFile && profilePicFile.size > 5 * 1024 * 1024) {
        msgEl.textContent = 'Profile picture must be 5MB or less.';
        msgEl.style.color = 'red';
        return;
      }

      const formData = new FormData();
      formData.append('first_name', firstName);
      formData.append('last_name', lastName);
      formData.append('email', email);
      if (profilePicFile) {
        formData.append('profile_picture', profilePicFile);
      }

      fetch('../../PHP/user_api.php?action=update_profile', {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            msgEl.textContent = data.error;
            msgEl.style.color = 'red';
            return;
          }
          msgEl.textContent = data.message || 'Profile updated successfully!';
          msgEl.style.color = 'green';
          if (data.face_image_path) {
            document.getElementById('profilePic').src = data.face_image_path;
          }
          document.getElementById('profilePicInput').value = '';
        })
        .catch(() => {
          msgEl.textContent = 'An error occurred while updating profile.';
          msgEl.style.color = 'red';
        });
    });
  </script>

</body>
</html>
