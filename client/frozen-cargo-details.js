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
                    frozenItem = {
                        status: formatStatus(matched.status),
                        name: matched.name || "N/A",
                        phone: matched.phone || "N/A",
                        description: matched.cargo_description || "N/A",
                        temperature: matched.temperature_requirement || "Frozen (-18°C)",
                        weight: matched.weight ? `${matched.weight} kg` : "N/A",
                        origin: matched.origin || "N/A",
                        destination: matched.destination || "N/A",
                        departureDate: matched.departure_date ? new Date(matched.departure_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        notes: matched.notes || "None"
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

    updateStatusTimeline(item.status);
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

const downloadInvoiceBtn = document.getElementById("downloadFrozenInvoice");
if (downloadInvoiceBtn) {
    downloadInvoiceBtn.addEventListener("click", async function () {
        if (!frozenId) {
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

            const invoiceUrl = `${CONFIG.API_URL}/frozen-cargos/${frozenId}/invoice`;
            const response = await fetch(invoiceUrl, {
                method: 'GET',
                credentials: 'include',
                headers: headers
            });

            if (response.ok) {
                const htmlContent = await response.text();
                await downloadInvoiceAsPdf(htmlContent, `Frozen-Cargo-Invoice-${frozenId}.pdf`);
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

// Track button
const trackBtn = document.getElementById("trackFrozen");
if (trackBtn) {
    trackBtn.addEventListener("click", function () {
        window.location.href = `frozen-cargo-history.html?id=${frozenId}`;
    });
}

loadFrozenDetails();
