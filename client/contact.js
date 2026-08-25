const contactForm = document.getElementById("contactForm");

if (contactForm) {
    contactForm.addEventListener("submit", async function(event) {
        event.preventDefault();
        const submitBtn = contactForm.querySelector("button[type='submit']") || contactForm.querySelector("button");

        const formData = {
            name: document.getElementById("contactName").value.trim(),
            email: document.getElementById("contactEmail").value.trim(),
            phone: document.getElementById("contactPhone").value.trim(),
            subject: document.getElementById("contactSubject").value.trim(),
            message: document.getElementById("contactMessage").value.trim()
        };

        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Sending Message...");
        }

        try {
            const response = await fetch(`${CONFIG.API_URL}/contact`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                const data = await response.json();
                showToast(data.message, "success");
                contactForm.reset();
            } else {
                const errData = await response.json();
                showToast(errData.message || "Failed to send message. Please try again.", "error");
            }
        } catch (error) {
            console.error("Contact error", error);
            showToast("An error occurred while sending the message.", "error");
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
}
