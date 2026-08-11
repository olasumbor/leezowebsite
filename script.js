const hamburger = document.querySelector(".hamburger");
const navLinks = document.querySelector(".nav-links");
const navItems = document.querySelectorAll(".nav-links a");

if (hamburger && navLinks) {
    hamburger.addEventListener("click", () => {
        hamburger.classList.toggle("active");
        navLinks.classList.toggle("active");

        document.body.classList.toggle("menu-open");
    });
}

navItems.forEach(link => {
    link.addEventListener("click", () => {

        if (hamburger) {
            hamburger.classList.remove("active");
        }

        if (navLinks) {
            navLinks.classList.remove("active");
        }

        document.body.classList.remove("menu-open");
    });
});













 