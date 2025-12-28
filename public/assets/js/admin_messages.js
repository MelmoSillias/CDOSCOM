// admin_messages.js
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let pageSize = 25;
    let allMessages = [];
    let filteredMessages = [];

    const tableBody = document.querySelector('#messages-table tbody');
    const paginationContainer = document.querySelector('#pagination-container');

    // Load messages
    function loadMessages() {
        const type = document.querySelector('#filter-type').value;
        const status = document.querySelector('#filter-status').value;
        const dateStart = document.querySelector('#filter-date-start').value;
        const dateEnd = document.querySelector('#filter-date-end').value;

        let url = '/api/messages?';
        if (type) url += 'type=' + encodeURIComponent(type) + '&';
        if (status) url += 'status=' + encodeURIComponent(status) + '&';
        if (dateStart) url += 'date_start=' + encodeURIComponent(dateStart) + '&';
        if (dateEnd) url += 'date_end=' + encodeURIComponent(dateEnd) + '&';
        url = url.slice(0, -1); // Remove trailing &

        fetch(url)
            .then(response => response.json())
            .then(data => {
                allMessages = data.data || [];
                filteredMessages = data.data || [];
                renderTable();
                renderPagination();
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    // Render table rows
    function renderTable() {
        const start = (currentPage - 1) * pageSize;
        const end = start + pageSize;
        const messagesToShow = filteredMessages.slice(start, end);

        tableBody.innerHTML = '';

        messagesToShow.forEach(message => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 transition-colors duration-150';

            const statusBadge = getStatusBadge(message.status);

            row.innerHTML = `
                <td class="px-4 md:px-8 py-5 font-medium text-gray-900">${message.id}</td>
                <td class="px-4 md:px-8 py-5 text-gray-800">${(message.firstName || '') + ' ' + (message.lastName || '')}</td>
                <td class="px-4 md:px-8 py-5 text-gray-700 hidden sm:table-cell">${message.email}</td>
                <td class="px-4 md:px-8 py-5 text-gray-700 hidden md:table-cell">${message.phone || ''}</td>
                <td class="px-4 md:px-8 py-5 text-gray-700 capitalize">${message.type}</td>
                <td class="px-4 md:px-8 py-5">${statusBadge}</td>
                <td class="px-4 md:px-8 py-5 text-gray-600 hidden lg:table-cell">${message.createdAt}</td>
                <td class="px-4 md:px-8 py-5">${getActionsHtml(message)}</td>
            `;

            tableBody.appendChild(row);
        });
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        if (status === 'unread') {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-red-100 text-red-800 rounded-full border border-red-200">Non lu</span>';
        } else if (status === 'read') {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full border border-yellow-200">Vu non répondu</span>';
        } else {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-green-100 text-green-800 rounded-full border border-green-200">Répondu</span>';
        }
    }

    // Get actions HTML
    function getActionsHtml(message) {
        let actions = `
            <div class="flex space-x-2">
                <button class="mark-read-btn bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Marquer comme vu et répondre">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>
                <button class="view-details-btn bg-gray-500 hover:bg-gray-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Voir les détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>`;

        if (message.status !== 'responded') {
            actions += `
                <button class="mark-responded-btn bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Marquer comme répondu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>`;
        }

        actions += `
                <button class="delete-btn bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Supprimer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>`;

        return actions;
    }

    // Render pagination
    function renderPagination() {
        const totalPages = Math.ceil(filteredMessages.length / pageSize);

        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let paginationHtml = '<div class="flex justify-center items-center space-x-2 mt-8">';

        // Previous button
        if (currentPage > 1) {
            paginationHtml += `<button class="pagination-btn bg-white hover:bg-blue-50 text-blue-600 border border-blue-300 px-4 py-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md font-medium" data-page="${currentPage - 1}">← Précédent</button>`;
        }

        // Page numbers
        for (let i = Math.max(1, currentPage - 2); i <= Math.min(totalPages, currentPage + 2); i++) {
            const activeClass = i === currentPage 
                ? 'bg-blue-600 text-white border-blue-600' 
                : 'bg-white hover:bg-blue-50 text-blue-600 border-blue-300';
            paginationHtml += `<button class="pagination-btn ${activeClass} border px-4 py-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md font-medium" data-page="${i}">${i}</button>`;
        }

        // Next button
        if (currentPage < totalPages) {
            paginationHtml += `<button class="pagination-btn bg-white hover:bg-blue-50 text-blue-600 border border-blue-300 px-4 py-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md font-medium" data-page="${currentPage + 1}">Suivant →</button>`;
        }

        paginationHtml += '</div>';
        paginationContainer.innerHTML = paginationHtml;

        // Add event listeners to pagination buttons
        document.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                currentPage = parseInt(this.dataset.page);
                renderTable();
                renderPagination();
            });
        });
    }

    // Event listeners
    document.querySelector('#apply-filters').addEventListener('click', function() {
        currentPage = 1;
        loadMessages();
    });

    document.querySelector('#reset-filters').addEventListener('click', function() {
        document.querySelector('#filter-type').value = '';
        document.querySelector('#filter-status').value = '';
        document.querySelector('#filter-date-start').value = '';
        document.querySelector('#filter-date-end').value = '';
        currentPage = 1;
        loadMessages();
    });

    // Mark as read and open reply modal
    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.mark-read-btn')) {
            const id = e.target.closest('.mark-read-btn').dataset.id;

            fetch(`/api/messages/${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'read' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    loadMessageForReply(id);
                } else {
                    showNotification('Erreur lors de la mise à jour', 'error');
                }
            });
        }
    });

    function loadMessageForReply(id) {
        fetch(`/api/messages/${id}`)
        .then(response => response.json())
        .then(messageData => {
            if (messageData.id) {
                const details = `
                    <div class="border-b pb-3 mb-3">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div><strong>De:</strong> ${messageData.firstName || ''} ${messageData.lastName || ''}</div>
                            <div><strong>Email:</strong> ${messageData.email}</div>
                            <div><strong>Téléphone:</strong> ${messageData.phone || 'N/A'}</div>
                            <div><strong>Type:</strong> ${messageData.type}</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Sujet:</strong> ${messageData.subject || 'N/A'}
                    </div>
                    <div>
                        <strong>Message:</strong>
                        <div class="mt-2 p-3 bg-gray-100 rounded text-sm">${messageData.content}</div>
                    </div>
                `;
                document.querySelector('#reply-message-details').innerHTML = details;
                document.querySelector('#reply-subject').value = 'Re: ' + (messageData.subject || '');
                document.querySelector('#reply-modal').classList.remove('hidden');
                loadMessages();
                showNotification('Message marqué comme vu', 'success');
            }
        });
    }

    // View details
    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.view-details-btn')) {
            const id = e.target.closest('.view-details-btn').dataset.id;

            fetch(`/api/messages/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.id) {
                    fetch(`/api/messages/${id}`, {
                        method: 'PATCH',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'read' })
                    });

                    const details = `
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><strong>ID:</strong> ${data.id}</div>
                            <div><strong>Type:</strong> ${data.type}</div>
                            <div><strong>Nom:</strong> ${data.firstName || ''} ${data.lastName || ''}</div>
                            <div><strong>Email:</strong> ${data.email}</div>
                            <div><strong>Téléphone:</strong> ${data.phone || ''}</div>
                            <div><strong>Statut:</strong> ${data.status === 'unread' ? 'Non lu' : data.status === 'read' ? 'Vu non répondu' : 'Répondu'}</div>
                            <div><strong>Date:</strong> ${data.createdAt}</div>
                            ${data.appointmentDate ? `<div><strong>Date RDV:</strong> ${data.appointmentDate}</div>` : ''}
                        </div>
                        <div class="mb-4">
                            <strong>Sujet:</strong> ${data.subject || 'N/A'}
                        </div>
                        <div>
                            <strong>Message:</strong><br>
                            <div class="mt-2 p-3 bg-gray-100 rounded">${data.content}</div>
                        </div>
                    `;
                    document.querySelector('#message-details').innerHTML = details;
                    document.querySelector('#details-modal').classList.remove('hidden');
                    loadMessages();
                }
            });
        }
    });

    // Mark as responded
    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.mark-responded-btn')) {
            const id = e.target.closest('.mark-responded-btn').dataset.id;

            fetch(`/api/messages/${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status: 'responded' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    loadMessages();
                    showNotification('Message marqué comme répondu', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour', 'error');
                }
            });
        }
    });

    // Delete
    let deleteId = null;
    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.delete-btn')) {
            deleteId = e.target.closest('.delete-btn').dataset.id;
            document.querySelector('#delete-modal').classList.remove('hidden');
        }
    });

    document.querySelector('#confirm-delete').addEventListener('click', function() {
        if (deleteId) {
            fetch(`/api/messages/${deleteId}`, { method: 'DELETE' })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    loadMessages();
                    showNotification('Message supprimé', 'success');
                } else {
                    showNotification('Erreur lors de la suppression', 'error');
                }
                document.querySelector('#delete-modal').classList.add('hidden');
            });
        }
    });

    // Reply form
    document.querySelector('#reply-form').addEventListener('submit', function(e) {
        e.preventDefault();
        showNotification('Fonctionnalité d\'email à implémenter', 'info');
        document.querySelector('#reply-modal').classList.add('hidden');
        this.reset();
    });

    // Modal close handlers
    document.querySelector('#close-details-modal').addEventListener('click', function() {
        document.querySelector('#details-modal').classList.add('hidden');
    });
    document.querySelector('#close-details-modal-btn').addEventListener('click', function() {
        document.querySelector('#details-modal').classList.add('hidden');
    });

    document.querySelector('#close-reply-modal').addEventListener('click', function() {
        document.querySelector('#reply-modal').classList.add('hidden');
        document.querySelector('#reply-form').reset();
    });
    document.querySelector('#close-reply-modal-btn').addEventListener('click', function() {
        document.querySelector('#reply-modal').classList.add('hidden');
        document.querySelector('#reply-form').reset();
    });

    document.querySelector('#cancel-delete').addEventListener('click', function() {
        document.querySelector('#delete-modal').classList.add('hidden');
    });

    // Close modals on outside click
    window.addEventListener('click', function(e) {
        if (e.target.id === 'details-modal') {
            document.querySelector('#details-modal').classList.add('hidden');
        }
        if (e.target.id === 'reply-modal') {
            document.querySelector('#reply-modal').classList.add('hidden');
            document.querySelector('#reply-form').reset();
        }
        if (e.target.id === 'delete-modal') {
            document.querySelector('#delete-modal').classList.add('hidden');
        }
    });

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-md text-white text-sm font-medium';

        if (type === 'success') {
            notification.classList.add('bg-green-500');
        } else if (type === 'error') {
            notification.classList.add('bg-red-500');
        } else if (type === 'info') {
            notification.classList.add('bg-blue-500');
        } else {
            notification.classList.add('bg-gray-500');
        }

        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(function() {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Initial load
    loadMessages();
});