<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BookingExcelExportService
{
    /**
     * Complete catalog of exportable columns with labels and widths.
     *
     * @return array<string, array{label: string, width: int}>
     */
    public function availableColumns(): array
    {
        return [
            'code' => ['label' => 'Kode Event', 'width' => 16],
            'title' => ['label' => 'Nama Event', 'width' => 28],
            'destination' => ['label' => 'Destinasi', 'width' => 20],
            'venue' => ['label' => 'Venue', 'width' => 24],
            'event_type' => ['label' => 'Jenis Event', 'width' => 22],
            'start_date' => ['label' => 'Tanggal Mulai', 'width' => 16],
            'end_date' => ['label' => 'Tanggal Selesai', 'width' => 16],
            'load_at' => ['label' => 'Loading', 'width' => 20],
            'unload_at' => ['label' => 'Unloading', 'width' => 20],
            'client' => ['label' => 'Client', 'width' => 24],
            'organizer' => ['label' => 'Organizer', 'width' => 24],
            'pic' => ['label' => 'PIC Sales', 'width' => 20],
            'guest_count' => ['label' => 'Jumlah Tamu (estimasi)', 'width' => 22],
            'price' => ['label' => 'Harga/Price (Rp)', 'width' => 18],
            'status' => ['label' => 'Status', 'width' => 14],
            'notes' => ['label' => 'Catatan', 'width' => 34],
        ];
    }

    /**
     * Default column keys matching the "Add Event" (Tambah Event) form fields.
     *
     * @return array<int, string>
     */
    public function defaultColumnKeys(): array
    {
        return [
            'title',
            'venue',
            'event_type',
            'start_date',
            'end_date',
            'load_at',
            'unload_at',
            'client',
            'organizer',
            'pic',
            'guest_count',
            'price',
            'status',
            'notes',
        ];
    }

    /**
     * Validate and normalize a list of requested column keys against the catalog.
     *
     * @param  array<int, string>|null  $columns
     * @return array<int, string>
     */
    public function resolveColumnKeys(?array $columns = null): array
    {
        if ($columns === null || $columns === []) {
            return $this->defaultColumnKeys();
        }

        $catalog = $this->availableColumns();
        $valid = [];

        foreach (array_keys($catalog) as $key) {
            if (in_array($key, $columns, true)) {
                $valid[] = $key;
            }
        }

        return $valid !== [] ? $valid : $this->defaultColumnKeys();
    }

    /**
     * Columns matching the selected keys (or the "Add Event" form fields by default).
     *
     * @param  array<int, string>|null  $columns
     * @return array<int, string>
     */
    public function headings(?array $columns = null): array
    {
        $catalog = $this->availableColumns();
        $keys = $this->resolveColumnKeys($columns);

        return array_values(array_map(fn (string $key) => $catalog[$key]['label'], $keys));
    }

    /**
     * Map a Booking model to a keyed associative array for all available columns.
     *
     * @return array<string, string|int|float|null>
     */
    public function mapKeyedRow(Booking $booking): array
    {
        $clientName = $booking->client?->name ?? '';
        $organizerName = $booking->organizer?->name ?? $clientName;

        return [
            'code' => $booking->code ?? '',
            'title' => $booking->title,
            'destination' => $booking->venue?->destination?->name ?? '',
            'venue' => $booking->venue?->name ?? '',
            'event_type' => $booking->eventType?->name ?? '',
            'start_date' => $booking->start_date?->format('Y-m-d') ?? '',
            'end_date' => $booking->end_date?->format('Y-m-d') ?? '',
            'load_at' => $booking->load_at?->format('Y-m-d H:i') ?? '',
            'unload_at' => $booking->unload_at?->format('Y-m-d H:i') ?? '',
            'client' => $clientName,
            'organizer' => $organizerName,
            'pic' => $booking->pic?->name ?? '',
            'guest_count' => $booking->guest_count !== null ? (int) $booking->guest_count : null,
            'price' => $booking->price !== null ? (float) $booking->price : null,
            'status' => ucfirst((string) $booking->status),
            'notes' => $booking->notes ?? '',
        ];
    }

    /**
     * Map a Booking model to an indexed row matching the selected columns.
     *
     * @param  array<int, string>|null  $columns
     * @return array<int, string|int|float|null>
     */
    public function mapRow(Booking $booking, ?array $columns = null): array
    {
        $keyed = $this->mapKeyedRow($booking);
        $keys = $this->resolveColumnKeys($columns);

        return array_values(array_map(fn (string $key) => $keyed[$key] ?? '', $keys));
    }

    /**
     * Generate a temporary .xlsx file and return a BinaryFileResponse download.
     *
     * @param  Collection<int, Booking>  $bookings
     * @param  array<int, string>|null  $columns
     */
    public function download(Collection $bookings, string $filename = 'daftar-event.xlsx', ?array $columns = null): BinaryFileResponse
    {
        $tempFile = $this->generateXlsxFile($bookings, $columns);

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Build the .xlsx file on disk and return its path.
     *
     * @param  Collection<int, Booking>  $bookings
     * @param  array<int, string>|null  $columns
     */
    public function generateXlsxFile(Collection $bookings, ?array $columns = null): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'bookings_export_');
        if ($tempFile === false) {
            throw new RuntimeException('Unable to create temporary file for Excel export.');
        }

        $zip = new ZipArchive;
        if ($zip->open($tempFile, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to open ZipArchive for Excel export.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($bookings, $columns));

        $zip->close();

        return $tempFile;
    }

    private function contentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
    <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
    <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
</Types>
XML;
    }

    private function rootRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>
XML;
    }

    private function workbookXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheets>
        <sheet name="Daftar Event" sheetId="1" r:id="rId1"/>
    </sheets>
</workbook>
XML;
    }

    private function workbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
    <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    private function stylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    <numFmts count="1">
        <numFmt numFmtId="164" formatCode="#,##0"/>
    </numFmts>
    <fonts count="2">
        <font>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
        <font>
            <b/>
            <sz val="11"/>
            <name val="Calibri"/>
        </font>
    </fonts>
    <fills count="3">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        <fill>
            <patternFill patternType="solid">
                <fgColor rgb="FFF1F5F9"/>
                <bgColor indexed="64"/>
            </patternFill>
        </fill>
    </fills>
    <borders count="2">
        <border>
            <left/><right/><top/><bottom/><diagonal/>
        </border>
        <border>
            <left style="thin"><color rgb="FFE2E8F0"/></left>
            <right style="thin"><color rgb="FFE2E8F0"/></right>
            <top style="thin"><color rgb="FFE2E8F0"/></top>
            <bottom style="thin"><color rgb="FFE2E8F0"/></bottom>
            <diagonal/>
        </border>
    </borders>
    <cellStyleXfs count="1">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>
    </cellStyleXfs>
    <cellXfs count="3">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
        <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>
        <xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyBorder="1"/>
    </cellXfs>
</styleSheet>
XML;
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @param  array<int, string>|null  $columns
     */
    private function worksheetXml(Collection $bookings, ?array $columns = null): string
    {
        $catalog = $this->availableColumns();
        $keys = $this->resolveColumnKeys($columns);

        $colsXml = '<cols>';
        foreach ($keys as $idx => $key) {
            $colNum = $idx + 1;
            $width = $catalog[$key]['width'] ?? 20;
            $colsXml .= sprintf('<col min="%d" max="%d" width="%d" customWidth="1"/>', $colNum, $colNum, $width);
        }
        $colsXml .= '</cols>';

        $rowsXml = '';

        // Header row (row 1, style s="1")
        $rowsXml .= '<row r="1">';
        foreach ($this->headings($keys) as $colIdx => $heading) {
            $cellRef = $this->columnLetter($colIdx + 1).'1';
            $rowsXml .= $this->inlineStringCell($cellRef, $heading, 1);
        }
        $rowsXml .= '</row>';

        // Data rows
        $rowNum = 2;
        foreach ($bookings as $booking) {
            $rowValues = $this->mapRow($booking, $keys);
            $rowsXml .= sprintf('<row r="%d">', $rowNum);

            foreach ($rowValues as $colIdx => $val) {
                $cellRef = $this->columnLetter($colIdx + 1).$rowNum;
                if (is_int($val) || is_float($val)) {
                    $rowsXml .= sprintf('<c r="%s" s="2" t="n"><v>%s</v></c>', $cellRef, $val);
                } else {
                    $rowsXml .= $this->inlineStringCell($cellRef, (string) ($val ?? ''), 0);
                }
            }

            $rowsXml .= '</row>';
            $rowNum++;
        }

        return <<<XML
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
    {$colsXml}
    <sheetData>{$rowsXml}</sheetData>
</worksheet>
XML;
    }

    private function inlineStringCell(string $cellRef, string $value, int $styleIndex): string
    {
        // Strip invalid XML control characters
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
        $escaped = htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<c r="%s" s="%d" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
            $cellRef,
            $styleIndex,
            $escaped
        );
    }

    private function columnLetter(int $colIndex): string
    {
        $letter = '';
        while ($colIndex > 0) {
            $mod = ($colIndex - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $colIndex = intdiv($colIndex - $mod, 26);
        }

        return $letter;
    }
}

