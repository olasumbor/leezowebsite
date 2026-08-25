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
// Dynamic User Dashboard Navbar & Mobile Navigation Drawer
function renderDashboardNavbar() {
    const dashHeader = document.querySelector(".dashboard-header");
    if (!dashHeader) return;

    let rawPath = window.location.pathname.toLowerCase().split('?')[0].split('#')[0].replace(/\/+$/, '') || 'dashboard.html';
    let currentPath = rawPath.split('/').pop() || 'dashboard.html';
    if (currentPath && !currentPath.includes('.')) {
        currentPath += '.html';
    }

    const dashLinks = [
        { href: "dashboard.html", label: "Dashboard", match: ["dashboard.html"] },
        { href: "shipment-history.html", label: "My Shipments", match: ["shipment-history.html", "shipment-details.html"] },
        { href: "procurement-history.html", label: "Procurement", match: ["procurement-history.html", "procurement.html", "procurement-details.html"] },
        { href: "pickup-delivery-history.html", label: "Pickup & Delivery", match: ["pickup-delivery-history.html", "pickup-delivery.html", "pickup-delivery-details.html"] },
        { href: "frozen-cargo-history.html", label: "Frozen Cargo", match: ["frozen-cargo-history.html", "frozen-cargo.html", "frozen-cargo-details.html"] },
        { href: "profile.html", label: "Profile", match: ["profile.html"] }
    ];

    const navItemsHtml = dashLinks.map(link => {
        const isActive = link.match.includes(currentPath) ? ' class="active"' : '';
        return `      <li><a href="${link.href}"${isActive}>${link.label}</a></li>`;
    }).join("\n");

    dashHeader.innerHTML = `
    <div class="dashboard-logo">
      <a href="dashboard.html">
        <img src="images/logo-leezo.NG.svg" alt="Leezo Exports Logistics Logo">
      </a>
    </div>

    <ul class="dashboard-nav-links">
${navItemsHtml}
      <li class="dashboard-mobile-logout">
        <button type="button" class="logout-button mobile-logout-btn">Sign Out</button>
      </li>
    </ul>

    <div class="dashboard-right">
      <button type="button" id="logoutButton" class="logout-button">Sign Out</button>
      <div class="dashboard-hamburger" aria-label="Toggle Dashboard Menu">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
    `;

    const logoutBtn = dashHeader.querySelector("#logoutButton");
    const mobileLogoutBtn = dashHeader.querySelector(".mobile-logout-btn");

    const handleLogout = async () => {
        if (typeof setButtonLoading === 'function' && logoutBtn) {
            setButtonLoading(logoutBtn, true, "Logging out...");
        }
        try {
            const token = localStorage.getItem("auth_token");
            if (token && typeof CONFIG !== "undefined") {
                await fetch(`${CONFIG.API_URL}/logout`, {
                    method: "POST",
                    headers: {
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                        "Authorization": `Bearer ${token}`
                    }
                });
            }
        } catch (e) {
            console.error("Logout error", e);
        } finally {
            localStorage.removeItem("loggedIn");
            localStorage.removeItem("userEmail");
            localStorage.removeItem("auth_token");
            window.location.href = "signin.html";
        }
    };

    if (logoutBtn) logoutBtn.addEventListener("click", handleLogout);
    if (mobileLogoutBtn) mobileLogoutBtn.addEventListener("click", handleLogout);

    const dashHamburger = dashHeader.querySelector(".dashboard-hamburger");
    const dashNavLinks = dashHeader.querySelector(".dashboard-nav-links");

    if (dashHamburger && dashNavLinks) {
        dashHamburger.addEventListener("click", (e) => {
            e.stopPropagation();
            dashHamburger.classList.toggle("active");
            dashNavLinks.classList.toggle("active");
            document.body.classList.toggle("menu-open");
        });

        const links = dashNavLinks.querySelectorAll("a");
        links.forEach(l => {
            l.addEventListener("click", () => {
                dashHamburger.classList.remove("active");
                dashNavLinks.classList.remove("active");
                document.body.classList.remove("menu-open");
            });
        });
    }
}

function initNavbars() {
    renderDynamicNavbar();
    renderDashboardNavbar();
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initNavbars);
} else {
    initNavbars();
}









// Newsletter Subscription
const newsletterForms = document.querySelectorAll(".newsletter-form");
newsletterForms.forEach(form => {
    form.addEventListener("submit", async function(event) {
        event.preventDefault();

        const emailInput = form.querySelector('input[type="email"]');
        const submitBtn = form.querySelector('button[type="submit"]') || form.querySelector('button');
        const email = emailInput ? emailInput.value.trim() : "";

        if (!email) return;

        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, 'Subscribing...');
        }

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
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
});