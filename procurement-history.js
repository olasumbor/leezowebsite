// ==========================================
// PROCUREMENT DATA
// ==========================================

// Existing sample procurement records
const sampleProcurementData = [

    {
        id: "PR8739020892",
        date: "Aug 08",
        status: "Completed"
    },

    {
        id: "PR8739020893",
        date: "Aug 10",
        status: "Pending"
    },

    {
        id: "PR8739020894",
        date: "Aug 12",
        status: "In Progress"
    },

    {
        id: "PR8739020895",
        date: "Aug 15",
        status: "Cancelled"
    },

    {
        id: "PR8739020896",
        date: "Aug 22",
        status: "Completed"
    }

];


// ==========================================
// GET SAVED PROCUREMENT REQUESTS
// ==========================================

const savedProcurements =
    JSON.parse(
        localStorage.getItem("procurements")
    ) || [];


// ==========================================
// COMBINE SAMPLE + SAVED PROCUREMENT DATA
// ==========================================

const procurementData = [
    ...sampleProcurementData,
    ...savedProcurements
];


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

        alert("No procurement found with this ID.");

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

displayProcurements(procurementData);

updateStatistics();