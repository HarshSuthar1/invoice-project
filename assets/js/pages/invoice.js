/**
 * Invoice Management System - Logic for Creating Invoices
 * Handles dynamic rows, real-time math, and secure data submission.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initial Setup
    fetchClients();
    fetchNextInvoiceNumber();
    
    // Add the first row automatically
    addNewRow();

    // Event Listeners
    document.getElementById('addRowBtn').addEventListener('click', addNewRow);
    document.getElementById('invoiceForm').addEventListener('submit', handleInvoiceSubmit);
});

/**
 * Adds a new item row to the invoice table
 */
function addNewRow() {
    const tbody = document.getElementById('itemsBody');
    const rowId = Date.now(); // Unique ID for finding this specific row
    
    const tr = document.createElement('tr');
    tr.setAttribute('data-id', rowId);
    tr.innerHTML = `
        <td>
            <input type="text" class="item-desc" placeholder="Item description..." required>
        </td>
        <td>
            <input type="number" class="item-qty" value="1" min="1" step="any" required>
        </td>
        <td>
            <input type="number" class="item-price" value="0.00" min="0" step="0.01" required>
        </td>
        <td>
            <span class="row-total">₹0.00</span>
        </td>
        <td>
            <button type="button" class="remove-btn" onclick="removeRow(this)">×</button>
        </td>
    `;

    tbody.appendChild(tr);

    // Add listeners to new inputs to trigger calculations
    const qtyInput = tr.querySelector('.item-qty');
    const priceInput = tr.querySelector('.item-price');

    qtyInput.addEventListener('input', calculateTotals);
    priceInput.addEventListener('input', calculateTotals);
    
    calculateTotals(); // Initial calculation for the new row
}

/**
 * Removes a row and updates totals
 */
function removeRow(button) {
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows.length > 1) {
        button.closest('tr').remove();
        calculateTotals();
    } else {
        alert("An invoice must have at least one item.");
    }
}

/**
 * Calculates row totals and the grand total for the UI
 */
function calculateTotals() {
    let grandTotal = 0;
    const rows = document.querySelectorAll('#itemsBody tr');

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const price = parseFloat(row.querySelector('.item-price').value) || 0;
        const total = qty * price;

        row.querySelector('.row-total').textContent = `₹${total.toFixed(2)}`;
        grandTotal += total;
    });

    // Update Display
    document.getElementById('displayGrandTotal').textContent = `₹${grandTotal.toFixed(2)}`;
    
    // Set hidden input (for reference, though PHP will recalculate)
    const grandTotalInput = document.getElementById('grandTotalInput');
    if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);
}

/**
 * Fetches clients from the API to populate the dropdown
 */
async function fetchClients() {
    try {
        const response = await fetch('/Business project/api/clients/get_clients.php');
        const clients = await response.json();
        const select = document.getElementById('clientSelect');

        clients.forEach(client => {
            const opt = document.createElement('option');
            opt.value = client.id;
            opt.textContent = client.name;
            select.appendChild(opt);
        });
    } catch (err) {
        console.error("Failed to load clients:", err);
    }
}

/**
 * Fetches the next logical invoice number
 */
async function fetchNextInvoiceNumber() {
    try {
        const response = await fetch('/Business project/api/invoices/get_next_invoice.php');
        const data = await response.json();
        document.getElementById('invoiceNumberInput').value = data.next_number;
    } catch (err) {
        console.error("Failed to fetch invoice number:", err);
    }
}

/**
 * Final Submission Logic
 */
async function handleInvoiceSubmit(e) {
    e.preventDefault();

    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = "Saving...";

    const items = [];
    const rows = document.querySelectorAll('#itemsBody tr');

    // Build the items array
    rows.forEach(row => {
        items.push({
            description: row.querySelector('.item-desc').value,
            quantity: parseFloat(row.querySelector('.item-qty').value),
            price: parseFloat(row.querySelector('.item-price').value)
        });
    });

    const formData = new FormData(e.target);
    
    // Pack items into a JSON string to match the PHP script's expectation
    formData.append('items', JSON.stringify(items));

    try {
        const response = await fetch('/Business project/api/invoices/save_invoice.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert('Invoice Saved Successfully!');
            window.location.href = '/Business project/public/index.php?page=manage-invoice';
        } else {
            alert('Error: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.textContent = "Save & Generate Invoice";
        }
    } catch (error) {
        console.error('Submission failed:', error);
        alert('Critical error while saving.');
        submitBtn.disabled = false;
    }
}