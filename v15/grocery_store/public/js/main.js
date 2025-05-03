/**
 * main.js - Main Application JavaScript
 *
 * Contains general functionality used throughout the application
 * including Bootstrap initialization and UI enhancements.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips everywhere
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Flash messages auto-close after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Handle mobile cart link differently
    const cartLink = document.getElementById('cartDropdown');
    if (cartLink) {
        cartLink.addEventListener('click', function(e) {
            // Check if we're in mobile view (under 992px for Bootstrap's lg breakpoint)
            if (window.innerWidth < 992) {
                e.preventDefault();
                e.stopPropagation();
                window.location.href = window.APP_URL + '/cart';
            }
        });
    }
});