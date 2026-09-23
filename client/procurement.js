// ============================================================
// PROCUREMENT REQUEST FORM (customer side, multi-item)
// Customers describe the items they want us to procure
// (description, category, supplier, quantity, weight) together
// with their own details and the schedule dates.
//
// Pricing is READ-ONLY for customers: rate, cost, shipment fee,
// transportation and the totals are set by our team and are only
// displayed on the procurement details page.
// ============================================================

const procurementForm = document.getElementById("procurementForm");

// ==========================================
// HELPERS
// ==========================================

function procNumberOrNull(value) {
    if (value === null || value === undefined || String(value).trim() === "") return null;
    const parsed = parseFloat(value);
    return isNaN(parsed) ? null : parsed;
}

// ==========================================
// ITEM ROWS
// ==========================================

function buildProcItemRow() {
    const row = document.createElement("tr");
    row.className = "proc-item-row";

    row.innerHTML = `
        <td><input type="text" class="proc-item-desc" placeholder="e.g. Dried honey beans" required></td>
        <td><input type="text" class="proc-item-category" placeholder="e.g. Foodstuff"></td>
        <td><input type="text" class="proc-item-supplier" placeholder="e.g. Local farm"></td>
        <td><input type="number" class="proc-item-qty" min="0" step="1" placeholder="0"></td>
        <td><input type="number" class="proc-item-weight" min="0" step="0.01" placeholder="0.00"></td>
        <td>
            <button type="button" class="proc-item-remove" title="Remove item" aria-label="Remove item">&times;</button>
        </td>
    `;

    row.querySelector(".proc-item-remove").addEventListener("click", function () {
        removeProcItemRow(row);
    });

    return row;
}

function addProcItemRow(item = null) {
    const tbody = document.getElementById("procItemsBody");
    if (!tbody) return null;

    const row = buildProcItemRow();

    if (item) {
        row.querySelector(".proc-item-desc").value = item.description || "";
        row.querySelector(".proc-item-category").value = item.category || "";
        row.querySelector(".proc-item-supplier").value = item.supplier || "";
        row.querySelector(".proc-item-qty").value = item.quantity || "";
        row.querySelector(".proc-item-weight").value = item.weight || "";
    }

    tbody.appendChild(row);

    return row;
}

function removeProcItemRow(row) {
    if (!row) return;

    row.remove();
}

// Collect items in the shape expected by the API.
// Pricing fields (rate, cost, shipment fee, transportation) are
// intentionally omitted: customers have read-only access to costs.
function collectProcItems() {
    const rows = document.querySelectorAll("#procItemsBody tr.proc-item-row");
    const items = [];

    rows.forEach(row => {
        const description = row.querySelector(".proc-item-desc").value.trim();
        if (!description) return;

        items.push({
            description: description,
            category: row.querySelector(".proc-item-category").value.trim() || null,
            supplier: row.querySelector(".proc-item-supplier").value.trim() || null,
            quantity: parseInt(row.querySelector(".proc-item-qty").value, 10) || 0,
            weight: procNumberOrNull(row.querySelector(".proc-item-weight").value)
        });
    });

    return items;
}

// ==========================================
// PREFILLS
// ==========================================

function setDefaultExpectedDelivery() {
    const expectedDeliveryInput = document.getElementById("expected_delivery");
    if (!expectedDeliveryInput || expectedDeliveryInput.value) return;

    const now = new Date();
    const localDate = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    expectedDeliveryInput.value = localDate.toISOString().slice(0, 10);
}

// Customer details come from the currently signed in customer
async function prefillCustomerDetails() {
    const nameInput = document.getElementById("name");
    const emailInput = document.getElementById("email");
    const phoneInput = document.getElementById("phone");
    const storedEmail = localStorage.getItem("userEmail");

    if (storedEmail && emailInput && !emailInput.value) {
        emailInput.value = storedEmail;
    }

    const token = localStorage.getItem("auth_token");
    if (!token) return;

    try {
        const response = await fetch(`${CONFIG.API_URL}/user`, {
            method: "GET",
            headers: {
                "Accept": "application/json",
                "Authorization": `Bearer ${token}`
            }
        });

        if (!response.ok) return;

        const user = await response.json();

        if (nameInput && user.name) nameInput.value = user.name;
        if (emailInput && user.email) emailInput.value = user.email;
        if (phoneInput && user.phone) phoneInput.value = user.phone;
    } catch (error) {
        console.warn("Could not load customer details for the procurement form.", error);
    }
}

// ==========================================
// FORM INIT + SUBMIT
// ==========================================

function initProcurementForm() {
    if (!procurementForm) return;

    const addItemBtn = document.getElementById("addProcItemBtn");
    if (addItemBtn) {
        addItemBtn.addEventListener("click", function () {
            addProcItemRow();
        });
    }

    setDefaultExpectedDelivery();

    // Start with one empty item row
    addProcItemRow();

    prefillCustomerDetails();

    procurementForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const submitBtn = procurementForm.querySelector("button[type='submit']");

        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const phone = document.getElementById("phone").value.trim();
        // Request date is set automatically by the server (today) and is
        // intentionally not collected from the form.
        const expectedDelivery = document.getElementById("expected_delivery").value || null;
        const items = collectProcItems();

        if (items.length === 0) {
            showToast("Please add at least one procurement item.", "warning");
            return;
        }

        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Submitting Procurement...");
        }

        try {
            const token = localStorage.getItem("auth_token");
            if (!token) {
                showToast("You must be logged in to request a procurement.", "warning");
                setTimeout(() => {
                    window.location.href = "signin.html";
                }, 1000);
                return;
            }

            const response = await fetch(`${CONFIG.API_URL}/procurements`, {
                method: "POST",
                headers: {
                    "Accept": "application/json",
                    "Content-Type": "application/json",
                    "Authorization": `Bearer ${token}`
                },
                body: JSON.stringify({
                    name,
                    email,
                    phone,
                    expected_delivery: expectedDelivery,
                    items
                })
            });

            const data = await response.json();

            if (response.ok) {
                showToast(`Your procurement request has been submitted successfully! Procurement ID: ${data.procurement.procurement_id}`, "success");

                setTimeout(() => {
                    window.location.href = "procurement-history.html";
                }, 1200);
            } else if (response.status === 401) {
                localStorage.removeItem("loggedIn");
                localStorage.removeItem("userEmail");
                localStorage.removeItem("auth_token");
                showToast("Your session has expired or you are not logged in. Redirecting to login...", "warning");
                setTimeout(() => {
                    window.location.href = "signin.html";
                }, 1000);
            } else if (response.status === 422 && data.errors) {
                const firstError = Object.values(data.errors)[0];
                showToast(Array.isArray(firstError) ? firstError[0] : firstError, "error");
            } else {
                showToast(data.message || "Failed to submit procurement request.", "error");
            }
        } catch (error) {
            console.error("Procurement submission error", error);
            showToast("An error occurred during submission.", "error");
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
}

// Expose helpers so other scripts / inline handlers can reuse them
window.addProcItemRow = addProcItemRow;
window.removeProcItemRow = removeProcItemRow;

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initProcurementForm);
} else {
    initProcurementForm();
}
