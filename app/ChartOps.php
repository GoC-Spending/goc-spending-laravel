<?php
namespace App;

use App\Helpers\Cleaners;
use App\Helpers\ContractDataProcessors;
use App\Helpers\Parsers;
use App\Helpers\Paths;
use App\Helpers\Miscellaneous;
use App\VendorData;
use GuzzleHttp\Client;
use XPathSelector\Selector;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ChartOps
{

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

    public static function arrayToChartJsSingle($id, $input, $params)
    {
        $valueColumn = '';

      // dd($params);

        $timeColumn = data_get($params, 'timeColumn');
        $stackGroupColumn = data_get($params, 'stackGroupColumn');

        $columns = AnalysisOps::createCsvHeaderKeys($input);
      // dd($timeColumn);
        foreach ($columns as $column) {
            if ($column != $timeColumn && $column != $stackGroupColumn) {
                $valueColumn = $column;
                break;
            }
        }

      // dd($valueColumn);
        $output = [];
        foreach ($input as $item) {
            $output[$item->$timeColumn] = $item->$valueColumn;
        }

        return self::generateChartJsTemplate($id, array_keys($output), array_values($output));
    }

    public static function arrayToChartJsStacked($id, $input, $params)
    {
      // Which column forms the "group", e.g. owner department
        $labelColumn = data_get($params, 'labelColumn');

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

      // General labels
        $axisLabels = $timeRange;

      // Get label groups
        $groupLabels = array_unique(data_get($input, "*.$labelColumn"));

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
            $label = data_get($item, $labelColumn, null);
            $timePeriod = data_get($item, $timeColumn, null);
            $value = data_get($item, $valueColumn, null);

          // dd($value);
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
