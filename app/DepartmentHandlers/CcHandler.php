<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class CcHandler extends DepartmentHandler
{
    public $indexUrl = 'http://canadacouncil.ca/about/public-accountability/proactive-disclosure/disclosure-of-contracts';
    public $baseUrl = 'http://canadacouncil.ca';
    public $ownerAcronym = 'cc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@role='main']//div[@class='score-right']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@role='main']//div[@class='score-right']//table//td//a/@href";

    public $contractContentSubsetXpath = "//div[@role='main']//div[@class='score-right']";

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
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@role='main']//div[@class='score-right']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@role='main']//div[@class='score-right']//h2", '/([0-9])</');
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
            ' To ',
            // Keys
            [
                'vendorName' => 'Vendor',
                'referenceNumber' => 'Reference Number',
                'contractDate' => 'Contract Date',
                'description' => 'Description',
                'contractPeriodStart' => '',
                'contractPeriodEnd' => '',
                'contractPeriodRange' => 'Contract Period',
                'contractValue' => 'Amount',
            ]
        );

        return $data;
    }
}
