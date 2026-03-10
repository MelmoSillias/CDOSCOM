document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#personnels-table tbody');
    const modal = document.getElementById('personnel-modal');
    const deleteModal = document.getElementById('delete-personnel-modal');
    const form = document.getElementById('personnel-form');
    const photoInput = document.getElementById('personnel-photo-file');
    const photoField = document.getElementById('personnel-photo');
    const photoPreview = document.getElementById('personnel-photo-preview');
    let deleteId = null;

    function notify(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-[70] rounded-xl bg-slate-900 text-white px-4 py-3 shadow-lg';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 2600);
    }

    function openModal(editData = null) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (!editData) {
            form.reset();
            document.getElementById('personnel-id').value = '';
            document.getElementById('personnel-modal-title').textContent = 'Nouveau personnel';
            photoField.value = '';
            photoInput.value = '';
            photoPreview.src = '';
            photoPreview.classList.add('hidden');
            return;
        }

        document.getElementById('personnel-modal-title').textContent = 'Modifier personnel';
        document.getElementById('personnel-id').value = editData.id;
        document.getElementById('personnel-nom').value = editData.nom || '';
        document.getElementById('personnel-prenom').value = editData.prenom || '';
        document.getElementById('personnel-poste').value = editData.poste || '';
        photoField.value = editData.photo || '';
        photoInput.value = '';
        if (editData.photo) {
            photoPreview.src = editData.photo;
            photoPreview.classList.remove('hidden');
        } else {
            photoPreview.classList.add('hidden');
        }
        document.getElementById('personnel-description').value = editData.description || '';
        document.getElementById('personnel-linkedin').value = editData.liensSociaux?.LinkedIn || '';
        document.getElementById('personnel-twitter').value = editData.liensSociaux?.Twitter || '';
        document.getElementById('personnel-facebook').value = editData.liensSociaux?.Facebook || '';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function getPayload() {
        const formData = new FormData();
        formData.append('nom', document.getElementById('personnel-nom').value.trim());
        formData.append('prenom', document.getElementById('personnel-prenom').value.trim());
        formData.append('poste', document.getElementById('personnel-poste').value.trim());
        formData.append('photo', photoField.value.trim());
        formData.append('description', document.getElementById('personnel-description').value.trim());
        formData.append('linkedin', document.getElementById('personnel-linkedin').value.trim());
        formData.append('twitter', document.getElementById('personnel-twitter').value.trim());
        formData.append('facebook', document.getElementById('personnel-facebook').value.trim());

        const file = photoInput.files && photoInput.files[0];
        if (file) {
            formData.append('photoFile', file);
        }

        return formData;
    }

    function renderRows(items) {
        tableBody.innerHTML = '';

        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'border-t hover:bg-blue-50/50 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-3 font-medium text-slate-800">${item.nom}</td>
                <td class="px-4 py-3 text-slate-700">${item.prenom}</td>
                <td class="px-4 py-3 text-slate-600">${item.poste}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <button class="edit-personnel px-3 py-1 rounded-lg bg-amber-500 text-white shadow-sm" data-id="${item.id}">Modifier</button>
                        <button class="delete-personnel px-3 py-1 rounded-lg bg-red-600 text-white shadow-sm" data-id="${item.id}">Supprimer</button>
                    </div>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        tableBody.querySelectorAll('.edit-personnel').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id, 10);
                const found = items.find(item => item.id === id);
                if (found) {
                    openModal(found);
                }
            });
        });

        tableBody.querySelectorAll('.delete-personnel').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteId = parseInt(btn.dataset.id, 10);
                deleteModal.classList.remove('hidden');
                deleteModal.classList.add('flex');
            });
        });
    }

    function loadPersonnels() {
        fetch('/api/admin/personnels')
            .then(r => r.json())
            .then(data => renderRows(data.data || []));
    }

    document.getElementById('open-create-personnel').addEventListener('click', () => openModal());
    document.getElementById('close-personnel-modal').addEventListener('click', closeModal);

    photoInput.addEventListener('change', () => {
        const file = photoInput.files && photoInput.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const result = typeof reader.result === 'string' ? reader.result : '';
            photoField.value = result;
            photoPreview.src = result;
            photoPreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = document.getElementById('personnel-id').value;
        const payload = getPayload();
        const method = 'POST';
        const url = id ? `/api/admin/personnels/${id}` : '/api/admin/personnels';

        if (id) {
            payload.append('_method', 'PUT');
        }

        fetch(url, {
            method,
            body: payload
        })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    notify(data.error);
                    return;
                }
                closeModal();
                loadPersonnels();
                notify(data.message || 'Operation reussie');
            });
    });

    document.getElementById('cancel-delete-personnel').addEventListener('click', () => {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    });

    document.getElementById('confirm-delete-personnel').addEventListener('click', () => {
        if (!deleteId) {
            return;
        }
        fetch(`/api/admin/personnels/${deleteId}`, { method: 'DELETE' })
            .then(r => r.json())
            .then(data => {
                deleteModal.classList.add('hidden');
                deleteModal.classList.remove('flex');
                deleteId = null;
                loadPersonnels();
                notify(data.message || 'Personnel supprime');
            });
    });

    loadPersonnels();
});
