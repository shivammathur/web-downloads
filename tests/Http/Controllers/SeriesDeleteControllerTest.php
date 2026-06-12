<?php
declare(strict_types=1);

namespace Http\Controllers;

use App\Http\Controllers\SeriesDeleteController;
use JsonException;
use Override;
use PHPUnit\Framework\TestCase;

class MockSeriesDeleteController extends SeriesDeleteController {
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

class SeriesDeleteControllerTest extends TestCase {
    /**
     * @throws JsonException
     */
    public function testHandleWithValidData() {
        $data = json_encode(["key" => "value"]);
        $tempFile = tempnam(sys_get_temp_dir(), 'phpunit');
        file_put_contents($tempFile, $data);
        $controller = new MockSeriesDeleteController($tempFile);
        $this->expectOutputString("Executed");
        $controller->handle();
        unlink($tempFile);
    }
}
