/*
profile.js
Comprehensive profile management with all form fields
*/

import { apiFetch } from '../core/api.js';
import { qs } from '../core/dom.js';
import { showError, showSuccess } from '../core/ui.js';

/* -----------------------------
   Field mapping - all profile fields
------------------------------ */
const PROFILE_FIELDS = [
  // Company Information
  'company_name',
  'gst_number',
  'email',
  'phone',
  'website',
  'pan_number',
  'address',
  
  // Banking Information
  'bank_name',
  'account_number',
  'ifsc_code',
  'account_holder_name',
  'branch_name',
  'swift_code',
  
  // Invoice Preferences
  'invoice_prefix',
  'default_due_days',
  'invoice_terms',
  'payment_instructions'
];

/* -----------------------------
   Load profile
------------------------------ */
const loadProfile = async () => {
  try {
    const data = await apiFetch('/api/profile/get_profile.php');
    const profile = data.profile || data;

    console.log('[profile.js] Loaded profile:', profile);

    // Populate all form fields
    PROFILE_FIELDS.forEach(fieldName => {
      const field = qs(`[name="${fieldName}"]`);
      if (field) {
        field.value = profile[fieldName] ?? '';
      }
    });

    // Update preview section
    updatePreview(profile);

    // Update last updated timestamp if available
    if (profile.updated_at) {
      const lastUpdatedEl = qs('#lastUpdated');
      if (lastUpdatedEl) {
        lastUpdatedEl.textContent = new Date(profile.updated_at).toLocaleString();
      }
    }

  } catch (err) {
    console.error('[profile.js] Error loading profile:', err);
    showError(err.message || 'Failed to load profile');
  }
};

/* -----------------------------
   Update preview section
------------------------------ */
const updatePreview = (profile) => {
  const companyName = qs('.company-preview');
  const companyDetails = qs('.company-details');

  if (companyName) {
    companyName.textContent = profile.company_name || 'Your Company Name';
  }

  if (companyDetails) {
    const details = [];
    
    if (profile.email) details.push(`Email: ${profile.email}`);
    if (profile.phone) details.push(`Phone: ${profile.phone}`);
    if (profile.gst_number) details.push(`GST: ${profile.gst_number}`);
    if (profile.address) details.push(profile.address);

    companyDetails.innerHTML = details.map(d => `<div>${d}</div>`).join('');
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

    // Reload profile to get updated data
    await loadProfile();

  } catch (err) {
    console.error('[profile.js] Error saving profile:', err);
    showError(err.message || 'Failed to update profile');
  } finally {
    form?.classList.remove('loading');
    if (saveBtn) saveBtn.disabled = false;
  }
});

/* -----------------------------
   Real-time preview updates
------------------------------ */
const setupLivePreview = () => {
  const fieldsToWatch = ['company_name', 'email', 'phone', 'gst_number', 'address'];
  
  fieldsToWatch.forEach(fieldName => {
    const field = qs(`[name="${fieldName}"]`);
    if (field) {
      field.addEventListener('input', () => {
        const profile = {};
        PROFILE_FIELDS.forEach(name => {
          const f = qs(`[name="${name}"]`);
          if (f) profile[name] = f.value;
        });
        updatePreview(profile);
      });
    }
  });
};

/* -----------------------------
   Initialize
------------------------------ */
document.addEventListener('DOMContentLoaded', () => {
  loadProfile();
  setupLivePreview();
});

// Allow save button outside the form to submit the profile form
document.addEventListener('click', (e) => {
  if (e.target.dataset && e.target.dataset.action === 'save-profile') {
    const form = qs('#profileForm');
    if (form) {
      form.requestSubmit ? form.requestSubmit() : form.dispatchEvent(new Event('submit', {cancelable: true}));
    }
  }
});