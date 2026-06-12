<?php
declare(strict_types=1);

namespace Http\Controllers;

use App\Http\Controllers\SeriesInitController;
use JsonException;
use Override;
use PHPUnit\Framework\TestCase;

class MockSeriesInitController extends SeriesInitController {
    #[Override]
    protected function validate(array $data): bool {
        return isset($data['key']);
    }

    #[Override]
    protected function execute(array $data): void {
        echo "Executed";
    }

    #[Override]
    public function handle(): void
    {
        $data = json_decode(file_get_contents($this->inputPath), true);

        if ($this->validate($data)) {
            $this->execute($data);
        }
    }
}

class SeriesInitControllerTest extends TestCase {
    /**
     * @throws JsonException
     */
    public function testHandleWithValidData() {
        $data = json_encode(["key" => "value"]);
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit');
        file_put_contents($tempFile, $data);
        $controller = new MockSeriesInitController($tempFile);
        $this->expectOutputString("Executed");
        $controller->handle();
        unlink($tempFile);
    }
}
