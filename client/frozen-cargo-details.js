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

    // Fallback sample data
    if (!frozenItem) {
        const sampleDetails = {
            "RQST7829102": { status: "Completed", name: "Vanessa Attah", phone: "+234 813 671 0716", description: "Frozen Snails & Pap", temperature: "Deep Freeze (-18°C)", weight: "45 kg", origin: "Lagos, Nigeria", destination: "London, UK", departureDate: "Aug 12, 2026", notes: "Strict temperature control required." },
            "RQST7829103": { status: "In Progress", name: "David Johnson", phone: "+234 703 989 0112", description: "Frozen Vegetables & Fish", temperature: "Chilled (0°C to 4°C)", weight: "80 kg", origin: "Lagos, Nigeria", destination: "Atlanta, US", departureDate: "Aug 15, 2026", notes: "Keep sealed in cold storage container." },
            "RQST7829104": { status: "Pending", name: "Blessing Okafor", phone: "+234 812 000 0000", description: "Cold Storage Sea Foods", temperature: "Deep Freeze (-18°C)", weight: "120 kg", origin: "Lagos, Nigeria", destination: "Toronto, CA", departureDate: "Aug 18, 2026", notes: "Express cold-chain shipment." },
            "RQST7829105": { status: "Pending", name: "Michael James", phone: "+234 810 000 0000", description: "Frozen Goat Meat", temperature: "Deep Freeze (-18°C)", weight: "60 kg", origin: "Lagos, Nigeria", destination: "Houston, US", departureDate: "Aug 20, 2026", notes: "Perishable items." }
        };
        frozenItem = sampleDetails[frozenId];
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
                const blob = new Blob([htmlContent], { type: 'text/html;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const win = window.open(url, '_blank');
                if (!win) {
                    const link = document.createElement("a");
                    link.href = url;
                    link.download = `Frozen-Cargo-Invoice-${frozenId}.html`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }
            } else {
                if (typeof showToast !== "undefined") showToast("Failed to generate invoice from backend.", "error");
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
