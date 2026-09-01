// ==========================================
// PICKUP & DELIVERY HANDLER & TABLE
// ==========================================

const pickupDeliveryForm = document.getElementById("pickupDeliveryForm");
const pickupRequestsTableBody = document.getElementById("pickupRequestsTableBody");

// Fetch user's pickup requests
async function fetchUserPickupRequests() {
    if (!pickupRequestsTableBody) return;
    
    try {
        const token = localStorage.getItem("auth_token");
        if (!token) return;

        const response = await fetch(`${CONFIG.API_URL}/pickup-deliveries`, {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Authorization": `Bearer ${token}`
            }
        });

        if (response.ok) {
            const requests = await response.json();
            renderPickupRequests(requests);
        } else {
            pickupRequestsTableBody.innerHTML = `<tr><td colspan="5" style="padding: 20px; text-align: center; color: #667085;">No pickup requests found.</td></tr>`;
        }
    } catch (error) {
        console.error("Error fetching pickup requests:", error);
        pickupRequestsTableBody.innerHTML = `<tr><td colspan="5" style="padding: 20px; text-align: center; color: #d92d20;">Failed to load requests.</td></tr>`;
    }
}

function renderPickupRequests(requests) {
    if (!requests || requests.length === 0) {
        pickupRequestsTableBody.innerHTML = `<tr><td colspan="5" style="padding: 20px; text-align: center; color: #667085;">No pickup & delivery requests submitted yet.</td></tr>`;
        return;
    }

    pickupRequestsTableBody.innerHTML = requests.map(r => {
        const formattedDate = r.created_at ? new Date(r.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "-";
        
        let statusBadge = `<span style="background: #fef0c7; color: #dc6803; padding: 4px 10px; border-radius: 12px; font-weight: 600; text-transform: capitalize;">${r.status || 'Pending'}</span>`;
        if (r.status === 'delivered' || r.status === 'completed') {
            statusBadge = `<span style="background: #ecfdf3; color: #12b76a; padding: 4px 10px; border-radius: 12px; font-weight: 600; text-transform: capitalize;">${r.status}</span>`;
        } else if (r.status === 'in_transit') {
            statusBadge = `<span style="background: #e0f2fe; color: #0284c7; padding: 4px 10px; border-radius: 12px; font-weight: 600; text-transform: capitalize;">In Transit</span>`;
        }

        return `
            <tr style="border-bottom: 1px solid #eaecf0;">
                <td style="padding: 12px 16px;"><strong>${r.request_id}</strong></td>
                <td style="padding: 12px 16px;">${escapeHtml(r.pickup_address || '')}</td>
                <td style="padding: 12px 16px;">${escapeHtml(r.delivery_address || '')}</td>
                <td style="padding: 12px 16px;">${formattedDate}</td>
                <td style="padding: 12px 16px;">${statusBadge}</td>
            </tr>
        `;
    }).join("");
}

function escapeHtml(str) {
    return str.replace(/[&<>'"]/g, 
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

if (pickupDeliveryForm) {
    pickupDeliveryForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();
        const delivery_phone = document.getElementById("delivery_phone") ? document.getElementById("delivery_phone").value.trim() : "";
        const pickup_address = document.getElementById("pickup_address").value.trim();
        const delivery_address = document.getElementById("delivery_address").value.trim();

        const submitBtn = pickupDeliveryForm.querySelector("button[type='submit']") || pickupDeliveryForm.querySelector("button");
        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Booking Pickup...");
        }

        try {
            const token = localStorage.getItem("auth_token");
            if (!token) {
                showToast("You must be logged in to submit a pickup request.", "warning");
                window.location.href = "signin.html";
                return;
            }

            const response = await fetch(`${CONFIG.API_URL}/pickup-deliveries`, {
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
                    delivery_phone,
                    pickup_address,
                    delivery_address
                })
            });

            const data = await response.json();

            if (response.ok) {
                const reqId = data.pickup_delivery?.request_id || "";
                showToast(`Pickup & delivery request submitted successfully! Request ID: ${reqId}`, "success");
                pickupDeliveryForm.reset();
                fetchUserPickupRequests();
            } else {
                showToast(data.message || "Failed to submit request.", "error");
            }
        } catch (error) {
            console.error("Pickup & Delivery submission error", error);
            showToast("An error occurred during submission. Please try again.", "error");
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
}

// Initial fetch on page load
fetchUserPickupRequests();
