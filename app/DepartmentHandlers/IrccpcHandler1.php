<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

// Passport Canada (now part of IRCC)
class IrccpcHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.cic.gc.ca/disclosure-divulgation/cont-eng.aspx';
    public $baseUrl = 'http://www.cic.gc.ca/';
    public $ownerAcronym = 'irccpc';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";



    public $quarterToContractXpath = "//main//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.cic.gc.ca/disclosure-divulgation/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.cic.gc.ca/disclosure-divulgation/" . $url;
    }

    public $contractContentSubsetXpath = "//form[@id='aspnetForm']";

    public function fiscalYearAndQuarterFromIrccTitle($quarterHtml, $output = 'year')
    {

        // Somewhat confusingly, IRCC indexes quarters by calendar year instead of by fiscal year.

        $quarterIndex = [
            'January 1 - March 31' => 4,
            'April 1 - June 30' => 1,
            'July 1 - September 30' => 2,
            'October 1 - December 31' => 3,
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

        return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'year');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl)
    {

        return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'quarter');
    }

    public function parseHtml($html)
    {

        // Passport Canada has a typo in "Vender name"
        $keyArray = [
            'vendorName' => 'Vender Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => 'Description (more details):',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => '',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        return Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);
    }
}
