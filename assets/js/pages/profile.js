/*
profile.js
Preserves:
- profile fetch
- form population
- profile update
*/

import { apiFetch } from '../core/api.js';
import { qs } from '../core/dom.js';
import { showError, showSuccess } from '../core/ui.js';

/* -----------------------------
   Load profile
------------------------------ */
const loadProfile = async () => {
  try {
    const data = await apiFetch('/api/profile/get_profile.php');
    const profile = data.profile;

    Object.keys(profile).forEach(key => {
      const field = qs(`[name="${key}"]`);
      if (field) {
        field.value = profile[key] ?? '';
      }
    });

  } catch (err) {
    showError(err.message || 'Failed to load profile');
  }
};

/* -----------------------------
   Save profile
------------------------------ */
qs('#profileForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();

  const form = qs('#profileForm');
  const saveBtn = document.querySelector('[data-action="save-profile"]');

  const formData = new FormData(e.target);

  try {
    form?.classList.add('loading');
    if (saveBtn) saveBtn.disabled = true;

    await apiFetch('/api/profile/save_profile.php', {
      method: 'POST',
      body: formData
    });

    showSuccess('Profile updated successfully');

  } catch (err) {
    showError(err.message || 'Failed to update profile');
  } finally {
    form?.classList.remove('loading');
    if (saveBtn) saveBtn.disabled = false;
  }
});

/* -----------------------------
   Helpers
------------------------------ */
document.addEventListener('DOMContentLoaded', loadProfile);

// Allow save button outside the form to submit the profile form
document.addEventListener('click', (e) => {
  if (e.target.dataset && e.target.dataset.action === 'save-profile') {
    qs('#profileForm')?.requestSubmit ? qs('#profileForm').requestSubmit() : qs('#profileForm')?.dispatchEvent(new Event('submit', {cancelable: true}));
  }
});

