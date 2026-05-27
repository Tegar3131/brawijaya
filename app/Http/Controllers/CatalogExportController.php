<?php

namespace App\Http\Controllers;

use App\Models\Collection as CollectionModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CatalogExportController extends Controller
{
    public function exportXml(Request $request, string $identifier)
    {
        $query = CollectionModel::query()
            ->with([
                'category',
                'currentLocation',
                'creators',
                'subjects',
                'metadata.metadataElement',
            ]);

        $user = $request->user();
        if (!$user?->hasAnyRole(['admin', 'pustakawan', 'kurator'])) {
            $query->where('publication_status', 'published');
            if ($user?->hasRole('member')) {
                $query->whereIn('visibility', ['public', 'member']);
            } else {
                $query->where('visibility', 'public');
            }
        }

        $collection = $query
            ->where(function (Builder $where) use ($identifier) {
                $where->where('ulid', $identifier)
                    ->orWhere('record_code', $identifier);
            })
            ->firstOrFail();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><collection></collection>');
        
        $xml->addChild('id', htmlspecialchars((string) $collection->id));
        $xml->addChild('record_code', htmlspecialchars((string) $collection->record_code));
        $xml->addChild('title', htmlspecialchars((string) $collection->title));
        $xml->addChild('unit_type', htmlspecialchars((string) $collection->unit_type));
        $xml->addChild('collection_type', htmlspecialchars((string) $collection->collection_type));
        $xml->addChild('description', htmlspecialchars((string) $collection->description));
        $xml->addChild('date_display', htmlspecialchars((string) $collection->date_display));
        
        if ($collection->category) {
            $category = $xml->addChild('category');
            $category->addChild('name', htmlspecialchars((string) $collection->category->name));
            $category->addChild('type', htmlspecialchars((string) $collection->category->type));
        }

        if ($collection->creators->isNotEmpty()) {
            $creators = $xml->addChild('creators');
            foreach ($collection->creators as $creator) {
                $cNode = $creators->addChild('creator');
                $cNode->addChild('name', htmlspecialchars((string) $creator->name));
                $cNode->addChild('role', htmlspecialchars((string) $creator->pivot->role));
            }
        }

        if ($collection->subjects->isNotEmpty()) {
            $subjects = $xml->addChild('subjects');
            foreach ($collection->subjects as $subject) {
                $sNode = $subjects->addChild('subject');
                $sNode->addChild('term', htmlspecialchars((string) $subject->term));
                $sNode->addChild('type', htmlspecialchars((string) $subject->type));
            }
        }

        if ($collection->metadata->isNotEmpty()) {
            $metadata = $xml->addChild('metadata_list');
            foreach ($collection->metadata as $meta) {
                $mNode = $metadata->addChild('metadata');
                $mNode->addChild('standard', htmlspecialchars((string) $meta->metadataElement?->standard));
                $mNode->addChild('element_key', htmlspecialchars((string) $meta->metadataElement?->element_key));
                $mNode->addChild('label', htmlspecialchars((string) $meta->metadataElement?->label));
                $mNode->addChild('value', htmlspecialchars((string) $meta->display_value));
            }
        }

        $filename = 'metadata-' . ($collection->record_code ?? $collection->ulid) . '.xml';

        return Response::make($xml->asXML(), 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
