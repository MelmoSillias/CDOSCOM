// admin_messages.js
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    let pageSize = 25;
    let allMessages = [];
    let filteredMessages = [];
    let currentReplyMessageId = null;
    let currentReplyRecipientEmail = null;

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
                refreshStats();
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    function refreshStats() {
        fetch('/api/messages/stats')
            .then(response => response.json())
            .then(stats => {
                setText('#stat-total', stats.totalMessages);
                setText('#stat-unread', stats.unreadMessages);
                setText('#stat-read', stats.readMessages);
                setText('#stat-responded', stats.respondedMessages);
            })
            .catch(() => {});
    }

    function setText(selector, value) {
        const el = document.querySelector(selector);
        if (el) {
            el.textContent = value ?? 0;
        }
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
            const mailStatusBadge = getMailStatusBadge(message.statutEnvoiMail);

            row.innerHTML = `
                <td class="px-4 md:px-8 py-5 font-medium text-gray-900">${message.id}</td>
                <td class="px-4 md:px-8 py-5 text-gray-800">${(message.firstName || '') + ' ' + (message.lastName || '')}</td>
                <td class="px-4 md:px-8 py-5 text-gray-700 hidden sm:table-cell">${message.email}</td>
                <td class="px-4 md:px-8 py-5 text-gray-700 hidden md:table-cell">${message.phone || ''}</td>
                <td class="px-4 md:px-8 py-5 text-gray-700 capitalize">${message.type}</td>
                <td class="px-4 md:px-8 py-5">${statusBadge}</td>
                <td class="px-4 md:px-8 py-5">${mailStatusBadge}</td>
                <td class="px-4 md:px-8 py-5 text-gray-600 hidden lg:table-cell">${message.createdAt}</td>
                <td class="px-4 md:px-8 py-5">${getActionsHtml(message)}</td>
            `;

            tableBody.appendChild(row);
        });
    }

    function getMailStatusBadge(status) {
        if (status === 'sent') {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-green-100 text-green-800 rounded-full border border-green-200">Envoye</span>';
        }
        if (status === 'failed') {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-red-100 text-red-800 rounded-full border border-red-200">Echec</span>';
        }

        return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-gray-100 text-gray-800 rounded-full border border-gray-200">En attente</span>';
    }

    // Get status badge HTML
    function getStatusBadge(status) {
        if (status === 'unread') {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-red-100 text-red-800 rounded-full border border-red-200">Non repondu</span>';
        } else if (status === 'read') {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-yellow-100 text-yellow-800 rounded-full border border-yellow-200">Vu</span>';
        } else {
            return '<span class="inline-flex items-center px-3 py-1.5 text-sm font-medium bg-green-100 text-green-800 rounded-full border border-green-200">Répondu</span>';
        }
    }

    // Get actions HTML
    function getActionsHtml(message) {
        let actions = `
            <div class="flex space-x-2">
                <button class="reply-btn bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Répondre">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8m-18 8h18a2 2 0 002-2V8a2 2 0 00-2-2H3a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                    </svg>
                </button>
                <button class="view-details-btn bg-gray-500 hover:bg-gray-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Voir les détails">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>`;

        actions += `
                <button class="delete-btn bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-colors duration-200 shadow-sm hover:shadow-md" data-id="${message.id}" title="Supprimer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>`;

        if (message.statutEnvoiMail === 'failed') {
            actions += `<button class="retry-mail-btn mt-2 bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded" data-id="${message.id}">Reessayer mail</button>`;
        }

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

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.retry-mail-btn')) {
            const id = e.target.closest('.retry-mail-btn').dataset.id;
            fetch(`/api/messages/${id}/retry-mail`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showNotification(data.error, 'error');
                        return;
                    }
                    loadMessages();
                    showNotification('Nouvelle tentative d\'envoi du mail effectuee', 'success');
                });
            return;
        }

            if (e.target.closest('.reply-btn')) {
                const id = e.target.closest('.reply-btn').dataset.id;
                loadMessageForReply(id);
        }
    });

    function loadMessageForReply(id) {
        fetch(`/api/messages/${id}`)
        .then(response => response.json())
        .then(messageData => {
            if (messageData.id) {
                    if (!isValidEmail(messageData.email)) {
                        showNotification('L\'email du visiteur est invalide. Reponse impossible.', 'error');
                        return;
                    }

                    currentReplyMessageId = messageData.id;
                    currentReplyRecipientEmail = messageData.email;

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
                document.querySelector('#reply-message').value = '';
                document.querySelector('#reply-modal').classList.remove('hidden');
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
                    markMessageAsRead(id);
                    const displayStatus = data.status === 'responded'
                        ? 'Répondu'
                        : 'Vu';

                    const details = `
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div><strong>ID:</strong> ${data.id}</div>
                            <div><strong>Type:</strong> ${data.type}</div>
                            <div><strong>Nom:</strong> ${data.firstName || ''} ${data.lastName || ''}</div>
                            <div><strong>Email:</strong> ${data.email}</div>
                            <div><strong>Téléphone:</strong> ${data.phone || ''}</div>
                            <div><strong>Statut:</strong> ${displayStatus}</div>
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

        if (!currentReplyMessageId || !isValidEmail(currentReplyRecipientEmail)) {
            showNotification('Impossible d\'envoyer la reponse: email visiteur invalide.', 'error');
            return;
        }

        const submitButton = this.querySelector('button[type="submit"]');
        const subject = document.querySelector('#reply-subject').value.trim();
        const message = document.querySelector('#reply-message').value.trim();

        if (!subject || !message) {
            showNotification('Le sujet et le message sont obligatoires.', 'error');
            return;
        }

        setButtonLoading(submitButton, true);

        fetch(`/api/messages/${currentReplyMessageId}/reply`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject, message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showNotification(data.error, 'error');
                return;
            }

            document.querySelector('#reply-modal').classList.add('hidden');
            document.querySelector('#reply-form').reset();
            currentReplyMessageId = null;
            currentReplyRecipientEmail = null;
            loadMessages();
            showNotification('Réponse envoyée et message marqué comme répondu', 'success');
        })
        .catch(() => {
            showNotification('Erreur lors de l\'envoi de la réponse.', 'error');
        })
        .finally(() => {
            setButtonLoading(submitButton, false);
        });
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
        currentReplyMessageId = null;
        currentReplyRecipientEmail = null;
    });
    document.querySelector('#close-reply-modal-btn').addEventListener('click', function() {
        document.querySelector('#reply-modal').classList.add('hidden');
        document.querySelector('#reply-form').reset();
        currentReplyMessageId = null;
        currentReplyRecipientEmail = null;
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
            currentReplyMessageId = null;
            currentReplyRecipientEmail = null;
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

    function markMessageAsRead(id) {
        fetch(`/api/messages/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: 'read' })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.error) {
                loadMessages();
            }
        })
        .catch(() => {});
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email || '');
    }

    function setButtonLoading(button, isLoading) {
        if (!button) {
            return;
        }

        if (!button.dataset.defaultText) {
            button.dataset.defaultText = button.textContent.trim();
        }

        button.disabled = isLoading;
        button.textContent = isLoading ? (button.dataset.loadingText || 'Chargement...') : button.dataset.defaultText;
    }

    // Initial load
    loadMessages();
});