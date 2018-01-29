<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class CnscHandler extends DepartmentHandler
{
    public $indexUrl = 'http://nuclearsafety.gc.ca/eng/transparency/contracts.cfm';
    public $baseUrl = 'http://nuclearsafety.gc.ca/eng/transparency/';
    public $ownerAcronym = 'cnsc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@id='wb-main']//table//td//a/@href";

    public $contractContentSubsetXpath = "//div[@id='wb-main']";

    public function indexToQuarterUrlTransform($url)
    {
        return $this->baseUrl . 'contracts.cfm' . $url;
    }

    public function quarterToContractUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h2", '/([0-9])</');
    }

    public function parseHtml($html) {
        $data = Parsers::extractContractDataViaGenericXpathParser(
            $html,
            // Keys
            "//table//th",
            // Values
            "//table//td",
            // Period split
            ' - ',
            // Keys
            [
                'vendorName' => 'Vendor Name:',
                'referenceNumber' => 'Reference Number:',
                'contractDate' => 'Contract Date:',
                'description' => 'Description of Work:',
                'contractPeriodStart' => '',
                'contractPeriodEnd' => '',
                'contractPeriodRange' => 'Contract Period',
                'deliveryDate' => 'Delivery Date:',
                'originalValue' => 'Original Contract Value:',
                'contractValue' => 'Contract Value:',
                'comments' => 'Comments:',
            ]
        );

        return $data;
    }
}