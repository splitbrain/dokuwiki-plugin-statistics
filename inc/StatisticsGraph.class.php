<?php

class StatisticsGraph {
    private $hlp;

    public function __construct($hlp){
        $this->hlp = $hlp;
    }


    public function countries(){
        // build top countries + other
        $result = $this->hlp->Query()->countries($this->tlimit,$this->start,0);
        $data = array();
        $top = 0;
        foreach($result as $row){
            if($top < 7){
                $data[$row['country']] = $row['cnt'];
            }else{
                $data['other'] += $row['cnt'];
            }
            $top++;
        }
        $pie = new AGC(300, 200);
        $pie->setProp("showkey",true);
        $pie->setProp("showval",false);
        $pie->setProp("showgrid",false);
        $pie->setProp("type","pie");
        $pie->setProp("keyinfo",1);
        $pie->setProp("keysize",8);
        $pie->setProp("keywidspc",-50);
        $pie->setProp("key",array_keys($data));
        $pie->addBulkPoints(array_values($data));
        @$pie->graph();
        $pie->showGraph();
    }

    public function browsers(){
        // build top browsers + other

        $result = $this->hlp->Query()->browsers($this->tlimit,$this->start,0,false);
        $data = array();
        $top = 0;
        foreach($result as $row){
            if($top < 5){
                $data[$row['ua_info']] = $row['cnt'];
            }else{
                $data['other'] += $row['cnt'];
            }
            $top++;
        }
        $pie = new AGC(300, 200);
        $pie->setProp("showkey",true);
        $pie->setProp("showval",false);
        $pie->setProp("showgrid",false);
        $pie->setProp("type","pie");
        $pie->setProp("keyinfo",1);
        $pie->setProp("keysize",8);
        $pie->setProp("keywidspc",-50);
        $pie->setProp("key",array_keys($data));
        $pie->addBulkPoints(array_values($data));
        @$pie->graph();
        $pie->showGraph();
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
        $data1   = array();
        $data2   = array();

        $graph = new AGC(400, 150);
        $graph->setProp("type","bar");
        $graph->setProp("showgrid",false);
        $graph->setProp("barwidth",.8);

        $graph->setColor('color',0,'blue');
        $graph->setColor('color',1,'red');
        $graph->setColor('color',2,'yellow');

        if($hours){
            //preset $hours
            for($i=0;$i<24;$i++){
                $data1[$i] = 0;
                $data2[$i] = 0;
                $data3[$i] = 0;
                $graph->setProp("scale",array(' 0h','   4h','   8h','    12h','    16h','    20h','    24h'));
            }
        }else{
            $graph->setProp("scale",array(next(array_keys($data1)),$this->to));
        }

        foreach($result as $row){
            $data1[$row['time']] = $row['pageviews'];
            $data2[$row['time']] = $row['sessions'];
            $data3[$row['time']] = $row['visitors'];
        }

        foreach($data1 as $key => $val){
            $graph->addPoint($val,$key,0);
        }
        foreach($data2 as $key => $val){
            $graph->addPoint($val,$key,1);
        }
        foreach($data3 as $key => $val){
            $graph->addPoint($val,$key,2);
        }

        @$graph->graph();
        $graph->showGraph();
    }

}