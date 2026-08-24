// ========================================
// PICK UP & DELIVERY DETAILS HANDLER
// ========================================

const urlParams = new URLSearchParams(window.location.search);
const pickupId = urlParams.get("id");

async function loadPickupDetails() {
    if (!pickupId) {
        showNotFound();
        return;
    }

    let pickup = null;

    // Try fetching from API first
    const token = localStorage.getItem("auth_token");
    if (token && typeof CONFIG !== "undefined") {
        try {
            const response = await fetch(`${CONFIG.API_URL}/pickup-deliveries`, {
                method: "GET",
                headers: {
                    "Accept": "application/json",
                    "Authorization": `Bearer ${token}`
                }
            });

            if (response.ok) {
                const requests = await response.json();
                const matched = requests.find(r => (r.request_id && r.request_id.toUpperCase() === pickupId.toUpperCase()) || String(r.id) === pickupId);
                if (matched) {
                    pickup = {
                        status: formatStatus(matched.status),
                        name: matched.name || "N/A",
                        phone: matched.phone || "N/A",
                        pickupAddress: matched.pickup_address || "N/A",
                        deliveryAddress: matched.delivery_address || "N/A",
                        otherDetails: matched.notes || matched.item_description || "N/A",
                        requestDate: matched.created_at ? new Date(matched.created_at).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        expectedDate: matched.pickup_date ? new Date(matched.pickup_date).toLocaleDateString("en-US", { month: "short", day: "2-digit", year: "numeric" }) : "N/A",
                        deliveredDate: matched.status === "completed" || matched.status === "delivered" ? "Delivered" : "—"
                    };
                }
            }
        } catch (err) {
            console.error("API error fetching pickup details:", err);
        }
    }

    // Fallback sample/local data
    if (!pickup) {
        const sampleDetails = {
            "PD8739020892": { status: "Completed", name: "John Adewale", phone: "+234 813 671 0716", pickupAddress: "Ikeja, Lagos, Nigeria", deliveryAddress: "Lekki, Lagos, Nigeria", otherDetails: "Handle package with care.", requestDate: "Aug 02, 2026", expectedDate: "Aug 08, 2026", deliveredDate: "Aug 08, 2026" },
            "PD8739020893": { status: "Pending", name: "Sarah Johnson", phone: "+234 703 989 0112", pickupAddress: "Surulere, Lagos, Nigeria", deliveryAddress: "Victoria Island, Lagos, Nigeria", otherDetails: "Please contact customer before arrival.", requestDate: "Aug 05, 2026", expectedDate: "Aug 12, 2026", deliveredDate: "—" },
            "PD8739020894": { status: "In Progress", name: "Michael Okafor", phone: "+234 812 000 0000", pickupAddress: "Yaba, Lagos, Nigeria", deliveryAddress: "Ajah, Lagos, Nigeria", otherDetails: "Fragile package.", requestDate: "Aug 08, 2026", expectedDate: "Aug 15, 2026", deliveredDate: "—" },
            "PD8739020895": { status: "Cancelled", name: "David James", phone: "+234 810 000 0000", pickupAddress: "Maryland, Lagos, Nigeria", deliveryAddress: "Ikoyi, Lagos, Nigeria", otherDetails: "Request cancelled by customer.", requestDate: "Aug 10, 2026", expectedDate: "Aug 18, 2026", deliveredDate: "—" },
            "PD8739020896": { status: "Completed", name: "Blessing Peters", phone: "+234 809 000 0000", pickupAddress: "Magodo, Lagos, Nigeria", deliveryAddress: "Festac, Lagos, Nigeria", otherDetails: "Documents and small parcel.", requestDate: "Aug 15, 2026", expectedDate: "Aug 22, 2026", deliveredDate: "Aug 22, 2026" }
        };
        pickup = sampleDetails[pickupId];
    }

    if (pickup) {
        renderDetails(pickup);
    } else {
        showNotFound();
    }
}

function formatStatus(status) {
    if (!status) return "Pending";
    const s = status.toLowerCase();
    if (s === "completed" || s === "delivered") return "Completed";
    if (s === "in_transit" || s === "in progress") return "In Progress";
    if (s === "cancelled") return "Cancelled";
    return "Pending";
}

function renderDetails(pickup) {
    if (document.getElementById("pickupId")) document.getElementById("pickupId").textContent = pickupId;
    if (document.getElementById("detailPickupId")) document.getElementById("detailPickupId").textContent = pickupId;
    if (document.getElementById("pickupName")) document.getElementById("pickupName").textContent = pickup.name;
    if (document.getElementById("pickupPhone")) document.getElementById("pickupPhone").textContent = pickup.phone;
    if (document.getElementById("pickupAddress")) document.getElementById("pickupAddress").textContent = pickup.pickupAddress;
    if (document.getElementById("deliveryAddress")) document.getElementById("deliveryAddress").textContent = pickup.deliveryAddress;
    if (document.getElementById("otherDetails")) document.getElementById("otherDetails").textContent = pickup.otherDetails;
    if (document.getElementById("pickupRequestDate")) document.getElementById("pickupRequestDate").textContent = pickup.requestDate;
    if (document.getElementById("pickupExpectedDate")) document.getElementById("pickupExpectedDate").textContent = pickup.expectedDate;
    if (document.getElementById("pickupDeliveredDate")) document.getElementById("pickupDeliveredDate").textContent = pickup.deliveredDate;
    if (document.getElementById("pickupStatus")) document.getElementById("pickupStatus").textContent = pickup.status;

    updatePickupStatus(pickup.status);
}

function showNotFound() {
    if (document.getElementById("pickupId")) document.getElementById("pickupId").textContent = "Request Not Found";
    if (document.getElementById("detailPickupId")) document.getElementById("detailPickupId").textContent = "No request record found";
}

function updatePickupStatus(status) {
    const statuses = ["statusRequested", "statusAssigned", "statusPickup", "statusTransit", "statusDelivered"];
    statuses.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove("status-complete", "status-current");
        }
    });

    if (status === "Completed") {
        statuses.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("status-complete");
        });
    } else if (status === "In Progress") {
        ["statusRequested", "statusAssigned", "statusPickup"].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.classList.add("status-complete");
        });
        const current = document.getElementById("statusTransit");
        if (current) current.classList.add("status-current");
    } else if (status === "Pending") {
        const requested = document.getElementById("statusRequested");
        if (requested) requested.classList.add("status-complete");
        const current = document.getElementById("statusAssigned");
        if (current) current.classList.add("status-current");
    }
}

// Download receipt handler
const downloadBtn = document.getElementById("downloadPickupReceipt");
if (downloadBtn) {
    downloadBtn.addEventListener("click", function () {
        const pId = pickupId || "request";
        const name = document.getElementById("pickupName")?.textContent || "";
        const phone = document.getElementById("pickupPhone")?.textContent || "";
        const pAddr = document.getElementById("pickupAddress")?.textContent || "";
        const dAddr = document.getElementById("deliveryAddress")?.textContent || "";
        const details = document.getElementById("otherDetails")?.textContent || "";
        const status = document.getElementById("pickupStatus")?.textContent || "";

        const receiptText = `
LEEZOEXPORTS LOGISTICS
PICK UP & DELIVERY RECEIPT
================================
Request ID: ${pId}
Customer Name: ${name}
Phone Number: ${phone}
Pick Up Address: ${pAddr}
Delivery Address: ${dAddr}
Details: ${details}
Status: ${status}
================================
Thank you for using Leezoexports Logistics.
        `;

        const blob = new Blob([receiptText], { type: "text/plain" });
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `${pId}-receipt.txt`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    });
}

// Track delivery button
const trackBtn = document.getElementById("trackPickup");
if (trackBtn) {
    trackBtn.addEventListener("click", function () {
        window.location.href = `pickup-delivery-history.html?id=${pickupId}`;
    });
}

loadPickupDetails();