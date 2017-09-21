<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers\Helpers;

class InacHandler extends DepartmentHandler
{

    public $indexUrl = 'http://www.aadnc-aandc.gc.ca/prodis/cntrcts/rprts-eng.asp';
    public $baseUrl = 'http://www.aadnc-aandc.gc.ca/';
    public $ownerAcronym = 'inac';

    // From the index page, list all the "quarter" URLs
    public $indexToQuarterXpath = "//div[@class='center']//ul/li/a/@href";

    public $multiPage = 1;
    public $quarterMultiPageXpath = "//div[@class='align-right size-small']/a/@href";

    public $quarterToContractXpath = "//table[@class='widthFull TableBorderBasic']//td//a/@href";

    public $contractContentSubsetXpath = "//div[@class='center']";

    public function fiscalYearFromQuarterPage($quarterHtml)
    {

        // <title>Disclosure of Contracts - 2016-2017 - 3rd Quarter - Indigenous and Northern Affairs Canada</title>
        return Helpers::xpathRegexComboSearch($quarterHtml, "//title", '/([0-9]{4})/');
    }

    public function fiscalQuarterFromQuarterPage($quarterHtml)
    {

        return Helpers::xpathRegexComboSearch($quarterHtml, "//title", '/-\s([0-9])[a-z]/');
    }

    public function parseHtml($html)
    {

        return Helpers::genericXpathParser($html, "//table[@class='widthFull TableBorderBasic']//th", "//table[@class='widthFull TableBorderBasic']//td", ' - ');
    }
}
