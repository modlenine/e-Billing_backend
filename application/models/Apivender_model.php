<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Apivender_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Bangkok");
        $this->db_mssql = $this->load->database('mssql' , TRUE);
        $this->db_mssql2 = $this->load->database('mssql2' , TRUE);//tbb , st database
    }

    public function checklogin()
    {
        if($this->input->post("vender-username") != "" && $this->input->post("vender-password") != ""){
            $venderUsername = $this->input->post("vender-username");
            $venderPassword = $this->input->post("vender-password");

            // Check ข้อมูลว่ามีการ Activate ไปแล้วหรือยัง
            $sqlCheckData = $this->db->query("SELECT vm_username FROM vender_member WHERE vm_username = '$venderUsername'");
            if($sqlCheckData->num_rows() == 0){
                // Check Default Password
                $defaultPassword = substr($venderUsername , -4 , 4);
                if($venderPassword == $defaultPassword){
                    $output = array(
                        "msg" => "ส่งไปหน้า Activate ข้อมูล",
                        "status" => "Redirect To Activate Page",
                        "taxid" => $venderUsername,
                        "defaultPassword" => md5($defaultPassword),
                    );
                }else{
                    $output = array(
                        "msg" => "Default Password ไม่ถูกต้อง",
                        "status" => "Default Password Incorrect",
                        "taxid" => $venderUsername,
                        "defaultPassword" => $defaultPassword
                    );
                }

            }else if($sqlCheckData->num_rows() > 0){
                // Check Data Login ว่าข้อมูลถูกต้องหรือไม่
                $user = mysqli_real_escape_string($this->escape_string(), $venderUsername);
                $pass = mysqli_real_escape_string($this->escape_string(), md5($venderPassword));

                $sqlLogin = $this->db->query(sprintf("SELECT * FROM vender_member WHERE vm_username='%s' AND vm_password='%s' ", $user, $pass));

                if($sqlLogin->num_rows() == 0){
                    $output = array(
                        "msg" => "ข้อมูล Username หรือ Password ไม่ถูกต้อง",
                        "status" => "Login failed",
                    );
                }else{

                    // Check Activate Data
                    if($sqlLogin->row()->vm_status == "wait activate"){
                        $output = array(
                            "msg" => "กรุณายืนยันตัวตนบน Email ที่ลงทะเบียน",
                            "status" => "Wait Activate Data",
                            "taxid" => $sqlLogin->row()->vm_taxid,
                        );
                    }else if($sqlLogin->row()->vm_status == "active"){

                        $arupdate = array(
                            "vm_lastlogin" => date("Y-m-d H:i:s")
                        );
                        $this->db->where("vm_taxid" , $venderUsername);
                        $this->db->update("vender_member" , $arupdate);

                        $uri = isset($_SESSION['RedirectKe']) ? $_SESSION['RedirectKe'] : '/intsys/ebilling/';
                        // Get Vender Data Information
                        $getVenderData = $this->getVenderInformation($venderUsername);
    
                        $output = array(
                            "msg" => "ลงชื่อเข้าใช้สำเร็จ",
                            "status" => "Login Successfully",
                            "uri" => $uri,
                            "session_vender_data" => $getVenderData->row_array(),
                            "vender_dateExpire" => strtotime(date("Y-m-d H:i:s")."+10 seconds"),
                        );
                    }else if($sqlLogin->row()->vm_status == "reset password"){
                        $arupdate = array(
                            "vm_status" => "active",
                            "vm_resetpass_token" => null,
                            "vm_resetpass_tokentime" => null,
                            "vm_lastlogin" => date("Y-m-d H:i:s")
                        );
                        $this->db->where("vm_taxid" , $venderUsername);
                        $this->db->update("vender_member" , $arupdate);

                        $uri = isset($_SESSION['RedirectKe']) ? $_SESSION['RedirectKe'] : '/intsys/ebilling/';
                        // Get Vender Data Information
                        $getVenderData = $this->getVenderInformation($venderUsername);
    
                        $output = array(
                            "msg" => "ลงชื่อเข้าใช้สำเร็จ",
                            "status" => "Login Successfully",
                            "uri" => $uri,
                            "session_vender_data" => $getVenderData->row_array(),
                            "vender_dateExpire" => strtotime(date("Y-m-d H:i:s")."+10 seconds"),
                        );
                    }



                }
            }
        }

        echo json_encode($output);
    }

    private function escape_string()
    {
        if($_SERVER['HTTP_HOST'] == "localhost"){
            return mysqli_connect("192.168.20.22", "ant", "Ant1234", "saleecolour");
        }else{
            return mysqli_connect("localhost", "ant", "Ant1234", "saleecolour");
        }
    }

    private function getVenderInformation($taxid)
    {
        if($taxid != ""){

            $mssql = $this->db_mssql->query("SELECT
            name,
            slc_fname,
            slc_lname,
            address,
            bpc_whtid
            FROM vendtable WHERE bpc_whtid = '$taxid'
            GROUP BY name , slc_fname , slc_lname , address , bpc_whtid
            ");

            $mssql2 = $this->db_mssql2->query("SELECT
            name,
            slc_fname,
            slc_lname,
            address,
            bpc_whtid
            FROM vendtable WHERE bpc_whtid = '$taxid'
            GROUP BY name , slc_fname , slc_lname , address , bpc_whtid
            ");

            if($mssql->num_rows() != 0){
                return $mssql;
            }else{
                return $mssql2;
            }

            
        }
    }

    public function saveActivate()
    {
        $this->load->model("email_model");
        if($this->input->post("venderActi-username") != "" && $this->input->post("venderActi-password") != "" && $this->input->post("venderActi-email") != ""){

            $venderUsername = $this->input->post("venderActi-username");
            $venderPassword = md5($this->input->post("venderActi-password"));
            $venderEmail = $this->input->post("venderActi-email");
            $link = md5(uniqid(rand(), true));

            // Check Email Duplicate
            $checkEmail = $this->checkEmailDuplicate($venderEmail);
            if($checkEmail->num_rows() == 0){
                if(md5(substr($venderUsername , -4 , 4)) == $this->input->post("codeVerify")){
                    $arInsertVmMember = array(
                        "vm_username" => $venderUsername,
                        "vm_password" => $venderPassword,
                        "vm_taxid" => $venderUsername,
                        "vm_email_temp" => $venderEmail,
                        "vm_status" => "wait activate",
                        "vm_datetime_wait_activate" => date("Y-m-d H:i:s"),
                        "vm_activatecode" => $link,
                        "vm_expire_linkactivate" => strtotime('+24 hours')
                    );
        
                    $this->db->insert("vender_member" , $arInsertVmMember);
                    $this->email_model->sendEmailtoUserForactivate($venderEmail , $venderUsername , $link);
        
                    $output = array(
                        "msg" => "ส่งข้อมูลการ Activate สำเร็จ",
                        "status" => "Send Activate Data Success",
                        "taxid" => $venderUsername,
                        "codeVerify" => $this->input->post("codeVerify"),
                        "default" => md5(substr($venderUsername , -4 , 4))
                    );
                }else{
                    $output = array(
                        "msg" => "Send Activate Data ไม่สำเร็จ",
                        "status" => "Send Activate Data Not Success"
                    );
                }
            }else{
                $output = array(
                    "msg" => "พบอีเมลซ้ำในระบบ",
                    "status" => "Found Duplicate Email"
                );
            }

        }else{
            $output = array(
                "msg" => "Send Activate Data ไม่สำเร็จ",
                "status" => "Send Activate Data Not Success"
            );
        }
        echo json_encode($output);
    }
    private function checkEmailDuplicate($email)
    {
        if($email != ""){
            $sql = $this->db->query("SELECT
            vm_email
            FROM vender_member WHERE vm_email = '$email'
            ");
            return $sql;
        }
    }

    public function resendActivateEmail()
    {
        $this->load->model("email_model");
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "resendActivateEmail"){
            $taxid = $received_data->taxid;
            $sqlget = $this->db->query("SELECT
            vm_email_temp,
            vm_activatecode,
            vm_expire_linkactivate,
            vm_status
            FROM vender_member WHERE vm_taxid = '$taxid'
            ");



            // Check activate link
            $activatecode = "";
            $linkExpire = "";
            $email = "";
            if($sqlget->num_rows() != 0){
                // Check Activate Status
                if($sqlget->row()->vm_status == "wait activate"){
                    if($sqlget->row()->vm_expire_linkactivate < time()){
                        $activatecode = md5(uniqid(rand(), true));
                        $linkExpire = strtotime('+24 hours');
                        $arupdate = array(
                            "vm_datetime_wait_activate" => date("Y-m-d H:i:s"),
                            "vm_activatecode" => $activatecode,
                            "vm_expire_linkactivate" => $linkExpire
                        );
                        $this->db->where("vm_taxid" , $taxid);
                        $this->db->update("vender_member" , $arupdate);
                    }else{
                        $activatecode = $sqlget->row()->vm_activatecode;
                    }
    
                    $email = $sqlget->row()->vm_email_temp;
                    $this->email_model->sendEmailtoUserForactivate($email , $taxid , $activatecode);
    
                    $output = array(
                        "msg" => "ส่งอีเมลซ้ำสำเร็จ",
                        "status" => "Resend Email Success",
                        "result" => $sqlget->row(),
                    );
                }else if($sqlget->row()->vm_status == "active"){
                    $output = array(
                        "msg" => "ไม่สามารถส่งอีเมลซ้ำได้เนื่องจากท่าน Activate account เรียบร้อยแล้ว",
                        "status" => "Activate Account Already",
                    );
                }

            }else{
                $output = array(
                    "msg" => "ส่งอีเมลซ้ำไม่สำเร็จ",
                    "status" => "Not Found This Account",
                );
            }

        }else{
            $output = array(
                "msg" => "ส่งอีเมลซ้ำไม่สำเร็จ",
                "status" => "Resend Email Not Success",
            );
        }
        echo json_encode($output);
    }

    public function checkActivate()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "checkActivate"){
            $taxid = $received_data->taxid;
            $activatecode = $received_data->activatecode;

            $sql = $this->db->query("SELECT
            vm_activatecode,
            vm_expire_linkactivate,
            vm_email_temp,
            vm_email
            FROM vender_member WHERE vm_taxid = '$taxid' AND vm_status = 'wait activate'
            ");

            if($sql->num_rows() != 0){
                if($sql->row()->vm_expire_linkactivate > time()){
                    $arupdate = array(
                        "vm_status" => "active",
                        "vm_activatecode" => null,
                        "vm_expire_linkactivate" => null,
                        "vm_datetime_activate" => date("Y-m-d H:i:s"),
                        "vm_email" => $sql->row()->vm_email_temp,
                        "vm_email_temp" => null
                    );
                    $this->db->where("vm_taxid",$taxid);
                    $this->db->update("vender_member" , $arupdate);

                    $output = array(
                        "msg" => "Activate Account สำเร็จ",
                        "status" => "Activate Account Success"
                    );
                }else{
                    $output = array(
                        "msg" => "ลิงค์สำหรับ Activate หมดอายุ",
                        "status" => "Activate Link Expire",
                        "taxid" => $taxid
                    );
                }
            }else{
                $output = array(
                    "msg" => "ไม่พบข้อมูลบัญชีรายการรอ Activate",
                    "status" => "Not Found This Account",
                );
            }
        }else{
            $output = array(
                "msg" => "ทำรายการไม่สำเร็จ",
                "status" => "Activate Account Not Success",
            );
        }
        echo json_encode($output);
    }


    public function checkActivateStatus()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "checkActivateStatus"){
            $taxid = $received_data->taxid;
            $sql = $this->db->query("SELECT
            vm_status
            FROM vender_member WHERE vm_taxid = '$taxid'
            ");

            if($sql->num_rows() != 0){
                if($sql->row()->vm_status == "wait activate"){
                    $output = array(
                        "msg" => "พบผู้ใช้งานรอ Activate",
                        "status" => "Wait Activate Data",
                        "taxid" => $taxid
                    );
                }else if($sql->row()->vm_status == "active"){
                    $output = array(
                        "msg" => "ผู้ใช้งาน Activate เรียบร้อยแล้ว",
                        "status" => "Account Activate Already",
                        "taxid" => $taxid
                    );
                }
            }else{
                $output = array(
                    "msg" => "ไม่พบข้อมูลผู้ใช้งาน",
                    "status" => "Not Found Data Account",
                    "code" => md5(substr($taxid , -4 , 4))
                );
            }
        }
        echo json_encode($output);
    }


    public function getdatauserForEditEmailActivate()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getdatauserForEditEmailActivate"){
            $taxid = $received_data->taxid;

            $sql = $this->db->query("SELECT
            vm_email_temp,
            vm_email,
            vm_status
            FROM vender_member WHERE vm_taxid = '$taxid'
            ");

            // check Activate Status
            if($sql->num_rows() != 0){
                if($sql->row()->vm_status == "wait activate"){
                    $output = array(
                        "msg" => "ดึงข้อมูล Email Account สำเร็จ",
                        "status" => "Select Data Success",
                        "result" => $sql->row()
                    );
                }else if($sql->row()->vm_status == "active"){
                    $output = array(
                        "msg" => "ท่านได้ Activate Account เรียบร้อยแล้ว",
                        "status" => "Activate Account Already",
                    );
                }
            }else{
                $output = array(
                    "msg" => "ดึงข้อมูล Email Account ไม่สำเร็จ",
                    "status" => "Select Data Not Success",
                );
            }
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Email Account ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }


    public function saveFotgotpassword()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        $this->load->model("email_model");
        if($received_data->action == "saveFotgotpassword"){
            $taxid = $received_data->taxid;
            $email = $received_data->email;

            $tokencode = md5(uniqid(rand(), true));
            $tokentime = strtotime('+ 15 minutes');

            // Get data for send email
            $sqlgetdata = $this->db->query("SELECT
            vm_taxid,
            vm_email
            FROM vender_member WHERE vm_taxid = '$taxid' AND vm_email = '$email'
            ");

            if($sqlgetdata->num_rows() != 0){
                $arupdate = array(
                    "vm_status" => "reset password",
                    "vm_resetpass_token" => $tokencode,
                    "vm_resetpass_tokentime" => $tokentime
                );
                $this->db->where("vm_taxid" , $taxid);
                $this->db->update("vender_member" , $arupdate);
                $this->email_model->sendEmailtoUserForForgotpassword($email , $taxid , $tokencode);
                $output = array(
                    "msg" => "ข้อมูลผู้ใช้ถูกต้องระบบส่งอีเมลสำหรับตั้งรหัสผ่านใหม่เรียบร้อยแล้ว",
                    "status" => "Email Sending",
                    "timetoken" => date("Y-m-d H:i:s" , $tokentime),
                    "timetoken2" => $tokentime
                );
            }else{
                $output = array(
                    "msg" => "ไม่พบข้อมูลผู้ใช้ในระบบหรืออีเมลไม่ถูกต้อง",
                    "status" => "Not Found Account"
                );
            }

        }else{
            $output = array(
                "msg" => "พบข้อผิดพลาดไม่สามารถดำเนินการต่อได้",
                "status" => "Error"
            );
        }
        echo json_encode($output);
    }

    public function saveResetpassword()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveResetpassword"){
            $taxid = $received_data->taxid;
            $newpassword = md5($received_data->newpassword);
            $token = $received_data->token;

            $sqlget = $this->db->query("SELECT
            vm_resetpass_token,
            vm_resetpass_tokentime,
            vm_email
            FROM vender_member WHERE vm_taxid = '$taxid' AND vm_status = 'reset password' AND vm_resetpass_token = '$token'
            ");

            if($sqlget->num_rows() != 0){
                // check time expire
                if($sqlget->row()->vm_resetpass_tokentime > time()){
                    $arupdate = array(
                        "vm_password" => $newpassword,
                        "vm_status" => "active",
                        "vm_resetpass_token" => null,
                        "vm_resetpass_tokentime" => null
                    );
                    $this->db->where("vm_taxid" , $taxid);
                    $this->db->update("vender_member" , $arupdate);

                    $output = array(
                        "msg" => "กำหนดรหัสผ่านใหม่สำเร็จ",
                        "status" => "Set New Password Success"
                    );
                }else{
                    $tokencode = md5(uniqid(rand(), true));
                    $tokentime = strtotime('+ 30 minutes');
                    $email = $sqlget->row()->vm_email;
                    $this->load->model("email_model");

                    $arupdate = array(
                        "vm_status" => "reset password",
                        "vm_resetpass_token" => $tokencode,
                        "vm_resetpass_tokentime" => $tokentime
                    );
                    $this->db->where("vm_taxid" , $taxid);
                    $this->db->update("vender_member" , $arupdate);

                    $this->email_model->sendEmailtoUserForForgotpassword($email , $taxid , $tokencode);

                    $output = array(
                        "msg" => "ลิ้งหมดอายุระบบได้ทำการส่งอีเมลให้ใหม่เรียบร้อยแล้ว",
                        "status" => "Link Expire Sending Email Again"
                    );
                }
            }else{
                $output = array(
                    "msg" => "ไม่พบข้อมูลการ Reset Password",
                    "status" => "Not Found User Reset Password"
                );
            }
            
        }else{
            $output = array(
                "msg" => "พบข้อผิดพลาดของการขอ Reset Password",
                "status" => "Error"
            );
        }
        echo json_encode($output);
    }

    public function checkResetPasswordStatus()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "checkResetPasswordStatus"){
            $taxid = $received_data->taxid;
            $sql = $this->db->query("SELECT
            vm_status
            FROM vender_member WHERE vm_taxid = '$taxid' AND vm_status = 'reset password'
            ");

            if($sql->num_rows() == 0){
                $output = array(
                    "msg" => "ไม่พบข้อมูลการขอ Reset Password",
                    "status" => "Not Found Reset Password Status"
                );
            }else{
                $output = array(
                    "msg" => "พบข้อมูลการขอ Reset Password",
                    "status" => "Found Reset Password Status"
                );
            }
        }else{
            $output = array(
                "msg" => "พบความผิดพลาดของการตรวจสอบสถานะ",
                "status" => "Error"
            );
        }
        echo json_encode($output);
    }


    public function saveChangeEmailForActivate()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "saveChangeEmailForActivate"){
            $this->load->model("email_model");
            $newEmail = $received_data->newemail;
            $taxid = $received_data->taxid;
            $activatecode = md5(uniqid(rand(), true));
            $linkExpire = strtotime('+24 hours');

            // Check Email Duplicate
            $checkEmail = $this->checkEmailDuplicate($newEmail);
            if($checkEmail->num_rows() == 0){
                $arupdate = array(
                    "vm_email" => null,
                    "vm_email_temp" => $newEmail,
                    "vm_status" => "wait activate",
                    "vm_activatecode" => $activatecode,
                    "vm_expire_linkactivate" => $linkExpire,
                    "vm_datetime_wait_activate" => date("Y-m-d H:i:s")
                );
                $this->db->where("vm_taxid" , $taxid);
                $this->db->update("vender_member" , $arupdate);
    
                $this->email_model->sendEmailtoUserForactivate($newEmail , $taxid , $activatecode);
    
                $output = array(
                    "msg" => "เปลี่ยน Email พร้อมส่ง Link สำหรับ Activate สำเร็จ",
                    "status" => "Change Email Success",
                    "taxid" => $taxid
                );
            }else{
                $output = array(
                    "msg" => "พบอีเมลซ้ำในระบบ",
                    "status" => "Found Duplicate Email"
                );
            }

        }else{
            $output = array(
                "msg" => "เปลี่ยน Email ไม่สำเร็จ",
                "status" => "Change Email Not Success"
            );
        }
        echo json_encode($output);
    }


    public function loadDataBilling()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "loadDataBilling"){
            $taxid = $received_data->taxid;

            $rsClientData = $this->getClientData($taxid);
            $rsVenderAccount = $this->getVenderAccount($taxid);
            

            $output = array(
                "msg" => "ดึงข้อมูลสำเร็จ",
                "status" => "Select Data Success",
                "venderClient" => $rsClientData->result(),
                "venderAccount" => $rsVenderAccount->result(),
                "countBill" => $this->getWaitBilling($taxid)
            );

            
        }else{
            $output = array(
                "msg" => "ดึงข้อมูลไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }


    private function getClientData($taxid)
    {
        if($taxid != ""){
            // Query vender Data
            $sql = $this->db->query("SELECT
            billupload.taxid,
            billupload.invoiceaccount,
            billupload.dataareaid
            FROM
            billupload
            where dataareaid in ('sln' , 'ca') and taxid = '$taxid'
            GROUP BY dataareaid , taxid
            ");
            return $sql;
        }
    }

    private function getVenderAccount($taxid)
    {
        if($taxid != ""){
            $sql = $this->db->query("SELECT
            billupload.taxid,
            billupload.invoiceaccount,
            billupload.payment,
            billupload.dataareaid
            FROM
            billupload
            where dataareaid in ('sln' , 'ca') and taxid = '$taxid'
            GROUP BY dataareaid , taxid , payment
            ");
            return $sql;
        }
    }

    private function getWaitBilling($taxid)
    {
        if($taxid != ""){
            $getvender = $this->getVenderAccount($taxid);
            $countData = [];
            foreach($getvender->result() as $rs){
                $venderAccount = $rs->invoiceaccount;
                $dataareaid = $rs->dataareaid;
                $payment = $rs->payment;

                $countData[] = $this->getCount($rs->invoiceaccount , $rs->dataareaid , $rs->payment)->row();
            }

            return $countData;
        }
    }

    private function getCount($venderAccount , $dataareaid , $payment){
        $dateyearNow = strtotime(date("Y-m"));
        $sql = $this->db->query("SELECT
        invoiceaccount,
        count(invoiceaccount)as countBill,
        dataareaid
        FROM billupload WHERE invoiceaccount = '$venderAccount' and dataareaid = '$dataareaid' and payment = '$payment' and ulstatus = 'Open' AND dataPeriod1 < '$dateyearNow'
        ");
        return $sql;
    }


    public function getVenderInformationByaccount()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getVenderInformationByaccount"){
            $venderaccount = $received_data->venderaccount;
            $dataareaid = $received_data->dataareaid;
            $payment = $received_data->payment;

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

            // Check Company By database
            $querySql = "";
            if($sql->num_rows() == 0){
                $querySql = $sql2;
            }else{
                $querySql = $sql;
            }

            $sqldataNum = $this->db_mssql->query("SELECT
            numofdays
            FROM paymterm WHERE paymtermid = '$payment' and dataareaid = '$dataareaid'
            ");

            // check data on schedule
            $resultSchedule = $this->getDataSchedule();
            $datePayreal = "";
            $dateCalc = "";
            if($resultSchedule->num_rows() != 0){   
                $dateCalc = $resultSchedule->row()->sc_dateCalc;
            }

            $resultGetSetting = $this->getBillSetting();

            $datepay = "";
            $dayfix = $resultGetSetting->row()->set_datefix;

            $daynum = 0;
            $numofday = 0;
            $dateCalcToTime = strtotime(date($dateCalc));

            if($sql->num_rows() != 0 && $sqldataNum->num_rows() != 0){
                $daynum = $dayfix + $sqldataNum->row()->numofdays;
                $datepay = strtotime(date($dateCalc)."+$daynum days");
                $numofday = $sqldataNum->row()->numofdays;
            }

            
            $startDate = $dateCalc; // Start date
            $endDate = date("Y-m-d" , $datepay); // End date

            // Convert dates to timestamps
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);

            // Calculate the number of days between the two timestamps
            $days = round(($endTimestamp - $startTimestamp) / (60 * 60 * 24));


            // find date pay real
            $monthPay = date("m" , $datepay);
            $yearPay = date("Y" , $datepay);
            $queryDatePayReal = $this->getDatePayReal($monthPay , $yearPay);
            if($queryDatePayReal->num_rows() != 0){
                $datePayreal = $queryDatePayReal->row()->sc_datePay;
            }


            $output = array(
                "msg" => "ดึงข้อมูล Vender Information สำเร็จ",
                "status" => "Select Data Success",
                "result" => $querySql->row_array(),
                "datenow" => date("d/m/Y"),
                "dayFix" => $dayfix,
                "numofday" => $numofday,
                "datepay" => date("d/m/Y" , $datepay),
                "datepayReal" => conDateFromDb($datePayreal),
                "datecalc" => date("d/m/Y" , $dateCalcToTime),
                "Number of days: " => $days
            );

            // $output = array(
            //     "test" => $sql->row_array(),
            //     "test2" => $venderaccount ,
            //     "test3" => $dataareaid
            // );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Vender Information ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
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

    private function getDataSchedule()
    {
        $month = date("m");
        $year = date("Y");

        $sql = $this->db->query("SELECT
        sc_year,
        sc_month,
        sc_dateOpen,
        sc_timeOpen,
        sc_dateClose,
        sc_timeClose,
        sc_dateCalc,
        sc_datePay
        FROM schedule WHERE sc_year = '$year' and sc_month = '$month'
        ");

        return $sql;
    }
    private function getDatePayReal($month , $year)
    {
        if($month != "" && $year != ""){
            $sql = $this->db->query("SELECT
            sc_datePay
            FROM schedule WHERE sc_year = '$year' and sc_month = '$month'
            ");

            return $sql;
        }
    }
    

    public function getBillDetail()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getBillDetail"){
            $venderaccount = $received_data->venderaccount;
            $dataareaid = $received_data->dataareaid;
            $payment = $received_data->payment;
            $dateyearNow = strtotime(date("Y-m"));

            $sql = $this->db->query("SELECT
            autoid,
            invoiceaccount,
            ledgervoucher,
            purchid,
            invoiceid,
            salesbalance,
            sumtax,
            invoiceamount,
            payment,
            invoicedate,
            dataareaid,
            dataYear,
            dataMonth
            FROM billupload
            WHERE invoiceaccount = '$venderaccount' AND dataareaid = '$dataareaid' and payment = '$payment' and ulstatus = 'Open' AND dataPeriod1 < '$dateyearNow'
            ORDER BY autoid DESC
            ");

            if($sql->num_rows() != 0){
                $output = array(
                    "msg" => "ดึงข้อมูล Bill Detail สำเร็จ",
                    "status" => "Select Data Success",
                    "result" => $sql->result(),
                    "period" => $sql->row()->dataYear.'-'.$sql->row()->dataMonth
                );
            }else{
                $output = array(
                    "msg" => "ไม่พบข้อมูลรายการรอวางบิล",
                    "status" => "Select Data Success",
                    "result" => $sql->result(),
                );
            }

        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Bill Detail ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }


    public function saveSelectBill()
    {
        if($this->input->post("cb") != ""){

            $autoid = $this->input->post("cb");
            $getFormno = getFormNo();


            // Insert data to bill trans
            foreach($autoid as $key => $value){
                $resultRow = $this->queryGetDataByautoid($value);
                $arInsertToTransection = array(
                    "tr_billupload_autoid" => $value,
                    "tr_formno" => $getFormno,
                    "tr_taxid" => $this->input->post("se-taxid"),
                    "tr_venderaccount" => $this->input->post("se-venderaccount"),
                    "tr_dataareaid" => $this->input->post("se-dataareaid"),
                    "tr_voucher" => $resultRow->row()->ledgervoucher,
                    "tr_po" => $resultRow->row()->purchid,
                    "tr_invoice" => $resultRow->row()->invoiceid,
                    "tr_invoicedate" => $resultRow->row()->invoicedate,
                    "tr_beforetax" => $resultRow->row()->salesbalance,
                    "tr_sumtax" => $resultRow->row()->sumtax,
                    "tr_includetax" => $resultRow->row()->invoiceamount,
                    "tr_payment" => $resultRow->row()->payment,
                    "tr_numofday" => $this->input->post("se-numofday"),
                    "tr_dayfix" => $this->input->post("se-dayfix"),
                    "tr_dateofbilling" => date("Y-m-d"),
                    "tr_dateofpay" => conDateStringToDate($this->input->post("se-dateofpay")),
                    "tr_dateofpayreal" => conDateStringToDate($this->input->post("se-datepayreal")),
                    "tr_datetime" => date("Y-m-d H:i:s"),
                    "tr_status" => "Checking",
                    "tr_period" => $this->input->post("se-period"),
                    "tr_periodbilling" => date("Y-m")
                );
                $this->db->insert("bill_trans" , $arInsertToTransection);
            }
             // Insert data to bill trans



            //  Insert data to bill main
            $arInsertTobillmain = array(
                "ma_formno" => $getFormno,
                "ma_taxid" => $this->input->post("se-taxid"),
                "ma_venderaccount" => $this->input->post("se-venderaccount"),
                "ma_dataareaid" => $this->input->post("se-dataareaid"),
                "ma_payment" => $this->input->post("se-payment"),
                "ma_numofday" => $this->input->post("se-numofday"),
                "ma_dayfix" => $this->input->post("se-dayfix"),
                "ma_dateofbilling" => date("Y-m-d"),
                "ma_dateofpay" => conDateStringToDate($this->input->post("se-dateofpay")),
                "ma_dateofpayreal" => conDateStringToDate($this->input->post("se-datepayreal")),
                "ma_datetime" => date("Y-m-d H:i:s"),
                "ma_status" => "Checking",
                "ma_dateofcalc" => conDateStringToDate($this->input->post("se-dateofcalc")),
                "ma_period" => $this->input->post("se-period"),
                "ma_periodbilling" => date("Y-m")
            );
            $this->db->insert("bill_main" , $arInsertTobillmain);

            // Send Email Zone
            $this->load->model("email_model");
            $this->email_model->sendEmailStep1_toAPAndVender($getFormno);


            $output = array(
                "msg" => "ทำรายการวางบิลสำเร็จ",
                "status" => "Insert Data Success",
                "formno" => $getFormno,
            );
        }else{
            $output = array(
                "msg" => "ทำรายการวางบิลไม่สำเร็จ",
                "status" => "Insert Data Not Success"
            );
        }

        echo json_encode($output);
    }
    private function queryGetDataByautoid($autoid)
    {
        if($autoid != ""){
            // Update on bill upload table
            $arupdateStatus = array(
                "ulstatus" =>  "In Progress"
            );
            $this->db->where("autoid" , $autoid);
            $this->db->update("billupload" , $arupdateStatus);


            $sql = $this->db->query("SELECT
            invoiceaccount,
            ledgervoucher,
            purchid,
            invoiceid,
            salesbalance,
            sumtax,
            invoiceamount,
            payment,
            invoicedate,
            dataareaid,
            dataMonth,
            dataYear,
            ulstatus
            FROM billupload WHERE autoid = '$autoid'
            ");
            return $sql;
        }

    }


    public function getVenderInformationByaccountParam($venderaccount , $dataareaid)
    {
        if($venderaccount != "" && $dataareaid != ""){

            $sql = $this->db_mssql->query("SELECT
            a.bpc_whtid,
            a.accountnum,
            a.name,
            a.slc_fname,
            a.slc_lname,
            a.address,
            a.phone,
            a.paymtermid,
            a.dataareaid
            FROM vendtable a
            WHERE a.accountnum = '$venderaccount' and a.dataareaid = '$dataareaid'
            ");
            return $sql;
        }

    }


    public function getDataBilled($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            tr_formno,
            tr_billupload_autoid,
            tr_po,
            tr_voucher,
            tr_invoicedate,
            tr_invoice,
            tr_includetax,
            tr_numofday,
            tr_dayfix,
            tr_dateofbilling,
            tr_dateofpay,
            tr_dateofpayreal
            FROM bill_trans
            WHERE tr_formno = '$formno' AND tr_status != 'User Cancel'
            ORDER BY tr_autoid ASC
            ");

            return $sql;
        }
    }


    public function loadBilledList($taxid , $startDate , $endDate , $company , $status , $invoice , $periodbilling)
    {
        // DB table to use
        $table = 'billedlist_view';

        // Table's primary key
        $primaryKey = 'ma_autoid';

        $columns = array(
            array('db' => 'ma_formno', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    $dataBillMain = getdataFromBillMain($d);
                    $dataBillFiles = getdataFromBillFiles($d);

                    if($_SERVER['HTTP_HOST'] == "localhost"){
                        $url = "ValidateBilled/";
                    }else{
                        $url = "/intsys/ebilling/ValidateBilled/";
                    }

                    $iconFiles = '';
                    $iconmemo = '';
                    if($dataBillMain->num_rows() != 0){
                        if($dataBillMain->row()->ma_memo_vender != ""){
                            $iconmemo = '<i class="dw dw-chat-12 ml-2 memovender" data-toggle="modal" data-target="#memoVender_modal" data_memovender="'.$dataBillMain->row()->ma_memo_vender.'"></i>';
                        }
                    }

                    if($dataBillFiles->num_rows() != 0){
                        $iconFiles = '<i class="dw dw-file2 ml-2 iconFiles"></i>';
                    }
                    $html = '
                    <a href="'.$url.$d.'"><b>'.$d.'</b></a> '.$iconmemo.$iconFiles.'
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
                    return conAreaidToFullname($d);
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
            $sql_searchBydate = "AND ma_datetime LIKE '%%' ";
        }else if($startDate == "0" && $endDate != "0"){
            $sql_searchBydate = "AND ma_datetime BETWEEN '$endDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate != "0"){
            $sql_searchBydate = "AND ma_datetime BETWEEN '$startDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate == "0"){
            $sql_searchBydate = "AND ma_datetime BETWEEN '$startDate 00:00:01' AND '$startDate 23:59:59' ";
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
            $searchInvoice = searchByInvoice($invoice , $taxid);
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

        $query_periodbilling = "";
        if($periodbilling == "0"){
            $query_periodbilling = "";
        }else{
            $query_periodbilling = "AND ma_periodbilling = '$periodbilling'";
        }

        $whereByTaxid = "ma_taxid = '$taxid'";
        

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, "$whereByTaxid $sql_searchBydate $query_company $query_status $query_invoice $query_periodbilling")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
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
            $dateOfpayreal = '';
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
                $venderaccount = $sqlbillmain->row()->ma_venderaccount;
                $dataareaid = $sqlbillmain->row()->ma_dataareaid;
                $memovender = $sqlbillmain->row()->ma_memo_vender;
                $ma_datecalc = $sqlbillmain->row()->ma_dateofcalc;
                $dateOfpayreal = $sqlbillmain->row()->ma_dateofpayreal;
            }

            $venderInformation = $this->getVenderInformationByaccountParam($venderaccount , $dataareaid);
            $resultDatamain = $this->getDataMain($formno);
            $mainStatus = '';
            if($resultDatamain->num_rows() != 0){
                $mainStatus = $resultDatamain->row()->ma_status;
            }


            // getupload status
            // $uploadData = [];
            // foreach($sqlbilltrans->result() as $rs){
            //     $rsBillUp = $this->checkupdatasta($rs->tr_billupload_autoid)->row();

            //     $dataArray = array(
            //         "upStatus" => $rsBillUp->ulstatus,
            //         "upAutoid" => $rsBillUp->autoid
            //     );

            //     $uploadData[] =  $dataArray;
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
                "dateOfpayreal" => conDateFromDb($dateOfpayreal),
                "datecalc" => conDateFromDb($ma_datecalc),
                "venderinformation" => $venderInformation->row(),
                "mainstatus" => $mainStatus,
                "memovender" => $memovender,
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
            bill_trans.tr_dateofpayreal,
            bill_trans.tr_status,
            bill_trans.tr_datetime,
            bill_trans.tr_datetimemodify,
            billupload.ulstatus
            FROM
            bill_trans
            INNER JOIN billupload ON billupload.autoid = bill_trans.tr_billupload_autoid
            WHERE bill_trans.tr_formno = '$formno' and bill_trans.tr_status in ('Checking','In Progress','Posted' , 'User Cancel')
            ORDER BY bill_trans.tr_autoid ASC
            ");

            return $sql;
        }
    }
    public function queryBillMain($formno)
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
            bill_main.ma_memo_vender,
            bill_main.ma_status,
            bill_main.ma_dateofcalc
            FROM
            bill_main
            WHERE ma_formno = '$formno' and ma_status in ('Checking','In Progress','Posted' , 'User Cancel')
            ORDER BY ma_autoid ASC
            ");

            return $sql;
        }
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
    


    public function saveCancelBill()
    {
        if($this->input->post("cbe") != ""){
            $autoid = $this->input->post("cbe");
            $checkData = [];
            foreach($autoid as $key => $value){
                $arUpdateToTransection = array(
                    "tr_datetimemodify" => date("Y-m-d H:i:s"),
                    "tr_status" => "User Cancel"
                );
                $this->db->where("tr_autoid" , $value);
                $this->db->update("bill_trans" , $arUpdateToTransection);

                $arUpdateBillUpload = array(
                    "ulstatus" => "Open"
                );
                $this->db->where("autoid" ,$this->input->post("cbebill_$value"));
                $this->db->update("billupload" , $arUpdateBillUpload);

            }

            // getData for update status
            $formno = $this->input->post("sed-formno");
            $resultCheckBillStatus = $this->checkBillFormstatus($formno);

            if($resultCheckBillStatus == 0){
                $arUpdateBillStatus = array(
                    "ma_status" => "User Cancel"
                );
                $this->db->where("ma_formno" , $formno);
                $this->db->update("bill_main" , $arUpdateBillStatus);
            }


            $output = array(
                "msg" => "ยกเลิกรายการวางบิลสำเร็จ",
                "status" => "Update Data Success",
            );
        }else{
            $output = array(
                "msg" => "ยกเลิกรายการวางบิลไม่สำเร็จ",
                "status" => "Update Data Not Success",
            );
        }
        echo json_encode($output);
    }

    private function checkBillFormstatus($formno)
    {
        if($formno != ""){
            $sql = $this->db->query("SELECT
            tr_status FROM bill_trans WHERE tr_formno = '$formno'
            ");

            $countStatus = 1;
            $checkCount = 0;
            foreach($sql->result() as $rs){
                if($rs->tr_status == "Checking"){
                    $checkCount = $checkCount + 1;
                }else{
                    $checkCount = $checkCount + 0;
                }
            }

            return $checkCount;
        }
    }


    public function loadBilledReport($taxid , $startDate , $endDate , $company , $status , $periodbilling)
    {
        // DB table to use
        $table = 'billedreport_view';

        // Table's primary key
        $primaryKey = 'tr_autoid';

        $columns = array(
            array('db' => 'tr_formno', 'dt' => 0 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'tr_venderaccount', 'dt' => 1 ,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'tr_dataareaid', 'dt' => 2 ,
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
            array('db' => 'tr_invoice', 'dt' => 3),
            array('db' => 'tr_invoicedate', 'dt' => 4 ,
                'formatter' => function($d , $row){
                    return conDateFromDb($d);
                }
            ),
            array('db' => 'tr_po', 'dt' => 5,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'tr_includetax', 'dt' => 6,
                'formatter' => function($d , $row){
                    return number_format($d , 2);
                }
            ),
            array('db' => 'tr_payment', 'dt' => 7,
                'formatter' => function($d , $row){
                    return $d;
                }
            ),
            array('db' => 'tr_datetime', 'dt' => 8,
                'formatter' => function($d , $row){
                    return conDateTimeFromDb($d);
                }
            ),
            array('db' => 'tr_status', 'dt' => 9,
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
            $sql_searchBydate = "AND tr_invoicedate LIKE '%%' ";
        }else if($startDate == "0" && $endDate != "0"){
            $sql_searchBydate = "AND tr_invoicedate BETWEEN '$endDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate != "0"){
            $sql_searchBydate = "AND tr_invoicedate BETWEEN '$startDate 00:00:01' AND '$endDate 23:59:59' ";
        }else if($startDate != "0" && $endDate == "0"){
            $sql_searchBydate = "AND tr_invoicedate BETWEEN '$startDate 00:00:01' AND '$startDate 23:59:59' ";
        }



        $query_company = "";
        if($company == "0"){
            $query_company = "";
        }else{
            $query_company = "AND tr_dataareaid = '$company' ";
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
                $query_status = "AND tr_status = '$statusText' ";
            }else{
                $query_status = "";
            }
        }


        $query_periodbilling = "";
        if($periodbilling == "0"){
            $query_periodbilling = "";
        }else{
            $query_periodbilling = "AND tr_periodbilling = '$periodbilling'";
        }


        $whereByTaxid = "tr_taxid = '$taxid'";
        

        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null, "$whereByTaxid $sql_searchBydate $query_company $query_status $query_periodbilling")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
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


    public function checkupdatasta($upautoid)
    {

        if($upautoid != ""){
            $sql = $this->db->query("SELECT
            autoid,
            ulstatus
            FROM billupload WHERE autoid = '$upautoid'
            ");
            return $sql;
        }
    }


    public function loadAnnounceData_show($taxid)
    {
        if($taxid != ""){
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
                // array('db' => 'an_type', 'dt' => 2 ,
                //     'formatter' => function($d , $row){
                //         return $d;
                //     }
                // ),
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

            $queryTaxid = "an_taxid = '$taxid' AND an_status = 'เผยแพร่'";


            echo json_encode(
                SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null,"$queryTaxid")
            );

        
            
            //  echo json_encode(
            //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
            //  );
        }
    }


    public function loadAnnounceData_main()
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
            // array('db' => 'an_type', 'dt' => 2 ,
            //     'formatter' => function($d , $row){
            //         return $d;
            //     }
            // ),
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

        $queryAnnounceMain = "an_type = 'ประกาศหลัก' AND an_status = 'เผยแพร่'";


        echo json_encode(
            SSP::complex($_GET, $sql_details, $table, $primaryKey, $columns, null,"$queryAnnounceMain")
        );

    
        
        //  echo json_encode(
        //      SSP::simple($_GET, $sql_details, $table, $primaryKey, $columns)
        //  );
    }


    public function checkDateOpenAndClose()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "checkDateOpenAndClose"){
            $year = date("Y");
            $month = date("m");
            $sql = $this->db->query("SELECT
            sc_dateOpen,
            sc_dateClose,
            sc_timeOpen,
            sc_timeClose
            FROM schedule WHERE sc_year = '$year' AND sc_month = '$month'
            ");

            $dateOpen = $sql->row()->sc_dateOpen;
            $timeOpen = $sql->row()->sc_timeOpen;
            $dateClose = $sql->row()->sc_dateClose;
            $timeClose = $sql->row()->sc_timeClose;

            

            $dateopenuse = "$dateOpen $timeOpen";
            $datecloseuse = "$dateClose $timeClose";

            $output = array(
                "msg" => "ดึงข้อมูลวันเปิดใช้งานโปรแกรมสำเร็จ",
                "status" => "Select Data Success",
                "dateOpen" => $dateopenuse,
                "dateClose" => $datecloseuse,
                "dateNow" => date("Y-m-d H:i:s"),
                "dateOpenSec" => conDatetimeToTimesec($dateopenuse),
                "dateCloseSec" => conDatetimeToTimesec($datecloseuse),
                "dateNowSec" => conDatetimeToTimesec(date("Y-m-d H:i:s")),
            );

        }else{
            $output = array(
                "msg" => "ดึงข้อมูลวันเปิดใช้งานโปรแกรมไม่สำเร็จ",
                "status" => "Select Data Not Success"
            );
        }

        echo json_encode($output);
    }


    public function getDataVenderMember()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getDataVenderMember"){
            $taxid = $received_data->taxid;
            $sql = $this->db->query("SELECT
            vm_email,
            vm_picture_profile,
            vm_picture_path
            FROM vender_member WHERE vm_taxid = '$taxid'
            ");

            $output = array(
                "msg" => "ดึงข้อมูล member สำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->row()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล member ไม่สำเร็จ",
                "status" => "Select Data Not Success",
            );
        }

        echo json_encode($output);
    }


    public function saveEditProfile()
    {
        if($this->input->post("userpro-taxid") != "" && $this->input->post("userpro-email") != ""){

            $email = $this->input->post("userpro-email");
            $taxid = $this->input->post("userpro-taxid");

            // Check Email
            $sqlcheckEmail = $this->db->query("SELECT
            vm_email
            FROM vender_member WHERE vm_taxid = '$taxid'
            ");

            // Check Email Duplicate
            $checkEmail = $this->checkEmailDuplicate($email);
            if($checkEmail->num_rows() == 0){
                if($sqlcheckEmail->num_rows() != 0){
                    if($email != $sqlcheckEmail->row()->vm_email){
                        $this->load->model("email_model");
                        $codeactivate = md5(uniqid(rand(), true));
                        $arupdate = array(
                            "vm_email_temp" => $email,
                            "vm_email" => null,
                            "vm_status" => "wait activate",
                            "vm_activatecode" => $codeactivate,
                            "vm_expire_linkactivate" => strtotime('+24 hours')
                        );
            
                        $this->db->where("vm_taxid" , $taxid);
                        $this->db->update("vender_member" , $arupdate);
    
                        $this->email_model->sendEmailtoUserForactivate($email , $taxid , $codeactivate);
    
                        $fileInput = "userpro-image";
                        uploadUserProfile($fileInput , $taxid);
            
                        $output = array(
                            "msg" => "อัพเดตข้อมูล User Profile สำเร็จ",
                            "status" => "Update Data Success And Activate Data Again",
                            "taxid" => $taxid
                        );
    
                    }else{
                        $fileInput = "userpro-image";
                        uploadUserProfile($fileInput , $taxid);
            
                        $output = array(
                            "msg" => "อัพเดตข้อมูล User Profile สำเร็จ",
                            "status" => "Update Data Success",
                        );
                    }
                }
            }else if($checkEmail->num_rows() != 0){
                if($sqlcheckEmail->row()->vm_email == $email){
                    $fileInput = "userpro-image";
                    uploadUserProfile($fileInput , $taxid);
        
                    $output = array(
                        "msg" => "อัพเดตข้อมูล User Profile สำเร็จ",
                        "status" => "Update Data Success",
                    );
                }else{
                    $output = array(
                        "msg" => "พบอีเมลซ้ำในระบบ",
                        "status" => "Found Duplicate Email"
                    );
                }
            }else{
                $output = array(
                    "msg" => "พบอีเมลซ้ำในระบบ",
                    "status" => "Found Duplicate Email"
                );
            }

        }else{
            $output = array(
                "msg" => "อัพเดตข้อมูล User Profile ไม่สำเร็จ",
                "status" => "Update Data Not Success"
            );
        }
        echo json_encode($output);
    }


    public function getPeriodBilling()
    {
        $received_data = json_decode(file_get_contents("php://input"));
        if($received_data->action == "getPeriodBilling"){
            $taxid = $received_data->taxid;

            $sql = $this->db->query("SELECT
            ma_periodbilling
            FROM bill_main WHERE ma_taxid = '$taxid'
            GROUP BY ma_periodbilling
            ");

            $output = array(
                "msg" => "ดึงข้อมูล Period ของการวางบิลสำเร็จ",
                "status" => "Select Data Success",
                "result" => $sql->result()
            );
        }else{
            $output = array(
                "msg" => "ดึงข้อมูล Period ของการวางบิลไม่สำเร็จ",
                "status" => "Select Data Not Success"
            );
        }
        echo json_encode($output);
    }
    

}
/* End of file ModelName.php */



?>