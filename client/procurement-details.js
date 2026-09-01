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
// ========================================
// DOWNLOAD INVOICE AS PDF
// ========================================

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
                await downloadInvoiceAsPdf(htmlContent, `Procurement-Invoice-${procurementId}.pdf`);
            } else {
                let msg = "Failed to generate procurement invoice.";
                try {
                    const err = await response.json();
                    if (err.message) msg = err.message;
                } catch(e) {}
                showToast(msg, "warning");
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