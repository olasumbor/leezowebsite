// ==========================================
// PICK UP & DELIVERY DATA
// ==========================================

const samplePickupData = [

    {
        id: "PD8739020892",
        date: "Aug 08",
        status: "Completed"
    },

    {
        id: "PD8739020893",
        date: "Aug 10",
        status: "Pending"
    },

    {
        id: "PD8739020894",
        date: "Aug 12",
        status: "In Progress"
    },

    {
        id: "PD8739020895",
        date: "Aug 15",
        status: "Cancelled"
    },

    {
        id: "PD8739020896",
        date: "Aug 22",
        status: "Completed"
    }

];



// ==========================================
// GET SAVED PICK UP REQUESTS
// ==========================================

const savedPickups =
    JSON.parse(
        localStorage.getItem("pickupRequests")
    ) || [];



// ==========================================
// COMBINE SAMPLE + SAVED DATA
// ==========================================

const pickupData = [

    ...samplePickupData,

    ...savedPickups

];



// ==========================================
// ELEMENTS
// ==========================================

const tableBody =
    document.getElementById("pickupTableBody");


const searchInput =
    document.getElementById("pickupSearch");


const searchButton =
    document.getElementById("pickupSearchButton");



// ==========================================
// DISPLAY PICK UP DATA
// ==========================================

function displayPickups(data) {

    tableBody.innerHTML = "";


    data.forEach(function (pickup) {


        const row =
            document.createElement("tr");


        row.innerHTML = `

            <td>
                ${pickup.id}
            </td>

            <td>
                ${pickup.date}
            </td>

            <td>
                ${pickup.status}
            </td>

            <td>

                <button
                    type="button"
                    class="view-pickup"
                    data-id="${pickup.id}"
                >
                    View
                </button>

            </td>

        `;


        tableBody.appendChild(row);

    });



    // =====================================
    // VIEW BUTTONS
    // =====================================

    const viewButtons =
        document.querySelectorAll(".view-pickup");


    viewButtons.forEach(function (button) {


        button.addEventListener("click", function () {


            const pickupId =
                this.getAttribute("data-id");


            window.location.href =
                `pickup-delivery-details.html?id=${pickupId}`;

        });

    });

}



// ==========================================
// UPDATE STATISTICS
// ==========================================

function updateStatistics() {


    const total =
        pickupData.length;



    const completed =
        pickupData.filter(function (pickup) {

            return pickup.status === "Completed";

        }).length;



    const inProgress =
        pickupData.filter(function (pickup) {

            return pickup.status === "In Progress";

        }).length;



    const pending =
        pickupData.filter(function (pickup) {

            return pickup.status === "Pending";

        }).length;



    document.getElementById(
        "totalPickups"
    ).textContent = total;



    document.getElementById(
        "completedPickups"
    ).textContent = completed;



    document.getElementById(
        "inProgressPickups"
    ).textContent = inProgress;



    document.getElementById(
        "pendingPickups"
    ).textContent = pending;

}



// ==========================================
// SEARCH PICK UP REQUEST
// ==========================================

function searchPickup() {


    const searchValue =
        searchInput.value
            .trim()
            .toUpperCase();



    // If search is empty,
    // show all requests again

    if (searchValue === "") {


        displayPickups(pickupData);

        return;

    }



    const results =
        pickupData.filter(function (pickup) {


            return pickup.id
                .toUpperCase()
                .includes(searchValue);

        });



    // =====================================
    // NO RESULT
    // =====================================

    if (results.length === 0) {


        alert("No pick up or delivery request found with this ID.");

        return;

    }



    // =====================================
    // SHOW RESULTS
    // =====================================

    displayPickups(results);

}



// ==========================================
// SEARCH BUTTON
// ==========================================

searchButton.addEventListener(
    "click",
    searchPickup
);



// ==========================================
// ENTER KEY SEARCH
// ==========================================

searchInput.addEventListener(
    "keydown",
    function (event) {


        if (event.key === "Enter") {

            searchPickup();

        }

    }
);



// ==========================================
// INITIAL PAGE LOAD
// ==========================================

displayPickups(pickupData);

updateStatistics();