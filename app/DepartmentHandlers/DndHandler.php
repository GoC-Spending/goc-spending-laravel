<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class DndHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.admfincs.forces.gc.ca/apps/dc/intro-eng.asp';
    public $baseUrl = 'http://www.admfincs.forces.gc.ca/';
    public $ownerAcronym = 'dnd';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='main']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@id='container']//table//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.admfincs.forces.gc.ca/apps/dc/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.admfincs.forces.gc.ca/apps/dc/" . $url;
    }

    public $contractContentSubsetXpath = "//div[@id='main']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='mc-cp']", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='mc-cp']", '/([0-9]):/');
    }

    public function parseHtml($html)
    {

        // DND doesn't include a contract period range, or an original value.
        // They also have wild date formats (eg. "9-2-2017")

        $keyArray = [
            'vendorName' => 'Vendor Name',
            'referenceNumber' => 'Reference Number',
            'contractDate' => 'Contract Date',
            'description' => 'Description of work',
            'extraDescription' => 'Additional Comments',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period',
            'deliveryDate' => 'Delivery Date',
            'originalValue' => '',
            'contractValue' => 'Contract Value',
            'comments' => 'Comments',
        ];

        $values = Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", 'to', $keyArray);

        

        return $values;
    }
}
