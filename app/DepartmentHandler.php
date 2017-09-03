<?php
namespace App;

use GuzzleHttp\Client;
use XPathSelector\Selector;
use App\Helpers;


// Includes both fetching and parsing functions
// now combined into one class.
class DepartmentHandler {

	// Fetching-related variables:
	public $guzzleClient;

	public $ownerAcronym;
	public $indexUrl;

	public $activeQuarterPage;
	public $activeFiscalYear;
	public $activeFiscalQuarter;

	public $totalContractsFetched = 0;


	public $contractContentSubsetXpath;
	public $contentSplitParameters = [];

	public $multiPage = 0;
	public $sleepBetweenDownloads = 0;


	// Parsing variables:
	public static $rowParams = [
	    'uuid' => '',
	    'vendorName' => '',
	    'referenceNumber' => '',
	    'contractDate' => '',
	    'description' => '',
	    'objectCode' => '',
	    'contractPeriodStart' => '',
	    'contractPeriodEnd' => '',
	    'startYear' => '',
	    'endYear' => '',
	    'deliveryDate' => '',
	    'originalValue' => '',
	    'contractValue' => '',
	    'comments' => '',
	    'ownerAcronym' => '',
	    'sourceYear' => '',
	    'sourceQuarter' => '',
	    'sourceFiscal' => '',
	    'sourceFilename' => '',
	    'sourceURL' => '',
	    'amendedValues' => [],
	];


	public function __construct($detailsArray = []) {

		if($this->baseUrl) {
			$this->guzzleClient = new Client(['base_uri' => $this->baseUrl]);
		}
		else {
			$this->guzzleClient = new Client;
		}
		



	}

	// By default, just return the same
	// Child classes can change this, to eg. add a parent URL
	public function quarterToContractUrlTransform($contractUrl) {
		return $contractUrl;
	}

	// Similar to the above, but for index pages
	public function indexToQuarterUrlTransform($contractUrl) {
		return $contractUrl;
	}

	// In case we want to filter specific URLs out of the list of quarter URLs
	// Useful for departments (like CBSA) that change their schema halfway through :P 
	public function filterQuarterUrls($quarterUrls) {
		return $quarterUrls;
	}

	public function run() {

		// Run the operation!
		$startDate = date('Y-m-d H:i:s');
		echo "Starting " . $this->ownerAcronym . " at ". $startDate . " \n\n";
		$startTime = microtime(true);


		$indexPage = $this->getPage($this->indexUrl);

		$quarterUrls = $this->getQuarterUrls($indexPage);

		$quarterUrls = $this->filterQuarterUrls($quarterUrls);

		$quartersFetched = 0;
		foreach ($quarterUrls as $url) {

			if(env('FETCH_LIMIT_QUARTERS', 2) && $quartersFetched >= env('FETCH_LIMIT_QUARTERS', 2)) {
				break;
			}

			$url = $this->indexToQuarterUrlTransform($url);

			echo $url . "\n";

			// If the quarter pages have server-side pagination, then we need to get the multiple pages that represent that quarter. If there's only one page, then we'll put that as a single item in an array below, to simplify any later steps:
			$quarterMultiPages = [];
			if($this->multiPage == 1) {

				$quarterPage = $this->getPage($url);

				// If there aren't multipages, this just returns the original quarter URL back as a single item array:
				$quarterMultiPages = $this->getMultiPageQuarterUrls($quarterPage);

			}
			else {
				$quarterMultiPages = [ $url ];
			}


			$contractsFetched = 0;
			// Retrive all the (potentially multiple) pages from the given quarter:
			foreach($quarterMultiPages as $url) {
				echo "D: " . $url . "\n";

				$this->activeQuarterPage = $url;

				$quarterPage = $this->getPage($url);

				// Clear it first just in case
				$this->activeFiscalYear = '';
				$this->activeFiscalQuarter = '';

				if(method_exists($this, 'fiscalYearFromQuarterPage')) {
					$this->activeFiscalYear = $this->fiscalYearFromQuarterPage($quarterPage);
				}
				if(method_exists($this, 'fiscalQuarterFromQuarterPage')) {
					$this->activeFiscalQuarter = $this->fiscalQuarterFromQuarterPage($quarterPage);
				}


				$contractUrls = $this->getContractUrls($quarterPage);

				foreach($contractUrls as $contractUrl) {

					if(env('FETCH_LIMIT_CONTRACTS_PER_QUARTER', 2) && $contractsFetched >= env('FETCH_LIMIT_CONTRACTS_PER_QUARTER', 2)) {
						break;
					}

					$contractUrl = $this->quarterToContractUrlTransform($contractUrl);

					echo "   " . $contractUrl . "\n";

					$this->downloadPage($contractUrl, $this->ownerAcronym);
					$this->saveMetadata($contractUrl);

					$this->totalContractsFetched++;
					$contractsFetched++;
				}

			}

			echo "$contractsFetched pages downloaded for this quarter.\n\n";

			$quartersFetched++;
		}
		// echo $indexPage;

	}

	public static function arrayFromHtml($htmlSource, $xpath) {

		$xs = Selector::loadHTML($htmlSource);

		$urls = $xs->findAll($xpath)->map(function ($node, $index) {
			return (string)$node;
		});

		return $urls;

	}

	public function getQuarterUrls($indexPage) {

		$urls = self::arrayFromHtml($indexPage, $this->indexToQuarterXpath);

		// var_dump($urls);

		$urls = array_unique($urls);

		return $urls;

	}

	public function getMultiPageQuarterUrls($quarterPage) {

		$urls = self::arrayFromHtml($quarterPage, $this->quarterMultiPageXpath);

		$urls = array_unique($urls);

		return $urls;

	}

	public function getContractUrls($quarterPage) {

		$urls = self::arrayFromHtml($quarterPage, $this->quarterToContractXpath);

		$urls = array_unique($urls);

		return $urls;

	}


	// Get a page using the Guzzle library
	// No longer a static function since we're reusing the client object between requests.
	// Ignores SSL verification per http://stackoverflow.com/a/32707976/756641
	public function getPage($url) {
		$response = $this->guzzleClient->request('GET', $url,
			[
				'verify' => false,
			]);
		return $response->getBody();
	}

	public static function urlToFilename($url, $extension = '.html') {

		return md5($url) . $extension;

	}


	// Generic page download function
	// Downloads the requested URL and saves it to the specified directory
	// If the same URL has already been downloaded, it avoids re-downloading it again.
	// This makes it easier to stop and re-start the script without having to go from the very beginning again.
	public function downloadPage($url, $subdirectory = '') {

		$url = self::cleanupIncomingUrl($url);

		$filename = self::urlToFilename($url);

		$directoryPath = storage_path() . '/' . env('FETCH_RAW_HTML_FOLDER', 'raw-data');

		if($subdirectory) {
			$directoryPath .= '/' . $subdirectory;
		}

		// If the folder doesn't exist yet, create it:
		// Thanks to http://stackoverflow.com/a/15075269/756641
		if(! is_dir($directoryPath)) {
			mkdir($directoryPath, 0755, true);
		}

		// If that particular page has already been downloaded,
		// don't download it again.
		// That lets us re-start the script without starting from the very beginning again.
		if(file_exists($directoryPath . '/' . $filename) == false || env('FETCH_REDOWNLOAD_EXISTING_FILES', 1)) {

			// Download the page in question:
			$pageSource = $this->getPage($url);

			// echo "ENCODING IS: ";
			// $encoding = mb_detect_encoding($pageSource, mb_detect_order(), 1);
			// echo $encoding . "\n";

			if($pageSource) {

				if($this->contentSplitParameters) {

					$split = explode($this->contentSplitParameters['startSplit'], $pageSource);
					$pageSource = explode($this->contentSplitParameters['endSplit'], $split[1])[0];

				}

				if($this->contractContentSubsetXpath) {

					$xs = Selector::loadHTML($pageSource);
					$pageSource = $xs->find($this->contractContentSubsetXpath)->innerHTML(); 

				}

				// Store it to a local location:
				file_put_contents($directoryPath . '/' . $filename, $pageSource);

				// Optionally sleep for a certain amount of time (eg. 0.1 seconds) in between fetches to avoid angry sysadmins:
				if(env('FETCH_SLEEP_BETWEEN_DOWNLOADS', 0)) {
					sleep(env('FETCH_SLEEP_BETWEEN_DOWNLOADS', 0));
				}

				// This can now be configured per-department
				// The two are cumulative (eg. you could have a system-wide sleep configuration, and a department-specific, and it'll sleep for both durations.)
				if($this->sleepBetweenDownloads) {
					sleep($this->sleepBetweenDownloads);
				}

			}

			
			
			return true;

		}
		else {
			$this->totalAlreadyDownloaded += 1;
			return false;
		}


	}

	public function saveMetadata($url) {

		// Only save metadata if we have anything useful:
		if(! $this->activeFiscalYear) {
			return false;
		}

		$filename = self::urlToFilename($url, '.json');
		$directoryPath = dirname(__FILE__) . '/' . Configuration::$metadataOutputFolder . '/' . $this->ownerAcronym;

		$directoryPath = storage_path() . '/' . env('FETCH_METADATA_FOLDER', 'metadata');


		// If the folder doesn't exist yet, create it:
		// Thanks to http://stackoverflow.com/a/15075269/756641
		if(! is_dir($directoryPath)) {
		    mkdir($directoryPath, 0755, true);
		}

		$output = [
			'sourceURL' => $url,
			'sourceYear' => intval($this->activeFiscalYear),
			'sourceQuarter' => intval($this->activeFiscalQuarter),
		];

		if(file_put_contents($directoryPath . '/' . $filename, json_encode($output, JSON_PRETTY_PRINT))) {
			return true;
		}

		return false;

	}

	// For departments that use ampersands in link URLs, this seems to be necessary before retrieving the pages:
	public static function cleanupIncomingUrl($url) {

		$url = str_replace('&amp;', '&', $url);
		return $url;

	}





	// Parsing functions:

	public static function getSourceDirectory($acronym) {

	    return storage_path() . '/' . env('FETCH_RAW_HTML_FOLDER', 'raw-data') . '/' . $acronym;

	}

	public static function getMetadataDirectory($acronym) {

	    return storage_path() . '/' . env('FETCH_METADATA_FOLDER', 'metadata') . '/' . $acronym;

	}



	public static function cleanParsedArray(&$values) {

	    $values['startYear'] = Helpers::yearFromDate($values['contractPeriodStart']);
	    $values['endYear'] = Helpers::yearFromDate($values['contractPeriodEnd']);

	    $values['originalValue'] = Helpers::cleanupContractValue($values['originalValue']);
	    $values['contractValue'] = Helpers::cleanupContractValue($values['contractValue']);

	    if(! $values['contractValue']) {
	        $values['contractValue'] = $values['originalValue'];
	    }

	    if($values['originalValue'] == 0) {
	        $values['originalValue'] = $values['contractValue'];
	    }

	    // Check for error-y non-unicode characters
	    $values['referenceNumber'] = Helpers::cleanText($values['referenceNumber']);
	    $values['vendorName'] = Helpers::cleanText($values['vendorName']);
	    $values['comments'] = Helpers::cleanText($values['comments']);
	    $values['description'] = Helpers::cleanText($values['description']);


	}

	public static function generateAdditionalMetadata(&$values) {

	    if($values['sourceYear'] && $values['sourceQuarter']) {

	        // Generate a more traditional "20162017-Q3"
	        $values['sourceFiscal'] = $values['sourceYear'] . (substr($values['sourceYear'], 2, 2) + 1) . '-Q' . $values['sourceQuarter'];

	    }

	}

	public function parseDepartment() {

	    $sourceDirectory = self::getSourceDirectory($this->acronym);

	    $validFiles = [];
	    $files = array_diff(scandir($sourceDirectory), ['..', '.']);

	    foreach($files as $file) {
	        // Check if it ends with .html
	        $suffix = '.html';
	        if(substr_compare( $file, $suffix, -strlen( $suffix )) === 0) {
	            $validFiles[] = $file;
	        }
	    }

	    $filesParsed = 0;
	    foreach($validFiles as $file) {
	        if(env('PARSE_LIMIT_FILES', 2) && $filesParsed >= env('PARSE_LIMIT_FILES', 2)) {
	            break;
	        }

	        // Retrieve the values from the department-specific file parser
	        // And merge these with the default values
	        // Just to guarantee that all the array keys are around:
	        $fileValues = array_merge(self::$rowParams, $this->parseFile($file));

	        $metadata = $this->getMetadata($file);

	        if($fileValues) {

	            self::cleanParsedArray($fileValues);
	            // var_dump($fileValues);

	            $fileValues = array_merge($fileValues, $metadata);

	            self::generateAdditionalMetadata($fileValues);

	            $fileValues['ownerAcronym'] = $this->acronym;

	            $fileValues['objectCode'] = self::getObjectCodeFromDescription($fileValues['description']);

	            // Useful for troubleshooting:
	            $fileValues['sourceFilename'] = $this->acronym . '/' . $file;

	            // A lot of DND's entries are missing reference numbers:
	            if(! $fileValues['referenceNumber']) {
	                echo "Warning: no reference number.\n";
	                $filehash = explode('.', $file)[0];

	                $fileValues['referenceNumber'] = $filehash;

	            }

	            // TODO - update this to match the schema discussed at 2017-03-28's Civic Tech!
	            $fileValues['uuid'] = $this->acronym . '-' . $fileValues['referenceNumber'];

	            $referenceNumber = $fileValues['referenceNumber'];

	            // If the row already exists, update it
	            // Otherwise, add it
	            if(isset($this->contracts[$referenceNumber])) {
	                echo "Updating $referenceNumber\n";

	                // Because we don't have a year/quarter for all organizations, let's use the largest contractValue for now:
	                $existingContract = $this->contracts[$referenceNumber];
	                if($fileValues['contractValue'] > $existingContract['contractValue']) {
	                    $this->contracts[$referenceNumber] = $fileValues;
	                }

	                // Add entries to the amendedValues array
	                // If it's the first time, add the original too
	                if($existingContract['amendedValues']) {
	                    $this->contracts[$referenceNumber]['amendedValues'] = array_merge($existingContract['amendedValues'], [$fileValues['contractValue']]);
	                }
	                else {
	                    $this->contracts[$referenceNumber]['amendedValues'] = [
	                        $existingContract['contractValue'],
	                        $fileValues['contractValue'],
	                    ];
	                }

	            } else {
	                // Add to the contracts array:
	                $this->contracts[$referenceNumber] = $fileValues;
	            }

	        }
	        else {
	            echo "Error: could not parse data for $file\n";
	        }



	        $filesParsed++;

	    }
	    // var_dump($validFiles);

	}

	public function getMetadata($htmlFilename) {

	    $filename = str_replace('.html', '.json', $htmlFilename);

	    $filepath = self::getMetadataDirectory($this->acronym) . '/' . $filename;

	    if(file_exists($filepath)) {

	        $source = file_get_contents($filepath);
	        $metadata = json_decode($source, 1);

	        if(is_array($metadata)) {

	            return $metadata;
	        }

	    }

	    return [];

	}

	public function parseFile($filename) {

	    $acronym = $this->acronym;

	    if ( ! class_exists('App\\DepartmentHandlers\\' . ucfirst($acronym) . 'Handler' ) ) {
	        echo 'Cannot find matching DepartmentHandler for ' . $acronym . "; skipping parsing it.\n";
	        return false;
	    }

	    $source = file_get_contents(self::getSourceDirectory($this->acronym) . '/' . $filename);

	    $source = Helpers::initialSourceTransform($source, $acronym);

	    return call_user_func( array( 'App\\DepartmentHandlers\\' . ucfirst($acronym) . 'Handler', 'parse' ), $source );

	}

	public static function getObjectCodeFromDescription($description) {

	    // For example,
	    // 514- Rental of other buildings
	    // 1228 - Computer software

	    // The full list of Chart of Accounts Object Codes is available here,
	    // https://www.tpsgc-pwgsc.gc.ca/recgen/pceaf-gwcoa/1718/ressource-resource-eng.html
	    // as the last link on the page.

	    $objectCode = '';

	    $matches = [];
	    $pattern = '/([0-9]{3,4})/';

	    preg_match($pattern, $description, $matches);

	    if($matches) {
	        // Get the matching pattern, and left-pad it with zeroes
	        // Sometimes these show up as eg. 514 and sometimes 0514
	        $objectCode = str_pad($matches[1], 4, '0', STR_PAD_LEFT);
	    }

	    return $objectCode;

	}

	public static function getDepartments() {

	    $output = [];
	    $sourceDirectory = storage_path() . '/' . env('FETCH_RAW_HTML_FOLDER', 'raw-data');


	    $departments = array_diff(scandir($sourceDirectory), ['..', '.']);

	    // Make sure that these are really directories
	    // This could probably done with some more elegant array map function
	    foreach($departments as $department) {
	        if(is_dir($sourceDirectory . $department)) {
	            $output[] = $department;
	        }
	    }

	    return $output;

	}

	public static function parseAllDepartments() {

	    // Run the operation!
	    $startTime = microtime(true);

	    // Question of the day is... how big can PHP arrays get?
	    $output = [];

	    $departments = DepartmentParser::getDepartments();

	    $departmentsParsed = 0;
	    foreach($departments as $acronym) {

	        // if(in_array($acronym, $configuration['departmentsToSkip'])) {
	        //     echo "Skipping " . $acronym . "\n";
	        //     continue;
	        // }

	        if(env('PARSE_LIMIT_DEPARTMENTS', 0) && $departmentsParsed >= env('PARSE_LIMIT_DEPARTMENTS', 0)) {
	            break;
	        }

	        $startDate = date('Y-m-d H:i:s');
	        echo "Starting " . $acronym . " at ". $startDate . " \n";

	        $department = new DepartmentParser($acronym);

	        $department->parseDepartment();

	        // Rather than storing the whole works in memory,
	        // let's just save one department at a time in individual
	        // JSON files:

	        $directoryPath = env('PARSE_JSON_OUTPUT_FOLDER', 'generated-data') . '/' . $acronym;

	        // If the folder doesn't exist yet, create it:
	        // Thanks to http://stackoverflow.com/a/15075269/756641
	        if(! is_dir($directoryPath)) {
	            mkdir($directoryPath, 0755, true);
	        }


	        // Iterative check of json_encode
	        // Trying to catch encoding issues
	        if(file_put_contents($directoryPath . '/contracts.json', json_encode($department->contracts, JSON_PRETTY_PRINT))) {
	            echo "...saved.\n";
	        }
	        else {
	            // echo "STARTHERE: \n";
	            // var_export($departmentArray);

	            // echo "ENDHERE. \n";
	            echo "...failed.\n";

	            $newOutput = [];

	            $index = 0;
	            $limit = 1000000;

	            foreach($department->contracts as $key => $data) {
	                $index++;
	                if($index > $limit) {
	                    break;
	                }
	                $newOutput[$key] = $data;

	                echo $index;

	                if(json_encode($data, JSON_PRETTY_PRINT)) {
	                    echo " P\n";
	                }
	                else {
	                    echo " F\n";
	                    var_dump($key);
	                    var_dump($data);
	                    exit();
	                }

	            }

	        }


	        // var_dump($department->contracts);
	        // var_dump(json_encode($department->contracts, JSON_PRETTY_PRINT));
	        // $output[$acronym] = $department->contracts;

	        echo "Started " . $acronym . " at " . $startDate . "\n";
	        echo "Finished at ". date('Y-m-d H:i:s') . " \n\n";

	        $departmentsParsed++;

	    }

	}



}
