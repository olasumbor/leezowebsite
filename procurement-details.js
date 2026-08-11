// ========================================
// PROCUREMENT DATA
// ========================================

const procurementDetails = {

    "PR8739020892": {
        status: "Completed",
        product: "Office Equipment",
        category: "Business Supplies",
        quantity: "25 units",
        supplier: "Global Office Supplies Ltd.",
        location: "Lagos, Nigeria",
        requestDate: "Aug 02, 2026",
        expectedDate: "Aug 08, 2026",
        deliveredDate: "Aug 08, 2026",
        recipient: "Leezoexports Logistics",
        recipientLocation: "Lagos, Nigeria",
        cost: "₦850,000"
    },

    "PR8739020893": {
        status: "Pending",
        product: "Packaging Materials",
        category: "Packaging",
        quantity: "100 units",
        supplier: "Prime Packaging Nigeria",
        location: "Lagos, Nigeria",
        requestDate: "Aug 05, 2026",
        expectedDate: "Aug 12, 2026",
        deliveredDate: "—",
        recipient: "Leezoexports Logistics",
        recipientLocation: "Lagos, Nigeria",
        cost: "₦320,000"
    },

    "PR8739020894": {
        status: "In Progress",
        product: "Warehouse Equipment",
        category: "Warehouse Supplies",
        quantity: "15 units",
        supplier: "Industrial Solutions Ltd.",
        location: "Lagos, Nigeria",
        requestDate: "Aug 08, 2026",
        expectedDate: "Aug 15, 2026",
        deliveredDate: "—",
        recipient: "Leezoexports Logistics",
        recipientLocation: "Lagos, Nigeria",
        cost: "₦1,250,000"
    },

    "PR8739020895": {
        status: "Cancelled",
        product: "Transport Equipment",
        category: "Transportation",
        quantity: "8 units",
        supplier: "West Africa Equipment Ltd.",
        location: "Lagos, Nigeria",
        requestDate: "Aug 10, 2026",
        expectedDate: "Aug 18, 2026",
        deliveredDate: "—",
        recipient: "Leezoexports Logistics",
        recipientLocation: "Lagos, Nigeria",
        cost: "₦600,000"
    },

    "PR8739020896": {
        status: "Completed",
        product: "Safety Equipment",
        category: "Safety Supplies",
        quantity: "40 units",
        supplier: "SafeGuard Nigeria Ltd.",
        location: "Lagos, Nigeria",
        requestDate: "Aug 15, 2026",
        expectedDate: "Aug 22, 2026",
        deliveredDate: "Aug 22, 2026",
        recipient: "Leezoexports Logistics",
        recipientLocation: "Lagos, Nigeria",
        cost: "₦475,000"
    }

};


// ========================================
// GET PROCUREMENT ID FROM URL
// ========================================

const urlParams = new URLSearchParams(window.location.search);

const procurementId = urlParams.get("id");


// ========================================
// FIND THE PROCUREMENT
// ========================================

let procurement = procurementDetails[procurementId];


// ========================================
// CHECK SAVED PROCUREMENT REQUESTS
// ========================================

if (!procurement) {

    const savedProcurements =
        JSON.parse(
            localStorage.getItem("procurements")
        ) || [];

    const savedProcurement =
        savedProcurements.find(function(item) {
            return item.id === procurementId;
        });


    // ========================================
    // CONVERT SAVED PROCUREMENT
    // TO DETAILS PAGE FORMAT
    // ========================================

    if (savedProcurement) {

        procurement = {

            status: savedProcurement.status,

            product: savedProcurement.details,

            category: "Procurement Request",

            quantity: "—",

            supplier: "—",

            location: "—",

            requestDate: savedProcurement.date,

            expectedDate: "—",

            deliveredDate: "—",

            recipient: savedProcurement.name,

            recipientLocation: "—",

            cost: "—"

        };

    }

}

// ========================================
// SHOW PROCUREMENT DETAILS
// ========================================

if (procurement) {

    // Procurement ID
    document.getElementById("procurementId").textContent = procurementId;

    document.getElementById("detailProcurementId").textContent = procurementId;


    // Procurement information
    document.getElementById("procurementProduct").textContent =
        procurement.product;

    document.getElementById("procurementCategory").textContent =
        procurement.category;

    document.getElementById("procurementQuantity").textContent =
        procurement.quantity;

    document.getElementById("procurementSupplier").textContent =
        procurement.supplier;

    document.getElementById("procurementLocation").textContent =
        procurement.location;


    // Dates
    document.getElementById("procurementRequestDate").textContent =
        procurement.requestDate;

    document.getElementById("procurementExpectedDate").textContent =
        procurement.expectedDate;

    document.getElementById("procurementDeliveredDate").textContent =
        procurement.deliveredDate;


    // Recipient
    document.getElementById("procurementRecipient").textContent =
        procurement.recipient;

    document.getElementById("procurementRecipientLocation").textContent =
        procurement.recipientLocation;


    // Cost
    document.getElementById("procurementCost").textContent =
        procurement.cost;


    // Update procurement status
    updateProcurementStatus(procurement.status);


} else {

    // If the procurement ID doesn't exist
    document.getElementById("procurementId").textContent =
        "Procurement Not Found";

    document.getElementById("detailProcurementId").textContent =
        "No procurement record found";

}


// ========================================
// PROCUREMENT STATUS
// ========================================

function updateProcurementStatus(status) {

    const statuses = [
        "statusSubmitted",
        "statusApproved",
        "statusSupplier",
        "statusProcured",
        "statusDelivered"
    ];


    // Reset all statuses
    statuses.forEach(function(id) {

        const element = document.getElementById(id);

        if (element) {
            element.classList.remove("status-complete");
            element.classList.remove("status-current");
        }

    });


    // Completed
    if (status === "Completed") {

        statuses.forEach(function(id) {

            const element = document.getElementById(id);

            if (element) {
                element.classList.add("status-complete");
            }

        });

    }


    // In Progress
    else if (status === "In Progress") {

        const completedStatuses = [
            "statusSubmitted",
            "statusApproved",
            "statusSupplier",
            "statusProcured"
        ];

        completedStatuses.forEach(function(id) {

            const element = document.getElementById(id);

            if (element) {
                element.classList.add("status-complete");
            }

        });

        const current = document.getElementById("statusProcured");

        if (current) {
            current.classList.add("status-current");
        }

    }


    // Pending
    else if (status === "Pending") {

        const submitted = document.getElementById("statusSubmitted");

        if (submitted) {
            submitted.classList.add("status-complete");
        }

        const current = document.getElementById("statusApproved");

        if (current) {
            current.classList.add("status-current");
        }

    }


    // Cancelled
    else if (status === "Cancelled") {

        const submitted = document.getElementById("statusSubmitted");

        if (submitted) {
            submitted.classList.add("status-complete");
        }

        const cancelled = document.getElementById("statusApproved");

        if (cancelled) {
            cancelled.classList.add("status-current");
        }

    }

}


// ========================================
// DOWNLOAD RECEIPT
// ========================================

const downloadReceiptButton =
    document.getElementById("downloadReceipt");

if (downloadReceiptButton) {

    downloadReceiptButton.addEventListener("click", function() {

        if (!procurement) {
            return;
        }

        const receiptText = `
LEEZOEPORTS LOGISTICS
PROCUREMENT RECEIPT
==============================

Procurement ID: ${procurementId}

Product: ${procurement.product}
Category: ${procurement.category}
Quantity: ${procurement.quantity}

Supplier: ${procurement.supplier}
Location: ${procurement.location}

Request Date: ${procurement.requestDate}
Expected Delivery: ${procurement.expectedDate}
Delivered: ${procurement.deliveredDate}

Recipient: ${procurement.recipient}
Recipient Location: ${procurement.recipientLocation}

Procurement Cost: ${procurement.cost}

Status: ${procurement.status}

==============================
Thank you for using
Leezoexports Logistics.
        `;

        const blob = new Blob(
            [receiptText],
            { type: "text/plain" }
        );

        const url = URL.createObjectURL(blob);

        const link = document.createElement("a");

        link.href = url;

        link.download = `${procurementId}-receipt.txt`;

        document.body.appendChild(link);

        link.click();

        document.body.removeChild(link);

        URL.revokeObjectURL(url);

    });

}


// ========================================
// TRACK PROCUREMENT
// ========================================

const trackProcurementButton =
    document.getElementById("trackProcurement");

if (trackProcurementButton) {

    trackProcurementButton.addEventListener("click", function() {

        window.location.href =
            `procurement-history.html?id=${procurementId}`;

    });

}