// ==========================================
// PROCUREMENT FORM
// ==========================================

const procurementForm =
    document.getElementById("procurementForm");


if (procurementForm) {

    procurementForm.addEventListener("submit", function (event) {

        event.preventDefault();


        // ======================================
        // GET FORM VALUES
        // ======================================

        const name =
            document.getElementById("name").value.trim();

        const email =
            document.getElementById("email").value.trim();

        const phone =
            document.getElementById("phone").value.trim();

        const details =
            document.getElementById("details").value.trim();


        // ======================================
        // GENERATE PROCUREMENT ID
        // ======================================

        const procurementId =
            "PR" + Date.now().toString().slice(-10);


        // ======================================
        // CREATE PROCUREMENT RECORD
        // ======================================

        const newProcurement = {

            id: procurementId,

            name: name,

            email: email,

            phone: phone,

            details: details,

            status: "Pending",

            date: new Date().toLocaleDateString(
                "en-US",
                {
                    month: "short",
                    day: "2-digit",
                    year: "numeric"
                }
            )

        };


        // ======================================
        // GET EXISTING PROCUREMENT REQUESTS
        // ======================================

        const existingProcurements =
            JSON.parse(
                localStorage.getItem("procurements")
            ) || [];


        // ======================================
        // ADD NEW PROCUREMENT
        // ======================================

        existingProcurements.push(
            newProcurement
        );


        // ======================================
        // SAVE TO LOCAL STORAGE
        // ======================================

        localStorage.setItem(
            "procurements",
            JSON.stringify(existingProcurements)
        );


        // ======================================
        // SAVE CURRENT PROCUREMENT
        // ======================================

        localStorage.setItem(
            "latestProcurement",
            JSON.stringify(newProcurement)
        );


        // ======================================
        // CONFIRMATION
        // ======================================

        alert(
            `Your procurement request has been submitted successfully.\n\nProcurement ID: ${procurementId}`
        );


        // ======================================
        // GO TO PROCUREMENT HISTORY
        // ======================================

        window.location.href =
            "procurement-history.html";

    });

}