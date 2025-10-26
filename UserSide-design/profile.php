<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Profile - Cindy’s Bakeshop</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet"/>
  <style>
    *{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
    body{background:#faf9f7;color:#333;min-height:100vh;display:flex;flex-direction:column;}

    nav{background:#8b4513;color:#fff;display:flex;justify-content:space-between;align-items:center;padding:1rem 2rem;}
    nav .logo{font-size:1.4rem;font-weight:600;}
    nav ul{list-style:none;display:flex;gap:1.5rem;align-items:center;}
    nav ul li a{color:#fff;text-decoration:none;font-weight:500;}
    nav ul li a:hover{text-decoration:underline;}

    .profile-container{max-width:640px;margin:2.5rem auto;background:#fff;padding:2rem;border-radius:16px;box-shadow:0 8px 20px rgba(0,0,0,0.08);} 
    .profile-container h2{color:#8b4513;margin-bottom:1rem;text-align:center;}
    .profile-view{text-align:center;display:flex;flex-direction:column;gap:1rem;}
    .profile-pic{width:120px;height:120px;border-radius:50%;object-fit:cover;margin:0 auto;border:4px solid #f0d6bd;}
    .profile-details p{margin:0.5rem 0;font-size:1rem;}
    .profile-details span{font-weight:600;color:#8b4513;}
    .btn{margin-top:1.5rem;background:#8b4513;color:#fff;border:none;padding:0.8rem 1.5rem;border-radius:10px;cursor:pointer;font-weight:600;transition:background 0.2s;}
    .btn:hover{background:#a0522d;}

    .profile-form{display:none;margin-top:1rem;}
    .profile-form label{display:block;font-weight:600;margin-top:1rem;}
    .profile-form input,.profile-form textarea{width:100%;padding:0.75rem;border:1px solid #d6c5b6;border-radius:10px;margin-top:0.4rem;font-size:1rem;background:#fffaf4;}
    .profile-form textarea{resize:vertical;min-height:100px;}
    .form-actions{display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap;}
    .cancel-btn{background:#f5e6d8;color:#8b4513;}
    .cancel-btn:hover{background:#edd5be;}
    #picUpload{display:none;}
    .error{color:#c0392b;text-align:center;margin-top:1rem;min-height:1.2rem;}
    .success{color:#2e7d32;text-align:center;margin-top:1rem;min-height:1.2rem;}
  </style>
</head>
<body>
  <nav>
    <div class="logo">🍞 Cindy’s Bakeshop</div>
    <ul>
      <li><a href="menu.php">Menu</a></li>
      <li><a href="favorites.php">Favorites</a></li>
      <li><a href="checkout.php">Cart</a></li>
      <li><a href="orders.php">Orders</a></li>
      <li><a href="settings.php">Settings</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <div class="profile-container">
    <h2>My Profile</h2>

    <div class="profile-view" id="profileView">
      <img src="../userSide/Images/cindy_s logo.png" alt="Profile Picture" id="profilePic" class="profile-pic"/>
      <div class="profile-details">
        <p><span>Full Name:</span> <span id="viewName">Loading...</span></p>
        <p><span>Email:</span> <span id="viewEmail"></span></p>
        <p><span>Address:</span> <span id="viewAddress">—</span></p>
      </div>
      <button class="btn" id="editBtn">Edit Profile</button>
      <button class="btn" id="settingsBtn" style="background:#f5e6d8; color:#8b4513;">Account Settings</button>
    </div>

    <form class="profile-form" id="profileForm">
      <input type="file" id="picUpload" accept="image/*">
      <button type="button" class="btn" id="changePhotoBtn" style="width:100%;margin-top:0;">Change Profile Photo</button>

      <label for="firstName">First Name</label>
      <input type="text" id="firstName" required>

      <label for="lastName">Last Name</label>
      <input type="text" id="lastName" required>

      <label for="emailField">Email Address</label>
      <input type="email" id="emailField" readonly>

      <label for="addressField">Delivery Address</label>
      <textarea id="addressField"></textarea>

      <label for="passwordField">New Password <span style="font-weight:400;color:#777;">(optional)</span></label>
      <input type="password" id="passwordField" placeholder="••••••••">

      <label for="confirmPasswordField">Confirm New Password</label>
      <input type="password" id="confirmPasswordField" placeholder="Repeat new password">

      <div class="form-actions">
        <button type="submit" class="btn" style="flex:1;">Save Changes</button>
        <button type="button" class="btn cancel-btn" id="cancelBtn" style="flex:1;">Cancel</button>
      </div>
    </form>
    <div class="error" id="errorMsg"></div>
    <div class="success" id="successMsg"></div>
  </div>

  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, onAuthStateChanged } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const auth = getAuth();
    let userEmail = null;
    let currentImage = null;

    const profileView = document.getElementById('profileView');
    const profileForm = document.getElementById('profileForm');
    const profilePic = document.getElementById('profilePic');
    const editBtn = document.getElementById('editBtn');
    const settingsBtn = document.getElementById('settingsBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const picUpload = document.getElementById('picUpload');
    const viewName = document.getElementById('viewName');
    const viewEmail = document.getElementById('viewEmail');
    const viewAddress = document.getElementById('viewAddress');
    const firstNameInput = document.getElementById('firstName');
    const lastNameInput = document.getElementById('lastName');
    const emailField = document.getElementById('emailField');
    const addressField = document.getElementById('addressField');
    const passwordField = document.getElementById('passwordField');
    const confirmPasswordField = document.getElementById('confirmPasswordField');
    const errorMsg = document.getElementById('errorMsg');
    const successMsg = document.getElementById('successMsg');

    function splitName(fullName) {
      const parts = (fullName || '').trim().split(/\s+/);
      return {
        first: parts[0] || '',
        last: parts.length > 1 ? parts.slice(1).join(' ') : ''
      };
    }

    function showView() {
      profileView.style.display = 'flex';
      profileForm.style.display = 'none';
      errorMsg.textContent = '';
      successMsg.textContent = '';
      if (currentImage) {
        profilePic.src = currentImage;
      }
    }

    function showForm() {
      profileView.style.display = 'none';
      profileForm.style.display = 'block';
      successMsg.textContent = '';
      errorMsg.textContent = '';
    }

    async function loadProfile() {
      if (!userEmail) return;
      try {
        const resp = await fetch(`/PHP/user_api.php?action=get_profile&email=${encodeURIComponent(userEmail)}`);
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        const split = splitName(data.name);
        firstNameInput.value = split.first;
        lastNameInput.value = split.last;
        emailField.value = userEmail;
        addressField.value = data.address || '';
        viewName.textContent = data.name || '—';
        viewEmail.textContent = userEmail;
        viewAddress.textContent = data.address || '—';
        const imagePath = data.face_image_path || '../userSide/Images/cindy_s logo.png';
        currentImage = imagePath.startsWith('http') ? imagePath : imagePath.startsWith('/') ? imagePath : `..${imagePath}`;
        profilePic.src = currentImage;
      } catch (err) {
        console.error('Failed to load profile', err);
        errorMsg.textContent = 'Unable to load profile information.';
      }
    }

    async function updateAddress(fullName) {
      try {
        const params = new URLSearchParams({
          email: userEmail,
          name: fullName,
          address: addressField.value.trim()
        });
        await fetch('/PHP/user_api.php?action=set_profile', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: params
        });
      } catch (err) {
        console.error('Failed to update address', err);
      }
    }

    profileForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      errorMsg.textContent = '';
      successMsg.textContent = '';

      const first = firstNameInput.value.trim();
      const last = lastNameInput.value.trim();
      const password = passwordField.value;
      const confirmPassword = confirmPasswordField.value;

      if (!first || !last) {
        errorMsg.textContent = 'Please provide both first and last name.';
        return;
      }
      if (password && password !== confirmPassword) {
        errorMsg.textContent = 'Passwords do not match.';
        return;
      }

      const formData = new FormData();
      formData.append('first_name', first);
      formData.append('last_name', last);
      formData.append('email', userEmail);
      if (password) {
        formData.append('password', password);
      }
      if (picUpload.files[0]) {
        formData.append('profile_picture', picUpload.files[0]);
      }

      try {
        const resp = await fetch('/PHP/user_api.php?action=update_profile', {
          method: 'POST',
          body: formData
        });
        const text = await resp.text();
        if (!resp.ok) throw new Error(text);
        const data = JSON.parse(text);
        const fullName = `${first} ${last}`.trim();
        await updateAddress(fullName);
        successMsg.textContent = data.message || 'Profile updated successfully!';
        currentImage = data.face_image_path || currentImage;
        profilePic.src = currentImage || profilePic.src;
        viewName.textContent = fullName;
        viewAddress.textContent = addressField.value.trim() || '—';
        passwordField.value = '';
        confirmPasswordField.value = '';
        showView();
      } catch (err) {
        console.error('Failed to update profile', err);
        errorMsg.textContent = 'An error occurred while updating your profile.';
      }
    });

    editBtn.addEventListener('click', () => {
      showForm();
    });

    cancelBtn.addEventListener('click', () => {
      passwordField.value = '';
      confirmPasswordField.value = '';
      picUpload.value = '';
      showView();
    });

    changePhotoBtn.addEventListener('click', () => {
      picUpload.click();
    });

    settingsBtn?.addEventListener('click', () => {
      window.location.href = 'settings.php';
    });

    picUpload.addEventListener('change', () => {
      const file = picUpload.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          profilePic.src = e.target.result;
        };
        reader.readAsDataURL(file);
      } else if (currentImage) {
        profilePic.src = currentImage;
      }
    });

    onAuthStateChanged(auth, (user) => {
      if (!user) {
        window.location.href = 'login.php';
        return;
      }
      userEmail = user.email;
      loadProfile();
    });
  </script>
</body>
</html>
