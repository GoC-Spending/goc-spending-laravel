<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class PchHandler extends DepartmentHandler
{

    // PCH's proactive disclosure website has been removed from the web.
    // In the meantime, we're re-using already scraped data (but only the parsing component of this will work.)

    public $indexUrl = 'http://www.pch.gc.ca/trans-trans/eng/1360351192981/1360351366560';
    public $baseUrl = 'http://www.pch.gc.ca/';
    public $ownerAcronym = 'pch';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@class='center']//ul/li/a/@href";


    public $quarterToContractXpath = "//div[@class='center']//table//td//a/@href";


    // public function quarterToContractUrlTransform($contractUrl)
    // {
    //     return "http://www.fintrac-canafe.gc.ca/transp/PD/cd/" . $contractUrl;
    // }



    // public function indexToQuarterUrlTransform($url)
    // {
    //     return "http://www.fintrac-canafe.gc.ca/transp/PD/cd/" . $url;
    // }


    public $contractContentSubsetXpath = "//div[@class='center']";



    // public function fiscalYearFromQuarterPage($quarterHtml)
    // {

    //     // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

    //     return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9]{4})/');
    // }

    // public function fiscalQuarterFromQuarterPage($quarterHtml)
    // {

    //     return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9])[a-z]/');
    // }

    public function parseHtml($html)
    {

        $keyArray = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of Work:',
            'extraDescription' => '',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => 'Original Contract Value:',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        $output = Parsers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", ' to ', $keyArray);

        // Since the quarter pages are no longer online, we'll pull the quarter metadata out of the "back" link which luckily includes it.
        // e.g. <a href="       lst-eng.cfm?Quarter=Q3-2005     " title="Return to previous list">Back</a>

        $sourceYear = Parsers::xpathRegexComboSearch($html, "//a/@href", '/([0-9]{4})/');

        $sourceQuarter = Parsers::xpathRegexComboSearch($html, "//a/@href", '/Q([0-9])/');

        if ($sourceYear) {
            $output['sourceYear'] = intval($sourceYear);
        }
        if ($sourceQuarter) {
            $output['sourceQuarter'] = intval($sourceQuarter);
        }
        
        // dd($output);
        return $output;
    }
}
