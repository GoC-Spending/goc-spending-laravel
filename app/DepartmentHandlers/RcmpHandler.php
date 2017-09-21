<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers\Helpers;

class RcmpHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.rcmp-grc.gc.ca/en/contra/?lst=1';
    public $baseUrl = 'http://www.rcmp-grc.gc.ca/';
    public $ownerAcronym = 'rcmp';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//main//ul/li/a/@href";

    public $multiPage = 0;

    public $quarterToContractXpath = "//table[@class='wb-tables table table-striped']//td//a/@href";

    public function quarterToContractUrlTransform($contractUrl)
    {
        return "http://www.rcmp-grc.gc.ca/en/contra/" . $contractUrl;
    }

    public function indexToQuarterUrlTransform($url)
    {
        return "http://www.rcmp-grc.gc.ca/en/contra/" . $url;
    }

    public $contractContentSubsetXpath = "//main";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <h1 id="wb-cont" property="name" class="page-header mrgn-tp-md">2016-2017, 3rd quarter (1 October - 31 December 2016)</h1>

        return Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@id='wb-cont']", '/,\s([0-9])/');
    }

    public function parseHtml($html)
    {

        $values = Helpers::extractContractDataViaGenericXpathParser($html, "//table//th", "//table//td", 'to');

        // The RCMP includes both original and amended values in the same cell, but only displays the amended value when there is one (seemingly, most of the time). This tries to re-split these into separate fields.

        // Amended value is first, then original.
        // For example,
        // Amended contract value:$45,375.00Original contract value:$32,450.00

        $contractValueSplit = str_replace('Amended contract value:', '', $values['contractValue']);
        $contractValueSplit = explode('Original contract value:', $contractValueSplit);

        $amendedValue = $contractValueSplit[0];
        $originalValue = $contractValueSplit[1];

        if ($originalValue) {
            $values['originalValue'] = $originalValue;
            $values['contractValue'] = $originalValue;
        }
        if ($amendedValue) {
            $values['contractValue'] = $amendedValue;
        }

        return $values;
    }
}
