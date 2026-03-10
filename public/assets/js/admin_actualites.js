document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#actualites-table tbody');
    const modal = document.getElementById('news-modal');
    const deleteModal = document.getElementById('delete-news-modal');
    const form = document.getElementById('news-form');
    const imageInput = document.getElementById('news-image-file');
    const imageField = document.getElementById('news-image');
    const imagePreview = document.getElementById('news-image-preview');
    let deleteId = null;

    const quill = new Quill('#news-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ header: [1, 2, false] }],
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link'],
                ['clean']
            ]
        }
    });

    function openModal(editData = null) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        if (!editData) {
            form.reset();
            document.getElementById('news-id').value = '';
            document.getElementById('news-modal-title').textContent = 'Nouvelle actualite';
            quill.root.innerHTML = '';
            imageField.value = '';
            imageInput.value = '';
            imagePreview.src = '';
            imagePreview.classList.add('hidden');
            return;
        }

        document.getElementById('news-modal-title').textContent = 'Modifier actualite';
        document.getElementById('news-id').value = editData.id;
        document.getElementById('news-titre').value = editData.titre || '';
        document.getElementById('news-slug').value = editData.slug || '';
        document.getElementById('news-date-publication').value = editData.datePublication || '';
        document.getElementById('news-delai-publication').value = editData.delaiPublication || '';
        imageField.value = editData.image || '';
        imageInput.value = '';
        if (editData.image) {
            imagePreview.src = editData.image;
            imagePreview.classList.remove('hidden');
        } else {
            imagePreview.classList.add('hidden');
        }
        document.getElementById('news-faq-1').value = editData.faq?.['Pourquoi cette actualite ?'] || '';
        document.getElementById('news-faq-2').value = editData.faq?.['Ce que vous pouvez faire :'] || '';
        document.getElementById('news-faq-3').value = editData.faq?.['Conseil Pratique'] || '';
        quill.root.innerHTML = editData.contenu || '';
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function notify(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-[70] rounded-xl bg-slate-900 text-white px-4 py-3 shadow-lg';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 2600);
    }

    function getPayload() {
        const formData = new FormData();
        formData.append('titre', document.getElementById('news-titre').value.trim());
        formData.append('slug', document.getElementById('news-slug').value.trim());
        formData.append('datePublication', document.getElementById('news-date-publication').value);
        formData.append('delaiPublication', document.getElementById('news-delai-publication').value);
        formData.append('image', document.getElementById('news-image').value.trim());
        formData.append('contenu', quill.root.innerHTML);
        formData.append('faqPourquoi', document.getElementById('news-faq-1').value.trim());
        formData.append('faqAction', document.getElementById('news-faq-2').value.trim());
        formData.append('faqConseil', document.getElementById('news-faq-3').value.trim());

        const file = imageInput.files && imageInput.files[0];
        if (file) {
            formData.append('imageFile', file);
        }

        return formData;
    }

    function renderRows(items) {
        tableBody.innerHTML = '';

        items.forEach(item => {
            const tr = document.createElement('tr');
            tr.className = 'border-t hover:bg-blue-50/50 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-3 font-medium text-slate-800">${item.titre}</td>
                <td class="px-4 py-3 text-slate-600">${(item.datePublication || '').replace('T', ' ')}</td>
                <td class="px-4 py-3 text-slate-600">${(item.delaiPublication || '').replace('T', ' ')}</td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <button class="edit-news px-3 py-1 rounded-lg bg-amber-500 text-white shadow-sm" data-id="${item.id}">Modifier</button>
                        <button class="delete-news px-3 py-1 rounded-lg bg-red-600 text-white shadow-sm" data-id="${item.id}">Supprimer</button>
                    </div>
                </td>
            `;
            tableBody.appendChild(tr);
        });

        tableBody.querySelectorAll('.edit-news').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.dataset.id, 10);
                const found = items.find(item => item.id === id);
                if (found) {
                    openModal(found);
                }
            });
        });

        tableBody.querySelectorAll('.delete-news').forEach(btn => {
            btn.addEventListener('click', () => {
                deleteId = parseInt(btn.dataset.id, 10);
                deleteModal.classList.remove('hidden');
                deleteModal.classList.add('flex');
            });
        });
    }

    function loadActualites() {
        fetch('/api/admin/actualites')
            .then(r => r.json())
            .then(data => renderRows(data.data || []));
    }

    document.getElementById('open-create-news').addEventListener('click', () => openModal());
    document.getElementById('close-news-modal').addEventListener('click', closeModal);

    imageInput.addEventListener('change', () => {
        const file = imageInput.files && imageInput.files[0];
        if (!file) {
            return;
        }

        const reader = new FileReader();
        reader.onload = () => {
            const result = typeof reader.result === 'string' ? reader.result : '';
            imageField.value = result;
            imagePreview.src = result;
            imagePreview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const id = document.getElementById('news-id').value;
        const payload = getPayload();
        const method = 'POST';
        const url = id ? `/api/admin/actualites/${id}` : '/api/admin/actualites';

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
                loadActualites();
                notify(data.message || 'Operation reussie');
            });
    });

    document.getElementById('cancel-delete-news').addEventListener('click', () => {
        deleteModal.classList.add('hidden');
        deleteModal.classList.remove('flex');
    });

    document.getElementById('confirm-delete-news').addEventListener('click', () => {
        if (!deleteId) {
            return;
        }
        fetch(`/api/admin/actualites/${deleteId}`, { method: 'DELETE' })
            .then(r => r.json())
            .then(data => {
                deleteModal.classList.add('hidden');
                deleteModal.classList.remove('flex');
                deleteId = null;
                loadActualites();
                notify(data.message || 'Actualite supprimee');
            });
    });

    loadActualites();
});
