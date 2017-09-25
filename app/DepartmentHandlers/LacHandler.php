<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class LacHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.bac-lac.gc.ca/eng/transparency/proactive-disclosure/Pages/disclosure-contracts-10k-reports.aspx';
    public $baseUrl = 'http://www.bac-lac.gc.ca/';
    public $ownerAcronym = 'lac';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $quarterToContractXpath = "//main//div[@id='WebPartWPQ1']//table//td//a/@href";


//    public function quarterToContractUrlTransform($contractUrl)
//    {
//        return "http://www.ppsc.gc.ca/eng/tra/pd-dp/contracts/" . $contractUrl;
//    }
//
//
//
//    public function indexToQuarterUrlTransform($url)
//    {
//        return "http://www.ppsc.gc.ca/eng/tra/pd-dp/contracts/" . $url;
//    }


    public $contractContentSubsetXpath = "//main";

    // Ignore the quarters that use "collectionscanada.gc.ca"
    // We would need to request these separately, given that the structure of the contract pages is different.
    public function filterQuarterUrls($quarterUrls)
    {

        // Remove the new entries with "open.canada.ca"
        $quarterUrls = array_filter($quarterUrls, function ($url) {
            if (strpos($url, 'collectionscanada.gc.ca') !== false) {
                return false;
            }
            return true;
        });

        return $quarterUrls;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9])[a-z]/');
    }

    public function parseHtml($html)
    {

        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => '',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period / Delivery Date:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value:',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        return Parsers::extractContractDataViaGenericXpathParser($html, "//div[@id='WebPartWPQ1']//div[@style='float: left; width: 30%; padding: 2px 2px 12px 35px;']", "//div[@id='WebPartWPQ1']//div[@style='float: left; width: 60%; padding: 2px 2px 12px 0px;']", ' to ', $keyArray);
    }
}
