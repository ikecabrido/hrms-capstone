<div class="medicine-inventory-module module-content">
    <div class="module-header">
        <div>
            <h1>Medicines Inventory</h1>
            <p>Track medicine availability, expirations, and low-stock alerts.</p>
        </div>
        <button type="button" id="addMedicineBtn" class="primary-btn"><i class="fa-solid fa-plus"></i> Add Medicine</button>
    </div>

    <div id="medicineInventoryMessage" class="module-message" role="alert" hidden></div>

    <div class="inventory-summary-grid">
        <div class="summary-card">
            <span class="summary-label">Total Medicines</span>
            <span class="summary-value" id="inventoryTotalCount">0</span>
            <span class="summary-meta">Across all active products</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Low Stock</span>
            <span class="summary-value" id="inventoryLowStockCount">0</span>
            <span class="summary-meta">Needs replenishment</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Expired</span>
            <span class="summary-value" id="inventoryExpiredCount">0</span>
            <span class="summary-meta">Review disposal list</span>
        </div>
        <div class="summary-card">
            <span class="summary-label">Suppliers</span>
            <span class="summary-value" id="inventorySupplierCount">0</span>
            <span class="summary-meta">Current provider network</span>
        </div>
    </div>

    <div class="inventory-alerts">
        <div class="alert-card">
            <h4>Inventory Health</h4>
            <p id="inventoryHealthText">System is ready for monitoring.</p>
        </div>
        <div class="alert-card warning" id="inventoryWarningCard">
            <h4>Follow-up Needed</h4>
            <p id="inventoryWarningText">No urgent stock items at the moment.</p>
        </div>
    </div>

    <div class="toolbar-row">
        <div class="search-box wide">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="medicineSearch" placeholder="Search by ID, name, generic name or category">
        </div>
        <div class="filter-box">
            <label for="medicineStatusFilter" class="sr-only">Filter medicines</label>
            <select id="medicineStatusFilter">
                <option value="all">All Medicines</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="low_stock">Low Stock</option>
                <option value="expired">Expired</option>
                <option value="near_expiration">Near Expiration</option>
            </select>
        </div>
        <button type="button" id="refreshMedicinesBtn" class="ghost-btn"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Medicine ID</th>
                        <th>Medicine Name</th>
                        <th>Generic Name</th>
                        <th>Category</th>
                        <th>Dosage / Strength</th>
                        <th>Stock</th>
                        <th>Expiration</th>
                        <th>Supplier</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="medicineInventoryTableBody">
                    <tr>
                        <td colspan="10" class="empty-state"><p>Loading medicines...</p></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="medicineFormModal" class="modal" hidden>
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 id="medicineModalTitle">Add Medicine</h3>
            <button type="button" id="closeMedicineModal" class="close-btn" aria-label="Close">&times;</button>
        </div>

        <form id="medicineForm" class="patient-form">
            <div class="form-grid">
                <div>
                    <label for="medicine_id">Medicine ID</label>
                    <input id="medicine_id" name="medicine_id" type="text" required>
                </div>
                <div>
                    <label for="medicine_name">Medicine Name</label>
                    <input id="medicine_name" name="medicine_name" type="text" required>
                </div>
                <div>
                    <label for="generic_name">Generic Name</label>
                    <input id="generic_name" name="generic_name" type="text">
                </div>
                <div>
                    <label for="category">Category</label>
                    <input id="category" name="category" type="text">
                </div>
                <div>
                    <label for="dosage_form">Dosage Form</label>
                    <select id="dosage_form" name="dosage_form">
                        <option value="Tablet">Tablet</option>
                        <option value="Capsule">Capsule</option>
                        <option value="Liquid">Liquid</option>
                        <option value="Injection">Injection</option>
                        <option value="Ointment">Ointment</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label for="strength">Strength</label>
                    <input id="strength" name="strength" type="text">
                </div>
                <div>
                    <label for="unit">Unit</label>
                    <input id="unit" name="unit" type="text" value="pcs">
                </div>
                <div>
                    <label for="current_stock">Current Stock</label>
                    <input id="current_stock" name="current_stock" type="number" min="0" value="0">
                </div>
                <div>
                    <label for="reorder_level">Reorder Level</label>
                    <input id="reorder_level" name="reorder_level" type="number" min="0" value="10">
                </div>
                <div>
                    <label for="unit_cost">Unit Cost</label>
                    <input id="unit_cost" name="unit_cost" type="number" step="0.01" min="0">
                </div>
                <div>
                    <label for="selling_price">Selling Price</label>
                    <input id="selling_price" name="selling_price" type="number" step="0.01" min="0">
                </div>
                <div>
                    <label for="expiry_date">Expiration Date</label>
                    <input id="expiry_date" name="expiry_date" type="date">
                </div>
                <div>
                    <label for="supplier">Supplier</label>
                    <select id="medicineSupplier" name="supplier"></select>
                </div>
                <div>
                    <label for="manufacturer">Manufacturer</label>
                    <input id="manufacturer" name="manufacturer" type="text">
                </div>
                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Available">Available</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                        <option value="Expired">Expired</option>
                        <option value="Expiring Soon">Expiring Soon</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="full-width">
                    <label for="storage_requirements">Storage Requirements</label>
                    <textarea id="storage_requirements" name="storage_requirements" rows="3"></textarea>
                </div>
            </div>

            <div class="detail-actions">
                <button type="button" id="saveMedicineBtn" class="primary-btn"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                <button type="button" id="cancelMedicineModal" class="ghost-btn"><i class="fa-solid fa-xmark"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>
