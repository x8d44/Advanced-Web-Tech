/**
 * ajax.js - AJAX Utility Functions
 *
 * Provides reusable functions for making AJAX requests and handling form submissions.
 * Used throughout the application for asynchronous communication with the server.
 */

// Function to make AJAX requests
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        xhr.open(method, url, true);
        
        if (method.toUpperCase() === 'POST' && !(data instanceof FormData)) {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }
        
        xhr.onload = function() {
            if (this.status >= 200 && this.status < 300) {
                try {
                    const response = JSON.parse(this.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(this.responseText);
                }
            } else {
                reject({
                    status: this.status,
                    statusText: this.statusText,
                    response: this.responseText
                });
            }
        };
        
        xhr.onerror = function() {
            reject({
                status: this.status,
                statusText: 'Network Error',
                response: null
            });
        };
        
        if (data instanceof FormData) {
            xhr.send(data);
        } else if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    });
}

// Function to handle form submissions via AJAX
function submitFormAjax(form, callback) {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(form);
        const url = form.getAttribute('action');
        const method = form.getAttribute('method') || 'POST';
        
        ajaxRequest(url, method, formData)
            .then(response => {
                if (callback) {
                    callback(null, response);
                }
            })
            .catch(error => {
                if (callback) {
                    callback(error);
                }
            });
    });
}