document.addEventListener('DOMContentLoaded', () => {
    const viewsCtx = document.getElementById('chart-views');
    const eventsCtx = document.getElementById('chart-events');
    const pagesList = document.getElementById('top-pages-list');

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    };

    fetch('/api/admin/dashboard/activity-stats')
        .then(r => r.json())
        .then(response => {
            const data = response.data || {};
            const kpis = data.kpis || {};

            setText('kpi-events', kpis.events || 0);
            setText('kpi-visitors', kpis.uniqueVisitors || 0);
            setText('kpi-page-views', kpis.pageViews || 0);
            setText('kpi-duration', `${Math.round((kpis.avgDurationMs || 0) / 1000)}s`);

            const daily = data.daily || [];
            const labels = daily.map(item => item.day);
            const values = daily.map(item => Number(item.views || 0));

            if (viewsCtx && window.Chart) {
                new Chart(viewsCtx, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Pages vues',
                            data: values,
                            borderColor: '#1d4ed8',
                            backgroundColor: 'rgba(29, 78, 216, 0.15)',
                            fill: true,
                            tension: 0.35
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: true } }
                    }
                });
            }

            const breakdown = data.eventBreakdown || [];
            if (eventsCtx && window.Chart) {
                new Chart(eventsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: breakdown.map(item => item.event_type),
                        datasets: [{
                            data: breakdown.map(item => Number(item.total || 0)),
                            backgroundColor: ['#1d4ed8', '#059669', '#d97706', '#7c3aed', '#dc2626']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }

            const topPages = data.topPages || [];
            if (pagesList) {
                pagesList.innerHTML = topPages.map((page, index) => `
                    <li class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                        <span class="text-sm text-gray-700">${index + 1}. ${page.path}</span>
                        <span class="text-xs font-semibold text-blue-700 bg-blue-100 px-2 py-1 rounded">${page.hits}</span>
                    </li>
                `).join('');

                if (topPages.length === 0) {
                    pagesList.innerHTML = '<li class="text-sm text-gray-500">Aucune donnee disponible.</li>';
                }
            }
        });
});
