document.addEventListener('DOMContentLoaded', () => {
    const navigationDropdowns = document.querySelectorAll('.nav-dropdown');

    navigationDropdowns.forEach((dropdown) => {
        dropdown.addEventListener('toggle', () => {
            if (dropdown.open) {
                navigationDropdowns.forEach((otherDropdown) => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.open = false;
                    }
                });
            }
        });
    });

    document.addEventListener('click', (event) => {
        navigationDropdowns.forEach((dropdown) => {
            if (!dropdown.contains(event.target)) {
                dropdown.open = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            navigationDropdowns.forEach((dropdown) => {
                dropdown.open = false;
            });
        }
    });

    document.querySelectorAll('.has-zone-link').forEach((zone) => {
        const openZone = () => window.open(zone.dataset.zoneUrl, '_blank', 'noopener,noreferrer');

        zone.addEventListener('click', openZone);
        zone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openZone();
            }
        });
    });

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});
