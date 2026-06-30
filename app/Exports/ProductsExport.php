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

class ProductsExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $products
    ) {
    }

    public function collection(): Collection
    {
        return $this->products;
    }

    public function title(): string
    {
        return 'Productos';
    }

    public function headings(): array
    {
        return [
            'Producto',
            'Categoría',
            'Subcategoría',
            'Precio Bs',
            'Cantidad',
            'Estado stock',
            'Imagen',
            'Creado',
        ];
    }

    public function map($product): array
    {
        $amount = (int) data_get($product, 'amount', 0);

        return [
            data_get($product, 'name', '-'),
            data_get($product, 'sub_categorie.categorie.name', 'Sin categoría'),
            data_get($product, 'sub_categorie.name', 'Sin subcategoría'),
            number_format((float) data_get($product, 'price', 0), 2, '.', ''),
            $amount,
            $this->stockLabel($amount),
            data_get($product, 'url_photo', 'Sin imagen') ?: 'Sin imagen',
            data_get($product, 'created_at', '-'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 32,
            'B' => 24,
            'C' => 26,
            'D' => 14,
            'E' => 12,
            'F' => 16,
            'G' => 46,
            'H' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->products->count() + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:H{$lastRow}");
                $sheet->getRowDimension(1)->setRowHeight(28);

                $sheet->getStyle('A1:H1')->applyFromArray([
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

                $sheet->getStyle("A1:H{$lastRow}")->applyFromArray([
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
                    $sheet->getRowDimension($row)->setRowHeight(38);

                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB'],
                            ],
                        ]);
                    }

                    $product = $this->products->get($row - 2);
                    $amount = (int) data_get($product, 'amount', 0);

                    $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);

                    $sheet->getStyle("F{$row}")->applyFromArray($this->stockStyle($amount));
                }
            },
        ];
    }

    private function stockLabel(int $amount): string
    {
        if ($amount <= 0) {
            return 'Sin stock';
        }

        if ($amount <= 5) {
            return 'Stock bajo';
        }

        return 'Disponible';
    }

    private function stockStyle(int $amount): array
    {
        if ($amount <= 0) {
            return [
                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEE2E2'],
                ],
            ];
        }

        if ($amount <= 5) {
            return [
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
            ];
        }

        return [
            'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D1FAE5'],
            ],
        ];
    }
}
