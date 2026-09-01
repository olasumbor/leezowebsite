const quoteForm = document.getElementById("quoteForm");
const quoteSuccess = document.getElementById("quoteSuccess");
const calculatedCostEl = document.getElementById("calculatedCost");
const chargeableWeightInfoEl = document.getElementById("chargeableWeightInfo");

// Auto-fill form details if user is logged in
async function prefillLoggedInUser() {
    const token = localStorage.getItem("auth_token");
    if (!token || typeof CONFIG === "undefined") return;

    try {
        const response = await fetch(`${CONFIG.API_URL}/user`, {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Authorization": `Bearer ${token}`
            }
        });

        if (response.ok) {
            const user = await response.json();
            const quoteName = document.getElementById("quoteName");
            const quoteEmail = document.getElementById("quoteEmail");
            const quotePhone = document.getElementById("quotePhone");

            if (quoteName && user.name && !quoteName.value) {
                quoteName.value = user.name;
            }
            if (quoteEmail && user.email && !quoteEmail.value) {
                quoteEmail.value = user.email;
            }
            if (quotePhone && user.phone && !quotePhone.value) {
                quotePhone.value = user.phone;
            }
        }
    } catch (error) {
        console.error("Error auto-filling user details:", error);
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", prefillLoggedInUser);
} else {
    prefillLoggedInUser();
}

if (quoteForm) {
    quoteForm.addEventListener("submit", async function (event) {
        event.preventDefault();
        const submitBtn = quoteForm.querySelector("button[type='submit']") || quoteForm.querySelector("button");

        const formData = {
            name: document.getElementById("quoteName").value.trim(),
            email: document.getElementById("quoteEmail").value.trim(),
            phone: document.getElementById("quotePhone").value.trim(),
            shippingType: document.getElementById("shippingType").value.trim(),
            originCountry: document.getElementById("originCountry").value.trim(),
            destinationCountry: document.getElementById("destinationCountry").value.trim(),
            shippingWeight: document.getElementById("shippingWeight").value,
            shippingWidth: document.getElementById("shippingWidth").value,
            shippingLength: document.getElementById("shippingLength").value,
            shippingDetails: document.getElementById("shippingDetails").value.trim(),
        };

        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Calculating Quote...");
        }

        try {
            const headers = {
                "Accept": "application/json",
                "Content-Type": "application/json"
            };

            const token = localStorage.getItem("auth_token");
            if (token) {
                headers["Authorization"] = `Bearer ${token}`;
            }

            const response = await fetch(`${CONFIG.API_URL}/quotes/calculate`, {
                method: "POST",
                headers: headers,
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                const data = await response.json();
                const trackingId = data.tracking_id || data.request_id;
                
                if (calculatedCostEl) {
                    if (token) {
                        calculatedCostEl.innerHTML = `Tracking ID: <strong>${trackingId}</strong> (Status: Pending Admin Review) &bull; <a href="shipment-history.html" style="color: #0284c7; text-decoration: underline;">View My Shipments</a>`;
                    } else {
                        calculatedCostEl.innerHTML = `Tracking ID: <strong>${trackingId}</strong> (Status: Pending Admin Review) &bull; <a href="track-shipment.html?tracking_id=${trackingId}" style="color: #0284c7; text-decoration: underline;">Track Shipment</a>`;
                    }
                }
                if (chargeableWeightInfoEl) {
                    chargeableWeightInfoEl.textContent = "Our pricing team will review your shipping details and send your custom quote rate directly to your email.";
                }
                
                quoteSuccess.style.display = "block";
                
                // Show success message
                showToast(`${data.message} Tracking ID: ${trackingId}`, "success");
                
                quoteSuccess.scrollIntoView({
                    behavior: "smooth",
                    block: "center"
                });
            } else {
                const errData = await response.json();
                showToast(errData.message || "Failed to submit quote request. Please check your inputs.", "error");
            }
        } catch (error) {
            console.error("Calculation error", error);
            showToast("An error occurred while calculating the quote.", "error");
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
}