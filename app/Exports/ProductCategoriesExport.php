<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductCategoriesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $categories
    ) {
    }

    public function collection(): Collection
    {
        return $this->categories;
    }

    public function title(): string
    {
        return 'Categorías';
    }

    public function headings(): array
    {
        return [
            'Categoría',
            'Cantidad subcategorías',
            'Cantidad productos',
            'Subcategorías',
            'Creado',
        ];
    }

    public function map($category): array
    {
        $subCategories = collect(data_get($category, 'sub_categories', []));

        return [
            data_get($category, 'name', '-'),
            (int) data_get($category, 'sub_categories_count', $subCategories->count()),
            $subCategories->sum('products_count'),
            $subCategories
                ->map(fn ($subCategory) => data_get($subCategory, 'name', '-') . ' (' . data_get($subCategory, 'products_count', 0) . ' productos)')
                ->join("\n"),
            data_get($category, 'created_at', '-'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 22,
            'C' => 22,
            'D' => 70,
            'E' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->categories->count() + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:E{$lastRow}");
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->getStyle('A1:E1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EF4444'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A1:E{$lastRow}")->applyFromArray([
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_TOP,
                        'wrapText' => true,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'E5E7EB'],
                        ],
                    ],
                ]);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(54);

                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB'],
                            ],
                        ]);
                    }

                    $sheet->getStyle("B{$row}:C{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '111827'],
                        ],
                    ]);
                }
            },
        ];
    }
}
