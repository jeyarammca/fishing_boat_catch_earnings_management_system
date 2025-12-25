// Fishing Boat Management System JavaScript

document.addEventListener('DOMContentLoaded', function () {
    // Activate Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize date pickers with today's date as minimum
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = new Date().toISOString().split('T')[0];
        }
    });
});

// Format currency
function formatCurrency(value) {
    var symbol = window.CURRENCY_SYMBOL || '₹';
    return symbol + parseFloat(value).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Format number to 2 decimal places
function formatNumber(num) {
    return parseFloat(num).toFixed(2);
}

// Calculate total dynamically
function calculateTotal(quantitySelector, rateSelector, totalSelector) {
    const quantityInput = document.querySelector(quantitySelector);
    const rateInput = document.querySelector(rateSelector);
    const totalInput = document.querySelector(totalSelector);

    if (quantityInput && rateInput && totalInput) {
        quantityInput.addEventListener('change', updateTotal);
        rateInput.addEventListener('change', updateTotal);

        function updateTotal() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const rate = parseFloat(rateInput.value) || 0;
            totalInput.value = formatNumber(quantity * rate);
        }
    }
}

// Confirm delete action
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this record?');
}

// Show loading indicator
function showLoader(elementSelector) {
    const element = document.querySelector(elementSelector);
    if (element) {
        element.innerHTML = '<div class="spinner"></div> Loading...';
    }
}

// Hide loading indicator
function hideLoader(elementSelector) {
    const element = document.querySelector(elementSelector);
    if (element) {
        element.innerHTML = '';
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    let rows = table.querySelectorAll('tr');

    rows.forEach(row => {
        let csvRow = [];
        let cols = row.querySelectorAll('td, th');
        cols.forEach(col => {
            csvRow.push('"' + col.innerText + '"');
        });
        csv.push(csvRow.join(','));
    });

    downloadCSV(csv.join('\n'), filename);
}

// Download CSV file
function downloadCSV(csv, filename) {
    const csvFile = new Blob([csv], { type: 'text/csv' });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(csvFile);
    downloadLink.download = filename + '.csv';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Real-time search in tables
function filterTable(inputSelector, tableSelector) {
    const input = document.querySelector(inputSelector);
    const table = document.querySelector(tableSelector);

    if (!input || !table) return;

    input.addEventListener('keyup', function () {
        const filter = this.value.toUpperCase();
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const text = row.innerText.toUpperCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Validate form fields
function validateForm(formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return true;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Toggle password visibility
function togglePasswordVisibility(inputSelector, toggleSelector) {
    const input = document.querySelector(inputSelector);
    const toggle = document.querySelector(toggleSelector);

    if (!input || !toggle) return;

    toggle.addEventListener('click', function () {
        input.type = input.type === 'password' ? 'text' : 'password';
        toggle.classList.toggle('fa-eye');
        toggle.classList.toggle('fa-eye-slash');
    });
}

// AJAX request helper
async function makeRequest(url, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        return await response.json();
    } catch (error) {
        console.error('Request failed:', error);
        return null;
    }
}

// Add active class to current navigation link
function setActiveNavLink() {
    const currentLocation = location.pathname.split('/').pop();
    const links = document.querySelectorAll('.nav-link, .dropdown-item');

    links.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === currentLocation ||
            link.getAttribute('href').includes(currentLocation.replace('.php', ''))) {
            link.classList.add('active');
        }
    });
}

setActiveNavLink();

// Format currency display
function formatCurrencyDisplay(value) {
    var symbol = window.CURRENCY_SYMBOL || '₹';
    return symbol + parseFloat(value).toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Debounce function for search inputs
function debounce(func, delay = 300) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}
