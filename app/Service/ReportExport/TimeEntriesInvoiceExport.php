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
class TimeEntriesInvoiceExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles
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
                'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                'I' => NumberFormat::FORMAT_NUMBER_00,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }

    }

    /**
     * @return array<int|string, array<string, array<string, bool>>>
     */
    public function styles(Worksheet $sheet): array
    {
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

        if ($this->exportFormat === ExportFormat::XLSX) {
            return [
                Date::PHPToExcel($model->group_day),
                implode('', array_map(fn($part) => strtoupper($part[0]), explode(' ', $model->user->name))),
                $model->description,
                $duration->totalHours,
                NumberFormat::toFormattedString($model->billable_rate ? (BigDecimal::ofUnscaledValue($model->billable_rate, 2)) : 0, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1),
                NumberFormat::toFormattedString($model->billable_rate ? (BigDecimal::ofUnscaledValue($duration->totalHours * $model->billable_rate, 2)) : 0, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1),
            ];
        } elseif ($this->exportFormat === ExportFormat::ODS) {
            return [
                Date::dateTimeToExcel($model->start),
                implode('', array_map(fn($part) => strtoupper($part[0]), explode(' ', $model->user->name))),
                $model->description,
                $model?->totalhours,
                $model?->billable_rate,
                $model?->totalhours * $model?->billable_rate,
            ];
        } else {
            throw new LogicException('Unsupported export format.');
        }
    }
}
