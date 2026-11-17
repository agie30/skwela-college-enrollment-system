        </div>
    </div>
</div>

<?php require __DIR__ . '/profile_modal.php'; ?>
<script src="../assets/js/profile-modal.js" defer></script>
<script>
(function () {
    const sidebar = document.getElementById('adminPageSidebar');
    const toggle = document.getElementById('adminPageMenuToggle');
    const backdrop = document.getElementById('adminSidebarBackdrop');
    if (!sidebar || !toggle || !backdrop) return;
    const closeMenu = () => {
        sidebar.classList.remove('is-open');
        backdrop.classList.remove('is-visible');
        toggle.setAttribute('aria-expanded', 'false');
    };
    toggle.addEventListener('click', () => {
        const open = !sidebar.classList.contains('is-open');
        sidebar.classList.toggle('is-open', open);
        backdrop.classList.toggle('is-visible', open);
        toggle.setAttribute('aria-expanded', String(open));
    });
    backdrop.addEventListener('click', closeMenu);
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeMenu();
    });
    window.addEventListener('resize', () => {
        if (window.innerWidth > 700) closeMenu();
    });
})();
</script>
