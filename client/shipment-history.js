let shipments = [];

// ========================================
// FETCH SHIPMENTS
// ========================================

async function fetchShipments() {
    try {
        const token = localStorage.getItem("auth_token");
        if (!token) {
            window.location.href = 'signin.html';
            return;
        }

        const response = await fetch(`${CONFIG.API_URL}/shipments`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            const data = await response.json();
            // Map the API data to the format expected by the frontend
            shipments = data.map(s => ({
                id: s.tracking_id,
                route: `${s.origin || '—'} → ${s.destination || '—'}`,
                date: new Date(s.shipped_date || s.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit" }),
                status: s.status ? s.status.replace('_', ' ').toUpperCase() : 'PENDING'
            }));
            
            displayShipments(shipments);
            fetchShipmentStats();
        } else if (response.status === 401) {
            window.location.href = 'signin.html';
        }
    } catch (error) {
        console.error('Failed to fetch shipments:', error);
    }
}

async function fetchShipmentStats() {
    try {
        const token = localStorage.getItem("auth_token");
        if (!token) return;

        const response = await fetch(`${CONFIG.API_URL}/shipments/stats`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        if (response.ok) {
            const stats = await response.json();
            const totalEl = document.getElementById("totalShipments");
            const deliveredEl = document.getElementById("deliveredShipments");
            const inTransitEl = document.getElementById("inTransitShipments");
            const pendingEl = document.getElementById("pendingShipments");

            if (totalEl) totalEl.textContent = stats.total ?? 0;
            if (deliveredEl) deliveredEl.textContent = stats.delivered ?? 0;
            if (inTransitEl) inTransitEl.textContent = stats.in_transit ?? 0;
            if (pendingEl) pendingEl.textContent = stats.pending ?? 0;
        }
    } catch (error) {
        console.error('Failed to fetch shipment stats:', error);
    }
}

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

        showToast("No shipment found with this ID.", "warning");

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

fetchShipments();