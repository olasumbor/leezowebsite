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