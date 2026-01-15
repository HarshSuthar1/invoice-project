import { qs } from '../core/dom.js';
import { showError, showSuccess } from '../core/ui.js';
import { apiFetch } from '../core/api.js';

console.log('[login.js] loaded');

const init = () => {
  const form = qs('#loginForm');
  const forgot = qs('.forgot-password a');

  if (form) {
    console.log('[login.js] attaching submit handler');
    form.addEventListener('submit', async (e) => {
      console.log('[login.js] submit event');
      e.preventDefault();

      const usernameEl = qs('#username');
      const passwordEl = qs('#password');
      const rememberEl = qs('#remember');

      const username = usernameEl ? usernameEl.value : '';
      const password = passwordEl ? passwordEl.value : '';
      const remember = rememberEl ? rememberEl.checked : false;

      try {
        // Determine endpoint from form action (fallback) and normalize to root-leading path
        let endpoint = form.getAttribute('action') || '/api/auth/login_user.php';
        if (!endpoint.startsWith('/') && !endpoint.startsWith('http')) {
          endpoint = `/${endpoint}`; // make it root-relative so apiFetch can prefix BASE_PATH
        }

        // Normalize endpoint: strip accidental /public segment (happens when relative URL resolves under /public)
        endpoint = endpoint.replace(/\/public(?=\/)/, '');
        // Ensure root-leading
        if (!endpoint.startsWith('/')) endpoint = `/${endpoint}`;

        console.log('[login.js] calling endpoint', endpoint);

        const data = await apiFetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ username, password }),
        });

        // apiFetch returns the body (throws on failure)
        if (remember) localStorage.setItem('username', username);

        sessionStorage.setItem('user', JSON.stringify(data.user));
        sessionStorage.setItem('isLoggedIn', 'true');

        console.log('[login.js] login success, redirecting to', data.redirect);
        window.location.href = data.redirect || '/Business%20project/public/index.php?page=dashboard';
      } catch (err) {
        console.error('Login error:', err.message || err);
        showError(err.message || 'Invalid username or password');
      }
    });
  }

  if (forgot) {
    forgot.addEventListener('click', (e) => {
      e.preventDefault();
      showError('Please contact your administrator');
    });
  }

  // Prefill remembered username (script runs at bottom of body so DOM is ready)
  const rememberedUser = localStorage.getItem('username');
  if (rememberedUser) {
    const input = qs('#username');
    const remember = qs('#remember');
    if (input) input.value = rememberedUser;
    if (remember) remember.checked = true;
  }

  // Show registration success message when arriving with ?registered=true
  const params = new URLSearchParams(window.location.search);
  if (params.get('registered') === 'true') {
    showSuccess('Registration successful. Please sign in.');
  }
};

init();