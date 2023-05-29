<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Exportdata extends CI_Controller {

    
    public function __construct()
    {
        parent::__construct();
        
        $this->load->model("report_model" , "report");
    }
    

    public function index()
    {
        echo "test";
    }

    public function downloadReport($dateStart , $dateEnd)
    {
        $this->report->downloadReport($dateStart , $dateEnd);
    }

    public function testcode()
    {
        $this->report->testcode();
    }

}

/* End of file Controllername.php */

?>
