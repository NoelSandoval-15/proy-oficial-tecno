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

class PurchaseNotesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithTitle, ShouldAutoSize
{
    private Collection $rows;

    public function __construct(
        private readonly Collection $purchaseNotes
    ) {
        $this->rows = $this->purchaseNotes->flatMap(function ($purchaseNote) {
            if ($purchaseNote->details_purchases->isEmpty()) {
                return collect([
                    [
                        'purchaseNote' => $purchaseNote,
                        'detail' => null,
                    ],
                ]);
            }

            return $purchaseNote->details_purchases->map(function ($detail) use ($purchaseNote) {
                return [
                    'purchaseNote' => $purchaseNote,
                    'detail' => $detail,
                ];
            });
        });
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function title(): string
    {
        return 'Compras';
    }

    public function headings(): array
    {
        return [
            'Fecha',
            'Hora',
            'Proveedor',
            'Administrador',
            'Insumo',
            'Cantidad',
            'Subtotal Bs',
            'Total compra Bs',
        ];
    }

    public function map($row): array
    {
        $purchaseNote = $row['purchaseNote'];
        $detail = $row['detail'];

        return [
            data_get($purchaseNote, 'date', '-'),
            data_get($purchaseNote, 'hour', '-'),
            data_get($purchaseNote, 'suppliers.name', 'Sin proveedor'),
            data_get($purchaseNote, 'users.name', 'Sin usuario'),
            $detail ? data_get($detail, 'insumos.name', 'Insumo eliminado') : 'Sin detalle',
            $detail ? (int) data_get($detail, 'amount', 0) : 0,
            $detail ? number_format((float) data_get($detail, 'price_purchase', 0), 2, '.', '') : '0.00',
            number_format((float) data_get($purchaseNote, 'total_price', 0), 2, '.', ''),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 16,
            'B' => 14,
            'C' => 30,
            'D' => 28,
            'E' => 38,
            'F' => 12,
            'G' => 16,
            'H' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->rows->count() + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:H{$lastRow}");
                $sheet->getRowDimension(1)->setRowHeight(30);

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

                    $sheet->getStyle("F{$row}:H{$row}")->applyFromArray([
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                        ],
                    ]);
                }
            },
        ];
    }
}
