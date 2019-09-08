<?php
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/display/xhprof.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../xhprof_lib/utils/xhprof_lib.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../xhprof_lib/utils/callgraph_utils.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../xhprof_lib/utils/xhprof_runs.php';

class Ol_Xhprof_Report
{
    /**
     *
     * @param XHProfRuns_Ol $obXhprofRuns
     * @param $urlParams
     * @param $sources
     * @param $runs
     * @param $symbol
     * @param $sort
     */
    public static function displayXHProfReportCompare($obXhprofRuns, $urlParams, $sources, $runs, $symbol, $sort)
    {
        global $totals;
        if ($runs) {
            $arRuns = explode(",", $runs);
            $arSources = explode(",", $sources);
            $runsCount = count($arRuns);
            ?>
            <div class="sticky">`
                <div style="float: left;">Each run is compared with the base run <b><?= $arRuns[0] ?></b></div>
                <div style="float: right;font-weight:bold;"><a href="./../xhprof_html/index.php">View all available runs →</a></div>
            </div>
            <?php

            self::printRunsInfo($obXhprofRuns, $arRuns, $arSources);

            $description = '';
            $xhprofData = $arFlatSymbolTabs = $arSymbolTabs = [];
            for ($i = 0; $i < $runsCount; $i++) {
                $xhprofData[] = $obXhprofRuns->get_run($arRuns[$i], $arSources[$i], $description);
            }

            foreach ($xhprofData as $data) {
                init_metrics($data, $symbol, $sort, false);

                if (!empty($symbol)) {
                    $data = xhprof_trim_run($data, [$symbol]);
                }

                $srSymbolTab = xhprof_compute_flat_info($data, $totals);
                $arFlatSymbolTab = self::convertXhprofArrayToFlatTable($srSymbolTab);
                usort($arFlatSymbolTab, 'sort_cbk');

                $arSymbolTabs[] = $srSymbolTab;
                $arFlatSymbolTabs[] = $arFlatSymbolTab;
            }

            $limit = 99999;
            if (empty($urlParams['all'])) {
                $limit = 100;  // display only limited number of rows
            }

            self::printTableOfRuns($urlParams, $arFlatSymbolTabs, $arSymbolTabs, $arRuns, $arSources, $limit);
        } elseif (method_exists($obXhprofRuns, 'list_runs')) {
            $obXhprofRuns->list_runs();
        }
    }

    /**
     * @param XHProfRuns_Ol $obXhprofRuns
     */
    protected static function printRunsInfo($obXhprofRuns, $arRuns, $arSources)
    {
        $arRunsInfo = $obXhprofRuns->getRunsInfo($arRuns, $arSources);

        if (empty($arRunsInfo)) {
            return;
        }
        ?>
        <h3 align="center">Compared runs info</h3>
        <table border="1" cellpadding="2" cellspacing="1" rules="rows" bordercolor="#bdc7d8" align="center"
               width="100%">
            <tr bgcolor="#bdc7d8">
                <th>Date</th>
                <th>Run</th>
                <th>Namespace</th>
                <th>Custom comment</th>
                <th>File size</th>
            </tr>
            <?php
            foreach ($arRunsInfo as $arRunInfo) {
                $href = XHProfRuns_Ol::getRunReportLink($arRunInfo['run'], $arRunInfo['source']);
                $url = "<a href='{$href}'>{$arRunInfo['file_date']}</a>";

                ?>
                <tr>
                    <td class="table-cell"><?= $url ?></td>
                    <td class="table-cell"><?= $arRunInfo['run'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['source'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['comment'] ?></td>
                    <td class="table-cell"><?= xhprof_count_format($arRunInfo['file_size']) ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
        <?php
    }

    protected static function printTableOfRuns($url_params, $arFlatTabs, $arSymbolTabs, $arRunsInfo, $arSources, $limit = 100, $printAverage = true)
    {
        self::includeCss();

        global $base_path;
        global $sort_col;
        global $descriptions;

        $size = count($arFlatTabs[0]);
        $desc = str_replace('<br>', ' ', $descriptions[$sort_col]);
        $display_link = "";
        if ($limit === 100) {
            $title = "Displaying top $limit functions: Sorted by $desc ";
            $display_link = xhprof_render_link(
                '[ display all ]',
                "$base_path/?" .
                http_build_query(xhprof_array_set($url_params, 'all', 1)));
        } else {
            $limit = $size;
            $title = "Sorted by $desc";
        }

        ?>
        <h3 align=center><?= $title . $display_link ?></h3>

        <table border=1 cellpadding=2 cellspacing=1 width="100%" rules=rows bordercolor="#bdc7d8" align=center>
            <?php

            $possible_metrics = array_keys(xhprof_get_possible_metrics());
            $possible_metrics[] = 'ct';
            $arMetrics = ['ct', 'wt', 'mu',];
            // leave possible metrics only
            $arMetrics = array_intersect($arMetrics, $possible_metrics);

            self::printTableHeader($arRunsInfo, $arSources, $arMetrics, $url_params, $printAverage);

            $arFlatTab = $arFlatTabs[0];
            foreach ($arFlatTab as $arRun) {
                if ($limit <= 0) {
                    break;
                }
                $functionName = $arRun['fn'];
                $runParam = explode(',', $url_params['run'])[0];
                $sourceParam = explode(',', $url_params['source'])[0];
                $funcUrlParams = ['run' => $runParam, 'source' => $sourceParam];
                $href = XHProfRuns_Ol::BASE_URL . '?' . http_build_query(xhprof_array_set($funcUrlParams, 'symbol', $functionName));
                ?>
                <tr>
                    <td><?= xhprof_render_link($functionName, $href) . print_source_link($arRun) ?></td>
                    <?php
                    foreach ($arMetrics as $metric) {
                        self::printFunctionMetric($arSymbolTabs, $functionName, $metric, $printAverage);
                    }
                    ?>
                </tr>
                <?php
                $limit--;
            }
            ?>

        </table>
        <?php
        // let's print the display all link at the bottom as well...
        if ($display_link) {
            ?>
            <div style="text-align: left; padding: 2em"><?= $display_link ?></div>
            <?php
        }
    }

    public static function includeCss()
    {
        // style sheets
        echo "<link href='./../xhprof_html/css/xhprof.css' rel='stylesheet' type='text/css' />";
    }

    protected static function printTableHeader(array $arRunsInfo, array $arSources, array $arMetrics, $url_params, $printAverage = true)
    {
        global $vwbar, $sortable_columns, $base_path;
        $runsCount = count($arRunsInfo);
        $colspan = $printAverage ? $runsCount + 1 : $runsCount;
        ?>
        <thead class="sticky-header">
        <tr>
            <th class="first-header-row" rowspan="2">Function name</th>
            <?php
            foreach ($arMetrics as $stat) {
                $desc = str_replace('<br>', ' ', stat_description($stat));
                if (array_key_exists($stat, $sortable_columns)) {
                    $href = "$base_path/?" . http_build_query(xhprof_array_set($url_params, 'sort', $stat));
                    $header = xhprof_render_link($desc, $href);
                } else {
                    $header = $desc;
                }

                ?>
                <th class="first-header-row vwbar" colspan="<?= $colspan ?>"><?= $header ?></th>
                <?php
            }
            ?>
        </tr>
        <tr>
            <?php
            foreach ($arMetrics as $stat) {
                for ($i = 0; $i < $runsCount; $i++) {

                    $runInfoWrapped = self::getWrappedTitle($arRunsInfo[$i], 5);
                    $sourceInfo = htmlentities($arSources[$i]);

                    $href = XHProfRuns_Ol::getRunReportLink($arRunsInfo[$i], $sourceInfo);
                    $url = "<a title='{$sourceInfo}' href='{$href}'>{$runInfoWrapped}</a>";

                    ?>
                    <th class="second-header-row vwbar"><?= $url ?></th>
                    <?php
                }
                if ($printAverage) {
                    ?>
                    <th class="second-header-row vwbar">Average</th>
                    <?php
                }
            }
            ?>
        </tr>
        </thead>
        <?php
    }

    protected static function printFunctionMetric($arXhprofData, $functionName, $metricCode, $printAverage)
    {
        global $sort_col, $format_cbk;
        $runsCount = count($arXhprofData);

        $value0 = 0;
        $average = 0;
        for ($i = 0; $i < $runsCount; $i++) {
            $deltaHtml = '';
            $value = $arXhprofData[$i][$functionName][$metricCode];
            $average += $value;
            if ($i === 0) {
                $value0 = $value;
            } elseif ((int)$value !== 0) {
                $deltaPercent = -round(100 - $value / $value0 * 100);
                if ($deltaPercent > 0) {
                    $deltaHtml = '<span class="worse">+' . $deltaPercent . ' %</span>';
                } elseif ($deltaPercent < 0) {
                    $deltaHtml = '<span class="better">' . $deltaPercent . ' %</span>';
                }
            }
            ob_start();
            print_td_num($value, $format_cbk[$metricCode], $sort_col === $metricCode);
            $tdContent = ob_get_contents();
            ob_end_clean();
            echo str_replace('</td>', ' ' . $deltaHtml . '</td>', $tdContent);
        }
        if ($printAverage) {
            $average /= $runsCount;
            print_td_num($average, $format_cbk[$metricCode], $sort_col === $metricCode);
        }
    }

    protected static function convertXhprofArrayToFlatTable(array $symbol_tab)
    {
        $flat_data = [];
        foreach ($symbol_tab as $symbol => $info) {
            $tmp = $info;
            $tmp["fn"] = $symbol;
            $flat_data[] = $tmp;
        }
        return $flat_data;
    }

    protected static function getWrappedTitle($title, $maxLineChars = 4)
    {
        $arLines = str_split($title, $maxLineChars);
        return implode("\n", $arLines);
    }
}