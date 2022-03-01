<?php

namespace App\Console\Commands;

use App\Traits\GetCountryNameFromCode;
use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade as PDF;
use League\Csv\Reader;

class FormatShopifyAddresses extends Command
{
    use GetCountryNameFromCode;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'format:shopify-addresses {csv}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Format Shopify CSV order export files into a PDF that can be printed out';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $csv = Reader::createFromPath($this->argument('csv'), 'r');
        $csv->setHeaderOffset(0);
        $address = '<style>.page-break {page-break-after: always;} p {font-size: 20px;}</style>';
        $i = 0;

        $progress = $this->output->createProgressBar(count($csv));

        foreach (collect($csv->getRecords())->groupBy('Email')->toArray() as $record) {
            foreach (array_reverse($record) as $lineItem) {
                $address .= "<h4>{$lineItem['Lineitem name']} - {$lineItem['Lineitem quantity']}</h4>";

                if (!isset($lineItem['Shipping Address1'])) {
                    continue;
                }

                $address .= sprintf(
                    "<p>%s</p><p>%s</p><p>%s</p>",
                    $lineItem['Shipping Name'],
//                    $lineItem['Shipping Address1'],
//                    $lineItem['Shipping Address2'],
//                    $lineItem['Shipping City'],
                    $lineItem['Shipping Zip'],
                    $lineItem['Shipping Country'] !== 'GB'
                        ? $this->getCountryNameFromCode($lineItem['Shipping Country'])
                        : null
                );

                if (!empty($lineItem['Shipping Method'])) {
                    $address .= "<h4> Shipping Method - {$lineItem['Shipping Method']}</h4><p></p>";
                }

            }

            $address .= '<p>---------------------</p>';

            $i++;

            if ($i % 4 === 0) {
                $address .= '<div class="page-break"></div>';
            }

            $progress->advance();
        }

//            if (
//                $record['Lineitem fulfillment status'] !== 'pending'
//            ) {
//                continue;
//            }

        PDF::loadHTML($address)
            ->setPaper('a4', 'portrait')
            ->setWarnings(false)
            ->save(
                sprintf(
                    "export-%s.pdf",
                    now()->format('d-m-Y-H-i')
                )
            );

        $progress->finish();
    }

    /**
     * @param $handle
     * @return mixed
     */
    public function cleanHeaders($handle)
    {
        return strtolower(
            str_replace(
                ' ',
                '_',
                str_replace(
                    [
                        "\n",
                        "\r",
                    ],
                    '',
                    fgets($handle)
                )
            )
        );
    }
}
