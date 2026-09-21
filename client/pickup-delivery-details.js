// ========================================
// PICK UP & DELIVERY DETAILS HANDLER
// ========================================

const urlParams = new URLSearchParams(window.location.search);
const pickupId = urlParams.get("id");

async function loadPickupDetails() {
    if (!pickupId) {
        showNotFound();
        return;
    }

    let pickup = null;

    // Try fetching from API first
    const token = localStorage.getItem("auth_token");
    if (token && typeof CONFIG !== "undefined") {
        try {
            const response = await fetch(`${CONFIG.API_URL}/pickup-deliveries`, {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (response.ok) {
                const requests = await response.json();
                const matched = requests.find(r => (r.request_id && r.request_id.toUpperCase() === pickupId.toUpperCase()) || String(r.id) === pickupId);
                if (matched) {
                    pickup = {
                        status: formatStatus(matched.status),
                        name: matched.name || "N/A",
                        phone: matched.phone || "N/A",
                        deliveryPhone: matched.delivery_phone || "N/A",
                        pickupAddress: matched.pickup_address || "N/A",
                        deliveryAddress: matched.delivery_address || "N/A",
                        requestDate: matched.created_at ? new Date(matched.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        deliveredDate: matched.status === "completed" || matched.status === "delivered" ? "Delivered" : "—",
                        cost: matched.cost ? (isNaN(matched.cost) ? matched.cost : `₦${parseFloat(matched.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "Pending Quote"
                    };
                }
            }
        } catch (err) {
            console.error("API error fetching pickup details:", err);
        }
    }

    if (!pickup) {
        const savedPickups = JSON.parse(localStorage.getItem("pickupRequests")) || [];
        const matched = savedPickups.find(p => (p.id || p.request_id) === pickupId);
        if (matched) {
            pickup = {
                status: formatStatus(matched.status || "Pending"),
                name: matched.name || "N/A",
                phone: matched.phone || "N/A",
                deliveryPhone: matched.delivery_phone || "N/A",
                pickupAddress: matched.pickup_address || "N/A",
                deliveryAddress: matched.delivery_address || "N/A",
                requestDate: matched.created_at ? new Date(matched.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                deliveredDate: matched.status === "completed" || matched.status === "delivered" ? "Delivered" : "—",
                cost: matched.cost ? (isNaN(matched.cost) ? matched.cost : `₦${parseFloat(matched.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "Pending Quote"
            };
        }
    }

    if (pickup) {
        renderDetails(pickup);
    } else {
        showNotFound();
    }
}

function formatStatus(status) {
    if (!status) return "Pending";
    const s = status.toLowerCase();
    if (s === "completed" || s === "delivered") return "Completed";
    if (s === "in_transit" || s === "in progress") return "In Progress";
    if (s === "cancelled") return "Cancelled";
    return "Pending";
}

function renderDetails(pickup) {
    if (document.getElementById("pickupId")) document.getElementById("pickupId").textContent = pickupId;
    if (document.getElementById("detailPickupId")) document.getElementById("detailPickupId").textContent = pickupId;
    if (document.getElementById("pickupName")) document.getElementById("pickupName").textContent = pickup.name;
    if (document.getElementById("pickupPhone")) document.getElementById("pickupPhone").textContent = pickup.phone;
    if (document.getElementById("deliveryPhone")) document.getElementById("deliveryPhone").textContent = pickup.deliveryPhone;
    if (document.getElementById("pickupAddress")) document.getElementById("pickupAddress").textContent = pickup.pickupAddress;
    if (document.getElementById("deliveryAddress")) document.getElementById("deliveryAddress").textContent = pickup.deliveryAddress;
    if (document.getElementById("pickupRequestDate")) document.getElementById("pickupRequestDate").textContent = pickup.requestDate;
    if (document.getElementById("pickupDeliveredDate")) document.getElementById("pickupDeliveredDate").textContent = pickup.deliveredDate;
    if (document.getElementById("pickupStatus")) document.getElementById("pickupStatus").textContent = pickup.status;
    if (document.getElementById("pickupCost")) document.getElementById("pickupCost").textContent = pickup.cost;

    updatePickupStatus(pickup.status);
}

function showNotFound() {
    if (document.getElementById("pickupId")) document.getElementById("pickupId").textContent = "Request Not Found";
    if (document.getElementById("detailPickupId")) document.getElementById("detailPickupId").textContent = "No request record found";
}

function updatePickupStatus(status) {
    const statuses = ["statusRequested", "statusAssigned", "statusPickup", "statusTransit", "statusDelivered"];
    statuses.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove("status-complete", "status-current");
        }
    });

    if (status === "Completed") {
        statuses.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("status-complete");
        });
    } else if (status === "In Progress") {
        ["statusRequested", "statusAssigned", "statusPickup"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("status-complete");
        });
        const current = document.getElementById("statusTransit");
        if (current) current.classList.add("status-current");
    } else if (status === "Pending") {
        const requested = document.getElementById("statusRequested");
        if (requested) requested.classList.add("status-complete");
        const current = document.getElementById("statusAssigned");
        if (current) current.classList.add("status-current");
    }
}

// Download Invoice Handler

const downloadInvoiceBtn = document.getElementById("downloadPickupInvoice") || document.getElementById("downloadInvoice");
if (downloadInvoiceBtn) {
    downloadInvoiceBtn.addEventListener("click", function () {
        if (!pickupId) {
            if (typeof showToast !== "undefined") showToast("Request ID not found.", "warning");
            return;
        }

        // Download PDF directly from backend
        window.location.href = `${CONFIG.API_URL}/pickup-deliveries/${pickupId}/invoice`;
    });
}

// Track delivery button

const trackBtn = document.getElementById("trackPickup");
if (trackBtn) {
    trackBtn.addEventListener("click", function () {
        window.location.href = `pickup-delivery-history.html?id=${pickupId}`;
    });
}

loadPickupDetails();