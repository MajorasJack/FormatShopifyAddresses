<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade as PDF;

class FormatShopifyAddresses extends Command
{
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
        $handle = fopen($this->argument('csv'), 'r');
        $headers = explode(',', $this->cleanHeaders($handle));
        $address = '<style>.page-break {page-break-after: always;} p {font-size: 24px;}</style>';
        $i = 0;

        if ($handle !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $data = array_combine($headers, $data);

                $address .= "<p></p><h3>{$data['lineitem_name']}</h3>";

                $address .= sprintf(
                    "<p>%s</p><p>%s</p><p>%s</p><p>%s</p><p>%s</p>",
                    $data['shipping_name'],
                    $data['shipping_address1'],
                    $data['shipping_address2'],
                    $data['shipping_city'],
                    $data['shipping_zip']
                );

                $i++;

                if ($i % 4 === 0) {
                    $address .= '<div class="page-break"></div>';
                }
            }

            PDF::loadHTML($address)
                ->setPaper('a4', 'portrait')
                ->setWarnings(false)
                ->save(
                    sprintf(
                        "export-%s.pdf",
                        now()->format('d-m-Y-H-i')
                    )
                );
        }
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
