import { $, $$ } from './core.js';
import { toast } from './ui.js';

export function initImages() {
    /* ---------- Images : réduction avant envoi ---------- */

    /*
     * Les photos de téléphone dépassent souvent la limite de 2 Mo de Free.
     * On les redimensionne dans le navigateur (orientation EXIF comprise) avant l'envoi.
     */
    async function shrinkImage(file, maxSide) {
        if (!file.type.startsWith('image/') || file.type === 'image/gif') return file;

        const bitmap = await createImageBitmap(file);
        const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));

        if (scale === 1 && file.size < 1.5 * 1024 * 1024 && /jpe?g|png/.test(file.type)) {
            bitmap.close();
            return file;
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(bitmap.width * scale);
        canvas.height = Math.round(bitmap.height * scale);
        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
        bitmap.close();

        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
        const name = file.name.replace(/\.[^.]+$/, '') + '.jpg';

        return new File([blob], name, { type: 'image/jpeg' });
    }

    $$('form[data-resize-images]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            if (form.dataset.ready) {
                delete form.dataset.ready;
                return;
            }

            const repeat = form.elements['re-password'];
            if (repeat && repeat.value !== form.elements.password.value) {
                event.preventDefault();
                toast('Les mots de passe sont différents.', 'error');
                repeat.focus();
                return;
            }

            const inputs = $$('input[type="file"][data-max-size]', form).filter((input) => input.files.length);
            if (!inputs.length) return;

            event.preventDefault();
            const submit = $('[type="submit"]:not([form])', form);
            submit?.setAttribute('aria-busy', 'true');

            try {
                for (const input of inputs) {
                    const resized = await shrinkImage(input.files[0], Number(input.dataset.maxSize));
                    const transfer = new DataTransfer();
                    transfer.items.add(resized);
                    input.files = transfer.files;
                }
            } catch {
                // Si la réduction échoue, le fichier d'origine est envoyé tel quel.
            }

            form.dataset.ready = '1';
            form.requestSubmit(event.submitter ?? undefined);
        });
    });
}
