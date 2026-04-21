<!DOCTYPE html>
<html>
<head>
    <title>Analytics Charts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #1f3c88;
        }
        .actions, .filters {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 16px;
            background: #1f3c88;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-right: 10px;
            margin-bottom: 10px;
            border: none;
            cursor: pointer;
        }
        .filters-card,
        .chart-card {
            background: white;
            padding: 18px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }
        .filter-grid,
        .grid {
            display: grid;
            gap: 20px;
        }
        .filter-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            margin-top: 12px;
        }
        .grid {
            grid-template-columns: 1fr 1fr;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
        }
        .chart-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .chart-toolbar h3 {
            margin: 0;
        }
        .chart-download {
            border: none;
            background: #1f3c88;
            color: white;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
        }
        .chart-empty {
            color: #64748b;
            font-size: 14px;
            margin-top: 12px;
        }
        .error-box {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 20px;
            display: none;
        }
        canvas {
            max-width: 100%;
            height: 320px !important;
        }
        @media (max-width: 1100px) {
            .filter-grid,
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <h1>Analytics Charts & Graphs</h1>

    <div id="errorBox" class="error-box"></div>

    <div class="actions">
        <a class="btn" href="<?= base_url('dashboard') ?>">Back Dashboard</a>
        <a class="btn" href="<?= base_url('dashboard/export/csv') ?>">Export CSV</a>
        <button type="button" class="btn" id="exportChartsPdf">Export PDF</button>
    </div>

    <div class="filters-card filters">
        <h3 style="margin-top:0;">Filter Analytics</h3>
        <div class="filter-grid">
            <div class="form-group">
                <label for="filterProgramme">Programme</label>
                <input type="text" id="filterProgramme" placeholder="e.g. BSc Computer Science">
            </div>
            <div class="form-group">
                <label for="filterGraduationYear">Graduation Year</label>
                <input type="number" id="filterGraduationYear" placeholder="e.g. 2026">
            </div>
            <div class="form-group">
                <label for="filterGraduationDate">Graduation Date</label>
                <input type="date" id="filterGraduationDate">
            </div>
            <div class="form-group">
                <label for="filterIndustrySector">Industry Sector</label>
                <input type="text" id="filterIndustrySector" placeholder="e.g. Healthcare">
            </div>
        </div>
        <div style="margin-top:16px;">
            <button type="button" class="btn" id="applyFilters">Apply Filters</button>
            <button type="button" class="btn" id="clearFilters">Clear Filters</button>
        </div>
    </div>

    <div class="grid">
        <div class="chart-card"><div class="chart-toolbar"><h3>1. Industry Distribution</h3><button class="chart-download" data-target="chart1">Download PNG</button></div><canvas id="chart1"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>2. Top Employers</h3><button class="chart-download" data-target="chart2">Download PNG</button></div><canvas id="chart2"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>3. Job Titles</h3><button class="chart-download" data-target="chart3">Download PNG</button></div><canvas id="chart3"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>4. Programmes</h3><button class="chart-download" data-target="chart4">Download PNG</button></div><canvas id="chart4"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>5. Graduation Years</h3><button class="chart-download" data-target="chart5">Download PNG</button></div><canvas id="chart5"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>6. Top Certifications</h3><button class="chart-download" data-target="chart6">Download PNG</button></div><canvas id="chart6"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>7. Skills Gap Signals</h3><button class="chart-download" data-target="chart7">Download PNG</button></div><canvas id="chart7"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>8. Summary Overview</h3><button class="chart-download" data-target="chart8">Download PNG</button></div><canvas id="chart8"></canvas></div>
        <div class="chart-card"><div class="chart-toolbar"><h3>9. Geographic Distribution</h3><button class="chart-download" data-target="chart9">Download PNG</button></div><canvas id="chart9"></canvas></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script>
        const chartRegistry = {};

        function currentFilters() {
            return {
                programme: document.getElementById('filterProgramme').value.trim(),
                graduation_year: document.getElementById('filterGraduationYear').value.trim(),
                graduation_date: document.getElementById('filterGraduationDate').value.trim(),
                industry_sector: document.getElementById('filterIndustrySector').value.trim()
            };
        }

        function queryString() {
            const params = new URLSearchParams();
            const filters = currentFilters();

            Object.entries(filters).forEach(([key, value]) => {
                if (value !== '') {
                    params.set(key, value);
                }
            });

            return params.toString();
        }

        function showError(message) {
            const box = document.getElementById('errorBox');
            box.textContent = message;
            box.style.display = 'block';
        }

        function clearError() {
            const box = document.getElementById('errorBox');
            box.textContent = '';
            box.style.display = 'none';
        }

        function showEmptyState(canvasId, message) {
            const canvas = document.getElementById(canvasId);
            const parent = canvas.parentElement;
            const existing = parent.querySelector('.chart-empty');
            if (existing) {
                existing.remove();
            }

            const note = document.createElement('p');
            note.className = 'chart-empty';
            note.textContent = message;
            parent.appendChild(note);
        }

        function clearEmptyState(canvasId) {
            const canvas = document.getElementById(canvasId);
            const parent = canvas.parentElement;
            const existing = parent.querySelector('.chart-empty');
            if (existing) {
                existing.remove();
            }
        }

        function resetChart(canvasId) {
            clearEmptyState(canvasId);
            if (chartRegistry[canvasId]) {
                chartRegistry[canvasId].destroy();
            }
        }

        function renderPlaceholderChart(canvasId, message) {
            resetChart(canvasId);
            showEmptyState(canvasId, message);

            chartRegistry[canvasId] = new Chart(document.getElementById(canvasId), {
                type: 'bar',
                data: {
                    labels: ['No data yet'],
                    datasets: [{
                        label: 'No data',
                        data: [0],
                        backgroundColor: '#cbd5e1',
                        borderColor: '#94a3b8',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        function getPalette(count) {
            const colors = [
                '#1f77b4', '#ff7f0e', '#2ca02c', '#d62728',
                '#9467bd', '#8c564b', '#e377c2', '#7f7f7f',
                '#bcbd22', '#17becf'
            ];

            return Array.from({ length: count }, (_, index) => colors[index % colors.length]);
        }

        async function getData(url) {
            const qs = queryString();
            const response = await fetch(qs ? `${url}?${qs}` : url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            });

            const payload = await response.json();

            if (!response.ok || payload.status !== 'success') {
                throw new Error(payload.message || 'Failed to load chart data.');
            }

            return payload.data;
        }

        function renderChart(canvasId, configBuilder, rows, emptyMessage) {
            resetChart(canvasId);

            if (!Array.isArray(rows) || rows.length === 0) {
                renderPlaceholderChart(canvasId, emptyMessage);
                return;
            }

            chartRegistry[canvasId] = new Chart(document.getElementById(canvasId), configBuilder(rows));
        }

        async function loadCharts() {
            clearError();

            try {
                const [industries, employers, jobTitles, programmes, graduationYears, certifications, skillsGap, summary, geographic] = await Promise.all([
                    getData('<?= base_url('api/analytics/industries') ?>'),
                    getData('<?= base_url('api/analytics/employers') ?>'),
                    getData('<?= base_url('api/analytics/job-titles') ?>'),
                    getData('<?= base_url('api/analytics/programmes') ?>'),
                    getData('<?= base_url('api/analytics/graduation-years') ?>'),
                    getData('<?= base_url('api/analytics/certifications') ?>'),
                    getData('<?= base_url('api/analytics/skills-gap') ?>'),
                    getData('<?= base_url('api/analytics/summary') ?>'),
                    getData('<?= base_url('api/analytics/geographic-distribution') ?>')
                ]);

                renderChart('chart1', data => ({
                    type: 'pie',
                    data: {
                        labels: data.map(item => item.industry_sector),
                        datasets: [{
                            data: data.map(item => Number(item.total)),
                            backgroundColor: getPalette(data.length)
                        }]
                    }
                }), industries, 'No industry data available for the current filters.');

                renderChart('chart2', data => ({
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.company_name),
                        datasets: [{
                            label: 'Employers',
                            data: data.map(item => Number(item.total)),
                            backgroundColor: '#1f77b4'
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                }), employers, 'No employer data available for the current filters.');

                renderChart('chart3', data => ({
                    type: 'doughnut',
                    data: {
                        labels: data.map(item => item.job_title),
                        datasets: [{
                            data: data.map(item => Number(item.total)),
                            backgroundColor: getPalette(data.length)
                        }]
                    }
                }), jobTitles, 'No job title data available for the current filters.');

                renderChart('chart4', data => ({
                    type: 'polarArea',
                    data: {
                        labels: data.map(item => item.programme),
                        datasets: [{
                            data: data.map(item => Number(item.total)),
                            backgroundColor: getPalette(data.length)
                        }]
                    }
                }), programmes, 'No programme data available for the current filters.');

                renderChart('chart5', data => ({
                    type: 'line',
                    data: {
                        labels: data.map(item => item.graduation_year),
                        datasets: [{
                            label: 'Graduates by Year',
                            data: data.map(item => Number(item.total)),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.2)',
                            fill: false,
                            tension: 0.2
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                }), graduationYears, 'No graduation year data available for the current filters.');

                renderChart('chart6', data => ({
                    type: 'bar',
                    data: {
                        labels: data.map(item => item.certification_name),
                        datasets: [{
                            label: 'Certifications',
                            data: data.map(item => Number(item.total)),
                            backgroundColor: '#10b981'
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                }), certifications, 'No certification data available for the current filters.');

                renderChart('chart7', data => ({
                    type: 'radar',
                    data: {
                        labels: data.map(item => item.skill_name),
                        datasets: [{
                            label: 'Skills Gap Signals',
                            data: data.map(item => Number(item.total)),
                            borderColor: '#f97316',
                            backgroundColor: 'rgba(249, 115, 22, 0.2)'
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                }), skillsGap, 'No skills-gap data available for the current filters.');

                renderChart('chart8', () => ({
                    type: 'bar',
                    data: {
                        labels: ['Total Alumni', 'Certifications', 'Employment Records'],
                        datasets: [{
                            label: 'Summary',
                            data: [
                                Number(summary.total_alumni ?? 0),
                                Number(summary.total_certifications ?? 0),
                                Number(summary.total_employment_records ?? 0)
                            ],
                            backgroundColor: ['#2563eb', '#10b981', '#f59e0b']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                }), [{ total: 1 }], 'No summary data available for the current filters.');

                renderChart('chart9', data => ({
                    type: 'pie',
                    data: {
                        labels: data.map(item => item.location_name),
                        datasets: [{
                            data: data.map(item => Number(item.total)),
                            backgroundColor: getPalette(data.length)
                        }]
                    }
                }), geographic, 'No geographic data available for the current filters.');
            } catch (error) {
                console.error(error);
                showError(error.message || 'Unable to load analytics charts right now.');
            }
        }

        document.querySelectorAll('.chart-download').forEach(button => {
            button.addEventListener('click', () => {
                const canvas = document.getElementById(button.getAttribute('data-target'));
                if (!canvas) {
                    return;
                }

                const link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = button.getAttribute('data-target') + '.png';
                link.click();
            });
        });

        document.getElementById('applyFilters').addEventListener('click', loadCharts);

        document.getElementById('clearFilters').addEventListener('click', () => {
            document.getElementById('filterProgramme').value = '';
            document.getElementById('filterGraduationYear').value = '';
            document.getElementById('filterGraduationDate').value = '';
            document.getElementById('filterIndustrySector').value = '';
            loadCharts();
        });

        document.getElementById('exportChartsPdf').addEventListener('click', () => {
            const jsPdfApi = window.jspdf;
            if (!jsPdfApi || !jsPdfApi.jsPDF) {
                showError('PDF export library could not be loaded.');
                return;
            }

            const pdf = new jsPdfApi.jsPDF('p', 'mm', 'a4');
            const chartCards = Array.from(document.querySelectorAll('.chart-card'));

            chartCards.forEach((card, index) => {
                const title = card.querySelector('h3')?.textContent || ('Chart ' + (index + 1));
                const canvas = card.querySelector('canvas');

                if (index > 0) {
                    pdf.addPage();
                }

                pdf.setFont('helvetica', 'bold');
                pdf.setFontSize(18);
                pdf.text(title, 15, 22);

                pdf.setFont('times', 'italic');
                pdf.setFontSize(10);
                pdf.text('Generated from Alumni Influencer analytics dashboard', 15, 29);

                const filters = currentFilters();
                const activeFilterText = Object.entries(filters)
                    .filter(([, value]) => value !== '')
                    .map(([key, value]) => `${key.replace('_', ' ')}: ${value}`)
                    .join(' | ');

                if (activeFilterText) {
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(9);
                    pdf.text(activeFilterText, 15, 35);
                }

                if (canvas) {
                    const imageData = canvas.toDataURL('image/png');
                    pdf.addImage(imageData, 'PNG', 15, activeFilterText ? 42 : 36, 180, 105);
                }
            });

            pdf.save('analytics_charts.pdf');
        });

        loadCharts();
    </script>
</body>
</html>
