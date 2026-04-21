<!DOCTYPE html>
<html>
<head>
    <title>Filtered Alumni</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 92%;
            margin: 24px auto;
        }
        .panel {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }
        h1 {
            color: #1f3c88;
        }
        form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            min-width: 180px;
        }
        button, .btn {
            padding: 10px 16px;
            border: none;
            background: #1f3c88;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            cursor: pointer;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #e5e5e5;
            text-align: left;
        }
        th {
            background: #eef2ff;
        }
        .muted {
            color: #6b7280;
            font-size: 13px;
        }
        .preset-list {
            margin: 12px 0 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .preset-chip {
            background: #eef2ff;
            border-radius: 999px;
            padding: 8px 12px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .preset-chip a {
            color: #1f3c88;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php
    $industrySectors = [
        'Agriculture',
        'Architecture and Planning',
        'Arts and Design',
        'Automotive',
        'Aviation',
        'Banking and Finance',
        'Biotechnology',
        'Construction',
        'Consulting',
        'Cybersecurity',
        'Data Science and Analytics',
        'E-commerce',
        'Education',
        'Energy and Utilities',
        'Engineering',
        'Entertainment and Media',
        'Environmental Services',
        'Fashion and Apparel',
        'Food and Beverage',
        'Government and Public Administration',
        'Healthcare',
        'Hospitality and Tourism',
        'Human Resources',
        'Information Technology (IT)',
        'Insurance',
        'Legal Services',
        'Logistics and Supply Chain',
        'Manufacturing',
        'Marketing and Advertising',
        'Mining and Metals',
        'Nonprofit and NGO',
        'Pharmaceuticals',
        'Real Estate',
        'Retail',
        'Telecommunications',
        'Transportation',
    ];
    ?>
    <div class="container">
        <div class="panel">
            <h1>Alumni Filter Page</h1>

            <form method="get">
                <input type="text" name="programme" placeholder="Programme" value="<?= esc($programme ?? '') ?>">
                <select name="industry_sector">
                    <option value="">All Industry Sectors</option>
                    <?php foreach ($industrySectors as $sector): ?>
                        <option value="<?= esc($sector) ?>" <?= ($industrySector ?? '') === $sector ? 'selected' : '' ?>><?= esc($sector) ?> sector</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="graduation_year" placeholder="Graduation Year" min="1900" max="2100" value="<?= esc($graduationYear ?? '') ?>">
                <button type="submit">Filter</button>
                <a class="btn" href="<?= base_url('dashboard') ?>">Back Dashboard</a>
                <a class="btn" href="<?= base_url('dashboard/alumni/export/csv?' . http_build_query(['programme' => $programme ?? '', 'industry_sector' => $industrySector ?? '', 'graduation_year' => $graduationYear ?? ''])) ?>">Export CSV</a>
                <a class="btn" href="<?= base_url('dashboard/alumni/export/pdf?' . http_build_query(['programme' => $programme ?? '', 'industry_sector' => $industrySector ?? '', 'graduation_year' => $graduationYear ?? ''])) ?>">Export PDF</a>
            </form>

            <form method="post" action="<?= base_url('dashboard/alumni/presets/save') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="programme" value="<?= esc($programme ?? '') ?>">
                <input type="hidden" name="industry_sector" value="<?= esc($industrySector ?? '') ?>">
                <input type="hidden" name="graduation_year" value="<?= esc($graduationYear ?? '') ?>">
                <input type="text" name="preset_name" placeholder="Preset name">
                <button type="submit">Save Filter Preset</button>
            </form>

            <?php if (!empty($presets ?? [])): ?>
                <div class="preset-list">
                    <?php foreach (($presets ?? []) as $presetName => $presetFilters): ?>
                        <div class="preset-chip">
                            <span><?= esc($presetName) ?></span>
                            <a href="<?= base_url('dashboard/alumni/presets/apply/' . rawurlencode((string) $presetName)) ?>">Apply</a>
                            <a href="<?= base_url('dashboard/alumni/presets/delete/' . rawurlencode((string) $presetName)) ?>">Delete</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Programme</th>
                    <th>Industry Sector</th>
                    <th>Graduation Date</th>
                </tr>

                <?php if (!empty($alumni)): ?>
                    <?php foreach ($alumni as $a): ?>
                        <tr>
                            <td><?= esc($a['name'] ?? '') ?></td>
                            <td><?= esc($a['email'] ?? '') ?></td>
                            <td><?= esc($a['programme'] ?? '') ?></td>
                            <td><?= esc($a['industry_sector'] ?? '') ?></td>
                            <td><?= esc($a['graduation_date_display'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="muted">No alumni matched your filters.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</body>
</html>
