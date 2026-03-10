document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('configuration-form');

    function notify(message) {
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 z-[70] rounded-xl bg-slate-900 text-white px-4 py-3 shadow-lg';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 2600);
    }

    function setValue(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.value = value || '';
        }
    }

    function setGalleryPreview(index, src) {
        const existing = document.getElementById(`cfg-gallery-existing-${index}`);
        const preview = document.getElementById(`cfg-gallery-preview-${index}`);
        if (!existing || !preview) {
            return;
        }

        existing.value = src || '';
        if (src) {
            preview.src = src;
            preview.classList.remove('hidden');
        } else {
            preview.src = '';
            preview.classList.add('hidden');
        }
    }

    function loadConfiguration() {
        fetch('/api/admin/configuration')
            .then(r => r.json())
            .then(response => {
                const data = response.data || {};
                setValue('cfg-facebook', data.social_links?.facebook);
                setValue('cfg-instagram', data.social_links?.instagram);
                setValue('cfg-linkedin', data.social_links?.linkedin);
                setValue('cfg-tiktok', data.social_links?.tiktok);
                setValue('cfg-whatsapp', data.social_links?.whatsapp);

                setValue('cfg-phone-1', data.contact?.phones?.[0]);
                setValue('cfg-phone-2', data.contact?.phones?.[1]);
                setValue('cfg-email', data.contact?.email);
                setValue('cfg-address', data.contact?.address);

                setValue('cfg-hours-week', data.opening_hours?.['lundi-vendredi']);
                setValue('cfg-hours-sat', data.opening_hours?.samedi);
                setValue('cfg-hours-sun', data.opening_hours?.dimanche);

                setGalleryPreview(1, data.gallery_images?.[0] || '');
                setGalleryPreview(2, data.gallery_images?.[1] || '');
                setGalleryPreview(3, data.gallery_images?.[2] || '');
                setGalleryPreview(4, data.gallery_images?.[3] || '');

                setValue('cfg-mail-recipient', data.mail?.recipient);
            });
    }

    function getPayload() {
        const formData = new FormData();

        formData.append('social_facebook', document.getElementById('cfg-facebook').value.trim());
        formData.append('social_instagram', document.getElementById('cfg-instagram').value.trim());
        formData.append('social_linkedin', document.getElementById('cfg-linkedin').value.trim());
        formData.append('social_tiktok', document.getElementById('cfg-tiktok').value.trim());
        formData.append('social_whatsapp', document.getElementById('cfg-whatsapp').value.trim());

        formData.append('contact_phone_1', document.getElementById('cfg-phone-1').value.trim());
        formData.append('contact_phone_2', document.getElementById('cfg-phone-2').value.trim());
        formData.append('contact_email', document.getElementById('cfg-email').value.trim());
        formData.append('contact_address', document.getElementById('cfg-address').value.trim());

        formData.append('hours_week', document.getElementById('cfg-hours-week').value.trim());
        formData.append('hours_sat', document.getElementById('cfg-hours-sat').value.trim());
        formData.append('hours_sun', document.getElementById('cfg-hours-sun').value.trim());

        formData.append('mail_recipient', document.getElementById('cfg-mail-recipient').value.trim());

        for (let index = 1; index <= 4; index += 1) {
            const fileInput = document.getElementById(`cfg-gallery-file-${index}`);
            const existingInput = document.getElementById(`cfg-gallery-existing-${index}`);
            const file = fileInput?.files?.[0];
            if (file) {
                formData.append(`galleryFile${index}`, file);
            }
            formData.append(`gallery_existing_${index}`, existingInput?.value || '');
        }

        formData.append('_method', 'PUT');

        return formData;
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const payload = getPayload();

        fetch('/api/admin/configuration', {
            method: 'POST',
            body: payload
        })
            .then(r => r.json())
            .then(data => {
                if (data.error) {
                    notify(data.error);
                    return;
                }
                notify(data.message || 'Configuration enregistree');
                loadConfiguration();
            });
    });

    for (let index = 1; index <= 4; index += 1) {
        const fileInput = document.getElementById(`cfg-gallery-file-${index}`);
        const preview = document.getElementById(`cfg-gallery-preview-${index}`);
        const existing = document.getElementById(`cfg-gallery-existing-${index}`);

        if (!fileInput || !preview || !existing) {
            continue;
        }

        fileInput.addEventListener('change', () => {
            const file = fileInput.files && fileInput.files[0];
            if (!file) {
                return;
            }

            const reader = new FileReader();
            reader.onload = () => {
                const result = typeof reader.result === 'string' ? reader.result : '';
                preview.src = result;
                preview.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    }

    loadConfiguration();
});
