<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CollectionImportService
{
    // ------------------------------------------------------------------ //
    // Kolom template per unit type
    // ------------------------------------------------------------------ //

    private const LIBRARY_COLUMNS = [
        'record_code'          => 'Kode Record *',
        'collection_type'      => 'Tipe Koleksi *',
        'title'                => 'Judul *',
        'subtitle'             => 'Sub Judul',
        'description'          => 'Deskripsi',
        'language_code'        => 'Kode Bahasa',
        'rights_status'        => 'Status Hak Cipta',
        'date_display'         => 'Tanggal Tampil',
        'year_start'           => 'Tahun Mulai',
        'year_end'             => 'Tahun Selesai',
        'category_id'          => 'ID Kategori',
        'publication_status'   => 'Status Publikasi',
        'visibility'           => 'Visibilitas',
        'bibliographic_level'  => 'Level Bibliografi *',
        'isbn13'               => 'ISBN-13',
        'isbn10'               => 'ISBN-10',
        'issn'                 => 'ISSN',
        'doi'                  => 'DOI',
        'publisher_name'       => 'Nama Penerbit',
        'publisher_place'      => 'Tempat Terbit',
        'edition'              => 'Edisi',
        'publication_year'     => 'Tahun Terbit',
        'ddc_classification'   => 'Klasifikasi DDC',
        'call_number'          => 'Call Number',
        'physical_extent'      => 'Deskripsi Fisik',
        'pages'                => 'Jumlah Halaman',
        'series_title'         => 'Judul Seri',
        'creators'             => 'Creator (nama:peran:utama|...)',
        'subjects'             => 'Subjek (term:tipe|...)',
    ];

    private const MUSEUM_COLUMNS = [
        'record_code'          => 'Kode Record *',
        'collection_type'      => 'Tipe Koleksi *',
        'title'                => 'Judul *',
        'subtitle'             => 'Sub Judul',
        'description'          => 'Deskripsi',
        'language_code'        => 'Kode Bahasa',
        'rights_status'        => 'Status Hak Cipta',
        'date_display'         => 'Tanggal Tampil',
        'year_start'           => 'Tahun Mulai',
        'year_end'             => 'Tahun Selesai',
        'category_id'          => 'ID Kategori',
        'publication_status'   => 'Status Publikasi',
        'visibility'           => 'Visibilitas',
        'inventory_number'     => 'Nomor Inventaris *',
        'object_name'          => 'Nama Objek *',
        'object_type_label'    => 'Label Tipe Objek *',
        'classification'       => 'Klasifikasi *',
        'maker_name'           => 'Nama Pembuat',
        'culture'              => 'Budaya',
        'period_display'       => 'Periode',
        'material_summary'     => 'Ringkasan Material',
        'technique_summary'    => 'Ringkasan Teknik',
        'condition_current'    => 'Kondisi Saat Ini',
        'provenance_history'   => 'Riwayat Provenance',
        'acquisition_method'   => 'Metode Akuisisi',
        'creators'             => 'Creator (nama:peran:utama|...)',
        'subjects'             => 'Subjek (term:tipe|...)',
    ];

    // ------------------------------------------------------------------ //
    // Contoh data untuk template
    // ------------------------------------------------------------------ //

    private const LIBRARY_EXAMPLE = [
        'record_code'          => 'LIB-BK-2025-0001',
        'collection_type'      => 'book',
        'title'                => 'Contoh Judul Buku',
        'subtitle'             => 'Sub Judul Opsional',
        'description'          => 'Deskripsi singkat buku',
        'language_code'        => 'ind',
        'rights_status'        => 'In Copyright',
        'date_display'         => '2025',
        'year_start'           => '2025',
        'year_end'             => '',
        'category_id'          => '',
        'publication_status'   => 'draft',
        'visibility'           => 'public',
        'bibliographic_level'  => 'monograph',
        'isbn13'               => '9789876543210',
        'isbn10'               => '',
        'issn'                 => '',
        'doi'                  => '',
        'publisher_name'       => 'Penerbit Brawijaya',
        'publisher_place'      => 'Malang',
        'edition'              => '1',
        'publication_year'     => '2025',
        'ddc_classification'   => '959.8',
        'call_number'          => '959.8 CON',
        'physical_extent'      => '1 jilid',
        'pages'                => '300',
        'series_title'         => '',
        'creators'             => 'Budi Santoso:author:1|Siti Rahayu:contributor:0',
        'subjects'             => 'Sejarah Indonesia:topical|Malang:geographic',
    ];

    private const MUSEUM_EXAMPLE = [
        'record_code'          => 'MUS-ART-2025-0001',
        'collection_type'      => 'artifact',
        'title'                => 'Contoh Nama Artefak',
        'subtitle'             => '',
        'description'          => 'Deskripsi singkat artefak',
        'language_code'        => 'ind',
        'rights_status'        => 'Public Domain',
        'date_display'         => 'abad ke-19',
        'year_start'           => '1850',
        'year_end'             => '1900',
        'category_id'          => '',
        'publication_status'   => 'draft',
        'visibility'           => 'public',
        'inventory_number'     => 'INV-2025-0001',
        'object_name'          => 'Keris',
        'object_type_label'    => 'Senjata Tradisional',
        'classification'       => 'Benda Budaya',
        'maker_name'           => 'Empu Brawijaya',
        'culture'              => 'Jawa',
        'period_display'       => 'Periode Mataram Islam',
        'material_summary'     => 'Besi, Baja',
        'technique_summary'    => 'Tempa',
        'condition_current'    => 'good',
        'provenance_history'   => 'Diperoleh dari keluarga bangsawan Malang',
        'acquisition_method'   => 'donation',
        'creators'             => 'Empu Brawijaya:creator:1',
        'subjects'             => 'Senjata Tradisional:topical|Jawa:geographic',
    ];

    public function __construct(
        private readonly LibraryCollectionService $libraryService,
        private readonly MuseumCollectionService $museumService,
    ) {}

    // ------------------------------------------------------------------ //
    // Generate template XLSX
    // ------------------------------------------------------------------ //

    public function generateTemplate(string $unitType): string
    {
        $columns = $unitType === 'library' ? self::LIBRARY_COLUMNS : self::MUSEUM_COLUMNS;
        $example = $unitType === 'library' ? self::LIBRARY_EXAMPLE : self::MUSEUM_EXAMPLE;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data');

        // Row tinggi
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getRowDimension(3)->setRowHeight(20);

        $col = 'A';
        foreach ($columns as $key => $label) {
            // ---- Row 1: KEY (dibaca sistem saat import — JANGAN DIUBAH) ----
            $sheet->setCellValueExplicit(
                $col . '1',
                $key,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->getStyle($col . '1')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A5F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            // ---- Row 2: LABEL (nama kolom yang ramah pengguna) ----
            $sheet->setCellValue($col . '2', $label);
            $sheet->getStyle($col . '2')->applyFromArray([
                'font'      => ['italic' => true, 'color' => ['rgb' => '374151'], 'size' => 9],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);

            // ---- Row 3: Contoh data ----
            $sheet->setCellValueExplicit(
                $col . '3',
                $example[$key] ?? '',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );
            $sheet->getStyle($col . '3')->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2FF']],
                'font'      => ['color' => ['rgb' => '6B7280'], 'italic' => true],
            ]);

            // Auto width
            $sheet->getColumnDimension($col)->setAutoSize(true);

            $col++;
        }

        // Freeze sampai row 3 (data mulai dari row 4)
        $sheet->freezePane('A4');

        // Sheet petunjuk
        $guide = $spreadsheet->createSheet();
        $guide->setTitle('Petunjuk');
        $guide->setCellValue('A1', 'PETUNJUK PENGISIAN TEMPLATE IMPORT KOLEKSI');
        $guide->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E3A5F']],
        ]);

        $guideRows = [
            ['', ''],
            ['ATURAN UMUM', ''],
            ['Baris 1', 'KEY kolom — jangan diubah (dipakai sistem saat import)'],
            ['Baris 2', 'Nama kolom — hanya panduan, boleh diabaikan'],
            ['Baris 3', 'Contoh data — boleh dihapus atau dimodifikasi'],
            ['Baris 4+', 'Isi data koleksi Anda di sini'],
            ['', ''],
            ['FORMAT KHUSUS', ''],
            ['creators', 'Format: nama:peran:utama(1/0), pisah beberapa creator dengan |'],
            ['', 'Contoh: Budi Santoso:author:1|Siti Rahayu:contributor:0'],
            ['', 'Peran valid: author, editor, contributor, translator, illustrator, compiler, creator'],
            ['subjects', 'Format: term:tipe, pisah beberapa subjek dengan |'],
            ['', 'Contoh: Sejarah Indonesia:topical|Malang:geographic'],
            ['', 'Tipe valid: topical, geographic, personal, corporate, chronological, genre'],
            ['', ''],
            ['NILAI VALID', ''],
        ];

        if ($unitType === 'library') {
            $guideRows[] = ['bibliographic_level', 'monograph, serial, article, thesis, map, manuscript'];
            $guideRows[] = ['publication_status', 'draft, published, restricted, archived'];
            $guideRows[] = ['visibility', 'public, member, internal, restricted'];
        } else {
            $guideRows[] = ['collection_type', 'artifact, historical_photo, archive_document, multimedia'];
            $guideRows[] = ['condition_current', 'excellent, good, fair, poor, critical'];
            $guideRows[] = ['publication_status', 'draft, published, restricted, archived'];
            $guideRows[] = ['visibility', 'public, member, internal, restricted'];
        }

        $r = 2;
        foreach ($guideRows as [$a, $b]) {
            $guide->setCellValue('A' . $r, $a);
            $guide->setCellValue('B' . $r, $b);
            if ($a && ! str_starts_with($a, ' ') && $b === '') {
                $guide->getStyle('A' . $r)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                ]);
            }
            $r++;
        }

        $guide->getColumnDimension('A')->setWidth(25);
        $guide->getColumnDimension('B')->setWidth(70);

        // Simpan ke temp file
        $tmpPath = sys_get_temp_dir() . '/simpb_template_' . $unitType . '_' . time() . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tmpPath);

        return $tmpPath;
    }

    // ------------------------------------------------------------------ //
    // Parse file (CSV atau XLSX)
    // ------------------------------------------------------------------ //

    public function parseFile(UploadedFile $file, string $unitType): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension === 'csv') {
            return $this->parseCsv($file->getRealPath(), $unitType);
        }

        return $this->parseXlsx($file->getRealPath(), $unitType);
    }

    private function parseCsv(string $path, string $unitType): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['headers' => [], 'rows' => [], 'parse_error' => 'File tidak dapat dibuka.'];
        }

        // Deteksi BOM UTF-8
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Deteksi delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        // Re-skip BOM jika ada
        $bom2 = fread($handle, 3);
        if ($bom2 !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        $rawHeaders = null;
        $lineNo     = 0;
        $rawRows    = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNo++;

            if ($rawHeaders === null) {
                $rawHeaders = array_map('trim', $data);
                continue;
            }

            // Skip baris label (baris ke-2 di template = label deskriptif)
            // Deteksi: jika baris ini adalah label row (tidak ada data numerik/kode)
            // Kita skip baris kedua hanya jika header row pertama adalah KEY (snake_case)

            $row = [];
            foreach ($rawHeaders as $i => $header) {
                $row[$header] = isset($data[$i]) ? trim($data[$i]) : '';
            }

            $rawRows[] = $row;
        }

        fclose($handle);

        // Normalisasi headers dan rows
        $headerMap = $this->buildHeaderMap($unitType);
        $normalizedHeaders = $this->normalizeHeaders($rawHeaders ?? [], $headerMap);
        $rows = $this->normalizeRows($rawRows, $rawHeaders ?? [], $normalizedHeaders, $headerMap);

        // Jika baris pertama adalah label row dari template (row 2), skip
        if (!empty($rows)) {
            $rows = $this->skipLabelRow($rows, $unitType);
        }

        return [
            'headers'       => $normalizedHeaders,
            'rows'          => $rows,
            'expected_cols' => array_keys($headerMap),
            'parse_error'   => null,
        ];
    }

    private function parseXlsx(string $path, string $unitType): array
    {
        try {
            $spreadsheet = IOFactory::load($path);
            // Selalu baca sheet pertama (index 0 = sheet 'Data')
            $sheet       = $spreadsheet->getSheet(0);
            $data        = $sheet->toArray(null, true, true, false);
        } catch (\Throwable $e) {
            return ['headers' => [], 'rows' => [], 'parse_error' => 'File Excel tidak valid: ' . $e->getMessage()];
        }

        if (empty($data)) {
            return ['headers' => [], 'rows' => [], 'parse_error' => 'File Excel kosong.'];
        }

        // Row 1 = keys (identifer mesin)
        $rawHeaders = array_map(fn($h) => trim((string)($h ?? '')), $data[0]);

        $headerMap = $this->buildHeaderMap($unitType);
        $normalizedHeaders = $this->normalizeHeaders($rawHeaders, $headerMap);

        $rawRows = [];
        $startRow = 1; // default: data mulai row 2 (index 1)

        // Jika row 2 adalah label row dari template baru (berisi label deskriptif),
        // skip row 2 dan mulai dari row 3 (index 2)
        if (count($data) > 1) {
            $row2 = array_map(fn($c) => trim((string)($c ?? '')), $data[1]);
            if ($this->isLabelRow($row2, $normalizedHeaders, $unitType)) {
                $startRow = 2; // skip label row
            }
        }

        for ($i = $startRow; $i < count($data); $i++) {
            $rowData = $data[$i];

            // Skip baris kosong
            $isEmpty = true;
            foreach ($rowData as $cell) {
                if ($cell !== null && trim((string) $cell) !== '') {
                    $isEmpty = false;
                    break;
                }
            }
            if ($isEmpty) continue;

            $row = [];
            foreach ($normalizedHeaders as $j => $key) {
                $raw = $rowData[$j] ?? null;
                $row[$key] = $raw !== null ? trim((string) $raw) : '';
            }

            $rawRows[] = $row;
        }

        return [
            'headers'       => $normalizedHeaders,
            'rows'          => $rawRows,
            'expected_cols' => array_keys($headerMap),
            'parse_error'   => null,
        ];
    }

    /**
     * Bangun map: key => label dan label => key untuk normalisasi header.
     */
    private function buildHeaderMap(string $unitType): array
    {
        return $unitType === 'library' ? self::LIBRARY_COLUMNS : self::MUSEUM_COLUMNS;
    }

    /**
     * Normalisasi header dari file (bisa berupa key atau label) → selalu jadi key.
     */
    private function normalizeHeaders(array $rawHeaders, array $headerMap): array
    {
        // Buat reverse map: label => key
        $labelToKey = array_flip($headerMap); // label => key

        return array_map(function (string $h) use ($headerMap, $labelToKey): string {
            $h = trim($h);
            // Jika sudah berupa key yang valid, kembalikan langsung
            if (array_key_exists($h, $headerMap)) {
                return $h;
            }
            // Coba cocokkan dengan label (case-insensitive, strip asterisk)
            $hClean = rtrim(strtolower($h), ' *');
            foreach ($labelToKey as $label => $key) {
                if (strtolower(rtrim((string)$label, ' *')) === $hClean) {
                    return $key;
                }
            }
            // Kembalikan apa adanya (akan diabaikan saat mapping)
            return $h;
        }, $rawHeaders);
    }

    /**
     * Normalisasi rows agar menggunakan key (bukan label) sebagai array key.
     */
    private function normalizeRows(array $rawRows, array $rawHeaders, array $normalizedHeaders, array $headerMap): array
    {
        $rows = [];
        foreach ($rawRows as $rawRow) {
            $row = [];
            foreach ($rawHeaders as $j => $origHeader) {
                $key = $normalizedHeaders[$j] ?? $origHeader;
                $row[$key] = $rawRow[$origHeader] ?? '';
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Deteksi apakah sebuah row adalah label row dari template
     * (berisi label deskriptif, bukan data aktual).
     */
    private function isLabelRow(array $row, array $normalizedHeaders, string $unitType): bool
    {
        $columns = $unitType === 'library' ? self::LIBRARY_COLUMNS : self::MUSEUM_COLUMNS;
        $labels  = array_values($columns);

        // Hitung berapa cell yang isinya sama persis dengan salah satu label
        $labelMatches = 0;
        foreach ($row as $cell) {
            $cellClean = rtrim(trim((string)$cell), ' *');
            foreach ($labels as $label) {
                if ($cellClean !== '' && strtolower($cellClean) === strtolower(rtrim((string)$label, ' *'))) {
                    $labelMatches++;
                    break;
                }
            }
        }

        // Jika lebih dari setengah cell cocok dengan label → ini label row
        $nonEmpty = count(array_filter($row, fn($c) => trim((string)$c) !== ''));
        return $nonEmpty > 0 && ($labelMatches / max($nonEmpty, 1)) > 0.5;
    }

    /**
     * Untuk CSV: skip label row jika ada di awal data.
     */
    private function skipLabelRow(array $rows, string $unitType): array
    {
        if (empty($rows)) return $rows;

        $firstRow = array_values($rows[0]);
        if ($this->isLabelRow($firstRow, [], $unitType)) {
            array_shift($rows);
        }

        return $rows;
    }

    // ------------------------------------------------------------------ //
    // Validasi setiap baris
    // ------------------------------------------------------------------ //

    public function validateRows(array $rows, string $unitType): array
    {
        $results = [];

        foreach ($rows as $index => $row) {
            $rowNo   = $index + 1;
            $payload = $this->rowToPayload($row, $unitType);
            $errors  = $this->validateSingleRow($payload, $unitType, $rowNo);

            $results[] = [
                'row'     => $rowNo,
                'data'    => $row,
                'payload' => $payload,
                'valid'   => empty($errors),
                'errors'  => $errors,
            ];
        }

        return $results;
    }

    private function validateSingleRow(array $payload, string $unitType, int $rowNo): array
    {
        $errors = [];

        // Validasi dasar koleksi
        $collectionRules = [
            'collection.record_code'        => ['required', 'string', 'max:80', 'unique:collections,record_code'],
            'collection.collection_type'    => ['required', 'string', 'max:80'],
            'collection.title'              => ['required', 'string', 'max:500'],
            'collection.subtitle'           => ['nullable', 'string', 'max:500'],
            'collection.language_code'      => ['nullable', 'string', 'max:10'],
            'collection.publication_status' => ['nullable', 'in:draft,published,restricted,archived'],
            'collection.visibility'         => ['nullable', 'in:public,member,internal,restricted'],
            'collection.year_start'         => ['nullable', 'integer', 'min:1'],
            'collection.year_end'           => ['nullable', 'integer', 'min:1'],
            'creators'                      => ['required', 'array', 'min:1'],
            'creators.*.name'               => ['required', 'string', 'max:255'],
        ];

        if ($unitType === 'library') {
            $specificRules = [
                'library_item.bibliographic_level' => ['required', 'in:monograph,serial,article,thesis,map,manuscript'],
                'library_item.isbn13'              => ['nullable', 'string', 'max:20', 'unique:library_items,isbn13'],
                'library_item.publication_year'    => ['nullable', 'integer', 'min:1000', 'max:9999'],
            ];
        } else {
            $specificRules = [
                'museum_item.inventory_number'  => ['required', 'string', 'max:100', 'unique:museum_items,inventory_number'],
                'museum_item.object_name'       => ['required', 'string', 'max:255'],
                'museum_item.object_type_label' => ['required', 'string', 'max:160'],
                'museum_item.classification'    => ['required', 'string', 'max:120'],
                'museum_item.condition_current' => ['nullable', 'in:excellent,good,fair,poor,critical'],
                'collection.collection_type'    => ['required', 'in:artifact,historical_photo,archive_document,multimedia'],
            ];
        }

        $rules     = array_merge($collectionRules, $specificRules);
        $validator = Validator::make($payload, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->toArray() as $field => $messages) {
                $errors[] = implode(', ', $messages);
            }
        }

        return $errors;
    }

    // ------------------------------------------------------------------ //
    // Eksekusi import
    // ------------------------------------------------------------------ //

    public function executeImport(array $validatedRows, string $unitType, User $actor): array
    {
        $results = [];

        foreach ($validatedRows as $rowResult) {
            if (! $rowResult['valid']) {
                $results[] = [
                    'row'    => $rowResult['row'],
                    'status' => 'skipped',
                    'reason' => 'Baris tidak valid, dilewati.',
                ];
                continue;
            }

            try {
                $payload = $rowResult['payload'];

                if ($unitType === 'library') {
                    $collection = $this->libraryService->create(
                        $payload,
                        $actor,
                        'Import massal via spreadsheet'
                    );
                } else {
                    $collection = $this->museumService->create(
                        $payload,
                        $actor,
                        'Import massal via spreadsheet'
                    );
                }

                $results[] = [
                    'row'         => $rowResult['row'],
                    'status'      => 'success',
                    'record_code' => $collection->record_code,
                    'title'       => $collection->title,
                    'identifier'  => $collection->ulid,
                ];
            } catch (ValidationException $e) {
                $errors = collect($e->errors())->flatten()->implode(', ');
                $results[] = [
                    'row'    => $rowResult['row'],
                    'status' => 'error',
                    'reason' => 'Validasi gagal: ' . $errors,
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'row'    => $rowResult['row'],
                    'status' => 'error',
                    'reason' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    // ------------------------------------------------------------------ //
    // Mapping: baris CSV/XLSX → payload service
    // ------------------------------------------------------------------ //

    private function rowToPayload(array $row, string $unitType): array
    {
        $v = fn(string $key) => isset($row[$key]) && $row[$key] !== '' ? $row[$key] : null;

        $collection = array_filter([
            'record_code'        => $v('record_code'),
            'collection_type'    => $v('collection_type'),
            'title'              => $v('title'),
            'subtitle'           => $v('subtitle'),
            'description'        => $v('description'),
            'language_code'      => $v('language_code'),
            'rights_status'      => $v('rights_status'),
            'date_display'       => $v('date_display'),
            'year_start'         => $v('year_start') !== null ? (int) $v('year_start') : null,
            'year_end'           => $v('year_end') !== null ? (int) $v('year_end') : null,
            'category_id'        => $v('category_id') !== null ? (int) $v('category_id') : null,
            'publication_status' => $v('publication_status') ?? 'draft',
            'visibility'         => $v('visibility') ?? 'public',
        ], fn($val) => $val !== null);

        $creators  = $this->parseMultiValue($row['creators'] ?? '', 3, ['name', 'role', 'is_primary']);
        $subjects  = $this->parseMultiValue($row['subjects'] ?? '', 2, ['term', 'type']);

        // Cast is_primary ke boolean
        foreach ($creators as &$creator) {
            $creator['is_primary'] = isset($creator['is_primary']) && $creator['is_primary'] === '1';
            $creator['role']       = $creator['role'] ?? 'author';
        }
        unset($creator);

        // Default subject type
        foreach ($subjects as &$subject) {
            $subject['type'] = $subject['type'] ?? 'topical';
        }
        unset($subject);

        $payload = [
            'collection' => $collection,
            'creators'   => $creators,
            'subjects'   => $subjects,
        ];

        if ($unitType === 'library') {
            $payload['library_item'] = array_filter([
                'bibliographic_level' => $v('bibliographic_level'),
                'isbn13'              => $v('isbn13'),
                'isbn10'              => $v('isbn10'),
                'issn'                => $v('issn'),
                'doi'                 => $v('doi'),
                'publisher_name'      => $v('publisher_name'),
                'publisher_place'     => $v('publisher_place'),
                'edition'             => $v('edition'),
                'publication_year'    => $v('publication_year') !== null ? (int) $v('publication_year') : null,
                'ddc_classification'  => $v('ddc_classification'),
                'call_number'         => $v('call_number'),
                'physical_extent'     => $v('physical_extent'),
                'pages'               => $v('pages') !== null ? (int) $v('pages') : null,
                'series_title'        => $v('series_title'),
            ], fn($val) => $val !== null);
        } else {
            $payload['museum_item'] = array_filter([
                'inventory_number'  => $v('inventory_number'),
                'object_name'       => $v('object_name'),
                'object_type_label' => $v('object_type_label'),
                'classification'    => $v('classification'),
                'maker_name'        => $v('maker_name'),
                'culture'           => $v('culture'),
                'period_display'    => $v('period_display'),
                'material_summary'  => $v('material_summary'),
                'technique_summary' => $v('technique_summary'),
                'condition_current' => $v('condition_current'),
                'provenance_history' => $v('provenance_history'),
                'acquisition_method' => $v('acquisition_method'),
            ], fn($val) => $val !== null);
        }

        return $payload;
    }

    /**
     * Parse nilai multi dengan separator | dan mapping ke keys.
     * Format: val1:val2:val3|val1:val2:val3
     */
    private function parseMultiValue(string $raw, int $parts, array $keys): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $items  = explode('|', $raw);
        $result = [];

        foreach ($items as $item) {
            $item = trim($item);
            if ($item === '') continue;

            $segments = explode(':', $item, $parts);
            $entry    = [];

            foreach ($keys as $i => $key) {
                $entry[$key] = isset($segments[$i]) ? trim($segments[$i]) : '';
            }

            $result[] = $entry;
        }

        return $result;
    }
}
