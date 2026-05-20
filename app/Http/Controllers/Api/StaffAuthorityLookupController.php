<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Creator;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffAuthorityLookupController extends Controller
{
    public function creators(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $limit = $request->input('limit', 10);
        $q = $request->input('q');

        $query = Creator::query()
            ->select(['id', 'name', 'authority_source', 'authority_uri', 'created_at', 'updated_at'])
            ->orderBy('name');

        if (!empty($q)) {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'LIKE', '%' . $q . '%')
                    ->orWhere('authority_source', 'LIKE', '%' . $q . '%')
                    ->orWhere('authority_uri', 'LIKE', '%' . $q . '%');
            });
        }

        $creators = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $creators,
        ]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $limit = $request->input('limit', 10);
        $q = $request->input('q');

        $query = Subject::query()
            ->select(['id', 'term', 'type as subject_type', 'vocabulary_source as authority_source', 'authority_uri', 'created_at', 'updated_at'])
            ->orderBy('term');

        if (!empty($q)) {
            $query->where(function ($sq) use ($q) {
                $sq->where('term', 'LIKE', '%' . $q . '%')
                    ->orWhere('vocabulary_source', 'LIKE', '%' . $q . '%')
                    ->orWhere('authority_uri', 'LIKE', '%' . $q . '%');
            });
        }

        $subjects = $query->limit($limit)->get();

        return response()->json([
            'success' => true,
            'data' => $subjects,
        ]);
    }
}
