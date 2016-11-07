<?php
/*
  represents a char in OCR project
  




*/

class ocr_eng {
    private $m_width;   // width of each template char
    private $m_height;  // height of each template char                               
    private $m_count;   // number of total chars in the template set
    private $m_data = array(); // data of each char
    
    // subtract one array from another
    //spec, reurn $ar1 - $ar2
    private function array_sub($ar1, $ar2){
        
        $diff = array();
        for($r=0; $r<$this->m_height; $r++)
        {
            $diff[$r] = array_diff($ar1[$r], $ar2[$r]);
        }
        
    }
    // constructor funciton
    public function ocr_eng()
    {
        $this->m_width  = 0;
        $this->m_height = 0;
        $this->m_count  = 0;
    }
    
    // initialisation
    public function init($w, $h, $c)
    {
        $this->m_width  = $w;
        $this->m_height = $h;
        $this->m_count  = $c;
    }
    
    // train function
    // keep the data in memory
    public function train($dat, $tag)
    {
        if(is_array($dat)) {
            $siz[0] = count($dat);
            if(is_array($dat[0]))
            {
                $siz[1] = count($dat[0]);
            }
        }
            
        
        if($siz[0]!= $this->m_height || $siz[1]!= $this->m_width)
            return false;
            
        $this->m_data[$tag] = $dat;
    }
    
    // use template matching to find the best match
    public function test($dat)
    {
        // hold the mean squared distances
        $D = array();
        
        foreach($this->m_data as $tag => $temp){
            $tt = 0;
            $tt2 = 0;
            $D[$tag] = 0;
            for($r=0; $r<$this->m_height; $r++){
                for($c=0; $c<$this->m_width; $c++){
                    $tt = $dat[$r][$c]-$temp[$r][$c];
                    $tt2 = $tt*$tt;
                    $D[$tag] += $tt2; // square the distance
                }
            }    
        }
        
        // now pick the template with min distance
        asort($D); // $D[0] holds the smallest distance   
        reset($D);     
        return key($D);        
    }   
}