// ==========================================
// FROZEN CARGO FORM HANDLER (multi-item)
// Users manage description / quantity / weight only.
// Rate & cost are admin-only and never sent from this form.
// ==========================================

const frozenCargoForm = document.getElementById("frozenCargoForm");

function buildFrozenItemRow() {
    const row = document.createElement("tr");
    row.className = "frozen-item-row";
    row.innerHTML = `
        <td><input type="text" class="frozen-item-desc" placeholder="e.g. Frozen tilapia, boxed" required></td>
        <td><input type="number" class="frozen-item-qty" min="0" step="1" placeholder="0"></td>
        <td><input type="number" class="frozen-item-weight" min="0" step="0.01" placeholder="0.00"></td>
        <td><button type="button" class="proc-item-remove" title="Remove item" aria-label="Remove item">&times;</button></td>
    `;
    row.querySelector(".proc-item-remove").addEventListener("click", function () {
        row.remove();
    });
    return row;
}

function addFrozenItemRow(item = null) {
    const tbody = document.getElementById("frozenItemsBody");
    if (!tbody) return null;
    const row = buildFrozenItemRow();
    if (item) {
        row.querySelector(".frozen-item-desc").value = item.description || item.name || "";
        row.querySelector(".frozen-item-qty").value = item.quantity ?? "";
        row.querySelector(".frozen-item-weight").value = item.weight ?? "";
    }
    tbody.appendChild(row);
    return row;
}

function collectFrozenItems() {
    const rows = document.querySelectorAll("#frozenItemsBody tr.frozen-item-row");
    const items = [];
    rows.forEach(row => {
        const description = row.querySelector(".frozen-item-desc").value.trim();
        if (!description) return;
        const qtyRaw = row.querySelector(".frozen-item-qty").value;
        const weightRaw = row.querySelector(".frozen-item-weight").value;
        items.push({
            description: description,
            quantity: qtyRaw === "" ? 0 : (parseInt(qtyRaw, 10) || 0),
            weight: weightRaw === "" ? null : (parseFloat(weightRaw) || null),
            // rate/cost intentionally omitted: admin-only
        });
    });
    return items;
}

window.addFrozenItemRow = addFrozenItemRow;

function initFrozenItems() {
    const tbody = document.getElementById("frozenItemsBody");
    if (tbody && tbody.querySelectorAll("tr.frozen-item-row").length === 0) {
        addFrozenItemRow();
    }
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initFrozenItems);
} else {
    initFrozenItems();
}

if (frozenCargoForm) {
    frozenCargoForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const temperature_requirement = document.getElementById("temperature_requirement").value;
        const origin = document.getElementById("origin").value.trim();
        const destination = document.getElementById("destination").value.trim();
        const departure_date = document.getElementById("departure_date").value || null;
        const notes = document.getElementById("notes").value.trim();
        const items = collectFrozenItems();

        if (items.length === 0) {
            showToast("Please add at least one frozen cargo item.", "warning");
            return;
        }

        const submitBtn = frozenCargoForm.querySelector("button[type='submit']") || frozenCargoForm.querySelector("button");
        if (typeof setButtonLoading === 'function' && submitBtn) {
            setButtonLoading(submitBtn, true, "Submitting Request...");
        }

        try {
            const token = localStorage.getItem("auth_token");
            const headers = {
                "Accept": "application/json",
                "Content-Type": "application/json"
            };

            if (token) {
                headers["Authorization"] = `Bearer ${token}`;
            }

            const response = await fetch(`${CONFIG.API_URL}/frozen-cargos`, {
                method: "POST",
                headers: headers,
                body: JSON.stringify({
                    temperature_requirement,
                    origin,
                    destination,
                    departure_date,
                    notes,
                    items
                })
            });

            const data = await response.json();

            if (response.ok) {
                const reqId = data.frozen_cargo?.request_id || "";
                showToast(`Frozen cargo request submitted successfully! Request ID: ${reqId}`, "success");
                frozenCargoForm.reset();
                const tbody = document.getElementById("frozenItemsBody");
                if (tbody) tbody.innerHTML = "";
                addFrozenItemRow();
            } else if (response.status === 422 && data.errors) {
                const firstError = Object.values(data.errors)[0];
                showToast(Array.isArray(firstError) ? firstError[0] : firstError, "error");
            } else {
                showToast(data.message || "Failed to submit frozen cargo request.", "error");
            }
        } catch (error) {
            console.error("Frozen cargo submission error", error);
            showToast("An error occurred during submission. Please try again.", "error");
        } finally {
            if (typeof setButtonLoading === 'function' && submitBtn) {
                setButtonLoading(submitBtn, false);
            }
        }
    });
}
