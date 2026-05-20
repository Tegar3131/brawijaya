<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\ItemMetadata;
use App\Models\MetadataElement;
use App\Models\User;

class CollectionMetadataAutoSyncService
{
    /**
     * @var \Illuminate\Database\Eloquent\Collection|MetadataElement[]
     */
    private $elementsCache;

    public function __construct()
    {
        $this->elementsCache = MetadataElement::all()->keyBy('element_key');
    }

    public function sync(Collection $collection, ?User $actor = null): void
    {
        $collection->loadMissing([
            'libraryItem',
            'museumItem',
            'creators',
            'subjects'
        ]);

        $syncData = [];

        // Base Collection
        $this->add($syncData, 'dc.title', $collection->title);
        $this->add($syncData, 'dc.description', $collection->description);
        $this->add($syncData, 'dc.language', $collection->language_code);
        $this->add($syncData, 'dc.rights', $collection->rights_status);
        $this->add($syncData, 'dc.date', $collection->date_display);
        $this->add($syncData, 'dc.type', $collection->collection_type);
        
        $identifier = $collection->record_code;
        if ($collection->libraryItem) {
            $identifier = $collection->libraryItem->isbn13 
                ?? $collection->libraryItem->isbn10 
                ?? $collection->libraryItem->issn 
                ?? $collection->libraryItem->doi 
                ?? $collection->record_code;
        }
        $this->add($syncData, 'dc.identifier', $identifier);

        // Creators
        foreach ($collection->creators as $index => $creator) {
            $this->add($syncData, 'dc.creator', $creator->name, $creator->pivot->sort_order ?? ($index + 1));
            
            if ($collection->libraryItem) {
                $this->add($syncData, 'mods.name.namePart', $creator->name, $creator->pivot->sort_order ?? ($index + 1));
            }
        }

        // Subjects
        foreach ($collection->subjects as $index => $subject) {
            $this->add($syncData, 'dc.subject', $subject->term, $subject->pivot->sort_order ?? ($index + 1));
            
            if ($collection->libraryItem) {
                $this->add($syncData, 'marc.650.a', $subject->term, $subject->pivot->sort_order ?? ($index + 1));
            }
        }

        // Library Items
        if ($collection->libraryItem) {
            $item = $collection->libraryItem;
            
            if ($item->publisher_name) {
                $this->add($syncData, 'dc.publisher', $item->publisher_name);
                $this->add($syncData, 'marc.260.b', $item->publisher_name);
            }
            if ($item->isbn13) {
                $this->add($syncData, 'marc.020.a', $item->isbn13);
            }
            if ($item->issn) {
                $this->add($syncData, 'marc.022.a', $item->issn);
            }
            if ($item->ddc_classification) {
                $this->add($syncData, 'marc.082.a', $item->ddc_classification);
            }
            if ($item->call_number) {
                $this->add($syncData, 'marc.090.a', $item->call_number);
            }
            
            $primaryCreator = $collection->creators->firstWhere('pivot.is_primary', true) ?? $collection->creators->first();
            if ($primaryCreator) {
                $this->add($syncData, 'marc.100.a', $primaryCreator->name);
            }

            $this->add($syncData, 'marc.245.a', $collection->title);
            if ($collection->subtitle) {
                $this->add($syncData, 'marc.245.b', $collection->subtitle);
            }
            if ($item->publisher_place) {
                $this->add($syncData, 'marc.260.a', $item->publisher_place);
            }
            if ($item->publication_year) {
                $this->add($syncData, 'marc.260.c', (string) $item->publication_year);
                $this->add($syncData, 'mods.originInfo.dateIssued', (string) $item->publication_year);
            }
            if ($item->physical_extent || $item->pages) {
                $extent = $item->physical_extent ?: ($item->pages ? $item->pages . ' pages' : null);
                if ($extent) {
                    $this->add($syncData, 'marc.300.a', $extent);
                }
            }
            
            $this->add($syncData, 'mods.titleInfo.title', $collection->title);
            $this->add($syncData, 'mods.genre', $item->bibliographic_level);
            if ($collection->description) {
                $this->add($syncData, 'mods.abstract', $collection->description);
            }
            $this->add($syncData, 'mods.identifier', $identifier);
        }

        // Museum Items
        if ($collection->museumItem) {
            $item = $collection->museumItem;
            
            if ($item->object_name) {
                $this->add($syncData, 'cdwa.object.work.type', $item->object_name);
            }
            $this->add($syncData, 'cdwa.title', $collection->title);
            
            if ($item->classification) {
                $this->add($syncData, 'cdwa.classification', $item->classification);
            }
            if ($item->material_summary) {
                $this->add($syncData, 'cdwa.material.medium', $item->material_summary);
            }
            if ($item->dimensions_display) {
                $this->add($syncData, 'cdwa.measurements', $item->dimensions_display);
            }
            if ($item->provenance_history) {
                $this->add($syncData, 'cdwa.description', $item->provenance_history);
            }
        }

        // Apply syncing
        $this->applySync($collection, $syncData, $actor);
    }

    private function add(array &$syncData, string $elementKey, mixed $value, int $sortOrder = 0): void
    {
        if (empty($value) && $value !== '0' && $value !== 0) return;
        
        if (!isset($syncData[$elementKey])) {
            $syncData[$elementKey] = [];
        }
        $syncData[$elementKey][] = [
            'value' => $value,
            'sort_order' => $sortOrder
        ];
    }

    private function applySync(Collection $collection, array $syncData, ?User $actor): void
    {
        $managedKeys = array_keys($syncData);
        
        $coreKeys = [
            'dc.title', 'dc.description', 'dc.language', 'dc.rights', 'dc.date', 'dc.type', 'dc.identifier',
            'dc.creator', 'dc.subject', 'dc.publisher',
            'marc.020.a', 'marc.022.a', 'marc.082.a', 'marc.090.a', 'marc.100.a', 'marc.245.a', 'marc.245.b',
            'marc.260.a', 'marc.260.b', 'marc.260.c', 'marc.300.a', 'marc.650.a',
            'mods.titleInfo.title', 'mods.name.namePart', 'mods.genre', 'mods.originInfo.dateIssued', 'mods.abstract', 'mods.identifier',
            'cdwa.object.work.type', 'cdwa.title', 'cdwa.classification', 'cdwa.material.medium', 'cdwa.measurements', 'cdwa.description'
        ];
        
        $keysToProcess = array_unique(array_merge($managedKeys, $coreKeys));
        
        // Find existing auto_sync metadata for these keys
        $existingAutoSync = ItemMetadata::where('collection_id', $collection->id)
            ->whereIn('source', ['auto_sync', 'auto_dc_mapping', 'auto_marc_mapping', 'auto_mods_mapping'])
            ->whereHas('metadataElement', function ($q) use ($keysToProcess) {
                $q->whereIn('element_key', $keysToProcess);
            })
            ->get();
            
        // Delete old auto_sync metadata
        foreach ($existingAutoSync as $meta) {
            $meta->delete();
        }

        // Recreate from syncData
        foreach ($syncData as $elementKey => $values) {
            $element = $this->elementsCache->get($elementKey);
            
            if (!$element) continue;
            
            foreach ($values as $index => $item) {
                $valueColumn = $this->valueColumnFor($element, $item['value']);
                
                $payload = [
                    'collection_id' => $collection->id,
                    'metadata_element_id' => $element->id,
                    'is_repeatable_field' => $element->is_repeatable,
                    'language_code' => $collection->language_code,
                    'source' => 'auto_sync',
                    'sort_order' => $item['sort_order'] > 0 ? $item['sort_order'] : $index,
                    'created_by' => $actor?->id,
                    $valueColumn => $item['value'],
                ];
                
                ItemMetadata::create($payload);
            }
        }
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
}
