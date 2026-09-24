// ========================================
// FROZEN CARGO DETAILS HANDLER
// ========================================

const urlParams = new URLSearchParams(window.location.search);
const frozenId = urlParams.get("id");

async function loadFrozenDetails() {
    if (!frozenId) {
        showNotFound();
        return;
    }

    let frozenItem = null;

    // Try fetching from API first
    const token = localStorage.getItem("auth_token");
    if (token && typeof CONFIG !== "undefined") {
        try {
            const response = await fetch(`${CONFIG.API_URL}/frozen-cargos/${frozenId}`, {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (response.ok) {
                const matched = await response.json();
                if (matched) {
                    const rawItems = Array.isArray(matched.items) ? matched.items : [];
                    const items = rawItems.map(item => ({
                        description: item.description || item.name || "N/A",
                        quantity: item.quantity ?? "—",
                        weight: item.weight != null ? `${item.weight} kg` : "—",
                        rate: item.rate != null ? `₦${parseFloat(item.rate).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending",
                        cost: item.cost != null ? `₦${parseFloat(item.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending",
                    }));
                    // Fallback for legacy records without item rows
                    if (items.length === 0 && matched.cargo_description) {
                        items.push({
                            description: matched.cargo_description,
                            quantity: "—",
                            weight: matched.weight ? `${matched.weight} kg` : "—",
                            rate: "Pending",
                            cost: matched.cost ? `₦${parseFloat(matched.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}` : "Pending",
                        });
                    }
                    const total = matched.total_cost ?? matched.cost ?? null;
                    frozenItem = {
                        status: formatStatus(matched.status),
                        name: matched.name || "N/A",
                        phone: matched.phone || "N/A",
                        description: matched.cargo_description || (items[0] ? items[0].description : "N/A"),
                        temperature: matched.temperature_requirement || "Frozen (-18°C)",
                        weight: matched.weight ? `${matched.weight} kg` : "N/A",
                        origin: matched.origin || "N/A",
                        destination: matched.destination || "N/A",
                        departureDate: matched.departure_date ? new Date(matched.departure_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        notes: matched.notes || "None",
                        cost: total ? (isNaN(total) ? total : `₦${parseFloat(total).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "Pending Quote",
                        invoiceGenerated: matched.invoice_generated === true,
                        items: items,
                    };
                }
            }
        } catch (err) {
            console.error("API error fetching frozen cargo details:", err);
        }
    }



    if (frozenItem) {
        renderDetails(frozenItem);
    } else {
        showNotFound();
    }
}

function formatStatus(status) {
    if (!status) return "Pending";
    const s = status.toLowerCase();
    if (s === "completed" || s === "delivered") return "Completed";
    if (s === "in_transit" || s === "in progress" || s === "confirmed") return "In Progress";
    if (s === "cancelled") return "Cancelled";
    return "Pending";
}

function renderDetails(item) {
    if (document.getElementById("frozenId")) document.getElementById("frozenId").textContent = frozenId;
    if (document.getElementById("detailFrozenId")) document.getElementById("detailFrozenId").textContent = frozenId;
    if (document.getElementById("frozenName")) document.getElementById("frozenName").textContent = item.name;
    if (document.getElementById("frozenPhone")) document.getElementById("frozenPhone").textContent = item.phone;
    if (document.getElementById("frozenDescription")) document.getElementById("frozenDescription").textContent = item.description;
    if (document.getElementById("frozenTemperature")) document.getElementById("frozenTemperature").textContent = item.temperature;
    if (document.getElementById("frozenWeight")) document.getElementById("frozenWeight").textContent = item.weight;
    if (document.getElementById("frozenOrigin")) document.getElementById("frozenOrigin").textContent = item.origin;
    if (document.getElementById("frozenDestination")) document.getElementById("frozenDestination").textContent = item.destination;
    if (document.getElementById("frozenDepartureDate")) document.getElementById("frozenDepartureDate").textContent = item.departureDate;
    if (document.getElementById("frozenNotes")) document.getElementById("frozenNotes").textContent = item.notes;
    if (document.getElementById("frozenStatus")) document.getElementById("frozenStatus").textContent = item.status;
    if (document.getElementById("frozenCost")) document.getElementById("frozenCost").textContent = item.cost;

    updateInvoiceAvailability(item.invoiceGenerated);

    renderFrozenItems(item.items || []);

    updateStatusTimeline(item.status);
}

function renderFrozenItems(items) {
    const container = document.getElementById("frozenItemsBlock");
    if (!container) return;
    if (!items || items.length === 0) {
        container.innerHTML = `<p id="frozenDescription" style="color:#6b7280;">No item details available.</p>`;
        return;
    }
    const rows = items.map(row => `
        <tr>
            <td>${escapeFrozenHtml(row.description || '—')}</td>
            <td>${escapeFrozenHtml(row.quantity ?? '—')}</td>
            <td>${escapeFrozenHtml(row.weight ?? '—')}</td>
            <td style="text-align:right;">${escapeFrozenHtml(row.rate ?? '—')}</td>
            <td style="text-align:right;">${escapeFrozenHtml(row.cost ?? '—')}</td>
        </tr>`).join("");
    container.innerHTML = `
        <div style="overflow-x:auto;">
        <table class="dash-shipment-items-table">
            <thead><tr>
                <th>Item Description</th><th>Quantity</th><th>Weight</th>
                <th style="text-align:right;">Rate (₦)</th><th style="text-align:right;">Cost (₦)</th>
            </tr></thead>
            <tbody>${rows}</tbody>
        </table></div>`;
}

function escapeFrozenHtml(value) {
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function showNotFound() {
    if (document.getElementById("frozenId")) document.getElementById("frozenId").textContent = "Request Not Found";
    if (document.getElementById("detailFrozenId")) document.getElementById("detailFrozenId").textContent = "No frozen cargo record found";
}

function updateStatusTimeline(status) {
    const statuses = ["statusSubmitted", "statusConfirmed", "statusTransit", "statusDelivered"];
    statuses.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.remove("status-complete", "status-current");
    });

    if (status === "Completed") {
        statuses.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("status-complete");
        });
    } else if (status === "In Progress") {
        ["statusSubmitted", "statusConfirmed"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("status-complete");
        });
        const current = document.getElementById("statusTransit");
        if (current) current.classList.add("status-current");
    } else if (status === "Pending") {
        const submitted = document.getElementById("statusSubmitted");
        if (submitted) submitted.classList.add("status-complete");
        const current = document.getElementById("statusConfirmed");
        if (current) current.classList.add("status-current");
    }
}

// Download Invoice Handler

const downloadInvoiceBtn = document.getElementById("downloadFrozenInvoice") || document.getElementById("downloadInvoice");
const invoiceNote = document.getElementById("frozenInvoiceNotReady");
let invoiceGenerated = false;

function updateInvoiceAvailability(available) {
    invoiceGenerated = available === true;
    if (downloadInvoiceBtn) {
        downloadInvoiceBtn.disabled = !invoiceGenerated;
        downloadInvoiceBtn.title = invoiceGenerated
            ? "Download your invoice"
            : "Invoice will be available once our team generates it";
    }
    if (invoiceNote) {
        invoiceNote.style.display = invoiceGenerated ? "none" : "block";
    }
}

// Start locked until the record confirms the admin generated the invoice
updateInvoiceAvailability(false);

if (downloadInvoiceBtn) {
    downloadInvoiceBtn.addEventListener("click", async function () {
        if (!frozenId) {
            if (typeof showToast !== "undefined") showToast("Request ID not found.", "warning");
            return;
        }

        if (!invoiceGenerated) {
            if (typeof showToast !== "undefined") showToast("Invoice has not been generated by our team yet.", "warning");
            return;
        }

        const token = localStorage.getItem("auth_token");
        if (!token) {
            if (typeof showToast !== "undefined") showToast("You must be signed in to download the invoice.", "warning");
            window.location.href = "signin.html";
            return;
        }

        const button = downloadInvoiceBtn;
        button.disabled = true;
        const originalLabel = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Downloading...';

        try {
            const response = await fetch(`${CONFIG.API_URL}/frozen-cargos/${frozenId}/invoice`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/pdf',
                    'Authorization': `Bearer ${token}`
                }
            });

            if (response.status === 401) {
                if (typeof showToast !== "undefined") showToast("Your session has expired. Please sign in again.", "warning");
                window.location.href = "signin.html";
                return;
            }

            if (response.status === 403) {
                const data = await response.json().catch(() => ({}));
                if (typeof showToast !== "undefined") showToast(data.message || "Invoice has not been generated by admin yet.", "warning");
                return;
            }

            if (!response.ok) {
                throw new Error('Failed to download invoice');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `FrozenCargo_${frozenId}_Invoice.pdf`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            if (typeof showToast !== "undefined") showToast('Invoice PDF downloaded successfully!', 'success');
        } catch (error) {
            console.error('Error downloading invoice:', error);
            if (typeof showToast !== "undefined") showToast('Error downloading invoice. Please try again.', 'error');
        } finally {
            button.disabled = false;
            button.innerHTML = originalLabel;
        }
    });
}

// Track button
const trackBtn = document.getElementById("trackFrozen");
if (trackBtn) {
    trackBtn.addEventListener("click", function () {
        window.location.href = `frozen-cargo-history.html?id=${frozenId}`;
    });
}

loadFrozenDetails();
