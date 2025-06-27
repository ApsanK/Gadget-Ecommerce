document.addEventListener('DOMContentLoaded', () => {
    const baseUrl = window.baseUrl || '/ecommerce/';

    const sidebar = document.querySelector('.sidebar');
    const sidebarToggle = document.querySelector('.sidebar-toggle');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target) && window.innerWidth <= 992) {
                sidebar.classList.remove('active');
            }
        });
    }

    // Function to refresh a single row
    function refreshOrderRow(orderId, status) {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `${baseUrl}admin/view/view_orders.php?action=get_order_row&id=${encodeURIComponent(orderId)}`, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                        if (row) {
                            row.outerHTML = response.row_html;
                        } else {
                            const tbody = document.querySelector('.admin-table tbody');
                            if (tbody) {
                                tbody.insertAdjacentHTML('beforeend', response.row_html);
                            }
                        }
                    } else {
                        console.log('Error refreshing row:', response.message);
                    }
                } catch (e) {
                    console.log('Parse error refreshing row:', e.message);
                }
            }
        };
        xhr.send();
    }

    window.toggleStatusDropdown = function(orderId) {
        document.querySelectorAll('.status-dropdown').forEach(dropdown => {
            dropdown.style.display = 'none';
        });

        const dropdown = document.getElementById(`status-dropdown-${orderId}`);
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        } else {
            console.log('Dropdown not found for orderId:', orderId);
        }
    };

    window.updateOrderStatus = function(orderId) {
        console.log('Updating status for orderId:', orderId);
        const select = document.querySelector(`#status-dropdown-${orderId} .status-select`);
        const status = select ? select.value : null;

        if (!orderId || !status) {
            alert('Error: Invalid order or status');
            return;
        }

        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../admin/view/view_orders.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            console.log('Raw response:', xhr.responseText, 'Status:', xhr.status);
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
                    if (row) {
                        const statusBadge = row.querySelector('.status-badge');
                        if (statusBadge) {
                            statusBadge.className = `status-badge status-${status.toLowerCase()}`;
                            statusBadge.textContent = response.status;
                            document.getElementById(`status-dropdown-${orderId}`).style.display = 'none';
                            alert(response.message);
                        } else {
                            console.log('Error: .status-badge not found in row for orderId:', orderId);
                            alert('Status updated but UI could not be refreshed');
                        }
                    } else {
                        console.log('Error: Row not found for orderId:', orderId);
                        refreshOrderRow(orderId, status);
                        alert('Status updated; refreshing table row');
                    }
                } else {
                    alert(response.message);
                }
            } catch (e) {
                console.log('Parse error:', e.message);
                alert('Error updating status');
            }
        };
        xhr.send(`order_id=${encodeURIComponent(orderId)}&status=${encodeURIComponent(status)}`);
    };

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.view-order-edit') && !e.target.closest('.status-dropdown')) {
            document.querySelectorAll('.status-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
});