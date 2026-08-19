<?php

namespace Tests\Unit;

use App\Services\SecureLessonDocumentService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SecureLessonDocumentServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'inspire-secure-doc-' . uniqid();
        File::makeDirectory($this->root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'lms' . DIRECTORY_SEPARATOR . 'resources', 0777, true);
        config(['services.crm.storage_root' => $this->root]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);

        parent::tearDown();
    }

    public function test_it_resolves_crm_paths_with_or_without_public_prefix(): void
    {
        $file = $this->root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'lms'
            . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'lesson.pdf';
        File::put($file, '%PDF-test');

        $service = app(SecureLessonDocumentService::class);

        $resolved = realpath($file);

        $this->assertSame($resolved, $service->resolvePath('lms/resources/lesson.pdf'));
        $this->assertSame($resolved, $service->resolvePath('public/lms/resources/lesson.pdf'));
    }

    public function test_it_rejects_missing_and_external_paths(): void
    {
        $service = app(SecureLessonDocumentService::class);

        $this->assertNull($service->resolvePath('lms/resources/missing.pdf'));
        $this->assertNull($service->resolvePath('https://example.test/lesson.pdf'));
    }
}
