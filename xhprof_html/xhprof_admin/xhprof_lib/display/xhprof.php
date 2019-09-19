<?php
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/display/xhprof.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../../xhprof_lib/utils/xhprof_lib.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../../xhprof_lib/utils/callgraph_utils.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../../xhprof_lib/utils/xhprof_runs.php';

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
        global $totals, $base_path;
        if ($runs) {
            $arRuns = explode(',', $runs);
            $arSources = explode(',', $sources);
            $runsCount = count($arRuns);

            $isOneRun = $runsCount === 1;
            $url = XHProfRuns_Ol::getNewReportRunListLink();
            ?>
            <div class="sticky">
                <div style="float: right;font-weight:bold;"><a href="<?= $url ?>">View all available runs →</a></div>
                <?php
                if ($isOneRun) {
                    ?>
                    <div style="float: left;">Run: <b><?= $arRuns[0] ?></b></div>
                    <?php
                } else {
                    if (XHProfRuns_Ol::needPrintAverageByRequest() === false) {
                        $showHideWord = 'Show';
                        $averageValue = 1;
                    } else {
                        $showHideWord = 'Hide';
                        $averageValue = 0;
                    }
                    $showAverageHref = xhprof_render_link("{$showHideWord} average values", "$base_path/?" . http_build_query(xhprof_array_set($urlParams, 'average', $averageValue)));
                    ?>
                    <div style="float: right; margin-right: 20px;"><?= $showAverageHref ?></div>
                    <div style="float: left;">Each run is compared with the base run <b><?= $arRuns[0] ?></b></div>
                    <?php
                }

                ?>
                <div>&nbsp;</div>
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

        $runsInfoCount = count($arRunsInfo);
        if ($runsInfoCount === 0) {
            return;
        }
        if ($runsInfoCount === 1) {
            $title = 'Run info';
        } else {
            $title = 'Comparing runs info';
        }

        ?>
        <h3 align="center"><?= $title?></h3>
        <table border="1" cellpadding="2" cellspacing="1" width="100%" rules="rows" align="center" class="highlighted">
            <thead>
            <tr>
                <th>Date</th>
                <th>Run</th>
                <th>Namespace</th>
                <th>New report</th>
                <th>Original report</th>
                <th>Callgraph</th>
                <th title="Exclude run from comparing">Exclude</th>
                <th width="40%">Custom comment</th>
                <th>File size</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($arRunsInfo as $arRunInfo) {
                ?>
                <tr>
                    <td class="table-cell"><?= $arRunInfo['file_date'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['run'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['source'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['new_report_href'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['original_report_href'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['callgraph_href'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['exclude_href'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['comment'] ?></td>
                    <td class="table-cell"><?= $arRunInfo['file_size_formatted'] ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    protected static function printTableOfRuns($url_params, $arFlatTabs, $arSymbolTabs, $arRunsInfo, $arSources, $limit = 100)
    {
        global $base_path;
        global $sort_col;
        global $descriptions;
        global $stats;

        $size = count($arFlatTabs[0]);
        $desc = str_replace('<br>', ' ', $descriptions[$sort_col]);

        $title = '';
        if ($limit === 100) {
            $title = "Displaying top $limit functions: ";
            $funcLimitAnchor = 'display all';
            $allValue = 1;

        } else {
            $limit = $size;
            $funcLimitAnchor = 'display top 100 functions';
            $allValue = 0;

        }
        $display_link = xhprof_render_link(
            $funcLimitAnchor,
            "$base_path/?" .
            http_build_query(xhprof_array_set($url_params, 'all', $allValue)));

        $title .= "Sorted by <span class='sorted'>$desc</span> ";

        ?>
        <h3 align=center><?= $title ?> [ <?= $display_link ?> ]</h3>

        <table border=1 cellpadding=2 cellspacing=1 width="100%" rules="rows" align="center" class="highlighted">
            <?php
            $arMetrics = array_diff($stats, ['fn']);

            // Keep absolute metrics only
            $arMetricsWoPercents = [];
            foreach ($arMetrics as $metric) {
                if (false === strpos($metric, '%')) {
                    $arMetricsWoPercents[] = $metric;
                }
            }

            $printAverage = XHProfRuns_Ol::needPrintAverageByRequest() && count($arRunsInfo) > 1;
            self::printTableHeader($arRunsInfo, $arSources, $arMetricsWoPercents, $url_params, $printAverage);
            self::printTableBody($url_params, $arFlatTabs, $arSymbolTabs, $limit, $printAverage, $arMetricsWoPercents);
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
        echo "<link href='" . XHProfRuns_Ol::getRelativeUrlToOriginalDir() . "xhprof_admin/xhprof_html/css/xhprof.css' rel='stylesheet' type='text/css' />";
    }

    protected static function printTableHeader(array $arRunsInfo, array $arSources, array $arMetrics, $url_params, $printAverage = true)
    {
        global $sortable_columns, $base_path, $sort_col;

        $runsCount = count($arRunsInfo);
        $colspan = $printAverage ? $runsCount + 1 : $runsCount;

        $isPrintRunIdInHeader = $runsCount > 1;
        ?>
        <thead>
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
                $cssClass = $sort_col === $stat ? ' sorted' : '';
                $thColspan = $colspan > 1 ? ' colspan="' . $colspan . '" ' : '';
                ?>
                <th class="first-header-row vwbar left-separator<?= $cssClass ?>" <?= $thColspan ?>><?= $header ?></th>
                <?php
            }
            ?>
        </tr>
        <?php
        if ($isPrintRunIdInHeader) {
            ?>
            <tr>
                <?php
                foreach ($arMetrics as $stat) {
                    for ($i = 0; $i < $runsCount; $i++) {
                        $runInfoWrapped = self::getWrappedTitle($arRunsInfo[$i], 5);

                        $additionalClass = '';
                        if ($i === 0) {
                            $additionalClass = ' left-separator';
                        }

                        ?>
                        <th class="second-header-row vwbar<?= $additionalClass ?>"><?= $runInfoWrapped ?></th>
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
            <?php
        }
        ?>
        </thead>
        <?php
    }

    protected static function printFunctionMetric($url_params, $arXhprofData, $functionName, $metricCode, $printAverage)
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
            $tdContent = str_replace('</td>', ' ' . $deltaHtml . '</td>', $tdContent);
            if ($i === 0) {
                $tdContent = str_replace('class="', 'class="left-separator ', $tdContent);
            }

            // set link for current sorted column cells only
            if ($sort_col === $metricCode) {
                $runParam = explode(',', $url_params['run'])[$i];
                $sourceParam = explode(',', $url_params['source'])[$i];
                $funcUrlParams = ['run' => $runParam, 'source' => $sourceParam];

                $href = XHProfRuns_Ol::getRelativeUrlToOriginalDir() . '?' . http_build_query(xhprof_array_set($funcUrlParams, 'symbol', $functionName));

                $pattern = '#(<td[^>]+>)(.+?)([\s]+<.+)#sui';
                $tdContent = preg_replace($pattern, "$1<a class='quite-url' target='_blank' href='{$href}'>$2</a>$3", $tdContent);
            }

            echo $tdContent;
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

    /**
     * @param $url_params
     * @param $arFlatTabs
     * @param $arSymbolTabs
     * @param $limit
     * @param $printAverage
     * @param array $arMetricsWoPercents
     */
    protected static function printTableBody($url_params, $arFlatTabs, $arSymbolTabs, $limit, $printAverage, array $arMetricsWoPercents)
    {
        $arFlatTab = $arFlatTabs[0];
        foreach ($arFlatTab as $arRun) {
            if ($limit <= 0) {
                break;
            }
            $functionName = $arRun['fn'];
            $runParam = explode(',', $url_params['run'])[0];
            $sourceParam = explode(',', $url_params['source'])[0];
            $funcUrlParams = ['run' => $runParam, 'source' => $sourceParam];
            $href = XHProfRuns_Ol::getRelativeUrlToOriginalDir() . '?' . http_build_query(xhprof_array_set($funcUrlParams, 'symbol', $functionName));
            ?>
            <tr>
                <td><?= xhprof_render_link($functionName, $href) ?></td>
                <?php
                foreach ($arMetricsWoPercents as $metric) {
                    self::printFunctionMetric($url_params, $arSymbolTabs, $functionName, $metric, $printAverage);
                }
                ?>
            </tr>
            <?php
            $limit--;
        }
    }

    public static function printHeadSection()
    {
        echo '<head><title>XHProf Admin: Hierarchical Profiler Report</title>';
        self::includeCss();
        echo '</head>';
    }

    public static function printFooter()
    {
        ?>
        <div class="footer"><span>&copy; Sergey Oleynikov, 2019</span>. <span><a
                        href="https://github.com/SergeyRock/xhprof-admin" target="_blank">https://github.com/SergeyRock/xhprof-admin</a></span>
        </div>
        <?php
    }

    public static function printOriginalReportLink()
    {
        ?>
        <a target="_blank" href="<?= XHProfRuns_Ol::getRelativeUrlToOriginalDir() ?>">Go to original run list report</a>
        <?php
    }
}