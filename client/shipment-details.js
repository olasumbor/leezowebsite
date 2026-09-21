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
                shipmentType: data.shipment_type || "",
                weight: data.weight ? `${data.weight}` : "—",
                packages: data.packages || 1,
                shippedDate: data.shipped_date ? new Date(data.shipped_date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : (data.created_at ? new Date(data.created_at).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—"),
                deliveredDate: data.delivered_date ? new Date(data.delivered_date).toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" }) : "—",
                recipient: data.recipient_name || data.recipient || "—",
                recipientLocation: data.recipient_location || data.destination || "—",
                shippingCost: data.shipping_cost ? (isNaN(data.shipping_cost) ? data.shipping_cost : `₦${parseFloat(data.shipping_cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "—",
                status: data.status,
                canEdit: data.can_edit === true,
                invoiceGenerated: data.invoice_generated === true || data.invoice_generated === 1 || data.invoice_generated === "1",
                items: data.items || []
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

    // Only allow editing while the shipment has not been priced/invoiced by admin
    const editButton = document.getElementById("editShipment");
    if (editButton) {
        if (shipment.canEdit) {
            editButton.style.display = "";
            editButton.addEventListener("click", () => {
                window.location.href = `create-shipment.html?edit=${encodeURIComponent(shipmentId)}`;
            });
        } else {
            editButton.style.display = "none";
        }
    }

    // Invoice can only be downloaded once admin has generated it.
    const invoiceBtn = document.getElementById("downloadInvoice");
    if (invoiceBtn) {
        if (shipment.invoiceGenerated) {
            invoiceBtn.disabled = false;
            invoiceBtn.style.opacity = "";
            invoiceBtn.style.cursor = "";
            invoiceBtn.title = "Download invoice";
        } else {
            invoiceBtn.disabled = true;
            invoiceBtn.style.opacity = "0.6";
            invoiceBtn.style.cursor = "not-allowed";
            invoiceBtn.title = "Invoice is not available yet. It becomes available once Leezofood prices and generates it.";
        }
    }

    document.getElementById("shipmentTitle").textContent = `SHIPMENT ${shipment.trackingNumber || shipmentId}`;
    document.getElementById("shipmentOrigin").textContent = shipment.origin;
    document.getElementById("shipmentDestination").textContent = shipment.destination;
    document.getElementById("trackingNumber").textContent = shipment.trackingNumber;
    document.getElementById("shipmentService").textContent = shipment.service + (shipment.shipmentType ? ` (${shipment.shipmentType.toUpperCase()})` : "");
    document.getElementById("shipmentWeight").textContent = shipment.weight;
    document.getElementById("shipmentPackages").textContent = shipment.packages;
    document.getElementById("shipmentDate").textContent = shipment.shippedDate;
    document.getElementById("deliveryDate").textContent = shipment.deliveredDate;
    document.getElementById("recipientName").textContent = shipment.recipient;
    document.getElementById("recipientLocation").textContent = shipment.recipientLocation;
    document.getElementById("shippingCost").textContent = shipment.shippingCost;

    renderShipmentItems();
}

// ========================================
// SHIPMENT ITEMS
// ========================================

function renderShipmentItems() {
    const container = document.getElementById("shipmentItemsBlock");
    const totalContainer = document.getElementById("shipmentItemsTotal");
    const totalValue = document.getElementById("shipmentItemsTotalValue");
    if (!container) return;

    const items = shipment.items || [];

    if (!items || items.length === 0) {
        container.innerHTML = `<p style="color: #6b7280;">No item details available for this shipment.</p>`;
        if (totalContainer) totalContainer.style.display = 'none';
        return;
    }

    let totalCost = 0;

    const rows = items.map(item => {
        const cost = parseFloat(item.cost) || 0;
        totalCost += cost;
        return `
            <tr>
                <td>${escapeHtml(item.name || '—')}</td>
                <td>${item.quantity || '—'}</td>
                <td>${item.weight ? `${item.weight}` : '—'}</td>
                <td style="text-align: right;">${formatCurrency(item.rate)}</td>
                <td style="text-align: right;">${formatCurrency(item.cost)}</td>
            </tr>
        `;
    }).join("");

    container.innerHTML = `
        <table class="dash-shipment-items-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity</th>
                    <th>Weight (kg)</th>
                    <th style="text-align: right;">Rate (₦)</th>
                    <th style="text-align: right;">Cost (₦)</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    `;

    // Display the total
    if (totalContainer && totalValue) {
        totalValue.textContent = formatCurrency(totalCost);
        totalContainer.style.display = 'block';
    }
}

function formatCurrency(amount) {
    const value = Number.isFinite(parseFloat(amount)) ? parseFloat(amount) : 0;
    return '₦' + value.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
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

const downloadInvoiceButton = document.getElementById("downloadInvoice") || document.getElementById("downloadShipmentInvoice");

if (downloadInvoiceButton) {
    downloadInvoiceButton.addEventListener("click", function () {
        if (!shipment || !shipment.invoiceGenerated) {
            showToast("Invoice has not been generated by admin yet. It becomes available once Leezofood prices and generates it.", "warning");
            return;
        }

        // Download PDF directly from backend
        window.location.href = `${CONFIG.API_URL}/shipments/${shipmentId}/invoice`;
    });
}