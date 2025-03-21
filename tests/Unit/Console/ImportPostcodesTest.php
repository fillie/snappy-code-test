<?php

namespace Tests\Unit\Console;

use App\Console\Commands\ImportPostcodes;
use App\Repositories\Contracts\PostcodeRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\MockObject\Exception;
use ReflectionException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Console\OutputStyle;
use Tests\TestCase;
use ZipArchive;

class ImportPostcodesTest extends TestCase
{
    /**
     * Helper method to set the command's output.
     */
    protected function setCommandOutput($command): void
    {
        $input = new StringInput('');
        $bufferedOutput = new BufferedOutput();
        $outputStyle = new OutputStyle($input, $bufferedOutput);
        $command->setOutput($outputStyle);
    }

    /**
     * Test that handle() returns an error code when no URL is defined.
     * @throws Exception
     */
    public function testHandleReturnsErrorWhenUrlNotDefined()
    {
        $client = $this->createMock(Client::class);
        $filesystem = $this->createMock(Filesystem::class);
        $postcodeRepositoryMock = $this->createMock(PostcodeRepositoryInterface::class);

        $command = new ImportPostcodes(
            $client,
            $filesystem,
            $postcodeRepositoryMock,
            '',
            'temp/dummy.zip',
            'postcodes.csv'
        );
        $this->setCommandOutput($command);

        $result = $command->handle();
        $this->assertSame(1, $result);
    }

    /**
     * Test that handle() returns an error if the HTTP client returns a non-200 response.
     * @throws Exception
     */
    public function testHandleFailsWhenFetchCsvFileFails()
    {
        $badResponse = new Response(404, [], 'Not Found');
        $postcodeRepositoryMock = $this->createMock(PostcodeRepositoryInterface::class);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willReturn($badResponse);

        $filesystem = $this->createMock(Filesystem::class);
        $command = new ImportPostcodes(
            $client,
            $filesystem,
            $postcodeRepositoryMock,
            'http://example.com/postcodes.zip',
            'temp/dummy.zip',
            'postcodes.csv'
        );
        $this->setCommandOutput($command);

        $result = $command->handle();
        $this->assertSame(1, $result);
    }

    /**
     * Test that handle() returns an error if saving the CSV file fails.
     * @throws Exception
     */
    public function testHandleFailsWhenSaveCsvFileFails()
    {
        $goodResponse = new Response(200, [], 'dummy zip content');
        $postcodeRepositoryMock = $this->createMock(PostcodeRepositoryInterface::class);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willReturn($goodResponse);

        $filesystem = $this->createMock(Filesystem::class);
        $testPath = 'temp/dummy.zip';
        $dir = dirname($testPath);

        // Use willReturnMap to cover both exists() calls.
        $filesystem->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                [$dir, true],
                [$testPath, false],
            ]);

        // Expect that put() is called once.
        $filesystem->expects($this->once())
            ->method('put')
            ->with($testPath, 'dummy zip content');

        $command = new ImportPostcodes(
            $client,
            $filesystem,
            $postcodeRepositoryMock,
            'http://example.com/postcodes.zip',
            $testPath,
            'postcodes.csv'
        );
        $this->setCommandOutput($command);

        $result = $command->handle();
        $this->assertSame(1, $result);
    }

    /**
     * Check that failing to unzip the file is being handled.
     *
     * @return void
     * @throws Exception
     * @throws ReflectionException
     */
    public function testUnzipFileSucceeds()
    {
        // Create a temporary file and rename it to have a .zip extension.
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');
        $zipFilePath = $tempFile . '.zip';
        rename($tempFile, $zipFilePath);

        // Define CSV file name and content.
        $csvFilename = 'test.csv';
        $csvContent  = "postcode,latitude,longitude\nAB1,1.0,2.0\n";

        // Create a valid zip archive with the CSV file.
        $zip = new \ZipArchive();
        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $this->fail("Could not open zip archive for testing.");
        }
        $zip->addFromString($csvFilename, $csvContent);
        $zip->close();

        // Create a Filesystem instance.
        $filesystem = new Filesystem();
        // Create a dummy HTTP client (won't be used in this test).
        $clientMock = $this->createMock(Client::class);
        $postcodeRepositoryMock = $this->createMock(PostcodeRepositoryInterface::class);

        // Instantiate the command using our zip file as the local file path.
        $command = $this->getMockBuilder(ImportPostcodes::class)
            ->setConstructorArgs([
                $clientMock,
                $filesystem,
                $postcodeRepositoryMock,
                'dummy_url',
                $zipFilePath,
                $csvFilename,
            ])
            ->onlyMethods(['getStoragePath'])
            ->getMock();

        // Override getStoragePath:
        // When "app/import" is requested, return a temporary directory that we create.
        // Otherwise, return the path unchanged.
        $command->method('getStoragePath')->willReturnCallback(function($path) {
            if ($path === 'app/import') {
                $importDir = sys_get_temp_dir() . '/import_test_import';
                if (!is_dir($importDir)) {
                    mkdir($importDir, 0777, true);
                }
                return $importDir;
            }
            return $path;
        });

        // Set a proper output so that command messages don't cause errors.
        $input  = new StringInput('');
        $buffer = new BufferedOutput();
        $output = new OutputStyle($input, $buffer);
        $command->setOutput($output);

        // Use reflection to call the private unzipFile() method.
        $refClass = new \ReflectionClass($command);
        $method = $refClass->getMethod('unzipFile');
        $csvPath = $method->invoke($command, $zipFilePath);

        // Build expected CSV path.
        $expectedImportDir = sys_get_temp_dir() . '/import_test_import';
        $expectedCsvPath   = $expectedImportDir . '/' . $csvFilename;
        $this->assertEquals($expectedCsvPath, $csvPath);
        $this->assertFileExists($csvPath);

        // Cleanup: remove created files and directories.
        unlink($zipFilePath);
        unlink($csvPath);
        rmdir($expectedImportDir);
    }


    /**
     * Test that handle() returns success (0) when the full workflow completes.
     *
     * In this test we create a temporary zip file containing a simple CSV.
     * We override getStoragePath() so it returns the provided path unchanged.
     * @throws Exception
     */
    public function testHandleSucceeds()
    {
        // Create temporary CSV content.
        $csvContent = "postcode,latitude,longitude\nAB1,1.0,2.0\n";
        $csvFilename = 'test_postcodes.csv';
        $tempDir = sys_get_temp_dir() . '/import_test';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        $zipFilePath = $tempDir . '/dummy.zip';

        // Create a zip archive with the CSV file.
        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->fail("Could not create zip archive for testing.");
        }
        $zip->addFromString($csvFilename, $csvContent);
        $zip->close();

        $zipContents = file_get_contents($zipFilePath);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('get')
            ->willReturn(new Response(200, [], $zipContents));

        // Use a real Filesystem.
        $filesystem = new Filesystem();

        // For this test, we want getStoragePath() to return the path as-is.
        $postcodeLocalFilePath = $zipFilePath;
        $postcodeFilename = $csvFilename;

        $postcodeRepositoryMock = $this->createMock(PostcodeRepositoryInterface::class);

        // Create a partial mock of ImportPostcodes overriding getStoragePath().
        $command = $this->getMockBuilder(ImportPostcodes::class)
            ->setConstructorArgs([
                $client,
                $filesystem,
                $postcodeRepositoryMock,
                'http://example.com/postcodes.zip',
                $postcodeLocalFilePath,
                $postcodeFilename,
            ])
            ->onlyMethods(['getStoragePath'])
            ->getMock();

        // Override getStoragePath so it simply returns the given path.
        $command->expects($this->any())
            ->method('getStoragePath')
            ->willReturnArgument(0);

        $this->setCommandOutput($command);

        // Stub out DB calls.
        DB::shouldReceive('beginTransaction')->once();
        DB::shouldReceive('commit')->once();

        $postcodeRepositoryMock->expects($this->once())
            ->method('insert')
            ->with([
                ['postcode' => 'AB1', 'latitude' => '1.0', 'longitude' => '2.0'],
            ]);

        $result = $command->handle();
        $this->assertSame(0, $result);

        // Clean up temporary files.
        if (file_exists($zipFilePath)) {
            unlink($zipFilePath);
        }
        $extractedCsv = $tempDir . '/' . $postcodeFilename;
        if (file_exists($extractedCsv)) {
            unlink($extractedCsv);
        }
        if (is_dir($tempDir)) {
            @rmdir($tempDir);
        }
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testRemoveCsvFile()
    {
        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->expects($this->exactly(2))->method('exists')->willReturn(true);
        $filesystem->expects($this->exactly(2))->method('delete')->willReturn(true);

        $clientMock = $this->createMock(Client::class);
        $postcodeRepositoryMock = $this->createMock(PostcodeRepositoryInterface::class);
        $command = new ImportPostcodes(
            $clientMock,
            $filesystem,
            $postcodeRepositoryMock,
            'dummy_url',
            'path/to/file.zip',
            'file.csv'
        );
        $this->setCommandOutput($command);

        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('removeCSVFile');
        $method->invoke($command, 'path/to/file.zip', 'path/to/file.csv');
    }
}
