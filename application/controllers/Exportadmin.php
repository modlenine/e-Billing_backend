<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Exportadmin extends CI_Controller {

    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        date_default_timezone_set("Asia/Bangkok");
        $this->load->model("exportadmin_model" , "exportadmin");
    }

    public function exportReportData($startDate_filter , $endDate_filter , $company_filter , $status_filter , $creditterm_filter , $datepayreal_filter , $period_filter)
    {
        $this->exportadmin->exportReportData($startDate_filter , $endDate_filter , $company_filter , $status_filter , $creditterm_filter , $datepayreal_filter , $period_filter);
    }
    

}

/* End of file Controllername.php */



?>