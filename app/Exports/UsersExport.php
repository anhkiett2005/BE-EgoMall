<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class UsersExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    ShouldAutoSize,
    WithColumnFormatting
{
    /**
     * @param Collection $users  // collection đã load sẵn từ Service, có eager load 'role'
     * @param string $roleDisplayName // tên hiển thị role (fallback)
     */
    public function __construct(
        protected Collection $users,
        protected string $roleDisplayName = ''
    ) {}

    private int $row = 0; // STT an toàn hơn static

    public function collection(): Collection
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'STT',
            'ID',
            'Họ tên',
            'Email',
            'SĐT',
            'Email xác thực',
            'Trạng thái',
            'Vai trò',
            'Ngày tạo',
            'Ngày cập nhật',
        ];
    }

    public function map($user): array
    {
        $this->row++;

        // NOTE: Excel xử lý Date tốt hơn nếu ta trả đối tượng DateTime/Carbon,
        // sau đó format cột ở columnFormats(). Null => để trống.
        $verifiedAt = $user->email_verified_at
            ? Carbon::parse($user->email_verified_at)
            : null;

        $createdAt = $user->created_at ? Carbon::parse($user->created_at) : null;
        $updatedAt = $user->updated_at ? Carbon::parse($user->updated_at) : null;

        $active = $user->is_active ? 'Hoạt động' : 'Ngưng hoạt động';

        return [
            $this->row,
            $user->id,
            $this->xlsSafe($user->name),
            $this->xlsSafe($user->email),
            // Phone để TEXT, chống mất số 0 đầu & công thức
            $this->xlsSafe((string)($user->phone ?? '')),
            // Trả về Excel serial date (nếu có), để lọc/sắp xếp đúng
            $verifiedAt ? ExcelDate::dateTimeToExcel($verifiedAt) : null,
            $active,
            $user->role?->display_name ?? $user->role?->name ?? $this->roleDisplayName,
            $createdAt ? ExcelDate::dateTimeToExcel($createdAt) : null,
            $updatedAt ? ExcelDate::dateTimeToExcel($updatedAt) : null,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Dùng wrapper trực tiếp, không getDelegate
                $sheet = $event->sheet;

                // Freeze + AutoFilter
                $sheet->freezePane('A2');
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

                // Range header động
                $highestColumn = $sheet->getHighestColumn(); // ví dụ 'J'
                $highestRow    = $sheet->getHighestRow();
                $headerRange   = "A1:{$highestColumn}1";
                $bodyRange   = "A2:{$highestColumn}{$highestRow}";

                // Căn giữa header
                $sheet->getStyle($headerRange)->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER);


                $sheet->getStyle($headerRange)->getFont()->setBold(true);

                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFCCCCCC');

                // === Tô màu đỏ nhạt cho hàng "Ngưng hoạt động" ===
                // tìm cột "Trạng thái" theo header
                $headers = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L'];
                $statusCol = null;
                foreach ($headers as $col) {
                    $val = (string) $sheet->getCell("{$col}1")->getCalculatedValue();
                    if (mb_strtolower(trim($val)) === 'trạng thái') {
                        $statusCol = $col;
                        break;
                    }
                }
                if ($statusCol) {
                    $highestRow = $sheet->getHighestDataRow();
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $status = (string) $sheet->getCell("{$statusCol}{$row}")->getCalculatedValue();
                        if (mb_strtolower(trim($status)) === 'ngưng hoạt động') {
                            $sheet->getStyle("A{$row}:{$highestColumn}{$row}")
                                ->getFill()->setFillType(Fill::FILL_SOLID)
                                ->getStartColor()->setARGB('FFFFC7CE');
                        }
                    }
                }


                // Viền header (đổi MEDIUM để bạn dễ thấy; sau thấy ok đổi lại THIN)
                $sheet->getStyle($headerRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_MEDIUM);

                // Tùy chọn: in nằm ngang + fit 1 trang
                $sheet->getDelegate()->getPageSetup()->setFitToWidth(1);
                $sheet->getDelegate()->getPageSetup()->setFitToHeight(0);
                $sheet->getDelegate()->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

                // Chỉ kẻ viền body khi có dữ liệu (>= row 2)
                if ($highestRow >= 2) {
                    $sheet->getStyle($bodyRange)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color'       => ['argb' => 'FF000000'],
                            ],
                        ],
                    ]);
                }
            },
        ];
    }



    public function columnFormats(): array
    {
        return [
            // Cột E (SĐT) dạng TEXT
            'E' => NumberFormat::FORMAT_TEXT,
            // Cột F, I, J là datetime chuẩn Excel
            'F' => 'dd-mm-yyyy hh:mm:ss',
            'I' => 'dd-mm-yyyy hh:mm:ss',
            'J' => 'dd-mm-yyyy hh:mm:ss',
        ];
    }

    /**
     * Tránh "Excel formula injection" khi ô bắt đầu bằng = + - @
     */
    private function xlsSafe(?string $v): string
    {
        if ($v === null) return '';
        $v = trim($v);
        return preg_match('/^[=\-\+@]/', $v) ? "'" . $v : $v;
    }
}
