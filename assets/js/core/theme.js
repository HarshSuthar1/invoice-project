const THEME_STORAGE_KEY = 'theme';
const LIGHT_THEME = 'light';
const DARK_THEME = 'dark';

const getStoredTheme = () => {
    try {
        const savedTheme = window.localStorage.getItem(THEME_STORAGE_KEY);
        return savedTheme === DARK_THEME ? DARK_THEME : LIGHT_THEME;
    } catch (error) {
        return LIGHT_THEME;
    }
};

const getNextTheme = (currentTheme) => (
    currentTheme === DARK_THEME ? LIGHT_THEME : DARK_THEME
);

const updateToggleButton = (theme) => {
    const toggleButton = document.getElementById('darkModeToggle');
    const icon = document.getElementById('darkModeIcon');
    const label = document.getElementById('darkModeLabel');
    const isDark = theme === DARK_THEME;

    if (!toggleButton) {
        return;
    }

    toggleButton.setAttribute('aria-pressed', String(isDark));
    toggleButton.setAttribute(
        'aria-label',
        isDark ? 'Switch to light mode' : 'Switch to dark mode'
    );
    toggleButton.setAttribute(
        'title',
        isDark ? 'Switch to light mode' : 'Switch to dark mode'
    );

    if (icon) {
        icon.textContent = isDark ? '\u2600' : '\u263E';
    }

    if (label) {
        label.textContent = isDark ? 'Light Mode' : 'Dark Mode';
    }
};

const applyTheme = (theme) => {
    const resolvedTheme = theme === DARK_THEME ? DARK_THEME : LIGHT_THEME;

    document.documentElement.setAttribute('data-theme', resolvedTheme);
    updateToggleButton(resolvedTheme);
};

export const initTheme = () => {
    applyTheme(getStoredTheme());
};

export const toggleTheme = () => {
    const currentTheme = document.documentElement.getAttribute('data-theme') || LIGHT_THEME;
    const nextTheme = getNextTheme(currentTheme);

    applyTheme(nextTheme);

    try {
        window.localStorage.setItem(THEME_STORAGE_KEY, nextTheme);
    } catch (error) {
        /* Ignore storage failures and keep the current page state. */
    }
};
