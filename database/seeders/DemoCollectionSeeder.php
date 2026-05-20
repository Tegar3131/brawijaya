<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Collection;
use App\Models\ConditionReport;
use App\Models\Creator;
use App\Models\DigitalAsset;
use App\Models\ItemMetadata;
use App\Models\LibraryCopy;
use App\Models\LibraryItem;
use App\Models\Material;
use App\Models\MetadataElement;
use App\Models\MuseumItem;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoCollectionSeeder extends Seeder
{
    public function run(): void
    {
        $pustakawan = User::where('email', 'pustakawan@simpb.test')->firstOrFail();
        $kurator = User::where('email', 'kurator@simpb.test')->firstOrFail();

        $libraryLocation = LocationResolver::byCode('LIB-RK-A1');
        $etdLocation = LocationResolver::byCode('LIB-RK-ETD');
        $museumLocation = LocationResolver::byCode('MUS-GD1-RG1-LM1');

        $this->seedLibraryCollections($pustakawan, $libraryLocation, $etdLocation);
        $this->seedMuseumCollections($kurator, $museumLocation);
    }

    private function seedLibraryCollections(User $pustakawan, $libraryLocation, $etdLocation): void
    {
        $bookCategory = Category::where('slug', 'buku')->firstOrFail();
        $journalCategory = Category::where('slug', 'jurnal')->firstOrFail();
        $thesisCategory = Category::where('slug', 'skripsi-tesis-disertasi')->firstOrFail();

        $militarySubject = $this->subject('Sejarah Militer Indonesia', 'LCSH', 'topic');
        $brawijayaSubject = $this->subject('Brawijaya', 'LOCAL', 'organization');
        $digitalLibrarySubject = $this->subject('Perpustakaan Digital', 'LOCAL', 'topic');
        $metadataSubject = $this->subject('Metadata', 'LOCAL', 'topic');

        $nugroho = $this->creator('Nugroho Notosusanto');
        $budi = $this->creator('Budi Santoso');
        $ahmad = $this->creator('Ahmad Fauzi Rahmatullah');

        $book = $this->upsertCollection([
            'record_code' => 'LIB-BK-2024-0001',
            'unit_type' => 'library',
            'collection_type' => 'book',
            'title' => 'Sejarah Perjuangan Brawijaya',
            'subtitle' => null,
            'description' => 'Buku tentang sejarah perjuangan dan peran Brawijaya dalam sejarah militer Indonesia.',
            'language_code' => 'ind',
            'rights_status' => 'In Copyright',
            'date_display' => '2023',
            'year_start' => 2023,
            'year_end' => 2023,
            'category_id' => $bookCategory->id,
            'current_location_id' => $libraryLocation->id,
            'publication_status' => 'published',
            'visibility' => 'public',
            'is_featured' => true,
            'featured_order' => 1,
            'created_by' => $pustakawan->id,
            'updated_by' => $pustakawan->id,
        ]);

        LibraryItem::updateOrCreate(
            ['collection_id' => $book->id],
            [
                'bibliographic_level' => 'monograph',
                'isbn13' => '978-602-000-000-1',
                'isbn10' => null,
                'issn' => null,
                'doi' => null,
                'publisher_name' => 'Universitas Brawijaya Press',
                'publisher_place' => 'Malang',
                'edition' => 'Edisi Pertama',
                'publication_year' => 2023,
                'publication_date' => '2023-08-17',
                'ddc_classification' => '959.8',
                'call_number' => '959.8 NUG s',
                'marc_leader' => '00000nam a2200000 a 4500',
                'marc_control_number' => 'SIMPB-LIB-BK-2024-0001',
                'marc_raw_json' => [
                    '020' => ['a' => '978-602-000-000-1'],
                    '100' => ['a' => 'Nugroho Notosusanto'],
                    '245' => ['a' => 'Sejarah Perjuangan Brawijaya'],
                    '260' => [
                        'a' => 'Malang',
                        'b' => 'Universitas Brawijaya Press',
                        'c' => '2023',
                    ],
                    '082' => ['a' => '959.8'],
                    '650' => [
                        ['a' => 'Sejarah Militer Indonesia'],
                        ['a' => 'Brawijaya'],
                    ],
                ],
                'mods_xml' => null,
                'physical_extent' => 'xii, 250 halaman',
                'physical_dimensions' => '24 cm',
                'pages' => 250,
                'illustrations' => 'Ilustrasi, peta',
                'series_title' => 'Seri Sejarah Brawijaya',
                'source_acquisition' => 'Pembelian',
                'acquired_at' => '2024-03-15',
            ]
        );

        $this->upsertLibraryCopy($book, 'C1', 'LIB-BK-2024-0001-C1', '959.8 NUG s', $libraryLocation->id);
        $this->upsertLibraryCopy($book, 'C2', 'LIB-BK-2024-0001-C2', '959.8 NUG s', $libraryLocation->id);

        $this->attachCreator($book, $nugroho, 'author', true);
        $this->attachSubject($book, $militarySubject, 'primary');
        $this->attachSubject($book, $brawijayaSubject, 'local');

        $this->libraryMetadata($book, $nugroho->name, ['Sejarah Militer Indonesia', 'Brawijaya'], [
            'description' => $book->description,
            'date' => '2023',
            'type' => 'book',
            'identifier' => '978-602-000-000-1',
            'language' => 'ind',
            'rights' => 'In Copyright',
            'marc245a' => 'Sejarah Perjuangan Brawijaya',
            'marc260b' => 'Universitas Brawijaya Press',
            'marc082a' => '959.8',
        ], $pustakawan);

        $this->upsertAsset($book, 'library/covers/lib-bk-2024-0001.jpg', [
            'uploaded_by' => $pustakawan->id,
            'asset_type' => 'cover',
            'file_role' => 'access',
            'disk' => 'public',
            'thumbnail_path' => 'library/thumbs/lib-bk-2024-0001.jpg',
            'filename' => 'lib-bk-2024-0001.jpg',
            'original_filename' => 'lib-bk-2024-0001.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 245000,
            'width_px' => 800,
            'height_px' => 1200,
            'caption' => 'Cover buku Sejarah Perjuangan Brawijaya.',
            'is_primary' => true,
            'is_public' => true,
            'access_level' => 'public',
            'sort_order' => 1,
        ]);

        $journal = $this->upsertCollection([
            'record_code' => 'LIB-JRN-2024-0001',
            'unit_type' => 'library',
            'collection_type' => 'journal',
            'title' => 'Digitalisasi Koleksi Langka di Perpustakaan Akademik',
            'subtitle' => null,
            'description' => 'Artikel jurnal mengenai strategi digitalisasi koleksi langka di lingkungan perpustakaan akademik.',
            'language_code' => 'ind',
            'rights_status' => 'In Copyright',
            'date_display' => '2024',
            'year_start' => 2024,
            'year_end' => 2024,
            'category_id' => $journalCategory->id,
            'current_location_id' => $libraryLocation->id,
            'publication_status' => 'published',
            'visibility' => 'public',
            'is_featured' => false,
            'featured_order' => null,
            'created_by' => $pustakawan->id,
            'updated_by' => $pustakawan->id,
        ]);

        LibraryItem::updateOrCreate(
            ['collection_id' => $journal->id],
            [
                'bibliographic_level' => 'article',
                'isbn13' => null,
                'isbn10' => null,
                'issn' => '2302-8491',
                'doi' => '10.1234/simpb.jip.v15i2.001',
                'publisher_name' => 'Jurnal Ilmu Perpustakaan',
                'publisher_place' => 'Malang',
                'edition' => null,
                'publication_year' => 2024,
                'publication_date' => '2024-06-01',
                'ddc_classification' => '025.84',
                'call_number' => '025.84 BUD d',
                'marc_leader' => '00000naa a2200000 a 4500',
                'marc_control_number' => 'SIMPB-LIB-JRN-2024-0001',
                'marc_raw_json' => [
                    '022' => ['a' => '2302-8491'],
                    '100' => ['a' => 'Budi Santoso'],
                    '245' => ['a' => 'Digitalisasi Koleksi Langka di Perpustakaan Akademik'],
                    '260' => [
                        'a' => 'Malang',
                        'b' => 'Jurnal Ilmu Perpustakaan',
                        'c' => '2024',
                    ],
                    '082' => ['a' => '025.84'],
                    '650' => [
                        ['a' => 'Perpustakaan Digital'],
                        ['a' => 'Metadata'],
                    ],
                ],
                'mods_xml' => null,
                'physical_extent' => '125-148',
                'physical_dimensions' => '29 cm',
                'pages' => 24,
                'illustrations' => 'Tabel, grafik',
                'series_title' => 'Jurnal Ilmu Perpustakaan Vol. 15 No. 2',
                'source_acquisition' => 'Langganan',
                'acquired_at' => '2024-06-01',
            ]
        );

        $this->upsertLibraryCopy($journal, 'C1', 'LIB-JRN-2024-0001-C1', '025.84 BUD d', $libraryLocation->id);

        $this->attachCreator($journal, $budi, 'author', true);
        $this->attachSubject($journal, $digitalLibrarySubject, 'primary');
        $this->attachSubject($journal, $metadataSubject, 'secondary');

        $this->libraryMetadata($journal, $budi->name, ['Perpustakaan Digital', 'Metadata'], [
            'description' => $journal->description,
            'date' => '2024',
            'type' => 'journal',
            'identifier' => '10.1234/simpb.jip.v15i2.001',
            'language' => 'ind',
            'rights' => 'In Copyright',
            'marc245a' => 'Digitalisasi Koleksi Langka di Perpustakaan Akademik',
            'marc260b' => 'Jurnal Ilmu Perpustakaan',
            'marc082a' => '025.84',
        ], $pustakawan);

        $this->upsertAsset($journal, 'library/articles/lib-jrn-2024-0001.pdf', [
            'uploaded_by' => $pustakawan->id,
            'asset_type' => 'pdf',
            'file_role' => 'access',
            'disk' => 'public',
            'thumbnail_path' => 'library/thumbs/lib-jrn-2024-0001.jpg',
            'filename' => 'lib-jrn-2024-0001.pdf',
            'original_filename' => 'Digitalisasi Koleksi Langka.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1180000,
            'caption' => 'File PDF artikel jurnal.',
            'is_primary' => true,
            'is_public' => false,
            'access_level' => 'member',
            'sort_order' => 1,
        ]);

        $thesis = $this->upsertCollection([
            'record_code' => 'LIB-ETD-2024-0001',
            'unit_type' => 'library',
            'collection_type' => 'thesis',
            'title' => 'Analisis Metadata Koleksi Museum Militer Berbasis Dublin Core dan ISAD(G)',
            'subtitle' => null,
            'description' => 'Skripsi tentang pemodelan metadata museum militer dengan pendekatan Dublin Core dan ISAD(G).',
            'language_code' => 'ind',
            'rights_status' => 'Hak Institusi',
            'date_display' => '2024',
            'year_start' => 2024,
            'year_end' => 2024,
            'category_id' => $thesisCategory->id,
            'current_location_id' => $etdLocation->id,
            'publication_status' => 'published',
            'visibility' => 'member',
            'is_featured' => false,
            'featured_order' => null,
            'created_by' => $pustakawan->id,
            'updated_by' => $pustakawan->id,
        ]);

        LibraryItem::updateOrCreate(
            ['collection_id' => $thesis->id],
            [
                'bibliographic_level' => 'thesis',
                'isbn13' => null,
                'isbn10' => null,
                'issn' => null,
                'doi' => null,
                'publisher_name' => 'Universitas Brawijaya',
                'publisher_place' => 'Malang',
                'edition' => null,
                'publication_year' => 2024,
                'publication_date' => '2024-08-01',
                'ddc_classification' => '069.0285',
                'call_number' => '069.0285 AHM a',
                'marc_leader' => '00000ntm a2200000 a 4500',
                'marc_control_number' => 'SIMPB-LIB-ETD-2024-0001',
                'marc_raw_json' => [
                    '100' => ['a' => 'Ahmad Fauzi Rahmatullah'],
                    '245' => ['a' => 'Analisis Metadata Koleksi Museum Militer Berbasis Dublin Core dan ISAD(G)'],
                    '260' => [
                        'a' => 'Malang',
                        'b' => 'Universitas Brawijaya',
                        'c' => '2024',
                    ],
                    '502' => ['a' => 'Skripsi, Universitas Brawijaya, 2024'],
                    '082' => ['a' => '069.0285'],
                ],
                'mods_xml' => null,
                'physical_extent' => 'xv, 180 halaman',
                'physical_dimensions' => '30 cm',
                'pages' => 180,
                'illustrations' => 'Tabel, diagram',
                'series_title' => null,
                'source_acquisition' => 'Deposit Institusi',
                'acquired_at' => '2024-08-01',
            ]
        );

        $this->upsertLibraryCopy($thesis, 'C1', 'LIB-ETD-2024-0001-C1', '069.0285 AHM a', $etdLocation->id);

        $this->attachCreator($thesis, $ahmad, 'author', true);
        $this->attachSubject($thesis, $metadataSubject, 'primary');
        $this->attachSubject($thesis, $digitalLibrarySubject, 'secondary');

        $this->libraryMetadata($thesis, $ahmad->name, ['Metadata', 'Perpustakaan Digital'], [
            'description' => $thesis->description,
            'date' => '2024',
            'type' => 'thesis',
            'identifier' => 'SIMPB-LIB-ETD-2024-0001',
            'language' => 'ind',
            'rights' => 'Hak Institusi',
            'marc245a' => 'Analisis Metadata Koleksi Museum Militer Berbasis Dublin Core dan ISAD(G)',
            'marc260b' => 'Universitas Brawijaya',
            'marc082a' => '069.0285',
        ], $pustakawan);

        $this->upsertAsset($thesis, 'library/etd/lib-etd-2024-0001.pdf', [
            'uploaded_by' => $pustakawan->id,
            'asset_type' => 'pdf',
            'file_role' => 'access',
            'disk' => 'public',
            'thumbnail_path' => 'library/thumbs/lib-etd-2024-0001.jpg',
            'filename' => 'lib-etd-2024-0001.pdf',
            'original_filename' => 'Analisis Metadata Koleksi Museum Militer.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 3200000,
            'caption' => 'File PDF karya ilmiah.',
            'is_primary' => true,
            'is_public' => false,
            'access_level' => 'member',
            'sort_order' => 1,
        ]);
    }

    private function seedMuseumCollections(User $kurator, $museumLocation): void
    {
        $artifactCategory = Category::where('slug', 'senjata')->firstOrFail();
        $photoCategory = Category::where('slug', 'foto-bersejarah')->firstOrFail();
        $archiveCategory = Category::where('slug', 'dokumen-arsip')->firstOrFail();

        $weaponSubject = $this->subject('Senjata Api', 'AAT', 'topic');
        $photoSubject = $this->subject('Foto Bersejarah', 'LOCAL', 'topic');
        $documentSubject = $this->subject('Dokumen Militer', 'LOCAL', 'topic');
        $brawijayaSubject = $this->subject('Brawijaya', 'LOCAL', 'organization');
        $trikoraSubject = $this->subject('Operasi Trikora', 'LOCAL', 'event');

        $bsa = $this->creator('Birmingham Small Arms Company');
        $unknownPhotographer = $this->creator('Fotografer Militer Tidak Diketahui');
        $kodam = $this->creator('Kodam V/Brawijaya');

        $iron = Material::where('name', 'Besi')->where('type', 'material')->firstOrFail();
        $steel = Material::where('name', 'Baja')->where('type', 'material')->firstOrFail();
        $wood = Material::where('name', 'Kayu')->where('type', 'material')->firstOrFail();
        $paper = Material::where('name', 'Kertas')->where('type', 'material')->firstOrFail();
        $ink = Material::where('name', 'Tinta')->where('type', 'material')->firstOrFail();
        $photoTechnique = Material::where('name', 'Fotografi gelatin silver')->where('type', 'technique')->firstOrFail();
        $printTechnique = Material::where('name', 'Cetak')->where('type', 'technique')->firstOrFail();

        $rifle = $this->upsertCollection([
            'record_code' => 'MUS-ART-SJT-0001',
            'unit_type' => 'museum',
            'collection_type' => 'artifact',
            'title' => 'Senapan Lee-Enfield No.4 Mk.I',
            'subtitle' => null,
            'description' => 'Senapan bolt-action yang dikaitkan dengan periode konflik pasca-kemerdekaan di Jawa Timur.',
            'language_code' => 'ind',
            'rights_status' => 'Hak Institusi',
            'date_display' => 'ca. 1942-1945',
            'year_start' => 1942,
            'year_end' => 1945,
            'category_id' => $artifactCategory->id,
            'current_location_id' => $museumLocation->id,
            'publication_status' => 'published',
            'visibility' => 'public',
            'is_featured' => true,
            'featured_order' => 2,
            'created_by' => $kurator->id,
            'updated_by' => $kurator->id,
        ]);

        $rifleItem = MuseumItem::updateOrCreate(
            ['collection_id' => $rifle->id],
            [
                'inventory_number' => 'MUS-ART-SJT-0001',
                'object_name' => 'Senapan Lee-Enfield No.4 Mk.I',
                'object_type_label' => 'senjata api',
                'object_type_uri' => 'local://simpb/object-type/senjata-api',
                'classification' => 'Senjata',
                'maker_name' => 'Birmingham Small Arms Company',
                'maker_uri' => 'local://simpb/creator/birmingham-small-arms-company',
                'culture' => 'Inggris / British',
                'period_display' => 'ca. 1942-1945',
                'made_year_start' => 1942,
                'made_year_end' => 1945,
                'material_summary' => 'besi, baja, kayu',
                'technique_summary' => 'perakitan mekanis',
                'height_cm' => 10.50,
                'width_cm' => 7.20,
                'length_depth_cm' => 113.00,
                'weight_gram' => 4150.00,
                'condition_current' => 'fair',
                'condition_checked_at' => '2024-02-01',
                'condition_notes' => 'Terdapat karat ringan pada laras bagian depan.',
                'provenance_history' => 'Diduga digunakan dalam periode konflik pasca-kemerdekaan di Jawa Timur.',
                'acquisition_method' => 'Hibah',
                'acquisition_source' => 'Koleksi veteran',
                'acquisition_date' => '2024-01-15',
                'is_sensitive' => false,
            ]
        );

        $rifleItem->materials()->syncWithoutDetaching([
            $iron->id => ['is_primary' => true],
            $steel->id => ['is_primary' => false],
            $wood->id => ['is_primary' => false],
        ]);

        $this->attachCreator($rifle, $bsa, 'maker', true);
        $this->attachSubject($rifle, $weaponSubject, 'primary');
        $this->attachSubject($rifle, $brawijayaSubject, 'local');

        $this->museumMetadata($rifle, $bsa->name, ['Senjata Api', 'Brawijaya'], [
            'description' => $rifle->description,
            'date' => 'ca. 1942-1945',
            'type' => 'artifact',
            'identifier' => 'MUS-ART-SJT-0001',
            'rights' => 'Hak Institusi',
            'objectType' => 'senjata api',
            'measurements' => '113 x 10.5 x 7.2 cm; 4150 gram',
            'material' => 'besi, baja, kayu',
            'technique' => 'perakitan mekanis',
            'provenance' => 'Diduga digunakan dalam periode konflik pasca-kemerdekaan di Jawa Timur.',
            'condition' => 'fair; karat ringan pada bagian logam',
        ], $kurator);

        $rifleAsset = $this->upsertAsset($rifle, 'museum/photos/MUS-ART-SJT-0001_front.jpg', [
            'uploaded_by' => $kurator->id,
            'asset_type' => 'photo',
            'file_role' => 'original',
            'disk' => 'public',
            'thumbnail_path' => 'museum/thumbs/MUS-ART-SJT-0001_front.jpg',
            'watermarked_path' => 'museum/watermarked/MUS-ART-SJT-0001_front.jpg',
            'filename' => 'MUS-ART-SJT-0001_front.jpg',
            'original_filename' => 'MUS-ART-SJT-0001_front.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 850000,
            'width_px' => 1920,
            'height_px' => 1280,
            'technical_metadata' => [
                'vra_core' => [
                    'work_type' => 'senjata api',
                    'image_relation' => 'documentary photograph',
                ],
                'mix' => [
                    'imageWidth' => 1920,
                    'imageLength' => 1280,
                ],
            ],
            'captured_at' => '2024-02-01',
            'photographer_name' => 'Tim Dokumentasi SIMPB',
            'view_angle' => 'front',
            'caption' => 'Foto tampak depan Senapan Lee-Enfield No.4 Mk.I.',
            'is_primary' => true,
            'is_public' => true,
            'access_level' => 'public',
            'watermark_applied' => true,
            'sort_order' => 1,
        ]);

        $this->upsertConditionReport(
            $rifleItem,
            'fair',
            $kurator,
            '2024-02-01 09:00:00',
            'Karat ringan pada bagian logam, kayu stabil.',
            'Lakukan stabilisasi karat dan inspeksi berkala.',
            'maintenance',
            '2024-08-01',
            $rifleAsset->id
        );

        $photo = $this->upsertCollection([
            'record_code' => 'MUS-PHO-1949-0001',
            'unit_type' => 'museum',
            'collection_type' => 'historical_photo',
            'title' => 'Foto Barisan Prajurit Brawijaya Tahun 1949',
            'subtitle' => null,
            'description' => 'Foto historis yang menampilkan barisan prajurit Brawijaya pada masa revolusi.',
            'language_code' => 'ind',
            'rights_status' => 'Hak Institusi',
            'date_display' => '1949',
            'year_start' => 1949,
            'year_end' => 1949,
            'category_id' => $photoCategory->id,
            'current_location_id' => $museumLocation->id,
            'publication_status' => 'published',
            'visibility' => 'public',
            'is_featured' => false,
            'featured_order' => null,
            'created_by' => $kurator->id,
            'updated_by' => $kurator->id,
        ]);

        $photoItem = MuseumItem::updateOrCreate(
            ['collection_id' => $photo->id],
            [
                'inventory_number' => 'MUS-PHO-1949-0001',
                'object_name' => 'Foto Barisan Prajurit Brawijaya Tahun 1949',
                'object_type_label' => 'foto bersejarah',
                'object_type_uri' => 'local://simpb/object-type/foto-bersejarah',
                'classification' => 'Foto Bersejarah',
                'maker_name' => 'Fotografer Militer Tidak Diketahui',
                'maker_uri' => 'local://simpb/creator/fotografer-militer-tidak-diketahui',
                'culture' => 'Indonesia',
                'period_display' => '1949',
                'made_year_start' => 1949,
                'made_year_end' => 1949,
                'material_summary' => 'kertas foto',
                'technique_summary' => 'fotografi gelatin silver',
                'height_cm' => 12.00,
                'width_cm' => 18.00,
                'length_depth_cm' => null,
                'weight_gram' => null,
                'condition_current' => 'good',
                'condition_checked_at' => '2024-02-10',
                'condition_notes' => 'Permukaan foto stabil, sedikit menguning.',
                'provenance_history' => 'Koleksi dokumentasi visual Museum Brawijaya.',
                'acquisition_method' => 'Transfer internal',
                'acquisition_source' => 'Arsip dokumentasi museum',
                'acquisition_date' => '2024-02-10',
                'is_sensitive' => false,
            ]
        );

        $photoItem->materials()->syncWithoutDetaching([
            $paper->id => ['is_primary' => true],
            $photoTechnique->id => ['is_primary' => false],
        ]);

        $this->attachCreator($photo, $unknownPhotographer, 'photographer', true);
        $this->attachSubject($photo, $photoSubject, 'primary');
        $this->attachSubject($photo, $brawijayaSubject, 'local');

        $this->museumMetadata($photo, $unknownPhotographer->name, ['Foto Bersejarah', 'Brawijaya'], [
            'description' => $photo->description,
            'date' => '1949',
            'type' => 'historical_photo',
            'identifier' => 'MUS-PHO-1949-0001',
            'rights' => 'Hak Institusi',
            'objectType' => 'foto bersejarah',
            'measurements' => '18 x 12 cm',
            'material' => 'kertas foto',
            'technique' => 'fotografi gelatin silver',
            'provenance' => 'Koleksi dokumentasi visual Museum Brawijaya.',
            'condition' => 'good; sedikit menguning',
        ], $kurator);

        $photoAsset = $this->upsertAsset($photo, 'museum/photos/MUS-PHO-1949-0001.jpg', [
            'uploaded_by' => $kurator->id,
            'asset_type' => 'photo',
            'file_role' => 'original',
            'disk' => 'public',
            'thumbnail_path' => 'museum/thumbs/MUS-PHO-1949-0001.jpg',
            'watermarked_path' => 'museum/watermarked/MUS-PHO-1949-0001.jpg',
            'filename' => 'MUS-PHO-1949-0001.jpg',
            'original_filename' => 'MUS-PHO-1949-0001.jpg',
            'mime_type' => 'image/jpeg',
            'extension' => 'jpg',
            'size_bytes' => 640000,
            'width_px' => 1600,
            'height_px' => 1100,
            'technical_metadata' => [
                'vra_core' => [
                    'work_type' => 'historical photograph',
                    'view' => 'front',
                ],
                'mix' => [
                    'imageWidth' => 1600,
                    'imageLength' => 1100,
                ],
            ],
            'captured_at' => '2024-02-10',
            'photographer_name' => 'Tim Dokumentasi SIMPB',
            'view_angle' => 'front',
            'caption' => 'Digitalisasi foto barisan prajurit Brawijaya tahun 1949.',
            'is_primary' => true,
            'is_public' => true,
            'access_level' => 'public',
            'watermark_applied' => true,
            'sort_order' => 1,
        ]);

        $this->upsertConditionReport(
            $photoItem,
            'good',
            $kurator,
            '2024-02-10 10:00:00',
            'Permukaan foto stabil, sedikit menguning.',
            'Simpan pada ruang dengan kelembapan stabil dan gunakan pelindung arsip bebas asam.',
            'normal',
            '2024-08-10',
            $photoAsset->id
        );

        $archive = $this->upsertCollection([
            'record_code' => 'MUS-ARC-1962-0001',
            'unit_type' => 'museum',
            'collection_type' => 'archive_document',
            'title' => 'Piagam Penghargaan Operasi Trikora',
            'subtitle' => null,
            'description' => 'Dokumen arsip/piagam penghargaan terkait Operasi Trikora.',
            'language_code' => 'ind',
            'rights_status' => 'Hak Institusi',
            'date_display' => '1962',
            'year_start' => 1962,
            'year_end' => 1962,
            'category_id' => $archiveCategory->id,
            'current_location_id' => $museumLocation->id,
            'publication_status' => 'published',
            'visibility' => 'public',
            'is_featured' => false,
            'featured_order' => null,
            'created_by' => $kurator->id,
            'updated_by' => $kurator->id,
        ]);

        $archiveItem = MuseumItem::updateOrCreate(
            ['collection_id' => $archive->id],
            [
                'inventory_number' => 'MUS-ARC-1962-0001',
                'object_name' => 'Piagam Penghargaan Operasi Trikora',
                'object_type_label' => 'dokumen arsip',
                'object_type_uri' => 'local://simpb/object-type/dokumen-arsip',
                'classification' => 'Dokumen Arsip',
                'maker_name' => 'Kodam V/Brawijaya',
                'maker_uri' => 'local://simpb/creator/kodam-v-brawijaya',
                'culture' => 'Indonesia',
                'period_display' => '1962',
                'made_year_start' => 1962,
                'made_year_end' => 1962,
                'material_summary' => 'kertas, tinta',
                'technique_summary' => 'cetak',
                'height_cm' => 29.70,
                'width_cm' => 21.00,
                'length_depth_cm' => null,
                'weight_gram' => null,
                'condition_current' => 'good',
                'condition_checked_at' => '2024-03-01',
                'condition_notes' => 'Kertas stabil, tinta masih terbaca.',
                'provenance_history' => 'Arsip internal yang dipreservasi sebagai dokumen sejarah militer.',
                'acquisition_method' => 'Transfer internal',
                'acquisition_source' => 'Kodam V/Brawijaya',
                'acquisition_date' => '2024-03-01',
                'is_sensitive' => false,
            ]
        );

        $archiveItem->materials()->syncWithoutDetaching([
            $paper->id => ['is_primary' => true],
            $ink->id => ['is_primary' => false],
            $printTechnique->id => ['is_primary' => false],
        ]);

        $this->attachCreator($archive, $kodam, 'creator', true);
        $this->attachSubject($archive, $documentSubject, 'primary');
        $this->attachSubject($archive, $trikoraSubject, 'event');
        $this->attachSubject($archive, $brawijayaSubject, 'local');

        $this->museumMetadata($archive, $kodam->name, ['Dokumen Militer', 'Operasi Trikora', 'Brawijaya'], [
            'description' => $archive->description,
            'date' => '1962',
            'type' => 'archive_document',
            'identifier' => 'MUS-ARC-1962-0001',
            'rights' => 'Hak Institusi',
            'objectType' => 'dokumen arsip',
            'measurements' => '29.7 x 21 cm',
            'material' => 'kertas, tinta',
            'technique' => 'cetak',
            'provenance' => 'Arsip internal yang dipreservasi sebagai dokumen sejarah militer.',
            'condition' => 'good; tinta masih terbaca',
        ], $kurator);

        $this->metadata($archive, 'isad.reference_code', 'MUS-ARC-1962-0001', 'value_string', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.title', 'Piagam Penghargaan Operasi Trikora', 'value_string', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.date', '1962', 'value_string', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.level_of_description', 'item', 'value_string', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.extent_medium', '1 lembar piagam kertas', 'value_string', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.creator_name', 'Kodam V/Brawijaya', 'value_string', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.scope_content', 'Piagam penghargaan terkait Operasi Trikora.', 'value_text', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.access_conditions', 'Akses publik terbatas pada versi digital.', 'value_text', 0, 'manual', $kurator);
        $this->metadata($archive, 'isad.physical_condition', 'Kertas stabil, tinta masih terbaca.', 'value_text', 0, 'manual', $kurator);

        $archiveAsset = $this->upsertAsset($archive, 'museum/documents/MUS-ARC-1962-0001.pdf', [
            'uploaded_by' => $kurator->id,
            'asset_type' => 'document',
            'file_role' => 'access',
            'disk' => 'public',
            'thumbnail_path' => 'museum/thumbs/MUS-ARC-1962-0001.jpg',
            'filename' => 'MUS-ARC-1962-0001.pdf',
            'original_filename' => 'MUS-ARC-1962-0001.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size_bytes' => 1200000,
            'technical_metadata' => [
                'isad_g' => [
                    'reference_code' => 'MUS-ARC-1962-0001',
                    'level_of_description' => 'item',
                    'scope_and_content' => 'Piagam penghargaan terkait Operasi Trikora.',
                ],
                'premis' => [
                    'event_type' => 'digitization',
                    'event_date' => '2024-03-01',
                ],
            ],
            'caption' => 'Dokumen digital Piagam Penghargaan Operasi Trikora.',
            'is_primary' => true,
            'is_public' => true,
            'access_level' => 'public',
            'watermark_applied' => false,
            'sort_order' => 1,
        ]);

        $this->upsertConditionReport(
            $archiveItem,
            'good',
            $kurator,
            '2024-03-01 11:00:00',
            'Kertas stabil, tinta masih terbaca.',
            'Simpan dalam map arsip bebas asam dan batasi paparan cahaya.',
            'normal',
            '2024-09-01',
            $archiveAsset->id
        );
    }

    private function upsertCollection(array $data): Collection
    {
        $collection = Collection::firstOrNew([
            'record_code' => $data['record_code'],
        ]);

        if (! $collection->exists) {
            $collection->ulid = (string) Str::ulid();
        }

        $collection->fill($data);
        $collection->save();

        return $collection;
    }

    private function upsertLibraryCopy(Collection $collection, string $copyNumber, string $barcode, string $callNumber, int $locationId): void
    {
        LibraryCopy::updateOrCreate(
            ['barcode' => $barcode],
            [
                'collection_id' => $collection->id,
                'copy_number' => $copyNumber,
                'call_number' => $callNumber,
                'location_id' => $locationId,
                'condition_grade' => 'good',
                'status' => 'available',
                'acquired_at' => '2024-03-15',
                'notes' => null,
            ]
        );
    }

    private function upsertAsset(Collection $collection, string $path, array $data): DigitalAsset
    {
        $asset = DigitalAsset::firstOrNew([
            'collection_id' => $collection->id,
            'path' => $path,
        ]);

        if (! $asset->exists) {
            $asset->ulid = (string) Str::ulid();
        }

        $asset->fill([
            'collection_id' => $collection->id,
            'path' => $path,
            'public_url' => $data['public_url'] ?? null,
            'watermarked_path' => $data['watermarked_path'] ?? null,
            'checksum_sha256' => $data['checksum_sha256'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'technical_metadata' => $data['technical_metadata'] ?? null,
            'captured_at' => $data['captured_at'] ?? null,
            'photographer_name' => $data['photographer_name'] ?? null,
            'view_angle' => $data['view_angle'] ?? null,
            'watermark_applied' => $data['watermark_applied'] ?? false,
        ] + $data);

        $asset->save();

        return $asset;
    }

    private function upsertConditionReport(
        MuseumItem $museumItem,
        string $conditionGrade,
        User $inspector,
        string $inspectedAt,
        string $description,
        string $recommendation,
        string $priority,
        string $nextReviewAt,
        ?int $assetId
    ): void {
        ConditionReport::updateOrCreate(
            [
                'museum_item_id' => $museumItem->id,
                'inspected_at' => $inspectedAt,
            ],
            [
                'condition_grade' => $conditionGrade,
                'inspected_by' => $inspector->id,
                'description' => $description,
                'recommendation' => $recommendation,
                'priority' => $priority,
                'next_review_at' => $nextReviewAt,
                'asset_id' => $assetId,
            ]
        );
    }

    private function attachCreator(Collection $collection, Creator $creator, string $role, bool $isPrimary = false): void
    {
        $collection->creators()->syncWithoutDetaching([
            $creator->id => [
                'role' => $role,
                'sort_order' => 1,
                'is_primary' => $isPrimary,
                'notes' => null,
            ],
        ]);
    }

    private function attachSubject(Collection $collection, Subject $subject, string $type): void
    {
        $collection->subjects()->syncWithoutDetaching([
            $subject->id => [
                'subject_type' => $type,
                'sort_order' => 1,
            ],
        ]);
    }

    private function libraryMetadata(Collection $collection, string $creator, array $subjects, array $data, User $user): void
    {
        $this->metadata($collection, 'dc.title', $collection->title, 'value_string', 0, 'auto_dc_mapping', $user);
        $this->metadata($collection, 'dc.creator', $creator, 'value_string', 0, 'auto_dc_mapping', $user);

        foreach ($subjects as $index => $subject) {
            $this->metadata($collection, 'dc.subject', $subject, 'value_string', $index, 'auto_dc_mapping', $user);
        }

        $this->metadata($collection, 'dc.description', $data['description'], 'value_text', 0, 'manual', $user);
        $this->metadata($collection, 'dc.date', $data['date'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.type', $data['type'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.identifier', $data['identifier'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.language', $data['language'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.rights', $data['rights'], 'value_string', 0, 'manual', $user);

        $this->metadata($collection, 'marc.245.a', $data['marc245a'], 'value_string', 0, 'import_marc', $user);
        $this->metadata($collection, 'marc.260.b', $data['marc260b'], 'value_string', 0, 'import_marc', $user);
        $this->metadata($collection, 'marc.082.a', $data['marc082a'], 'value_string', 0, 'import_marc', $user);
    }

    private function museumMetadata(Collection $collection, string $creator, array $subjects, array $data, User $user): void
    {
        $this->metadata($collection, 'dc.title', $collection->title, 'value_string', 0, 'auto_dc_mapping', $user);
        $this->metadata($collection, 'dc.creator', $creator, 'value_string', 0, 'auto_dc_mapping', $user);

        foreach ($subjects as $index => $subject) {
            $this->metadata($collection, 'dc.subject', $subject, 'value_string', $index, 'auto_dc_mapping', $user);
        }

        $this->metadata($collection, 'dc.description', $data['description'], 'value_text', 0, 'manual', $user);
        $this->metadata($collection, 'dc.date', $data['date'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.type', $data['type'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.identifier', $data['identifier'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'dc.rights', $data['rights'], 'value_string', 0, 'manual', $user);

        $this->metadata($collection, 'cdwa.object.workType', $data['objectType'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.title', $collection->title, 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.creator', $creator, 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.measurements', $data['measurements'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.material.medium', $data['material'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.technique', $data['technique'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.displayCreationDate', $data['date'], 'value_string', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.provenance', $data['provenance'], 'value_text', 0, 'manual', $user);
        $this->metadata($collection, 'cdwa.condition', $data['condition'], 'value_text', 0, 'manual', $user);
    }

    private function metadata(
        Collection $collection,
        string $elementKey,
        mixed $value,
        string $valueColumn,
        int $sortOrder,
        string $source,
        ?User $user
    ): void {
        $element = MetadataElement::where('element_key', $elementKey)->firstOrFail();

        $payload = [
            'is_repeatable_field' => $element->is_repeatable,
            'value_string' => null,
            'value_text' => null,
            'value_integer' => null,
            'value_decimal' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_json' => null,
            'language_code' => $collection->language_code,
            'authority_uri' => null,
            'source' => $source,
            'created_by' => $user?->id,
        ];

        $payload[$valueColumn] = $value;

        ItemMetadata::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'metadata_element_id' => $element->id,
                'sort_order' => $sortOrder,
            ],
            $payload
        );
    }

    private function creator(string $name): Creator
    {
        return Creator::firstOrCreate(
            ['normalized_name' => Str::lower($name)],
            [
                'name' => $name,
                'authority_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/creator/' . Str::slug($name),
            ]
        );
    }

    private function subject(string $term, string $source, string $type): Subject
    {
        return Subject::firstOrCreate(
            [
                'vocabulary_source' => $source,
                'authority_uri' => 'local://simpb/subject/' . Str::slug($term),
            ],
            [
                'term' => $term,
                'slug' => Str::slug($term),
                'type' => $type,
                'scope_note' => null,
            ]
        );
    }
}

class LocationResolver
{
    public static function byCode(string $code)
    {
        return \App\Models\Location::where('code', $code)->firstOrFail();
    }
}