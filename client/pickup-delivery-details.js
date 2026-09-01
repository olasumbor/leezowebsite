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
                deliveryPhone: matched.delivery_phone || "N/A",
                pickupAddress: matched.pickup_address || "N/A",
                deliveryAddress: matched.delivery_address || "N/A",
                requestDate: matched.created_at ? new Date(matched.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
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
    if (document.getElementById("deliveryPhone")) document.getElementById("deliveryPhone").textContent = pickup.deliveryPhone;
    if (document.getElementById("pickupAddress")) document.getElementById("pickupAddress").textContent = pickup.pickupAddress;
    if (document.getElementById("deliveryAddress")) document.getElementById("deliveryAddress").textContent = pickup.deliveryAddress;
    if (document.getElementById("pickupRequestDate")) document.getElementById("pickupRequestDate").textContent = pickup.requestDate;
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
async function downloadInvoiceAsPdf(htmlContent, filename) {
    if (!window.html2pdf) {
        await new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
            script.onload = resolve;
            script.onerror = () => reject(new Error('Failed to load html2pdf library.'));
            document.head.appendChild(script);
        });
    }

    const tempContainer = document.createElement('div');
    tempContainer.style.position = 'fixed';
    tempContainer.style.left = '-9999px';
    tempContainer.style.top = '0';
    tempContainer.style.width = '800px';
    tempContainer.style.background = '#ffffff';
    tempContainer.innerHTML = htmlContent;
    document.body.appendChild(tempContainer);

    const noPrintBar = tempContainer.querySelector('.no-print-bar');
    if (noPrintBar) {
        noPrintBar.remove();
    }

    const invoiceElement = tempContainer.querySelector('.invoice-card') || tempContainer.querySelector('.receipt-card') || tempContainer;

    const opt = {
        margin:       [0.2, 0.2, 0.2, 0.2],
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    try {
        await window.html2pdf().set(opt).from(invoiceElement).save();
    } finally {
        if (tempContainer.parentNode) {
            tempContainer.parentNode.removeChild(tempContainer);
        }
    }
}

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
                await downloadInvoiceAsPdf(htmlContent, `Pickup-Delivery-Invoice-${pickupId}.pdf`);
            } else {
                let msg = "Failed to generate invoice.";
                try {
                    const err = await response.json();
                    if (err.message) msg = err.message;
                } catch(e) {}
                if (typeof showToast !== "undefined") showToast(msg, "warning");
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