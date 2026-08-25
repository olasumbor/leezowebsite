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
                
                calculatedCostEl.textContent = "Pending Admin Review";
                chargeableWeightInfoEl.textContent = `Chargeable Weight: ${data.chargeable_weight} kg (Volumetric: ${data.volumetric_weight} kg, Actual: ${data.actual_weight} kg)`;
                
                quoteSuccess.style.display = "block";
                
                // Show success message
                showToast(`${data.message} Request ID: ${data.request_id}`, "success");
                
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