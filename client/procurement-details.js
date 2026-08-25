// ========================================
// GET PROCUREMENT ID FROM URL
// ========================================

const urlParams = new URLSearchParams(window.location.search);
const procurementId = urlParams.get("id");

let procurement = null;

// ========================================
// FETCH PROCUREMENT DETAILS
// ========================================

async function fetchProcurementDetails() {
    if (!procurementId) {
        showNotFound();
        return;
    }

    try {
        const token = localStorage.getItem("auth_token");
        const headers = {
            'Accept': 'application/json'
        };
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const response = await fetch(`${CONFIG.API_URL}/procurements/${procurementId}`, {
            method: 'GET',
            credentials: 'include',
            headers: headers
        });

        if (response.ok) {
            const data = await response.json();
            
            // Map API response to frontend format
            procurement = {
                id: data.procurement_id || data.id || procurementId,
                status: data.status,
                product: data.details || "—",
                category: data.category || "Procurement Request",
                quantity: data.quantity || "—",
                supplier: data.supplier || "—",
                location: data.location || "—",
                requestDate: data.created_at ? new Date(data.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                expectedDate: data.expected_date ? new Date(data.expected_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                deliveredDate: data.delivered_date ? new Date(data.delivered_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                recipient: data.name || "—",
                recipientLocation: data.recipient_location || "—",
                cost: data.cost ? (isNaN(data.cost) ? data.cost : `₦${parseFloat(data.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "—"
            };

            showDetails();
        } else if (response.status === 401) {
            window.location.href = 'signin.html';
        } else {
            showNotFound();
        }
    } catch (error) {
        console.error('Failed to fetch procurement details:', error);
        showNotFound();
    }
}

// ========================================
// SHOW PROCUREMENT DETAILS
// ========================================

function showDetails() {
    if (!procurement) return;

    const displayId = procurement.id || procurementId;
    document.getElementById("procurementId").textContent = displayId;
    document.getElementById("detailProcurementId").textContent = displayId;

    document.getElementById("procurementProduct").textContent = procurement.product;
    document.getElementById("procurementCategory").textContent = procurement.category;
    document.getElementById("procurementQuantity").textContent = procurement.quantity;
    document.getElementById("procurementSupplier").textContent = procurement.supplier;
    document.getElementById("procurementLocation").textContent = procurement.location;

    document.getElementById("procurementRequestDate").textContent = procurement.requestDate;
    document.getElementById("procurementExpectedDate").textContent = procurement.expectedDate;
    document.getElementById("procurementDeliveredDate").textContent = procurement.deliveredDate;

    document.getElementById("procurementRecipient").textContent = procurement.recipient;
    document.getElementById("procurementRecipientLocation").textContent = procurement.recipientLocation;
    document.getElementById("procurementCost").textContent = procurement.cost;

    updateProcurementStatus(procurement.status);
}

function showNotFound() {
    const idEl = document.getElementById("procurementId");
    if (idEl) idEl.textContent = "Procurement Not Found";

    const detailIdEl = document.getElementById("detailProcurementId");
    if (detailIdEl) detailIdEl.textContent = "No procurement record found";
}

// Fetch immediately on load
fetchProcurementDetails();


// ========================================
// PROCUREMENT STATUS
// ========================================

function updateProcurementStatus(status) {

    const statuses = [
        "statusSubmitted",
        "statusApproved",
        "statusSupplier",
        "statusProcured",
        "statusDelivered"
    ];

    const normalizedStatus = (status || "").toLowerCase().trim();

    // Reset all statuses
    statuses.forEach(function(id) {

        const element = document.getElementById(id);

        if (element) {
            element.classList.remove("status-complete");
            element.classList.remove("status-current");
        }

    });


    // Completed
    if (normalizedStatus === "completed" || normalizedStatus === "delivered") {

        statuses.forEach(function(id) {

            const element = document.getElementById(id);

            if (element) {
                element.classList.add("status-complete");
            }

        });

    }


    // In Progress
    else if (normalizedStatus === "in progress" || normalizedStatus === "in_progress" || normalizedStatus === "approved" || normalizedStatus === "procured") {

        const completedStatuses = [
            "statusSubmitted",
            "statusApproved",
            "statusSupplier",
            "statusProcured"
        ];

        completedStatuses.forEach(function(id) {

            const element = document.getElementById(id);

            if (element) {
                element.classList.add("status-complete");
            }

        });

        const current = document.getElementById("statusProcured");

        if (current) {
            current.classList.add("status-current");
        }

    }


    // Pending
    else if (normalizedStatus === "pending") {

        const submitted = document.getElementById("statusSubmitted");

        if (submitted) {
            submitted.classList.add("status-complete");
        }

        const current = document.getElementById("statusApproved");

        if (current) {
            current.classList.add("status-current");
        }

    }


    // Cancelled
    else if (normalizedStatus === "cancelled") {

        const submitted = document.getElementById("statusSubmitted");

        if (submitted) {
            submitted.classList.add("status-complete");
        }

        const cancelled = document.getElementById("statusApproved");

        if (cancelled) {
            cancelled.classList.add("status-current");
        }

    }

}


// ========================================
// DOWNLOAD INVOICE
// ========================================

const downloadInvoiceButton = document.getElementById("downloadInvoice") || document.getElementById("downloadReceipt");

if (downloadInvoiceButton) {

    downloadInvoiceButton.addEventListener("click", async function() {

        if (!procurementId) {
            showToast("Procurement ID not found.", "warning");
            return;
        }

        if (typeof setButtonLoading === 'function') {
            setButtonLoading(downloadInvoiceButton, true, "Generating Invoice...");
        }

        try {
            const token = localStorage.getItem('auth_token');
            const headers = {};
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const invoiceUrl = `${CONFIG.API_URL}/procurements/${procurementId}/invoice`;
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
                    link.download = `Procurement-Invoice-${procurementId}.html`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            } else {
                showToast("Failed to generate procurement invoice from backend.", "error");
            }
        } catch (error) {
            console.error("Failed to download procurement invoice:", error);
            showToast("An error occurred while generating invoice.", "error");
        } finally {
            if (typeof setButtonLoading === 'function') {
                setButtonLoading(downloadInvoiceButton, false);
            }
        }

    });

}


// ========================================
// TRACK PROCUREMENT
// ========================================

const trackProcurementButton =
    document.getElementById("trackProcurement");

if (trackProcurementButton) {

    trackProcurementButton.addEventListener("click", function() {

        window.location.href =
            `procurement-history.html?id=${procurementId}`;

    });

}