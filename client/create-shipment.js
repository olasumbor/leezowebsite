// ========================================
// Create Shipment (User) - multi-item form
// ========================================

function csEscape(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function appendCsItemRow(data) {
    const tbody = document.getElementById('csItems');
    if (!tbody) return;
    data = data || {};

    const tr = document.createElement('tr');
    tr.className = 'cs-item-row';
    tr.innerHTML = `
        <td><input type="text" class="cs-item-name" value="${csEscape(data.name || '')}" placeholder="e.g. Frozen Tilapia"></td>
        <td><input type="number" min="0" step="any" class="cs-item-quantity" value="${csEscape(data.quantity || '')}"></td>
        <td><input type="number" min="0" step="any" class="cs-item-weight" value="${csEscape(data.weight || '')}"></td>
        <td style="text-align: center;">
            <button type="button" class="btn-remove-item" onclick="removeCsItemRow(this)" title="Remove item">×</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function collectCsItems() {
    const tbody = document.getElementById('csItems');
    if (!tbody) return [];
    const items = [];
    tbody.querySelectorAll('tr.cs-item-row').forEach(row => {
        const name = (row.querySelector('.cs-item-name')?.value || '').trim();
        if (!name) return;
        items.push({
            name: name,
            quantity: (row.querySelector('.cs-item-quantity')?.value || '').trim() || null,
            weight: (row.querySelector('.cs-item-weight')?.value || '').trim() || null,
            // Rate and cost are admin-only; users cannot set them
            rate: null,
            cost: null,
        });
    });
    return items;
}

window.addItemRow = () => appendCsItemRow();
window.removeCsItemRow = (btn) => {
    const row = btn.closest('tr');
    if (row) row.remove();
};

document.addEventListener('DOMContentLoaded', () => {
    // Edit mode support: create-shipment.html?edit=<id or tracking_id>
    const urlParams = new URLSearchParams(window.location.search);
    const editId = urlParams.get('edit');
    let isEditMode = !!editId;

    // Start with one empty item row (will be replaced when prefilling)
    appendCsItemRow();

    if (isEditMode) {
        setupEditMode(editId);
    }

    const form = document.getElementById('createShipmentForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('csSubmitBtn');
            const token = localStorage.getItem('auth_token');

            if (typeof setButtonLoading === 'function') {
                setButtonLoading(submitBtn, true, 'Submitting...');
            }

            try {
                const payload = {
                    origin: document.getElementById('csOrigin').value,
                    destination: document.getElementById('csDestination').value,
                    shipment_type: document.getElementById('csType').value,
                    service: document.getElementById('csService').value,
                    // Recipient details are derived from the authenticated user server-side
                    items: collectCsItems(),
                };

                // In edit mode, block submission if the shipment has been locked
                // (priced or invoiced by admin)
                if (isEditMode) {
                    const locked = await isShipmentLockedForEdit(editId);
                    if (locked) {
                        showToast('This shipment can no longer be edited because it has been priced or invoiced by Leezofood. Please contact support for changes.', 'error');
                        window.location.href = 'shipment-history.html';
                        return;
                    }
                }

                const response = await fetch(`${CONFIG.API_URL}/shipments${isEditMode ? '/' + encodeURIComponent(editId) : ''}`, {
                    method: isEditMode ? 'PUT' : 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify(payload)
                });

                if (response.ok) {
                    const data = await response.json();
                    const msg = data.message || (isEditMode ? 'Shipment updated successfully!' : 'Shipment request submitted successfully!');
                    showToast(msg, 'success');
                    setTimeout(() => {
                        window.location.href = 'shipment-history.html';
                    }, 1200);
                } else {
                    let msg = 'Failed to submit shipment request.';
                    try {
                        const err = await response.json();
                        if (err.message) msg = err.message;
                    } catch (err) { /* ignore */ }
                    showToast(msg, 'error');
                }
            } catch (error) {
                console.error(error);
                showToast('An error occurred. Please try again.', 'error');
            } finally {
                if (typeof setButtonLoading === 'function') {
                    setButtonLoading(submitBtn, false);
                }
            }
        });
    }
});

// ========================================
// EDIT MODE HELPERS
// ========================================

async function fetchShipmentForEdit(id) {
    const token = localStorage.getItem('auth_token');
    const response = await fetch(`${CONFIG.API_URL}/shipments/${encodeURIComponent(id)}`, {
        method: 'GET',
        credentials: 'include',
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    });
    if (!response.ok) return null;
    return await response.json();
}

// Double-check on the server that the shipment is still editable
// (can_edit is false once admin set prices or generated an invoice)
async function isShipmentLockedForEdit(id) {
    const data = await fetchShipmentForEdit(id);
    if (!data) return true;
    return data.can_edit !== true;
}

async function setupEditMode(editId) {
    const pageTitle = document.getElementById('csPageTitle');
    const pageSubtitle = document.getElementById('csPageSubtitle');
    const submitBtn = document.getElementById('csSubmitBtn');

    if (pageTitle) pageTitle.textContent = 'Edit Shipment';
    if (pageSubtitle) pageSubtitle.textContent = 'Update your shipment details below. Editing is locked once the shipment has been priced or invoiced.';
    if (submitBtn) submitBtn.textContent = 'Update Shipment';

    let data = null;
    try {
        data = await fetchShipmentForEdit(editId);
    } catch (error) {
        console.error('Failed to load shipment for editing:', error);
    }

    if (!data) {
        showToast('Could not load the shipment to edit.', 'error');
        setTimeout(() => { window.location.href = 'shipment-history.html'; }, 1500);
        return;
    }

    if (data.can_edit !== true) {
        showToast('This shipment can no longer be edited because it has been priced or invoiced by Leezofood. Please contact support for changes.', 'error');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    // Prefill route & type
    if (document.getElementById('csOrigin')) document.getElementById('csOrigin').value = data.origin || 'Lagos, Nigeria';
    if (document.getElementById('csDestination')) document.getElementById('csDestination').value = data.destination || '';
    if (document.getElementById('csType')) document.getElementById('csType').value = data.shipment_type || '';
    if (document.getElementById('csService')) document.getElementById('csService').value = data.service || '';

    // Prefill items (pricing fields are hidden from users server-side)
    const tbody = document.getElementById('csItems');
    if (tbody) {
        tbody.innerHTML = '';
        const items = Array.isArray(data.items) && data.items.length > 0 ? data.items : [{}];
        items.forEach(item => appendCsItemRow(item));
        if (items.length === 0) appendCsItemRow();
    }
}