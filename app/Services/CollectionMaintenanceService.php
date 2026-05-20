<?php

namespace App\Services;

use App\Models\Collection as CollectionModel;
use App\Models\Creator;
use App\Models\DigitalAsset;
use App\Models\ItemMetadata;
use App\Models\MetadataElement;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CollectionMaintenanceService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly CollectionVersionService $collectionVersionService
    ) {
    }

    public function upsertMetadataRows(
        CollectionModel $collection,
        array $rows,
        User $actor,
        ?string $reason = 'Update collection metadata'
    ): CollectionModel {
        return DB::transaction(function () use ($collection, $rows, $actor, $reason) {
            $changes = [];

            foreach ($rows as $index => $row) {
                $element = MetadataElement::where('element_key', $row['element_key'])->firstOrFail();

                $sortOrder = $row['sort_order'] ?? $index;
                $valueColumn = $row['value_column'] ?? $this->valueColumnFor($element, $row['value']);

                $old = ItemMetadata::where('collection_id', $collection->id)
                    ->where('metadata_element_id', $element->id)
                    ->where('sort_order', $sortOrder)
                    ->first();

                $payload = [
                    'is_repeatable_field' => $element->is_repeatable,
                    'value_string' => null,
                    'value_text' => null,
                    'value_integer' => null,
                    'value_decimal' => null,
                    'value_date' => null,
                    'value_datetime' => null,
                    'value_json' => null,
                    'language_code' => $row['language_code'] ?? $collection->language_code,
                    'authority_uri' => $row['authority_uri'] ?? null,
                    'source' => $row['source'] ?? 'staff_api',
                    'created_by' => $actor->id,
                ];

                $payload[$valueColumn] = $row['value'];

                $metadata = ItemMetadata::updateOrCreate(
                    [
                        'collection_id' => $collection->id,
                        'metadata_element_id' => $element->id,
                        'sort_order' => $sortOrder,
                    ],
                    $payload
                );

                $changes[] = [
                    'metadata_id' => $metadata->id,
                    'element_key' => $element->element_key,
                    'old' => $old?->toArray(),
                    'new' => $metadata->fresh()->toArray(),
                ];
            }

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => 'metadata_upsert',
                    'changes' => $changes,
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'metadata_upsert',
                event: 'metadata.upserted',
                actor: $actor,
                auditable: $fresh,
                oldValues: null,
                newValues: [
                    'rows_count' => count($changes),
                    'changes' => $changes,
                ],
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $fresh;
        });
    }

    public function deleteMetadata(
        CollectionModel $collection,
        ItemMetadata $metadata,
        User $actor,
        ?string $reason = 'Delete collection metadata'
    ): CollectionModel {
        return DB::transaction(function () use ($collection, $metadata, $actor, $reason) {
            if ((int) $metadata->collection_id !== (int) $collection->id) {
                throw new RuntimeException('Metadata tidak belongs to collection ini.');
            }

            $oldValues = $metadata->load('metadataElement')->toArray();
            $metadata->delete();

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => 'metadata_delete',
                    'old' => $oldValues,
                    'new' => null,
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'metadata_delete',
                event: 'metadata.deleted',
                actor: $actor,
                auditable: $fresh,
                oldValues: $oldValues,
                newValues: null,
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $fresh;
        });
    }

    public function attachCreator(
        CollectionModel $collection,
        array $data,
        User $actor,
        ?string $reason = 'Attach creator to collection'
    ): CollectionModel {
        return DB::transaction(function () use ($collection, $data, $actor, $reason) {
            $name = trim($data['name']);

            $creator = Creator::firstOrCreate(
                ['normalized_name' => Str::lower($name)],
                [
                    'name' => $name,
                    'authority_source' => $data['authority_source'] ?? 'LOCAL',
                    'authority_uri' => $data['authority_uri'] ?? 'local://simpb/creator/' . Str::slug($name),
                    'birth_death_dates' => $data['birth_death_dates'] ?? null,
                    'biography' => $data['biography'] ?? null,
                ]
            );

            $oldPivot = DB::table('collection_creator')
                ->where('collection_id', $collection->id)
                ->where('creator_id', $creator->id)
                ->first();

            $pivot = [
                'role' => $data['role'] ?? 'creator',
                'sort_order' => $data['sort_order'] ?? 1,
                'is_primary' => $data['is_primary'] ?? false,
                'notes' => $data['notes'] ?? null,
                'updated_at' => now(),
            ];

            if ($oldPivot) {
                $collection->creators()->updateExistingPivot($creator->id, $pivot);
                $event = 'creator.updated';
                $action = 'creator_update';
            } else {
                $collection->creators()->attach($creator->id, $pivot + [
                    'created_at' => now(),
                ]);
                $event = 'creator.attached';
                $action = 'creator_attach';
            }

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => $action,
                    'creator_id' => $creator->id,
                    'old' => $oldPivot ? (array) $oldPivot : null,
                    'new' => $pivot,
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: $action,
                event: $event,
                actor: $actor,
                auditable: $fresh,
                oldValues: $oldPivot ? (array) $oldPivot : null,
                newValues: [
                    'creator_id' => $creator->id,
                    'name' => $creator->name,
                    'pivot' => $pivot,
                ],
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $fresh;
        });
    }

    public function detachCreator(
        CollectionModel $collection,
        Creator $creator,
        User $actor,
        ?string $reason = 'Detach creator from collection'
    ): CollectionModel {
        return DB::transaction(function () use ($collection, $creator, $actor, $reason) {
            $oldPivot = DB::table('collection_creator')
                ->where('collection_id', $collection->id)
                ->where('creator_id', $creator->id)
                ->first();

            if (! $oldPivot) {
                throw new RuntimeException('Creator tidak terhubung dengan koleksi ini.');
            }

            $collection->creators()->detach($creator->id);

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => 'creator_detach',
                    'creator_id' => $creator->id,
                    'old' => (array) $oldPivot,
                    'new' => null,
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'creator_detach',
                event: 'creator.detached',
                actor: $actor,
                auditable: $fresh,
                oldValues: (array) $oldPivot,
                newValues: null,
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                    'creator_name' => $creator->name,
                ]
            );

            return $fresh;
        });
    }

    public function attachSubject(
        CollectionModel $collection,
        array $data,
        User $actor,
        ?string $reason = 'Attach subject to collection'
    ): CollectionModel {
        return DB::transaction(function () use ($collection, $data, $actor, $reason) {
            $term = trim($data['term']);
            $source = $data['vocabulary_source'] ?? 'LOCAL';
            $authorityUri = $data['authority_uri'] ?? 'local://simpb/subject/' . Str::slug($term);

            $subject = Subject::firstOrCreate(
                [
                    'vocabulary_source' => $source,
                    'authority_uri' => $authorityUri,
                ],
                [
                    'term' => $term,
                    'slug' => Str::slug($term),
                    'type' => $data['type'] ?? 'topic',
                    'scope_note' => $data['scope_note'] ?? null,
                ]
            );

            $oldPivot = DB::table('collection_subject')
                ->where('collection_id', $collection->id)
                ->where('subject_id', $subject->id)
                ->first();

            $pivot = [
                'subject_type' => $data['subject_type'] ?? 'secondary',
                'sort_order' => $data['sort_order'] ?? 1,
                'updated_at' => now(),
            ];

            if ($oldPivot) {
                $collection->subjects()->updateExistingPivot($subject->id, $pivot);
                $event = 'subject.updated';
                $action = 'subject_update';
            } else {
                $collection->subjects()->attach($subject->id, $pivot + [
                    'created_at' => now(),
                ]);
                $event = 'subject.attached';
                $action = 'subject_attach';
            }

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => $action,
                    'subject_id' => $subject->id,
                    'old' => $oldPivot ? (array) $oldPivot : null,
                    'new' => $pivot,
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: $action,
                event: $event,
                actor: $actor,
                auditable: $fresh,
                oldValues: $oldPivot ? (array) $oldPivot : null,
                newValues: [
                    'subject_id' => $subject->id,
                    'term' => $subject->term,
                    'pivot' => $pivot,
                ],
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $fresh;
        });
    }

    public function detachSubject(
        CollectionModel $collection,
        Subject $subject,
        User $actor,
        ?string $reason = 'Detach subject from collection'
    ): CollectionModel {
        return DB::transaction(function () use ($collection, $subject, $actor, $reason) {
            $oldPivot = DB::table('collection_subject')
                ->where('collection_id', $collection->id)
                ->where('subject_id', $subject->id)
                ->first();

            if (! $oldPivot) {
                throw new RuntimeException('Subject tidak terhubung dengan koleksi ini.');
            }

            $collection->subjects()->detach($subject->id);

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => 'subject_detach',
                    'subject_id' => $subject->id,
                    'old' => (array) $oldPivot,
                    'new' => null,
                ]
            );

            $this->auditLogService->record(
                module: 'collection',
                action: 'subject_detach',
                event: 'subject.detached',
                actor: $actor,
                auditable: $fresh,
                oldValues: (array) $oldPivot,
                newValues: null,
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                    'subject_term' => $subject->term,
                ]
            );

            return $fresh;
        });
    }

    public function registerDigitalAsset(
        CollectionModel $collection,
        array $data,
        User $actor,
        ?string $reason = 'Register digital asset metadata'
    ): DigitalAsset {
        return DB::transaction(function () use ($collection, $data, $actor, $reason) {
            $path = $data['path'];
            $filename = $data['filename'] ?? basename($path);
            $extension = $data['extension'] ?? pathinfo($filename, PATHINFO_EXTENSION);

            $asset = DigitalAsset::create([
                'ulid' => (string) Str::ulid(),
                'collection_id' => $collection->id,
                'uploaded_by' => $data['uploaded_by'] ?? $actor->id,
                'asset_type' => $data['asset_type'],
                'file_role' => $data['file_role'] ?? 'original',
                'disk' => $data['disk'] ?? 'public',
                'path' => $path,
                'public_url' => $data['public_url'] ?? null,
                'thumbnail_path' => $data['thumbnail_path'] ?? null,
                'watermarked_path' => $data['watermarked_path'] ?? null,
                'filename' => $filename,
                'original_filename' => $data['original_filename'] ?? $filename,
                'mime_type' => $data['mime_type'],
                'extension' => $extension,
                'size_bytes' => $data['size_bytes'] ?? 0,
                'checksum_sha256' => $data['checksum_sha256'] ?? null,
                'width_px' => $data['width_px'] ?? null,
                'height_px' => $data['height_px'] ?? null,
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'technical_metadata' => $data['technical_metadata'] ?? null,
                'captured_at' => $data['captured_at'] ?? null,
                'photographer_name' => $data['photographer_name'] ?? null,
                'view_angle' => $data['view_angle'] ?? null,
                'caption' => $data['caption'] ?? null,
                'is_primary' => $data['is_primary'] ?? false,
                'is_public' => $data['is_public'] ?? true,
                'access_level' => $data['access_level'] ?? 'public',
                'watermark_applied' => $data['watermark_applied'] ?? false,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $fresh = $this->freshCollection($collection);

            $this->recordVersion(
                collection: $fresh,
                actor: $actor,
                reason: $reason,
                diff: [
                    'operation' => 'digital_asset_register',
                    'old' => null,
                    'new' => $asset->toArray(),
                ]
            );

            $this->auditLogService->record(
                module: 'digital_asset',
                action: 'register',
                event: 'digital_asset.registered',
                actor: $actor,
                auditable: $fresh,
                oldValues: null,
                newValues: $asset->toArray(),
                metadata: [
                    'reason' => $reason,
                    'record_code' => $fresh->record_code,
                ]
            );

            return $asset->fresh();
        });
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

    private function freshCollection(CollectionModel $collection): CollectionModel
    {
        return CollectionModel::withTrashed()
            ->with([
                'metadata.metadataElement',
                'creators',
                'subjects',
                'digitalAssets',
                'libraryItem',
                'libraryCopies',
                'museumItem.materials',
                'museumItem.conditionReports',
            ])
            ->findOrFail($collection->id);
    }

    private function recordVersion(CollectionModel $collection, User $actor, string $reason, array $diff): void
    {
        $snapshot = [
            'collection' => $collection->toArray(),
            'metadata_count' => $collection->metadata()->count(),
            'creators_count' => $collection->creators()->count(),
            'subjects_count' => $collection->subjects()->count(),
            'digital_assets_count' => $collection->digitalAssets()->count(),
        ];

        $this->collectionVersionService->recordSnapshot(
            collection: $collection,
            snapshot: $snapshot,
            actor: $actor,
            reason: $reason,
            diff: $diff
        );
    }
}