// ==========================================
// FROZEN CARGO HISTORY HANDLER
// ==========================================

let frozenData = [];

const tableBody = document.getElementById("frozenTableBody");
const searchInput = document.getElementById("frozenSearch");
const searchButton = document.getElementById("frozenSearchButton");

async function loadFrozenHistory() {
    try {
        const token = localStorage.getItem("auth_token");
        if (token && typeof CONFIG !== "undefined") {
            const response = await fetch(`${CONFIG.API_URL}/frozen-cargos`, {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (response.ok) {
                const apiData = await response.json();
                frozenData = apiData.map(item => ({
                    id: item.request_id || `RQST-${item.id}`,
                    route: `${item.cargo_description || 'Cold Cargo'} (${item.origin || 'Lagos'} → ${item.destination || 'Destination'})`,
                    date: item.departure_date ? new Date(item.departure_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : (item.created_at ? new Date(item.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "-"),
                    status: formatStatus(item.status),
                    rawItem: item
                }));
            }
        }
    } catch (err) {
        console.error("Error fetching frozen cargo history from API:", err);
    }

    // Fallback sample data if API returned empty
    if (frozenData.length === 0) {
        const sampleData = [
            { id: "RQST7829102", route: "Frozen Snails & Pap (Lagos → London, UK)", date: "Aug 12, 2026", status: "Completed" },
            { id: "RQST7829103", route: "Frozen Vegetables & Fish (Lagos → Atlanta, US)", date: "Aug 15, 2026", status: "In Progress" },
            { id: "RQST7829104", route: "Cold Storage Sea Foods (Lagos → Toronto, CA)", date: "Aug 18, 2026", status: "Pending" },
            { id: "RQST7829105", route: "Frozen Goat Meat (Lagos → Houston, US)", date: "Aug 20, 2026", status: "Pending" }
        ];
        frozenData = sampleData;
    }

    displayFrozenRequests(frozenData);
    updateStatistics();
}

function formatStatus(status) {
    if (!status) return "Pending";
    const s = status.toLowerCase();
    if (s === "completed" || s === "delivered") return "Completed";
    if (s === "in_transit" || s === "in progress" || s === "confirmed") return "In Progress";
    if (s === "cancelled") return "Cancelled";
    return "Pending";
}

function displayFrozenRequests(data) {
    if (!tableBody) return;
    tableBody.innerHTML = "";

    if (data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 20px;">No frozen cargo requests found.</td></tr>`;
        return;
    }

    data.forEach(function (item) {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td><strong>${item.id}</strong></td>
            <td>${item.route}</td>
            <td>${item.date}</td>
            <td><span class="status-badge ${item.status.toLowerCase().replace(' ', '-')}">${item.status}</span></td>
            <td>
                <button type="button" class="view-frozen" data-id="${item.id}" style="padding: 4px 12px; background: #15803d; color: #fff; border: none; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: 600;">View Details</button>
            </td>
        `;
        tableBody.appendChild(row);
    });

    const viewButtons = document.querySelectorAll(".view-frozen");
    viewButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const reqId = this.getAttribute("data-id");
            window.location.href = `frozen-cargo-details.html?id=${reqId}`;
        });
    });
}

function updateStatistics() {
    const total = frozenData.length;
    const completed = frozenData.filter(p => p.status === "Completed").length;
    const inProgress = frozenData.filter(p => p.status === "In Progress").length;
    const pending = frozenData.filter(p => p.status === "Pending").length;

    const totalEl = document.getElementById("totalFrozen");
    const completedEl = document.getElementById("completedFrozen");
    const inProgressEl = document.getElementById("inProgressFrozen");
    const pendingEl = document.getElementById("pendingFrozen");

    if (totalEl) totalEl.textContent = total;
    if (completedEl) completedEl.textContent = completed;
    if (inProgressEl) inProgressEl.textContent = inProgress;
    if (pendingEl) pendingEl.textContent = pending;
}

function searchFrozen() {
    if (!searchInput) return;
    const searchValue = searchInput.value.trim().toUpperCase();

    if (searchValue === "") {
        displayFrozenRequests(frozenData);
        return;
    }

    const results = frozenData.filter(item => item.id.toUpperCase().includes(searchValue));

    if (results.length === 0) {
        if (typeof showToast !== "undefined") {
            showToast("No frozen cargo request found with this ID.", "warning");
        } else {
            alert("No frozen cargo request found with this ID.");
        }
        return;
    }

    displayFrozenRequests(results);
}

if (searchButton) {
    searchButton.addEventListener("click", searchFrozen);
}

if (searchInput) {
    searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            searchFrozen();
        }
    });
}

loadFrozenHistory();
