// GlobeTrek Admin - single shared JS file.
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggle && sidebar && overlay) {
        const setOpen = function (open) {
            sidebar.classList.toggle('open', open);
            overlay.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close admin menu' : 'Open admin menu');
        };
        toggle.addEventListener('click', function () { setOpen(!sidebar.classList.contains('open')); });
        overlay.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setOpen(false); });
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () { setOpen(false); });
        });
    }

    // Confirm all destructive / sensitive actions before form POST.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const question = form.getAttribute('data-confirm') || 'Continue with this action?';
            if (!window.confirm(question)) event.preventDefault();
        });
    });

    // Selected image preview; never uploads until the user presses Save.
    document.querySelectorAll('input[type="file"][data-image-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const target = document.getElementById(input.getAttribute('data-image-preview'));
            const file = input.files && input.files[0];
            if (!target || !file || !file.type.startsWith('image/')) return;
            const url = URL.createObjectURL(file);
            target.onload = function () { URL.revokeObjectURL(url); };
            target.src = url;
            target.hidden = false;
        });
    });
});
