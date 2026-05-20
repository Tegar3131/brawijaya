<?php

namespace App\Services;

use App\Models\DigitalAsset;
use App\Models\User;
use RuntimeException;

class DigitalAssetAccessService
{
    public function assertCanDownload(DigitalAsset $asset, ?User $user): void
    {
        $asset->loadMissing('collection');

        if ($asset->access_level === 'public' && $asset->is_public) {
            return;
        }

        if (! $user) {
            throw new RuntimeException('Unauthenticated.');
        }

        if ($user->hasRole('admin')) {
            return;
        }

        if ($asset->access_level === 'member') {
            if ($user->hasRole('member') && $user->hasActiveMembership()) {
                return;
            }

            if ($this->isStaffForCollection($user, $asset)) {
                return;
            }
        }

        if ($asset->access_level === 'internal') {
            if ($this->isStaffForCollection($user, $asset)) {
                return;
            }
        }

        if ($asset->access_level === 'restricted') {
            throw new RuntimeException('File restricted hanya dapat diakses admin.');
        }

        throw new RuntimeException('Anda tidak memiliki akses ke file ini.');
    }

    private function isStaffForCollection(User $user, DigitalAsset $asset): bool
    {
        $collection = $asset->collection;

        if (! $collection) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('pustakawan') && $collection->unit_type === 'library') {
            return true;
        }

        if ($user->hasRole('kurator') && $collection->unit_type === 'museum') {
            return true;
        }

        return false;
    }
}