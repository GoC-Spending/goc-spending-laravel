<?php
namespace App\DepartmentHandlers;

use App\Helpers\Cleaners;
use App\Helpers\Parsers;
use XPathSelector\Selector;

class CbsaHandler extends DepartmentHandler
{

    
    public $indexUrl = 'http://www.cbsa-asfc.gc.ca/pd-dp/contracts-contrats/reports-rapports-eng.html';
    public $baseUrl = 'http://www.cbsa-asfc.gc.ca/';
    public $ownerAcronym = 'cbsa';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main[@class='container']//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//table[@id='pdcon-table']//td//a/@href";


    public $contractContentSubsetXpath = "//div[@id='wb-main-in']";

    // Since the a href tags on the quarter pages just return a path-relative URL, use this to prepend the rest of the URL path
    public function quarterToContractUrlTransform($contractUrl)
    {
        echo "Q: " . $this->activeQuarterPage . "\n";

        $urlArray = explode('/', $this->activeQuarterPage);
        array_pop($urlArray);

        $urlString = implode('/', $urlArray).'/';

        return $urlString.$contractUrl;
    }

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

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont">ARCHIVED - 2015-2016, Q3 (October - December 2015)</h1>
        return Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Parsers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/Q([0-9])/');
    }


    

    public function parseHtml($html)
    {

        $values = [];
        $keyToLabel = [
            'vendorName' => 'Vendor Name:',
            'referenceNumber' => 'Reference Number:',
            'contractDate' => 'Contract Date:',
            'description' => 'Description of work:',
            'extraDescription' => 'Additional description:',
            'contractPeriodStart' => '',
            'contractPeriodEnd' => '',
            'contractPeriodRange' => 'Contract Period:',
            'deliveryDate' => 'Delivery Date:',
            'originalValue' => '',
            'contractValue' => 'Contract Value:',
            'comments' => 'Comments:',
        ];

        $cleanKeys = [];
        foreach ($keyToLabel as $key => $label) {
            $cleanKeys[$key] = Cleaners::cleanLabelText($label);
        }

        $labelToKey = array_flip($cleanKeys);

        $xs = Selector::loadHTML($html);

        // Extracts the keys (from the <th> tags) in order
        $keyXpath = "//table[@class='contractDetail span-6']//th";
        $keyNodes = $xs->findAll($keyXpath)->map(function ($node, $index) {
            return (string)$node;
        });

        // Extracts the values (from the <td> tags) in hopefully the same order:
        $valueXpath = "//table[@class='contractDetail span-6']//td";
        $valueNodes = $xs->findAll($valueXpath)->map(function ($node, $index) {
            return (string)$node;
        });

        $keys = [];

        // var_dump($keyNodes);
        // var_dump($valueNodes);

        foreach ($keyNodes as $index => $keyNode) {
            $keyNode = Cleaners::cleanLabelText($keyNode);

            if (isset($labelToKey[$keyNode]) && $labelToKey[$keyNode]) {
                $values[$labelToKey[$keyNode]] = Cleaners::cleanHtmlValue($valueNodes[$index]);
            }
        }

        // var_dump($values);
        // exit();

        // Change the "to" range into start and end values:
        if (isset($values['contractPeriodRange']) && $values['contractPeriodRange']) {
            $split = explode(' to ', $values['contractPeriodRange']);
            $values['contractPeriodStart'] = trim($split[0]);
            $values['contractPeriodEnd'] = trim($split[1]);
        }

        // var_dump($values);
        return $values;
    }
}
