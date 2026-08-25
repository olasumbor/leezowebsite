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

// Dynamic Navbar Rendering for Logged In vs Logged Out Users
function renderDynamicNavbar() {
    const nav = document.querySelector("nav");
    if (!nav) return;

    const token = localStorage.getItem("auth_token");
    const loggedIn = localStorage.getItem("loggedIn") === "true";
    const isLoggedIn = !!token || loggedIn;

    let rawPath = window.location.pathname.toLowerCase().split('?')[0].split('#')[0].replace(/\/+$/, '') || 'index.html';
    let currentPath = rawPath.split('/').pop() || 'index.html';
    if (currentPath && !currentPath.includes('.')) {
        currentPath += '.html';
    }

    let activeNavLinks = [];
    if (isLoggedIn) {
        // Logged-in navbar menu items
        activeNavLinks = [
            { href: "index.html", label: "Home" },
            { href: "about.html", label: "About Us" },
            { href: "services.html", label: "Our Services" },
            { href: "pickup-delivery.html", label: "Pickup & Delivery" },
            { href: "frozen-cargo.html", label: "Frozen Cargo" },
            { href: "procurement.html", label: "Procurement" },
            { href: "gallery.html", label: "Gallery" },
            { href: "track-shipment.html", label: "Track Shipment" },
            { href: "contact.html", label: "Contact Us" }
        ];
    } else {
        // Logged-out navbar menu items (protected features hidden)
        activeNavLinks = [
            { href: "index.html", label: "Home" },
            { href: "about.html", label: "About Us" },
            { href: "services.html", label: "Our Services" },
            { href: "gallery.html", label: "Gallery" },
            { href: "track-shipment.html", label: "Track Shipment" },
            { href: "contact.html", label: "Contact Us" }
        ];
    }

    const ul = nav.querySelector(".nav-links");
    if (ul) {
        let lis = activeNavLinks.map(link => {
            const isActive = currentPath === link.href ? ' class="active"' : '';
            return `      <li><a href="${link.href}"${isActive}>${link.label}</a></li>`;
        }).join("\n");

        if (isLoggedIn) {
            const isDashActive = currentPath === "dashboard.html" ? ' class="active"' : '';
            lis += `\n      <li class="mobile-signin"><a href="dashboard.html"${isDashActive}>Dashboard</a></li>`;
        } else {
            const isSigninActive = currentPath === "signin.html" ? ' class="active"' : '';
            lis += `\n      <li class="mobile-signin"><a href="signin.html"${isSigninActive}>Sign In</a></li>`;
        }

        ul.innerHTML = lis;

        // Re-attach hamburger click listener to newly generated links
        const navItems = ul.querySelectorAll("a");
        navItems.forEach(link => {
            link.addEventListener("click", () => {
                const hamburger = document.querySelector(".hamburger");
                if (hamburger) hamburger.classList.remove("active");
                ul.classList.remove("active");
                document.body.classList.remove("menu-open");
            });
        });
    }

    const navRight = nav.querySelector(".nav-right");
    if (navRight) {
        if (isLoggedIn) {
            navRight.innerHTML = `<a href="dashboard.html" class="signin-btn nav-signin-btn">Dashboard</a>`;
        } else {
            navRight.innerHTML = `<a href="signin.html" class="signin-btn nav-signin-btn">Sign In</a>`;
        }
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", renderDynamicNavbar);
} else {
    renderDynamicNavbar();
}









// Newsletter Subscription
const newsletterForms = document.querySelectorAll(".newsletter-form");
newsletterForms.forEach(form => {
    form.addEventListener("submit", async function(event) {
        event.preventDefault();

        const emailInput = form.querySelector('input[type="email"]');
        const email = emailInput.value.trim();

        if (!email) return;

        try {
            const response = await fetch(`${CONFIG.API_URL}/newsletter/subscribe`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email })
            });

            if (response.ok) {
                const data = await response.json();
                showToast(data.message, "success");
                form.reset();
            } else {
                const errData = await response.json();
                showToast(errData.message || "Failed to subscribe to newsletter.", "error");
            }
        } catch (error) {
            console.error("Newsletter error", error);
            showToast("An error occurred while subscribing.", "error");
        }
    });
});