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
                        notes: matched.notes || "None",
                        cost: matched.cost ? (isNaN(matched.cost) ? matched.cost : `₦${parseFloat(matched.cost).toLocaleString('en-NG', { minimumFractionDigits: 2 })}`) : "Pending Quote"
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

const downloadInvoiceBtn = document.getElementById("downloadFrozenInvoice") || document.getElementById("downloadInvoice");
if (downloadInvoiceBtn) {
    downloadInvoiceBtn.addEventListener("click", function () {
        if (!frozenId) {
            if (typeof showToast !== "undefined") showToast("Request ID not found.", "warning");
            return;
        }

        // Download PDF directly from backend
        window.location.href = `${CONFIG.API_URL}/frozen-cargos/${frozenId}/invoice`;
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
