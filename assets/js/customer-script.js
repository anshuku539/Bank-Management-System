// Customer Portal JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeComponents();
});

function initializeComponents() {
    // Add any initialization code here
}

// Format currency
function formatCurrency(amount) {
    return '₹ ' + parseFloat(amount).toFixed(2);
}

// Show success message
function showSuccess(message) {
    showAlert(message, 'success');
}

// Show error message
function showError(message) {
    showAlert(message, 'danger');
}

// Generic alert function
function showAlert(message, type) {
    const alertClass = 'alert-' + type;
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    const alertContainer = document.querySelector('.page-content');
    const alertDiv = document.createElement('div');
    alertDiv.innerHTML = alertHtml;
    alertContainer.insertBefore(alertDiv.firstElementChild, alertContainer.firstChild);
}

// Confirm transaction
function confirmTransaction(message) {
    return confirm(message || 'Are you sure you want to proceed?');
}

// Update balance display
function updateBalance() {
    // Balance update logic handled in page-specific scripts
}

// Print statement
function printStatement() {
    window.print();
}
