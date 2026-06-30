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

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithEvents, WithTitle, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $logs
    ) {
    }

    public function collection(): Collection
    {
        return $this->logs;
    }

    public function title(): string
    {
        return 'Bitácora';
    }

    public function headings(): array
    {
        return [
            'Usuario',
            'Acción',
            'Módulo',
            'Método',
            'Ruta / URL',
            'Hora',
            'Fecha',
            'IP',
            'Estado',
        ];
    }

    public function map($log): array
    {
        $userName = data_get($log, 'user_name', 'Sistema');
        $userEmail = data_get($log, 'user_email', 'Sin correo');
        $userRole = data_get($log, 'user_role', 'Sin rol');

        $routeName = data_get($log, 'route_name', '-');
        $url = data_get($log, 'url', '-');

        return [
            "{$userName}\n{$userEmail}\nRol: {$userRole}",
            data_get($log, 'action', '-'),
            data_get($log, 'module', '-'),
            data_get($log, 'method', '-'),
            "{$routeName}\n{$url}",
            data_get($log, 'hora_bolivia', data_get($log, 'time', '-')),
            data_get($log, 'fecha_bolivia', data_get($log, 'date', '-')),
            data_get($log, 'ip_address', '-'),
            data_get($log, 'status_code', '-'),
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 34,
            'B' => 16,
            'C' => 22,
            'D' => 12,
            'E' => 70,
            'F' => 13,
            'G' => 15,
            'H' => 18,
            'I' => 12,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                $lastRow = $this->logs->count() + 1;

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:I{$lastRow}");

                $sheet->getRowDimension(1)->setRowHeight(26);

                $sheet->getStyle("A1:I1")->applyFromArray([
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

                $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
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

                $sheet->getStyle("A2:I{$lastRow}")->applyFromArray([
                    'font' => [
                        'size' => 10,
                        'color' => ['rgb' => '111827'],
                    ],
                ]);

                for ($row = 2; $row <= $lastRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(48);

                    if ($row % 2 === 0) {
                        $sheet->getStyle("A{$row}:I{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F9FAFB'],
                            ],
                        ]);
                    }

                    $log = $this->logs->get($row - 2);

                    $role = data_get($log, 'user_role', 'Sin rol');
                    $action = data_get($log, 'action', '-');
                    $method = data_get($log, 'method', '-');
                    $status = (int) data_get($log, 'status_code', 0);

                    $sheet->getStyle("A{$row}")->applyFromArray($this->roleStyle($role));
                    $sheet->getStyle("B{$row}")->applyFromArray($this->actionStyle($action));
                    $sheet->getStyle("D{$row}")->applyFromArray($this->methodStyle($method));
                    $sheet->getStyle("I{$row}")->applyFromArray($this->statusStyle($status));
                }
            },
        ];
    }

    private function roleStyle(string $role): array
    {
        return match ($role) {
            'Master' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '991B1B']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEE2E2'],
                ],
            ],
            'Administrador' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
            'Mesero' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '92400E']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
            ],
            'Cliente' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'],
                ],
            ],
            default => [
                'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ],
        };
    }

    private function actionStyle(string $action): array
    {
        return match ($action) {
            'ELIMINAR' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
            ],
            'CREAR' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
            ],
            'EDITAR' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
            ],
            'EXPORTAR' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '7E22CE']],
            ],
            'BUSCAR' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'A16207']],
            ],
            'CAMBIAR_TEMA' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'BE185D']],
            ],
            default => [
                'font' => ['bold' => true, 'color' => ['rgb' => '334155']],
            ],
        };
    }

    private function methodStyle(string $method): array
    {
        return match ($method) {
            'POST' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
            ],
            'PATCH', 'PUT' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '1D4ED8']],
            ],
            'DELETE' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
            ],
            default => [
                'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
            ],
        };
    }

    private function statusStyle(int $status): array
    {
        if ($status >= 200 && $status < 300) {
            return [
                'font' => ['bold' => true, 'color' => ['rgb' => '047857']],
            ];
        }

        if ($status >= 400) {
            return [
                'font' => ['bold' => true, 'color' => ['rgb' => 'B91C1C']],
            ];
        }

        return [
            'font' => ['bold' => true, 'color' => ['rgb' => '475569']],
        ];
    }
}
