const trackingInput = document.getElementById("tracking-number");
const trackButton = document.getElementById("track-button");
const trackingResult = document.querySelector(".tracking-result");

if (trackButton) {
    trackButton.addEventListener("click", async function () {

        const trackingNumber = trackingInput.value.trim();

        if (trackingNumber === "") {
            showToast("Please enter a tracking number.", "warning");
            return;
        }

        if (typeof setButtonLoading === 'function') {
            setButtonLoading(trackButton, true, "Searching...");
        }

        try {
            const response = await fetch(`${CONFIG.API_URL}/track/${trackingNumber}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const data = await response.json();

                document.getElementById("result-tracking-number").textContent = data.tracking_id;
                
                // Update the status text
                const statusEl = document.getElementById("shipment-status");
                if (statusEl) {
                    statusEl.textContent = data.status ? data.status.replace(/_/g, ' ').toUpperCase() : 'PENDING';
                }

                // Update the status timeline
                updateStatusTimeline(data.status);

                trackingResult.style.display = "block";
                trackingResult.scrollIntoView({
                    behavior: "smooth",
                    block: "start"
                });

            } else {
                showToast("Tracking number not found. Please check the number and try again.", "warning");
                trackingResult.style.display = "none";
            }
        } catch (error) {
            console.error("Error fetching tracking details:", error);
            showToast("An error occurred while tracking the shipment.", "error");
        } finally {
            if (typeof setButtonLoading === 'function') {
                setButtonLoading(trackButton, false);
            }
        }

    });

}

// Helper to update the timeline UI based on current status
function updateStatusTimeline(status) {
    const statusItems = document.querySelectorAll(".tracking-status-item");
    
    // Define the sequence
    const sequence = [
        "Shipment Picked Up",
        "Departed Origin",
        "In Transit",
        "Arrived at Destination",
        "Delivered"
    ];

    const norm = (status || "").toLowerCase().trim();
    let currentIndex = 0;

    if (norm === "delivered" || norm === "completed") {
        currentIndex = 4;
    } else if (norm === "arrived" || norm === "arrived_at_destination") {
        currentIndex = 3;
    } else if (norm === "in_transit" || norm === "in transit") {
        currentIndex = 2;
    } else if (norm === "departed" || norm === "departed_origin") {
        currentIndex = 1;
    } else if (norm === "picked_up" || norm === "processing") {
        currentIndex = 0;
    } else {
        currentIndex = sequence.indexOf(status);
        if (currentIndex === -1) currentIndex = 0;
    }

    statusItems.forEach((item, index) => {
        // Reset classes
        item.classList.remove("completed", "current");
        
        const icon = item.querySelector(".status-icon");
        
        if (index < currentIndex) {
            item.classList.add("completed");
            if (icon) icon.textContent = "✓";
        } else if (index === currentIndex) {
            item.classList.add("current");
            if (icon) icon.textContent = "●";
        } else {
            if (icon) icon.textContent = "○";
        }
    });
}