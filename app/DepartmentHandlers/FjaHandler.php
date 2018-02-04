<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class FjaHandler extends DepartmentHandler
{
    public $indexUrl = 'http://www.fja-cmf.gc.ca/pd-dp/contracts-contrats/reports-rapports-eng.aspx';
    public $baseUrl = 'http://www.fja-cmf.gc.ca/pd-dp/contracts-contrats/';
    public $ownerAcronym = 'fja';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@id='wb-main']//ul/li/a/@href";

    public $quarterToContractXpath = "//div[@id='wb-main']//table//td//a/@href";

    public $contractContentSubsetXpath = "//div[@id='wb-main']";

    public function indexToQuarterUrlTransform($url)
    {
        return $this->baseUrl . $url;
    }

    public function quarterToContractUrlTransform($url)
    {
        if (strpos($url, '?') !== false) {
            // URL style is
            // http://www.fja-cmf.gc.ca/pd-dp/contracts-contrats/details-eng.aspx?refno=4F001-14-514
            return $this->baseUrl . $url;
        } else {
            // URL style is
            // http://www.fja.gc.ca/pd-dp/contracts-contrats/2014-2015_3/ctrt1-eng.html
            // Quarter page URL style is,
            // http://www.fja-cmf.gc.ca/pd-dp/contracts-contrats/2014-2015_3/index-eng.html
            return str_replace('index-eng.html', $url, $this->activeQuarterPageUrl);
        }
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@id='wb-main']//h2", '/([0-9])</');
    }

    public function parseHtml($html)
    {
        $html = str_replace([' to/Ã ', ' to/au ', ' to/à '], ' to ', $html);

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
                'vendorName' => 'Vendor Name:',
                'referenceNumber' => 'Reference Number:',
                'contractDate' => 'Contract Date:',
                'description' => 'Description of work:',
                'extraDescription' => 'Description (more details):',
                'contractPeriodStart' => '',
                'contractPeriodEnd' => '',
                'contractPeriodRange' => 'Contract Period:',
                'deliveryDate' => 'Delivery Date:',
                'originalValue' => 'Original Contract Value:',
                'contractValue' => 'Contract Value:',
                'comments' => 'Comments:',
                'formerPublicServant' => 'Former Public Servant:',
            ]
        );

        // Convert formerPublicServant to boolean
        if (array_key_exists('formerPublicServant', $data)) {
            $data['formerPublicServant'] = filter_var($data['formerPublicServant'], FILTER_VALIDATE_BOOLEAN);
        }

        return $data;
    }
}
