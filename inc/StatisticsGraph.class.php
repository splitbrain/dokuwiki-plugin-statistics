<?php

require dirname(__FILE__) . '/pchart/pData.php';
require dirname(__FILE__) . '/pchart/pChart.php';
require dirname(__FILE__) . '/pchart/GDCanvas.php';
require dirname(__FILE__) . '/pchart/PieChart.php';

class StatisticsGraph {
    private $hlp;
    private $tlimit;
    private $start;
    private $from;
    private $to;

    public function __construct(helper_plugin_statistics $hlp) {
        $this->hlp = $hlp;
    }

    public function render($call, $from, $to, $start) {
        $from = preg_replace('/[^\d\-]+/', '', $from);
        $to   = preg_replace('/[^\d\-]+/', '', $to);
        if(!$from) $from = date('Y-m-d');
        if(!$to) $to = date('Y-m-d');
        $this->tlimit = "A.dt >= '$from 00:00:00' AND A.dt <= '$to 23:59:59'";
        $this->start  = (int) $start;
        $this->from   = $from;
        $this->to     = $to;

        if(method_exists($this, $call)) {
            $this->$call();
        } else {
            $this->hlp->sendGIF();
        }
    }

    /**
     * Create a PieChart
     *
     * @param array $data associative array contianing label and values
     */
    protected function PieChart($data) {
        $DataSet = new pData;
        $Canvas  = new GDCanvas(400, 200, false);
        $Chart   = new PieChart(400, 200, $Canvas);
        $Chart->setFontProperties(dirname(__FILE__) . '/pchart/Fonts/DroidSans.ttf', 8);

        $DataSet->AddPoints(array_values($data), 'Serie1');
        $DataSet->AddPoints(array_keys($data), 'Serie2');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries("Serie2");

        $Chart->drawBasicPieGraph(
            $DataSet->getData(),
            $DataSet->GetDataDescription(),
            120, 100, 60, PIE_PERCENTAGE
        );
        $Chart->drawPieLegend(
            230, 15,
            $DataSet->GetData(),
            $DataSet->GetDataDescription(),
            new Color(250)
        );

        header('Content-Type: image/png');
        $Chart->Render('');
    }

    /**
     * Build a PieChart with only the top data shown and all other summarized
     *
     * @param string $query The function to call on the Query object to get the data
     * @param string $key The key containing the label
     * @param int $max How many discrete values to show before summarizing under "other"
     */
    protected function sumUpPieChart($query, $key, $max=4){
        $result = $this->hlp->Query()->$query($this->tlimit, $this->start, 0, false);
        $data   = array();
        $top    = 0;
        foreach($result as $row) {
            if($top < $max) {
                $data[$row[$key]] = $row['cnt'];
            } else {
                $data['other'] += $row['cnt'];
            }
            $top++;
        }
        $this->PieChart($data);
    }

    /**
     * Create a history graph for the given info type
     *
     * @param $info
     */
    protected function history($info) {
        $diff = abs(strtotime($this->from) - strtotime($this->to));
        $days = floor($diff / (60*60*24));
        $months = $days > 40;

        $result = $this->hlp->Query()->history($this->tlimit, $info, $months);

        $data = array();
        $times = array();
        foreach($result as $row) {
            $data[] = $row['cnt'];
            if($months) {
                $times[] = substr($row['time'],0,4).'-'.substr($row['time'],4,2);
            }else {
                $times[] = substr($row['time'], -5);
            }
        }

        $DataSet = new pData();
        $DataSet->AddPoints($data, 'Serie1');
        $DataSet->AddPoints($times, 'Times');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries('Times');

        $DataSet->SetSeriesName($this->hlp->getLang('graph_'.$info), 'Serie1');

        $Canvas = new GDCanvas(600, 200, false);
        $Chart  = new pChart(600, 200, $Canvas);

        $Chart->setFontProperties(dirname(__FILE__) . '/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50, 10, 580, 140);
        $Chart->drawScale(
            $DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
            45, 1, false, ceil(count($times) / 12)
        );
        $Chart->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());

        $DataSet->removeSeries('Times');
        $DataSet->removeSeriesName('Times');
        $Chart->drawLegend(
            75, 5,
            $DataSet->GetDataDescription(),
            new Color(250)
        );


        header('Content-Type: image/png');
        $Chart->Render('');
    }

    #region Graphbuilding functions

    public function countries() {
        $this->sumUpPieChart('countries', 'country');
    }

    public function searchengines() {
        $this->sumUpPieChart('searchengines', 'engine', 3);
    }

    public function browsers() {
        $this->sumUpPieChart('browsers', 'ua_info');
    }

    public function os() {
        $this->sumUpPieChart('os', 'os');
    }

    public function topuser() {
        $this->sumUpPieChart('topuser', 'user');
    }

    public function topeditor() {
        $this->sumUpPieChart('topeditor', 'user');
    }

    public function topgroup() {
        $this->sumUpPieChart('topgroup', 'group');
    }

    public function topgroupedit() {
        $this->sumUpPieChart('topgroupedit', 'group');
    }

    public function viewport() {
        $result = $this->hlp->Query()->viewport($this->tlimit, 0, 100);
        $data1  = array();
        $data2  = array();
        $data3  = array();

        foreach($result as $row) {
            $data1[] = $row['res_x'];
            $data2[] = $row['res_y'];
            $data3[] = $row['cnt'];
        }

        $DataSet = new pData;
        $DataSet->AddPoints($data1, 'Serie1');
        $DataSet->AddPoints($data2, 'Serie2');
        $DataSet->AddPoints($data3, 'Serie3');
        $DataSet->AddAllSeries();

        $Canvas = new GDCanvas(650, 490, false);
        $Chart  = new pChart(650, 490, $Canvas);

        $Chart->setFontProperties(dirname(__FILE__) . '/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50, 30, 630, 470);
        $Chart->drawXYScale(
            $DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
            'Serie2', 'Serie1'
        );

        $Chart->drawXYPlotGraph($DataSet, 'Serie2', 'Serie1', 0, 20, 2, null, false, 'Serie3');
        header('Content-Type: image/png');
        $Chart->Render('');
    }

    public function resolution() {
        $result = $this->hlp->Query()->resolution($this->tlimit, 0, 100);
        $data1  = array();
        $data2  = array();
        $data3  = array();

        foreach($result as $row) {
            $data1[] = $row['res_x'];
            $data2[] = $row['res_y'];
            $data3[] = $row['cnt'];
        }

        $DataSet = new pData;
        $DataSet->AddPoints($data1, 'Serie1');
        $DataSet->AddPoints($data2, 'Serie2');
        $DataSet->AddPoints($data3, 'Serie3');
        $DataSet->AddAllSeries();

        $Canvas = new GDCanvas(650, 490, false);
        $Chart  = new pChart(650, 490, $Canvas);

        $Chart->setFontProperties(dirname(__FILE__) . '/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50, 30, 630, 470);
        $Chart->drawXYScale(
            $DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
            'Serie2', 'Serie1'
        );

        $Chart->drawXYPlotGraph($DataSet, 'Serie2', 'Serie1', 0, 20, 2, null, false, 'Serie3');
        header('Content-Type: image/png');
        $Chart->Render('');
    }


    public function history_page_count() {
        $this->history('page_count');
    }

    public function history_page_size() {
        $this->history('page_size');
    }

    public function history_media_count() {
        $this->history('media_count');
    }

    public function history_media_size() {
        $this->history('media_size');
    }


    public function dashboardviews() {
        $hours  = ($this->from == $this->to);
        $result = $this->hlp->Query()->dashboardviews($this->tlimit, $hours);
        $data1  = array();
        $data2  = array();
        $data3  = array();
        $times  = array();

        foreach($result as $time => $row) {
            $data1[] = (int) $row['pageviews'];
            $data2[] = (int) $row['sessions'];
            $data3[] = (int) $row['visitors'];
            $times[] = $time . ($hours ? 'h' : '');
        }

        $DataSet = new pData();
        $DataSet->AddPoints($data1, 'Serie1');
        $DataSet->AddPoints($data2, 'Serie2');
        $DataSet->AddPoints($data3, 'Serie3');
        $DataSet->AddPoints($times, 'Times');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries('Times');

        $DataSet->SetSeriesName($this->hlp->getLang('graph_views'), 'Serie1');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_sessions'), 'Serie2');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_visitors'), 'Serie3');

        $Canvas = new GDCanvas(700, 280, false);
        $Chart  = new pChart(700, 280, $Canvas);

        $Chart->setFontProperties(dirname(__FILE__) . '/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50, 10, 680, 200);
        $Chart->drawScale(
            $DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
            ($hours ? 0 : 45), 1, false, ceil(count($times) / 12)
        );
        $Chart->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());

        $DataSet->removeSeries('Times');
        $DataSet->removeSeriesName('Times');
        $Chart->drawLegend(
            550, 15,
            $DataSet->GetDataDescription(),
            new Color(250)
        );

        header('Content-Type: image/png');
        $Chart->Render('');
    }

    public function dashboardwiki() {
        $hours  = ($this->from == $this->to);
        $result = $this->hlp->Query()->dashboardwiki($this->tlimit, $hours);
        $data1  = array();
        $data2  = array();
        $data3  = array();
        $times  = array();

        foreach($result as $time => $row) {
            $data1[] = (int) $row['E'];
            $data2[] = (int) $row['C'];
            $data3[] = (int) $row['D'];
            $times[] = $time . ($hours ? 'h' : '');
        }

        $DataSet = new pData();
        $DataSet->AddPoints($data1, 'Serie1');
        $DataSet->AddPoints($data2, 'Serie2');
        $DataSet->AddPoints($data3, 'Serie3');
        $DataSet->AddPoints($times, 'Times');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries('Times');

        $DataSet->SetSeriesName($this->hlp->getLang('graph_edits'), 'Serie1');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_creates'), 'Serie2');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_deletions'), 'Serie3');

        $Canvas = new GDCanvas(700, 280, false);
        $Chart  = new pChart(700, 280, $Canvas);

        $Chart->setFontProperties(dirname(__FILE__) . '/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50, 10, 680, 200);
        $Chart->drawScale(
            $DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
            ($hours ? 0 : 45), 1, false, ceil(count($times) / 12)
        );
        $Chart->drawLineGraph($DataSet->GetData(), $DataSet->GetDataDescription());

        $DataSet->removeSeries('Times');
        $DataSet->removeSeriesName('Times');
        $Chart->drawLegend(
            550, 15,
            $DataSet->GetDataDescription(),
            new Color(250)
        );

        header('Content-Type: image/png');
        $Chart->Render('');
    }

    #endregion Graphbuilding functions
}
