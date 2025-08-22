import { initializeApp, getApps } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-app.js";
import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/10.12.2/firebase-auth.js";

async function loadFirebase() {
  if (!getApps().length) {
    const configUrl = new URL('../PHP/firebase_config.php', import.meta.url);
    const response = await fetch(configUrl, { credentials: 'same-origin' });
    if (!response.ok) {
      throw new Error(`Unable to load Firebase configuration: ${response.status}`);
    }
    initializeApp(await response.json());
  }
  const auth = getAuth();
  window.auth = auth;
  window.getAuth = getAuth;
  onAuthStateChanged(auth, user => {
    if (user) {
      console.log('Logged in as:', user.email);
    } else {
      console.log('Not logged in');
    }
  });
}

loadFirebase().catch(err => console.error('Firebase init failed:', err));
