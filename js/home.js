
document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menuToggle");
    const mainNav = document.getElementById("mainNav");

    if (!menuToggle || !mainNav) {
        return;
    }

    function closeMenu() {
        mainNav.classList.remove("open");
        menuToggle.setAttribute("aria-expanded", "false");
        menuToggle.setAttribute("aria-label", "Open navigation menu");
        menuToggle.textContent = "☰";
    }

    menuToggle.addEventListener("click", function () {

        const isOpen = mainNav.classList.toggle("open");

        menuToggle.setAttribute("aria-expanded", String(isOpen));

        menuToggle.setAttribute(
            "aria-label",
            isOpen ? "Close navigation menu" : "Open navigation menu"
        );

        menuToggle.textContent = isOpen ? "✕" : "☰";
    });

    // Close menu when a navigation link is clicked
    mainNav.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", closeMenu);
    });

    // Close menu using Escape key
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeMenu();
        }
    });

    // Close menu when clicking outside
    document.addEventListener("click", function (event) {
        if (
            !mainNav.contains(event.target) &&
            !menuToggle.contains(event.target)
        ) {
            closeMenu();
        }
    });

});
