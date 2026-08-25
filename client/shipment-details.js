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
                origin: data.origin || "—",
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

    document.getElementById("shipmentTitle").textContent = `SHIPMENT ${shipmentId}`;
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
// DOWNLOAD INVOICE
// ========================================


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
                const blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const win = window.open(url, '_blank');
                if (!win) {
                    const link = document.createElement("a");
                    link.href = url;
                    link.download = `Shipment-Invoice-${shipmentId}.html`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            } else {
                showToast("Failed to generate shipment invoice from backend.", "error");
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