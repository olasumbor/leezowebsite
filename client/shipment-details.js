// ========================================
// GET SHIPMENT ID FROM URL
// ========================================

const urlParams = new URLSearchParams(window.location.search);
const shipmentId = urlParams.get("id");

let shipment = null;

// ========================================
// FETCH SHIPMENT DETAILS
// ========================================

async function fetchShipmentDetails() {
    if (!shipmentId) {
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

        const response = await fetch(`${CONFIG.API_URL}/shipments/${shipmentId}`, {
            method: 'GET',
            credentials: 'include',
            headers: headers
        });

        if (response.ok) {
            const data = await response.json();
            
            shipment = {
                origin: data.origin || "Lagos, Nigeria",
                destination: data.destination || "—",
                trackingNumber: data.tracking_id || data.tracking_number || shipmentId,
                service: data.service || "Standard Shipping",
                weight: data.weight ? `${data.weight} kg` : "—",
                packages: data.packages || 1,
                shippedDate: data.shipped_date ? new Date(data.shipped_date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : (data.created_at ? new Date(data.created_at).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—"),
                deliveredDate: data.delivered_date ? new Date(data.delivered_date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—",
                recipient: data.recipient_name || data.recipient || "—",
                recipientLocation: data.recipient_location || data.destination || "—",
                shippingCost: data.shipping_cost ? (isNaN(data.shipping_cost) ? data.shipping_cost : `₦${parseFloat(data.shipping_cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "—",
                status: data.status
            };

            showDetails();
        } else if (response.status === 401) {
            window.location.href = 'signin.html';
        } else {
            showNotFound();
        }
    } catch (error) {
        console.error('Failed to fetch shipment details:', error);
        showNotFound();
    }
}

// ========================================
// DISPLAY SHIPMENT
// ========================================

function showDetails() {
    if (!shipment) return;

    document.getElementById("shipmentTitle").textContent = `SHIPMENT ${shipment.trackingNumber || shipmentId}`;
    document.getElementById("shipmentOrigin").textContent = shipment.origin;
    document.getElementById("shipmentDestination").textContent = shipment.destination;
    document.getElementById("trackingNumber").textContent = shipment.trackingNumber;
    document.getElementById("shipmentService").textContent = shipment.service;
    document.getElementById("shipmentWeight").textContent = shipment.weight;
    document.getElementById("shipmentPackages").textContent = shipment.packages;
    document.getElementById("shipmentDate").textContent = shipment.shippedDate;
    document.getElementById("deliveryDate").textContent = shipment.deliveredDate;
    document.getElementById("recipientName").textContent = shipment.recipient;
    document.getElementById("recipientLocation").textContent = shipment.recipientLocation;
    document.getElementById("shippingCost").textContent = shipment.shippingCost;
}

// ========================================
// SHIPMENT NOT FOUND
// ========================================

function showNotFound() {
    document.querySelector(".shipment-details-card").innerHTML = `
        <h1>Shipment Not Found</h1>
        <p>
            We could not find the shipment you are looking for.
        </p>
        <button onclick="window.location.href='shipment-history.html'">
            Back to Shipment History
        </button>
    `;
}

// Fetch immediately
fetchShipmentDetails();


// ========================================
// BACK BUTTON
// ========================================

document.getElementById("backButton").addEventListener(
    "click",
    function () {

        window.location.href = "shipment-history.html";

    }
);


// ========================================
// TRACK SHIPMENT
// ========================================

document.getElementById("trackShipment").addEventListener(
    "click",
    function () {

        window.location.href =
            `track-shipment.html?id=${shipmentId}`;

    }
);


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

const downloadShipmentInvoiceButton = document.getElementById("downloadInvoice") || document.getElementById("downloadReceipt");

if (downloadShipmentInvoiceButton) {
    downloadShipmentInvoiceButton.addEventListener("click", async function () {

        if (!shipmentId) {
            showToast("Shipment ID not found.", "warning");
            return;
        }

        if (typeof setButtonLoading === 'function') {
            setButtonLoading(downloadShipmentInvoiceButton, true, "Generating Invoice...");
        }

        try {
            const token = localStorage.getItem('auth_token');
            const headers = {};
            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            const invoiceUrl = `${CONFIG.API_URL}/shipments/${shipmentId}/invoice`;
            const response = await fetch(invoiceUrl, {
                method: 'GET',
                credentials: 'include',
                headers: headers
            });

            if (response.ok) {
                const htmlContent = await response.text();
                await downloadInvoiceAsPdf(htmlContent, `Shipment-Invoice-${shipmentId}.pdf`);
            } else {
                let msg = "Failed to generate shipment invoice.";
                try {
                    const err = await response.json();
                    if (err.message) msg = err.message;
                } catch(e) {}
                showToast(msg, "warning");
            }
        } catch (error) {
            console.error("Failed to download shipment invoice:", error);
            showToast("An error occurred while generating shipment invoice.", "error");
        } finally {
            if (typeof setButtonLoading === 'function') {
                setButtonLoading(downloadShipmentInvoiceButton, false);
            }
        }

    });
}