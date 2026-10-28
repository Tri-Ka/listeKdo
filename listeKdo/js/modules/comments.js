import { $, $$ } from './core.js';
import { toast } from './ui.js';
import { shrinkImage } from './images.js';

/* ---------- Visionneuse d'images ---------- */

function openViewer(link) {
    const viewer = $('#image-viewer');
    if (!viewer) return false;
    const img = $('.viewer__image', viewer);
    img.src = link.getAttribute('href');
    img.alt = $('img', link)?.alt || '';
    // Par-dessus la fenêtre de l'idée, sans la fermer (comme la confirmation).
    viewer.showModal();
    document.body.classList.add('is-locked');
    return true;
}

/*
 * Photo ou GIF dans un commentaire (formulaires [data-comment-images]) :
 * bouton image, collage depuis le presse-papiers ou glisser-déposer, avec un aperçu retirable.
 * Les photos sont réduites avant l'envoi ; les GIF partent tels quels pour rester animés.
 */

const MAX_BYTES = 2 * 1024 * 1024;
const TYPES = ['image/jpeg', 'image/png', 'image/gif'];

function imageInput(form) {
    return $('[data-comment-image]', form);
}

function showPreview(form) {
    const input = imageInput(form);
    const preview = $('[data-comment-preview]', form);
    if (!input || !preview) return;

    const img = $('img', preview);
    if (img.src.startsWith('blob:')) URL.revokeObjectURL(img.src);

    const file = input.files[0];
    preview.hidden = !file;
    img.src = file ? URL.createObjectURL(file) : '';
    form.classList.toggle('comment-form--image', Boolean(file));
}

/** Pièce jointe du commentaire (vérifiée, avec aperçu) ; null la retire. */
export function setImage(form, file) {
    const input = imageInput(form);
    if (!input) return;

    if (file && !TYPES.includes(file.type)) {
        toast('Seules les images JPEG, PNG ou GIF peuvent être envoyées.', 'error');
        file = null;
    } else if (file && file.type === 'image/gif' && file.size > MAX_BYTES) {
        toast('Ce GIF dépasse 2 Mo : choisissez-en un plus léger.', 'error');
        file = null;
    }

    const transfer = new DataTransfer();
    if (file) transfer.items.add(file);
    input.files = transfer.files;
    showPreview(form);
}

function firstImage(files) {
    return [...(files || [])].find((file) => file.type.startsWith('image/')) || null;
}

/* ---------- Champ éditable (contenteditable) ---------- */

/** Texte brut du champ éditable, recopié dans le champ caché « content » envoyé avec le formulaire. */
function syncEditor(editor) {
    const form = editor.closest('form');
    let text = editor.innerText.replace(/\u00a0/g, ' ').replace(/\n+$/, '');
    if (!text.trim()) {
        text = '';
        // Sans contenu, le champ redevient vide pour réafficher le texte d'invite.
        if (editor.innerHTML !== '') editor.innerHTML = '';
    }
    if (text.length > 2000) {
        text = text.slice(0, 2000);
        toast('Le commentaire est limité à 2000 caractères.', 'error');
    }
    if (form) form.elements.content.value = text;
}

/** Image insérée par le clavier du téléphone (GIF, autocollant) : elle devient la pièce jointe du commentaire. */
async function adoptInsertedImage(editor, img) {
    const src = img.getAttribute('src') || '';
    img.remove();
    syncEditor(editor);
    if (!src) return;
    try {
        const blob = await (await fetch(src)).blob();
        const type = blob.type || 'image/gif';
        const extension = type.split('/')[1] || 'gif';
        setImage(editor.closest('form'), new File([blob], `clavier.${extension}`, { type }));
    } catch {
        toast('Cette image n’a pas pu être ajoutée. Enregistrez-la puis utilisez le bouton image.', 'error');
    }
}

function insertPlainText(text) {
    if (text) document.execCommand('insertText', false, text);
}

function initEditors() {
    document.addEventListener('input', (event) => {
        if (event.target.matches?.('[data-comment-editor]')) syncEditor(event.target);
    });

    // Tout ce qui est collé ou déposé arrive en texte brut (pas de mise en forme d'un autre site).
    document.addEventListener('paste', (event) => {
        const editor = event.target.closest?.('[data-comment-editor]');
        if (!editor || firstImage(event.clipboardData?.files)) return;
        event.preventDefault();
        insertPlainText(event.clipboardData?.getData('text/plain'));
    });
    document.addEventListener('drop', (event) => {
        const editor = event.target.closest?.('[data-comment-editor]');
        if (!editor || firstImage(event.dataTransfer?.files)) return;
        event.preventDefault();
        editor.focus();
        insertPlainText(event.dataTransfer?.getData('text/plain'));
    });

    // Certains claviers insèrent l'image directement dans le champ, sans événement « coller ».
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType !== 1) return;
                const editor = mutation.target.closest?.('[data-comment-editor]') || node.closest?.('[data-comment-editor]');
                if (!editor) return;
                (node.matches('img') ? [node] : $$('img', node)).forEach((img) => adoptInsertedImage(editor, img));
            });
        });
    });
    observer.observe(document.body, { childList: true, subtree: true });
}

/** Le commentaire a-t-il quelque chose à envoyer (texte ou image) ? */
export function commentHasContent(form) {
    return Boolean(form.elements.content.value.trim() || imageInput(form)?.files.length);
}

/** Données du formulaire, avec la photo réduite. */
export async function commentFormData(form, button) {
    const data = new FormData(form, button);
    const input = imageInput(form);
    const file = input?.files[0];

    if (file) {
        try {
            const resized = await shrinkImage(file, Number(input.dataset.maxSize));
            data.set('image', resized, resized.name);
        } catch {
            // Si la réduction échoue, le fichier d'origine est envoyé tel quel.
        }
    }

    return data;
}

export function resetCommentForm(form) {
    form.reset();
    if (imageInput(form)) showPreview(form);
    form.elements.content.style.height = '';
    const editor = $('[data-comment-editor]', form);
    if (editor) editor.innerHTML = '';
}

export function initComments() {
    initEditors();

    document.addEventListener('change', (event) => {
        const input = event.target;
        if (input.matches?.('[data-comment-image]')) setImage(input.form, input.files[0] || null);
    });

    document.addEventListener('click', (event) => {
        const target = event.target;
        const remove = target.closest?.('[data-comment-image-remove]');
        if (remove) setImage(remove.form || remove.closest('form'), null);

        const viewerLink = target.closest?.('[data-image-viewer]');
        if (viewerLink && openViewer(viewerLink)) event.preventDefault();
    });

    // Coller une image ou un GIF (copié depuis un site ou le clavier du téléphone).
    document.addEventListener('paste', (event) => {
        const form = event.target.closest?.('form[data-comment-images]');
        const file = form && firstImage(event.clipboardData?.files);
        if (!file) return;
        event.preventDefault();
        setImage(form, file);
    });

    // Glisser-déposer une image sur le formulaire.
    const dropForm = (event) => event.target.closest?.('form[data-comment-images]');
    document.addEventListener('dragover', (event) => {
        const form = dropForm(event);
        if (form && [...event.dataTransfer.types].includes('Files')) {
            event.preventDefault();
            form.classList.add('comment-form--drop');
        }
    });
    document.addEventListener('dragleave', (event) => dropForm(event)?.classList.remove('comment-form--drop'));
    document.addEventListener('drop', (event) => {
        const form = dropForm(event);
        if (!form) return;
        form.classList.remove('comment-form--drop');
        const file = firstImage(event.dataTransfer.files);
        if (!file) return;
        event.preventDefault();
        setImage(form, file);
    });
}
