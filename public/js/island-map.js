document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-interactive-map]');

    if (!root) {
        return;
    }

    const markers = [...root.querySelectorAll('[data-map-marker]')];
    const cards = [...document.querySelectorAll('[data-location-card]')];
    const filters = [...document.querySelectorAll('[data-map-filter]')];
    const search = document.querySelector('#map-location-search');
    const title = document.querySelector('#map-details-title');
    const category = document.querySelector('#map-details-category');
    const description = document.querySelector('#map-details-description');
    const detailsPanel = document.querySelector('#map-details');
    const count = document.querySelector('#map-result-count');
    const noResults = document.querySelector('#map-no-results');
    let activeFilter = 'all';
    let locateTimer;
    let detailsTimer;

    const categoryLabel = (value) => value
        .replaceAll('_', ' ')
        .replace(/\b\w/g, (letter) => letter.toUpperCase());

    const selectLocation = (id, moveFocus = false) => {
        const marker = markers.find((item) => item.dataset.locationId === String(id));

        if (!marker || marker.hidden) {
            return;
        }

        markers.forEach((item) => item.classList.toggle('is-active', item === marker));
        cards.forEach((item) => {
            item.querySelector('.map-place-card')?.classList.toggle('is-active', item.dataset.locationCard === String(id));
        });

        title.textContent = marker.dataset.name;
        category.textContent = categoryLabel(marker.dataset.category);
        description.textContent = marker.dataset.description;

        window.clearTimeout(detailsTimer);
        detailsPanel?.classList.remove('is-refreshing');

        if (detailsPanel) {
            void detailsPanel.offsetWidth;
            detailsPanel.classList.add('is-refreshing');
            detailsTimer = window.setTimeout(() => detailsPanel.classList.remove('is-refreshing'), 360);
        }

        if (moveFocus) {
            marker.focus({ preventScroll: true });
            root.scrollIntoView({ behavior: 'smooth', block: 'center' });

            window.clearTimeout(locateTimer);
            markers.forEach((item) => item.classList.remove('is-located'));

            window.setTimeout(() => {
                // Force a reflow so selecting the same directory card restarts the cue.
                void marker.offsetWidth;
                marker.classList.add('is-located');
                locateTimer = window.setTimeout(() => marker.classList.remove('is-located'), 1500);
            }, 250);
        }
    };

    const applyFilters = () => {
        const term = search?.value.trim().toLowerCase() ?? '';
        let visibleCount = 0;

        cards.forEach((card) => {
            const categoryMatches = activeFilter === 'all' || card.dataset.category === activeFilter;
            const searchMatches = !term || card.dataset.search.includes(term);
            const visible = categoryMatches && searchMatches;
            card.classList.toggle('d-none', !visible);
            visibleCount += visible ? 1 : 0;
        });

        markers.forEach((marker) => {
            const matchingCard = cards.find((card) => card.dataset.locationCard === marker.dataset.locationId);
            marker.hidden = matchingCard?.classList.contains('d-none') ?? true;
        });

        count.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'place' : 'places'}`;
        noResults.classList.toggle('d-none', visibleCount !== 0);

        const activeMarker = markers.find((marker) => marker.classList.contains('is-active') && !marker.hidden);
        if (!activeMarker) {
            const firstVisible = markers.find((marker) => !marker.hidden);
            if (firstVisible) {
                selectLocation(firstVisible.dataset.locationId);
            }
        }
    };

    markers.forEach((marker) => marker.addEventListener('click', () => selectLocation(marker.dataset.locationId)));

    cards.forEach((card) => {
        card.querySelector('[data-location-select]')?.addEventListener('click', () => {
            selectLocation(card.dataset.locationCard, true);
        });
    });

    filters.forEach((filter) => {
        filter.addEventListener('click', () => {
            activeFilter = filter.dataset.mapFilter;
            filters.forEach((item) => {
                const selected = item === filter;
                item.classList.toggle('active', selected);
                item.setAttribute('aria-pressed', String(selected));
            });
            applyFilters();
        });
    });

    search?.addEventListener('input', applyFilters);

    if (markers.length > 0) {
        selectLocation(markers[0].dataset.locationId);
    }
});
