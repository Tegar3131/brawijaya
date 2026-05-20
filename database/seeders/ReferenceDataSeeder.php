<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Creator;
use App\Models\Location;
use App\Models\Material;
use App\Models\MetadataElement;
use App\Models\Subject;
use App\Models\VocabularyTerm;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedVocabularyTerms();
        $this->seedCategories();
        $this->seedLocations();
        $this->seedSubjects();
        $this->seedCreators();
        $this->seedMaterials();
        $this->seedMetadataElements();
    }

    private function seedVocabularyTerms(): void
    {
        $terms = [
            [
                'authority_source' => 'ISO639-2',
                'term_code' => 'ind',
                'term_uri' => 'urn:iso:std:iso:639:-2:ind',
                'preferred_label' => 'Bahasa Indonesia',
                'alt_labels' => ['Indonesian'],
                'scope_note' => 'Kode bahasa untuk metadata koleksi berbahasa Indonesia.',
            ],
            [
                'authority_source' => 'ISO639-2',
                'term_code' => 'eng',
                'term_uri' => 'urn:iso:std:iso:639:-2:eng',
                'preferred_label' => 'English',
                'alt_labels' => ['Bahasa Inggris'],
                'scope_note' => 'Kode bahasa untuk metadata koleksi berbahasa Inggris.',
            ],
            [
                'authority_source' => 'LOCAL',
                'term_code' => 'LIBRARY_BOOK',
                'term_uri' => 'local://simpb/collection-type/book',
                'preferred_label' => 'Buku',
                'alt_labels' => ['Book'],
                'scope_note' => 'Jenis koleksi perpustakaan berupa buku.',
            ],
            [
                'authority_source' => 'LOCAL',
                'term_code' => 'LIBRARY_JOURNAL',
                'term_uri' => 'local://simpb/collection-type/journal',
                'preferred_label' => 'Jurnal',
                'alt_labels' => ['Journal', 'Serial'],
                'scope_note' => 'Jenis koleksi perpustakaan berupa jurnal atau terbitan berseri.',
            ],
            [
                'authority_source' => 'LOCAL',
                'term_code' => 'LIBRARY_THESIS',
                'term_uri' => 'local://simpb/collection-type/thesis',
                'preferred_label' => 'Skripsi/Tesis/Disertasi',
                'alt_labels' => ['Thesis', 'Dissertation', 'ETD'],
                'scope_note' => 'Koleksi karya ilmiah akademik.',
            ],
            [
                'authority_source' => 'LOCAL',
                'term_code' => 'MUSEUM_ARTIFACT',
                'term_uri' => 'local://simpb/collection-type/artifact',
                'preferred_label' => 'Artefak',
                'alt_labels' => ['Object', 'Physical Object'],
                'scope_note' => 'Koleksi museum berupa benda fisik.',
            ],
            [
                'authority_source' => 'LOCAL',
                'term_code' => 'MUSEUM_HISTORICAL_PHOTO',
                'term_uri' => 'local://simpb/collection-type/historical-photo',
                'preferred_label' => 'Foto Bersejarah',
                'alt_labels' => ['Historical Photograph'],
                'scope_note' => 'Koleksi museum berupa foto atau visual bersejarah.',
            ],
            [
                'authority_source' => 'LOCAL',
                'term_code' => 'MUSEUM_ARCHIVE_DOCUMENT',
                'term_uri' => 'local://simpb/collection-type/archive-document',
                'preferred_label' => 'Dokumen Arsip',
                'alt_labels' => ['Archive Document', 'Historical Document'],
                'scope_note' => 'Koleksi dokumen historis dengan deskripsi arsip.',
            ],
            [
                'authority_source' => 'AAT',
                'term_code' => '300011002',
                'term_uri' => 'http://vocab.getty.edu/aat/300011002',
                'preferred_label' => 'Besi',
                'alt_labels' => ['Iron'],
                'scope_note' => 'Material logam untuk artefak.',
            ],
            [
                'authority_source' => 'AAT',
                'term_code' => '300011914',
                'term_uri' => 'http://vocab.getty.edu/aat/300011914',
                'preferred_label' => 'Kayu',
                'alt_labels' => ['Wood'],
                'scope_note' => 'Material organik untuk artefak.',
            ],
            [
                'authority_source' => 'AAT',
                'term_code' => '300014109',
                'term_uri' => 'http://vocab.getty.edu/aat/300014109',
                'preferred_label' => 'Kertas',
                'alt_labels' => ['Paper'],
                'scope_note' => 'Material dokumen, foto, dan arsip.',
            ],
        ];

        foreach ($terms as $term) {
            VocabularyTerm::updateOrCreate(
                [
                    'authority_source' => $term['authority_source'],
                    'term_uri' => $term['term_uri'],
                ],
                $term + ['is_active' => true]
            );
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            [
                'slug' => 'buku',
                'name' => 'Buku',
                'type' => 'library',
                'description' => 'Koleksi buku perpustakaan.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'jurnal',
                'name' => 'Jurnal',
                'type' => 'library',
                'description' => 'Koleksi jurnal, majalah, dan terbitan berseri.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'skripsi-tesis-disertasi',
                'name' => 'Skripsi/Tesis/Disertasi',
                'type' => 'library',
                'description' => 'Koleksi karya ilmiah akademik.',
                'sort_order' => 30,
            ],
            [
                'slug' => 'manuskrip-naskah',
                'name' => 'Manuskrip/Naskah',
                'type' => 'library',
                'description' => 'Koleksi manuskrip atau naskah khusus.',
                'sort_order' => 40,
            ],
            [
                'slug' => 'artefak-bersejarah',
                'name' => 'Artefak Bersejarah',
                'type' => 'museum',
                'description' => 'Koleksi artefak fisik museum.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'senjata',
                'name' => 'Senjata',
                'type' => 'museum',
                'description' => 'Koleksi senjata atau perlengkapan tempur.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'foto-bersejarah',
                'name' => 'Foto Bersejarah',
                'type' => 'museum',
                'description' => 'Koleksi foto dan visual bersejarah.',
                'sort_order' => 30,
            ],
            [
                'slug' => 'dokumen-arsip',
                'name' => 'Dokumen Arsip',
                'type' => 'museum',
                'description' => 'Koleksi dokumen, surat, piagam, atau arsip historis.',
                'sort_order' => 40,
            ],
            [
                'slug' => 'multimedia',
                'name' => 'Multimedia',
                'type' => 'general',
                'description' => 'Koleksi audio, video, dan media digital lainnya.',
                'sort_order' => 50,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['is_active' => true]
            );
        }
    }

    private function seedLocations(): void
    {
        $libraryRoom = Location::updateOrCreate(
            ['code' => 'LIB-RB'],
            [
                'name' => 'Ruang Baca Perpustakaan',
                'location_type' => 'room',
                'description' => 'Ruang baca utama perpustakaan.',
                'is_public' => true,
            ]
        );

        Location::updateOrCreate(
            ['code' => 'LIB-RK-A1'],
            [
                'parent_id' => $libraryRoom->id,
                'name' => 'Rak Perpustakaan A1',
                'location_type' => 'rack',
                'description' => 'Rak koleksi umum perpustakaan.',
                'is_public' => true,
            ]
        );

        Location::updateOrCreate(
            ['code' => 'LIB-RK-ETD'],
            [
                'parent_id' => $libraryRoom->id,
                'name' => 'Rak Karya Ilmiah',
                'location_type' => 'rack',
                'description' => 'Rak khusus skripsi, tesis, dan disertasi.',
                'is_public' => true,
            ]
        );

        $museumRoom = Location::updateOrCreate(
            ['code' => 'MUS-GD1-RG1'],
            [
                'name' => 'Gedung 1 - Ruang Koleksi',
                'location_type' => 'room',
                'description' => 'Ruang penyimpanan dan dokumentasi koleksi museum.',
                'is_public' => false,
            ]
        );

        Location::updateOrCreate(
            ['code' => 'MUS-GD1-RG1-LM1'],
            [
                'parent_id' => $museumRoom->id,
                'name' => 'Lemari Koleksi 1',
                'location_type' => 'cabinet',
                'description' => 'Lemari penyimpanan artefak utama.',
                'is_public' => false,
            ]
        );

        Location::updateOrCreate(
            ['code' => 'MUS-GD1-RG1-DSP1'],
            [
                'parent_id' => $museumRoom->id,
                'name' => 'Display Case 1',
                'location_type' => 'display_case',
                'description' => 'Display case untuk pameran koleksi terpilih.',
                'is_public' => true,
            ]
        );
    }

    private function seedSubjects(): void
    {
        $subjects = [
            [
                'term' => 'Sejarah Militer Indonesia',
                'vocabulary_source' => 'LCSH',
                'authority_uri' => 'local://simpb/subject/sejarah-militer-indonesia',
                'type' => 'topic',
                'scope_note' => 'Topik sejarah militer Indonesia.',
            ],
            [
                'term' => 'Brawijaya',
                'vocabulary_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/subject/brawijaya',
                'type' => 'organization',
                'scope_note' => 'Tema lokal terkait Brawijaya.',
            ],
            [
                'term' => 'Perpustakaan Digital',
                'vocabulary_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/subject/perpustakaan-digital',
                'type' => 'topic',
                'scope_note' => 'Topik digital library dan manajemen koleksi digital.',
            ],
            [
                'term' => 'Metadata',
                'vocabulary_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/subject/metadata',
                'type' => 'topic',
                'scope_note' => 'Topik metadata, deskripsi sumber daya, dan katalogisasi.',
            ],
            [
                'term' => 'Senjata Api',
                'vocabulary_source' => 'AAT',
                'authority_uri' => 'local://simpb/subject/senjata-api',
                'type' => 'topic',
                'scope_note' => 'Artefak berupa senjata api.',
            ],
            [
                'term' => 'Foto Bersejarah',
                'vocabulary_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/subject/foto-bersejarah',
                'type' => 'topic',
                'scope_note' => 'Koleksi visual historis.',
            ],
            [
                'term' => 'Dokumen Militer',
                'vocabulary_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/subject/dokumen-militer',
                'type' => 'topic',
                'scope_note' => 'Dokumen, surat, piagam, dan arsip militer.',
            ],
            [
                'term' => 'Operasi Trikora',
                'vocabulary_source' => 'LOCAL',
                'authority_uri' => 'local://simpb/subject/operasi-trikora',
                'type' => 'event',
                'scope_note' => 'Peristiwa historis terkait Operasi Trikora.',
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                [
                    'vocabulary_source' => $subject['vocabulary_source'],
                    'authority_uri' => $subject['authority_uri'],
                ],
                [
                    'term' => $subject['term'],
                    'slug' => Str::slug($subject['term']),
                    'type' => $subject['type'],
                    'scope_note' => $subject['scope_note'],
                ]
            );
        }
    }

    private function seedCreators(): void
    {
        $creators = [
            [
                'name' => 'Nugroho Notosusanto',
                'authority_source' => 'LOCAL',
                'birth_death_dates' => null,
                'biography' => 'Penulis dan sejarawan Indonesia.',
            ],
            [
                'name' => 'Tim Sejarah Brawijaya',
                'authority_source' => 'LOCAL',
                'birth_death_dates' => null,
                'biography' => 'Nama korporat untuk penulis atau penyusun koleksi sejarah Brawijaya.',
            ],
            [
                'name' => 'Birmingham Small Arms Company',
                'authority_source' => 'LOCAL',
                'birth_death_dates' => null,
                'biography' => 'Produsen senjata asal Inggris.',
            ],
            [
                'name' => 'Fotografer Militer Tidak Diketahui',
                'authority_source' => 'LOCAL',
                'birth_death_dates' => null,
                'biography' => 'Pembuat foto tidak teridentifikasi dalam arsip museum.',
            ],
            [
                'name' => 'Kodam V/Brawijaya',
                'authority_source' => 'LOCAL',
                'birth_death_dates' => null,
                'biography' => 'Institusi terkait koleksi sejarah militer Brawijaya.',
            ],
        ];

        foreach ($creators as $creator) {
            Creator::updateOrCreate(
                ['normalized_name' => Str::lower($creator['name'])],
                [
                    'name' => $creator['name'],
                    'authority_source' => $creator['authority_source'],
                    'authority_uri' => 'local://simpb/creator/' . Str::slug($creator['name']),
                    'birth_death_dates' => $creator['birth_death_dates'],
                    'biography' => $creator['biography'],
                ]
            );
        }
    }

    private function seedMaterials(): void
    {
        $materials = [
            [
                'name' => 'Besi',
                'name_en' => 'Iron',
                'authority_uri' => 'http://vocab.getty.edu/aat/300011002',
                'type' => 'material',
            ],
            [
                'name' => 'Baja',
                'name_en' => 'Steel',
                'authority_uri' => 'http://vocab.getty.edu/aat/300011028',
                'type' => 'material',
            ],
            [
                'name' => 'Kayu',
                'name_en' => 'Wood',
                'authority_uri' => 'http://vocab.getty.edu/aat/300011914',
                'type' => 'material',
            ],
            [
                'name' => 'Kertas',
                'name_en' => 'Paper',
                'authority_uri' => 'http://vocab.getty.edu/aat/300014109',
                'type' => 'material',
            ],
            [
                'name' => 'Tinta',
                'name_en' => 'Ink',
                'authority_uri' => 'http://vocab.getty.edu/aat/300015012',
                'type' => 'material',
            ],
            [
                'name' => 'Tekstil',
                'name_en' => 'Textile',
                'authority_uri' => 'http://vocab.getty.edu/aat/300231565',
                'type' => 'material',
            ],
            [
                'name' => 'Fotografi gelatin silver',
                'name_en' => 'Gelatin silver process',
                'authority_uri' => 'http://vocab.getty.edu/aat/300127121',
                'type' => 'technique',
            ],
            [
                'name' => 'Cetak',
                'name_en' => 'Printing',
                'authority_uri' => 'http://vocab.getty.edu/aat/300053301',
                'type' => 'technique',
            ],
            [
                'name' => 'Perakitan mekanis',
                'name_en' => 'Mechanical assembly',
                'authority_uri' => null,
                'type' => 'technique',
            ],
        ];

        foreach ($materials as $material) {
            Material::updateOrCreate(
                [
                    'name' => $material['name'],
                    'type' => $material['type'],
                ],
                $material + ['is_active' => true]
            );
        }
    }

    private function seedMetadataElements(): void
    {
        $elements = [
            // Dublin Core baseline
            ['dc', 'dc.title', 'Title', 'string', false, true, 'all', null, 10],
            ['dc', 'dc.creator', 'Creator', 'string', true, false, 'all', null, 20],
            ['dc', 'dc.subject', 'Subject', 'string', true, false, 'all', 'LCSH/LOCAL', 30],
            ['dc', 'dc.description', 'Description', 'text', false, false, 'all', null, 40],
            ['dc', 'dc.publisher', 'Publisher', 'string', false, false, 'library', null, 50],
            ['dc', 'dc.contributor', 'Contributor', 'string', true, false, 'all', null, 60],
            ['dc', 'dc.date', 'Date', 'string', false, false, 'all', null, 70],
            ['dc', 'dc.type', 'Type', 'string', false, false, 'all', 'LOCAL', 80],
            ['dc', 'dc.format', 'Format', 'string', false, false, 'all', null, 90],
            ['dc', 'dc.identifier', 'Identifier', 'string', true, false, 'all', null, 100],
            ['dc', 'dc.source', 'Source', 'string', false, false, 'all', null, 110],
            ['dc', 'dc.language', 'Language', 'string', false, false, 'all', 'ISO639-2', 120],
            ['dc', 'dc.relation', 'Relation', 'string', true, false, 'all', null, 130],
            ['dc', 'dc.coverage', 'Coverage', 'string', true, false, 'all', null, 140],
            ['dc', 'dc.rights', 'Rights', 'string', false, false, 'all', null, 150],

            // MARC 21 library
            ['marc21', 'marc.leader', 'MARC Leader', 'string', false, false, 'library', null, 10],
            ['marc21', 'marc.020.a', 'ISBN', 'string', false, false, 'library', null, 20],
            ['marc21', 'marc.022.a', 'ISSN', 'string', false, false, 'library', null, 30],
            ['marc21', 'marc.082.a', 'DDC Classification', 'string', false, false, 'library', null, 40],
            ['marc21', 'marc.100.a', 'Main Entry - Personal Name', 'string', false, false, 'library', null, 50],
            ['marc21', 'marc.245.a', 'Title Statement', 'string', false, true, 'library', null, 60],
            ['marc21', 'marc.245.b', 'Remainder of Title', 'string', false, false, 'library', null, 70],
            ['marc21', 'marc.260.a', 'Place of Publication', 'string', false, false, 'library', null, 80],
            ['marc21', 'marc.260.b', 'Publisher', 'string', false, false, 'library', null, 90],
            ['marc21', 'marc.260.c', 'Date of Publication', 'string', false, false, 'library', null, 100],
            ['marc21', 'marc.300.a', 'Physical Description', 'string', false, false, 'library', null, 110],
            ['marc21', 'marc.650.a', 'Subject Added Entry', 'string', true, false, 'library', 'LCSH/LOCAL', 120],

            // MODS library/API
            ['mods', 'mods.titleInfo.title', 'MODS Title', 'string', false, false, 'library', null, 10],
            ['mods', 'mods.name.namePart', 'MODS Name', 'string', true, false, 'library', null, 20],
            ['mods', 'mods.genre', 'MODS Genre', 'string', false, false, 'library', null, 30],
            ['mods', 'mods.originInfo.dateIssued', 'MODS Date Issued', 'string', false, false, 'library', null, 40],
            ['mods', 'mods.abstract', 'MODS Abstract', 'text', false, false, 'library', null, 50],
            ['mods', 'mods.identifier', 'MODS Identifier', 'string', true, false, 'library', null, 60],

            // CDWA Lite museum
            ['cdwa_lite', 'cdwa.object.workType', 'Object/Work Type', 'string', false, true, 'museum', 'AAT/LOCAL', 10],
            ['cdwa_lite', 'cdwa.title', 'Object Title', 'string', false, true, 'museum', null, 20],
            ['cdwa_lite', 'cdwa.creator', 'Creator/Maker', 'string', true, false, 'museum', 'ULAN/LOCAL', 30],
            ['cdwa_lite', 'cdwa.measurements', 'Measurements', 'string', false, false, 'museum', null, 40],
            ['cdwa_lite', 'cdwa.material.medium', 'Material/Medium', 'string', true, false, 'museum', 'AAT', 50],
            ['cdwa_lite', 'cdwa.technique', 'Technique', 'string', true, false, 'museum', 'AAT', 60],
            ['cdwa_lite', 'cdwa.displayCreationDate', 'Display Creation Date', 'string', false, false, 'museum', null, 70],
            ['cdwa_lite', 'cdwa.location', 'Current Location', 'string', false, false, 'museum', null, 80],
            ['cdwa_lite', 'cdwa.provenance', 'Provenance', 'text', false, false, 'museum', null, 90],
            ['cdwa_lite', 'cdwa.condition', 'Condition', 'text', false, false, 'museum', null, 100],

            // VRA Core visual resource
            ['vra_core', 'vra.workType', 'Work Type', 'string', false, false, 'museum', 'AAT', 10],
            ['vra_core', 'vra.title', 'VRA Title', 'string', false, false, 'museum', null, 20],
            ['vra_core', 'vra.agent', 'Agent', 'string', true, false, 'museum', null, 30],
            ['vra_core', 'vra.material', 'Material', 'string', true, false, 'museum', 'AAT', 40],
            ['vra_core', 'vra.measurements', 'Measurements', 'string', false, false, 'museum', null, 50],
            ['vra_core', 'vra.date', 'Date', 'string', false, false, 'museum', null, 60],
            ['vra_core', 'vra.location', 'Location', 'string', false, false, 'museum', null, 70],
            ['vra_core', 'vra.image.view', 'Image View', 'string', false, false, 'digital_asset', null, 80],
            ['vra_core', 'vra.image.rights', 'Image Rights', 'string', false, false, 'digital_asset', null, 90],

            // ISAD(G) archive
            ['isad_g', 'isad.reference_code', 'Reference Code', 'string', false, true, 'museum', null, 10],
            ['isad_g', 'isad.title', 'Title', 'string', false, true, 'museum', null, 20],
            ['isad_g', 'isad.date', 'Date', 'string', false, false, 'museum', null, 30],
            ['isad_g', 'isad.level_of_description', 'Level of Description', 'string', false, false, 'museum', null, 40],
            ['isad_g', 'isad.extent_medium', 'Extent and Medium', 'string', false, false, 'museum', null, 50],
            ['isad_g', 'isad.creator_name', 'Name of Creator', 'string', true, false, 'museum', null, 60],
            ['isad_g', 'isad.administrative_history', 'Administrative/Biographical History', 'text', false, false, 'museum', null, 70],
            ['isad_g', 'isad.custodial_history', 'Archival History', 'text', false, false, 'museum', null, 80],
            ['isad_g', 'isad.scope_content', 'Scope and Content', 'text', false, false, 'museum', null, 90],
            ['isad_g', 'isad.access_conditions', 'Conditions Governing Access', 'text', false, false, 'museum', null, 100],
            ['isad_g', 'isad.physical_condition', 'Physical Characteristics', 'text', false, false, 'museum', null, 110],

            // METS / PREMIS / MIX preservation and technical metadata
            ['mets', 'mets.objid', 'METS Object ID', 'string', false, false, 'digital_asset', null, 10],
            ['mets', 'mets.fileSec', 'METS File Section', 'json', false, false, 'digital_asset', null, 20],
            ['premis', 'premis.object.identifier', 'PREMIS Object Identifier', 'string', false, false, 'digital_asset', null, 10],
            ['premis', 'premis.event.type', 'PREMIS Event Type', 'string', true, false, 'digital_asset', null, 20],
            ['premis', 'premis.event.dateTime', 'PREMIS Event Date Time', 'datetime', true, false, 'digital_asset', null, 30],
            ['mix', 'mix.imageWidth', 'MIX Image Width', 'integer', false, false, 'digital_asset', null, 10],
            ['mix', 'mix.imageLength', 'MIX Image Length', 'integer', false, false, 'digital_asset', null, 20],
            ['mix', 'mix.bitsPerSample', 'MIX Bits Per Sample', 'integer', false, false, 'digital_asset', null, 30],
        ];

        foreach ($elements as [$standard, $key, $label, $dataType, $repeatable, $required, $appliesTo, $vocabularySource, $sortOrder]) {
            MetadataElement::updateOrCreate(
                [
                    'standard' => $standard,
                    'element_key' => $key,
                    'applies_to' => $appliesTo,
                ],
                [
                    'label' => $label,
                    'data_type' => $dataType,
                    'is_repeatable' => $repeatable,
                    'is_required' => $required,
                    'vocabulary_source' => $vocabularySource,
                    'sort_order' => $sortOrder,
                    'help_text' => null,
                ]
            );
        }
    }
}