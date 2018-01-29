<?php
namespace App\DepartmentHandlers;

use App\Helpers\Parsers;

class AcoaHandler extends DepartmentHandler
{
    public $indexUrl = 'http://www.acoa-apeca.gc.ca/eng/Accountability/ProactiveDisclosure/Contracts/Pages/Reports.aspx';
    public $baseUrl = 'http://www.acoa-apeca.gc.ca';
    public $ownerAcronym = 'acoa';

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
        return $this->baseUrl . $url;
    }

    public function fiscalYearFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {
        return Parsers::xpathRegexComboSearch($quarterHtml, "//div[@class='center']//h2", '/([0-9])[a-z]/');
    }

    public function parseHtml($html) {
        $data = Parsers::extractContractDataViaGenericXpathParser(
            $html,
            // Keys
            "//div[@class='ACOADataFormRow']/div[@class='ACOADataFormLabelOneColumn']",
            // Values
            "//div[@class='ACOADataFormRow']/div[@class='ACOADataFormFieldOneColumn']",
            // Period split
            ' to '
        );

        return $data;
    }
}