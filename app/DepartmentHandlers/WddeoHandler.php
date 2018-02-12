<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class WddeoHandler extends DepartmentHandler
{
    public $indexUrl = 'https://www.wd-deo.gc.ca/eng/7705.asp';
    public $baseUrl = 'https://www.wd-deo.gc.ca';
    public $ownerAcronym = 'wddeo';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@class='center']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@class='center']//table//td//a/@href";

    public $contractContentSubsetXpath = "//div[@class='center']";

    public function indexToQuarterUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function quarterToContractUrlTransform($url)
    {
        return $this->baseUrl . '/eng/' . $url;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9])</');
    }

    public function parseHtml($html)
    {
        $data = Parsers::extractContractDataViaGenericXpathParser(
            $html,
            // Keys
            "//table//th",
            // Values
            "//table//td",
            // Period split
            ' to ',
            // Keys
            [
               'vendorName' => 'Vendor Name :',
               'referenceNumber' => 'Reference Number :',
               'contractDate' => 'Contract Date :',
               'description' => 'Description of Work :',
               'contractPeriodStart' => '',
               'contractPeriodEnd' => '',
               'contractPeriodRange' => 'Contract Period / Delivery Date :',
               'originalValue' => 'Original Contract Value :',
               'contractValue' => 'Contract Value :',
               'comments' => 'Comments :',
            ]
        );

      // Since the data is in the same field, set them that way in the output.
        $data['deliveryDate'] = $data['contractPeriodRange'];

        return $data;
    }
}
