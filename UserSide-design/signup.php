<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign Up - Cindy’s Bakeshop Hagonoy</title>

<style>
* { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',sans-serif; }
body, html { height:100%; width:100%; }
body {
  background: linear-gradient(120deg, rgba(214,105,30,0.8), rgba(255,255,255,0.2)), url('../userSide/Images/bread/bread5.png') no-repeat center center/cover;
  display:flex; flex-direction:column; min-height:100vh; position:relative;
}
body::before { content:""; position:absolute; inset:0; background:rgba(0,0,0,0.45); z-index:0; }

header {
  background: #fff;
  padding: 1rem 2rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: relative;
  z-index: 2;
  border-bottom: 1px solid #eee;
}
header .logo img { height:50px; }
nav { flex: 1; display: flex; justify-content: center; }
nav ul { list-style: none; display: flex; gap: 2rem; align-items: center; }
nav ul li a {
  color: #333;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s ease;
  padding: 6px 10px;
  border-radius: 18px;
}
nav ul li a.active,
nav ul li a:hover {
  background: #fcbf49;
  color: #333;
}

.hamburger { display:none; font-size:1.8rem; cursor:pointer; }
@media (max-width:768px){
  nav { position:absolute; top:70px; left:0; right:0; background:#fff; display:none; flex-direction:column; text-align:center; border-bottom:1px solid #eee; padding:1rem 0; }
  nav.active { display:flex; }
  nav ul { flex-direction: column; gap:1.5rem; }
  .hamburger { display:block; }
}

.main { position:relative; z-index:2; flex:1; display:flex; justify-content:center; align-items:center; padding:2rem; }
.signup-box {
  background: rgba(255,255,255,0.98);
  padding:2.5rem;
  border-radius:12px;
  box-shadow:0 6px 20px rgba(0,0,0,0.3);
  max-width:480px;
  width:100%;
  display:flex;
  flex-direction:column;
  gap:16px;
}
.signup-box h2 { color:#d62828; text-align:center; margin-bottom:0.5rem; font-size:1.6rem; }

.input-group { position:relative; display:flex; flex-direction:column; gap:6px; }
.input-group label { font-weight:600; }
.input-group input { padding:12px; border-radius:8px; border:1px solid #ccc; font-size:1rem; width:100%; transition:border-color 0.2s; }
.input-group input:focus { border-color:#d62828; outline:none; }

.password-wrapper { position:relative; display:flex; align-items:center; }
.password-wrapper input { flex:1; padding-right:40px; }
.eye-icon { position:absolute; right:10px; cursor:pointer; width:24px; height:24px; fill:#666; transition:0.2s; }
.eye-icon:hover { fill:#d62828; }

button { width:100%; padding:12px; background:#d62828; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer; font-size:1rem; margin-top:8px; transition:background 0.2s; }
button:hover { background:#b71c1c; }
.small-btn { background:#fcbf49; color:#333; padding:6px 10px; font-size:0.9rem; border-radius:6px; margin-top:5px; width:auto; cursor:pointer; border:none; }
.note { font-size:0.85rem; color:#666; margin-top:8px; text-align:center; }
.note a { color:#d62828; font-weight:600; text-decoration:none; }
.error { color:#d62828; text-align:center; min-height:1.3rem; font-size:0.9rem; }
.success { color:#2e7d32; text-align:center; min-height:1.3rem; font-size:0.95rem; }

.face-capture { display:flex; flex-direction:column; gap:10px; align-items:center; border:1px dashed #ccc; padding:1rem; border-radius:12px; background:#fff6ed; }
.face-capture video { width:100%; max-width:300px; border-radius:10px; display:none; }
.face-capture img { width:100%; max-width:300px; border-radius:10px; display:none; }
.capture-actions { display:flex; gap:10px; flex-wrap:wrap; justify-content:center; }
.capture-note { font-size:0.85rem; color:#555; text-align:center; }
</style>
</head>
<body>

<header>
  <div class="logo"><img src="../Kehnt_admin_Design/Cindys.png" alt="Cindy's Logo"></div>
  <div class="hamburger" onclick="toggleMenu()">☰</div>
  <nav id="navMenu">
    <ul>
      <li><a href="home.php">Home</a></li>
      <li><a href="login.php">Login</a></li>
      <li><a href="home.php#about">About</a></li>
      <li><a href="home.php#visit">Contacts</a></li>
      <li><a href="signup.php" class="active">Signup</a></li>
    </ul>
  </nav>
</header>

<div class="main">
  <div class="signup-box">
    <h2>Create your account</h2>
    <div class="error" id="errorMsg"></div>
    <div class="success" id="successMsg"></div>
    <form id="signupForm">
      <div class="input-group">
        <label>First Name</label>
        <input id="fname" name="fname" autocomplete="given-name" required>
      </div>
      <div class="input-group">
        <label>Last Name</label>
        <input id="lname" name="lname" autocomplete="family-name" required>
      </div>
      <div class="input-group">
        <label>Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required>
        <button type="button" class="small-btn" id="sendCodeBtn">Send Verification Code</button>
        <input id="emailCode" name="emailCode" type="text" placeholder="Enter verification code" style="display:none;" required>
      </div>
      <div class="input-group">
        <label>Password</label>
        <div class="password-wrapper">
          <input id="password" name="password" type="password" autocomplete="new-password" required>
          <svg id="eyeIcon" class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
          </svg>
        </div>
      </div>
      <div class="input-group">
        <label>Confirm Password</label>
        <div class="password-wrapper">
          <input id="confirmPassword" name="confirmPassword" type="password" autocomplete="new-password" required>
          <svg id="eyeIcon2" class="eye-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
            <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/>
          </svg>
        </div>
      </div>

      <div class="face-capture" id="faceCapture">
        <strong>Face Capture</strong>
        <video id="video" autoplay playsinline muted></video>
        <img id="capturedImage" src="" alt="Captured face preview">
        <div class="capture-actions">
          <button type="button" class="small-btn" id="startCameraBtn">Enable Camera</button>
          <button type="button" class="small-btn" id="captureBtn" disabled>Capture Face</button>
          <button type="button" class="small-btn" id="retakeBtn" style="display:none;">Retake</button>
        </div>
        <p class="capture-note">A quick face capture helps us verify your profile across the app.</p>
      </div>

      <input type="hidden" id="faceImage" name="faceImage">

      <button type="submit">Register</button>
    </form>
    <div class="note">Please verify your email, confirm your password, and capture your face before registering.</div>
  </div>
</div>

<script>
const sendCodeBtn = document.getElementById('sendCodeBtn');
const emailInput = document.getElementById('email');
const emailCodeInput = document.getElementById('emailCode');
let generatedCode = null;

sendCodeBtn.addEventListener('click', () => {
  const email = emailInput.value.trim();
  if (!email) { alert('Enter your email first'); return; }
  generatedCode = Math.floor(100000 + Math.random() * 900000).toString();
  emailCodeInput.style.display = 'block';
  alert("Verification code for " + email + " is: " + generatedCode + " (demo)");
});

const passwordInput = document.getElementById("password");
const eyeIcon = document.getElementById("eyeIcon");
const confirmInput = document.getElementById("confirmPassword");
const eyeIcon2 = document.getElementById("eyeIcon2");

eyeIcon.addEventListener("click", () => {
  const type = passwordInput.type === "password" ? "text" : "password";
  passwordInput.type = type;
  eyeIcon.style.fill = type === "text" ? "#d62828" : "#666";
});

eyeIcon2.addEventListener("click", () => {
  const type = confirmInput.type === "password" ? "text" : "password";
  confirmInput.type = type;
  eyeIcon2.style.fill = type === "text" ? "#d62828" : "#666";
});

function toggleMenu() { document.getElementById("navMenu").classList.toggle("active"); }
</script>

<script type="module">
  import '../userSide/firebase-init.js';
  import { getAuth, createUserWithEmailAndPassword } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

  const errorMsg = document.getElementById('errorMsg');
  const successMsg = document.getElementById('successMsg');
  const form = document.getElementById('signupForm');
  const faceInput = document.getElementById('faceImage');
  const video = document.getElementById('video');
  const capturedImage = document.getElementById('capturedImage');
  const startCameraBtn = document.getElementById('startCameraBtn');
  const captureBtn = document.getElementById('captureBtn');
  const retakeBtn = document.getElementById('retakeBtn');
  let stream = null;

  async function startCamera() {
    if (stream) return;
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
      video.srcObject = stream;
      video.style.display = 'block';
      captureBtn.disabled = false;
    } catch (err) {
      console.error('Camera access denied', err);
      errorMsg.textContent = 'Unable to access camera. Please allow camera permissions.';
    }
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach(track => track.stop());
      stream = null;
    }
    video.srcObject = null;
    video.style.display = 'none';
  }

  startCameraBtn.addEventListener('click', () => {
    errorMsg.textContent = '';
    startCamera();
  });

  captureBtn.addEventListener('click', () => {
    if (!stream) {
      errorMsg.textContent = 'Start the camera first to capture your face.';
      return;
    }
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/png');
    faceInput.value = imageData;
    capturedImage.src = imageData;
    capturedImage.style.display = 'block';
    retakeBtn.style.display = 'inline-block';
    stopCamera();
    captureBtn.disabled = true;
    successMsg.textContent = 'Face captured successfully!';
  });

  retakeBtn.addEventListener('click', () => {
    capturedImage.style.display = 'none';
    faceInput.value = '';
    successMsg.textContent = '';
    captureBtn.disabled = false;
    startCamera();
  });

  function isStrongPassword(pw) {
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
    return regex.test(pw);
  }

  const auth = getAuth();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorMsg.textContent = '';
    successMsg.textContent = '';

    const firstName = document.getElementById('fname').value.trim();
    const lastName = document.getElementById('lname').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const enteredCode = emailCodeInput.value.trim();

    if (!isStrongPassword(password)) {
      errorMsg.textContent = 'Password must be 8+ chars with uppercase, lowercase, number, and symbol.';
      return;
    }
    if (password !== confirmPassword) {
      errorMsg.textContent = 'Passwords do not match.';
      return;
    }
    if (!generatedCode || enteredCode !== generatedCode) {
      errorMsg.textContent = 'Invalid or missing email verification code.';
      return;
    }
    if (!faceInput.value) {
      errorMsg.textContent = 'Please capture your face before signing up.';
      return;
    }

    try {
      await createUserWithEmailAndPassword(auth, email, password);
      const fullName = `${firstName} ${lastName}`.trim();
      const response = await fetch('../PHP/register_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ fullName, email, password, faceImage: faceInput.value })
      });
      const text = await response.text();
      if (!response.ok) {
        throw new Error(text);
      }
      const data = JSON.parse(text);
      if (!data.success) {
        throw new Error(data.error || 'Failed to save user');
      }
      successMsg.textContent = 'Signup successful! Redirecting to login...';
      errorMsg.textContent = '';
      setTimeout(() => window.location.href = 'login.php', 1800);
    } catch (err) {
      console.error(err);
      errorMsg.textContent = err.message.replace('Firebase:', '').trim();
    }
  });
</script>

</body>
</html>
