// ============================================================
// Procurement multi-item helpers (admin dashboard)
// Supports two independent forms on the same page:
//   - create form  -> container 'createProcurementItemsContainer'
//   - edit modal   -> container 'procurementItemsContainer'
// Every helper is scoped to an explicit container so rows/totals
// in one form never leak into the other.
// ============================================================

function resolveProcurementContext(containerIdOrEl) {
    // Explicit container wins
    if (containerIdOrEl) {
        const container = typeof containerIdOrEl === 'string'
            ? document.getElementById(containerIdOrEl)
            : containerIdOrEl;
        if (container) return buildProcurementContext(container);
    }

    // Otherwise pick the container belonging to the currently open form:
    // prefer the visible edit modal, fall back to the create container.
    const editContainer = document.getElementById('procurementItemsContainer');
    const editModal = document.getElementById('procurementModal');
    const editVisible = editModal && editModal.style.display !== 'none' && editModal.style.display !== '';
    if (editVisible && editContainer) return buildProcurementContext(editContainer);

    const createContainer = document.getElementById('createProcurementItemsContainer');
    if (createContainer) return buildProcurementContext(createContainer);

    if (editContainer) return buildProcurementContext(editContainer);

    return null;
}

function buildProcurementContext(container) {
    const isCreate = container.id === 'createProcurementItemsContainer';
    return {
        container: container,
        totalsDiv: document.getElementById(isCreate ? 'createProcurementTotals' : 'procurementTotals'),
        totalCostId: isCreate ? 'createProcTotalCost' : 'procTotalCost',
        totalShipFeeId: isCreate ? 'createProcTotalShipmentFee' : 'procTotalShipmentFee',
        totalTransportId: isCreate ? 'createProcTotalTransportation' : 'procTotalTransportation',
        grandTotalId: isCreate ? 'createProcGrandTotal' : 'procGrandTotal',
        isCreate: isCreate
    };
}

// Legacy detector kept for backwards compatibility (now visibility-aware)
function getProcurementContainer() {
    return resolveProcurementContext(null);
}

window.addProcurementItemRow = function(item = null, index = null, containerId = null) {
    const context = resolveProcurementContext(containerId);
    if (!context || !context.container) return;

    const container = context.container;
    const rowId = index !== null ? index : Date.now();
    
    const row = document.createElement('div');
    row.className = 'procurement-item-row';
    row.style.cssText = 'display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr 1fr auto; gap: 8px; margin-bottom: 10px; align-items: center;';
    row.dataset.rowId = rowId;
    
    if (item) {
        row.innerHTML = `
            <input type="text" class="proc-item-desc" value="${escapeAttr(item.description || '')}" placeholder="Item Description" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" class="proc-item-category" value="${escapeAttr(item.category || '')}" placeholder="Category" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" class="proc-item-supplier" value="${escapeAttr(item.supplier || '')}" placeholder="Supplier" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-qty" value="${item.quantity ?? ''}" placeholder="Qty" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-weight" value="${item.weight ?? ''}" placeholder="Weight" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-rate" value="${item.rate ?? ''}" placeholder="Rate" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-cost" value="${item.cost ?? ''}" placeholder="Cost" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-ship-fee" value="${item.shipment_fee ?? ''}" placeholder="Ship Fee" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-transport" value="${item.transportation ?? ''}" placeholder="Transport" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="button" data-remove-proc-item style="color: #ef4444; background: none; border: none; cursor: pointer; padding: 8px;">
                <i class="fas fa-trash"></i>
            </button>
        `;
    } else {
        row.innerHTML = `
            <input type="text" class="proc-item-desc" placeholder="Item Description" required style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" class="proc-item-category" placeholder="Category" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="text" class="proc-item-supplier" placeholder="Supplier" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-qty" placeholder="Qty" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-weight" placeholder="Weight" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-rate" placeholder="Rate" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-cost" placeholder="Cost" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-ship-fee" placeholder="Ship Fee" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <input type="number" class="proc-item-transport" placeholder="Transport" step="0.01" min="0" style="padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <button type="button" data-remove-proc-item style="color: #ef4444; background: none; border: none; cursor: pointer; padding: 8px;">
                <i class="fas fa-trash"></i>
            </button>
        `;
    }

    const removeBtn = row.querySelector('[data-remove-proc-item]');
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            window.removeProcurementItemRow(removeBtn);
        });
    }
    
    container.appendChild(row);
    
    // Add event listeners for real-time calculation (scoped to this form)
    const inputs = row.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('input', () => window.calculateProcurementTotals(container));
    });

    window.calculateProcurementTotals(container);
    if (context.totalsDiv) context.totalsDiv.style.display = 'block';
};

// Remove an item row
window.removeProcurementItemRow = function(button) {
    const row = button.closest('.procurement-item-row');
    if (row) {
        const container = row.parentElement;
        row.remove();
        window.calculateProcurementTotals(container);
        const context = container && container.id ? buildProcurementContext(container) : getProcurementContainer();
        if (context && context.container && context.container.children.length === 0 && context.totalsDiv) {
            context.totalsDiv.style.display = 'none';
        }
    }
};

// Calculate totals from the items form (scoped to one container)
window.calculateProcurementTotals = function(containerIdOrEl) {
    const context = resolveProcurementContext(containerIdOrEl);
    if (!context || !context.container) return;

    const rows = context.container.querySelectorAll('.procurement-item-row');
    let totalCost = 0;
    let totalShipmentFee = 0;
    let totalTransportation = 0;

    rows.forEach(row => {
        const cost = parseFloat(row.querySelector('.proc-item-cost')?.value) || 0;
        const shipmentFee = parseFloat(row.querySelector('.proc-item-ship-fee')?.value) || 0;
        const transport = parseFloat(row.querySelector('.proc-item-transport')?.value) || 0;

        totalCost += cost;
        totalShipmentFee += shipmentFee;
        totalTransportation += transport;
    });

    const grandTotal = totalCost + totalShipmentFee + totalTransportation;

    if (context && context.totalsDiv) {
        context.totalsDiv.style.display = 'block';
        document.getElementById(context.totalCostId).textContent = '₦' + totalCost.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById(context.totalShipFeeId).textContent = '₦' + totalShipmentFee.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById(context.totalTransportId).textContent = '₦' + totalTransportation.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById(context.grandTotalId).textContent = '₦' + grandTotal.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
};

// Update totals display from API data (for edit form)
window.updateProcurementTotalsDisplay = function(procurement) {
    const totals = procurement.formatted_totals || {};
    const context = getProcurementContainer();
    if (context && context.totalsDiv) {
        context.totalsDiv.style.display = 'block';
        document.getElementById(context.totalCostId).textContent = totals.total_cost || '₦0.00';
        document.getElementById(context.totalShipFeeId).textContent = totals.total_shipment_fee || '₦0.00';
        document.getElementById(context.totalTransportId).textContent = totals.total_transportation || '₦0.00';
        document.getElementById(context.grandTotalId).textContent = totals.grand_total || '₦0.00';
    }
};

// Add a bare function declaration too, so inline onclick handlers like
// onclick="addProcurementItemRow()" resolve even under strict CSP /
// browser-extension hook environments where window.* lookups can fail.
function addProcurementItemRow(item = null, index = null, containerId = null) {
    return window.addProcurementItemRow(item, index, containerId);
}

// Get items from form (scoped to one container; defaults to the visible form)
window.getProcurementItemsFromForm = function(containerIdOrEl) {
    const context = resolveProcurementContext(containerIdOrEl);
    const rows = context && context.container
        ? context.container.querySelectorAll('.procurement-item-row')
        : document.querySelectorAll('.procurement-item-row');
    const items = [];
    
    rows.forEach(row => {
        const desc = row.querySelector('.proc-item-desc')?.value?.trim();
        if (!desc) return;
        
        items.push({
            description: desc,
            category: row.querySelector('.proc-item-category')?.value?.trim() || null,
            supplier: row.querySelector('.proc-item-supplier')?.value?.trim() || null,
            quantity: parseInt(row.querySelector('.proc-item-qty')?.value) || 0,
            weight: parseFloat(row.querySelector('.proc-item-weight')?.value) || null,
            rate: parseFloat(row.querySelector('.proc-item-rate')?.value) || null,
            cost: parseFloat(row.querySelector('.proc-item-cost')?.value) || null,
            shipment_fee: parseFloat(row.querySelector('.proc-item-ship-fee')?.value) || 0,
            transportation: parseFloat(row.querySelector('.proc-item-transport')?.value) || 0,
        });
    });
    
    return items;
};

// Escape a value for safe use inside an HTML attribute
function escapeAttr(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
