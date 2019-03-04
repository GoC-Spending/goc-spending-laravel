<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class StatsHandler extends DepartmentHandler
{

    public $indexUrl = 'https://www.statcan.gc.ca/eng/about/contract/report';
    public $baseUrl = 'https://www.statcan.gc.ca/';
    public $ownerAcronym = 'stats';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@role='main']//ul/li/a/@href";



    public $quarterToContractXpath = "//div[@role='main']//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return trim($contractUrl);
    }

    /*
    public function indexToQuarterUrlTransform($url) {
        return "http://www.cic.gc.ca/disclosure-divulgation/" . $url;
    }*/


    // Ignore the latest quarter that uses "open.canada.ca" as a link instead.
    // We'll need to retrieve those from the actual dataset.
    public function filterQuarterUrls($quarterUrls)
    {

        // Remove the new entries with "open.canada.ca"
        $quarterUrls = array_filter($quarterUrls, function ($url) {
            if (strpos($url, 'open.canada.ca') !== false) {
                return false;
            }
            return true;
        });

        return $quarterUrls;
    }


    public $contractContentSubsetXpath = "//div[@role='main']";

    public function fiscalYearAndQuarterFromTitle($quarterHtml, $output = 'year')
    {

        $quarterIndex = [
            'Fourth quarter' => 4,
            'First quarter' => 1,
            'Second quarter' => 2,
            'Third quarter' => 3,
        ];

        $fiscalYear = Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
        $fiscalQuarter = '';


// \w
        $title = Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/(.*)/');

        // Try to find one of the text labels in the title element, and match the Q1, Q2, etc. value:
        foreach ($quarterIndex as $quarterLabel => $quarterKey) {
            if (strpos($title, $quarterLabel) !== false) {
                $fiscalQuarter = $quarterKey;
                break;
            }
        }

        if ($output == 'year') {
            return $fiscalYear;
        } else {
            return $fiscalQuarter;
        }
    }

    public function fiscalYearFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromTitle($quarterHtml, 'year');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromTitle($quarterHtml, 'quarter');
    }

    public function parseHtml($html)
    {

        // Older StatCan pages don't use <th> elements for labels
        // So as a quick fix, we'll str_replace those ahead of time
        // and then keep the Xpath parser consistent.
        $html = str_replace(
            [
                '<td>Vendor:</td>',
                '<td>Reference Number:</td>',
                '<td>Contract Date:</td>',
                '<td>Description of Work:</td>',
                '<td>Contract Period :</td>',
                '<td>Contract Period:</td>',
                '<td>Delivery Date:</td>',
                '<td>Contract Value:</td>',
                '<td>Comments:</td>',
                // Fixes for eg.
                // https://www.statcan.gc.ca/eng/about/contract/2004/62700-04-0035
                '<th class="row-stub" scope="row">Nom du vendeur:</th>',
                '<th class="row-stub" scope="row">Numéro de référence :</th>',
                '<th class="row-stub" scope="row">Description&nbsp;of&nbsp;Work:</th>',
            ],
            [
                '<th scope="row">Vendor:</th>',
                '<th scope="row">Reference Number:</th>',
                '<th scope="row">Contract Date:</th>',
                '<th scope="row">Description of Work:</th>',
                '<th scope="row">Contract Period :</th>',
                '<th scope="row">Contract Period :</th>',
                '<th scope="row">Delivery Date:</th>',
                '<th scope="row">Contract Value:</th>',
                '<th scope="row">Comments:</th>',
                // Replacements per above
                '<th scope="row">Vendor:</th>',
                '<th scope="row">Reference Number:</th>',
                '<th scope="row">Description of Work:</th>',

            ],
            $html
        );

        $keyArray = [
            'vendorName' => 'Vendor',
            'referenceNumber' => 'Reference Number',
            'contractDate' => 'Contract Date',
            'description' => 'Description of work',
            'extraDescription' => 'Detailed Description',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period',
            'deliveryDate' => 'Delivery Date',
            'originalValue' => 'Original Contract Value',
            'contractValue' => 'Contract Value',
            'comments' => 'Comments',
        ];

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th[@scope='row']", "//table//td", ' to ', $keyArray);
    }
}
