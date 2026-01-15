/*
Shared API helper
All backend responses follow:
{ success: boolean, message?: string, data?: any }
*/

const BASE_PATH = '/Business%20project';

export const apiFetch = async (url, options = {}) => {
  try {
    // Prefix absolute root paths so fetch works from htdocs root when project is in a subfolder
    const fullUrl = url.startsWith(BASE_PATH) ? url : (url.startsWith('/') ? `${BASE_PATH}${url}` : url);

    console.debug('[apiFetch] requesting', fullUrl, options);
    const response = await fetch(fullUrl, {
      credentials: 'same-origin',
      ...options
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message || 'API Error');
    }

    return data.data ?? data;
  } catch (err) {
    console.error('API Error:', err.message);
    throw err;
  }
};
