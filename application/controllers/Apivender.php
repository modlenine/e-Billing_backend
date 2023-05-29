<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Apivender extends CI_Controller {

    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        date_default_timezone_set("Asia/Bangkok");
        $this->load->model("apivender_model" , "api");
    }
    

    public function index()
    {
        echo "Welcome to ebilling system";
    }

    public function checklogin()
    {
        $this->api->checklogin();
    }

    public function saveActivate()
    {
        $this->api->saveActivate();
    }

    public function resendActivateEmail()
    {
        $this->api->resendActivateEmail();
    }

    public function checkActivate()
    {
        $this->api->checkActivate();
    }

    public function checkActivateStatus()
    {
        $this->api->checkActivateStatus();
    }

    public function getdatauserForEditEmailActivate()
    {
        $this->api->getdatauserForEditEmailActivate();
    }

    public function saveChangeEmailForActivate()
    {
        $this->api->saveChangeEmailForActivate();
    }

    public function saveFotgotpassword()
    {
        $this->api->saveFotgotpassword();
    }

    public function saveResetpassword()
    {
        $this->api->saveResetpassword();
    }

    public function checkResetPasswordStatus()
    {
        $this->api->checkResetPasswordStatus();
    }

    public function loadDataBilling()
    {
        $this->api->loadDataBilling();
    }

    public function getVenderInformationByaccount()
    {
        $this->api->getVenderInformationByaccount();
    }

    public function getBillDetail()
    {
        $this->api->getBillDetail();
    }

    public function saveSelectBill()
    {
        $this->api->saveSelectBill();
    }

    public function printBillReport($formno , $venderaccount , $dataareaid)
    {
        if($formno != "" && $venderaccount != "" && $dataareaid != ""){
            require_once('TCPDF/tcpdf.php');

            $data = array(
                "dataBillMain" => $this->api->queryBillMain($formno),
                "dataBillDetail" => $this->api->getDataBilled($formno),
                "dataVenderInformation" => $this->api->getVenderInformationByaccountParam($venderaccount , $dataareaid),
                "formno" => $formno
            );

            $this->load->view("printBilled" , $data);
        }
    }

    public function loadBilledList($taxid , $startDate , $endDate , $company , $status , $invoice , $periodbilling)
    {
        $this->api->loadBilledList($taxid , $startDate , $endDate , $company , $status , $invoice , $periodbilling);
    }

    public function getBillDetailEdit()
    {
        $this->api->getBillDetailEdit();
    }

    public function saveCancelBill()
    {
        $this->api->saveCancelBill();
    }

    public function loadBilledReport($taxid , $startDate , $endDate , $company , $status , $periodbilling)
    {
        $this->api->loadBilledReport($taxid , $startDate , $endDate , $company , $status , $periodbilling);
    }

    public function checkupdatasta()
    {
        $this->api->checkupdatasta();
    }

    public function testcode()
    {
        $this->load->view("testemail");
    }


    public function loadAnnounceData_show($taxid)
    {
        $this->api->loadAnnounceData_show($taxid);
    }

    public function loadAnnounceData_main()
    {
        $this->api->loadAnnounceData_main();
    }

    public function checkDateOpenAndClose()
    {
        $this->api->checkDateOpenAndClose();
    }

    public function getDataVenderMember()
    {
        $this->api->getDataVenderMember();
    }

    public function saveEditProfile()
    {
        $this->api->saveEditProfile();
    }

    public function getPeriodBilling()
    {
        $this->api->getPeriodBilling();
    }



}
/* End of file Controllername.php */
?>