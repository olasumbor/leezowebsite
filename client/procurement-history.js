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
                id: p.procurement_id,
                date: new Date(p.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit" }),
                status: p.status
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

    data.forEach(function (procurement) {

        const row = document.createElement("tr");

        row.innerHTML = `

            <td>
                ${procurement.id}
            </td>

            <td>
                ${procurement.date}
            </td>

            <td>
                ${procurement.status}
            </td>

            <td>

                <button
                    type="button"
                    class="view-procurement"
                    data-id="${procurement.id}"
                >
                    View
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


    document.getElementById(
        "totalProcurements"
    ).textContent = total;


    document.getElementById(
        "completedProcurements"
    ).textContent = completed;


    document.getElementById(
        "inProgressProcurements"
    ).textContent = inProgress;


    document.getElementById(
        "pendingProcurements"
    ).textContent = pending;

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