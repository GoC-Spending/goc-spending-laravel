<?php
namespace App;

use App\Helpers\Cleaners;
use App\Helpers\ContractDataProcessors;
use App\Helpers\Parsers;
use App\Helpers\Paths;
use App\Helpers\Miscellaneous;
use GuzzleHttp\Client;
use App\AnalysisOps;
use XPathSelector\Selector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ChartOps
{

    public static function buildAnalysisTemplate()
    {
        return view('charts/_analysis', ['config' => AnalysisOps::$config])->render();
    }

    public static function saveAnalysisTemplate()
    {
        $html = self::buildAnalysisTemplate();
        $filepath = storage_path() . '/' . env('STORAGE_RELATIVE_WEBSITE_ANALYSIS_CONTENT', '../../goc-spending-website-v1/site/content/analysis.html');
        file_put_contents($filepath, $html);
    }


    public static function run($id, $dataMethod, $dataMethodParams = [], $chartMethod = '', $chartMethodParams = [])
    {

        $id = self::cleanHtmlId($id);

        if ($dataMethodParams) {
          // So far, all the analysis functions take one parameter at most (e.g. department or vendor)
          // May need to revisit this for multiple parameters (as is the case for chart functions)
            $data = AnalysisOps::$dataMethod($dataMethodParams);
        } else {
            $data = AnalysisOps::$dataMethod();
        }

        $html = ChartOps::$chartMethod($id, $data, $chartMethodParams);
        return $html;
    }


  // Todo - integrate this with Laravel views, and save output to a specific location
    public static function saveChartHtml($id, $html)
    {
        $id = self::cleanHtmlId($id);
        echo "Saving $id\n";
        echo $html . "\n\n";
    }

    public static function generateConfigYearRange()
    {
        $years = range(AnalysisOps::$config['startYear'], AnalysisOps::$config['endYear']);
        return $years;
    }

    public static function generateConfigFiscalRange()
    {
        $output = [];
        $years = range(AnalysisOps::$config['startYear'], AnalysisOps::$config['endYear']);
        foreach ($years as $year) {
          // 201819-Q2
            $fiscalYear = $year . substr(intval($year) + 1, 2, 2);
            foreach (range(1, 4) as $quarter) {
                $output[] = $fiscalYear . '-Q' . $quarter;
            }
        }
        return $output;
    }

  // Todo - remove this temporary function
    public static function chartExport()
    {
        $years = self::generateConfigYearRange();
        dd(json_encode($years));
    }

    public static function cleanHtmlId($id)
    {
        $id = str_replace('/', '-', $id);
        return Str::slug($id);
    }

    public static function deIndexArrayTopLevel($array)
    {
        $output = [];
        foreach ($array as $key => $values) {
            $output[] = $values;
        }
        return $output;
    }

    public static function generateChartJsTemplate($id, $labelsArray, $valuesArray, $type = 'year-single', $options = '')
    {
        return '<canvas id="' . self::cleanHtmlId($id) . '" width="400" height="200" data-chart-type="' . $type .'" data-chart-options="' . $options . '" data-chart-range="' . e(json_encode($labelsArray)) . '" data-chart-values="' . e(json_encode($valuesArray)) . '"></canvas>' . "\n";
    }

    public static function getColor($colorMapping, $keyword, $index, $border = 0)
    {
        if ($colorMapping == 'keyword') {
            return Miscellaneous::getColorByKeyword($keyword, $border);
        } elseif ($colorMapping == 'index') {
            return Miscellaneous::getColorByIndex($index, $border);
        }
    }

    public static function arrayToChartJsStacked($id, $input, $params)
    {
      // Which column forms the "group", e.g. owner department
        $labelColumn = data_get($params, 'labelColumn');
        
        // In the case of a "single-entry stacked chart", AKA a regular bar chart:
        $isSingleEntry = data_get($params, 'isSingleEntry', 0);
        $singleEntryLabel = data_get($params, 'singleEntryLabel', 'Values');

        $timeColumn = data_get($params, 'timeColumn');
        $valueColumn = data_get($params, 'valueColumn');
        $useConfigYears = data_get($params, 'useConfigYears', 0);
        $useConfigFiscal = data_get($params, 'useConfigFiscal', 0);
        $defaultValue = data_get($params, 'defaultValue', 0);
        $chartOptions = data_get($params, 'chartOptions', '');
        $colorMapping = data_get($params, 'colorMapping', 'index');

        $timeRange = [];
        $output = [];
        $axisLabels = [];

        if ($useConfigYears) {
            $timeRange = self::generateConfigYearRange();
        }
        if ($useConfigFiscal) {
            $timeRange = self::generateConfigFiscalRange();
        }
        // TODO - else, retrieve the timeRange from the timeColumn entries

      // General labels
        $axisLabels = $timeRange;

      // Get label groups
        if ($isSingleEntry) {
            $groupLabels = [
            $singleEntryLabel,
            ];
        } else {
            $groupLabels = array_unique(data_get($input, "*.$labelColumn"));
        }
        

        $colorIndex = 0;
        foreach ($groupLabels as $groupLabel) {
            $output[$groupLabel] = [
            'label' => Cleaners::generateLabelText($groupLabel),
            'backgroundColor' => self::getColor($colorMapping, $groupLabel, $colorIndex),
            'borderColor' => self::getColor($colorMapping, $groupLabel, $colorIndex, 1),
            'data' => [],
            ];
            $colorIndex++;
            foreach ($timeRange as $timeUnit) {
                $output[$groupLabel]['data'][$timeUnit] = $defaultValue;
            }
        }

        foreach ($input as $item) {
            if ($isSingleEntry) {
                $label = $singleEntryLabel;
            } else {
                $label = data_get($item, $labelColumn, null);
            }
            
            $timePeriod = data_get($item, $timeColumn, null);
            $value = data_get($item, $valueColumn, null);

          
            if ($label && $timePeriod && $value) {
                if (isset($output[$label]['data'][$timePeriod])) {
                    // dd('yes');
                    $output[$label]['data'][$timePeriod] = $value;
                }
            }
        }

        

      // Remove the indexes for compatibility with Chart.js's datasets object:
        foreach ($output as $label => &$values) {
            $values['data'] = self::deIndexArrayTopLevel($values['data']);
        }
        $output = self::deIndexArrayTopLevel($output);

        // dd($output);

        return self::generateChartJsTemplate($id, $axisLabels, $output, 'year-stacked', $chartOptions);
    }

  // Stacked chart but, instead of using "group by" for stacking, take entries from different FILTER columns for a given time period
    public static function arrayToChartJsStackedTranspose($id, $input, $params)
    {

      // 'arrayToChartJsStackedTranspose', [
      //   'useConfigYears' => 1,
      //   'valueColumns' => ['total_contracts', 'total_amendments'],
      //   'timeColumn' => 'source_year',
      //   ]);


        $timeColumn = data_get($params, 'timeColumn');
        // e.g. total_contracts, total_amendments
        $valueColumns = data_get($params, 'valueColumns', []);
        $useConfigYears = data_get($params, 'useConfigYears', 0);
        $useConfigFiscal = data_get($params, 'useConfigFiscal', 0);
        $defaultValue = data_get($params, 'defaultValue', 0);
        $chartOptions = data_get($params, 'chartOptions', '');
        $colorMapping = data_get($params, 'colorMapping', 'index');

        $timeRange = [];
        $output = [];
        $axisLabels = [];

        if ($useConfigYears) {
            $timeRange = self::generateConfigYearRange();
        }
        if ($useConfigFiscal) {
            $timeRange = self::generateConfigFiscalRange();
        }

      // General labels
        $axisLabels = $timeRange;

      // Get label groups
        $groupLabels = $valueColumns;

        $colorIndex = 0;
        foreach ($groupLabels as $groupLabel) {
            $output[$groupLabel] = [
            'label' => Cleaners::generateLabelText($groupLabel),
            'backgroundColor' => self::getColor($colorMapping, $groupLabel, $colorIndex),
            'borderColor' => self::getColor($colorMapping, $groupLabel, $colorIndex, 1),
            'data' => [],
            ];
            $colorIndex++;
            foreach ($timeRange as $timeUnit) {
                $output[$groupLabel]['data'][$timeUnit] = $defaultValue;
            }
        }

      

        foreach ($input as $item) {
            foreach ($valueColumns as $labelColumn) {
                $timePeriod = data_get($item, $timeColumn, null);
                $value = data_get($item, $labelColumn, null);

            // dd($value);
                if ($timePeriod && $value) {
                    if (isset($output[$labelColumn]['data'][$timePeriod])) {
                        // dd('yes');
                        $output[$labelColumn]['data'][$timePeriod] = $value;
                    }
                }
            }
        }

      // Remove the indexes for compatibility with Chart.js's datasets object:
        foreach ($output as $label => &$values) {
            $values['data'] = self::deIndexArrayTopLevel($values['data']);
        }
        $output = self::deIndexArrayTopLevel($output);

        return self::generateChartJsTemplate($id, $axisLabels, $output, 'year-stacked', $chartOptions);
    }
}
