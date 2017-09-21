<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers\Helpers;

class NrcanHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www2.nrcan-rncan.gc.ca/dc-dpm/index.cfm?fuseaction=r.q&lang=eng';
    public $baseUrl = 'http://www2.nrcan-rncan.gc.ca/';
    public $ownerAcronym = 'nrcan';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//main//table//td//a/@href";

    
    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www2.nrcan-rncan.gc.ca/dc-dpm/" . $contractUrl;
    }
    

    
    public function indexToQuarterUrlTransform($url)
    {
        return "http://www2.nrcan-rncan.gc.ca/dc-dpm/" . $url;
    }
    

    public $contractContentSubsetXpath = "//main";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Helpers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Helpers::xpathRegexComboSearch($quarterHtml, "//main//h2", '/([0-9])[a-z]/');
    }

    public function parseHtml($html)
    {

        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => 'Additional Comments:',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value:',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        return Helpers::genericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);
    }
}
