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

        if (shipmentTableBody) {
            shipmentTableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: #6b7280;">Loading shipments...</td></tr>`;
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
                id: s.tracking_id || s.id,
                route: `${s.origin || 'Lagos, Nigeria'} → ${s.destination || '—'}`,
                date: new Date(s.shipped_date || s.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }),
                status: s.status ? s.status.replace('_', ' ').toUpperCase() : 'PENDING',
                rawStatus: s.status
            }));
            
            displayShipments(shipments);
            updateShipmentStatsLocal();
            fetchShipmentStats();
        } else if (response.status === 401) {
            window.location.href = 'signin.html';
        } else {
            if (shipmentTableBody) {
                shipmentTableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: #ef4444;">Failed to load shipments. Please try refreshing.</td></tr>`;
            }
        }
    } catch (error) {
        console.error('Failed to fetch shipments:', error);
        if (shipmentTableBody) {
            shipmentTableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: #ef4444;">Error connecting to server.</td></tr>`;
        }
    }
}

function updateShipmentStatsLocal() {
    const total = shipments.length;
    const delivered = shipments.filter(s => {
        const st = (s.rawStatus || s.status || '').toLowerCase();
        return st === 'delivered' || st === 'completed';
    }).length;
    const inTransit = shipments.filter(s => {
        const st = (s.rawStatus || s.status || '').toLowerCase();
        return st === 'in_transit' || st === 'in transit' || st === 'processing' || st === 'in-transit';
    }).length;
    const pending = shipments.filter(s => {
        const st = (s.rawStatus || s.status || '').toLowerCase();
        return st === 'pending' || (st !== 'delivered' && st !== 'completed' && st !== 'in_transit' && st !== 'in transit' && st !== 'processing' && st !== 'cancelled');
    }).length;

    const totalEl = document.getElementById("totalShipments");
    const deliveredEl = document.getElementById("deliveredShipments");
    const inTransitEl = document.getElementById("inTransitShipments");
    const pendingEl = document.getElementById("pendingShipments");

    if (totalEl) totalEl.textContent = total;
    if (deliveredEl) deliveredEl.textContent = delivered;
    if (inTransitEl) inTransitEl.textContent = inTransit;
    if (pendingEl) pendingEl.textContent = pending;
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

            if (totalEl && stats.total !== undefined) totalEl.textContent = stats.total;
            if (deliveredEl && stats.delivered !== undefined) deliveredEl.textContent = stats.delivered;
            if (inTransitEl && stats.in_transit !== undefined) inTransitEl.textContent = stats.in_transit;
            if (pendingEl && stats.pending !== undefined) pendingEl.textContent = stats.pending;
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
    if (!shipmentTableBody) return;
    shipmentTableBody.innerHTML = "";

    if (!shipmentList || shipmentList.length === 0) {
        shipmentTableBody.innerHTML = `<tr><td colspan="5" style="text-align: center; padding: 20px; color: #6b7280;">No shipments found.</td></tr>`;
        return;
    }

    shipmentList.forEach(function (shipment) {

        const row = document.createElement("tr");

        row.innerHTML = `
            <td><strong>${shipment.id}</strong></td>

            <td>${shipment.route}</td>

            <td>${shipment.date}</td>

            <td><span class="status-badge ${shipment.status.toLowerCase().replace(' ', '-')}">${shipment.status}</span></td>

            <td>
                <button 
                    class="view-shipment" 
                    data-shipment-id="${shipment.id}">
                    View Details
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