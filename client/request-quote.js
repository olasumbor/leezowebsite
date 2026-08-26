const quoteForm = document.getElementById("quoteForm");
const quoteSuccess = document.getElementById("quoteSuccess");
const calculatedCostEl = document.getElementById("calculatedCost");
const chargeableWeightInfoEl = document.getElementById("chargeableWeightInfo");

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
            shippingHeight: document.getElementById("shippingHeight").value,
            shippingWidth: document.getElementById("shippingWidth").value,
            shippingLength: document.getElementById("shippingLength").value,
            shippingDetails: document.getElementById("shippingDetails").value.trim(),
        };

        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Calculating Quote...");
        }

        try {
            const response = await fetch(`${CONFIG.API_URL}/quotes/calculate`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(formData)
            });

            if (response.ok) {
                const data = await response.json();
                const trackingId = data.tracking_id || data.request_id;
                
                if (calculatedCostEl) {
                    calculatedCostEl.textContent = `Tracking ID: ${trackingId} (Status: Pending Admin Review)`;
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