import { qs } from '../core/dom.js';
import { showError, showSuccess } from '../core/ui.js';
import { apiFetch } from '../core/api.js';

console.log('[signin.js] loaded');

const init = () => {
  const form = qs('#registerForm');

  if (form) {
    console.log('[signin.js] attaching submit handler');
    form.addEventListener('submit', async (e) => {
      console.log('[signin.js] submit event');
      e.preventDefault();

      const firstName = qs('#firstName')?.value || '';
      const lastName = qs('#lastName')?.value || '';
      const email = qs('#email')?.value || '';
      const username = qs('#username')?.value || '';
      const password = qs('#password')?.value || '';
      const confirmPassword = qs('#confirmPassword')?.value || '';

      // Basic validation
      if (password !== confirmPassword) {
        showError('Passwords do not match');
        return;
      }

      if (password.length < 6) {
        showError('Password must be at least 6 characters long');
        return;
      }

      try {
        let endpoint = form.getAttribute('action') || '/api/auth/register_user.php';
        if (!endpoint.startsWith('/') && !endpoint.startsWith('http')) {
          endpoint = `/${endpoint}`;
        }
        // Normalize endpoint to avoid accidental /public in path
        endpoint = endpoint.replace(/\/public(?=\/)/, '');
        if (!endpoint.startsWith('/')) endpoint = `/${endpoint}`;

        console.log('[signin.js] calling endpoint', endpoint);

        const data = await apiFetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ firstName, lastName, email, username, password }),
        });

        window.location.href = data.redirect || '/Business%20project/public/index.php?page=login&registered=true';
      } catch (err) {
        console.error('Register error:', err.message || err);
        showError(err.message || 'Registration failed. Please try again.');
      }
    });
  }
};

init();