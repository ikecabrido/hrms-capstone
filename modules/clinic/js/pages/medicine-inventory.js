function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
}

function showModuleMessage(message, type = 'success') {
    const box = document.querySelector('#medicineInventoryMessage');
    if (!box) return;
    box.textContent = message;
    box.className = 'module-message ' + (type === 'error' ? 'error' : 'success');
    box.hidden = false;
}

function clearModuleMessage() {
    const box = document.querySelector('#medicineInventoryMessage');
    if (!box) return;
    box.textContent = '';
    box.hidden = true;
    box.className = 'module-message';
}

async function medicineRequest(params, method = 'GET') {
    const queryParams = new URLSearchParams(params);
    const url = method === 'GET' ? `medicine-inventory-data.php?${queryParams.toString()}` : 'medicine-inventory-data.php';
    const options = {
        method,
        credentials: 'same-origin',
        cache: 'no-store',
    };

    if (method === 'POST') {
        options.headers = { 'Content-Type': 'application/json' };
        options.body = JSON.stringify(params);
    }

    const response = await fetch(url, options);
    const rawText = await response.text();
    let data;
    try {
        data = JSON.parse(rawText);
    } catch (parseErr) {
        const preview = (rawText || '').trim().slice(0, 500);
        console.error('[JSON-GATEKEEPER-V2] Non-JSON response from server. First 500 chars:', preview);
        const looksLikeHtml = (rawText || '').trim().startsWith('<');
        const friendlyMsg = looksLikeHtml
            ? 'Temporary server issue — please refresh the page. If the problem persists, contact your administrator or check the server error log.'
            : (parseErr.message || 'Invalid response from server.');
        throw new Error(friendlyMsg);
    }
    if (!response.ok || !data.success) {
        throw new Error(data.message || 'Request could not be completed.');
    }
    return data;
}

function getStatusBadgeClass(status) {
    const normalized = String(status || 'Available').toLowerCase();
    if (normalized.includes('expired')) return 'danger';
    if (normalized.includes('low')) return 'warning';
    if (normalized.includes('inactive')) return 'neutral';
    if (normalized.includes('out')) return 'danger';
    if (normalized.includes('expiring')) return 'warning';
    return 'success';
}

function getExpiryDateClass(expiryDate) {
    if (!expiryDate || expiryDate === 'N/A') return '';
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const expiry = new Date(expiryDate);
    expiry.setHours(0, 0, 0, 0);
    
    const daysUntilExpiry = Math.floor((expiry - today) / (1000 * 60 * 60 * 24));
    
    if (daysUntilExpiry < 0) return 'expired';
    if (daysUntilExpiry <= 30) return 'warning';
    return '';
}

function getStockBadgeClass(currentStock, reorderLevel) {
    const stock = Number(currentStock ?? 0);
    const reorder = Number(reorderLevel ?? 10);
    
    if (stock === 0) return 'critical';
    if (stock <= reorder) return 'low';
    return '';
}

function buildMedicineRow(item) {
    const stockClass = getStockBadgeClass(item.current_stock, item.reorder_level);
    const expiryClass = getExpiryDateClass(item.expiry_date);
    const statusClass = getStatusBadgeClass(item.status);
    
    const stockBadgeClass = stockClass ? ` stock-badge ${stockClass}` : '';
    const expiryDateClass = expiryClass ? ` expiry-date ${expiryClass}` : ' expiry-date';
    
    return `
        <tr data-medicine-id="${escapeHtml(item.medicine_id)}">
            <td class="medicine-id" title="${escapeHtml(item.medicine_id)}">${escapeHtml(item.medicine_id)}</td>
            <td class="medicine-name" title="${escapeHtml(item.medicine_name)}">${escapeHtml(item.medicine_name)}</td>
            <td class="generic-name" title="${escapeHtml(item.generic_name || 'N/A')}">${escapeHtml(item.generic_name || 'N/A')}</td>
            <td class="category">${escapeHtml(item.category || 'N/A')}</td>
            <td class="dosage">${escapeHtml(item.dosage_form || 'N/A')} ${escapeHtml(item.strength || '')}</td>
            <td class="stock"><span class="${stockBadgeClass}">${escapeHtml(item.current_stock ?? 0)} <i style="font-size: 0.75rem;">${escapeHtml(item.unit || 'pcs')}</i></span></td>
            <td class="expiry"><span class="${expiryDateClass}">${escapeHtml(item.expiry_date || 'N/A')}</span></td>
            <td class="supplier" title="${escapeHtml(item.supplier || 'N/A')}">${escapeHtml(item.supplier || 'N/A')}</td>
            <td class="status"><span class="state-badge ${statusClass}">${escapeHtml(item.status || 'Available')}</span></td>
        </tr>\n    `;
}

function renderMedicineTable(items) {
    const tbody = document.querySelector('#medicineInventoryTableBody');
    if (!tbody) return;

    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="9" class="empty-state"><p>No medicines match the current search or filter.</p></td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(buildMedicineRow).join('');
}

function renderInventorySummary(items) {
    const totalCount = document.querySelector('#inventoryTotalCount');
    const lowStockCount = document.querySelector('#inventoryLowStockCount');
    const expiredCount = document.querySelector('#inventoryExpiredCount');
    const supplierCount = document.querySelector('#inventorySupplierCount');
    const healthText = document.querySelector('#inventoryHealthText');
    const warningText = document.querySelector('#inventoryWarningText');

    if (!totalCount || !lowStockCount || !expiredCount || !supplierCount) return;

    const normalized = Array.isArray(items) ? items : [];
    const lowStock = normalized.filter(item => Number(item.current_stock ?? 0) <= Number(item.reorder_level ?? 0)).length;
    const expired = normalized.filter(item => {
        const value = String(item.status || '').toLowerCase();
        return value.includes('expired') || value.includes('expiring');
    }).length;
    const suppliers = new Set(
        normalized
            .map(item => String(item.supplier || '').trim())
            .filter(Boolean)
    );

    totalCount.textContent = String(normalized.length);
    lowStockCount.textContent = String(lowStock);
    expiredCount.textContent = String(expired);
    supplierCount.textContent = String(suppliers.size);

    if (healthText) {
        if (lowStock === 0 && expired === 0) {
            healthText.textContent = 'Inventory is stable and within normal stock levels.';
        } else if (lowStock > 0 && expired > 0) {
            healthText.textContent = 'Multiple items need attention for restocking and expiration review.';
        } else if (lowStock > 0) {
            healthText.textContent = 'Several medicines require stock replenishment.';
        } else {
            healthText.textContent = 'Review the expiration list for upcoming action.';
        }
    }

    if (warningText) {
        if (lowStock > 0 || expired > 0) {
            warningText.textContent = `${lowStock} item(s) require restocking and ${expired} item(s) need expiry review.`;
        } else {
            warningText.textContent = 'No urgent stock items at the moment.';
        }
    }
}

async function loadMedicines() {
    const search = document.querySelector('#medicineSearch')?.value.trim() || '';
    const filter = document.querySelector('#medicineStatusFilter')?.value || 'all';
    const data = await medicineRequest({ action: 'list', search, filter }, 'GET');
    const items = data.items || [];
    renderMedicineTable(items);
    renderInventorySummary(items);
}

async function loadSuppliers() {
    const select = document.querySelector('#medicineSupplier');
    if (!select) return;

    const data = await medicineRequest({ action: 'suppliers' }, 'GET');
    const currentValue = select.dataset.value || '';
    const options = ['<option value="">Select supplier</option>']
        .concat((data.suppliers || []).map(supplier => `<option value="${escapeHtml(supplier.supplier_name)}">${escapeHtml(supplier.supplier_name)}</option>`));
    select.innerHTML = options.join('');
    if (currentValue) {
        select.value = currentValue;
    }
}

function openMedicineModal(mode = 'add', medicine = null) {
    const modal = document.querySelector('#medicineFormModal');
    const form = document.querySelector('#medicineForm');
    const title = document.querySelector('#medicineModalTitle');
    if (!modal || !form || !title) return;

    const isEdit = mode === 'edit' && medicine;
    title.textContent = isEdit ? 'Edit Medicine' : 'Add Medicine';
    form.dataset.mode = mode;
    form.reset();

    const fields = {
        medicine_id: medicine?.medicine_id || '',
        medicine_name: medicine?.medicine_name || '',
        generic_name: medicine?.generic_name || '',
        category: medicine?.category || '',
        dosage_form: medicine?.dosage_form || 'Tablet',
        strength: medicine?.strength || '',
        unit: medicine?.unit || 'pcs',
        current_stock: medicine?.current_stock ?? 0,
        reorder_level: medicine?.reorder_level ?? 10,
        unit_cost: medicine?.unit_cost ?? '',
        selling_price: medicine?.selling_price ?? '',
        expiry_date: medicine?.expiry_date || '',
        supplier: medicine?.supplier || '',
        manufacturer: medicine?.manufacturer || '',
        storage_requirements: medicine?.storage_requirements || '',
        status: medicine?.status || 'Available',
    };

    Object.entries(fields).forEach(([key, value]) => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) field.value = value;
    });

    if (isEdit) {
        form.querySelector('[name="medicine_id"]').readOnly = true;
    } else {
        form.querySelector('[name="medicine_id"]').readOnly = false;
    }

    modal.hidden = false;
}

function closeMedicineModal() {
    const modal = document.querySelector('#medicineFormModal');
    if (!modal) return;
    modal.hidden = true;
    const form = document.querySelector('#medicineForm');
    if (form) form.reset();
    clearModuleMessage();
}

async function submitMedicineForm() {
    const form = document.querySelector('#medicineForm');
    if (!form) return;

    const payload = Object.fromEntries(new FormData(form).entries());
    if (!payload.medicine_id || !payload.medicine_name) {
        showModuleMessage('Medicine ID and medicine name are required.', 'error');
        return;
    }

    const response = await medicineRequest({ action: 'save', ...payload }, 'POST');
    showModuleMessage(response.message || 'Medicine saved successfully.');
    closeMedicineModal();
    await loadMedicines();
}

async function deactivateMedicine(medicineId) {
    if (!medicineId) {
        showModuleMessage('Please select a medicine to deactivate.', 'error');
        return;
    }

    const confirmed = window.confirm('Deactivate this medicine? It will remain in the database but be marked inactive.');
    if (!confirmed) {
        return;
    }

    const response = await medicineRequest({ action: 'deactivate', medicine_id: medicineId }, 'POST');
    showModuleMessage(response.message || 'Medicine deactivated successfully.');
    await loadMedicines();
}

async function updateMedicineStock(medicineId) {
    if (!medicineId) return;

    const currentValue = prompt('Enter the new stock quantity for this medicine:', '0');
    if (currentValue === null) return;

    const quantity = Number(currentValue);
    if (!Number.isInteger(quantity) || quantity < 0) {
        showModuleMessage('Stock value must be a non-negative integer.', 'error');
        return;
    }

    const response = await medicineRequest({ action: 'stock', medicine_id: medicineId, current_stock: quantity }, 'POST');
    showModuleMessage(response.message || 'Medicine stock updated successfully.');
    await loadMedicines();
}

export function initMedicineInventory() {
    const root = document.querySelector('.medicine-inventory-module');
    if (!root || root.dataset.initialized === 'true') return;
    root.dataset.initialized = 'true';

    const addMedicineButton = document.querySelector('#addMedicineBtn');
    const closeModalButton = document.querySelector('#closeMedicineModal');
    const cancelModalButton = document.querySelector('#cancelMedicineModal');
    const saveMedicineButton = document.querySelector('#saveMedicineBtn');
    const searchInput = document.querySelector('#medicineSearch');
    const filterSelect = document.querySelector('#medicineStatusFilter');
    const refreshButton = document.querySelector('#refreshMedicinesBtn');

    if (addMedicineButton) {
        addMedicineButton.addEventListener('click', () => openMedicineModal('add'));
    }

    if (closeModalButton) {
        closeModalButton.addEventListener('click', closeMedicineModal);
    }

    if (cancelModalButton) {
        cancelModalButton.addEventListener('click', closeMedicineModal);
    }

    if (saveMedicineButton) {
        saveMedicineButton.addEventListener('click', () => submitMedicineForm().catch(error => showModuleMessage(error.message, 'error')));
    }

    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => loadMedicines().catch(error => showModuleMessage(error.message, 'error')), 250);
        });
    }

    if (filterSelect) {
        filterSelect.addEventListener('change', () => loadMedicines().catch(error => showModuleMessage(error.message, 'error')));
    }

    if (refreshButton) {
        refreshButton.addEventListener('click', () => loadMedicines().catch(error => showModuleMessage(error.message, 'error')));
    }

    document.body.addEventListener('click', event => {
        const stockButton = event.target.closest('.stock-medicine');
        if (stockButton) {
            updateMedicineStock(stockButton.dataset.medicineId).catch(error => showModuleMessage(error.message, 'error'));
            return;
        }
    });

    loadSuppliers().catch(error => showModuleMessage(error.message, 'error'));
    loadMedicines().catch(error => showModuleMessage(error.message, 'error'));
}

document.addEventListener('DOMContentLoaded', () => initMedicineInventory());
window.addEventListener('page:loaded', () => initMedicineInventory());
