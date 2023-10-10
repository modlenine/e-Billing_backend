<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Apiadmin extends CI_Controller {

    
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");

        $this->load->model("apiadmin_model" , "api");
    }
    

    public function index()
    {
        echo "test";
    }

    public function testcode()
    {
        echo date("Y-m" , 1677603600);
    }

    public function getDateTimeNow()
    {
        $output = array(
            "dateTimeNow" => strtotime(date("Y-m-d H:i:s"))
        );

        echo json_encode($output);
    }

    public function getcompany()
    {
        $this->api->getcompany();
    }

    public function getstatus()
    {
        $this->api->getstatus();
    }

    public function getCreditterm()
    {
        $this->api->getCreditterm();
    }

    public function getDateOfPayReal()
    {
        $this->api->getDateOfPayReal();
    }

    public function getDateOfPayRealM()
    {
        $this->api->getDateOfPayRealM();
    }

    public function getstatusUpload()
    {
        $this->api->getstatusUpload();
    }

    public function getperiod()
    {
        $this->api->getperiod();
    }

    public function getperiod_rp()
    {
        $this->api->getperiod_rp();
    }

    public function checklogin_admin()
    {
        $this->api->checklogin_admin();
    }

    public function loadBillingUploadData($startDate , $endDate , $company , $status , $month , $year)
    {
        $this->api->loadBillingUploadData($startDate , $endDate , $company , $status , $month , $year);
    }

    public function uploadData()
    {
        $this->api->uploadData();
    }

    public function api_printDocument($formno)
    {
        if($formno != ""){
            require_once('TCPDF/tcpdf.php');

            $data = array(
                "dataMain" => getViewFullData($formno),
                "dataFile" => getFile($formno)
            );

            $this->load->view("printDocument" , $data);
        }
    }

    public function getYear()
    {
        $this->api->getYear();
    }

    public function loadBilledReport($startDate , $endDate , $company , $status , $creditterm , $dateofpayreal , $period)
    {
        $this->api->loadBilledReport($startDate , $endDate , $company , $status , $creditterm , $dateofpayreal , $period);
    }

    public function loaddatareportConditionSum()
    {
        $this->api->loaddatareportConditionSum();
    }

    public function loadBilledReportsum($company)
    {
        $this->api->loadBilledReportsum($company);
    }

    public function deleteData()
    {
        $this->api->deleteData();
    }

    public function loadBilledList($startDate , $endDate , $company , $status , $invoice , $dateofpayreal)
    {
        $this->api->loadBilledList($startDate , $endDate , $company , $status , $invoice , $dateofpayreal);
    }

    public function saveConfirmBill()
    {
        $this->api->saveConfirmBill();
    }


    public function getBillDetailEdit()
    {
        $this->api->getBillDetailEdit();
    }


    public function saveBillSetting()
    {
        $this->api->saveBillSetting();
    }

    public function loadBillSetting()
    {
        $this->api->loadBillSetting();
    }

    public function delInvoice()
    {
        $this->api->delInvoice();
    }


    public function saveConfirmPay()
    {
        $this->api->saveConfirmPay();
    }

    public function saveUploadFile()
    {
        $this->api->saveUploadFile();
    }

    public function deleteFiles()
    {
        $this->api->deleteFiles();
    }

    public function getVenderdata()
    {
        $this->api->getVenderdata();
    }

    public function checkVenderActivate()
    {
        $this->api->checkVenderActivate();
    }

    public function loadAnnounceData_byvender($taxid)
    {
        $this->api->loadAnnounceData_byvender($taxid);
    }

    public function getDataGraph()
    {
        $this->api->getDataGraph();
    }

    public function getYearList()
    {
        $this->api->getYearList();
    }

    public function getGraph1()
    {
        $this->api->getGraph1();
    }

    public function saveAnnounceVender()
    {
        $this->api->saveAnnounceVender();
    }

    public function getDataAnnByAutoid()
    {
        $this->api->getDataAnnByAutoid();
    }

    public function saveEditAnnounceVender()
    {
        $this->api->saveEditAnnounceVender();
    }

    public function loadAnnounceDataMain()
    {
        $this->api->loadAnnounceDataMain();
    }

    public function saveAnnounceMain()
    {
        $this->api->saveAnnounceMain();
    }

    public function saveAnnounceMainEdit()
    {
        $this->api->saveAnnounceMainEdit();
    }

    public function loadScheduleSetting()
    {
        $this->api->loadScheduleSetting();
    }

    public function saveSchedule(){
        $this->api->saveSchedule();
    }

    public function getScheduleData()
    {
        $this->api->getScheduleData();
    }

    public function delScheduleData()
    {
        $this->api->delScheduleData();
    }

    public function getScheduleDataForCheck()
    {
        $this->api->getScheduleDataForCheck();
    }

    public function loadUserPermission()
    {
        $this->api->loadUserPermission();
    }

    public function searchUser()
    {
        $this->api->searchUser();
    }

    public function saveUserSetting()
    {
        $this->api->saveUserSetting();
    }

    public function getUserPermission()
    {
        $this->api->getUserPermission();
    }

    public function getDataUserSettingForEdit()
    {
        $this->api->getDataUserSettingForEdit();
    }

    public function deleteUser()
    {
        $this->api->deleteUser();
    }

    public function sendNotifyBeforePay()
    {
        $this->api->sendNotifyBeforePay();
        //http://localhost/intsys/ebilling/ebilling_backend/apiadmin/sendNotifyBeforePay
        //Link check notify every day
        //เช็คข้อมูลทุกวันเวลา 02.30 หากตรวจสอบพบว่าต้องส่ง Email ระบบจะส่ง Email ให้อัตโนมัติ
    }

    public function getReportnotifypay()
    {
        $this->api->getReportnotifypay();
    }





}/* End of file Controllername.php */
