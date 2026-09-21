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

    // Parse the HTML content
    const parser = new DOMParser();
    const doc = parser.parseFromString(htmlContent, 'text/html');

    // Create a container div that properly holds the document
    const wrapper = document.createElement('div');
    wrapper.style.position = 'fixed';
    wrapper.style.left = '-9999px';
    wrapper.style.top = '0';
    wrapper.style.background = '#ffffff';
    wrapper.style.width = '800px';
    wrapper.style.height = '1200px';
    wrapper.style.overflow = 'hidden';
    wrapper.style.padding = '30px 45px';
    wrapper.style.boxSizing = 'border-box';
    document.body.appendChild(wrapper);

    // Extract and preserve styles from the original HTML
    const styleTags = doc.querySelectorAll('style');
    styleTags.forEach(style => {
        const clonedStyle = document.createElement('style');
        clonedStyle.textContent = style.textContent;
        wrapper.appendChild(clonedStyle);
    });

    // Extract body content (without the body tag itself)
    const bodyContent = doc.body ? doc.body.innerHTML : htmlContent;
    wrapper.innerHTML += bodyContent;

    // Force styles to apply by making wrapper visible briefly
    wrapper.style.visibility = 'visible';
    wrapper.style.display = 'block';

    // Wait for fonts and images to load
    await new Promise((resolve) => {
        setTimeout(resolve, 1000); // Give time for rendering
    });

    // The wrapper itself is the invoice element
    const invoiceElement = wrapper;

    const opt = {
        margin:       [0.2, 0.2, 0.2, 0.2],
        filename:     filename,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  {
            scale: 2,
            useCORS: true,
            logging: false,
            allowTaint: true,
            backgroundColor: '#ffffff',
            width: 800,
            height: 1200,
            windowWidth: 800
        },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };

    try {
        await window.html2pdf().set(opt).from(invoiceElement).save();
    } finally {
        if (wrapper.parentNode) {
            wrapper.parentNode.removeChild(wrapper);
        }
    }
}

const downloadInvoiceButton = document.getElementById("downloadInvoice") || document.getElementById("downloadProcurementInvoice");

if (downloadInvoiceButton) {

    downloadInvoiceButton.addEventListener("click", function() {

        if (!procurementId) {
            showToast("Procurement ID not found.", "warning");
            return;
        }

        // Download PDF directly from backend
        window.location.href = `${CONFIG.API_URL}/procurements/${procurementId}/invoice`;

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