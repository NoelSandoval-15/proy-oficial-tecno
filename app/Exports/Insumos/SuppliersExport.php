<?php

namespace App\Exports\Insumos;

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

class SuppliersExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $suppliers
    ) {
    }

    public function collection(): Collection
    {
        return $this->suppliers;
    }

    public function title(): string
    {
        return 'Proveedores';
    }

    public function headings(): array
    {
        return [
            'Proveedor',
            'Descripción',
            'Teléfono',
            // 'Imagen',
            'Creado',
            // 'Actualizado',
        ];
    }

    public function map($supplier): array
    {
        return [
            data_get($supplier, 'name', '-'),
            data_get($supplier, 'description', '-'),
            data_get($supplier, 'telephone', '-'),
            // data_get($supplier, 'url_photo', 'Sin imagen') ?: 'Sin imagen',
            optional(data_get($supplier, 'created_at'))->format('d/m/Y H:i'),
            // optional(data_get($supplier, 'updated_at'))->format('d/m/Y H:i'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 45,
            'C' => 16,
            'D' => 45,
            'E' => 20,
            'F' => 20,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->suppliers->count() + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:F{$lastRow}");
                $sheet->getRowDimension(1)->setRowHeight(30);

                $sheet->getStyle('A1:F1')->applyFromArray([
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

                $sheet->getStyle("A1:F{$lastRow}")->applyFromArray([
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
                        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB'],
                            ],
                        ]);
                    }

                    $sheet->getStyle("C{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                }
            },
        ];
    }
}
