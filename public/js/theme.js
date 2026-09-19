document.addEventListener('DOMContentLoaded', () => {
    const toggles = [...document.querySelectorAll('[data-theme-toggle]')];

    if (toggles.length === 0) {
        return;
    }

    const updateToggles = (theme) => {
        const isDark = theme === 'dark';

        toggles.forEach((toggle) => {
            toggle.setAttribute('aria-pressed', String(isDark));
            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');

            const icon = toggle.querySelector('[data-theme-icon]');
            const label = toggle.querySelector('[data-theme-label]');

            if (icon) {
                icon.textContent = isDark ? '\u2600' : '\u263E';
            }

            if (label) {
                label.textContent = isDark ? 'Light mode' : 'Dark mode';
            }
        });
    };

    const setTheme = (theme) => {
        document.documentElement.setAttribute('data-bs-theme', theme);
        localStorage.setItem('picnic-island-theme', theme);
        updateToggles(theme);
    };

    toggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    });

    updateToggles(document.documentElement.getAttribute('data-bs-theme') ?? 'light');
});
