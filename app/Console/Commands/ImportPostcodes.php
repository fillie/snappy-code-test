<?php

namespace App\Console\Commands;

use App\Repositories\Contracts\PostcodeRepositoryInterface;
use App\Repositories\Eloquent\EloquentPostcodeRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface;
use ZipArchive;
use Exception;

class ImportPostcodes extends Command
{
    private string $postcodeUrlToImport;
    private string $postcodeLocalFilePath;
    private string $postcodeFilename;
    private Client $httpClient;
    private Filesystem $filesystem;
    private PostcodeRepositoryInterface $postcodeRepository;

    private const CHUNK_SIZE = 500;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-postcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import postcodes from a downloaded CSV into the database';

    public function __construct(
        Client $httpClient,
        Filesystem $filesystem,
        PostcodeRepositoryInterface $postcodeRepository,
        ?string $postcodeUrlToImport = null,
        ?string $postcodeLocalFilePath = null,
        ?string $postcodeFilename = null
    ) {
        $this->httpClient = $httpClient;
        $this->filesystem = $filesystem;
        $this->postcodeRepository = $postcodeRepository;

        // Use the injected values or fall back to the config() helper.
        $this->postcodeUrlToImport = $postcodeUrlToImport ?? config('console.postcode_url_to_import');
        $this->postcodeLocalFilePath = $postcodeLocalFilePath ?? config('console.postcode_local_file_path');
        $this->postcodeFilename = $postcodeFilename ?? config('console.postcode_filename');

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->runImport();
        } catch (Exception | GuzzleException $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * The main workflow of the import process.
     *
     * @throws Exception
     * @throws GuzzleException
     */
    private function runImport(): void
    {
        if (!$this->postcodeUrlToImport) {
            throw new Exception("Postcodes URL is not defined in configuration.");
        }

        $response = $this->fetchCsvFile($this->postcodeUrlToImport);
        $zipFilePath = $this->saveCsvFile($response);
        $csvFilePath = $this->unzipFile($zipFilePath);

        $this->processCsvFile($csvFilePath);
        $this->removeCSVFile($zipFilePath, $csvFilePath);
    }

    /**
     * Responsible for downloading the CSV file (as a Zip) and returning its response.
     *
     * @param string $url
     * @return ResponseInterface
     * @throws Exception|GuzzleException
     */
    private function fetchCsvFile(string $url): ResponseInterface
    {
        $this->info("Downloading postcodes file from: {$url}");

        $response = $this->httpClient->get($url);
        if ($response->getStatusCode() !== 200) {
            throw new Exception("Failed to download file from: {$url}");
        }

        return $response;
    }

    /**
     * Responsible for saving the downloaded Zip file to a specified location.
     * Always overwrites any existing file.
     *
     * @param Response $response
     * @return string
     * @throws Exception
     */
    private function saveCsvFile(Response $response): string
    {
        $this->info("Attempting to save Zip file.");
        $zipFilePath = $this->getStoragePath($this->postcodeLocalFilePath);

        if (!$this->filesystem->exists(dirname($this->postcodeLocalFilePath))) {
            $this->filesystem->makeDirectory(dirname($this->postcodeLocalFilePath), 0755, true);
        }

        $this->filesystem->put($this->postcodeLocalFilePath, $response->getBody());
        $this->info("Zip file imported. File saved to {$zipFilePath}");

        if (!$this->filesystem->exists($this->postcodeLocalFilePath)) {
            throw new Exception("Failed to store file: {$zipFilePath}");
        }

        return $zipFilePath;
    }

    /**
     * Responsible for unzipping the downloaded file and returning the CSV file path.
     *
     * @param string $zipFilePath
     * @return string
     * @throws Exception
     */
    private function unzipFile(string $zipFilePath): string
    {
        $this->info("Attempting to extract file.");

        $zip = new ZipArchive;
        if (!$zip->open($zipFilePath)) {
            throw new Exception("Failed to open zip file: {$zipFilePath}");
        }

        $extractPath = $this->getStoragePath('app/import');
        $zip->extractTo($extractPath);
        $zip->close();
        $this->info("Extraction complete to {$extractPath}");

        $csvFilePath = $extractPath . '/' . $this->postcodeFilename;
        if (!$this->filesystem->exists($csvFilePath)) {
            throw new Exception("Failed to find CSV file: {$csvFilePath}");
        }

        $this->assertCsvIsReadable($csvFilePath);

        return $csvFilePath;
    }

    /**
     * Process the CSV file and import its data into the database.
     *
     * @param string $csvFilePath
     * @return void
     * @throws Exception
     */
    private function processCsvFile(string $csvFilePath): void
    {
        $this->info("Starting import from CSV: {$csvFilePath}");

        if (($handle = fopen($csvFilePath, 'r')) === false) {
            throw new Exception("Failed to open CSV file: {$csvFilePath}");
        }
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            throw new Exception("Failed to read header from CSV file.");
        }

        $this->output->progressStart();
        $chunkToBeInserted = [];
        $currentRow = 0;

        DB::beginTransaction();

        while (($row = fgetcsv($handle)) !== false) {
            $rowWithColumnHeaders = array_combine($header, $row);

            $chunkToBeInserted[] = [
                'postcode'  => $rowWithColumnHeaders['postcode'],
                'latitude'  => $rowWithColumnHeaders['latitude'],
                'longitude' => $rowWithColumnHeaders['longitude'],
            ];

            if (count($chunkToBeInserted) >= self::CHUNK_SIZE) {
                $this->processChunk($chunkToBeInserted);
                $chunkToBeInserted = [];
                $this->output->progressAdvance(self::CHUNK_SIZE);
            }
            $currentRow++;
        }

        if (!empty($chunkToBeInserted)) {
            $this->processChunk($chunkToBeInserted);
            $this->output->progressAdvance(count($chunkToBeInserted));
        }

        DB::commit();
        fclose($handle);
        $this->output->progressFinish();
        $this->info("Import complete, processed {$currentRow} rows.");
    }

    /**
     * Responsible for removing both the ZIP file and the extracted CSV file.
     *
     * @param string $zipFilePath
     * @param string $csvFilePath
     * @throws Exception
     */
    private function removeCSVFile(string $zipFilePath, string $csvFilePath): void
    {
        $this->info("Cleaning up downloaded file.");

        if ($this->filesystem->exists($zipFilePath)) {
            $this->filesystem->delete($zipFilePath);
            $this->info("Deleted ZIP file: {$zipFilePath}");
        } else {
            $this->warn("ZIP file not found for deletion: {$zipFilePath}");
        }

        if ($this->filesystem->exists($csvFilePath)) {
            $this->filesystem->delete($csvFilePath);
            $this->info("Deleted CSV file: {$csvFilePath}");
        } else {
            $this->warn("CSV file not found for deletion: {$csvFilePath}");
        }
    }


    /**
     * Responsible for error handling and rolling back the transaction should an insert fail.
     *
     * @param array $chunkToBeInserted
     * @return void
     * @throws Exception
     */
    private function processChunk(array $chunkToBeInserted): void
    {
        try {
            $this->insertChunk($chunkToBeInserted);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Failed to insert chunk: {$e->getMessage()}");
        }
    }

    /**
     * Attempts to insert a chunk at a time.
     *
     * @param array $batchData
     * @return void
     * @throws Exception
     */
    private function insertChunk(array $batchData): void
    {
        $this->postcodeRepository->insert($batchData);
    }

    /**
     * Returns the full storage path for a given file or directory.
     *
     * @param string $path
     * @return string
     */
    protected function getStoragePath(string $path): string
    {
        return storage_path($path);
    }

    /**
     * Asserts that a CSV file is readable.
     *
     * @param string $csvFilePath
     * @throws Exception
     */
    private function assertCsvIsReadable(string $csvFilePath): void
    {
        if (!is_readable($csvFilePath)) {
            throw new Exception("Failed to open CSV file: {$csvFilePath}");
        }
    }
}
