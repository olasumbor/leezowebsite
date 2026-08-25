// ==========================================
// FROZEN CARGO FORM HANDLER
// ==========================================

const frozenCargoForm = document.getElementById("frozenCargoForm");

if (frozenCargoForm) {
    frozenCargoForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const cargo_description = document.getElementById("cargo_description").value.trim();
        const temperature_requirement = document.getElementById("temperature_requirement").value;
        const weight = document.getElementById("weight").value ? parseFloat(document.getElementById("weight").value) : null;
        const origin = document.getElementById("origin").value.trim();
        const destination = document.getElementById("destination").value.trim();
        const departure_date = document.getElementById("departure_date").value || null;
        const notes = document.getElementById("notes").value.trim();

        const submitBtn = frozenCargoForm.querySelector("button[type='submit']") || frozenCargoForm.querySelector("button");
        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Submitting Request...");
        }

        try {
            const token = localStorage.getItem("auth_token");
            const headers = {
                "Accept": "application/json",
                "Content-Type": "application/json"
            };

            if (token) {
                headers["Authorization"] = `Bearer ${token}`;
            }

            const response = await fetch(`${CONFIG.API_URL}/frozen-cargos`, {
                method: "POST",
                headers: headers,
                body: JSON.stringify({
                    name,
                    email,
                    phone,
                    cargo_description,
                    temperature_requirement,
                    weight,
                    origin,
                    destination,
                    departure_date,
                    notes
                })
            });

            const data = await response.json();

            if (response.ok) {
                const reqId = data.frozen_cargo?.request_id || "";
                showToast(`Frozen cargo request submitted successfully! Request ID: ${reqId}`, "success");
                frozenCargoForm.reset();
            } else {
                showToast(data.message || "Failed to submit frozen cargo request.", "error");
            }
        } catch (error) {
            console.error("Frozen cargo submission error", error);
            showToast("An error occurred during submission. Please try again.", "error");
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
}
