// ========================================
// SHIPMENT DATA
// ========================================

const shipmentDetails = {

    "LZ8739020892": {

        origin: "Lagos, Nigeria",

        destination: "London, UK",

        trackingNumber: "CAR8739020892",

        service: "Air Cargo",

        weight: "12.5 kg",

        packages: "2",

        shippedDate: "Aug 2, 2026",

        deliveredDate: "Aug 8, 2026",

        recipient: "John Smith",

        recipientLocation: "London, UK",

        shippingCost: "₦185,000"

    },


    "LZ8739020893": {

        origin: "Ghana",

        destination: "London, UK",

        trackingNumber: "CAR8739020893",

        service: "Air Cargo",

        weight: "10 kg",

        packages: "1",

        shippedDate: "Aug 4, 2026",

        deliveredDate: "Aug 10, 2026",

        recipient: "David Smith",

        recipientLocation: "London, UK",

        shippingCost: "₦150,000"

    },


    "LZ8739020894": {

        origin: "Lagos, Nigeria",

        destination: "Canada",

        trackingNumber: "CAR8739020894",

        service: "Sea Cargo",

        weight: "25 kg",

        packages: "3",

        shippedDate: "Aug 5, 2026",

        deliveredDate: "Aug 12, 2026",

        recipient: "Michael Brown",

        recipientLocation: "Toronto, Canada",

        shippingCost: "₦220,000"

    },


    "LZ8739020895": {

        origin: "Lagos, Nigeria",

        destination: "USA",

        trackingNumber: "CAR8739020895",

        service: "Air Cargo",

        weight: "15 kg",

        packages: "2",

        shippedDate: "Aug 8, 2026",

        deliveredDate: "Aug 15, 2026",

        recipient: "James Wilson",

        recipientLocation: "New York, USA",

        shippingCost: "₦195,000"

    },


    "LZ8739020896": {

        origin: "Lagos, Nigeria",

        destination: "Toronto, Canada",

        trackingNumber: "CAR8739020896",

        service: "Sea Cargo",

        weight: "30 kg",

        packages: "4",

        shippedDate: "Aug 12, 2026",

        deliveredDate: "Aug 22, 2026",

        recipient: "Robert Johnson",

        recipientLocation: "Toronto, Canada",

        shippingCost: "₦250,000"

    }

};


// ========================================
// GET SHIPMENT ID FROM URL
// ========================================

const urlParams = new URLSearchParams(window.location.search);

const shipmentId = urlParams.get("id");


// ========================================
// FIND SHIPMENT
// ========================================

const shipment = shipmentDetails[shipmentId];


// ========================================
// DISPLAY SHIPMENT
// ========================================

if (shipment) {

    document.getElementById("shipmentTitle").textContent =
        `SHIPMENT ${shipmentId}`;


    document.getElementById("shipmentOrigin").textContent =
        shipment.origin;


    document.getElementById("shipmentDestination").textContent =
        shipment.destination;


    document.getElementById("trackingNumber").textContent =
        shipment.trackingNumber;


    document.getElementById("shipmentService").textContent =
        shipment.service;


    document.getElementById("shipmentWeight").textContent =
        shipment.weight;


    document.getElementById("shipmentPackages").textContent =
        shipment.packages;


    document.getElementById("shipmentDate").textContent =
        shipment.shippedDate;


    document.getElementById("deliveryDate").textContent =
        shipment.deliveredDate;


    document.getElementById("recipientName").textContent =
        shipment.recipient;


    document.getElementById("recipientLocation").textContent =
        shipment.recipientLocation;


    document.getElementById("shippingCost").textContent =
        shipment.shippingCost;

}


// ========================================
// SHIPMENT NOT FOUND
// ========================================

else {

    document.querySelector(".shipment-details-card").innerHTML = `

        <h1>Shipment Not Found</h1>

        <p>
            We could not find the shipment you are looking for.
        </p>

        <button
            onclick="window.location.href='shipment-history.html'">
            Back to Shipment History
        </button>

    `;

}


// ========================================
// BACK BUTTON
// ========================================

document.getElementById("backButton").addEventListener(
    "click",
    function () {

        window.location.href = "shipment-history.html";

    }
);


// ========================================
// TRACK SHIPMENT
// ========================================

document.getElementById("trackShipment").addEventListener(
    "click",
    function () {

        window.location.href =
            `track-shipment.html?id=${shipmentId}`;

    }
);


// ========================================
// DOWNLOAD RECEIPT
// ========================================

document.getElementById("downloadReceipt").addEventListener(
    "click",
    function () {

        alert("Receipt download will be connected to the backend later.");

    }
);