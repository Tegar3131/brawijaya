<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDigitalAssetsTable extends Migration
{
    public function up(): void
    {
        Schema::create('digital_assets', function (Blueprint $table) {
            $table->id();

            $table->ulid('ulid')
                ->unique()
                ->comment('Public sortable identifier untuk aset digital.');

            $table->foreignId('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete()
                ->comment('Koleksi induk pemilik aset digital.');

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('User yang mengunggah aset digital.');

            $table->enum('asset_type', [
                'cover',
                'photo',
                'document',
                'pdf',
                'epub',
                'video',
                'audio',
                'thumbnail',
                'mets_package',
            ])
                ->index()
                ->comment('Jenis aset digital.');

            $table->enum('file_role', [
                'original',
                'access',
                'thumbnail',
                'watermarked',
                'derivative',
            ])
                ->default('original')
                ->index()
                ->comment('Peran file dalam siklus digitalisasi.');

            $table->string('disk', 60)
                ->default('public')
                ->comment('Disk Laravel: local, public, s3, minio.');

            $table->string('path', 1000)
                ->comment('Path file pada disk storage.');

            $table->string('public_url', 1000)
                ->nullable()
                ->comment('URL publik jika file diekspos langsung.');

            $table->string('thumbnail_path', 1000)
                ->nullable()
                ->comment('Path thumbnail jika tersedia.');

            $table->string('watermarked_path', 1000)
                ->nullable()
                ->comment('Path file versi watermark untuk akses publik.');

            $table->string('filename', 255)
                ->comment('Nama file yang disimpan sistem.');

            $table->string('original_filename', 255)
                ->nullable()
                ->comment('Nama file asli saat diunggah.');

            $table->string('mime_type', 120)
                ->index()
                ->comment('MIME type, contoh image/jpeg atau application/pdf.');

            $table->string('extension', 20)
                ->index()
                ->comment('Ekstensi file.');

            $table->unsignedBigInteger('size_bytes')
                ->default(0)
                ->comment('Ukuran file dalam byte.');

            $table->string('checksum_sha256', 64)
                ->nullable()
                ->index()
                ->comment('Checksum SHA-256 untuk validasi integritas file.');

            $table->unsignedInteger('width_px')
                ->nullable()
                ->comment('Lebar gambar/video dalam pixel.');

            $table->unsignedInteger('height_px')
                ->nullable()
                ->comment('Tinggi gambar/video dalam pixel.');

            $table->unsignedInteger('duration_seconds')
                ->nullable()
                ->comment('Durasi video/audio dalam detik.');

            $table->json('technical_metadata')
                ->nullable()
                ->comment('Metadata teknis seperti VRA Core, MIX, PREMIS, EXIF, atau hasil ekstraksi file.');

            $table->date('captured_at')
                ->nullable()
                ->index()
                ->comment('Tanggal pengambilan foto/audio/video jika diketahui.');

            $table->string('photographer_name', 255)
                ->nullable()
                ->comment('Nama fotografer untuk foto artefak atau dokumentasi koleksi.');

            $table->string('view_angle', 80)
                ->nullable()
                ->comment('Sudut pengambilan: front, back, side, detail.');

            $table->text('caption')
                ->nullable();

            $table->boolean('is_primary')
                ->default(false)
                ->index()
                ->comment('True jika menjadi gambar/file utama koleksi.');

            $table->boolean('is_public')
                ->default(true)
                ->index()
                ->comment('True jika aset dapat ditampilkan di portal publik.');

            $table->enum('access_level', [
                'public',
                'member',
                'internal',
                'restricted',
            ])
                ->default('public')
                ->index()
                ->comment('Level akses file digital.');

            $table->boolean('watermark_applied')
                ->default(false)
                ->comment('True jika file publik sudah diberi watermark.');

            $table->unsignedSmallInteger('sort_order')
                ->default(0)
                ->comment('Urutan tampil dalam galeri.');

            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['collection_id', 'asset_type', 'is_primary'],
                'assets_collection_primary_idx'
            );

            $table->index(
                ['collection_id', 'access_level', 'is_public'],
                'assets_collection_access_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_assets');
    }
}