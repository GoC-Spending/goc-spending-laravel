<?php
namespace App\DepartmentHandlers;

use App\DepartmentHandler;
use App\Helpers;

class IcHandler extends DepartmentHandler {

	public $indexUrl = 'https://www.ic.gc.ca/app/scr/ic/cr/quarters.html?lang=eng';
	public $baseUrl = 'https://www.ic.gc.ca/';
	public $ownerAcronym = 'ic';

	// From the index page, list all the "quarter" URLs
	public $indexToQuarterXpath = "//div[@role='main']//ul/li/a/@href";

	public $multiPage = 0;

	public $quarterToContractXpath = "//div[@role='main']//table//td//a/@href";

	// public function quarterToContractUrlTransform($contractUrl) {
	// 	return "http://www.cic.gc.ca/disclosure-divulgation/" . $contractUrl;
	// }

	// public function indexToQuarterUrlTransform($url) {
	// 	return "http://www.cic.gc.ca/disclosure-divulgation/" . $url;
	// }

	public $contractContentSubsetXpath = "//div[@role='main']";

	public function removeSessionIdsFromUrl($url) {

		// Changes URLs from
		// "/app/scr/ic/cr/contract.html;jsessionid=00016skqjTiJbbz1GakSKZcquPJ:-3001ID?id=205786"
		// to
		// "/app/scr/ic/cr/contract.html?id=205786"

		$url = preg_replace('/;([^?]*)?/i', '', $url);
		return $url;
	}

	public function fiscalYearAndQuarterFromIrccTitle($quarterHtml, $output ='year') {

		// Somewhat confusingly, IC (like IRCC) indexes quarters by calendar year instead of by fiscal year.

		$quarterIndex = [
			'January 1 to March 31' => 4,
			'April 1 to June 30' => 1,
			'July 1 to September 30' => 2,
			'October 1 to December 31' => 3,
		];

		$fiscalYear = Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@class='printHeader']", '/([0-9]{4})/');
		$fiscalQuarter = '';


// \w
		$title = Helpers::xpathRegexComboSearch($quarterHtml, "//h1[@class='printHeader']");

		$title = str_replace(["\n", "\r", "\t"], ' ', $title);
		$title = str_replace(["   ", "  "], ' ', $title);

		// Try to find one of the text labels in the title element, and match the Q1, Q2, etc. value:
		foreach($quarterIndex as $quarterLabel => $quarterKey) {
			if(strpos($title, $quarterLabel) !== false) {
				$fiscalQuarter = $quarterKey;
				break;
			}
		}

		// Correction to keep these aligned with fiscal-year based entries.
		// For example, when IC has "January 1 - March 31, 2016", other departments would have "2015-2016 Q4" and so the operative year would actually be 2015.
		if($fiscalQuarter == 4) {
			$fiscalYear -= 1;
		}

		if($output == 'year') {
			return $fiscalYear;
		}
		else {
			return $fiscalQuarter;
		}

	}

	public function fiscalYearFromQuarterPage($quarterHtml, $quarterUrl) {

		return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'year');

	}

	public function fiscalQuarterFromQuarterPage($quarterHtml, $quarterUrl) {

		return $this->fiscalYearAndQuarterFromIrccTitle($quarterHtml, 'quarter');

	}

	public static function parseHtml($html) {

		$keyArray = [
			'vendorName' => 'Vendor Name',
			'referenceNumber' => 'Reference Number',
			'contractDate' => 'Contract Date',
			'description' => 'Description',
			'extraDescription' => 'Additional Comments',
			'contractPeriodStart' => '',
			'contractPeriodEnd' => '',
			'contractPeriodRange' => 'Contract period / delivery date',
			'deliveryDate' => 'Delivery Date:',
			'originalValue' => 'Original Contract Value',
			'contractValue' => 'Contract Value',
			'comments' => 'Comment',
		];

		return Helpers::genericXpathParser($html, "//div[@class='formTable']//div[@class='ic2col1 formLeftCol']", "//div[@class='formTable']//div[@class='ic2col2 formRightCol']", ' - ', $keyArray);

	}

}
