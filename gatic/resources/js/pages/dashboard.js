import Chart from 'chart.js/auto';

function normalizeLivewirePayload(payload) {
    if (Array.isArray(payload) && payload.length > 0 && typeof payload[0] === 'object') {
        return payload[0] ?? {};
    }

    if (payload && typeof payload === 'object') return payload;

    return {};
}

function cssVar(name, fallback) {
    const v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return v || fallback;
}

function parseRgb(color) {
    const m = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/i);
    if (!m) return null;
    return { r: Number(m[1]), g: Number(m[2]), b: Number(m[3]) };
}

function parseHex(color) {
    const hex = color.replace('#', '').trim();
    if (hex.length !== 6) return null;
    const r = parseInt(hex.slice(0, 2), 16);
    const g = parseInt(hex.slice(2, 4), 16);
    const b = parseInt(hex.slice(4, 6), 16);
    if (Number.isNaN(r) || Number.isNaN(g) || Number.isNaN(b)) return null;
    return { r, g, b };
}

function withAlpha(color, alpha) {
    const rgb = color.startsWith('#') ? parseHex(color) : parseRgb(color);
    if (!rgb) return color;
    return `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, ${alpha})`;
}

function formatDayLabel(isoDate) {
    if (typeof isoDate !== 'string' || isoDate.length < 10) return String(isoDate ?? '');
    const dd = isoDate.slice(8, 10);
    const mm = isoDate.slice(5, 7);
    return `${dd}/${mm}`;
}

function palette() {
    const tick = cssVar('--bs-secondary-color', 'rgba(0,0,0,0.55)');
    const grid = cssVar('--bs-border-color', 'rgba(0,0,0,0.12)');

    return {
        tick,
        grid,
        series: {
            assets_assigned_ops: cssVar('--color-info', '#0dcaf0'),
            assets_loan_ops: cssVar('--color-warning', '#ffc107'),
            qty_out_ops: cssVar('--color-danger', '#dc3545'),
            qty_in_ops: cssVar('--color-success', '#198754'),
        },
        alertVariants: {
            danger: cssVar('--color-danger', '#dc3545'),
            warning: cssVar('--color-warning', '#ffc107'),
            info: cssVar('--color-info', '#0dcaf0'),
            success: cssVar('--color-success', '#198754'),
            primary: cssVar('--cfe-green-dark', '#006b47'),
            secondary: cssVar('--color-secondary', '#6c757d'),
        },
    };
}

function buildOrUpdateMovementsChart(state, movementTrend) {
    const canvas = document.getElementById('dashboardMovementsTrend');
    if (!(canvas instanceof HTMLCanvasElement)) return;

    const p = palette();
    const labels = Array.isArray(movementTrend?.labels) ? movementTrend.labels.map(formatDayLabel) : [];
    const datasets = Array.isArray(movementTrend?.datasets) ? movementTrend.datasets : [];

    const chartData = {
        labels,
        datasets: datasets.map((ds) => {
            const key = String(ds?.key ?? '');
            const color = p.series[key] ?? cssVar('--cfe-green', '#008e5a');
            const data = Array.isArray(ds?.data) ? ds.data.map((n) => Number(n ?? 0)) : [];
            return {
                label: String(ds?.label ?? key),
                data,
                backgroundColor: withAlpha(color, 0.25),
                borderColor: withAlpha(color, 0.95),
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 26,
                stack: 'movements',
            };
        }),
    };

    if (!state.movements) {
        state.movements = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { labels: { usePointStyle: true, boxWidth: 8, color: p.tick } },
                    tooltip: {},
                },
                scales: {
                    x: {
                        stacked: true,
                        grid: { color: withAlpha(p.grid, 0.35) },
                        ticks: { color: p.tick, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        grid: { color: withAlpha(p.grid, 0.35) },
                        ticks: { color: p.tick },
                    },
                },
            },
        });
        return;
    }

    state.movements.data.labels = chartData.labels;
    state.movements.data.datasets = chartData.datasets;
    state.movements.update();
}

function buildOrUpdateAlertsChart(state, alerts) {
    const canvas = document.getElementById('dashboardAlertsSnapshot');
    if (!(canvas instanceof HTMLCanvasElement)) return;

    const p = palette();
    const items = Array.isArray(alerts) ? alerts : [];
    const labels = items.map((it) => String(it?.label ?? ''));
    const values = items.map((it) => Number(it?.value ?? 0));
    const hrefs = items.map((it) => (typeof it?.href === 'string' && it.href !== '' ? it.href : null));
    const colors = items.map((it) => {
        const variant = String(it?.variant ?? 'secondary');
        const c = p.alertVariants[variant] ?? p.alertVariants.secondary;
        return withAlpha(c, 0.85);
    });

    const data = {
        labels,
        datasets: [
            {
                label: 'Alertas',
                data: values,
                backgroundColor: colors.map((c) => withAlpha(c, 0.25)),
                borderColor: colors,
                borderWidth: 1,
                borderRadius: 8,
            },
        ],
    };

    const onClick = (_evt, els) => {
        if (!els || els.length === 0) return;
        const idx = els[0]?.index;
        const href = typeof idx === 'number' ? hrefs[idx] : null;
        if (href) window.location.assign(href);
    };

    const onHover = (_evt, els) => {
        const hasTarget = Array.isArray(els) && els.length > 0 && hrefs[els[0]?.index] != null;
        canvas.style.cursor = hasTarget ? 'pointer' : 'default';
    };

    if (!state.alerts) {
        state.alerts = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data,
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                onClick,
                onHover,
                plugins: {
                    legend: { display: false },
                    tooltip: {},
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: withAlpha(p.grid, 0.35) },
                        ticks: { color: p.tick },
                    },
                    y: {
                        grid: { display: false },
                        ticks: { color: p.tick },
                    },
                },
            },
        });
        return;
    }

    state.alerts.data.labels = labels;
    state.alerts.data.datasets[0].data = values;
    state.alerts.data.datasets[0].backgroundColor = data.datasets[0].backgroundColor;
    state.alerts.data.datasets[0].borderColor = data.datasets[0].borderColor;
    state.alerts.options.onClick = onClick;
    state.alerts.options.onHover = onHover;
    state.alerts.update();
}

let listenerRegistered = false;

export function registerDashboardCharts() {
    const root = document.querySelector('[data-page=\"dashboard\"]');
    if (!root) return;

    const state = { movements: null, alerts: null };
    let lastCharts = null;

    const handler = (payload) => {
        const p = normalizeLivewirePayload(payload);
        const charts = p?.charts ?? p;
        if (!charts || typeof charts !== 'object') return;

        lastCharts = charts;
        buildOrUpdateMovementsChart(state, charts.movementTrend);
        buildOrUpdateAlertsChart(state, charts.alerts);
    };

    const register = () => {
        if (listenerRegistered) return;
        if (!window.Livewire?.on) return;
        listenerRegistered = true;
        window.Livewire.on('dashboard:charts', handler);
    };

    register();
    document.addEventListener('livewire:init', register);

    window.addEventListener('gatic:theme-changed', () => {
        if (!lastCharts) return;
        buildOrUpdateMovementsChart(state, lastCharts.movementTrend);
        buildOrUpdateAlertsChart(state, lastCharts.alerts);
    });
}
