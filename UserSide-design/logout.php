<?php
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Logging out...</title>
  <style>
    body { display:flex; align-items:center; justify-content:center; min-height:100vh; font-family:'Poppins',sans-serif; background:#faf5ef; color:#8b4513; }
  </style>
</head>
<body>
  <p>Signing you out...</p>

  <script type="module">
    import '../userSide/firebase-init.js';
    import { getAuth, signOut } from 'https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js';

    const auth = getAuth();
    signOut(auth).finally(() => {
      window.location.href = 'login.php';
    });
  </script>
</body>
</html>
