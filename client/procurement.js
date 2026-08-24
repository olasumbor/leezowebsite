// ==========================================
// PROCUREMENT FORM
// ==========================================

const procurementForm =
    document.getElementById("procurementForm");


if (procurementForm) {

    procurementForm.addEventListener("submit", async function (event) {

        event.preventDefault();


        // ======================================
        // GET FORM VALUES
        // ======================================

        const name =
            document.getElementById("name").value.trim();

        const email =
            document.getElementById("email").value.trim();

        const phone =
            document.getElementById("phone").value.trim();

        const details =
            document.getElementById("details").value.trim();


        try {
            const token = localStorage.getItem("auth_token");
            if (!token) {
                showToast("You must be logged in to request a procurement.", "warning");
                setTimeout(() => {
                    window.location.href = "signin.html";
                }, 1000);
                return;
            }

            const response = await fetch(`${CONFIG.API_URL}/procurements`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${token}`
                },
                body: JSON.stringify({ 
                    name, 
                    email, 
                    phone,
                    details
                })
            });

            const data = await response.json();

            if (response.ok) {
                showToast(`Your procurement request has been submitted successfully! Procurement ID: ${data.procurement.procurement_id}`, "success");
                
                setTimeout(() => {
                    window.location.href = "procurement-history.html";
                }, 1200);
            } else if (response.status === 401) {
                localStorage.removeItem("loggedIn");
                localStorage.removeItem("userEmail");
                localStorage.removeItem("auth_token");
                showToast("Your session has expired or you are not logged in. Redirecting to login...", "warning");
                setTimeout(() => {
                    window.location.href = "signin.html";
                }, 1000);
            } else {
                showToast(data.message || "Failed to submit procurement request.", "error");
            }
        } catch (error) {
            console.error("Procurement submission error", error);
            showToast("An error occurred during submission.", "error");
        }

    });

}