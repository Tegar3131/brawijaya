<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CollectionImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StaffCollectionImportController extends Controller
{
    public function __construct(
        private readonly CollectionImportService $importService,
    ) {}

    // ------------------------------------------------------------------ //
    // Download template XLSX
    // ------------------------------------------------------------------ //

    public function template(Request $request, string $unitType): BinaryFileResponse
    {
        $staff = $this->authorizeStaff($request, $unitType);

        if (! in_array($unitType, ['library', 'museum'], true)) {
            abort(400, 'Unit type tidak valid. Gunakan "library" atau "museum".');
        }

        $filePath = $this->importService->generateTemplate($unitType);
        $fileName = 'template_import_' . $unitType . '.xlsx';

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ------------------------------------------------------------------ //
    // Preview — parse & validasi tanpa menyimpan
    // ------------------------------------------------------------------ //

    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file'      => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
            'unit_type' => ['required', 'in:library,museum'],
        ]);

        $unitType = $request->input('unit_type');
        $staff    = $this->authorizeStaff($request, $unitType);

        $parsed = $this->importService->parseFile($request->file('file'), $unitType);

        if ($parsed['parse_error']) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => $parsed['parse_error'], 'code' => 'PARSE_ERROR'],
            ], 422);
        }

        if (empty($parsed['rows'])) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'File tidak memiliki data (kosong atau hanya header).', 'code' => 'EMPTY_FILE'],
            ], 422);
        }

        if (count($parsed['rows']) > 500) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Maksimum 500 baris per upload. File Anda memiliki ' . count($parsed['rows']) . ' baris.', 'code' => 'TOO_MANY_ROWS'],
            ], 422);
        }

        $validationResults = $this->importService->validateRows($parsed['rows'], $unitType);

        $totalValid   = count(array_filter($validationResults, fn($r) => $r['valid']));
        $totalInvalid = count($validationResults) - $totalValid;

        return response()->json([
            'success' => true,
            'data'    => [
                'unit_type'     => $unitType,
                'total_rows'    => count($validationResults),
                'total_valid'   => $totalValid,
                'total_invalid' => $totalInvalid,
                'rows'          => array_map(fn($r) => [
                    'row'    => $r['row'],
                    'valid'  => $r['valid'],
                    'errors' => $r['errors'],
                    'data'   => [
                        'record_code' => $r['data']['record_code'] ?? '-',
                        'title'       => $r['data']['title']       ?? '-',
                        'creators'    => $r['data']['creators']    ?? '-',
                    ],
                ], $validationResults),
            ],
        ]);
    }

    // ------------------------------------------------------------------ //
    // Execute — jalankan import sesungguhnya
    // ------------------------------------------------------------------ //

    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'file'      => ['required', 'file', 'mimes:csv,xlsx,xls', 'max:5120'],
            'unit_type' => ['required', 'in:library,museum'],
        ]);

        $unitType = $request->input('unit_type');
        $staff    = $this->authorizeStaff($request, $unitType);

        $parsed = $this->importService->parseFile($request->file('file'), $unitType);

        if ($parsed['parse_error']) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => $parsed['parse_error'], 'code' => 'PARSE_ERROR'],
            ], 422);
        }

        if (empty($parsed['rows'])) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'File tidak memiliki data.', 'code' => 'EMPTY_FILE'],
            ], 422);
        }

        if (count($parsed['rows']) > 500) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => 'Maksimum 500 baris per upload.', 'code' => 'TOO_MANY_ROWS'],
            ], 422);
        }

        $validationResults = $this->importService->validateRows($parsed['rows'], $unitType);
        $importResults     = $this->importService->executeImport($validationResults, $unitType, $staff);

        $totalSuccess = count(array_filter($importResults, fn($r) => $r['status'] === 'success'));
        $totalError   = count(array_filter($importResults, fn($r) => $r['status'] === 'error'));
        $totalSkipped = count(array_filter($importResults, fn($r) => $r['status'] === 'skipped'));

        return response()->json([
            'success' => true,
            'data'    => [
                'unit_type'     => $unitType,
                'total_rows'    => count($importResults),
                'total_success' => $totalSuccess,
                'total_error'   => $totalError,
                'total_skipped' => $totalSkipped,
                'rows'          => $importResults,
            ],
            'message' => "Import selesai: {$totalSuccess} berhasil, {$totalError} gagal, {$totalSkipped} dilewati.",
        ]);
    }

    // ------------------------------------------------------------------ //
    // Authorization helper
    // ------------------------------------------------------------------ //

    private function authorizeStaff(Request $request, string $unitType): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');

        if ($user->hasRole('admin')) {
            return $user;
        }

        if ($unitType === 'library' && $user->hasRole('pustakawan')) {
            return $user;
        }

        if ($unitType === 'museum' && $user->hasRole('kurator')) {
            return $user;
        }

        abort(403, 'Anda tidak memiliki akses untuk mengimpor koleksi unit ini.');
    }
}
