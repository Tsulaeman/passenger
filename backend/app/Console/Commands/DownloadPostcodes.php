<?php

namespace App\Console\Commands;

use App\Models\PostCode;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use League\Csv\Reader;
use Symfony\Component\Console\Helper\ProgressBar;

class DownloadPostcodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:download-postcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download UK postcodes and import into database';

    protected $url = 'https://data.freemaptools.com/download/full-uk-postcodes/ukpostcodes.zip';

    /**
     * Execute the console command.
     */
    public function handle(Client $client)
    {
        $zipFilePath = storage_path('app/ukpostcodes.zip');
        $csvFilePath = storage_path('app/ukpostcodes.csv');

        $this->info('Downloading postcodes...');

        $this->download($client, $zipFilePath);

        $this->info('Unzipping postcodes...');

        $this->unzip($zipFilePath);

        $this->info('Importing postcodes...');

        $this->import($csvFilePath);

        $this->info('Cleaning up...');

        $this->cleanup($zipFilePath, $csvFilePath);

        $this->info('Done!');
    }

    protected function download(Client $client, $zipFilePath): void
    {
        // Download zip file from $this->url and store in $zipFilePath
        try{
            $client->request('GET', $this->url, [
                'sink' => $zipFilePath,
            ]);

            // Validate zip file
            if (!file_exists($zipFilePath)) {
                $this->error('Unable to download zip file');
                exit;
            }
        } catch (\Exception $e) {
            $this->error('Unable to download zip file');
            exit;
        }
    }

    protected function unzip($zipFilePath): void
    {
        $zip = new \ZipArchive();
        $zip->open($zipFilePath);
        $zip->extractTo(storage_path('app'));
        $zip->close();
    }

    protected function import($csvFilePath): void
    {
        // import postcodes from $csvFilePath
        // optimise the db insertion for performance
        // use chunking and upsert to speed up the import and avoid memory issues

        // Increase memory limit
        ini_set('memory_limit', '2048M');
        // increase execution time
        ini_set('max_execution_time', 300);

        $csv = Reader::createFromPath($csvFilePath, 'r');
        $csv->setHeaderOffset(0);

        $total = $csv->count();

        $progressBar = $this->getProgressBar($total);

        $postCodes = [];
        foreach ($csv as $row) {
            if($row['postcode'] === '') {
                continue;
            }
            // check for empty lat/long and make them null
            if($row['latitude'] === '') {
                $row['latitude'] = null;
            }
            if($row['longitude'] === '') {
                $row['longitude'] = null;
            }
            $postCodes[] = [
                'postcode' => $row['postcode'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
            ];

            // the csv file has around 1.7 million rows
            if (count($postCodes) === 10000) {
                // use upsert for performance
                PostCode::upsert($postCodes, ['postcode'], ['latitude', 'longitude']);
                $postCodes = [];
            }

            $progressBar->advance();
        }

        if (count($postCodes) > 0) {
            // use upsert for performance
            PostCode::upsert($postCodes, ['postcode'], ['latitude', 'longitude']);
        }

        $progressBar->finish();
    }

    protected function cleanup($zipFilePath, $csvFilePath): void
    {
        unlink($zipFilePath);
        unlink($csvFilePath);
    }

    protected function getProgressBar($total): ProgressBar
    {
        $progressBar = $this->output->createProgressBar($total);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        $progressBar->setMessage($this->getMemoryUsage(), 'memory');
        $progressBar->start();
        return $progressBar;
    }

    protected function getMemoryUsage(): string
    {
        $memory = memory_get_usage(true);

        if ($memory < 1024) {
            return $memory . ' B';
        } elseif ($memory < 1048576) {
            return round($memory / 1024, 2) . ' KB';
        } else {
            return round($memory / 1048576, 2) . ' MB';
        }
    }
}
