<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class OsfiHandler extends DepartmentHandler
{
    public $indexUrl = 'http://www.osfi-bsif.gc.ca/Eng/wt-ow/Pages/conts.aspx?ContNav=2';
    public $baseUrl = 'http://www.osfi-bsif.gc.ca/Eng/wt-ow/Pages/conts.aspx';
    public $ownerAcronym = 'osfi';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@id='wb-main']//table//td/a/@href";

    public $contractContentSubsetXpath = "//div[@id='wb-main']";

    public function indexToQuarterUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function quarterToContractUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h3", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h3", '/([0-9])$/');
    }

    public function parseHtml($html)
    {
        $data = Parsers::extractContractDataViaGenericXpathParser(
            $html,
            // Keys
            "//table[@class='span-8']//th",
            // Values
            "//table[@class='span-8']//td",
            // Period split
            ' to ',
            // Keys
            [
                'vendorName' => 'Vendor Name:',
                'referenceNumber' => 'Reference Number:',
                'contractDate' => 'Contract Date:',
                'description' => 'Work Description:',
                'contractPeriodStart' => '',
                'contractPeriodEnd' => '',
                'contractPeriodRange' => 'Contract Period:',
                'deliveryDate' => 'Delivery Date:',
                'originalValue' => 'Original Contract Value:',
                'contractValue' => 'Contract Value:',
                'comments' => 'Comments:',
            ]
        );

        return $data;
    }
}
