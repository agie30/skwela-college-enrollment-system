(function () {
    const modal = document.getElementById('sharedProfileModal');
    if (!modal) return;

    const preview = document.getElementById('sharedProfilePhotoPreview');
    const fallback = document.getElementById('sharedProfilePhotoFallback');
    const input = document.getElementById('sharedProfilePictureInput');

    window.openSharedProfile = function () {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        const closeButton = modal.querySelector('.shared-profile-close');
        if (closeButton) closeButton.focus();
    };

    window.closeSharedProfile = function () {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    };

    if (input) {
        input.addEventListener('change', function () {
            const file = input.files && input.files[0];
            if (!file) return;
            const validTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Choose a JPG, PNG, or WEBP image.');
                input.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                alert('The image must be 2 MB or smaller.');
                input.value = '';
                return;
            }
            if (preview && fallback) {
                preview.src = URL.createObjectURL(file);
                preview.hidden = false;
                fallback.hidden = true;
            }
        });
    }

    modal.addEventListener('click', function (event) {
        if (event.target === modal) window.closeSharedProfile();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            window.closeSharedProfile();
        }
    });

    if (modal.dataset.open === '1') window.openSharedProfile();
})();
