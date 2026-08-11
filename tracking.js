const trackingInput = document.getElementById("tracking-number");
const trackButton = document.getElementById("track-button");
const trackingResult = document.querySelector(".tracking-result");

if (trackButton) {
    trackButton.addEventListener("click", function () {

        const trackingNumber = trackingInput.value.trim();

        if (trackingNumber === "") {
            alert("Please enter a tracking number.");
            return;
        }

        if (trackingNumber.toUpperCase() === "CAR8739020892") {

            document.getElementById("result-tracking-number").textContent =
                trackingNumber.toUpperCase();

            trackingResult.style.display = "block";

            trackingResult.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });

        } else {

            alert("Tracking number not found. Please check the number and try again.");

        }

    });

}