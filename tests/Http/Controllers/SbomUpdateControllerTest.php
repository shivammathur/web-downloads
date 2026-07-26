<?php
declare(strict_types=1);

namespace Http\Controllers;

use App\Helpers\Helpers;
use App\Http\Controllers\SbomUpdateController;
use PHPUnit\Framework\TestCase;

class SbomUpdateControllerTest extends TestCase
{
    private string $buildsDirectory;
    private ?string $originalBuildsDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->buildsDirectory = sys_get_temp_dir() . '/sbom_update_controller_' . uniqid();
        mkdir($this->buildsDirectory, 0755, true);

        $this->originalBuildsDirectory = getenv('BUILDS_DIRECTORY') ?: null;
        putenv('BUILDS_DIRECTORY=' . $this->buildsDirectory);
    }

    protected function tearDown(): void
    {
        putenv($this->originalBuildsDirectory === null ? 'BUILDS_DIRECTORY' : 'BUILDS_DIRECTORY=' . $this->originalBuildsDirectory);
        Helpers::rmdirr($this->buildsDirectory);
        parent::tearDown();
    }

    public function testEnqueuesSbomUpdate(): void
    {
        $payload = [
            'php_version' => '8.2',
            'sbom' => [
                'license' => 'PHP-3.01',
                'components' => [[
                    'name' => 'pcre2lib',
                    'version' => '10.40',
                    'path' => 'ext/pcre/pcre2lib',
                    'license' => 'BSD-3-Clause WITH PCRE2-exception',
                    'purl' => 'pkg:generic/pcre2@10.40',
                ]],
            ],
        ];
        $inputPath = $this->createInputFile($payload);

        (new SbomUpdateController($inputPath))->handle();
        unlink($inputPath);

        $taskFiles = glob($this->buildsDirectory . '/sbom/sbom-update-*.json');
        $this->assertCount(1, $taskFiles);
        $task = json_decode(file_get_contents($taskFiles[0]), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($payload, $task);
    }

    private function createInputFile(array $data): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sbom-update-input-');
        file_put_contents($path, json_encode($data));

        return $path;
    }
}
