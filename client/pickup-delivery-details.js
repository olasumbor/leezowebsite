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
                        pickupAddress: matched.pickup_address || "N/A",
                        deliveryAddress: matched.delivery_address || "N/A",
                        otherDetails: matched.notes || matched.item_description || "N/A",
                        requestDate: matched.created_at ? new Date(matched.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        expectedDate: matched.pickup_date ? new Date(matched.pickup_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        deliveredDate: matched.status === "completed" || matched.status === "delivered" ? "Delivered" : "—"
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
                pickupAddress: matched.pickup_address || "N/A",
                deliveryAddress: matched.delivery_address || "N/A",
                otherDetails: matched.notes || matched.item_description || "N/A",
                requestDate: matched.created_at ? new Date(matched.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                expectedDate: matched.pickup_date ? new Date(matched.pickup_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                deliveredDate: matched.status === "completed" || matched.status === "delivered" ? "Delivered" : "—"
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
    if (document.getElementById("pickupAddress")) document.getElementById("pickupAddress").textContent = pickup.pickupAddress;
    if (document.getElementById("deliveryAddress")) document.getElementById("deliveryAddress").textContent = pickup.deliveryAddress;
    if (document.getElementById("otherDetails")) document.getElementById("otherDetails").textContent = pickup.otherDetails;
    if (document.getElementById("pickupRequestDate")) document.getElementById("pickupRequestDate").textContent = pickup.requestDate;
    if (document.getElementById("pickupExpectedDate")) document.getElementById("pickupExpectedDate").textContent = pickup.expectedDate;
    if (document.getElementById("pickupDeliveredDate")) document.getElementById("pickupDeliveredDate").textContent = pickup.deliveredDate;
    if (document.getElementById("pickupStatus")) document.getElementById("pickupStatus").textContent = pickup.status;

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
const downloadInvoiceBtn = document.getElementById("downloadPickupInvoice");
if (downloadInvoiceBtn) {
    downloadInvoiceBtn.addEventListener("click", async function () {
        if (!pickupId) {
            if (typeof showToast !== "undefined") showToast("Request ID not found.", "warning");
            return;
        }

        if (typeof setButtonLoading === 'function') {
            setButtonLoading(downloadInvoiceBtn, true, "Generating Invoice...");
        }

        try {
            const token = localStorage.getItem('auth_token');
            const headers = {};
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const invoiceUrl = `${CONFIG.API_URL}/pickup-deliveries/${pickupId}/invoice`;
            const response = await fetch(invoiceUrl, {
                method: 'GET',
                credentials: 'include',
                headers: headers
            });

            if (response.ok) {
                const htmlContent = await response.text();
                const blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const win = window.open(url, '_blank');
                if (!win) {
                    const link = document.createElement("a");
                    link.href = url;
                    link.download = `Pickup-Delivery-Invoice-${pickupId}.html`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            } else {
                if (typeof showToast !== "undefined") showToast("Failed to generate invoice from backend.", "error");
            }
        } catch (error) {
            console.error("Failed to download invoice:", error);
            if (typeof showToast !== "undefined") showToast("An error occurred while generating invoice.", "error");
        } finally {
            if (typeof setButtonLoading === 'function') {
                setButtonLoading(downloadInvoiceBtn, false);
            }
        }
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