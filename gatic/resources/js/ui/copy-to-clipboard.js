function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', 'true');
    textarea.style.position = 'fixed';
    textarea.style.top = '-9999px';
    textarea.style.left = '-9999px';

    document.body.appendChild(textarea);
    textarea.select();

    try {
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        textarea.remove();
    }
}

async function copyToClipboard(text) {
    if (navigator?.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        return true;
    }

    return fallbackCopy(text);
}

function showToast(type, message) {
    if (window.GaticToasts?.show) {
        window.GaticToasts.show({ type, message });
        return;
    }

    // Fallback: no toast system available
    window.alert(message);
}

export function registerCopyToClipboard() {
    document.addEventListener('click', async (e) => {
        const target = e.target;
        if (!(target instanceof HTMLElement)) return;

        const btn = target.closest('[data-copy-to-clipboard]');
        if (!(btn instanceof HTMLElement)) return;

        const text = btn.dataset.copyText;
        if (typeof text !== 'string' || text.trim() === '') return;

        try {
            const ok = await copyToClipboard(text);
            if (ok) showToast('success', 'ID copiado.');
            else showToast('error', 'No se pudo copiar el ID.');
        } catch {
            showToast('error', 'No se pudo copiar el ID.');
        }
    });
}

