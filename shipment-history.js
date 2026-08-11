// ========================================
// SAMPLE SHIPMENT DATA
// ========================================

const shipments = [
    {
        id: "LZ8739020892",
        route: "Lagos → London",
        date: "Aug 08",
        status: "Delivered"
    },
    {
        id: "LZ8739020893",
        route: "Ghana → London",
        date: "Aug 10",
        status: "Delivered"
    },
    {
        id: "LZ8739020894",
        route: "Lagos → Canada",
        date: "Aug 12",
        status: "Delivered"
    },
    {
        id: "LZ8739020895",
        route: "Lagos → USA",
        date: "Aug 15",
        status: "Delivered"
    },
    {
        id: "LZ8739020896",
        route: "Lagos → Toronto",
        date: "Aug 22",
        status: "Delivered"
    }
];


// ========================================
// DISPLAY SHIPMENTS
// ========================================

const shipmentTableBody = document.getElementById("shipmentTableBody");

function displayShipments(shipmentList) {

    shipmentTableBody.innerHTML = "";

    shipmentList.forEach(function (shipment) {

        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${shipment.id}</td>

            <td>${shipment.route}</td>

            <td>${shipment.date}</td>

            <td>${shipment.status}</td>

            <td>
                <button 
                    class="view-shipment" 
                    data-shipment-id="${shipment.id}">
                    View
                </button>
            </td>
        `;

        shipmentTableBody.appendChild(row);
    });

    addViewButtonListeners();
}


// ========================================
// VIEW BUTTON
// ========================================

function addViewButtonListeners() {

    const viewButtons = document.querySelectorAll(".view-shipment");

    viewButtons.forEach(function (button) {

        button.addEventListener("click", function () {

            const shipmentId = button.getAttribute("data-shipment-id");

            window.location.href =
                `shipment-details.html?id=${shipmentId}`;

        });

    });
}


// ========================================
// SEARCH
// ========================================

const searchInput = document.getElementById("shipmentSearch");
const searchButton = document.getElementById("shipmentSearchButton");

function searchShipment() {

    const searchValue = searchInput.value.trim().toLowerCase();

    if (searchValue === "") {

        displayShipments(shipments);

        return;
    }

    const filteredShipments = shipments.filter(function (shipment) {

        return shipment.id.toLowerCase().includes(searchValue);

    });

    if (filteredShipments.length === 0) {

        alert("No shipment found with this ID.");

        displayShipments(shipments);

        return;
    }

    displayShipments(filteredShipments);
}


// Search button
searchButton.addEventListener("click", searchShipment);


// Allow Enter key to search
searchInput.addEventListener("keypress", function (event) {

    if (event.key === "Enter") {

        searchShipment();

    }

});


// ========================================
// INITIAL LOAD
// ========================================

displayShipments(shipments);