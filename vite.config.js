import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/pages/auth/login.js',
                'resources/js/auth/frontendAuth.js',
                'resources/js/pages/dashboard/memberDashboard.js',
                'resources/js/pages/dashboard/staffDashboard.js',
                'resources/js/pages/catalog/catalogIndex.js',
                'resources/js/pages/catalog/catalogShow.js',
                'resources/js/pages/member/bookmarksIndex.js',
                'resources/js/pages/member/reservationsIndex.js',
                'resources/js/pages/member/borrowingsActive.js',
                'resources/js/pages/member/borrowingsHistory.js',
                'resources/js/pages/staff/collectionsIndex.js',
                'resources/js/pages/staff/collectionShow.js',
                'resources/js/pages/staff/collectionAudit.js',
                'resources/js/pages/staff/collectionVersions.js',
                'resources/js/pages/staff/collectionMetadata.js',
                'resources/js/pages/staff/collectionCreatorsSubjects.js',
                'resources/js/pages/staff/collectionAssets.js',
                'resources/js/pages/staff/collectionForm.js',
                'resources/js/pages/staff/collectionImport.js',
            ],
            refresh: true,
        }),
    ],
});