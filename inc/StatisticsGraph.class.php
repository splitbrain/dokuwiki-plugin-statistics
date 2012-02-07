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
        $Canvas = new GDCanvas(400, 200);
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

    public function view(){

        $graph = new AGC(400, 200);
        $graph->setColor('color',0,'blue');
        $graph->setColor('color',1,'red');
        $graph->setProp("showkey",true);
        $graph->setProp("key",'view port width',0);
        $graph->setProp("key",'view port height',1);

        $result = $this->hlp->Query()->viewport($this->tlimit,0,0,true);
        foreach($result as $row){
            $graph->addPoint($row['cnt'],$row['res_x'],0);
        }

        $result = $this->hlp->Query()->viewport($this->tlimit,0,0,false);
        foreach($result as $row){
            $graph->addPoint($row['cnt'],$row['res_y'],1);
        }

        @$graph->graph();
        $graph->showGraph();
    }

    public function trend(){
        $hours  = ($this->from == $this->to);
        $result = $this->hlp->Query()->trend($this->tlimit,$hours);
        $data1  = array();
        $data2  = array();
        $data3  = array();
        $data4  = array();

        foreach($result as $row){
            $data1[] = $row['pageviews'];
            $data2[] = $row['sessions'];
            $data3[] = $row['visitors'];
            $data4[] = $row['time'].($hours?'h':'');
        }

        $DataSet = new pData();
        $DataSet->AddPoints($data2,'Serie2');
        $DataSet->AddPoints($data3,'Serie3');
        $DataSet->AddPoints($data1,'Serie1');
        $DataSet->AddPoints($data4,'Serie4');
        $DataSet->AddAllSeries();
        $DataSet->SetAbscissaLabelSeries('Serie4');

        $DataSet->SetSeriesName('Views','Serie1');
        $DataSet->SetSeriesName('Sessions','Serie2');
        $DataSet->SetSeriesName('Visitors','Serie3');

        $Canvas = new GDCanvas(500, 280);
        $Chart  = new pChart(500,280,$Canvas);

        $Chart->setFontProperties(dirname(__FILE__).'/pchart/Fonts/DroidSans.ttf', 8);
        $Chart->setGraphArea(50,30,480,200);
        $Chart->drawScale($DataSet, new ScaleStyle(SCALE_NORMAL, new Color(127)),
                          ($hours?0:45), 1, false, round(count($data1)/12) );
        $Chart->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());

        header('Content-Type: image:png');
        $Chart->Render('');
    }

}
