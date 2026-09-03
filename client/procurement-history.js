// ==========================================
// PROCUREMENT DATA
// ==========================================

let procurementData = [];

// ==========================================
// FETCH PROCUREMENT DATA FROM API
// ==========================================
async function fetchProcurements() {
    try {
        const token = localStorage.getItem("auth_token");
        if (!token) {
            window.location.href = 'signin.html';
            return;
        }

        const response = await fetch(`${CONFIG.API_URL}/procurements`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            // Format dates and IDs for the frontend
            procurementData = data.map(p => ({
                id: p.procurement_id || `PROC-${p.id}`,
                date: new Date(p.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }),
                status: formatStatus(p.status),
                rawStatus: p.status
            }));
            displayProcurements(procurementData);
            updateStatistics();
        } else if (response.status === 401) {
            // Unauthorized, redirect to login
            window.location.href = 'signin.html';
        }
    } catch (error) {
        console.error('Failed to fetch procurements:', error);
    }
}

function formatStatus(status) {
    if (!status) return "Pending";
    const s = status.toLowerCase().replace('_', ' ');
    if (s === "completed" || s === "delivered") return "Completed";
    if (s === "in_progress" || s === "in progress" || s === "processing" || s === "approved") return "In Progress";
    if (s === "cancelled") return "Cancelled";
    return "Pending";
}

// =================================================
// ELEMENTS
// =================================================

const tableBody =
    document.getElementById("procurementTableBody");

const searchInput =
    document.getElementById("procurementSearch");

const searchButton =
    document.getElementById("procurementSearchButton");


// =================================================
// DISPLAY PROCUREMENT DATA
// =================================================

function displayProcurements(data) {

    tableBody.innerHTML = "";

    if (!data || data.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="4" style="text-align: center; padding: 20px; color: #6b7280;">No procurement requests found.</td></tr>`;
        return;
    }

    data.forEach(function (procurement) {

        const row = document.createElement("tr");

        row.innerHTML = `

            <td>
                <strong>${procurement.id}</strong>
            </td>

            <td>
                ${procurement.date}
            </td>

            <td>
                <span class="status-badge ${procurement.status.toLowerCase().replace(' ', '-')}">${procurement.status}</span>
            </td>

            <td>

                <button
                    type="button"
                    class="view-procurement"
                    data-id="${procurement.id}"
                >
                    View Details
                </button>

            </td>

        `;

        tableBody.appendChild(row);

    });


    // =============================================
    // VIEW BUTTONS
    // =============================================

    const viewButtons =
        document.querySelectorAll(".view-procurement");


    viewButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const procurementId =
                this.getAttribute("data-id");

            window.location.href =
                `procurement-details.html?id=${procurementId}`;

        });

    });

}


// =================================================
// UPDATE STATISTICS
// =================================================

function updateStatistics() {

    const total =
        procurementData.length;


    const completed =
        procurementData.filter(function (procurement) {

            return procurement.status === "Completed";

        }).length;


    const inProgress =
        procurementData.filter(function (procurement) {

            return procurement.status === "In Progress";

        }).length;


    const pending =
        procurementData.filter(function (procurement) {

            return procurement.status === "Pending";

        }).length;


    const totalEl = document.getElementById("totalProcurements");
    const completedEl = document.getElementById("completedProcurements");
    const inProgressEl = document.getElementById("inProgressProcurements");
    const pendingEl = document.getElementById("pendingProcurements");

    if (totalEl) totalEl.textContent = total;
    if (completedEl) completedEl.textContent = completed;
    if (inProgressEl) inProgressEl.textContent = inProgress;
    if (pendingEl) pendingEl.textContent = pending;

}



// =================================================
// SEARCH PROCUREMENT
// =================================================

function searchProcurement() {

    const searchValue =
        searchInput.value.trim().toUpperCase();


    // If search is empty,
    // show the complete table again.

    if (searchValue === "") {

        displayProcurements(procurementData);

        return;
    }


    const results =
        procurementData.filter(function (procurement) {

            return procurement.id
                .toUpperCase()
                .includes(searchValue);

        });


    // =============================================
    // NO RESULT
    // =============================================

    if (results.length === 0) {

        showToast("No procurement found with this ID.", "warning");

        return;
    }


    // =============================================
    // SHOW SEARCH RESULTS
    // =============================================

    displayProcurements(results);

}


// =================================================
// SEARCH BUTTON
// =================================================

searchButton.addEventListener(
    "click",
    searchProcurement
);


// =================================================
// ENTER KEY SEARCH
// =================================================

searchInput.addEventListener(
    "keydown",
    function (event) {

        if (event.key === "Enter") {

            searchProcurement();

        }

    }
);


// =================================================
// INITIAL PAGE LOAD
// =================================================

fetchProcurements();