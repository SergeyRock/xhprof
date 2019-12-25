<?php

require_once $GLOBALS['OL_XHPROF_LIB_ROOT'] . '/../../../xhprof_lib/utils/xhprof_runs.php';

class XHProfRuns_Ol extends XHProfRuns_Default
{
    const CUSTOM_COMMENTS_FILE_SUFFIX = 'comment';
    const SESSION_FILE_SUFFIX = 'session';
	protected $dir;
	protected $suffix = 'xhprof';

    public static function getWrappedTitle($title, $maxLineChars = 5)
    {
        $arLines = str_split($title, $maxLineChars);
        return implode("\n", $arLines);
    }

    public static function getRelativeUrlToOriginalDir()
    {
        $dir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $dir = str_replace('xhprof_admin/xhprof_html', '', $dir);
        if ($dir !== '/') {
            $dir .= '/';
        }

        return $dir;
    }

	public function __construct($dir = null)
	{
		parent::__construct($dir);

		if (empty($dir)) {
			$dir = ini_get("xhprof.output_dir");
			if (empty($dir)) {
                $dir = sys_get_temp_dir();
                if (empty($dir)) {
                    $dir = "/tmp";
                }
			}
		}
		$this->dir = $dir;
	}

	function list_runs()
	{
		if (is_dir($this->dir)) {
			?>
            <div>
                <form action="./" method="post">
                    <div class="sticky" >
                        <table width="100%" rule="rows">
                            <tr>
                                <td style="vertical-align: top">
                                    <div class="control-element">
                                        <input name="compare_runs" title="Choose runs to compare" type="submit" value="Compare runs"> –
                                        1) select <b>runs</b>;
                                        2) set <b>sort</b> value (the base run must have the smallest value);
                                        3) Set  <label for="calc_average"><b>calc average</b></label> →  <input type="checkbox" value="Y" name="calc_average" id="calc_average"> (if needed)
                                    </div>
                                    <div class="control-element">
                                        <input name="diff_runs" title="Choose runs to diff" type="submit" value="Diff runs"> –
                                        1) select two <b>runs</b> (namespaces of both runs must be the same);
                                        2) set <b>sort</b> value
                                    </div>
                                    <div class="control-element">
                                        <input name="aggregate_runs" title="Choose runs to aggregate" type="submit" value="Aggregate runs"> –
                                        1) select <b>runs</b> (namespaces of all runs must be the same);
                                        2) set <b>sort</b> value; 3) set <b>weight</b> of run (if needed)
                                    </div>
                                </td>
                                <td style="vertical-align: top">
                                    <div class="control-element" style="text-align: right;" >
                                        <input name="delete_runs" id="delete_runs" style="display:none;" type="submit" value="Delete selected runs">
                                        <input onclick="return confirm('Are you sure?') ? document.getElementById('delete_runs').click() : false;" type="button" value="Delete selected runs">
                                    </div>
                                    <div class="control-element" style="text-align: right;">
                                        <input name="save_comments" type="submit" value="Save custom comments">
                                    </div>
                                    <div class="control-element" style="text-align: right;">
                                        <?php
                                        Ol_Xhprof_Report::printOriginalReportLink();
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <h3 align="center">Available runs</h3>
                    <table border="1" cellpadding="2" cellspacing="1" width="100%" rules="rows" align="center" class="highlighted">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Run</th>
                                <th>Namespace</th>
                                <th>New report</th>
                                <th>Original report</th>
                                <th>Callgraph</th>
                                <th>Sort</th>
                                <th>Weight, %</th>
                                <th width="40%">Custom comment</th>
                                <th>File size</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
						$files = glob("{$this->dir}/*.{$this->suffix}");
						usort($files, function($a, $b){return filemtime($b) - filemtime($a);});
						$i = count($files) * 10;
						foreach ($files as $file) {
						    $i -= 10;
							$this->printHtmlRow($file, $i);
						}
						?>
                        </tbody>
                    </table>
                </form>
            </div>
			<?php
		} else {
		    echo "Folder <b>{$this->dir}</b> is not found";
        }
	}

	public function getRunsInfo($arRuns, $arSources)
    {
        $runsCount = count($arRuns);
        $arRunInfos = [];
        for ($i = 0; $i < $runsCount; $i++) {
            $arRunInfo = [
                'run' => $arRuns[$i],
                'run_wrapped' => self::getWrappedTitle($arRuns[$i], 5),
                'source' => $arSources[$i],
                'run_source' => $arRuns[$i] . '.' . $arSources[$i],
                'file' => $this->getRunFileName($arRuns[$i], $arSources[$i]),
                'comment_file' => $this->getRunCommentFileName($arRuns[$i], $arSources[$i]),
                'session_file' => $this->getRunSessionFileName($arRuns[$i], $arSources[$i]),
                'original_report_url' => self::getOriginalReportRunLink($arRuns[$i], $arSources[$i]),
                'new_report_url' => self::getNewReportRunLink($arRuns[$i], $arSources[$i]),
                'callgraph_url' => self::getCallgraphLink($arRuns[$i], $arSources[$i]),
            ];

            $arRunInfo['original_report_href'] = "<a target='_blank' href='{$arRunInfo['original_report_url']}'>view original report</a>";
            $arRunInfo['new_report_href'] = "<a href='{$arRunInfo['new_report_url']}'>view new report</a>";
            $arRunInfo['callgraph_href'] = "<a target='_blank' href='{$arRunInfo['callgraph_url']}'>view callgraph</a>";

            if(file_exists($arRunInfo['file'])) {
                $arRunInfo['file_date'] = date('Y-m-d H:i:s', filemtime($arRunInfo['file']));
                $arRunInfo['file_size'] = filesize($arRunInfo['file']);
                $arRunInfo['file_size_formatted'] = xhprof_count_format($arRunInfo['file_size']);
            }
            if(file_exists($arRunInfo['comment_file'])) {
                $arRunInfo['comment'] = json_decode(file_get_contents($arRunInfo['comment_file']), true);
            }
            if (file_exists($arRunInfo['session_file'])) {
                $arRunInfo['session'] = htmlspecialchars($this->getRunSessionInfo($arRunInfo['session_file']));
            }

            // additional highlighted source part
            $search = "/(.+)_(page)(.+)_(userId)_([\d]+)/ui";
            $replace = "[<span style='color:red'>$2</span>=<b>###$3###</b>] [<span style='color:green'>$4</span>=<b>$5</b>]";
            $highlightedSource = preg_replace($search, $replace, $arSources[$i]);

            $search = "/###(.+)###/su";
            if (preg_match($search, $highlightedSource, $matches)) {
                $matches[1] = str_replace('_', '/', $matches[1]);
                $highlightedSource = preg_replace($search, $matches[1], $highlightedSource);
            }

            if ($highlightedSource === $arSources[$i]) {
                $highlightedSource = '';
            }

            // prepare url for excluding run from compare report
            parse_str($_SERVER['QUERY_STRING'], $arUrl);
            $arUrl['run'] = explode(',', $arUrl['run']);
            $arUrl['source'] = explode(',', $arUrl['source']);

            unset($arUrl['run'][$i], $arUrl['source'][$i]);

            $arUrl['run'] = implode(',', $arUrl['run']);
            $arUrl['source'] = implode(',', $arUrl['source']);
            $arRunInfo['exclude_url'] = self::getNewReportRunLink($arUrl['run'], $arUrl['source'], $arUrl['average']);
            $arRunInfo['exclude_href'] = "<a href='{$arRunInfo['exclude_url']}'>exclude</a>";

            $arRunInfo['$highlighted_source'] = $highlightedSource;

            $arRunInfos[] = $arRunInfo;
        }
        return $arRunInfos;
    }

    public static function getOriginalReportRunLink($run, $source)
    {
        return self::getRelativeUrlToOriginalDir() . '?run=' . htmlentities($run) . '&source=' . htmlentities($source);
    }

    public static function getNewReportRunLink($run, $source, $average = 0)
    {
        return self::getRelativeUrlToOriginalDir() . 'xhprof_admin/xhprof_html/?run=' . htmlentities($run) . '&source=' . htmlentities($source) . '&average=' . htmlentities($average);
    }

    public static function getNewReportRunListLink()
    {
        return self::getRelativeUrlToOriginalDir() . 'xhprof_admin/xhprof_html/';
    }

    public static function getCallgraphLink($run, $source)
    {
        return self::getRelativeUrlToOriginalDir() . 'callgraph.php?run=' . htmlentities($run) . '&source=' . htmlentities($source);
    }

    public static function getDiffRunsReportLink($run1, $run2, $source)
    {
        return self::getRelativeUrlToOriginalDir() . '?run1=' . htmlentities($run1) . '&run2=' . htmlentities($run2) . '&source=' . htmlentities($source);
    }

    public static function getAggregateRunsReportLink($run, $source, $wts)
    {
        $url = self::getOriginalReportRunLink($run, $source);
        if ($wts) {
            $url .= '&wts=' . $wts;
        }
        return $url;
    }

    protected function printHtmlRow($fileName, $sort)
    {
        list($run, $source) = explode('.', basename($fileName));

        $arRunInfos = $this->getRunsInfo([$run], [$source]);
        $arRunInfo = $arRunInfos[0];
        $runId = $arRunInfo['run_source'];
        ?>
        <tr title="<?= $arRunInfo['session'] ?>">
            <td><?= $arRunInfo['file_date'] ?></td>
            <td>
                <input type="checkbox" value="<?= $runId ?>" name="runs[]" id="<?= $runId ?>">
                <label for="<?= $runId ?>"><?= htmlentities($arRunInfo['run']) ?></label>
            </td>
            <td><?= htmlentities($arRunInfo['source']) ?><br><?= $arRunInfo['highlighted_source'] ?></td>
            <td><?= $arRunInfo['new_report_href'] ?></td>
            <td><?= $arRunInfo['original_report_href'] ?></td>
            <td><?= $arRunInfo['callgraph_href'] ?></td>
            <td><input type="number" value="<?= $sort ?>" name="sort[<?=$runId?>]" step="10" autocomplete="off"></td>
            <td><input type="number" value="0" min="0" max="100"  name="weight[<?=$runId?>]" step="10" autocomplete="off" class="weight"></td>
            <td>
                <textarea name="comment[<?=$runId?>]" rows="2" class="custom_comment"><?= htmlentities($arRunInfo['comment']) ?></textarea>
            </td>
            <td><?= $arRunInfo['file_size_formatted'] ?></td>
        </tr>
        <?php
    }

    protected function getRunSessionInfo($fileName)
    {
        if (!file_exists($fileName)) {
            return '';
        }

        return trim(json_decode(file_get_contents($fileName)));
    }

	protected static function getRunsSourcesSortsWeightsFromRequestSorted()
    {
        $arRuns = $arSources = $arSorts = [];
        foreach ($_REQUEST['runs'] as $runAndSource) {
            list($run0, $source0) = explode('.', $runAndSource);
            $arRuns[] = $run0;
            $arSources[] = $source0;
            $arSorts[] = $_REQUEST['sort'][$runAndSource];
            $arWeights[] = $_REQUEST['weight'][$runAndSource];
        }

        // sort runs by sort values in asc order
        array_multisort($arSorts, $arRuns, $arSources, $arWeights);
        return [$arRuns, $arSources, $arSorts, $arWeights];
    }

    public static function goToDiffRunsByRequest()
    {
        list($arRuns, $arSources) = self::getRunsSourcesSortsWeightsFromRequestSorted();

        if (count($arRuns) !== 2) {
            echo '<div class="error">Only two runs must be selected.</div>';
            return false;
        }

        $arSources = array_unique($arSources);
        if (count($arSources) !== 1) {
            echo '<div class="error">Runs can be diffed in the same namespace only.</div>';
            return false;
        }

        $redirectUrl = self::getDiffRunsReportLink($arRuns[0], $arRuns[1], $arSources[0]);

        header('HTTP/1.1 301 Moved Permanently');
        header("Location: $redirectUrl");
        exit;
    }

    public static function goToAggregateRunsByRequest()
    {
        list($arRuns, $arSources, $arSorts, $arWeights) = self::getRunsSourcesSortsWeightsFromRequestSorted();

        if (count($arRuns) < 2) {
            echo '<div class="error">At least two runs must be selected.</div>';
            return false;
        }

        $runs = implode(',', $arRuns);

        $arSources = array_unique($arSources);
        if (count($arSources) !== 1) {
            echo '<div class="error">Runs can be aggregated in the same namespace only.</div>';
            return false;
        }

        $arWeights0 = array_unique($arWeights);
        $weights = false;
        if (count($arWeights0) > 1) {
            $weights = implode(',', $arWeights);
        }

        $redirectUrl = self::getAggregateRunsReportLink($runs, $arSources[0], $weights);

        header('HTTP/1.1 301 Moved Permanently');
        header("Location: $redirectUrl");
        exit;
    }

    /**
     * Convert input runs into appropriate string format for displayXHProfReportCompare and redirect to new location
     * @return bool
     */
    public static function goToCompareRunsByRequest()
    {
        list($arRuns, $arSources) = self::getRunsSourcesSortsWeightsFromRequestSorted();

        if (count($arRuns) < 2) {
            echo '<div class="error">At least two runs must be selected.</div>';
            return false;
        }

        $runs = implode(',', $arRuns);
        $sources = implode(',', $arSources);

        $calcAverage = array_key_exists('calc_average', $_REQUEST) ? 1 : 0;

        $redirectUrl = self::getNewReportRunLink($runs, $sources, $calcAverage);
        header('HTTP/1.1 301 Moved Permanently');
        header("Location: $redirectUrl");
        exit;
	}

    public function getRunFileName($run, $source)
    {
        return "{$this->dir}/{$run}.{$source}.{$this->suffix}";
	}

    public function getRunCommentFileName($run, $source)
    {
        return $this->getRunFileName($run, $source) . '.' . self::CUSTOM_COMMENTS_FILE_SUFFIX;
	}

    public function getRunSessionFileName($run, $source)
    {
        return $this->getRunFileName($run, $source) . '.' . self::SESSION_FILE_SUFFIX;
	}

	public function saveCustomCommentsByRequest()
    {
        $arComments = $_REQUEST['comment'];

        $arSavedRuns = $arDeletedRuns = [];
        foreach($arComments as $runId => $comment) {
            list($run, $source) = explode('.', $runId);
            $fileComment = $this->getRunCommentFileName($run, $source);
            if (trim($comment) === '') {
                if (file_exists($fileComment)) {
                    unlink($fileComment);
                    $arDeletedRuns[] = $run;
                }
            } else {
                $comment = json_encode($comment);
                file_put_contents($fileComment, $comment, LOCK_EX);
                $arSavedRuns[] = $run;
            }
        }

        if (!empty($arSavedRuns)) {
            $savedRuns = implode(', ', $arSavedRuns);
            echo '<div class="success">Saved custom comments of runs (' . count($arSavedRuns) . '): ' . $savedRuns . '</div>';
        }

        if (!empty($arDeletedRuns)) {
            $deletedRuns = implode(', ', $arDeletedRuns);
            echo '<div class="success">Deleted custom comments of runs (' . count($arDeletedRuns) . '): ' . $deletedRuns . '</div>';
        }

        return true;
    }

    public function deleteSelectedRunsByRequest()
    {
        list($arRuns, $arSources) = self::getRunsSourcesSortsWeightsFromRequestSorted();
        $runsCount = count($arRuns);

        if ($runsCount < 1) {
            echo '<div class="error">Nothing to delete. First select runs to delete.</div>';
            return false;
        }

        $arErrRuns = $arDeletedRuns = $arErrRunsComments = $arErrRunsSessions = [];
        for ($i = 0; $i < $runsCount; $i++) {
            $file = $this->getRunFileName($arRuns[$i], $arSources[$i]);
            if (file_exists($file)) {
                if(unlink($file) === false) {
                    $arErrRuns[] = $arRuns[$i];
                } else {
                    $arDeletedRuns[] = $arRuns[$i];
                }
            }

            $file = $this->getRunCommentFileName($arRuns[$i], $arSources[$i]);
            if (file_exists($file)) {
                if(unlink($file) === false) {
                    $arErrRunsComments[] = $arRuns[$i];
                }
            }

            $file = $this->getRunSessionFileName($arRuns[$i], $arSources[$i]);
            if (file_exists($file)) {
                if(unlink($file) === false) {
                    $arErrRunsSessions[] = $arRuns[$i];
                }
            }
        }

        $deletedCount = count($arDeletedRuns);
        if ($deletedCount > 0) {
            $deletedRuns = implode(', ', $arDeletedRuns);
            echo '<div class="success">Deleted runs (' . $deletedCount . '): ' . $deletedRuns . '</div>';
        }

        $errRunsCount = count($arErrRuns);
        if($errRunsCount > 0) {
            $errRuns = implode(', ', $arErrRuns);
            echo '<div class="error">Unable to delete runs (' . $errRunsCount . '): ' . $errRuns . '</div>';
        }

        $errRunsCommentsCount = count($arErrRunsComments);
        if($errRunsCommentsCount > 0) {
            $errComments = implode(', ', $arErrRunsComments);
            echo '<div class="error">Unable to delete comments of runs (' . $errRunsCommentsCount . '): ' . $errComments . '</div>';
        }

        $errRunsSessionsCount = count($arErrRunsSessions);
        if($errRunsSessionsCount > 0) {
            $errSessions = implode(', ', $arErrRunsSessions);
            echo '<div class="error">Unable to delete sessions of runs (' . $errRunsSessionsCount . '): ' . $errSessions . '</div>';
        }
    }

    public static function needPrintAverageByRequest()
    {
        return array_key_exists('average', $_REQUEST) && (int)$_REQUEST['average'] === 1;
    }
}
