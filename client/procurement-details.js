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
                supplier: data.supplier || "—",
                location: data.location || "—",
                requestDate: data.request_date ? new Date(data.request_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                expectedDate: data.expected_delivery ? new Date(data.expected_delivery).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                deliveryDate: data.delivery_date ? new Date(data.delivery_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                receiptDate: data.receipt_date ? new Date(data.receipt_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "—",
                recipient: data.name || "—",
                recipientLocation: data.recipient_location || "—",
                customerEmail: data.email || "—",
                customerPhone: data.phone || "—",
                items: data.items || [],
            invoiceGenerated: data.invoice_generated === true,
                totalCost: data.total_cost ? `₦${parseFloat(data.total_cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending Quote",
                totalShipmentFee: data.total_shipment_fee ? `₦${parseFloat(data.total_shipment_fee).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending Quote",
                totalTransportation: data.total_transportation ? `₦${parseFloat(data.total_transportation).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending Quote",
                grandTotal: data.grand_total ? `₦${parseFloat(data.grand_total).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending Quote",
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

    document.getElementById("procurementCategory").textContent = procurement.category;
    document.getElementById("procurementSupplier").textContent = procurement.supplier;
    document.getElementById("procurementLocation").textContent = procurement.location;

    document.getElementById("procurementRequestDate").textContent = procurement.requestDate;
    document.getElementById("procurementExpectedDate").textContent = procurement.expectedDate;
    document.getElementById("procurementDeliveryDate").textContent = procurement.deliveryDate || "—";
    document.getElementById("procurementReceiptDate").textContent = procurement.receiptDate || "—";

    document.getElementById("procurementRecipient").textContent = procurement.recipient;
    document.getElementById("procurementRecipientLocation").textContent = procurement.recipientLocation;

    // Customer details
    const customerDetails = `${procurement.recipient} | ${procurement.customerEmail} | ${procurement.customerPhone}`;
    document.getElementById("procurementCustomerDetails").textContent = customerDetails;

    // Totals
    document.getElementById("procurementTotalCost").textContent = procurement.totalCost;
    document.getElementById("procurementTotalShipmentFee").textContent = procurement.totalShipmentFee;
    document.getElementById("procurementTotalTransportation").textContent = procurement.totalTransportation;
    document.getElementById("procurementGrandTotal").textContent = procurement.grandTotal;

    // Detail table totals
    document.getElementById("detailTotalCost").textContent = procurement.totalCost;
    document.getElementById("detailTotalShipmentFee").textContent = procurement.totalShipmentFee;
    document.getElementById("detailTotalTransportation").textContent = procurement.totalTransportation;
    document.getElementById("detailGrandTotal").textContent = procurement.grandTotal;

    // Render items table
    // Download Invoice button is only enabled once the admin generates the invoice
    const downloadBtn = document.getElementById("downloadInvoice");
    const invoiceNote = document.getElementById("invoiceNotReady");
    if (downloadBtn) {
        downloadBtn.disabled = !procurement.invoiceGenerated;
        downloadBtn.title = procurement.invoiceGenerated
            ? "Download your invoice"
            : "Invoice will be available once our team generates it";
    }
    if (invoiceNote) {
        invoiceNote.style.display = procurement.invoiceGenerated ? "none" : "block";
    }

    renderProcurementItemsTable(procurement.items);

    updateProcurementStatus(procurement.status);
}

function renderProcurementItemsTable(items) {
    const tbody = document.getElementById("procurementItemsTableBody");
    if (!tbody) return;

    if (!items || items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="10" class="empty">No items found</td></tr>';
        return;
    }

    tbody.innerHTML = items.map((item, index) => {
        const qty = item.quantity !== null && item.quantity !== undefined ? item.quantity : "—";
        const weight = item.weight !== null && item.weight !== undefined ? parseFloat(item.weight).toFixed(2) : "—";
        const rate = item.rate !== null && item.rate !== undefined ? `₦${parseFloat(item.rate).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending";
        const cost = item.cost !== null && item.cost !== undefined ? `₦${parseFloat(item.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending";
        const shipmentFee = item.shipment_fee !== null && item.shipment_fee !== undefined ? `₦${parseFloat(item.shipment_fee).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending";
        const transportation = item.transportation !== null && item.transportation !== undefined ? `₦${parseFloat(item.transportation).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending";

        return `
            <tr>
                <td class="num">${index + 1}</td>
                <td>${item.description || "—"}</td>
                <td>${item.category || "—"}</td>
                <td>${item.supplier || "—"}</td>
                <td class="numeric">${qty}</td>
                <td class="numeric">${weight}</td>
                <td class="numeric">${rate}</td>
                <td class="numeric">${cost}</td>
                <td class="numeric">${shipmentFee}</td>
                <td class="numeric">${transportation}</td>
            </tr>
        `;
    }).join("");
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
            element.classList.remove("active", "completed");
            element.classList.add("pending");
        }
    });

    // Mark appropriate statuses
    if (normalizedStatus === "pending" || normalizedStatus === "submitted") {
        markStep("statusSubmitted");
    } else if (normalizedStatus === "approved" || normalizedStatus === "processing") {
        markStep("statusSubmitted");
        markStep("statusApproved");
    } else if (normalizedStatus === "supplier" || normalizedStatus === "assigned") {
        markStep("statusSubmitted");
        markStep("statusApproved");
        markStep("statusSupplier");
    } else if (normalizedStatus === "procured") {
        markStep("statusSubmitted");
        markStep("statusApproved");
        markStep("statusSupplier");
        markStep("statusProcured");
    } else if (normalizedStatus === "delivered" || normalizedStatus === "completed") {
        markStep("statusSubmitted");
        markStep("statusApproved");
        markStep("statusSupplier");
        markStep("statusProcured");
        markStep("statusDelivered");
    }

    function markStep(stepId) {
        const element = document.getElementById(stepId);
        if (element) {
            element.classList.remove("pending");
            element.classList.add("active");
        }
    }
}

// ========================================
// DOWNLOAD PDF INVOICE
// ========================================

async function downloadProcurementPDF() {
    if (!procurementId) {
        showToast("Procurement ID not found.", "warning");
        return;
    }

    if (!procurement?.invoiceGenerated) {
        showToast("Invoice is not available yet. It will be ready once our team generates it.", "warning");
        return;
    }

    const button = document.getElementById("downloadInvoice");
    if (button) {
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    }

    try {
        // Fetch the HTML invoice template from the backend
        const token = localStorage.getItem("auth_token");
        const headers = { 'Accept': 'text/html' };
        if (token) headers['Authorization'] = `Bearer ${token}`;

        const response = await fetch(`${CONFIG.API_URL}/procurements/${procurementId}/invoice`, {
            method: 'GET',
            headers: headers
        });

        if (!response.ok) {
            throw new Error('Failed to fetch invoice');
        }

        const htmlContent = await response.text();

        // Generate PDF from the HTML
        const { jsPDF } = window.jspdf;
        const document = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

        // Simple approach: split into pages
        const pageWidth = document.internal.pageSize.getWidth();
        const pageHeight = document.internal.pageSize.getHeight();
        const margin = 15;
        const contentWidth = pageWidth - 2 * margin;

        // Split the HTML into lines
        const lines = htmlContent.split('\n');
        let yPos = margin;

        document.setFont("helvetica", "normal");

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();

            if (yPos > pageHeight - margin) {
                document.addPage();
                yPos = margin;
            }

            // Simple text extraction (simplified approach)
            const textMatch = line.match(/<td[^>]*>([^<]+)<\/td>|<h[1-6][^>]*>([^<]+)<\/h[1-6]>|<p[^>]*>([^<]+)<\/p>/i);
            if (textMatch) {
                const text = textMatch[1] || textMatch[2] || textMatch[3] || "";
                if (text.trim()) {
                    document.text(text.trim(), margin, yPos, { maxWidth: contentWidth });
                    yPos += 5;
                }
            }
        }

        // Save the PDF
        document.save(`Procurement_${procurementId}_Invoice.pdf`);
        showToast('Invoice PDF downloaded successfully!', 'success');

    } catch (error) {
        console.error('Error generating PDF:', error);
        showToast('Error generating PDF. Please try again.', 'error');
    } finally {
        if (button) {
            button.disabled = false;
            button.innerHTML = 'Download Invoice';
        }
    }
}

const downloadInvoiceButton = document.getElementById("downloadInvoice") || document.getElementById("downloadProcurementInvoice");

if (downloadInvoiceButton) {

    downloadInvoiceButton.addEventListener("click", async function() {

        if (!procurementId) {
            showToast("Procurement ID not found.", "warning");
            return;
        }

        if (!procurement?.invoiceGenerated) {
            showToast("Invoice is not available yet. It will be ready once our team generates it.", "warning");
            return;
        }

        const token = localStorage.getItem("auth_token");
        if (!token) {
            showToast("You must be signed in to download the invoice.", "warning");
            window.location.href = "signin.html";
            return;
        }

        const button = downloadInvoiceButton;
        button.disabled = true;
        const originalLabel = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';

        try {
            const response = await fetch(`${CONFIG.API_URL}/procurements/${procurementId}/invoice`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'Authorization': `Bearer ${token}`
                }
            });

            if (response.status === 401) {
                showToast("Your session has expired. Please sign in again.", "warning");
                window.location.href = "signin.html";
                return;
            }

            if (response.status === 403) {
                const data = await response.json().catch(() => ({}));
                showToast(data.message || "Invoice has not been generated by admin yet.", "warning");
                return;
            }

            if (!response.ok) {
                throw new Error('Failed to download invoice');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `Procurement_${procurementId}_Invoice.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            showToast('Invoice PDF downloaded successfully!', 'success');
        } catch (error) {
            console.error('Error downloading invoice:', error);
            showToast('Error downloading invoice. Please try again.', 'error');
        } finally {
            button.disabled = !procurement?.invoiceGenerated;
            button.innerHTML = originalLabel;
        }

    });

}


