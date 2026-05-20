<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\ConditionReport;
use App\Models\Creator;
use App\Models\DigitalAsset;
use App\Models\ItemMetadata;
use App\Models\Material;
use App\Models\MetadataElement;
use App\Models\MuseumItem;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MuseumCollectionService
{
    public function __construct(
        private readonly CollectionService $collectionService,
        private readonly AuditLogService $auditLogService,
        private readonly CollectionVersionService $collectionVersionService,
        private readonly CollectionMetadataAutoSyncService $autoSyncService
    ) {
    }

    /**
     * Membuat koleksi museum lengkap secara transaksional.
     *
     * Struktur $data:
     * [
     *   'collection' => [...],
     *   'museum_item' => [...],
     *   'materials' => [[...]],
     *   'creators' => [[...]],
     *   'subjects' => [[...]],
     *   'metadata' => [[...]], // opsional metadata tambahan
     *   'digital_assets' => [[...]],
     *   'condition_reports' => [[...]]
     * ]
     *
     * @throws ValidationException
     */
    public function create(array $data, ?User $actor = null, ?string $reason = 'Create museum collection'): Collection
    {
        $validated = $this->validatePayload($data);

        return DB::transaction(function () use ($validated, $actor, $reason) {
            $collectionData = $validated['collection'];
            $collectionData['unit_type'] = 'museum';
            $collectionData['publication_status'] = $collectionData['publication_status'] ?? 'draft';
            $collectionData['visibility'] = $collectionData['visibility'] ?? 'public';

            $collection = $this->collectionService->createBase(
                data: $collectionData,
                actor: $actor,
                reason: $reason
            );

            $museumItem = $this->createMuseumItem(
                collection: $collection,
                data: $validated['museum_item']
            );

            $materials = $this->syncMaterials(
                museumItem: $museumItem,
                materials: $validated['materials'] ?? []
            );

            $creators = $this->syncCreators(
                collection: $collection,
                creators: $validated['creators'] ?? []
            );

            $subjects = $this->syncSubjects(
                collection: $collection,
                subjects: $validated['subjects'] ?? []
            );

            $assets = $this->createDigitalAssets(
                collection: $collection,
                assets: $validated['digital_assets'] ?? [],
                actor: $actor
            );

            $this->createConditionReports(
                museumItem: $museumItem,
                reports: $validated['condition_reports'] ?? [],
                assets: $assets,
                actor: $actor
            );

            $this->autoSyncService->sync($collection, $actor);

            $this->createAdditionalMetadata(
                collection: $collection,
                metadataRows: $validated['metadata'] ?? [],
                actor: $actor
            );

            $fresh = $collection->fresh([
                'museumItem',
                'museumItem.materials',
                'museumItem.conditionReports',
                'creators',
                'subjects',
                'metadata',
                'digitalAssets',
            ]);

            $this->collectionVersionService->record(
                collection: $fresh,
                actor: $actor,
                reason: 'Museum details, materials, metadata, condition report, and assets attached',
                diff: [
                    'old' => null,
                    'new' => [
                        'museum_item_id' => $museumItem->id,
                        'materials_count' => $museumItem->materials()->count(),
                        'creators_count' => $fresh->creators()->count(),
                        'subjects_count' => $fresh->subjects()->count(),
                        'metadata_count' => $fresh->metadata()->count(),
                        'digital_assets_count' => $fresh->digitalAssets()->count(),
                        'condition_reports_count' => $museumItem->conditionReports()->count(),
                    ],
                ]
            );

            $this->auditLogService->record(
                module: 'museum',
                action: 'create',
                event: 'museum_collection.created',
                actor: $actor,
                auditable: $fresh,
                oldValues: null,
                newValues: [
                    'record_code' => $fresh->record_code,
                    'title' => $fresh->title,
                    'museum_item_id' => $museumItem->id,
                    'inventory_number' => $museumItem->inventory_number,
                    'materials_count' => $museumItem->materials()->count(),
                    'metadata_count' => $fresh->metadata()->count(),
                    'digital_assets_count' => $fresh->digitalAssets()->count(),
                    'condition_reports_count' => $museumItem->conditionReports()->count(),
                ],
                metadata: [
                    'reason' => $reason,
                    'collection_type' => $fresh->collection_type,
                    'classification' => $museumItem->classification,
                ]
            );

            return $fresh;
        });
    }

    public function update(Collection $collection, array $data, ?User $actor = null, ?string $reason = 'Update museum collection'): Collection
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

            if (isset($validated['museum_item'])) {
                $museumItem = $collection->museumItem;
                if ($museumItem) {
                    $museumItem->update($validated['museum_item']);
                } else {
                    $museumItem = $this->createMuseumItem($collection, $validated['museum_item']);
                }
            }

            if (isset($validated['materials'])) {
                if ($collection->museumItem) {
                    $collection->museumItem->materials()->detach();
                    $this->syncMaterials($collection->museumItem, $validated['materials']);
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
                'museumItem',
                'museumItem.materials',
                'museumItem.conditionReports',
                'creators',
                'subjects',
                'metadata',
                'digitalAssets',
            ]);

            $this->auditLogService->record(
                module: 'museum',
                action: 'update',
                event: 'museum_collection.updated_from_staff_form',
                actor: $actor,
                auditable: $fresh,
                oldValues: null,
                newValues: [
                    'record_code' => $fresh->record_code,
                    'title' => $fresh->title,
                    'museum_item_id' => $fresh->museumItem?->id,
                ],
                metadata: [
                    'reason' => $reason,
                ]
            );

            return $fresh;
        });
    }

    private function createMuseumItem(Collection $collection, array $data): MuseumItem
    {
        return MuseumItem::create([
            'collection_id' => $collection->id,
            'inventory_number' => $data['inventory_number'],
            'object_name' => $data['object_name'],
            'object_type_label' => $data['object_type_label'],
            'object_type_uri' => $data['object_type_uri'] ?? null,
            'classification' => $data['classification'],
            'maker_name' => $data['maker_name'] ?? null,
            'maker_uri' => $data['maker_uri'] ?? null,
            'culture' => $data['culture'] ?? null,
            'period_display' => $data['period_display'] ?? null,
            'made_year_start' => $data['made_year_start'] ?? null,
            'made_year_end' => $data['made_year_end'] ?? null,
            'material_summary' => $data['material_summary'] ?? null,
            'technique_summary' => $data['technique_summary'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'width_cm' => $data['width_cm'] ?? null,
            'length_depth_cm' => $data['length_depth_cm'] ?? null,
            'weight_gram' => $data['weight_gram'] ?? null,
            'condition_current' => $data['condition_current'] ?? 'good',
            'condition_checked_at' => $data['condition_checked_at'] ?? null,
            'condition_notes' => $data['condition_notes'] ?? null,
            'provenance_history' => $data['provenance_history'] ?? null,
            'acquisition_method' => $data['acquisition_method'] ?? null,
            'acquisition_source' => $data['acquisition_source'] ?? null,
            'acquisition_date' => $data['acquisition_date'] ?? null,
            'is_sensitive' => $data['is_sensitive'] ?? false,
        ]);
    }

    private function syncMaterials(MuseumItem $museumItem, array $materials): array
    {
        $resolved = [];

        foreach ($materials as $index => $materialData) {
            $name = trim($materialData['name']);
            $type = $materialData['type'];

            $material = Material::firstOrCreate(
                [
                    'name' => $name,
                    'type' => $type,
                ],
                [
                    'name_en' => $materialData['name_en'] ?? null,
                    'authority_uri' => $materialData['authority_uri'] ?? null,
                    'is_active' => true,
                ]
            );

            $museumItem->materials()->attach($material->id, [
                'is_primary' => $materialData['is_primary'] ?? ($index === 0),
            ]);

            $resolved[] = [
                'model' => $material,
                'type' => $type,
                'is_primary' => $materialData['is_primary'] ?? ($index === 0),
            ];
        }

        return $resolved;
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
                'role' => $creatorData['role'] ?? 'maker',
                'sort_order' => $creatorData['sort_order'] ?? ($index + 1),
                'is_primary' => $creatorData['is_primary'] ?? ($index === 0),
                'notes' => $creatorData['notes'] ?? null,
            ]);

            $resolved[] = [
                'model' => $creator,
                'role' => $creatorData['role'] ?? 'maker',
                'sort_order' => $creatorData['sort_order'] ?? ($index + 1),
                'is_primary' => $creatorData['is_primary'] ?? ($index === 0),
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

    private function createDigitalAssets(Collection $collection, array $assets, ?User $actor): array
    {
        $created = [];

        foreach ($assets as $asset) {
            $path = $asset['path'];
            $filename = $asset['filename'] ?? basename($path);
            $extension = $asset['extension'] ?? pathinfo($filename, PATHINFO_EXTENSION);

            $createdAsset = DigitalAsset::create([
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

            $created[] = $createdAsset;
        }

        return $created;
    }

    private function createConditionReports(MuseumItem $museumItem, array $reports, array $assets, ?User $actor): void
    {
        $primaryAsset = collect($assets)->firstWhere('is_primary', true) ?? ($assets[0] ?? null);

        foreach ($reports as $report) {
            $asset = null;

            if (isset($report['asset_index']) && isset($assets[$report['asset_index']])) {
                $asset = $assets[$report['asset_index']];
            } elseif (isset($report['asset_id'])) {
                $asset = DigitalAsset::find($report['asset_id']);
            } else {
                $asset = $primaryAsset;
            }

            ConditionReport::create([
                'museum_item_id' => $museumItem->id,
                'condition_grade' => $report['condition_grade'],
                'inspected_by' => $report['inspected_by'] ?? $actor?->id,
                'inspected_at' => $report['inspected_at'],
                'description' => $report['description'] ?? null,
                'recommendation' => $report['recommendation'] ?? null,
                'priority' => $report['priority'] ?? 'normal',
                'next_review_at' => $report['next_review_at'] ?? null,
                'asset_id' => $asset?->id,
            ]);
        }
    }

    private function createAutomaticMetadata(
        Collection $collection,
        MuseumItem $museumItem,
        array $creators,
        array $subjects,
        array $materials,
        ?User $actor
    ): void {
        $this->metadata($collection, 'dc.title', $collection->title, 0, 'auto_dc_mapping', $actor);

        foreach ($creators as $index => $creator) {
            $this->metadata($collection, 'dc.creator', $creator['model']->name, $index, 'auto_dc_mapping', $actor);
        }

        foreach ($subjects as $index => $subject) {
            $this->metadata($collection, 'dc.subject', $subject['model']->term, $index, 'auto_dc_mapping', $actor);
        }

        if ($collection->description) {
            $this->metadata($collection, 'dc.description', $collection->description, 0, 'auto_dc_mapping', $actor);
        }

        if ($collection->date_display) {
            $this->metadata($collection, 'dc.date', $collection->date_display, 0, 'auto_dc_mapping', $actor);
        }

        $this->metadata($collection, 'dc.type', $collection->collection_type, 0, 'auto_dc_mapping', $actor);
        $this->metadata($collection, 'dc.identifier', $museumItem->inventory_number, 0, 'auto_dc_mapping', $actor);

        if ($collection->language_code) {
            $this->metadata($collection, 'dc.language', $collection->language_code, 0, 'auto_dc_mapping', $actor);
        }

        if ($collection->rights_status) {
            $this->metadata($collection, 'dc.rights', $collection->rights_status, 0, 'auto_dc_mapping', $actor);
        }

        $primaryCreator = collect($creators)->firstWhere('is_primary', true) ?? $creators[0] ?? null;

        $this->metadata($collection, 'cdwa.object.workType', $museumItem->object_type_label, 0, 'auto_cdwa_mapping', $actor);
        $this->metadata($collection, 'cdwa.title', $collection->title, 0, 'auto_cdwa_mapping', $actor);

        if ($primaryCreator) {
            $this->metadata($collection, 'cdwa.creator', $primaryCreator['model']->name, 0, 'auto_cdwa_mapping', $actor);
        }

        $measurements = $this->formatMeasurements($museumItem);

        if ($measurements) {
            $this->metadata($collection, 'cdwa.measurements', $measurements, 0, 'auto_cdwa_mapping', $actor);
        }

        foreach ($materials as $index => $material) {
            if ($material['type'] === 'material') {
                $this->metadata(
                    $collection,
                    'cdwa.material.medium',
                    $material['model']->name,
                    $index,
                    'auto_cdwa_mapping',
                    $actor,
                    authorityUri: $material['model']->authority_uri
                );
            }
        }

        $techniqueIndex = 0;

        foreach ($materials as $material) {
            if ($material['type'] === 'technique') {
                $this->metadata(
                    $collection,
                    'cdwa.technique',
                    $material['model']->name,
                    $techniqueIndex,
                    'auto_cdwa_mapping',
                    $actor,
                    authorityUri: $material['model']->authority_uri
                );

                $techniqueIndex++;
            }
        }

        if ($museumItem->period_display) {
            $this->metadata($collection, 'cdwa.displayCreationDate', $museumItem->period_display, 0, 'auto_cdwa_mapping', $actor);
        }

        if ($collection->currentLocation) {
            $this->metadata($collection, 'cdwa.location', $collection->currentLocation->name, 0, 'auto_cdwa_mapping', $actor);
        }

        if ($museumItem->provenance_history) {
            $this->metadata($collection, 'cdwa.provenance', $museumItem->provenance_history, 0, 'auto_cdwa_mapping', $actor);
        }

        if ($museumItem->condition_notes || $museumItem->condition_current) {
            $this->metadata(
                $collection,
                'cdwa.condition',
                trim($museumItem->condition_current . '; ' . ($museumItem->condition_notes ?? '')),
                0,
                'auto_cdwa_mapping',
                $actor
            );
        }

        if (in_array($collection->collection_type, ['historical_photo', 'artifact', 'archive_document'], true)) {
            $this->metadata($collection, 'vra.workType', $museumItem->object_type_label, 0, 'auto_vra_mapping', $actor);
            $this->metadata($collection, 'vra.title', $collection->title, 0, 'auto_vra_mapping', $actor);

            foreach ($creators as $index => $creator) {
                $this->metadata($collection, 'vra.agent', $creator['model']->name, $index, 'auto_vra_mapping', $actor);
            }

            foreach ($materials as $index => $material) {
                $this->metadata(
                    $collection,
                    'vra.material',
                    $material['model']->name,
                    $index,
                    'auto_vra_mapping',
                    $actor,
                    authorityUri: $material['model']->authority_uri
                );
            }

            if ($measurements) {
                $this->metadata($collection, 'vra.measurements', $measurements, 0, 'auto_vra_mapping', $actor);
            }

            if ($museumItem->period_display) {
                $this->metadata($collection, 'vra.date', $museumItem->period_display, 0, 'auto_vra_mapping', $actor);
            }

            if ($collection->currentLocation) {
                $this->metadata($collection, 'vra.location', $collection->currentLocation->name, 0, 'auto_vra_mapping', $actor);
            }
        }

        if ($collection->collection_type === 'archive_document') {
            $this->metadata($collection, 'isad.reference_code', $museumItem->inventory_number, 0, 'auto_isad_mapping', $actor);
            $this->metadata($collection, 'isad.title', $collection->title, 0, 'auto_isad_mapping', $actor);

            if ($collection->date_display) {
                $this->metadata($collection, 'isad.date', $collection->date_display, 0, 'auto_isad_mapping', $actor);
            }

            $this->metadata($collection, 'isad.level_of_description', 'item', 0, 'auto_isad_mapping', $actor);

            if ($museumItem->material_summary) {
                $this->metadata($collection, 'isad.extent_medium', $museumItem->material_summary, 0, 'auto_isad_mapping', $actor);
            }

            if ($primaryCreator) {
                $this->metadata($collection, 'isad.creator_name', $primaryCreator['model']->name, 0, 'auto_isad_mapping', $actor);
            }

            if ($museumItem->provenance_history) {
                $this->metadata($collection, 'isad.custodial_history', $museumItem->provenance_history, 0, 'auto_isad_mapping', $actor);
            }

            if ($collection->description) {
                $this->metadata($collection, 'isad.scope_content', $collection->description, 0, 'auto_isad_mapping', $actor);
            }

            if ($collection->visibility !== 'public') {
                $this->metadata($collection, 'isad.access_conditions', $collection->visibility, 0, 'auto_isad_mapping', $actor);
            }

            if ($museumItem->condition_notes) {
                $this->metadata($collection, 'isad.physical_condition', $museumItem->condition_notes, 0, 'auto_isad_mapping', $actor);
            }
        }
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

    private function formatMeasurements(MuseumItem $museumItem): ?string
    {
        $parts = [];

        if ($museumItem->height_cm !== null) {
            $parts[] = 'tinggi ' . $museumItem->height_cm . ' cm';
        }

        if ($museumItem->width_cm !== null) {
            $parts[] = 'lebar ' . $museumItem->width_cm . ' cm';
        }

        if ($museumItem->length_depth_cm !== null) {
            $parts[] = 'panjang/kedalaman ' . $museumItem->length_depth_cm . ' cm';
        }

        if ($museumItem->weight_gram !== null) {
            $parts[] = 'berat ' . $museumItem->weight_gram . ' gram';
        }

        return empty($parts) ? null : implode('; ', $parts);
    }

    /**
     * @throws ValidationException
     */
    private function validatePayload(array $data, ?int $collectionId = null): array
    {
        $museumItemId = null;
        if ($collectionId) {
            $museumItemId = MuseumItem::where('collection_id', $collectionId)->value('id');
        }

        $rules = [
            'collection' => ['required', 'array'],
            'collection.record_code' => [
                'required', 
                'string', 
                'max:80', 
                Rule::unique('collections', 'record_code')->ignore($collectionId)
            ],
            'collection.collection_type' => [
                'required',
                Rule::in(['artifact', 'historical_photo', 'archive_document', 'multimedia']),
            ],
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

            'museum_item' => ['required', 'array'],
            'museum_item.inventory_number' => [
                'required', 
                'string', 
                'max:100', 
                Rule::unique('museum_items', 'inventory_number')->ignore($museumItemId)
            ],
            'museum_item.object_name' => ['required', 'string', 'max:255'],
            'museum_item.object_type_label' => ['required', 'string', 'max:160'],
            'museum_item.object_type_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'museum_item.classification' => ['required', 'string', 'max:120'],
            'museum_item.maker_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'museum_item.maker_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'museum_item.culture' => ['sometimes', 'nullable', 'string', 'max:160'],
            'museum_item.period_display' => ['sometimes', 'nullable', 'string', 'max:160'],
            'museum_item.made_year_start' => ['sometimes', 'nullable', 'integer'],
            'museum_item.made_year_end' => ['sometimes', 'nullable', 'integer'],
            'museum_item.material_summary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'museum_item.technique_summary' => ['sometimes', 'nullable', 'string', 'max:500'],
            'museum_item.height_cm' => ['sometimes', 'nullable', 'numeric'],
            'museum_item.width_cm' => ['sometimes', 'nullable', 'numeric'],
            'museum_item.length_depth_cm' => ['sometimes', 'nullable', 'numeric'],
            'museum_item.weight_gram' => ['sometimes', 'nullable', 'numeric'],
            'museum_item.condition_current' => [
                'sometimes',
                Rule::in(['excellent', 'good', 'fair', 'poor', 'critical']),
            ],
            'museum_item.condition_checked_at' => ['sometimes', 'nullable', 'date'],
            'museum_item.condition_notes' => ['sometimes', 'nullable', 'string'],
            'museum_item.provenance_history' => ['sometimes', 'nullable', 'string'],
            'museum_item.acquisition_method' => ['sometimes', 'nullable', 'string', 'max:120'],
            'museum_item.acquisition_source' => ['sometimes', 'nullable', 'string', 'max:255'],
            'museum_item.acquisition_date' => ['sometimes', 'nullable', 'date'],
            'museum_item.is_sensitive' => ['sometimes', 'boolean'],

            'materials' => ['sometimes', 'array'],
            'materials.*.name' => ['required_with:materials', 'string', 'max:200'],
            'materials.*.name_en' => ['sometimes', 'nullable', 'string', 'max:200'],
            'materials.*.authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'materials.*.type' => ['required_with:materials', Rule::in(['material', 'technique'])],
            'materials.*.is_primary' => ['sometimes', 'boolean'],

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

            'condition_reports' => ['sometimes', 'array'],
            'condition_reports.*.condition_grade' => [
                'required_with:condition_reports',
                Rule::in(['excellent', 'good', 'fair', 'poor', 'critical']),
            ],
            'condition_reports.*.inspected_by' => ['sometimes', 'nullable', 'exists:users,id'],
            'condition_reports.*.inspected_at' => ['required_with:condition_reports', 'date'],
            'condition_reports.*.description' => ['sometimes', 'nullable', 'string'],
            'condition_reports.*.recommendation' => ['sometimes', 'nullable', 'string'],
            'condition_reports.*.priority' => ['sometimes', Rule::in(['normal', 'maintenance', 'urgent'])],
            'condition_reports.*.next_review_at' => ['sometimes', 'nullable', 'date'],
            'condition_reports.*.asset_id' => ['sometimes', 'nullable', 'exists:digital_assets,id'],
            'condition_reports.*.asset_index' => ['sometimes', 'nullable', 'integer'],
        ];

        return Validator::make($data, $rules)->validate();
    }
}