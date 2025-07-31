<?php

declare(strict_types=1);

namespace App\Service\ReportExport;

use App\Enums\ExportFormat;
use App\Models\TimeEntry;
use App\Service\IntervalService;
use Brick\Math\BigDecimal;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use LogicException;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @implements WithMapping<TimeEntry>
 */
class TimeEntriesInvoiceExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, \Maatwebsite\Excel\Concerns\WithEvents
{
    use Exportable;

    /**
     * @var Builder<TimeEntry>
     */
    private Builder $builder;

    private ExportFormat $exportFormat;

    private string $timezone;

    private Request $request;

    /**
     * @param  Builder<TimeEntry>  $builder
     */
    public function __construct(Builder $builder, ExportFormat $exportFormat, string $timezone, Request $request)
    {
        $this->builder = $builder;
        $this->exportFormat = $exportFormat;
        $this->timezone = $timezone;
        $this->request = $request;
    }

    /**
     * @return Builder<TimeEntry>
     */
    public function query(): Builder
    {
        return $this->builder;
    }

    /**
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        if ($this->exportFormat === ExportFormat::XLSX) {
            return [
                'A' => 'yyyy-mm-dd',
                'D' => NumberFormat::FORMAT_NUMBER_00,
                'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Pastikan total fee juga pakai format angka
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                'I' => NumberFormat::FORMAT_NUMBER_00,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }

    public function styles(Worksheet $sheet): array
    {
        // Set lebar kolom Description (kolom C) menjadi 130
        $sheet->getColumnDimension('C')->setWidth(130);
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @return string[]
     */
    public function headings(): array
    {
        return [
            'Date',
            'Name',
            'Description',
            'Hours',
            'Rate',
            'Total Fee',
        ];
    }

    /**
     * @param  TimeEntry  $model
     * @return array<int, string|float|null>
     */
    public function map($model): array
    {
        $interval = app(IntervalService::class);
        $duration = $interval->roundTime($model->totalhours, $this->request->rounding, (int) $this->request->rounding_value);

        // Format description: capitalize first letter, add period if missing
        $description = trim($model->description);
        if ($description !== '') {
            $description = ucfirst($description);
            if (!preg_match('/[.!?]$/', $description)) {
                $description .= '.';
            }
        }

        if ($this->exportFormat === ExportFormat::XLSX) {
            // Excel formula for Total Fee: =D{row}*E{row}
            // Data starts at row 2 (after headings)
            static $rowNumber = 2;
            $rate = $model->billable_rate / 100; // convert cents to normal value
            $result = [
                Date::PHPToExcel($model->group_day),
                implode('', array_map(fn($part) => strtoupper($part[0]), explode(' ', $model->user->name))),
                $description,
                $duration->totalHours,
                $rate,
                "=D{$rowNumber}*E{$rowNumber}",
            ];
            $rowNumber++;
            return $result;
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            $rate = $model?->billable_rate ? $model->billable_rate / 100 : null;
            return [
                Date::dateTimeToExcel($model->start),
                implode('', array_map(fn($part) => strtoupper($part[0]), explode(' ', $model->user->name))),
                $description,
                $model?->totalhours,
                $rate,
                $model?->totalhours * $rate,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }

    public function registerEvents(): array
    {
        return [
            \Maatwebsite\Excel\Events\AfterSheet::class => function(\Maatwebsite\Excel\Events\AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                // Set lebar kolom Description (kolom C) menjadi 130 setelah autosize
                $sheet->getColumnDimension('C')->setWidth(130);
                $highestDataRow = $sheet->getHighestDataRow();
                $totalRow = $highestDataRow + 1;
                // Total Jam (kolom D)
                $sheet->setCellValue("C{$totalRow}", 'Total');
                $sheet->setCellValue("D{$totalRow}", "=SUM(D2:D{$highestDataRow})");
                // Total Fee (kolom F)
                $sheet->setCellValue("F{$totalRow}", "=SUM(F2:F{$highestDataRow})");
                // Format angka untuk total fee (kolom F)
                $sheet->getStyle("F{$totalRow}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
                // Bold untuk baris total
                $sheet->getStyle("C{$totalRow}:F{$totalRow}")->getFont()->setBold(true);
            }
        ];
    }
}
