<?php

require dirname(__FILE__).'/pchart/pData.php';
require dirname(__FILE__).'/pchart/pChart.php';
require dirname(__FILE__).'/pchart/GDCanvas.php';
require dirname(__FILE__).'/pchart/PieChart.php';


class StatisticsGraph {
    private $hlp;
    private $tlimit;
    private $start;
    private $from;
    private $to;

    public function __construct($hlp){
        $this->hlp = $hlp;
    }

    public function render($call,$from,$to,$start){
        $from = preg_replace('/[^\d\-]+/','',$from);
        $to   = preg_replace('/[^\d\-]+/','',$to);
        if(!$from) $from = date('Y-m-d');
        if(!$to)   $to   = date('Y-m-d');
        $this->tlimit = "A.dt >= '$from 00:00:00' AND A.dt <= '$to 23:59:59'";
        $this->start  = (int) $start;
        $this->from   = $from;
        $this->to     = $to;

        if(method_exists($this,$call)){
            $this->$call();
        }else{
            $this->hlp->sendGIF();
        }
    }

    public function PieChart($data){
        $DataSet = new pData;
        $Canvas = new GDCanvas(400, 200, false);
        $Chart = new PieChart(400, 200, $Canvas);
        $Chart->setFontProperties(dirname(__FILE__).'/pchart/Fonts/DroidSans.ttf', 8);

        $DataSet->AddPoints(array_values($data),'Serie1');
        $DataSet->AddPoints(array_keys($data),'Serie2');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries("Serie2");

        $Chart->drawBasicPieGraph(
            $DataSet->getData(),
            $DataSet->GetDataDescription(),
            120, 100, 60, PIE_PERCENTAGE);
        $Chart->drawPieLegend(
            230, 15,
            $DataSet->GetData(),
            $DataSet->GetDataDescription(),
            new Color(250));

        header('Content-Type: image/png');
        $Chart->Render('');
    }

    public function countries(){
        // build top countries + other
        $result = $this->hlp->Query()->countries($this->tlimit,$this->start,0);
        $data = array();
        $top = 0;
        foreach($result as $row){
            if($top < 6){
                $data[$row['country']] = $row['cnt'];
            }else{
                $data['other'] += $row['cnt'];
            }
            $top++;
        }

        $this->PieChart($data);
    }

    public function searchengines(){
        // build top countries + other
        $result = $this->hlp->Query()->searchengines($this->tlimit,$this->start,0);
        $data = array();
        $top = 0;
        foreach($result as $row){
            if($top < 3){
                $data[$row['engine']] = $row['cnt'];
            }else{
                $data['other'] += $row['cnt'];
            }
            $top++;
        }

        $this->PieChart($data);
    }

    public function browsers(){
        // build top browsers + other
        $result = $this->hlp->Query()->browsers($this->tlimit,$this->start,0,false);
        $data = array();
        $top = 0;
        foreach($result as $row){
            if($top < 4){
                $data[$row['ua_info']] = $row['cnt'];
            }else{
                $data['other'] += $row['cnt'];
            }
            $top++;
        }
        $this->PieChart($data);
    }

    public function os(){
        // build top browsers + other
        $result = $this->hlp->Query()->os($this->tlimit,$this->start,0,false);
        $data = array();
        $top = 0;
        foreach($result as $row){
            if($top < 4){
                $data[$row['os']] = $row['cnt'];
            }else{
                $data['other'] += $row['cnt'];
            }
            $top++;
        }
        $this->PieChart($data);
    }

    public function viewport(){
        $result = $this->hlp->Query()->viewport($this->tlimit,0,100);
        $data1 = array();
        $data2 = array();

        foreach($result as $row){
            $data1[] = $row['res_x'];
            $data2[] = $row['res_y'];
            $data3[] = $row['cnt'];
        }

        $DataSet = new pData;
        $DataSet->AddPoints($data1,'Serie1');
        $DataSet->AddPoints($data2,'Serie2');
        $DataSet->AddPoints($data3,'Serie3');
        $DataSet->AddAllSeries();

        $Canvas = new GDCanvas(650, 490, false);
        $Chart  = new pChart(650,490,$Canvas);

        $Chart->setFontProperties(dirname(__FILE__).'/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50,30,630,470);
        $Chart->drawXYScale($DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
                            'Serie2','Serie1');

        $Chart->drawXYPlotGraph($DataSet,'Serie2','Serie1',0,20,2,null,false,'Serie3');
        header('Content-Type: image/png');
        $Chart->Render('');
    }

    public function resolution(){
        $result = $this->hlp->Query()->resolution($this->tlimit,0,100);
        $data1 = array();
        $data2 = array();

        foreach($result as $row){
            $data1[] = $row['res_x'];
            $data2[] = $row['res_y'];
            $data3[] = $row['cnt'];
        }

        $DataSet = new pData;
        $DataSet->AddPoints($data1,'Serie1');
        $DataSet->AddPoints($data2,'Serie2');
        $DataSet->AddPoints($data3,'Serie3');
        $DataSet->AddAllSeries();

        $Canvas = new GDCanvas(650, 490, false);
        $Chart  = new pChart(650,490,$Canvas);

        $Chart->setFontProperties(dirname(__FILE__).'/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50,30,630,470);
        $Chart->drawXYScale($DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
                            'Serie2','Serie1');

        $Chart->drawXYPlotGraph($DataSet,'Serie2','Serie1',0,20,2,null,false,'Serie3');
        header('Content-Type: image/png');
        $Chart->Render('');
    }

    public function dashboardviews(){
        $hours  = ($this->from == $this->to);
        $result = $this->hlp->Query()->dashboardviews($this->tlimit,$hours);
        $data1  = array();
        $data2  = array();
        $data3  = array();
        $times  = array();

        foreach($result as $time => $row){
            $data1[] = (int) $row['pageviews'];
            $data2[] = (int) $row['sessions'];
            $data3[] = (int) $row['visitors'];
            $times[] = $time.($hours?'h':'');
        }

        $DataSet = new pData();
        $DataSet->AddPoints($data1,'Serie1');
        $DataSet->AddPoints($data2,'Serie2');
        $DataSet->AddPoints($data3,'Serie3');
        $DataSet->AddPoints($times,'Times');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries('Times');

        $DataSet->SetSeriesName($this->hlp->getLang('graph_views'),'Serie1');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_sessions'),'Serie2');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_visitors'),'Serie3');

        $Canvas = new GDCanvas(700, 280, false);
        $Chart  = new pChart(700,280,$Canvas);

        $Chart->setFontProperties(dirname(__FILE__).'/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50,10,680,200);
        $Chart->drawScale($DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
                          ($hours?0:45), 1, false, ceil(count($times)/12) );
        $Chart->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());

        $DataSet->removeSeries('Times');
        $DataSet->removeSeriesName('Times');
        $Chart->drawLegend(
            550, 15,
            $DataSet->GetDataDescription(),
            new Color(250));

        header('Content-Type: image/png');
        $Chart->Render('');
    }

    public function dashboardwiki(){
        $hours  = ($this->from == $this->to);
        $result = $this->hlp->Query()->dashboardwiki($this->tlimit,$hours);
        $data1  = array();
        $data2  = array();
        $data3  = array();
        $times  = array();

        foreach($result as $time => $row){
            $data1[] = (int) $row['E'];
            $data2[] = (int) $row['C'];
            $data3[] = (int) $row['D'];
            $times[] = $time.($hours?'h':'');
        }

        $DataSet = new pData();
        $DataSet->AddPoints($data1,'Serie1');
        $DataSet->AddPoints($data2,'Serie2');
        $DataSet->AddPoints($data3,'Serie3');
        $DataSet->AddPoints($times,'Times');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries('Times');

        $DataSet->SetSeriesName($this->hlp->getLang('graph_edits'),'Serie1');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_creates'),'Serie2');
        $DataSet->SetSeriesName($this->hlp->getLang('graph_deletions'),'Serie3');

        $Canvas = new GDCanvas(700, 280, false);
        $Chart  = new pChart(700,280,$Canvas);

        $Chart->setFontProperties(dirname(__FILE__).'/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50,10,680,200);
        $Chart->drawScale($DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
                          ($hours?0:45), 1, false, ceil(count($times)/12) );
        $Chart->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());

        $DataSet->removeSeries('Times');
        $DataSet->removeSeriesName('Times');
        $Chart->drawLegend(
            550, 15,
            $DataSet->GetDataDescription(),
            new Color(250));

        header('Content-Type: image/png');
        $Chart->Render('');
    }

}
