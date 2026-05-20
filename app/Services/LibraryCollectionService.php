<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Creator;
use App\Models\DigitalAsset;
use App\Models\ItemMetadata;
use App\Models\LibraryCopy;
use App\Models\LibraryItem;
use App\Models\MetadataElement;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LibraryCollectionService
{
    public function __construct(
        private readonly CollectionService $collectionService,
        private readonly AuditLogService $auditLogService,
        private readonly CollectionVersionService $collectionVersionService,
        private readonly CollectionMetadataAutoSyncService $autoSyncService
    ) {
    }

    /**
     * Membuat koleksi perpustakaan lengkap secara transaksional.
     *
     * Struktur $data:
     * [
     *   'collection' => [...],
     *   'library_item' => [...],
     *   'copies' => [[...], [...]],
     *   'creators' => [[...]],
     *   'subjects' => [[...]],
     *   'metadata' => [[...]], // opsional metadata tambahan
     *   'digital_assets' => [[...]]
     * ]
     *
     * @throws ValidationException
     */
    public function create(array $data, ?User $actor = null, ?string $reason = 'Create library collection'): Collection
    {
        $validated = $this->validatePayload($data);

        return DB::transaction(function () use ($validated, $actor, $reason) {
            $collectionData = $validated['collection'];
            $collectionData['unit_type'] = 'library';
            $collectionData['publication_status'] = $collectionData['publication_status'] ?? 'draft';
            $collectionData['visibility'] = $collectionData['visibility'] ?? 'public';

            $collection = $this->collectionService->createBase(
                data: $collectionData,
                actor: $actor,
                reason: $reason
            );

            $libraryItem = $this->createLibraryItem(
                collection: $collection,
                data: $validated['library_item']
            );

            $this->createCopies(
                collection: $collection,
                copies: $validated['copies'] ?? []
            );

            $creators = $this->syncCreators(
                collection: $collection,
                creators: $validated['creators'] ?? []
            );

            $subjects = $this->syncSubjects(
                collection: $collection,
                subjects: $validated['subjects'] ?? []
            );

            $this->autoSyncService->sync($collection, $actor);

            $this->createAdditionalMetadata(
                collection: $collection,
                metadataRows: $validated['metadata'] ?? [],
                actor: $actor
            );

            $this->createDigitalAssets(
                collection: $collection,
                assets: $validated['digital_assets'] ?? [],
                actor: $actor
            );

            $fresh = $collection->fresh([
                'libraryItem',
                'libraryCopies',
                'creators',
                'subjects',
                'metadata',
                'digitalAssets',
            ]);

            $this->collectionVersionService->record(
                collection: $fresh,
                actor: $actor,
                reason: 'Library details, metadata, copies, and assets attached',
                diff: [
                    'old' => null,
                    'new' => [
                        'library_item_id' => $libraryItem->id,
                        'copies_count' => $fresh->libraryCopies()->count(),
                        'creators_count' => $fresh->creators()->count(),
                        'subjects_count' => $fresh->subjects()->count(),
                        'metadata_count' => $fresh->metadata()->count(),
                        'digital_assets_count' => $fresh->digitalAssets()->count(),
                    ],
                ]
            );

            $this->auditLogService->record(
                module: 'library',
                action: 'create',
                event: 'library_collection.created',
                actor: $actor,
                auditable: $fresh,
                oldValues: null,
                newValues: [
                    'record_code' => $fresh->record_code,
                    'title' => $fresh->title,
                    'library_item_id' => $libraryItem->id,
                    'copies_count' => $fresh->libraryCopies()->count(),
                    'metadata_count' => $fresh->metadata()->count(),
                ],
                metadata: [
                    'reason' => $reason,
                    'collection_type' => $fresh->collection_type,
                    'bibliographic_level' => $libraryItem->bibliographic_level,
                ]
            );

            return $fresh;
        });
    }

    /**
     * Memperbarui koleksi perpustakaan secara terpadu.
     * Tidak menghapus/mengganti copies dan metadata tambahan (dikelola terpisah).
     */
    public function update(Collection $collection, array $data, ?User $actor = null, ?string $reason = 'Update library collection'): Collection
    {
        $validated = $this->validatePayload($data, $collection->id);

        return DB::transaction(function () use ($collection, $validated, $actor, $reason) {
            $collectionData = $validated['collection'];

            $collection = $this->collectionService->updateBase(
                collection: $collection,
                data: $collectionData,
                actor: $actor,
                reason: $reason
            );

            if (isset($validated['library_item'])) {
                $libraryItem = $collection->libraryItem;
                if ($libraryItem) {
                    $libraryItem->update($validated['library_item']);
                } else {
                    $libraryItem = $this->createLibraryItem($collection, $validated['library_item']);
                }
            }

            if (isset($validated['creators'])) {
                $collection->creators()->detach();
                $this->syncCreators($collection, $validated['creators']);
            }

            if (isset($validated['subjects'])) {
                $collection->subjects()->detach();
                $this->syncSubjects($collection, $validated['subjects']);
            }

            $this->autoSyncService->sync($collection, $actor);

            $fresh = $collection->fresh([
                'libraryItem',
                'libraryCopies',
                'creators',
                'subjects',
                'metadata',
                'digitalAssets',
            ]);

            $this->auditLogService->record(
                module: 'library',
                action: 'update',
                event: 'library_collection.updated_from_staff_form',
                actor: $actor,
                auditable: $fresh,
                oldValues: null,
                newValues: [
                    'record_code' => $fresh->record_code,
                    'title' => $fresh->title,
                    'library_item_id' => $fresh->libraryItem?->id,
                ],
                metadata: [
                    'reason' => $reason,
                ]
            );

            return $fresh;
        });
    }

    private function createLibraryItem(Collection $collection, array $data): LibraryItem
    {
        return LibraryItem::create([
            'collection_id' => $collection->id,
            'bibliographic_level' => $data['bibliographic_level'] ?? 'monograph',
            'isbn13' => $data['isbn13'] ?? null,
            'isbn10' => $data['isbn10'] ?? null,
            'issn' => $data['issn'] ?? null,
            'doi' => $data['doi'] ?? null,
            'publisher_name' => $data['publisher_name'] ?? null,
            'publisher_place' => $data['publisher_place'] ?? null,
            'edition' => $data['edition'] ?? null,
            'publication_year' => $data['publication_year'] ?? null,
            'publication_date' => $data['publication_date'] ?? null,
            'ddc_classification' => $data['ddc_classification'] ?? null,
            'call_number' => $data['call_number'] ?? null,
            'marc_leader' => $data['marc_leader'] ?? null,
            'marc_control_number' => $data['marc_control_number'] ?? null,
            'marc_raw_json' => $data['marc_raw_json'] ?? null,
            'mods_xml' => $data['mods_xml'] ?? null,
            'physical_extent' => $data['physical_extent'] ?? null,
            'physical_dimensions' => $data['physical_dimensions'] ?? null,
            'pages' => $data['pages'] ?? null,
            'illustrations' => $data['illustrations'] ?? null,
            'series_title' => $data['series_title'] ?? null,
            'source_acquisition' => $data['source_acquisition'] ?? null,
            'acquired_at' => $data['acquired_at'] ?? null,
        ]);
    }

    private function createCopies(Collection $collection, array $copies): void
    {
        foreach ($copies as $copy) {
            LibraryCopy::create([
                'collection_id' => $collection->id,
                'copy_number' => $copy['copy_number'],
                'barcode' => $copy['barcode'],
                'call_number' => $copy['call_number'],
                'location_id' => $copy['location_id'] ?? $collection->current_location_id,
                'condition_grade' => $copy['condition_grade'] ?? 'good',
                'status' => $copy['status'] ?? 'available',
                'acquired_at' => $copy['acquired_at'] ?? null,
                'last_inventory_at' => $copy['last_inventory_at'] ?? null,
                'notes' => $copy['notes'] ?? null,
            ]);
        }
    }

    private function syncCreators(Collection $collection, array $creators): array
    {
        $resolved = [];

        foreach ($creators as $index => $creatorData) {
            $name = trim($creatorData['name']);

            $creator = Creator::firstOrCreate(
                ['normalized_name' => Str::lower($name)],
                [
                    'name' => $name,
                    'authority_source' => $creatorData['authority_source'] ?? 'LOCAL',
                    'authority_uri' => $creatorData['authority_uri'] ?? 'local://simpb/creator/' . Str::slug($name),
                    'birth_death_dates' => $creatorData['birth_death_dates'] ?? null,
                    'biography' => $creatorData['biography'] ?? null,
                ]
            );

            $collection->creators()->attach($creator->id, [
                'role' => $creatorData['role'] ?? 'author',
                'sort_order' => $creatorData['sort_order'] ?? ($index + 1),
                'is_primary' => $creatorData['is_primary'] ?? $index === 0,
                'notes' => $creatorData['notes'] ?? null,
            ]);

            $resolved[] = [
                'model' => $creator,
                'role' => $creatorData['role'] ?? 'author',
                'sort_order' => $creatorData['sort_order'] ?? ($index + 1),
                'is_primary' => $creatorData['is_primary'] ?? $index === 0,
            ];
        }

        return $resolved;
    }

    private function syncSubjects(Collection $collection, array $subjects): array
    {
        $resolved = [];

        foreach ($subjects as $index => $subjectData) {
            $term = trim($subjectData['term']);
            $source = $subjectData['vocabulary_source'] ?? 'LOCAL';
            $authorityUri = $subjectData['authority_uri'] ?? 'local://simpb/subject/' . Str::slug($term);

            $subject = Subject::firstOrCreate(
                [
                    'vocabulary_source' => $source,
                    'authority_uri' => $authorityUri,
                ],
                [
                    'term' => $term,
                    'slug' => Str::slug($term),
                    'type' => $subjectData['type'] ?? 'topic',
                    'scope_note' => $subjectData['scope_note'] ?? null,
                ]
            );

            $collection->subjects()->attach($subject->id, [
                'subject_type' => $subjectData['subject_type'] ?? ($index === 0 ? 'primary' : 'secondary'),
                'sort_order' => $subjectData['sort_order'] ?? ($index + 1),
            ]);

            $resolved[] = [
                'model' => $subject,
                'subject_type' => $subjectData['subject_type'] ?? ($index === 0 ? 'primary' : 'secondary'),
                'sort_order' => $subjectData['sort_order'] ?? ($index + 1),
            ];
        }

        return $resolved;
    }

    private function createAutomaticMetadata(
        Collection $collection,
        LibraryItem $libraryItem,
        array $creators,
        array $subjects,
        ?User $actor
    ): void {
        $this->metadata($collection, 'dc.title', $collection->title, 0, 'auto_dc_mapping', $actor);

        foreach ($creators as $index => $creator) {
            $this->metadata(
                collection: $collection,
                elementKey: 'dc.creator',
                value: $creator['model']->name,
                sortOrder: $index,
                source: 'auto_dc_mapping',
                actor: $actor
            );
        }

        foreach ($subjects as $index => $subject) {
            $this->metadata(
                collection: $collection,
                elementKey: 'dc.subject',
                value: $subject['model']->term,
                sortOrder: $index,
                source: 'auto_dc_mapping',
                actor: $actor
            );
        }

        if ($collection->description) {
            $this->metadata($collection, 'dc.description', $collection->description, 0, 'auto_dc_mapping', $actor);
        }

        if ($collection->date_display) {
            $this->metadata($collection, 'dc.date', $collection->date_display, 0, 'auto_dc_mapping', $actor);
        }

        $this->metadata($collection, 'dc.type', $collection->collection_type, 0, 'auto_dc_mapping', $actor);

        $identifier = $libraryItem->isbn13
            ?? $libraryItem->isbn10
            ?? $libraryItem->issn
            ?? $libraryItem->doi
            ?? $collection->record_code;

        $this->metadata($collection, 'dc.identifier', $identifier, 0, 'auto_dc_mapping', $actor);

        if ($collection->language_code) {
            $this->metadata($collection, 'dc.language', $collection->language_code, 0, 'auto_dc_mapping', $actor);
        }

        if ($collection->rights_status) {
            $this->metadata($collection, 'dc.rights', $collection->rights_status, 0, 'auto_dc_mapping', $actor);
        }

        if ($libraryItem->publisher_name) {
            $this->metadata($collection, 'dc.publisher', $libraryItem->publisher_name, 0, 'auto_dc_mapping', $actor);
        }

        if ($libraryItem->isbn13) {
            $this->metadata($collection, 'marc.020.a', $libraryItem->isbn13, 0, 'auto_marc_mapping', $actor);
        }

        if ($libraryItem->issn) {
            $this->metadata($collection, 'marc.022.a', $libraryItem->issn, 0, 'auto_marc_mapping', $actor);
        }

        if ($libraryItem->ddc_classification) {
            $this->metadata($collection, 'marc.082.a', $libraryItem->ddc_classification, 0, 'auto_marc_mapping', $actor);
        }

        $primaryCreator = collect($creators)->firstWhere('is_primary', true) ?? $creators[0] ?? null;

        if ($primaryCreator) {
            $this->metadata($collection, 'marc.100.a', $primaryCreator['model']->name, 0, 'auto_marc_mapping', $actor);
        }

        $this->metadata($collection, 'marc.245.a', $collection->title, 0, 'auto_marc_mapping', $actor);

        if ($collection->subtitle) {
            $this->metadata($collection, 'marc.245.b', $collection->subtitle, 0, 'auto_marc_mapping', $actor);
        }

        if ($libraryItem->publisher_place) {
            $this->metadata($collection, 'marc.260.a', $libraryItem->publisher_place, 0, 'auto_marc_mapping', $actor);
        }

        if ($libraryItem->publisher_name) {
            $this->metadata($collection, 'marc.260.b', $libraryItem->publisher_name, 0, 'auto_marc_mapping', $actor);
        }

        if ($libraryItem->publication_year) {
            $this->metadata($collection, 'marc.260.c', (string) $libraryItem->publication_year, 0, 'auto_marc_mapping', $actor);
        }

        if ($libraryItem->physical_extent) {
            $this->metadata($collection, 'marc.300.a', $libraryItem->physical_extent, 0, 'auto_marc_mapping', $actor);
        }

        foreach ($subjects as $index => $subject) {
            $this->metadata($collection, 'marc.650.a', $subject['model']->term, $index, 'auto_marc_mapping', $actor);
        }

        $this->metadata($collection, 'mods.titleInfo.title', $collection->title, 0, 'auto_mods_mapping', $actor);

        foreach ($creators as $index => $creator) {
            $this->metadata($collection, 'mods.name.namePart', $creator['model']->name, $index, 'auto_mods_mapping', $actor);
        }

        $this->metadata($collection, 'mods.genre', $libraryItem->bibliographic_level, 0, 'auto_mods_mapping', $actor);

        if ($libraryItem->publication_year) {
            $this->metadata($collection, 'mods.originInfo.dateIssued', (string) $libraryItem->publication_year, 0, 'auto_mods_mapping', $actor);
        }

        if ($collection->description) {
            $this->metadata($collection, 'mods.abstract', $collection->description, 0, 'auto_mods_mapping', $actor);
        }

        $this->metadata($collection, 'mods.identifier', $identifier, 0, 'auto_mods_mapping', $actor);
    }

    private function createAdditionalMetadata(Collection $collection, array $metadataRows, ?User $actor): void
    {
        foreach ($metadataRows as $index => $row) {
            $this->metadata(
                collection: $collection,
                elementKey: $row['element_key'],
                value: $row['value'],
                sortOrder: $row['sort_order'] ?? $index,
                source: $row['source'] ?? 'manual',
                actor: $actor,
                valueColumn: $row['value_column'] ?? null,
                languageCode: $row['language_code'] ?? $collection->language_code,
                authorityUri: $row['authority_uri'] ?? null
            );
        }
    }

    private function createDigitalAssets(Collection $collection, array $assets, ?User $actor): void
    {
        foreach ($assets as $asset) {
            $path = $asset['path'];
            $filename = $asset['filename'] ?? basename($path);
            $extension = $asset['extension'] ?? pathinfo($filename, PATHINFO_EXTENSION);

            DigitalAsset::create([
                'ulid' => (string) Str::ulid(),
                'collection_id' => $collection->id,
                'uploaded_by' => $asset['uploaded_by'] ?? $actor?->id,
                'asset_type' => $asset['asset_type'],
                'file_role' => $asset['file_role'] ?? 'original',
                'disk' => $asset['disk'] ?? 'public',
                'path' => $path,
                'public_url' => $asset['public_url'] ?? null,
                'thumbnail_path' => $asset['thumbnail_path'] ?? null,
                'watermarked_path' => $asset['watermarked_path'] ?? null,
                'filename' => $filename,
                'original_filename' => $asset['original_filename'] ?? $filename,
                'mime_type' => $asset['mime_type'],
                'extension' => $extension,
                'size_bytes' => $asset['size_bytes'] ?? 0,
                'checksum_sha256' => $asset['checksum_sha256'] ?? null,
                'width_px' => $asset['width_px'] ?? null,
                'height_px' => $asset['height_px'] ?? null,
                'duration_seconds' => $asset['duration_seconds'] ?? null,
                'technical_metadata' => $asset['technical_metadata'] ?? null,
                'captured_at' => $asset['captured_at'] ?? null,
                'photographer_name' => $asset['photographer_name'] ?? null,
                'view_angle' => $asset['view_angle'] ?? null,
                'caption' => $asset['caption'] ?? null,
                'is_primary' => $asset['is_primary'] ?? false,
                'is_public' => $asset['is_public'] ?? true,
                'access_level' => $asset['access_level'] ?? 'public',
                'watermark_applied' => $asset['watermark_applied'] ?? false,
                'sort_order' => $asset['sort_order'] ?? 0,
            ]);
        }
    }

    private function metadata(
        Collection $collection,
        string $elementKey,
        mixed $value,
        int $sortOrder,
        string $source,
        ?User $actor,
        ?string $valueColumn = null,
        ?string $languageCode = null,
        ?string $authorityUri = null
    ): ItemMetadata {
        $element = MetadataElement::where('element_key', $elementKey)->firstOrFail();

        $valueColumn = $valueColumn ?? $this->valueColumnFor($element, $value);

        $payload = [
            'is_repeatable_field' => $element->is_repeatable,
            'value_string' => null,
            'value_text' => null,
            'value_integer' => null,
            'value_decimal' => null,
            'value_date' => null,
            'value_datetime' => null,
            'value_json' => null,
            'language_code' => $languageCode ?? $collection->language_code,
            'authority_uri' => $authorityUri,
            'source' => $source,
            'created_by' => $actor?->id,
        ];

        $payload[$valueColumn] = $value;

        return ItemMetadata::updateOrCreate(
            [
                'collection_id' => $collection->id,
                'metadata_element_id' => $element->id,
                'sort_order' => $sortOrder,
            ],
            $payload
        );
    }

    private function valueColumnFor(MetadataElement $element, mixed $value): string
    {
        if (is_array($value)) {
            return 'value_json';
        }

        return match ($element->data_type) {
            'text' => 'value_text',
            'integer' => 'value_integer',
            'decimal' => 'value_decimal',
            'date' => 'value_date',
            'datetime' => 'value_datetime',
            'json' => 'value_json',
            default => 'value_string',
        };
    }

    /**
     * @throws ValidationException
     */
    private function validatePayload(array $data, ?int $collectionId = null): array
    {
        $rules = [
            'collection' => ['required', 'array'],
            'collection.record_code' => [
                'required', 
                'string', 
                'max:80', 
                Rule::unique('collections', 'record_code')->ignore($collectionId)
            ],
            'collection.collection_type' => ['required', 'string', 'max:80'],
            'collection.title' => ['required', 'string', 'max:500'],
            'collection.subtitle' => ['sometimes', 'nullable', 'string', 'max:500'],
            'collection.description' => ['sometimes', 'nullable', 'string'],
            'collection.language_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'collection.rights_status' => ['sometimes', 'nullable', 'string', 'max:120'],
            'collection.date_display' => ['sometimes', 'nullable', 'string', 'max:120'],
            'collection.year_start' => ['sometimes', 'nullable', 'integer'],
            'collection.year_end' => ['sometimes', 'nullable', 'integer'],
            'collection.category_id' => ['sometimes', 'nullable', 'exists:categories,id'],
            'collection.current_location_id' => ['sometimes', 'nullable', 'exists:locations,id'],
            'collection.publication_status' => ['sometimes', Rule::in(['draft', 'published', 'restricted', 'archived'])],
            'collection.visibility' => ['sometimes', Rule::in(['public', 'member', 'internal', 'restricted'])],
            'collection.is_featured' => ['sometimes', 'boolean'],
            'collection.featured_order' => ['sometimes', 'nullable', 'integer'],

            'library_item' => ['required', 'array'],
            'library_item.bibliographic_level' => [
                'required',
                Rule::in(['monograph', 'serial', 'article', 'thesis', 'map', 'manuscript']),
            ],
            'library_item.isbn13' => ['sometimes', 'nullable', 'string', 'max:20', 'unique:library_items,isbn13'],
            'library_item.isbn10' => ['sometimes', 'nullable', 'string', 'max:20'],
            'library_item.issn' => ['sometimes', 'nullable', 'string', 'max:20'],
            'library_item.doi' => ['sometimes', 'nullable', 'string', 'max:160'],
            'library_item.publisher_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'library_item.publisher_place' => ['sometimes', 'nullable', 'string', 'max:160'],
            'library_item.edition' => ['sometimes', 'nullable', 'string', 'max:120'],
            'library_item.publication_year' => ['sometimes', 'nullable', 'integer'],
            'library_item.publication_date' => ['sometimes', 'nullable', 'date'],
            'library_item.ddc_classification' => ['sometimes', 'nullable', 'string', 'max:60'],
            'library_item.call_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'library_item.marc_leader' => ['sometimes', 'nullable', 'string', 'max:40'],
            'library_item.marc_control_number' => ['sometimes', 'nullable', 'string', 'max:120'],
            'library_item.marc_raw_json' => ['sometimes', 'nullable', 'array'],
            'library_item.mods_xml' => ['sometimes', 'nullable', 'string'],
            'library_item.physical_extent' => ['sometimes', 'nullable', 'string', 'max:160'],
            'library_item.physical_dimensions' => ['sometimes', 'nullable', 'string', 'max:120'],
            'library_item.pages' => ['sometimes', 'nullable', 'integer'],
            'library_item.illustrations' => ['sometimes', 'nullable', 'string', 'max:255'],
            'library_item.series_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'library_item.source_acquisition' => ['sometimes', 'nullable', 'string', 'max:120'],
            'library_item.acquired_at' => ['sometimes', 'nullable', 'date'],

            'copies' => ['sometimes', 'array'],
            'copies.*.copy_number' => ['required_with:copies', 'string', 'max:40'],
            'copies.*.barcode' => ['required_with:copies', 'string', 'max:120', 'distinct', 'unique:library_copies,barcode'],
            'copies.*.call_number' => ['required_with:copies', 'string', 'max:120'],
            'copies.*.location_id' => ['sometimes', 'nullable', 'exists:locations,id'],
            'copies.*.condition_grade' => ['sometimes', Rule::in(['excellent', 'good', 'fair', 'poor'])],
            'copies.*.status' => ['sometimes', Rule::in(['available', 'borrowed', 'reserved', 'repair', 'lost', 'archived'])],
            'copies.*.acquired_at' => ['sometimes', 'nullable', 'date'],
            'copies.*.last_inventory_at' => ['sometimes', 'nullable', 'date'],
            'copies.*.notes' => ['sometimes', 'nullable', 'string'],

            'creators' => ['required', 'array', 'min:1'],
            'creators.*.name' => ['required', 'string', 'max:255'],
            'creators.*.role' => ['sometimes', 'string', 'max:80'],
            'creators.*.is_primary' => ['sometimes', 'boolean'],
            'creators.*.sort_order' => ['sometimes', 'integer'],
            'creators.*.authority_source' => ['sometimes', 'nullable', 'string', 'max:40'],
            'creators.*.authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'creators.*.birth_death_dates' => ['sometimes', 'nullable', 'string', 'max:120'],
            'creators.*.biography' => ['sometimes', 'nullable', 'string'],

            'subjects' => ['sometimes', 'array'],
            'subjects.*.term' => ['required_with:subjects', 'string', 'max:255'],
            'subjects.*.vocabulary_source' => ['sometimes', 'nullable', 'string', 'max:40'],
            'subjects.*.authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'subjects.*.type' => [
                'sometimes',
                Rule::in(['topic', 'geographic', 'temporal', 'person', 'organization', 'event']),
            ],
            'subjects.*.subject_type' => ['sometimes', 'string', 'max:60'],
            'subjects.*.sort_order' => ['sometimes', 'integer'],
            'subjects.*.scope_note' => ['sometimes', 'nullable', 'string'],

            'metadata' => ['sometimes', 'array'],
            'metadata.*.element_key' => ['required_with:metadata', 'exists:metadata_elements,element_key'],
            'metadata.*.value' => ['required_with:metadata'],
            'metadata.*.value_column' => [
                'sometimes',
                Rule::in([
                    'value_string',
                    'value_text',
                    'value_integer',
                    'value_decimal',
                    'value_date',
                    'value_datetime',
                    'value_json',
                ]),
            ],
            'metadata.*.sort_order' => ['sometimes', 'integer'],
            'metadata.*.source' => ['sometimes', 'nullable', 'string', 'max:80'],
            'metadata.*.language_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'metadata.*.authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],

            'digital_assets' => ['sometimes', 'array'],
            'digital_assets.*.asset_type' => [
                'required_with:digital_assets',
                Rule::in(['cover', 'photo', 'document', 'pdf', 'epub', 'video', 'audio', 'thumbnail', 'mets_package']),
            ],
            'digital_assets.*.file_role' => [
                'sometimes',
                Rule::in(['original', 'access', 'thumbnail', 'watermarked', 'derivative']),
            ],
            'digital_assets.*.disk' => ['sometimes', 'string', 'max:60'],
            'digital_assets.*.path' => ['required_with:digital_assets', 'string', 'max:1000'],
            'digital_assets.*.public_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'digital_assets.*.thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'digital_assets.*.watermarked_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'digital_assets.*.filename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'digital_assets.*.original_filename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'digital_assets.*.mime_type' => ['required_with:digital_assets', 'string', 'max:120'],
            'digital_assets.*.extension' => ['sometimes', 'nullable', 'string', 'max:20'],
            'digital_assets.*.size_bytes' => ['sometimes', 'integer'],
            'digital_assets.*.checksum_sha256' => ['sometimes', 'nullable', 'string', 'max:64'],
            'digital_assets.*.width_px' => ['sometimes', 'nullable', 'integer'],
            'digital_assets.*.height_px' => ['sometimes', 'nullable', 'integer'],
            'digital_assets.*.duration_seconds' => ['sometimes', 'nullable', 'integer'],
            'digital_assets.*.technical_metadata' => ['sometimes', 'nullable', 'array'],
            'digital_assets.*.captured_at' => ['sometimes', 'nullable', 'date'],
            'digital_assets.*.photographer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'digital_assets.*.view_angle' => ['sometimes', 'nullable', 'string', 'max:80'],
            'digital_assets.*.caption' => ['sometimes', 'nullable', 'string'],
            'digital_assets.*.is_primary' => ['sometimes', 'boolean'],
            'digital_assets.*.is_public' => ['sometimes', 'boolean'],
            'digital_assets.*.access_level' => ['sometimes', Rule::in(['public', 'member', 'internal', 'restricted'])],
            'digital_assets.*.watermark_applied' => ['sometimes', 'boolean'],
            'digital_assets.*.sort_order' => ['sometimes', 'integer'],
        ];

        return Validator::make($data, $rules)->validate();
    }
}