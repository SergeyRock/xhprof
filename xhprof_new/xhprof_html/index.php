<?php

// by default assume that xhprof_html & xhprof_lib directories
// are at the same level.
$GLOBALS['OL_XHPROF_LIB_ROOT'] = __DIR__ . '/../xhprof_lib';

require_once $GLOBALS['OL_XHPROF_LIB_ROOT'].'/display/xhprof.php';
include_once $GLOBALS['OL_XHPROF_LIB_ROOT'].'/../../xhprof_lib/display/xhprof.php';
require_once $GLOBALS['OL_XHPROF_LIB_ROOT'].'/utils/xhprof_runs.php';

// param name, its type, and default value
$params = array('run'        => array(XHPROF_STRING_PARAM, ''),
                'wts'        => array(XHPROF_STRING_PARAM, ''),
                'symbol'     => array(XHPROF_STRING_PARAM, ''),
                'sort'       => array(XHPROF_STRING_PARAM, 'wt'), // wall time
                'run1'       => array(XHPROF_STRING_PARAM, ''),
                'run2'       => array(XHPROF_STRING_PARAM, ''),
                'source'     => array(XHPROF_STRING_PARAM, 'xhprof'),
                'all'        => array(XHPROF_UINT_PARAM, 0),
                );

// pull values of these params, and create named globals for each param
xhprof_param_init($params);

/* reset params to be a array of variable names to values
   by the end of this page, param should only contain values that need
   to be preserved for the next page. unset all unwanted keys in $params.
 */
foreach ($params as $k => $v) {
  $params[$k] = $$k;

  // unset key from params that are using default values. So URLs aren't
  // ridiculously long.
  if ($params[$k] == $v[1]) {
    unset($params[$k]);
  }
}

echo "<html>";

echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
xhprof_include_js_css('./../../xhprof_html');
Ol_Xhprof_Report::includeCss();
echo "</head>";

echo "<body>";

$vbar  = ' class="vbar"';
$vwbar = ' class="vwbar"';
$vwlbar = ' class="vwlbar"';
$vbbar = ' class="vbbar"';
$vrbar = ' class="vrbar"';
$vgbar = ' class="vgbar"';

$obXhprofRuns = new XHProfRuns_Ol();

if (array_key_exists('compare_runs', $_REQUEST)) {
    XHProfRuns_Ol::goToCompareRunsByRequest();
} elseif (array_key_exists('delete_runs', $_REQUEST)) {
    $obXhprofRuns->deleteSelectedRunsByRequest();
} elseif (array_key_exists('save_comments', $_REQUEST)) {
    $obXhprofRuns->saveCustomCommentsByRequest();
}

Ol_Xhprof_Report::displayXHProfReportCompare($obXhprofRuns, $params, $source, $run, $symbol, $sort);


echo "</body>";
echo "</html>";
