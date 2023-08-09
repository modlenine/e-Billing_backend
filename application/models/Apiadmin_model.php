<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Apiadmin_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->db2 = $this->load->database('saleecolour', TRUE);
        $this->db_mssql = $this->load->database('mssql' , TRUE);//sln,ca database
        $this->db_mssql2 = $this->load->database('mssql2' , TRUE);//tbb , st database

        date_default_timezone_set("Asia/Bangkok");
        $this->load->model("email_model");
    }



    public function testcode()
    {
        echo "test call";
    }

    public function escape_string()
    {
        if($_SERVER['HTTP_HOST'] == "localhost"){
            return mysqli_connect("192.168.20.22", "ant", "Ant1234", "saleecolour");
        }else{
            return mysqli_connect("localhost", "ant", "Ant1234", "saleecolour");
        }

    }


    public function checklogin_admin()
    {

        if ($this->input->post("username") != "" && $this->input->post("password") != "") {
            $username = $this->input->post("username");
            $password = $this->input->post("password");

            $user = mysqli_real_escape_string($this->escape_string(), $username);
            $pass = mysqli_real_escape_string($this->escape_string(), md5($password));

            // Check ว่าเป็นการ Login ของ Vender หรือว่า พนักงาน
            $sql = $this->db2->query(sprintf("SELECT * FROM member WHERE username='%s' AND password='%s' ", $user, $pass));
            if ($sql->num_rows() == 0) {
                $output = array(
                    "msg" => "ไม่พบข้อมูลผู้ใช้งานในระบบ",
                    "status" => "Login failed"
                );
            } else {
                $uri = isset($_SESSION['RedirectKe']) ? $_SESSION['RedirectKe'] : '/intsys/ebilling/admin/';
                // header('location:' . $uri);
                // Check IT
                $output = array(
                    "msg" => "ลงชื่อเข้าใช้สำเร็จ",
                    "status" => "Login Successfully",
                    "uri" => $uri,
                    "session_data" => $sql->row_array(),
                    "dateExpire" => strtotime(date("Y-m-d H:i:s")."+10 seconds"),
                );
            }


        }else{
            $output = array(
                "msg" => "กรุณากรอก Username & Password",
                "status" => "Login failed please fill username and password"
            );
        }
      
        echo json_encode($output);
    }

    public function loadBillingUploadData($startDate , $endDate , $company , $status , $month , $year)
    {
        // DB table to use
        $table = 'datalist';

        // Table's primary key
        $primaryKey = 'autoid';

        $columns = array(
            array('db' => 'autoid', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    
                    $getData = getdataByAutoid($d);

                    $taxid = "";
                    $venderid = "";
                    $po = "";
                    $invoice = "";
                    $includevat = "";
                    $invoicedate = "";
                    $ulstatus = "";

                    if($getData->num_rows() != 0){
                        $taxid = $getData->row()->taxid;
                        $venderid = $getData->row()->invoiceaccount;
                        $po = $getData->row()->purchid;
                        $invoice = $getData->row()->invoiceid;
                        $includevat = number_format($getData->row()->invoiceamount , 2);
                        $invoicedate = conDateFromDb($getData->row()->invoicedate);
                        $autoid = $getData->row()->autoid;
                        $ulstatus = $getData->row()->ulstatus;
                    }

                    if($ulstatus == "Open"){
                        $html = '
                        <a href="javascript:void(0)" class="select-delinvoice" data-toggle="modal" data-target="#deleteInvoice_modal"
                            data_autoid="'.$autoid.'"
                            data_taxid="'.$taxid.'"
                            data_venderid="'.$venderid.'"
                            data_po="'.$po.'"
                            data_invoice="'.$invoice.'"
                            data_includevat="'.$includevat.'"
                            data_invoicedate="'.$invoicedate.'"
                        ><button class="btn btn-info">เลือก</button></a>
                        ';
                    }else{
                        $html = '
                        <a href="javascript:void(0)"><b></b></a>
                        ';
                    }

                    return $html;
                }
            ),
            array('db' => 'taxid', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'invoiceaccount', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'ledgervoucher', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'purchid', 'dt' => 4),
            array('db' => 'invoiceid', 'dt' => 5),
            array('db' => 'salesbalance', 'dt' => 6 ,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'sumtax', 'dt' => 7 ,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'invoiceamount', 'dt' => 8 ,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'payment', 'dt' => 9),
            array('db' => 'invoicedate', 'dt' => 10 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'dataareaid', 'dt' => 11 ,
                'formatter' => function($d , $row){
                    return conAreaidToFullname($d);
                }
            ),
            array('db' => 'ulstatus', 'dt' => 12 ,
                'formatter' => function($d , $row){
                    $textColor = "";

                    if($d == "Open"){
                        $textColor = 'style="color:#0099FF;"';
                    }else if($d == "In Progress"){
                        $textColor = 'style="color:#8B4513;"';
                    }else if($d == "Posted"){
                        $textColor = 'style="color:#009900;"';
                    }

                    $html = "<span $textColor><b>$d</b></span>";

                    return $html;
                }
            ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        $sql_searchBydate = "";
        
        if($startDate == "0" && $endDate == "0"){
            $sql_searchBydate = "invoicedate LIKE '%%' ";
        }else if($startDate == "0" && $endDate != "0"){
            $sql_searchBydate = "invoicedate BETWEEN '$endDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate != "0"){
            $sql_searchBydate = "invoicedate BETWEEN '$startDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate == "0"){
            $sql_searchBydate = "invoicedate BETWEEN '$startDate 00:00:01' AND '$startDate 23:59:59' ";
        }



        $query_company = "";
        if($company == "0"){
            $query_company = "";
        }else{
            $query_company = "AND dataareaid = '$company' ";
        }




        $query_status = "";
        $con_status = "";

        $sqlConStatus = conStatusTotext($status);
        if($sqlConStatus->num_rows() != 0){
            $con_status = $sqlConStatus->row()->s_statusname;
            $query_status = "AND ulstatus = '$con_status' ";
        }else{
            $query_status = "";
        }

        // if($status == "0"){
        //     $query_status = "";
        // }else{
        //     $con_status = str_replace("-" , " ",$status);
        //     $query_status = "AND status = '$con_status' ";
        // }

        $query_period = "";
        if($month == "0" && $year == "0"){
            $query_period = "";
        }else{
            $query_period = "AND dataMonth = '$month' AND dataYear = '$year'";
        }
        

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, "$sql_searchBydate $query_company $query_status $query_period")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }

    public function uploadData()
    {
        
        if (isset($_FILES['ipf-uploadData']) == true) {
            $filename = explode(".", $_FILES['ipf-uploadData']['name']);
            if (end($filename) == "csv" || end($filename) == "txt") {
                $handle = fopen($_FILES['ipf-uploadData']['tmp_name'], "r");
                // Select ข้อมูล Master table 1 row มาเช็ค
                $userupload = $this->input->post("ip-userupload");
                $ecodeupload = $this->input->post("ip-ecodeupload");
                $dataMonth = $this->input->post("ip-uploadDataMonth");
                $dataYear = $this->input->post("ip-uploadDataYear");
                $statusUpload = 1;

                $dataMonthBill = $this->input->post("ip-uploadBillDataMonth");
                $dataYearBill = $this->input->post("ip-uploadBillDataYear");
                
                while ($data = fgetcsv($handle ,null, ",")) {

                    if($data[10] != "" && $data[7] != ""){
                        $date = date_create($data[8]);
                        $dataYearInvoice = date_format($date , "Y");
                        $dataMonthInvoice = date_format($date , "m");
    
                        // $arupdate = array(
                        //     "invoiceaccount" => $data[0],
                        //     "ledgervoucher" => $data[1],
                        //     "purchid" => $data[2],
                        //     "invoiceid" => $data[3],
                        //     "salesbalance" => conPrice($data[4]),
                        //     "sumtax" => conPrice($data[5]),
                        //     "invoiceamount" => conPrice($data[6]),
                        //     "payment" => $data[7],
                        //     "invoicedate" => $data[8],
                        //     "dataareaid" => $data[9],
                        //     "dataMonth" => $dataMonth,
                        //     "dataYear" => $dataYearInvoice,
                        //     "ulstatus" => "รอวางบิล",
                        //     "user_upload" => $userupload,
                        //     "ecode_upload" => $ecodeupload,
                        //     "datetime_upload" => date("Y-m-d H:i:s"),
                        //     "taxid" => $data[10]
                        // );
    

                        // $venderAccount = $data[0];
                        // $voucher = $data[1];
                        // $po = $data[2];
                        // $invoice = $data[3];
                        // $dataareaid = $data[9];
                        // $invoicedate = $data[8];


                        if($dataMonthInvoice == $dataMonth && $dataYear == $dataYearInvoice){
                            $statusUpload = $statusUpload * 1;
                        }else{
                            $statusUpload = $statusUpload * 0;
                        }


                        // if($dataMonthInvoice == $dataMonth && $dataYear == $dataYearInvoice){
                        //     if($this->checkDuplicateData($venderAccount , $voucher , $po , $invoice , $dataareaid , $invoicedate)->num_rows() == 0){
                        //         $this->db->insert("billupload", $arupdate);
                        //     }
                        // }else{
                        //     $statusUpload = false;
                        //     break;
                        // }

    

                    }
                    

                }

                if($statusUpload == 1){
                    $handle = fopen($_FILES['ipf-uploadData']['tmp_name'], "r");
                    while ($data = fgetcsv($handle ,null, ",")) {
                        if($data[10] != "" && $data[7] != ""){
                            $date = date_create($data[8]);
                            $dataYearInvoice = date_format($date , "Y");
                            $dataMonthInvoice = date_format($date , "m");

                            $period1 = strtotime($dataYearInvoice."-".$dataMonth);
                            $period2 = strtotime($dataYearBill."-".$dataMonthBill);
    
                            $arupdate = array(
                                "invoiceaccount" => removeBOM($data[0]),
                                "ledgervoucher" => $data[1],
                                "purchid" => $data[2],
                                "invoiceid" => $data[3],
                                "salesbalance" => conPrice($data[4]),
                                "sumtax" => conPrice($data[5]),
                                "invoiceamount" => conPrice($data[6]),
                                "payment" => $data[7],
                                "invoicedate" => $data[8],
                                "dataareaid" => $data[9],
                                "dataMonth" => $dataMonth,
                                "dataYear" => $dataYearInvoice,
                                "dataPeriod1" => $period1,
                                "dataMonthBill" => $dataMonthBill,
                                "dataYearBill" => $dataYearBill,
                                "dataPeriod2" => $period2,
                                "ulstatus" => "Open",
                                "user_upload" => $userupload,
                                "ecode_upload" => $ecodeupload,
                                "datetime_upload" => date("Y-m-d H:i:s"),
                                "taxid" => $data[10]
                            );
    
    
                            $venderAccount = $data[0];
                            $voucher = $data[1];
                            $po = $data[2];
                            $invoice = $data[3];
                            $dataareaid = $data[9];
                            $invoicedate = $data[8];
    
    
                            if($dataMonthInvoice == $dataMonth && $dataYear == $dataYearInvoice){
                                if($this->checkDuplicateData($venderAccount , $voucher , $invoice , $dataareaid , $invoicedate)->num_rows() == 0){
                                    $this->db->insert("billupload", $arupdate);
                                }
                            }
    
                        }
                    }
                    $output = array(
                        "msg" => "อัพโหลดเอกสารสำเร็จ",
                        "status" => "Upload Data Success",
                        "statusUpload" =>  $statusUpload
                    );
                }else{
                    $output = array(
                        "msg" => "ข้อมูลเอกสารอัพโหลดไม่ตรงกับเดือนที่เลือก",
                        "status" => "Upload Data Failed1",
                        "statusUpload" => $statusUpload
                    );
                }



                fclose($handle);

                // if($statusUpload == 0){
                //     $output = array(
                //         "msg" => "ข้อมูลเอกสารอัพโหลดไม่ตรงกับเดือนที่เลือก",
                //         "status" => "Upload Data Failed1",
                //         "statusUpload" => $statusUpload
                //     );
                // }else if($statusUpload == 1){
                //     $output = array(
                //         "msg" => "อัพโหลดเอกสารสำเร็จ",
                //         "status" => "Upload Data Success",
                //         "statusUpload" =>  $statusUpload
                //     );
                // }


            } else {
                $output = array(
                    "msg" => "รองรับเฉพาะไฟล์ CSV เท่านั้น",
                    "status" => "Upload CSV File Only",
                );
            }
         
        } else {
            $output = array(
                "msg" => "พบข้อผิดพลาดในการอัพโหลดไฟล์",
                "status" => "Upload Data Failed",
            );
        }

        // $output = array(
        //     "file" => isset($_FILES['ipf-uploadData'])
        // );

        echo json_encode($output);
        
    }


    private function checkDuplicateData($venderAccount , $voucher  , $invoice , $dataareaid , $invoicedate)
    {
        if($venderAccount != "" && $voucher != "" && $dataareaid != ""){
            $sql = $this->db->query("SELECT
            invoiceaccount,
            ledgervoucher,
            purchid,
            invoiceid,
            dataareaid
            FROM billupload WHERE invoiceaccount = '$venderAccount' and ledgervoucher = '$voucher' and invoiceid = '$invoice' and dataareaid = '$dataareaid' and invoicedate = '$invoicedate'
            ");

            return $sql;
        }
    }


    public function getYear()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getYear"){
            $month = $received_data->monthSelect;

            $sql = $this->db->query("SELECT
            dataYear
            From billupload WHERE dataMonth = '$month'
            GROUP BY dataYear
            ORDER BY dataYear DESC
            ");
            
            $output = array(
                "msg" => "ดึงข้อมูลปี สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลปี ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }

    public function getcompany()
    {
        $sql = $this->db->query("SELECT
        c_autoid,
        c_shotname,
        c_middlename,
        c_fullname
        FROM company_master ORDER BY c_shotname ASC
        ");

        $output = array(
            "msg" => "ดึงข้อมูล Company สำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result()
        );
        echo json_encode($output);
    }

    public function getstatus()
    {
        $sql = $this->db->query("SELECT
        s_autoid,
        s_statusname
        FROM status_master WHERE s_type IS NULL ORDER BY s_autoid ASC
        ");

        $output = array(
            "msg" => "ดึงข้อมูล status สำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result()
        );

        echo json_encode($output);
    }

    public function getCreditterm()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getCreditterm"){
            $sql = $this->db->query("SELECT
            payment,
            dataareaid
            FROM billupload GROUP BY payment
            ");
            $dataRs = [];
            foreach($sql->result() as $rs){
                $ar = array(
                    "payment" => $rs->payment,
                    "numofday" => conCreditTerm($rs->payment , $rs->dataareaid)->row()->numofdays
                );
                $dataRs[] = $ar;
            }
            $output = array(
                "msg" => "ดึงข้อมูล Payment สำเร็จ",
                "status" => "Select Data Success",
                "result" => $dataRs
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Payment ไม่สำเร็จสำเร็จ",
                "status" => "Select Data Not Success",
            );
        }
        echo json_encode($output);
    }

    public function getDateOfPayReal()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDateOfPayReal"){
            $sql = $this->db->query("SELECT
            tr_dateofpayreal
            FROM bill_trans GROUP BY tr_dateofpayreal
            ");
            $output = array(
                "msg" => "ดึงข้อมูลวันที่จ่ายเงินสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลวันที่จ่ายเงินสำเร็จ",
                "status" => "Select Data Success",
            );
        }
        echo json_encode($output);
    }

    public function getstatusUpload()
    {
        $sql = $this->db->query("SELECT
        s_autoid,
        s_statusname,
        s_statusname2
        FROM status_master WHERE s_type = 'upload'
        ORDER BY s_autoid ASC
        ");

        $output = array(
            "msg" => "ดึงข้อมูล status สำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result()
        );

        echo json_encode($output);
    }

    public function getperiod()
    {
        $sql = $this->db->query("SELECT
        dataMonth,
        dataYear
        FROM billupload
        GROUP BY dataMonth , dataYear
        ORDER BY dataYear DESC , dataMonth DESC
        ");

        $output = array(
            "msg" => "ดึงข้อมูล Data Period สำเร็จ",
            "status" => "Select Data Success",
            "result" => $sql->result()
        );

        echo json_encode($output);
    }

    public function getperiod_rp()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDataPeriod"){
            $periodsearch = $received_data->period;

            $year = substr($periodsearch , 0 , 4);
            $month = substr($periodsearch , 5 , 2);
            $condition = "";

            if($periodsearch != ""){
                $condition = "WHERE dataYear LIKE '%$year%' AND dataMonth LIKE '%$month%'";
            }else{
                $condition = "";
            }

            $sql = $this->db->query("SELECT
            dataMonth,
            dataYear
            FROM billupload $condition
            GROUP BY dataMonth , dataYear
            ORDER BY dataMonth ASC , dataYear DESC
            ");

            $output = array(
                "msg" => "ดึงข้อมูล Data Period สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result(),
            );

        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Data Period ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }


        echo json_encode($output);
    }


    public function deleteData()
    {
        if($this->input->post("ip-deleteDataMonth") != "" && $this->input->post("ip-deleteDataYear") != ""){
            $month = $this->input->post("ip-deleteDataMonth");
            $year = $this->input->post("ip-deleteDataYear");

            // Check Data pending
            $resuleCheck = $this->checkDataBeforeDelete($year , $month);
            $check = 1;
            foreach($resuleCheck->result() as $rs){
                if($rs->ulstatus == "Open"){
                    $check = $check * 1;
                }else{
                    $check = $check * 0;
                }
            }

            if($check != 0){
                $this->db->where("dataMonth" , $month);
                $this->db->where("dataYear" , $year);
                $this->db->delete("billupload");
    
                $output = array(
                    "msg" => "ลบข้อมูลสำเร็จ",
                    "status" => "Delete Data Success"
                );
            }else{
                $output = array(
                    "msg" => "ลบข้อมูลไม่สำเร็จ ตรวจสอบพบรายการรอดำเนินการ",
                    "status" => "Delete Data Not Success2"
                );
            }

        }else{
            $output = array(
                "msg" => "ลบข้อมูลไม่สำเร็จ",
                "status" => "Delete Data Not Success"
            );
        }
        echo json_encode($output);
    }
    private function checkDataBeforeDelete($year , $month)
    {
        if($year != "" && $month != ""){
            $sql = $this->db->query("SELECT
            ulstatus
            FROM billupload WHERE dataMonth = '$month' AND dataYear = '$year'
            ");
            return $sql;
        }
    }

    public function loadBilledList($startDate , $endDate , $company , $status , $invoice)
    {
        // DB table to use
        $table = 'billedlist_view_admin';

        // Table's primary key
        $primaryKey = 'ma_autoid';

        $columns = array(
            array('db' => 'ma_formno', 'dt' => 0 ,
                'formatter' => function($d , $row){

                    if($_SERVER['HTTP_HOST'] == "localhost"){
                        $url = "/admin/confirmbilled/";
                    }else{
                        $url = "/intsys/ebilling/admin/confirmbilled/";
                    }

                    $taxid = getTaxidByFormno($d)->row()->ma_taxid;
                    $html = '
                    <a href="'.$url.$d.'/'.$taxid.'"><b>'.$d.'</b></a>
                    ';
                    return $html;
                }
            ),
            array('db' => 'ma_taxid', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'ma_venderaccount', 'dt' => 2),
            array('db' => 'ma_dataareaid', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    $fullCompany = '';
                    switch($d){
                        case "sln":
                            $fullCompany = 'Salee Colour';
                            break;
                        case "ca":
                            $fullCompany = 'Composite Asia';
                            break;
                        case "tbb":
                            $fullCompany = 'The bubbles';
                            break;
                        case "st":
                            $fullCompany = 'Subterra';
                            break;
                    }

                    return $fullCompany;
                }
            ),
            array('db' => 'ma_payment', 'dt' => 4),
            array('db' => 'ma_datetime', 'dt' => 5,
                'formatter' => function($d , $row){
                    return conDateTimeFromDb($d);
                }
            ),
            array('db' => 'ma_status', 'dt' => 6,
                'formatter' => function($d , $row){
                    $textColor = "";
                    switch($d){
                        case "User Cancel":
                            $textColor = "style='color:#CC0000;'";
                            break;
                        case "Checking":
                            $textColor = "style='color:#0099FF;'";
                            break;
                        case "In Progress":
                            $textColor = "style='color:#8B4513;'";
                            break;
                        case "Posted":
                            $textColor = "style='color:#009900;'";
                            break;
                    }
                    return "<span ".$textColor."><b>$d</b></span>";
                }
            ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        $sql_searchBydate = "";
        
        if($startDate == "0" && $endDate == "0"){
            $sql_searchBydate = "ma_datetime LIKE '%%' ";
        }else if($startDate == "0" && $endDate != "0"){
            $sql_searchBydate = "ma_datetime BETWEEN '$endDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate != "0"){
            $sql_searchBydate = "ma_datetime BETWEEN '$startDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate == "0"){
            $sql_searchBydate = "ma_datetime BETWEEN '$startDate 00:00:01' AND '$startDate 23:59:59' ";
        }



        $query_company = "";
        if($company == "0"){
            $query_company = "";
        }else{
            $query_company = "AND ma_dataareaid = '$company' ";
        }


        $query_status = "";
        $con_status = "";
        $getDataByStatusId = "";
        $statusText = "";
        if($status == "0"){
            $query_status = "";
        }else{
            $con_status = str_replace("-" , " ",$status);
            $getDataByStatusId = getStatus($status);
            if($getDataByStatusId->num_rows() != 0){
                $statusText = $getDataByStatusId->row()->s_statusname;
                $query_status = "AND ma_status = '$statusText' ";
            }else{
                $query_status = "";
            }
        }

        $query_invoice = "";
        $getFormnoByInvoice = "";
        if($invoice == "0"){
            $query_invoice = "";
        }else{
            $searchInvoice = searchByInvoice2($invoice);
            if($searchInvoice->num_rows() != 0){
                // $getFormnoByInvoice = $searchInvoice->row()->tr_formno;
                // $query_invoice = "AND ma_formno = '$getFormnoByInvoice'";
                // $searchInvoice = searchByInvoice("SALEE2022-15" , "107551000282");
                $formno_arr = [];
                foreach($searchInvoice->result() as $rs){
                    $formno_arr[] = $rs->tr_formno;
                }
                $resultArr =  json_encode($formno_arr);
                $con_status = str_replace("[" , "(",$resultArr);
                $con_status2 = str_replace("]" , ")",$con_status);
                $query_invoice = "AND ma_formno IN $con_status2";
                // echo $con_status2;
            }else{
                $query_invoice = "";
            }
        }
        

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, "$sql_searchBydate $query_company $query_status $query_invoice")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }

    public function saveConfirmBill()
    {
        if($this->input->post("sedadmin-formno") != ""){
            $formno = $this->input->post("sedadmin-formno");
            $taxid = $this->input->post("sedadmin-taxid");

            $arconfirmbilling = array(
                "tr_status" => "In Progress"
            );
            $this->db->where("tr_formno" , $formno);
            $this->db->where("tr_taxid" , $taxid);
            $this->db->update("bill_trans" , $arconfirmbilling);


            $arconfirmbillingmain = array(
                "ma_status" => "In Progress",
                "ma_ap_name" => $this->input->post("seadmin-username"),
                "ma_ap_ecode" => $this->input->post("seadmin-ecode"),
                "ma_ap_datetime" => date("Y-m-d H:i:s"),
            );
            $this->db->where("ma_formno" , $formno);
            $this->db->where("ma_taxid" , $taxid);
            $this->db->update("bill_main" , $arconfirmbillingmain);

            $this->load->model("email_model");
            $this->email_model->sendEmailStep2_toFinanceAndVender($formno);

            $output = array(
                "msg" => "รายการได้รับการอนุมัติ รอบันทึกข้อมูลทำจ่าย",
                "status" => "Update Data Success"
            );
        }else{
            $output = array(
                "msg" => "พบข้อผิดพลาด",
                "status" => "Update Data Not Success"
            );
        }

        echo json_encode($output);
    }


    public function getBillDetailEdit()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getBillDetailEdit"){
            $formno = $received_data->formno;

            $sqlbillmain = $this->queryBillMain($formno);
            $sqlbilltrans = $this->queryBillTrans($formno);
            $sqlFiles = $this->queryFiles($formno);

            $creditterm = 0;
            $dayFix = 0;
            $dateOfbill = '';
            $dateOfpay = '';
            $dateOfpayReal = '';
            $venderaccount = '';
            $dataareaid = '';
            $ma_datecalc = '';

            $resultGetSetting = $this->getBillSetting();
            $datecalc = $resultGetSetting->row()->set_datecalc;

            if($sqlbillmain->num_rows() != 0){
                $creditterm = $sqlbillmain->row()->ma_payment;
                $dayFix = $sqlbillmain->row()->ma_dayfix;
                $dateOfbill = $sqlbillmain->row()->ma_dateofbilling;
                $dateOfpay = $sqlbillmain->row()->ma_dateofpay;
                $dateOfpayReal = $sqlbillmain->row()->ma_dateofpayreal;
                $venderaccount = $sqlbillmain->row()->ma_venderaccount;
                $dataareaid = $sqlbillmain->row()->ma_dataareaid;
                $memovender = $sqlbillmain->row()->ma_memo_vender;
                $memoadmin = $sqlbillmain->row()->ma_memo_admin;
                $ma_datecalc = $sqlbillmain->row()->ma_dateofcalc;
            }

            $venderInformation = $this->getVenderInformationByaccountParam($venderaccount , $dataareaid);
            $resultDatamain = $this->getDataMain($formno);
            $mainStatus = '';
            if($resultDatamain->num_rows() != 0){
                $mainStatus = $resultDatamain->row()->ma_status;
            }

            // $showDataCalc = '';
            // if($datecalc < 10){
            //     $showDataCalc = "0".$datecalc;
            // }else{
            //     $showDataCalc = $datecalc;
            // }

            $output = array(
                "msg" => "ดึงข้อมูลการวางบิลสำหรับแก้ไข สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sqlbilltrans->result(),
                "resultFiles" => $sqlFiles->result(),
                // "resultBillUp" => $uploadData,
                "creditterm" => $creditterm,
                "dayFix" => $dayFix,
                "dateOfbill" => conDateFromDb($dateOfbill),
                "dateOfpay" => conDateFromDb($dateOfpay),
                "dateOfpayReal" => conDateFromDb($dateOfpayReal),
                "datecalc" => conDateFromDb($ma_datecalc),
                "venderinformation" => $venderInformation->row(),
                "mainstatus" => $mainStatus,
                "memovender" => $memovender,
                "memoadmin" => $memoadmin
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลการวางบิลสำหรับแก้ไข ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }

    private function queryBillTrans($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            bill_trans.tr_autoid,
            bill_trans.tr_billupload_autoid,
            bill_trans.tr_formno,
            bill_trans.tr_taxid,
            bill_trans.tr_venderaccount,
            bill_trans.tr_dataareaid,
            bill_trans.tr_voucher,
            bill_trans.tr_po,
            bill_trans.tr_invoice,
            bill_trans.tr_invoicedate,
            bill_trans.tr_beforetax,
            bill_trans.tr_sumtax,
            bill_trans.tr_includetax,
            bill_trans.tr_payment,
            bill_trans.tr_numofday,
            bill_trans.tr_dayfix,
            bill_trans.tr_dateofbilling,
            bill_trans.tr_dateofpay,
            bill_trans.tr_status,
            bill_trans.tr_datetime,
            bill_trans.tr_datetimemodify,
            billupload.ulstatus
            FROM
            bill_trans
            INNER JOIN billupload ON billupload.autoid = bill_trans.tr_billupload_autoid
            WHERE bill_trans.tr_formno = '$formno' and bill_trans.tr_status in ('Checking','In Progress','Posted')
            ORDER BY bill_trans.tr_autoid ASC
            ");

            return $sql;
        }
    }
    private function queryBillMain($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            bill_main.ma_autoid,
            bill_main.ma_formno,
            bill_main.ma_taxid,
            bill_main.ma_venderaccount,
            bill_main.ma_dataareaid,
            bill_main.ma_payment,
            bill_main.ma_numofday,
            bill_main.ma_dayfix,
            bill_main.ma_dateofbilling,
            bill_main.ma_dateofpay,
            bill_main.ma_dateofpayreal,
            bill_main.ma_datetime,
            bill_main.ma_status,
            bill_main.ma_memo_vender,
            bill_main.ma_memo_admin,
            bill_main.ma_dateofcalc
            FROM
            bill_main
            WHERE ma_formno = '$formno' and ma_status in ('Checking','In Progress','Posted' , 'User Cancel')
            ORDER BY ma_autoid ASC
            ");

            return $sql;
        }
    }
    private function getBillSetting()
    {
        $sql = $this->db->query("SELECT
        set_dateopen,
        set_dateclose,
        set_datecalc,
        set_datefix
        FROM setting_bill
        ");
        return $sql;
    }
    private function queryFiles($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            bf_filename,
            bf_filepath,
            bf_autoid
            FROM bill_files WHERE bf_formno = '$formno'
            ");
            return $sql;
        }
    }



    public function loadBilledReport($startDate , $endDate , $company , $status , $creditterm , $dateofpayreal , $period)
    {
        // DB table to use
        $table = 'billedreport_view';

        // Table's primary key
        $primaryKey = 'autoid';

        $columns = array(
            array('db' => 'taxid', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    $rscon = contaxidToname($d);
                    $names = $rscon->row()->name;
                    return $names;
                }
            ),
            array('db' => 'periodupload', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'invoiceaccount', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'dataareaid', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    $fullCompany = '';
                    switch($d){
                        case "sln":
                            $fullCompany = 'Salee Colour';
                            break;
                        case "ca":
                            $fullCompany = 'Composite Asia';
                            break;
                        case "tbb":
                            $fullCompany = 'The bubbles';
                            break;
                        case "st":
                            $fullCompany = 'Subterra';
                            break;
                    }

                    return $fullCompany;
                }
            ),
            array('db' => 'invoiceid', 'dt' => 4),
            array('db' => 'invoicedate', 'dt' => 5 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'purchid', 'dt' => 6,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'invoiceamount', 'dt' => 7,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'payment', 'dt' => 8,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'tr_periodbilling', 'dt' => 9,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'tr_dateofpayreal', 'dt' => 10,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'tr_datetime', 'dt' => 11,
                'formatter' => function($d , $row){
                    return conDateTimeFromDb($d);
                }
            ),
            array('db' => 'ulstatus', 'dt' => 12,
                'formatter' => function($d , $row){
                    $textColor = "";
                    switch($d){
                        case "User Cancel":
                            $textColor = "style='color:#CC0000;'";
                            break;
                        case "Open":
                            $textColor = "style='color:#0099FF;'";
                            break;
                        case "In Progress":
                            $textColor = "style='color:#8B4513;'";
                            break;
                        case "Posted":
                            $textColor = "style='color:#009900;'";
                            break;
                    }
                    return "<span ".$textColor."><b>$d</b></span>";
                }
            ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        $sql_searchBydate = "";
        
        if($startDate == "0" && $endDate == "0"){
            $sql_searchBydate = "invoicedate LIKE '%%' ";
        }else if($startDate == "0" && $endDate != "0"){
            $sql_searchBydate = "invoicedate BETWEEN '$endDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate != "0"){
            $sql_searchBydate = "invoicedate BETWEEN '$startDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate == "0"){
            $sql_searchBydate = "invoicedate BETWEEN '$startDate 00:00:01' AND '$startDate 23:59:59' ";
        }



        $query_company = "";
        if($company == "0"){
            $query_company = "";
        }else{
            $query_company = "AND dataareaid = '$company' ";
        }


        $query_status = "";
        $con_status = "";
        $getDataByStatusId = "";
        $statusText = "";
        if($status == "0"){
            $query_status = "";
        }else{
            $con_status = str_replace("-" , " ",$status);
            $getDataByStatusId = getStatus($status);
            if($getDataByStatusId->num_rows() != 0){
                $statusText = $getDataByStatusId->row()->s_statusname;
                $query_status = "AND ulstatus = '$statusText' ";
            }else{
                $query_status = "";
            }
        }


        $query_creditterm = "";
        if($creditterm == "0"){
            $query_creditterm = "";
        }else{
            $conNumofday = conNumofday($creditterm)->row()->paymtermid;
            $query_creditterm = "AND payment = '$conNumofday'";
        }

        $query_dateofpayreal = "";
        if($dateofpayreal == "0"){
            $query_dateofpayreal = "";
        }else{
            $query_dateofpayreal = "AND tr_dateofpayreal = '$dateofpayreal'";
        }

        $query_period = "";
        if($period == "0"){
            $query_period = "";
        }else{
            $query_period = "AND periodupload = '$period'";
        }


        

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, "$sql_searchBydate $query_company $query_status $query_creditterm $query_dateofpayreal $query_period")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }

    public function loaddatareportConditionSum()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "loaddatareportConditionSum"){
            $startDate = $received_data->startDate_filter;
            $endDate = $received_data->endDate_filter;
            $company = $received_data->company_filter;
            $status = $received_data->status_filter;
            $creditterm = $received_data->creditterm_filter;
            $datepayreal = $received_data->datepayreal_filter;
            $period = $received_data->period_filter;

            $sql_searchBydate = "";
        
            if($startDate == "0" && $endDate == "0"){
                $sql_searchBydate = "invoicedate LIKE '%%' ";
            }else if($startDate == "0" && $endDate != "0"){
                $sql_searchBydate = "invoicedate BETWEEN '$endDate 00:00:01' AND '$endDate 23:59:59' ";
            }else if($startDate != "0" && $endDate != "0"){
                $sql_searchBydate = "invoicedate BETWEEN '$startDate 00:00:01' AND '$endDate 23:59:59' ";
            }else if($startDate != "0" && $endDate == "0"){
                $sql_searchBydate = "invoicedate BETWEEN '$startDate 00:00:01' AND '$startDate 23:59:59' ";
            }
    
    
    
            $query_company = "";
            if($company == "0"){
                $query_company = "";
            }else{
                $query_company = "AND dataareaid = '$company' ";
            }
    
    
            $query_status = "";
            $con_status = "";
            $getDataByStatusId = "";
            $statusText = "";
            if($status == "0"){
                $query_status = "";
            }else{
                $con_status = str_replace("-" , " ",$status);
                $getDataByStatusId = getStatus($status);
                if($getDataByStatusId->num_rows() != 0){
                    $statusText = $getDataByStatusId->row()->s_statusname;
                    $query_status = "AND ulstatus = '$statusText' ";
                }else{
                    $query_status = "";
                }
            }
    
    
            $query_creditterm = "";
            if($creditterm == "0"){
                $query_creditterm = "";
            }else{
                $conNumofday = conNumofday($creditterm)->row()->paymtermid;
                $query_creditterm = "AND payment = '$conNumofday'";
            }
    
            $query_dateofpayreal = "";
            if($datepayreal == "0"){
                $query_dateofpayreal = "";
            }else{
                $query_dateofpayreal = "AND tr_dateofpayreal = '$datepayreal'";
            }
    
            $query_period = "";
            if($period == "0"){
                $query_period = "";
            }else{
                $conPeriodTotime = strtotime($period);
                $query_period = "AND dataPeriod1 = '$conPeriodTotime'";
            }

            $sql = $this->db->query("SELECT
            count(invoiceid)as invoice,
            sum(invoiceamount)as totalamount
            FROM
            (billupload
            LEFT JOIN bill_trans ON (bill_trans.tr_billupload_autoid = billupload.autoid))
            WHERE $sql_searchBydate $query_company $query_status $query_creditterm $query_dateofpayreal $query_period
            ORDER BY
            dataYear DESC,
            dataMonth DESC");

            $output = array(
                "msg" => "ดึงข้อมูลการ Sum ของรายงานสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row()
            );

        }else{
            $output = array(
                "msg" => "ดึงข้อมูลการ Sum ของรายงานไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }

    public function loadBilledReportsum($company)
    {
        $query_company = "";
        $viewTable = "";
        if($company == "0"){
            $query_company = "";
            $viewTable = "billedsumreport_view";
        }else{
            $query_company = "dataareaid = '$company' ";
            $viewTable = "billedsumreportgroup_view";
        }

        // DB table to use
        $table = $viewTable;

        // Table's primary key
        $primaryKey = 'period';

        $columns = array(
            array('db' => 'period', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'total_bill', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'total_billing', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'total_billed', 'dt' => 3),
            // array('db' => 'period', 'dt' => 4 ,
            //     'formatter' => function($d , $row){
            //         $getBillAmount = getAmountReportSum($d , "all");
            //         return $getBillAmount->row()->amount;
            //     }
            // ),
            array('db' => 'excludevat', 'dt' => 4 ,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'vat', 'dt' => 5 ,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'includevat', 'dt' => 6 ,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'billing_amount', 'dt' => 7,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'billed_amount', 'dt' => 8,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'total_vender', 'dt' => 9,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'total_venderbilling', 'dt' => 10,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'total_venderbilled', 'dt' => 11,
            'formatter' => function($d , $row){
                return $d;
            }
        ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');






        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, "$query_company")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }

    public function getVenderInformationByaccountParam($venderaccount , $dataareaid)
    {
        if($venderaccount != "" && $dataareaid != ""){

            $sql = $this->db_mssql->query("SELECT
            bpc_whtid,
            accountnum,
            name,
            slc_fname,
            slc_lname,
            address,
            phone,
            paymtermid,
            dataareaid
            FROM vendtable
            WHERE accountnum = '$venderaccount' and dataareaid = '$dataareaid'
            ");

            $sql2 = $this->db_mssql2->query("SELECT
            bpc_whtid,
            accountnum,
            name,
            slc_fname,
            slc_lname,
            address,
            phone,
            paymtermid,
            dataareaid
            FROM vendtable
            WHERE accountnum = '$venderaccount' and dataareaid = '$dataareaid'
            ");

            if($sql->num_rows() == 0){
                return $sql2;
            }else{
                return $sql;
            }

        }

    }

    private function getDataMain($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            ma_taxid,
            ma_venderaccount,
            ma_dataareaid,
            ma_payment,
            ma_numofday,
            ma_dayfix,
            ma_dateofbilling,
            ma_dateofpay,
            ma_status
            FROM bill_main WHERE ma_formno = '$formno'
            ");

            return $sql;
        }
    }


    public function saveBillSetting()
    {
        if($this->input->post("billDateFix") != ""){
            $arsaveBillSetting = array(
                "set_dateopen" => $this->input->post("billDateOpen"),
                "set_dateclose" => $this->input->post("billDateClose"),
                "set_datecalc" => $this->input->post("billDateCalc"),
                "set_datefix" => $this->input->post("billDateFix"),
                "set_user" => $this->input->post("set_user"),
                "set_ecode" => $this->input->post("set_ecode"),
                "set_datetime" => date("Y-m-d H:i:s")
            );

            $sqlCheckData = $this->db->query("SELECT * FROM setting_bill");
            if($sqlCheckData->num_rows() == 0){
                $this->db->insert("setting_bill" , $arsaveBillSetting);
            }else{
                $this->db->update("setting_bill" , $arsaveBillSetting);
            }

            $output = array(
                "msg" => "บันทึกข้อมูลการตั้งค่าสำเร็จ",
                "status" => "Update Data Success",
            );
        }else{
            $output = array(
                "msg" => "บันทึกข้อมูลการตั้งค่าไม่สำเร็จ",
                "status" => "Update Data Not Success",
            );
        }
        echo json_encode($output);
    }


    public function loadBillSetting()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "loadBillSetting"){
            $sql = $this->db->query("SELECT
            set_dateopen,
            set_dateclose,
            set_datecalc,
            set_datefix,
            set_user,
            set_ecode,
            set_datetime
            FROM setting_bill
            ");

            $output = array(
                "msg" => "ดึงข้อมูลการตั้งค่าสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลการตั้งค่าไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }


    public function delInvoice()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "delInvoice"){
            $autoid = $received_data->autoid;
            $this->db->where("autoid" , $autoid);
            $this->db->delete("billupload");

            $output = array(
                "msg" => "ลบข้อมูลสำเร็จ",
                "status" => "Delete Data Success"
            );
        }else{
            $output = array(
                "msg" => "ลบข้อมูลไม่สำเร็จ",
                "status" => "Delete Data Not Success"
            );
        }

        echo json_encode($output);
    }

    public function saveConfirmPay()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveConfirmPay"){
            $formno = $received_data->formno;
            $taxid = $received_data->taxid;
            $memovender = $received_data->memovender;
            $memoadmin = $received_data->memoadmin;
            $fnname = $received_data->fnname;
            $fnecode = $received_data->fnecode;

            //update status and memo if not null
            $arupdatemain = array(
                "ma_status" => "Posted",
                "ma_memo_vender" => $memovender,
                "ma_memo_admin" => $memoadmin,
                "ma_fn_name" => $fnname,
                "ma_fn_ecode" => $fnecode,
                "ma_fn_datetime" => date("Y-m-d H:i:s")
            );
            $this->db->where("ma_formno" , $formno);
            $this->db->where("ma_taxid" , $taxid);
            $this->db->update("bill_main" , $arupdatemain);

            $arupdatetrans = array(
                "tr_status" => "Posted",
            );
            $this->db->where("tr_formno" , $formno);
            $this->db->where("tr_taxid" , $taxid);
            $this->db->update("bill_trans" , $arupdatetrans);

            //Select Data for update status on upload data
            $biiTransData = $this->getBillTransByFormno($formno);
            if($biiTransData->num_rows() != 0){
                foreach($biiTransData->result() as $rs){
                    $arupdateStatus = array(
                        "ulstatus" => "Posted"
                    );
                    $this->db->where("autoid" , $rs->tr_billupload_autoid);
                    $this->db->update("billupload" , $arupdateStatus);
                }
            }

            $this->load->model("email_model");
            $this->email_model->sendEmailStep3_toAccountAndVender($formno);

            $output = array(
                "msg" => "บันทึกข้อมูลการทำจ่ายเรียบร้อยแล้ว",
                "status" => "Update Data Success"
            );
        }else{
            $output = array(
                "msg" => "บันทึกข้อมูลไม่สำเร็จ",
                "status" => "Update Data Not Success"
            );
        }

        echo json_encode($output);
    }
    private function getBillTransByFormno($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            tr_billupload_autoid
            FROM bill_trans WHERE tr_formno = '$formno'
            ");
            return $sql;
        }
    }

    public function saveUploadFile()
    {
        if($this->input->post("ap-file-formno") != ""){
            $formno = $this->input->post("ap-file-formno");
            $taxid = $this->input->post("ap-file-taxid");
            $user = $this->input->post("ap-file-user");
            $ecode = $this->input->post("ap-file-ecode");
            $fileInput = "ap-file_name";

            uploadFiles($fileInput , $formno , $taxid , $user , $ecode);

            $output = array(
                "msg" => "อัพโหลดไฟล์สำเร็จ",
                "status" => "Upload Data Success"
            );
        }else{
            $output = array(
                "msg" => "อัพโหลดไฟล์ไม่สำเร็จ",
                "status" => "Upload Data Not Success"
            );
        }

        echo json_encode($output);
    }


    public function deleteFiles()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "deleteFiles"){
            $filename = $received_data->filename;
            $filepath = $received_data->filepath;
            $fileautoid = $received_data->fileautoid;

            $path = $_SERVER['DOCUMENT_ROOT']."/intsys/ebilling/ebilling_backend/".$filepath.$filename;
            unlink($path);

            $this->db->where('bf_autoid' , $fileautoid);
            $this->db->delete('bill_files');

            $output = array(
                "msg" => "ลบไฟล์สำเร็จ",
                "status" => "Delete Data Success",
            );
        }else{
            $output = array(
                "msg" => "ลบไฟล์ไม่สำเร็จ",
                "status" => "Delete Data Not Success",
            );
        }
        echo json_encode($output);
    }

    public function getVenderdata()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getVenderdata"){
            $venderdataInput = $received_data->venderdataInput;

            //search venderdata on sln , ca database
            // $sql = $this->db_mssql->query("SELECT
            // bpc_whtid,
            // accountnum,
            // slc_fname,
            // slc_lname
            // FROM vendtable
            // WHERE MATCH(bpc_whtid, accountnum , slc_fname , slc_lname) AGAINST('$venderdataInput') 
            // ");

            $idArr = explode(" ", $venderdataInput); 
            $context = " CONCAT(bpc_whtid,' ', 
            accountnum,' ',
            name) "; 
            $condition = " $context LIKE '%" . implode("%' OR $context LIKE '%", $idArr) . "%' "; 

            $sql = $this->db_mssql->query("SELECT TOP 50  
            bpc_whtid,
            accountnum,
            name,
            dataareaid
            FROM vendtable
            WHERE $condition AND dataareaid IN ('sln' , 'ca')
            GROUP BY bpc_whtid , name , accountnum , dataareaid
            ORDER BY bpc_whtid DESC");

            $sql2 = $this->db_mssql2->query("SELECT TOP 50  
            bpc_whtid,
            accountnum,
            name,
            dataareaid
            FROM vendtable
            WHERE $condition 
            GROUP BY bpc_whtid , name , accountnum , dataareaid
            ORDER BY bpc_whtid DESC");

            $queryResult = "";
            if($sql->num_rows() != 0){
                $queryResult = $sql->result();
            }else{
                $queryResult = $sql2->result();
            }

            $output = array(
                "msg" => "ค้นหาข้อมูล Vender สำเร็จ",
                "status" => "Select Data Success",
                "venderdata" => $queryResult
            );

        }else{
            $output = array(
                "msg" => "ค้นหาข้อมูล Vender ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }

    public function checkVenderActivate()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "checkVenderActivate"){
            $taxid = $received_data->taxid;
            $sql = $this->db->query("SELECT
            vm_taxid
            FROM vender_member WHERE vm_taxid = '$taxid'
            ");
            
            $resultCheck = 0;
            if($sql->num_rows() != 0){
                $resultCheck = 1;
            }

            $output = array(
                "msg" => "เช็คข้อมูลสำเร็จ",
                "status" => "Select Data Success",
                "result" => $resultCheck
            );
        }else{
            $output = array(
                "msg" => "เช็คข้อมูลไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }

    public function loadAnnounceData_byvender($taxid)
    {
        // DB table to use
        $table = 'announce_view';

        // Table's primary key
        $primaryKey = 'an_autoid';

        $columns = array(
            array('db' => 'an_datetime', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    return conDateTimeFromDb($d);
                }
            ),
            array('db' => 'an_taxid', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    $result = getDataVenderByTaxid($d);
                    $html = '';
                    if($result->num_rows() != 0){
                        $html .='
                        <span>'.$result->row()->name.'</span><br>
                        <span>'.$result->row()->bpc_whtid.'</span>
                        ';
                    }
                    return $html;
                }
            ),
            array('db' => 'an_text', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    $html = '<span>'.$d.'</span>';
                    return $html;
                }
            ),
            array('db' => 'an_status', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    $iconStatus = '';
                    if($d == "เผยแพร่"){
                        $iconStatus = '<i class="dw dw-tick iconStatusYes"></i>';
                    }else if($d == "ไม่เผยแพร่"){
                        $iconStatus = '<i class="dw dw-stop iconStatusNo"></i>';
                    }
                    return $iconStatus;
                }
            ),
            array('db' => 'an_autoid', 'dt' => 4 ,
            'formatter' => function($d , $row){
                $html = '
                <i class="dw dw-edit-file editAnnSub" data-toggle="modal" data-target="#editAnnounce_modal"
                    data_autoid="'.$d.'"
                ></i>
                ';
                return $html;
            }
        ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        if($taxid != 0){
            $queryTaxid = "an_type = 'ประกาศย่อย' AND an_taxid = '$taxid'";
        }else{
            $queryTaxid = "an_type = 'ประกาศย่อย'";
        }


        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null,"$queryTaxid")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }


    public function getDataGraph()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDataGraph"){
            //Get data number
            $billuploadAll = $this->getNumberBillUpload();
            $billuploadWait = $this->getNumberBillUpload("ulstatus IN ('Open')");
            $billuploadBilling = $this->getNumberBillUpload("ulstatus IN ('In Progress')");
            $billuploadBilled = $this->getNumberBillUpload("ulstatus IN ('Posted')");

            $output = array(
                "msg" => "ดึงข้อมูลสำเร็จ",
                "status" => "Select Data Success",
                "billuploadAll" => $billuploadAll->row()->billall,
                "billuploadWait" => $billuploadWait->row()->billall,
                "billuploadBilling" => $billuploadBilling->row()->billall,
                "billuploadBilled" => $billuploadBilled->row()->billall,
            );



        }else{
            $output = array(
                "msg" => "ดึงข้อมูลไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }
        echo json_encode($output);
    }
    private function getNumberBillUpload($condition = "")
    {
        $queryByContidion = "";
        if($condition != ""){
            $queryByContidion = "WHERE $condition";
        }else{
            $queryByContidion = "";
        }
        $sql = $this->db->query("SELECT
        count(autoid)as billall
        From billupload $queryByContidion
        ");
        return $sql;
    }


    public function getYearList()
    {
        $sql = $this->db->query("SELECT
        dataYear
        FROM billupload GROUP BY dataYear ORDER BY dataYear DESC
        ");

        $output = array(
            "msg" => "ดึงข้อมูลปีสำเร็จ",
            "status" => "Select Data Success",
            "yearList" => $sql->result(),
            "yearNow" => date("Y")
        );

        echo json_encode($output);
    }

    public function getGraph1()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDataForGraph1"){
            $selectYear = $received_data->selectYear;

            $resultAll = $this->graph1_getData($selectYear , "");
            $resultWait = $this->graph1_getData($selectYear , "รอวางบิล");
            $resultBilling = $this->graph1_getData($selectYear , "กำลังวางบิล");
            $resultBilled = $this->graph1_getData($selectYear , "วางบิลเรียบร้อยแล้ว");

            $output = array(
                "msg" => "ดึงข้อมูลกราฟสำเร็จ",
                "status" => "Select Data Success",
                "resultAll" => $resultAll->result(),
                "resultWait" => $resultWait->result(),
                "resultBilling" => $resultBilling->result(),
                "resultBilled" => $resultBilled->result()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลกราฟไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }
        echo json_encode($output);
    }
    private function graph1_getData($selectYear , $condition = "")
    {
        $query = "";
        if($condition == ""){
            $query = "ulstatus LIKE '%%'";
        }else{
            $query = "ulstatus = '$condition'";
        }
        $sql = $this->db->query("SELECT count(invoiceid)AS invoice , dataMonth , dataYear FROM billupload WHERE dataYear = '$selectYear' AND $query group by dataMonth");

        return $sql;
    }


    public function saveAnnounceVender()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveAnnounceVender"){
            $taxid = $received_data->taxid;
            $announceText = $received_data->announceText;
            $username = $received_data->username;
            $ecode = $received_data->ecode;

            $arInsertAnnounce = array(
                "an_taxid" => $taxid,
                "an_text" => $announceText,
                "an_type" => "ประกาศย่อย",
                "an_status" => "เผยแพร่",
                "an_user" => $username,
                "an_ecode" => $ecode,
                "an_datetime" => date("Y-m-d H:i:s")
            );
            $sql = $this->db->insert("announce" , $arInsertAnnounce);

            $output = array(
                "msg" => "บันทึกข้อมูลประกาศสำเร็จ",
                "status" => "Insert Data Success",
                "taxid" => $taxid ,
                "text" => $announceText
            );
        }else{
            $output = array(
                "msg" => "บันทึกข้อมูลประกาศไม่สำเร็จ",
                "status" => "Insert Data Not Success",
            );
        }

        echo json_encode($output);
    }


    public function getDataAnnByAutoid()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDataAnnByAutoid"){
            $autoid = $received_data->autoid;

            $sql = $this->db->query("SELECT
            an_text,
            an_status
            FROM announce WHERE an_autoid = '$autoid'
            ");

            $output = array(
                "msg" => "ดึงข้อมูลประกาศสำเร็จ",
                "status" => "Select Data Success",
                "annText" => $sql->row()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลประกาศสำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }



    public function saveEditAnnounceVender()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveEditAnnounceVender"){
            $autoid = $received_data->autoid;
            $username = $received_data->username;
            $ecode = $received_data->ecode;
            $textEdit = $received_data->textEdit;
            $status = $received_data->status;

            $arupdate = array(
                "an_text" => $textEdit,
                "an_user_modify" => $username,
                "an_ecode_modify" => $ecode,
                "an_datetime_modify" => date("Y-m-d H:i:s"),
                "an_status" => $status
            );
            $this->db->where("an_autoid" , $autoid);
            $this->db->update("announce" , $arupdate);

            $output = array(
                "msg" => "อัพเดตข้อมูลสำเร็จ",
                "status" => "Update Data Success"
            );
        }else{
            $output = array(
                "msg" => "อัพเดตข้อมูลไม่สำเร็จ",
                "status" => "Update Data Not Success"
            );
        }

        echo json_encode($output);
    }


    public function loadAnnounceDataMain()
    {
        // DB table to use
        $table = 'announce_view';

        // Table's primary key
        $primaryKey = 'an_autoid';

        $columns = array(
            array('db' => 'an_datetime', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    return conDateTimeFromDb($d);
                }
            ),
            array('db' => 'an_text', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    $html = '<span>'.$d.'</span>';
                    return $html;
                }
            ),
            array('db' => 'an_status', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    $iconStatus = '';
                    if($d == "เผยแพร่"){
                        $iconStatus = '<i class="dw dw-tick iconStatusYes"></i>';
                    }else if($d == "ไม่เผยแพร่"){
                        $iconStatus = '<i class="dw dw-stop iconStatusNo"></i>';
                    }
                    return $iconStatus;
                }
            ),
            array('db' => 'an_autoid', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    $html = '
                    <i class="dw dw-edit-file editAnnMain" data-toggle="modal" data-target="#editAnnounceMain_modal"
                        data_autoid="'.$d.'"
                    ></i>
                    ';
                    return $html;
                }
            ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        $queryAnnounceMain = "an_type = 'ประกาศหลัก'";


        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null,"$queryAnnounceMain")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }


    public function saveAnnounceMain()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveAnnounceMain"){
            $textMain = $received_data->textMain;
            $username = $received_data->username;
            $ecode = $received_data->ecode;

            $arInsert = array(
                "an_text" => $textMain,
                "an_user" => $username,
                "an_ecode" => $ecode,
                "an_datetime" => date("Y-m-d H:i:s"),
                "an_type" => "ประกาศหลัก",
                "an_status" => "เผยแพร่"
            );

            $this->db->insert("announce" , $arInsert);

            $output = array(
                "msg" => "บันทึกข้อมูลประกาศหลักสำเร็จ",
                "status" => "Insert Data Success"
            );
        }else{
            $output = array(
                "msg" => "บันทึกข้อมูลประกาศหลักไม่สำเร็จ",
                "status" => "Insert Data Not Success"
            );
        }

        echo json_encode($output);
    }


    public function saveAnnounceMainEdit()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveAnnounceMainEdit"){
            $autoid = $received_data->autoid;
            $username = $received_data->username;
            $ecode = $received_data->ecode;
            $textMain = $received_data->textMain;
            $status = $received_data->status;

            $arupdateData = array(
                "an_text" => $textMain,
                "an_user_modify" => $username,
                "an_ecode_modify" => $ecode,
                "an_datetime_modify" => date("Y-m-d H:i:s"),
                "an_status" => $status
            );
            $this->db->where("an_autoid" , $autoid);
            $this->db->update("announce" , $arupdateData);

            $output = array(
                "msg" => "บันทึกการแก้ไขข้อมูลสำเร็จ",
                "status" => "Update Data Success"
            );
        }else{
            $output = array(
                "msg" => "บันทึกการแก้ไขข้อมูลไม่สำเร็จ",
                "status" => "Update Data Not Success"
            );
        }

        echo json_encode($output);
    }

    public function saveSchedule()
    {
        
        if($this->input->post("sche-dayOpen") != "" && $this->input->post("sche-timeOpen") != "" && $this->input->post("sche-timeClose") != "" && $this->input->post("sche-dayClose") != "" && $this->input->post("sche-dayCalc") != "" && $this->input->post("sche-dayPay") != ""){

            $yearandmonth = date_create($this->input->post("sche-year"));
            $sc_year = date_format($yearandmonth , "Y");
            $sc_month = date_format($yearandmonth , "m");

            if($this->input->post("sche-autoid") == ""){
                // check data duplicate
                $resultCheckScheduleDuplicate = $this->checkScheduleDuplicate($sc_year , $sc_month);
                if($resultCheckScheduleDuplicate->num_rows() == 0){

                    $arInsert = array(
                        "sc_year" => $sc_year,
                        "sc_month" => $sc_month,
                        "sc_dateOpen" => $this->input->post("sche-dayOpen"),
                        "sc_timeOpen" => $this->input->post("sche-timeOpen"),
                        "sc_dateClose" => $this->input->post("sche-dayClose"),
                        "sc_timeClose" => $this->input->post("sche-timeClose"),
                        "sc_dateCalc" => $this->input->post("sche-dayCalc"),
                        "sc_datePay" => $this->input->post("sche-dayPay"),
                        "sc_username" => $this->input->post("sche-username"),
                        "sc_ecode" => $this->input->post("sche-ecode"),
                        "sc_datetime" => date("Y-m-d H:i:s")
                    );
                    
                    $this->db->insert("schedule" , $arInsert);
        
                    $output = array(
                        "msg" => "บันทึกข้อมูลการตั้งค่า Schedule สำเร็จ",
                        "status" => "Insert Data Success"
                    );
                }else{
                    $output = array(
                        "msg" => "พบข้อมูลซ้ำในระบบ",
                        "status" => "Found Duplicate Data"
                    );
                }
            }else{


                $arUpdate = array(
                    "sc_dateOpen" => $this->input->post("sche-dayOpen"),
                    "sc_timeOpen" => $this->input->post("sche-timeOpen"),
                    "sc_dateClose" => $this->input->post("sche-dayClose"),
                    "sc_timeClose" => $this->input->post("sche-timeClose"),
                    "sc_dateCalc" => $this->input->post("sche-dayCalc"),
                    "sc_datePay" => $this->input->post("sche-dayPay"),
                    "sc_username_modify" => $this->input->post("sche-username"),
                    "sc_ecode_modify" => $this->input->post("sche-ecode"),
                    "sc_datetime_modify" => date("Y-m-d H:i:s")
                );

                $this->db->where("sc_autoid" , $this->input->post("sche-autoid"));
                $this->db->update("schedule" , $arUpdate);
                $output = array(
                    "msg" => "อัพเดตข้อมูลการตั้งค่า Schedule สำเร็จ",
                    "status" => "Update Data Success"
                );

            }

        }else{
            $output = array(
                "msg" => "บันทึกข้อมูลการตั้งค่า Schedule ไม่สำเร็จ",
                "status" => "Insert Data Not Success"
            );
        }
        echo json_encode($output);
    }
    private function checkScheduleDuplicate($year , $month)
    {
        if($year != "" && $month != ""){
            $sql = $this->db->query("SELECT
            sc_year,
            sc_month,
            sc_autoid
            FROM schedule WHERE sc_year = '$year' AND sc_month = '$month'
            ");

            return $sql;
        }
    }

    public function loadScheduleSetting()
    {
        // DB table to use
        $table = 'schedule_view';

        // Table's primary key
        $primaryKey = 'sc_autoid';

        $columns = array(
            array('db' => 'sc_year', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'sc_month', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'sc_dateOpen', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'sc_timeOpen', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'sc_dateClose', 'dt' => 4 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'sc_timeClose', 'dt' => 5 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'sc_dateCalc', 'dt' => 6 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'sc_datePay', 'dt' => 7 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'sc_autoid', 'dt' => 8 ,
                'formatter' => function($d , $row){
                    $html = '
                    <div class="text-center">
                        <i class="dw dw-edit2 editSchedule" data_autoid="'.$d.'"></i>
                        <i class="dw dw-trash1 ml-3 delSchedule" data_autoid="'.$d.'"></i>
                    </div>
                    ';
                    return $html;
                }
            ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null,null)
        );

        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }


    public function getScheduleData()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getScheduleData"){
            $autoid = $received_data->autoid;
            $sql = $this->db->query("SELECT
            sc_year,
            sc_month,
            sc_dateOpen,
            sc_timeOpen,
            sc_dateClose,
            sc_timeClose,
            sc_dateCalc,
            sc_datePay,
            sc_autoid
            FROM schedule WHERE sc_autoid = '$autoid'
            ");

            $resultCheck = "";
            if($sql->num_rows() != ""){
                $resultCheck = $this->getDataBillingForChangeSchedule($sql->row()->sc_year , $sql->row()->sc_month);
            }

            if($resultCheck == 0){
                $output = array(
                    "msg" => "ดึงข้อมูล Schedule สำเร็จ",
                    "status" => "Select Data Success",
                    "result" => $sql->row(),
                    "test" => $resultCheck
                );
            }else{
                $output = array(
                    "msg" => "ตรวจพบรายการกำลังวางบิลไม่สามารถแก้ไข Schedule ได้",
                    "status" => "Found Billing Data",
                    "test" => $resultCheck
                );
            }


        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Schedule ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }
    private function getDataBillingForChangeSchedule($year , $month)
    {
        if($year != "" && $month != ""){
            $sql = $this->db->query("SELECT
            ulstatus
            FROM billupload WHERE dataYear = '$year' AND dataMonth = '$month' AND ulstatus NOT IN ('Open')
            ");

            return $sql->num_rows();
        }
    }


    public function delScheduleData()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "delScheduleData"){
            $autoid = $received_data->autoid;

            $dataYear = "";
            $dataMonth = "";

            // Check upload Data
            $rsScheduleData = $this->getDataScheduleForCheckUploadData($autoid);
            if($rsScheduleData->num_rows() != 0){
                $dataYear = $rsScheduleData->row()->sc_year;
                $dataMonth = $rsScheduleData->row()->sc_month;
            }

            $rsDataUpload = $this->getDataUploadForCheck($dataYear , $dataMonth);
            if($rsDataUpload->num_rows() != 0){
                $output = array(
                    "msg" => "ไม่สามารถลบ Schedule ได้เนื่องจากตรวจพบรายการ Upload กำลังรอดำเนินการอยู่",
                    "status" => "Can Not Delete Data"
                );
            }else{
                $this->db->where("sc_autoid" , $autoid);
                $this->db->delete("schedule");

                $output = array(
                    "msg" => "ลบรายการสำเร็จ",
                    "status" => "Delete Data Success"
                );
            }

        }else{
            $output = array(
                "msg" => "ไม่สามารถลบรายการได้พบข้อผิดพลาด",
                "status" => "Delete Data Not Success"
            );
        }
        echo json_encode($output);
    }
    private function getDataScheduleForCheckUploadData($autoid)
    {
        if($autoid != ""){
            $sql = $this->db->query("SELECT
            sc_year,
            sc_month
            FROM schedule WHERE sc_autoid = '$autoid'
            ");
            return $sql;
        }
    }
    private function getDataUploadForCheck($year , $month)
    {
        if($year != "" && $month != ""){
            $sql = $this->db->query("SELECT
            dataMonth,
            dataYear
            FROM billupload WHERE dataYear = '$year' AND dataMonth = '$month'
            ");
            return $sql;
        }
    }


    public function getScheduleDataForCheck()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getScheduleDataForCheck"){
            $month = $received_data->month;
            $year = $received_data->year;

            $sql = $this->db->query("SELECT
            sc_year,
            sc_month
            FROM schedule WHERE sc_year = '$year' AND sc_month = '$month'
            ");
            
            $output = array(
                "msg" => "ดึงข้อมูล Schedule สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->num_rows()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Schedule ไม่สำเร็จ",
                "status" => "Select Data Not Success"
            );
        }

        echo json_encode($output);
    }

    public function loadUserPermission()
    {
        // DB table to use
        $table = 'userpermission_view';

        // Table's primary key
        $primaryKey = 'u_autoid';

        $columns = array(
            array('db' => 'u_ecode', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'u_username', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'u_fullname', 'dt' => 2 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'u_dept', 'dt' => 3 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'u_datetime', 'dt' => 4 ,
                'formatter' => function($d , $row){
                    return conDateTimeFromDb($d);
                }
            ),
            array('db' => 'u_userstatus', 'dt' => 5 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'u_autoid', 'dt' => 6 ,
                'formatter' => function($d , $row){
                    $html = '
                    <div class="text-center">
                        <i class="dw dw-edit2 editUser" data-toggle="modal" data-target="#addUser_modal" data_autoid="'.$d.'"></i>
                        <i class="dw dw-trash1 ml-3 delUser" data_autoid="'.$d.'"></i>
                    </div>
                    ';
                    return $html;
                }
            ),
        );

        // SQL server connection information
        $sql_details = array(
            'user' => getDb()->db_username,
            'pass' => getDb()->db_password,
            'db'   => getDb()->db_databasename,
            'host' => getDb()->db_host
        );

        /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
                * If you just want to use the basic configuration for DataTables with PHP
                * server-side, there is no need to edit below this line.
                */
        // $path = $_SERVER['DOCUMENT_ROOT']."/intsys/oss/server-side/scripts/ssp.class.php";
        require('server-side/scripts/ssp.class.php');

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null,null)
        );

        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }

    public function searchUser()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "searchuser"){
            $searchuser = $received_data->searchuser;

            $idArr = explode(" ", $searchuser); 
            $context = " CONCAT(ecode,' ',username,' ',Fname,' ',Lname,' ',Dept,' ',DeptCode,' ',memberemail) "; 
            $condition = " $context LIKE '%" . implode("%' OR $context LIKE '%", $idArr) . "%' "; 

            $sql = $this->db2->query("SELECT
            ecode,
            username,
            Fname,
            Lname,
            Dept,
            DeptCode,
            resigned,
            memberemail
            FROM member WHERE $condition AND resigned = 0
            ORDER BY Fname
            ");

            $output = array(
                "msg" => "ดึงข้อมูลผู้ใช้สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลผู้ใช้ไม่สำเร็จ",
                "status" => "Select Data Not Success"
            );
        }

        echo json_encode($output);
    }


    public function saveUserSetting()
    {
        if($this->input->post("ip-addUser-autoid") == ""){
            if($this->input->post("ip-addUser-ecode") != ""){
                $arsaveUser = array(
                    "u_username" => $this->input->post("ip-addUser-username"),
                    "u_fullname" => $this->input->post("ip-addUser-fullname"),
                    "u_ecode" => $this->input->post("ip-addUser-ecode"),
                    "u_email" => $this->input->post("ip-addUser-email"),
                    "u_dept" => $this->input->post("ip-addUser-dept"),
                    "u_deptcode" => $this->input->post("ip-addUser-deptcode"),
                    "u_datetime" => date("Y-m-d H:i:s"),
                    "u_userstatus" => "active",
                    "u_account_section" => $this->input->post("ip-addUser-acc"),
                    "u_ap_section" => $this->input->post("ip-addUser-ap"),
                    "u_finance_section" => $this->input->post("ip-addUser-fn"),
                    "u_admin_section" => $this->input->post("ip-addUser-admin"),
                    "u_upload_section" => $this->input->post("ip-addUser-upload"),
                    "u_upload2_section" => $this->input->post("ip-addUser-uploadPosted"),
    
                    "u_usercreate" => $this->input->post("ip-addUser-login-username"),
                    "u_ecodecreate" => $this->input->post("ip-addUser-login-ecode"),
                    "u_datetimecreate" => date("Y-m-d H:i:s"),
                );
    
                $this->db->insert("user_permission" , $arsaveUser);
    
                $output = array(
                    "msg" => "บันทึกข้อมูล user สำเร็จ",
                    "status" => "Insert Data Success"
                );
            }else{
                $output = array(
                    "msg" => "บันทึกข้อมูล user ไม่สำเร็จ",
                    "status" => "Insert Data Not Success"
                );
            }
        }else if($this->input->post("ip-addUser-autoid") != ""){
            $arsaveUpdateUser = array(

                "u_userstatus" => "active",
                "u_account_section" => $this->input->post("ip-addUser-acc"),
                "u_ap_section" => $this->input->post("ip-addUser-ap"),
                "u_finance_section" => $this->input->post("ip-addUser-fn"),
                "u_admin_section" => $this->input->post("ip-addUser-admin"),
                "u_upload_section" => $this->input->post("ip-addUser-upload"),
                "u_upload2_section" => $this->input->post("ip-addUser-uploadPosted"),

                "u_usermodify" => $this->input->post("ip-addUser-login-username"),
                "u_ecodemodify" => $this->input->post("ip-addUser-login-ecode"),
                "u_datetimemodify" => date("Y-m-d H:i:s"),
            );
            $this->db->where("u_autoid" , $this->input->post("ip-addUser-autoid"));
            $this->db->update("user_permission" , $arsaveUpdateUser);

            $output = array(
                "msg" => "บันทึกการแก้ไขข้อมูล user สำเร็จ",
                "status" => "Update Data Success"
            );
        }

        echo json_encode($output);
    }


    public function getUserPermission()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getUserPermission"){
            $ecode = $received_data->ecode;

            $sql = $this->db->query("SELECT
            user_permission.u_autoid,
            user_permission.u_username,
            user_permission.u_fullname,
            user_permission.u_ecode,
            user_permission.u_email,
            user_permission.u_dept,
            user_permission.u_deptcode,
            user_permission.u_datetime,
            user_permission.u_userstatus,
            user_permission.u_account_section,
            user_permission.u_ap_section,
            user_permission.u_finance_section,
            user_permission.u_admin_section,
            user_permission.u_upload_section,
            user_permission.u_upload2_section,
            user_permission.u_usercreate,
            user_permission.u_ecodecreate,
            user_permission.u_datetimecreate
            FROM
            user_permission
            WHERE u_ecode = '$ecode'
            ");

            $output = array(
                "msg" => "ดึงข้อมูล user สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล user ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }
        echo json_encode($output);
    }


    public function getDataUserSettingForEdit()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDataUserSettingForEdit"){
            $autoid = $received_data->autoid;
            $sql = $this->db->query("SELECT
            user_permission.u_autoid,
            user_permission.u_username,
            user_permission.u_fullname,
            user_permission.u_ecode,
            user_permission.u_email,
            user_permission.u_dept,
            user_permission.u_deptcode,
            user_permission.u_datetime,
            user_permission.u_account_section,
            user_permission.u_ap_section,
            user_permission.u_finance_section,
            user_permission.u_admin_section,
            user_permission.u_upload_section,
            user_permission.u_upload2_section
            FROM
            user_permission
            WHERE u_autoid = '$autoid'");

            $output = array(
                "msg" => "ดึงข้อมูล User สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล User ไม่สำเร็จ",
                "status" => "Select Data Not Success"
            );
        }
        echo json_encode($output);
    }


    public function deleteUser()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "deleteUser"){
            $autoid = $received_data->autoid;
            $this->db->where("u_autoid" , $autoid);
            $this->db->delete("user_permission");

            $output = array(
                "msg" => "ลบข้อมูลสำเร็จ",
                "status" => "Delete Data Success"
            );
        }else{
            $output = array(
                "msg" => "ลบข้อมูลไม่สำเร็จ",
                "status" => "Delete Data Not Success"
            );
        }
        echo json_encode($output);
    }


    public function sendNotifyBeforePay()
    {
        $queryVenderPaying = $this->queryGetVenderPaying();

        $mainformnoPaying = [];
        $email = "";
        $taxid = "";
        foreach($queryVenderPaying->result() as $rs){
            $queryVenderPayingDetail = $this->queryVenderPayingDetail($rs->ma_taxid);

            $taxid = $rs->ma_taxid;

            foreach($queryVenderPayingDetail->result() as $rs){
                $email = $rs->vm_email;
                array_push($mainformnoPaying , $rs->ma_formno);
            }

            
            $this->email_model->sendEmailtoVenderNotifyPay($taxid , $mainformnoPaying , $email);

            $email = "";
            $taxid = "";
            $mainformnoPaying = [];
        }

        $output = array(
            "msg" => "ส่ง Email สำเร็จ",
            "status" => "Send Email Success",
            "datetime" => date("Y-m-d H:i:s")
        );

        echo json_encode($output);
    }
    private function queryGetVenderPaying()
    {
        $sql = $this->db->query("SELECT 
        ma_taxid
        FROM bill_main
        WHERE ma_status = 'Posted' AND 
        DATE_SUB(ma_dateofpayreal, INTERVAL 10 DAY) = CURDATE() 
        GROUP BY ma_taxid");
        return $sql;
    }
    private function queryVenderPayingDetail($taxid)
    {
        if($taxid != ""){
            $sql = $this->db->query("SELECT 
                ma_formno,
                ma_taxid,
                ma_venderaccount,
                ma_dataareaid,
                ma_dateofpayreal,
                DATE_SUB(ma_dateofpayreal, INTERVAL 10 DAY)AS beforeday,
                vm_email,
                ma_status,
                ma_memo_vender
                FROM bill_main
                INNER JOIN vender_member ON ma_taxid = vm_taxid
                WHERE ma_status = 'Posted' AND 
                DATE_SUB(ma_dateofpayreal, INTERVAL 10 DAY) = CURDATE() AND
                ma_taxid = '$taxid'
            ");
            return $sql;
        }
    }









}/* End of file ModelName.php */
