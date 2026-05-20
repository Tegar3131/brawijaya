<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\CollectionVersion;
use App\Models\User;

class CollectionVersionService
{
    public function record(
        Collection $collection,
        ?User $actor = null,
        ?string $reason = null,
        ?array $diff = null
    ): CollectionVersion {
        return $this->recordSnapshot(
            collection: $collection,
            snapshot: $collection->fresh()?->toArray() ?? $collection->attributesToArray(),
            actor: $actor,
            reason: $reason,
            diff: $diff
        );
    }

    public function recordSnapshot(
        Collection $collection,
        array $snapshot,
        ?User $actor = null,
        ?string $reason = null,
        ?array $diff = null
    ): CollectionVersion {
        $nextVersionNo = ((int) CollectionVersion::where('collection_id', $collection->id)
            ->max('version_no')) + 1;

        return CollectionVersion::create([
            'collection_id' => $collection->id,
            'version_no' => $nextVersionNo,
            'changed_by' => $actor?->id,
            'change_reason' => $reason,
            'snapshot_json' => $snapshot,
            'diff_json' => $diff,
            'created_at' => now(),
        ]);
    }
}