<?php

namespace App\Controllers;

use Config\Database;

class DashboardController extends BaseController
{
    private const METRIC_DEFINITIONS = [
        'total_alumni' => 'Total Alumni',
        'total_certifications' => 'Total Certifications',
        'total_employment_records' => 'Employment Records',
        'top_industries' => 'Top Industries',
        'top_employers' => 'Top Employers',
    ];

    public function index()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        return view('dashboard/index', $this->dashboardViewData());
    }

    public function alumni()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $filters = $this->getAlumniFilters();
        $alumni = $this->buildAlumniRows($filters);

        return view('dashboard/alumni', [
            'title' => 'Filtered Alumni',
            'alumni' => $alumni,
            'programme' => $filters['programme'],
            'graduationYear' => $filters['graduation_year'],
            'industrySector' => $filters['industry_sector'],
            'presets' => session()->get('dashboard_filter_presets') ?? [],
        ]);
    }

    public function savePreset()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $name = trim((string) $this->request->getPost('preset_name'));
        if ($name === '') {
            return redirect()->to(base_url('dashboard/alumni'))->with('error', 'Preset name is required.');
        }

        $presets = session()->get('dashboard_filter_presets') ?? [];
        $presets[$name] = [
            'programme' => trim((string) $this->request->getPost('programme')),
            'graduation_year' => trim((string) $this->request->getPost('graduation_year')),
            'industry_sector' => trim((string) $this->request->getPost('industry_sector')),
        ];
        session()->set('dashboard_filter_presets', $presets);

        return redirect()->to(base_url('dashboard/alumni'))->with('success', 'Filter preset saved.');
    }

    public function applyPreset(string $name)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $presets = session()->get('dashboard_filter_presets') ?? [];
        if (!isset($presets[$name]) || !is_array($presets[$name])) {
            return redirect()->to(base_url('dashboard/alumni'))->with('error', 'Preset not found.');
        }

        return redirect()->to(base_url('dashboard/alumni?' . http_build_query($presets[$name])));
    }

    public function deletePreset(string $name)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $presets = session()->get('dashboard_filter_presets') ?? [];
        unset($presets[$name]);
        session()->set('dashboard_filter_presets', $presets);

        return redirect()->to(base_url('dashboard/alumni'))->with('success', 'Preset deleted.');
    }

    public function charts()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        return view('dashboard/charts', [
            'title' => 'Charts',
        ]);
    }

    public function exportCsv()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $rows = $this->buildGenericExportRows();
        $this->streamCsv('analytics_export.csv', $rows);
    }

    public function exportPdf()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $summary = $this->getDashboardSummary();
        $blocks = [
            ['type' => 'title', 'text' => 'University Analytics Dashboard'],
            ['type' => 'subtitle', 'text' => 'High-level university alumni analytics summary'],
            ['type' => 'spacer', 'text' => ''],
            ['type' => 'section', 'text' => 'Core Metrics'],
            ['type' => 'body', 'text' => 'Total Alumni: ' . $summary['total_alumni']],
            ['type' => 'body', 'text' => 'Total Certifications: ' . $summary['total_certifications']],
            ['type' => 'body', 'text' => 'Employment Records: ' . $summary['total_employment_records']],
            ['type' => 'spacer', 'text' => ''],
            ['type' => 'section', 'text' => 'Top Industries'],
        ];

        if (!empty($summary['top_industries'])) {
            foreach ($summary['top_industries'] as $row) {
                $blocks[] = ['type' => 'body', 'text' => ($row['industry_sector'] ?? 'Unknown') . ': ' . ($row['total'] ?? 0)];
            }
        } else {
            $blocks[] = ['type' => 'body', 'text' => 'No industry data available yet.'];
        }

        $blocks[] = ['type' => 'spacer', 'text' => ''];
        $blocks[] = ['type' => 'section', 'text' => 'Top Employers'];

        if (!empty($summary['top_employers'])) {
            foreach ($summary['top_employers'] as $row) {
                $blocks[] = ['type' => 'body', 'text' => ($row['company_name'] ?? 'Unknown') . ': ' . ($row['total'] ?? 0)];
            }
        } else {
            $blocks[] = ['type' => 'body', 'text' => 'No employer data available yet.'];
        }

        $this->streamStyledPdf('analytics_dashboard.pdf', $blocks);
    }

    public function exportFilteredCsv()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $filters = $this->getAlumniFilters();
        $rows = $this->formatAlumniRowsForExport($this->buildAlumniRows($filters));
        $this->streamCsv('filtered_alumni_export.csv', $rows);
    }

    public function exportFilteredPdf()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $filters = $this->getAlumniFilters();
        $rows = $this->formatAlumniRowsForExport($this->buildAlumniRows($filters));
        $blocks = [
            ['type' => 'title', 'text' => 'Filtered Alumni Report'],
            ['type' => 'subtitle', 'text' => 'Programme: ' . ($filters['programme'] !== '' ? $filters['programme'] : 'All')],
            ['type' => 'subtitle', 'text' => 'Industry Sector: ' . ($filters['industry_sector'] !== '' ? $filters['industry_sector'] : 'All')],
            ['type' => 'subtitle', 'text' => 'Graduation Year: ' . ($filters['graduation_year'] !== '' ? $filters['graduation_year'] : 'All')],
            ['type' => 'spacer', 'text' => ''],
        ];

        if (empty($rows)) {
            $blocks[] = ['type' => 'body', 'text' => 'No alumni matched the selected filters.'];
        } else {
            foreach ($rows as $row) {
                $blocks[] = ['type' => 'section', 'text' => $row['name']];
                $blocks[] = ['type' => 'body', 'text' => 'Email: ' . $row['email']];
                $blocks[] = ['type' => 'body', 'text' => 'Programme: ' . $row['programme']];
                $blocks[] = ['type' => 'body', 'text' => 'Industry Sector: ' . $row['industry_sector']];
                $blocks[] = ['type' => 'body', 'text' => 'Graduation Date: ' . $row['graduation_date']];
                $blocks[] = ['type' => 'spacer', 'text' => ''];
            }
        }

        $this->streamStyledPdf('filtered_alumni_report.pdf', $blocks);
    }

    public function report()
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $selectedMetrics = $this->request->getVar('metrics');
        if (!is_array($selectedMetrics)) {
            if (is_string($selectedMetrics) && $selectedMetrics !== '') {
                $selectedMetrics = [$selectedMetrics];
            } else {
                $selectedMetrics = [];
            }
        }

        if (empty($selectedMetrics)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Select at least one metric for the custom report.');
        }

        $selectedMetrics = array_values(array_filter(
            $selectedMetrics,
            static fn ($metric) => array_key_exists($metric, self::METRIC_DEFINITIONS)
        ));

        if (empty($selectedMetrics)) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Select at least one metric for the custom report.');
        }

        $format = strtolower(trim((string) $this->request->getVar('format')));
        $metrics = $this->buildMetricsPayload($selectedMetrics);

        if ($format === 'csv') {
            $rows = [];
            foreach ($metrics as $metric) {
                if (!empty($metric['rows'])) {
                    foreach ($metric['rows'] as $row) {
                        $rows[] = [
                            'metric' => $metric['label'],
                            'item' => $row['label'],
                            'value' => $row['value'],
                        ];
                    }
                } else {
                    $rows[] = [
                        'metric' => $metric['label'],
                        'item' => '',
                        'value' => $metric['value'],
                    ];
                }
            }

            $this->streamCsv('custom_metrics_report.csv', $rows);
        }

        if ($format === 'pdf') {
            $blocks = [
                ['type' => 'title', 'text' => 'Custom Metrics Report'],
                ['type' => 'subtitle', 'text' => 'Selected metrics overview'],
                ['type' => 'spacer', 'text' => ''],
            ];

            foreach ($metrics as $metric) {
                $blocks[] = ['type' => 'section', 'text' => $metric['label']];
                if (($metric['value'] ?? '') !== '') {
                    $blocks[] = ['type' => 'body', 'text' => 'Value: ' . $metric['value']];
                }
                foreach ($metric['rows'] as $row) {
                    $blocks[] = ['type' => 'body', 'text' => $row['label'] . ': ' . $row['value']];
                }
                $blocks[] = ['type' => 'spacer', 'text' => ''];
            }

            $this->streamStyledPdf('custom_metrics_report.pdf', $blocks);
        }

        return redirect()->to(base_url('dashboard'))->with('error', 'Choose CSV or PDF for the report export.');
    }

    private function dashboardViewData(): array
    {
        $summary = $this->getDashboardSummary();

        return [
            'title' => 'Dashboard',
            'totalAlumni' => $summary['total_alumni'],
            'totalCertifications' => $summary['total_certifications'],
            'totalEmploymentRecords' => $summary['total_employment_records'],
            'topIndustries' => $summary['top_industries'],
            'topEmployers' => $summary['top_employers'],
            'metricDefinitions' => self::METRIC_DEFINITIONS,
        ];
    }

    private function getDashboardSummary(): array
    {
        $db = Database::connect();

        $summary = [
            'total_alumni' => 0,
            'total_certifications' => 0,
            'total_employment_records' => 0,
            'top_industries' => [],
            'top_employers' => [],
        ];

        if ($db->tableExists('users')) {
            $summary['total_alumni'] = $db->table('users')->countAllResults();
        }

        if ($db->tableExists('certifications')) {
            $summary['total_certifications'] = $db->table('certifications')->countAllResults();
        }

        if ($db->tableExists('employment_history')) {
            $summary['total_employment_records'] = $db->table('employment_history')->countAllResults();
            $fields = $db->getFieldNames('employment_history');

            if (in_array('industry_sector', $fields, true)) {
                $summary['top_industries'] = $db->query("
                    SELECT industry_sector, COUNT(*) AS total
                    FROM employment_history
                    WHERE industry_sector IS NOT NULL
                      AND industry_sector != ''
                    GROUP BY industry_sector
                    ORDER BY total DESC
                    LIMIT 5
                ")->getResultArray();
            }

            if (in_array('company_name', $fields, true)) {
                $summary['top_employers'] = $db->query("
                    SELECT company_name, COUNT(*) AS total
                    FROM employment_history
                    WHERE company_name IS NOT NULL
                      AND company_name != ''
                    GROUP BY company_name
                    ORDER BY total DESC
                    LIMIT 5
                ")->getResultArray();
            }
        }

        return $summary;
    }

    private function getAlumniFilters(): array
    {
        return [
            'programme' => trim((string) $this->request->getGet('programme')),
            'graduation_year' => trim((string) $this->request->getGet('graduation_year')),
            'industry_sector' => trim((string) $this->request->getGet('industry_sector')),
        ];
    }

    private function buildAlumniRows(array $filters): array
    {
        $db = Database::connect();
        $alumni = [];

        if (!$db->tableExists('users')) {
            return $alumni;
        }

        $userFields = $db->getFieldNames('users');
        $employmentFields = $db->tableExists('employment_history') ? $db->getFieldNames('employment_history') : [];
        $builder = $db->table('users u');

        $select = ['u.id', 'u.name', 'u.email'];
        if (in_array('programme', $userFields, true)) {
            $select[] = 'u.programme';
        }
        if (in_array('graduation_year', $userFields, true)) {
            $select[] = 'u.graduation_year';
        }
        if (in_array('graduation_date', $userFields, true)) {
            $select[] = 'u.graduation_date';
        }

        if (!empty($employmentFields) && in_array('industry_sector', $employmentFields, true)) {
            $builder->join('employment_history eh_filter', 'eh_filter.user_id = u.id', 'left');
        }

        $builder->select(implode(', ', $select));

        if ($filters['programme'] !== '' && in_array('programme', $userFields, true)) {
            $builder->like('u.programme', $filters['programme']);
        }

        if ($filters['industry_sector'] !== '' && !empty($employmentFields) && in_array('industry_sector', $employmentFields, true)) {
            $builder->where('eh_filter.industry_sector', $filters['industry_sector']);
        }

        $builder->groupBy('u.id');
        $alumni = $builder->get()->getResultArray();

        $userIds = array_values(array_filter(array_map(static fn ($row) => (int) ($row['id'] ?? 0), $alumni)));
        if (empty($userIds)) {
            return $alumni;
        }

        $employmentByUser = $this->groupRowsByUser(
            $db,
            'employment_history',
            ['industry_sector'],
            $userIds
        );
        $degreesByUser = $this->groupRowsByUser(
            $db,
            'degrees',
            ['completion_date'],
            $userIds
        );

        foreach ($alumni as &$row) {
            $userId = (int) ($row['id'] ?? 0);
            $employment = $employmentByUser[$userId] ?? [];
            $degrees = $degreesByUser[$userId] ?? [];

            $row['industry_sector'] = $this->implodeUniqueValues($employment, 'industry_sector');
            $row['graduation_date_display'] = $this->implodeUniqueValues($degrees, 'completion_date');

            if ($row['graduation_date_display'] === '') {
                $row['graduation_date_display'] = trim((string) ($row['graduation_date'] ?? ''));
            }

            $row['graduation_year_values'] = [];
            foreach (array_map('trim', explode(',', $row['graduation_date_display'])) as $dateValue) {
                if ($dateValue !== '' && strlen($dateValue) >= 4) {
                    $row['graduation_year_values'][] = substr($dateValue, 0, 4);
                }
            }

            if (empty($row['graduation_year_values']) && !empty($row['graduation_year'])) {
                $row['graduation_year_values'][] = (string) $row['graduation_year'];
            }

            $row['graduation_year_values'] = array_values(array_unique($row['graduation_year_values']));
        }
        unset($row);

        if ($filters['graduation_year'] !== '') {
            $alumni = array_values(array_filter($alumni, static function (array $row) use ($filters): bool {
                return in_array($filters['graduation_year'], $row['graduation_year_values'] ?? [], true);
            }));
        }

        return $alumni;
    }

    private function formatAlumniRowsForExport(array $rows): array
    {
        return array_map(static function (array $row): array {
            return [
                'name' => $row['name'] ?? '',
                'email' => $row['email'] ?? '',
                'programme' => $row['programme'] ?? '',
                'industry_sector' => $row['industry_sector'] ?? '',
                'graduation_date' => $row['graduation_date_display'] ?? '',
            ];
        }, $rows);
    }

    private function buildMetricsPayload(array $selectedMetrics): array
    {
        $summary = $this->getDashboardSummary();
        $metrics = [];

        foreach ($selectedMetrics as $metricKey) {
            if (!array_key_exists($metricKey, self::METRIC_DEFINITIONS)) {
                continue;
            }

            $metric = [
                'key' => $metricKey,
                'label' => self::METRIC_DEFINITIONS[$metricKey],
                'value' => '',
                'rows' => [],
            ];

            if ($metricKey === 'total_alumni') {
                $metric['value'] = (string) $summary['total_alumni'];
            } elseif ($metricKey === 'total_certifications') {
                $metric['value'] = (string) $summary['total_certifications'];
            } elseif ($metricKey === 'total_employment_records') {
                $metric['value'] = (string) $summary['total_employment_records'];
            } elseif ($metricKey === 'top_industries') {
                $metric['rows'] = array_map(static fn ($row) => [
                    'label' => $row['industry_sector'] ?? '',
                    'value' => $row['total'] ?? 0,
                ], $summary['top_industries']);
                $metric['value'] = (string) count($metric['rows']);
            } elseif ($metricKey === 'top_employers') {
                $metric['rows'] = array_map(static fn ($row) => [
                    'label' => $row['company_name'] ?? '',
                    'value' => $row['total'] ?? 0,
                ], $summary['top_employers']);
                $metric['value'] = (string) count($metric['rows']);
            }

            $metrics[] = $metric;
        }

        return $metrics;
    }

    private function buildGenericExportRows(): array
    {
        $db = Database::connect();
        $rows = [];

        if ($db->tableExists('users')) {
            $rows = $db->query("
                SELECT 
                    u.id,
                    u.name,
                    u.email,
                    " . ($this->columnExists($db, 'users', 'programme') ? "u.programme," : "NULL AS programme,") . "
                    " . ($this->columnExists($db, 'users', 'graduation_year') ? "u.graduation_year," : "NULL AS graduation_year,") . "
                    eh.company_name,
                    eh.job_title,
                    " . ($this->columnExists($db, 'employment_history', 'industry_sector') ? "eh.industry_sector" : "NULL AS industry_sector") . "
                FROM users u
                LEFT JOIN employment_history eh ON eh.user_id = u.id
                ORDER BY u.id DESC
            ")->getResultArray();
        }

        return $rows;
    }

    private function streamCsv(string $filename, array $rows): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if (!empty($rows)) {
            fputcsv($output, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($output, $row);
            }
        } else {
            fputcsv($output, ['No data found']);
        }

        fclose($output);
        exit;
    }

    private function streamStyledPdf(string $filename, array $blocks): void
    {
        $content = '';
        $y = 740;

        foreach ($blocks as $block) {
            $type = $block['type'] ?? 'body';
            $text = $this->escapePdfText((string) ($block['text'] ?? ''));

            if ($type === 'title') {
                $content .= "BT\n0.12 0.24 0.53 rg\n/F2 22 Tf\n1 0 0 1 50 {$y} Tm\n({$text}) Tj\nET\n";
                $y -= 30;
            } elseif ($type === 'subtitle') {
                $content .= "BT\n0.20 0.20 0.20 rg\n/F3 12 Tf\n1 0 0 1 50 {$y} Tm\n({$text}) Tj\nET\n";
                $y -= 20;
            } elseif ($type === 'section') {
                $content .= "BT\n0.05 0.10 0.25 rg\n/F2 15 Tf\n1 0 0 1 50 {$y} Tm\n({$text}) Tj\nET\n";
                $y -= 22;
            } elseif ($type === 'spacer') {
                $y -= 10;
            } else {
                $content .= "BT\n0 0 0 rg\n/F1 10 Tf\n1 0 0 1 65 {$y} Tm\n({$text}) Tj\nET\n";
                $y -= 16;
            }

            if ($y < 60) {
                break;
            }
        }

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R /F2 5 0 R /F3 6 0 R >> >> /Contents 7 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $pdf;
        exit;
    }

    private function escapePdfText(string $text): string
    {
        $text = preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $text);
    }

    private function columnExists($db, $table, $column)
    {
        if (!$db->tableExists($table)) {
            return false;
        }

        return in_array($column, $db->getFieldNames($table), true);
    }

    private function groupRowsByUser($db, string $table, array $preferredColumns, array $userIds): array
    {
        if (empty($userIds) || !$db->tableExists($table)) {
            return [];
        }

        $availableColumns = $db->getFieldNames($table);
        if (!in_array('user_id', $availableColumns, true)) {
            return [];
        }

        $select = array_values(array_intersect(array_merge(['user_id'], $preferredColumns), $availableColumns));
        $rows = $db->table($table)
            ->select(implode(', ', $select))
            ->whereIn('user_id', $userIds)
            ->get()
            ->getResultArray();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['user_id']][] = $row;
        }

        return $grouped;
    }

    private function implodeUniqueValues(array $rows, string $field): string
    {
        $values = [];

        foreach ($rows as $row) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                $values[$value] = true;
            }
        }

        return implode(', ', array_keys($values));
    }
}
