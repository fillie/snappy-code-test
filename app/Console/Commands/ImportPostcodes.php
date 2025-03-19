<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use ZipArchive;
use Exception;

class ImportPostcodes extends Command
{
    private string $postcodeUrlToImport;
    private string $postcodeLocalFilePath;
    private string $postcodeFilename;

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

    public function __construct()
    {
        $this->postcodeUrlToImport = config('console.postcode_url_to_import');
        $this->postcodeLocalFilePath = config('console.postcode_local_file_path');
        $this->postcodeFilename = config('console.postcode_filename');

        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->postcodeUrlToImport) {
            $this->error("Postcodes URL is not defined in configuration.");
            return 1;
        }

        // Attempt to download Zip containing CSV from URL
        try {
            $response = $this->fetchCsvFile($this->postcodeUrlToImport);
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        // Save Zip file locally
        try {
            $file = $this->saveCsvFile($response);
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        // Extract Zip file to access CSV
        try {
            $csvFilePath = $this->unzipFile($file);
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        // Process CSV file and import data into the database
        try {
            $this->processCsvFile($csvFilePath);
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Responsible for downloading the CSV file (as a Zip) and returning its response.
     *
     * @param string $url
     * @return Response
     * @throws Exception
     */
    protected function fetchCsvFile(string $url): Response
    {
        $this->info("Downloading postcodes file from: {$url}");

        $response = Http::get($url);
        if (!$response->successful()) {
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
    protected function saveCsvFile(Response $response): string
    {
        $this->info("Attempting to save Zip file.");
        $zipFilePath = storage_path($this->postcodeLocalFilePath);

        if (!is_dir(dirname($zipFilePath))) {
            mkdir(dirname($zipFilePath), 0755, true);
        }
        file_put_contents($zipFilePath, $response->body());
        $this->info("Zip file imported. File saved to {$zipFilePath}");

        if (!file_exists($zipFilePath)) {
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
    protected function unzipFile(string $zipFilePath): string
    {
        $this->info("Attempting to extract file.");

        $zip = new ZipArchive;
        if (!$zip->open($zipFilePath)) {
            throw new Exception("Failed to open zip file: {$zipFilePath}");
        }

        $extractPath = storage_path('app/import');
        $zip->extractTo($extractPath);
        $zip->close();
        $this->info("Extraction complete to {$extractPath}");

        $csvFilePath = $extractPath . '/' . $this->postcodeFilename;
        if (!file_exists($csvFilePath)) {
            throw new Exception("Failed to find CSV file: {$csvFilePath}");
        }

        if (!fopen($csvFilePath, 'r')) {
            throw new Exception("Failed to open CSV file: {$csvFilePath}");
        }

        return $csvFilePath;
    }

    /**
     * Process the CSV file and import its data into the database.
     *
     * @param string $csvFilePath
     * @return void
     * @throws Exception
     */
    protected function processCsvFile(string $csvFilePath): void
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

        // Start the transaction, so data is rolled back in the case of an issue.
        DB::beginTransaction();

        while (($row = fgetcsv($handle)) !== false) {

            $rowWithColumnHeaders = array_combine($header, $row);

            $chunkToBeInserted[] = [
                'postcode' => $rowWithColumnHeaders['postcode'],
                'latitude' => $rowWithColumnHeaders['latitude'],
                'longitude' => $rowWithColumnHeaders['longitude'],
            ];

            // We have reached our target chunk size, so we attempt the insert
            if (count($chunkToBeInserted) >= self::CHUNK_SIZE) {
                $this->processChunk($chunkToBeInserted);
                // Empty the chunk for the next batch
                $chunkToBeInserted = [];
                $this->output->progressAdvance(self::CHUNK_SIZE);
            }
            $currentRow++;
        }

        // Insert any remaining records not forming a complete chunk
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
     * Responsible for error handling and rolling back the transaction should an insert fail.
     *
     * @param array $chunkToBeInserted
     * @return void
     * @throws Exception
     */
    protected function processChunk(array $chunkToBeInserted): void
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
    protected function insertChunk(array $batchData): void
    {
        //DB::table('postcodes')->insert($batchData);
    }
}
