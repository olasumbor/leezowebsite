// ==========================================
// PICK UP & DELIVERY HISTORY HANDLER
// ==========================================

let pickupData = [];

const tableBody = document.getElementById("pickupTableBody");
const searchInput = document.getElementById("pickupSearch");
const searchButton = document.getElementById("pickupSearchButton");

async function loadPickupHistory() {
    try {
        const token = localStorage.getItem("auth_token");
        if (token && typeof CONFIG !== "undefined") {
            const response = await fetch(`${CONFIG.API_URL}/pickup-deliveries`, {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (response.ok) {
                const apiData = await response.json();
                pickupData = apiData.map(item => ({
                    id: item.request_id || `PKD-${item.id}`,
                    date: item.created_at ? new Date(item.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "-",
                    status: formatStatus(item.status),
                    rawItem: item
                }));
            }
        }
    } catch (err) {
        console.error("Error fetching pickup history from API:", err);
    }

    // Fallback or combine with saved local pickups if API returned empty
    if (pickupData.length === 0) {
        const savedPickups = JSON.parse(localStorage.getItem("pickupRequests")) || [];
        const samplePickupData = [
            { id: "PKD8739020892", date: "Aug 08, 2026", status: "Completed" },
            { id: "PKD8739020893", date: "Aug 10, 2026", status: "Pending" },
            { id: "PKD8739020894", date: "Aug 12, 2026", status: "In Progress" },
            { id: "PKD8739020895", date: "Aug 15, 2026", status: "Cancelled" },
            { id: "PKD8739020896", date: "Aug 22, 2026", status: "Completed" }
        ];
        pickupData = [...savedPickups.map(p => ({
            id: p.id || p.request_id,
            date: p.date || p.created_at || "-",
            status: formatStatus(p.status || "Pending")
        })), ...samplePickupData];
    }

    displayPickups(pickupData);
    updateStatistics();
}

function formatStatus(status) {
    if (!status) return "Pending";
    const s = status.toLowerCase();
    if (s === "completed" || s === "delivered") return "Completed";
    if (s === "in_transit" || s === "in progress") return "In Progress";
    if (s === "cancelled") return "Cancelled";
    return "Pending";
}

function displayPickups(data) {
    if (!tableBody) return;
    tableBody.innerHTML = "";

    if (data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;">No pickup requests found.</td></tr>`;
        return;
    }

    data.forEach(function (pickup) {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${pickup.id}</td>
            <td>${pickup.date}</td>
            <td>${pickup.status}</td>
            <td>
                <button type="button" class="view-pickup" data-id="${pickup.id}">View</button>
            </td>
        `;
        tableBody.appendChild(row);
    });

    const viewButtons = document.querySelectorAll(".view-pickup");
    viewButtons.forEach(function (button) {
        button.addEventListener("click", function () {
            const pickupId = this.getAttribute("data-id");
            window.location.href = `pickup-delivery-details.html?id=${pickupId}`;
        });
    });
}

function updateStatistics() {
    const total = pickupData.length;
    const completed = pickupData.filter(p => p.status === "Completed").length;
    const inProgress = pickupData.filter(p => p.status === "In Progress").length;
    const pending = pickupData.filter(p => p.status === "Pending").length;

    const totalEl = document.getElementById("totalPickups");
    const completedEl = document.getElementById("completedPickups");
    const inProgressEl = document.getElementById("inProgressPickups");
    const pendingEl = document.getElementById("pendingPickups");

    if (totalEl) totalEl.textContent = total;
    if (completedEl) completedEl.textContent = completed;
    if (inProgressEl) inProgressEl.textContent = inProgress;
    if (pendingEl) pendingEl.textContent = pending;
}

function searchPickup() {
    if (!searchInput) return;
    const searchValue = searchInput.value.trim().toUpperCase();

    if (searchValue === "") {
        displayPickups(pickupData);
        return;
    }

    const results = pickupData.filter(pickup => pickup.id.toUpperCase().includes(searchValue));

    if (results.length === 0) {
        if (typeof showToast !== "undefined") {
            showToast("No pick up or delivery request found with this ID.", "warning");
        } else {
            alert("No pick up or delivery request found with this ID.");
        }
        return;
    }

    displayPickups(results);
}

if (searchButton) {
    searchButton.addEventListener("click", searchPickup);
}

if (searchInput) {
    searchInput.addEventListener("keydown", function (event) {
        if (event.key === "Enter") {
            searchPickup();
        }
    });
}

loadPickupHistory();