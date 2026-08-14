const modal = document.querySelector('#event-poster-modal');

if (modal) {
    const image = modal.querySelector('img');
    const close = () => modal.close();

    document.querySelectorAll('[data-event-poster]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            image.src = trigger.dataset.eventPoster;
            image.alt = trigger.dataset.eventPosterAlt || 'Event poster';
            modal.showModal();
        });
    });

    modal.querySelector('[data-close-poster]').addEventListener('click', close);
    modal.addEventListener('click', (event) => {
        const bounds = modal.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) close();
    });
}
