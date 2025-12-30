function formatRelativeSeconds(seconds) {
    if (seconds < 60) return `${seconds}s`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m`;
    const hours = Math.floor(minutes / 60);
    return `${hours}h`;
}

function tick() {
    const nowMs = Date.now();

    document.querySelectorAll('[data-gatic-freshness]').forEach((el) => {
        if (!(el instanceof HTMLElement)) return;

        const updatedAtMs = Number(el.dataset.updatedAtMs ?? 0);
        if (!updatedAtMs) return;

        const diffSeconds = Math.max(0, Math.floor((nowMs - updatedAtMs) / 1000));
        el.textContent = formatRelativeSeconds(diffSeconds);
    });
}

export function registerFreshness() {
    document.addEventListener('DOMContentLoaded', () => {
        tick();
        window.setInterval(tick, 1000);
    });
}

