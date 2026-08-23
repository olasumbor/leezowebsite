// ========================================
// PICK UP & DELIVERY DATA
// ========================================

const pickupDetails = {


    "PD8739020892": {

        status: "Completed",

        name: "John Adewale",

        phone: "+234 813 671 0716",

        pickupAddress:
            "Ikeja, Lagos, Nigeria",

        deliveryAddress:
            "Lekki, Lagos, Nigeria",

        otherDetails:
            "Handle package with care.",

        requestDate:
            "Aug 02, 2026",

        expectedDate:
            "Aug 08, 2026",

        deliveredDate:
            "Aug 08, 2026"

    },



    "PD8739020893": {

        status: "Pending",

        name: "Sarah Johnson",

        phone: "+234 703 989 0112",

        pickupAddress:
            "Surulere, Lagos, Nigeria",

        deliveryAddress:
            "Victoria Island, Lagos, Nigeria",

        otherDetails:
            "Please contact customer before arrival.",

        requestDate:
            "Aug 05, 2026",

        expectedDate:
            "Aug 12, 2026",

        deliveredDate:
            "—"

    },



    "PD8739020894": {

        status: "In Progress",

        name: "Michael Okafor",

        phone: "+234 812 000 0000",

        pickupAddress:
            "Yaba, Lagos, Nigeria",

        deliveryAddress:
            "Ajah, Lagos, Nigeria",

        otherDetails:
            "Fragile package.",

        requestDate:
            "Aug 08, 2026",

        expectedDate:
            "Aug 15, 2026",

        deliveredDate:
            "—"

    },



    "PD8739020895": {

        status: "Cancelled",

        name: "David James",

        phone: "+234 810 000 0000",

        pickupAddress:
            "Maryland, Lagos, Nigeria",

        deliveryAddress:
            "Ikoyi, Lagos, Nigeria",

        otherDetails:
            "Request cancelled by customer.",

        requestDate:
            "Aug 10, 2026",

        expectedDate:
            "Aug 18, 2026",

        deliveredDate:
            "—"

    },


    "PD8739020896": {

        status: "Completed",

        name: "Blessing Peters",

        phone: "+234 809 000 0000",

        pickupAddress:
            "Magodo, Lagos, Nigeria",

        deliveryAddress:
            "Festac, Lagos, Nigeria",

        otherDetails:
            "Documents and small parcel.",

        requestDate:
            "Aug 15, 2026",

        expectedDate:
            "Aug 22, 2026",

        deliveredDate:
            "Aug 22, 2026"

    }

};



// ========================================
// GET REQUEST ID FROM URL
// ========================================

const urlParams =
    new URLSearchParams(
        window.location.search
    );


const pickupId =
    urlParams.get("id");



// ========================================
// FIND REQUEST
// ========================================

let pickup =
    pickupDetails[pickupId];



// ========================================
// CHECK SAVED REQUESTS
// ========================================

if (!pickup) {


    const savedPickups =
        JSON.parse(
            localStorage.getItem(
                "pickupRequests"
            )
        ) || [];



    const savedPickup =
        savedPickups.find(
            function (item) {

                return item.id === pickupId;

            }
        );



    if (savedPickup) {


        pickup = {

            status:
                savedPickup.status || "Pending",

            name:
                savedPickup.name || "—",

            phone:
                savedPickup.phone || "—",

            pickupAddress:
                savedPickup.pickupAddress || "—",

            deliveryAddress:
                savedPickup.deliveryAddress || "—",

            otherDetails:
                savedPickup.otherDetails || "—",

            requestDate:
                savedPickup.date || "—",

            expectedDate:
                "—",

            deliveredDate:
                "—"

        };

    }

}



// ========================================
// SHOW DETAILS
// ========================================

if (pickup) {


    document.getElementById(
        "pickupId"
    ).textContent = pickupId;


    document.getElementById(
        "detailPickupId"
    ).textContent = pickupId;



    document.getElementById(
        "pickupName"
    ).textContent = pickup.name;



    document.getElementById(
        "pickupPhone"
    ).textContent = pickup.phone;



    document.getElementById(
        "pickupAddress"
    ).textContent = pickup.pickupAddress;



    document.getElementById(
        "deliveryAddress"
    ).textContent = pickup.deliveryAddress;



    document.getElementById(
        "otherDetails"
    ).textContent = pickup.otherDetails;



    document.getElementById(
        "pickupRequestDate"
    ).textContent = pickup.requestDate;



    document.getElementById(
        "pickupExpectedDate"
    ).textContent = pickup.expectedDate;



    document.getElementById(
        "pickupDeliveredDate"
    ).textContent = pickup.deliveredDate;



    document.getElementById(
        "pickupStatus"
    ).textContent = pickup.status;



    updatePickupStatus(
        pickup.status
    );


} else {


    document.getElementById(
        "pickupId"
    ).textContent =
        "Request Not Found";


    document.getElementById(
        "detailPickupId"
    ).textContent =
        "No request record found";

}



// ========================================
// PICK UP STATUS
// ========================================

function updatePickupStatus(status) {


    const statuses = [

        "statusRequested",

        "statusAssigned",

        "statusPickup",

        "statusTransit",

        "statusDelivered"

    ];



    // Reset statuses

    statuses.forEach(function (id) {


        const element =
            document.getElementById(id);


        if (element) {

            element.classList.remove(
                "status-complete"
            );

            element.classList.remove(
                "status-current"
            );

        }

    });



    // COMPLETED

    if (status === "Completed") {


        statuses.forEach(function (id) {


            const element =
                document.getElementById(id);


            if (element) {

                element.classList.add(
                    "status-complete"
                );

            }

        });

    }



    // IN PROGRESS

    else if (status === "In Progress") {


        const completedStatuses = [

            "statusRequested",

            "statusAssigned",

            "statusPickup"

        ];



        completedStatuses.forEach(function (id) {


            const element =
                document.getElementById(id);


            if (element) {

                element.classList.add(
                    "status-complete"
                );

            }

        });



        const current =
            document.getElementById(
                "statusTransit"
            );


        if (current) {

            current.classList.add(
                "status-current"
            );

        }

    }



    // PENDING

    else if (status === "Pending") {


        const requested =
            document.getElementById(
                "statusRequested"
            );


        if (requested) {

            requested.classList.add(
                "status-complete"
            );

        }



        const current =
            document.getElementById(
                "statusAssigned"
            );


        if (current) {

            current.classList.add(
                "status-current"
            );

        }

    }



    // CANCELLED

    else if (status === "Cancelled") {


        const requested =
            document.getElementById(
                "statusRequested"
            );


        if (requested) {

            requested.classList.add(
                "status-complete"
            );

        }

    }

}



// ========================================
// DOWNLOAD RECEIPT
// ========================================

const downloadPickupReceiptButton =
    document.getElementById(
        "downloadPickupReceipt"
    );


if (downloadPickupReceiptButton) {


    downloadPickupReceiptButton.addEventListener(
        "click",
        function () {


            if (!pickup) {

                return;

            }



            const receiptText = `

LEEZOEXPORTS LOGISTICS

PICK UP & DELIVERY RECEIPT

================================

Request ID: ${pickupId}

Customer Name: ${pickup.name}

Phone Number: ${pickup.phone}

Pick Up Address:
${pickup.pickupAddress}

Delivery Address:
${pickup.deliveryAddress}

Other Details:
${pickup.otherDetails}

Request Date:
${pickup.requestDate}

Expected Delivery:
${pickup.expectedDate}

Delivered:
${pickup.deliveredDate}

Status:
${pickup.status}

================================

Thank you for using
Leezoexports Logistics.

            `;



            const blob =
                new Blob(

                    [receiptText],

                    {
                        type: "text/plain"
                    }

                );



            const url =
                URL.createObjectURL(blob);



            const link =
                document.createElement("a");



            link.href = url;



            link.download =
                `${pickupId}-receipt.txt`;



            document.body.appendChild(link);



            link.click();



            document.body.removeChild(link);



            URL.revokeObjectURL(url);

        }
    );

}



// ========================================
// TRACK DELIVERY
// ========================================

const trackPickupButton =
    document.getElementById(
        "trackPickup"
    );


if (trackPickupButton) {


    trackPickupButton.addEventListener(
        "click",
        function () {


            window.location.href =
                `pickup-delivery-history.html?id=${pickupId}`;

        }
    );

}