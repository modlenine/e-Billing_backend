<?php
class emailfn{
    public $ci;
    function __construct()
    {
        $this->ci = &get_instance();
        date_default_timezone_set("Asia/Bangkok");
    }
    public function gci()
    {
        return $this->ci;
    }
}



function emailobj()
{
    $obj = new emailfn();
    return $obj->gci();
}



function getEmailUser()
{
    $query = emailobj()->db->query("SELECT * FROM email_information");
    return $query->row();
}



function send_email($subject , $body ,$to = "" , $cc = "")
{

    if (!class_exists('PHPMailer')) {
        // PHPMailer class is not yet defined, so declare it
        require("PHPMailer_5.2.0/class.phpmailer.php");
        require("PHPMailer_5.2.0/class.smtp.php");

    }

        // Now you can safely instantiate the PHPMailer class
        $mail = new PHPMailer();

        // PHPMailer class is already defined, handle the situation accordingly
        // You may choose to throw an exception, log an error, or take an alternative action
        $mail->IsSMTP();
        $mail->CharSet = "utf-8";  // ในส่วนนี้ ถ้าระบบเราใช้ tis-620 หรือ windows-874 สามารถแก้ไขเปลี่ยนได้
        $mail->SMTPDebug = 1;                                      // set mailer to use SMTP
        $mail->Host = "mail.saleecolour.net";  // specify main and backup server
    
        $mail->Port = 587; // พอร์ท
    
        $mail->SMTPAuth = true;     // turn on SMTP authentication
        $mail->Username = getEmailUser()->email_user;  // SMTP username
    
        $mail->Password = getEmailUser()->email_password; // SMTP password
    
        $mail->From = getEmailUser()->email_user;
        $mail->FromName = "โปรแกรมวางบิลออนไลน์ e-Billing System";
    
    
        if($to != ""){
            foreach($to as $email){
                $mail->AddAddress($email);
            }
        }
    
    
        if($cc != ""){
            foreach($cc as $email){
                $mail->AddCC($email);
            }
        }
    
    
        // $mail->AddAddress("chainarong_k@saleecolour.com");
        $mail->AddBCC("chainarong_k@saleecolour.com");
    
        $mail->WordWrap = 50;                                 // set word wrap to 50 characters
        $mail->IsHTML(true);                                  // set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = '
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Sarabun&display=swap");
    
            h3 , p , span , div{
                font-family: Tahoma, sans-serif;
                font-size:14px;
            }
            
            table {
                font-family: Tahoma, sans-serif;
                font-size:14px;
                border-collapse: collapse;
                width: 800px;
            }
            
            .center{
                margin-left: auto;
                margin-right: auto;
            }
            
            td, th {
                border: 1px solid #ccc;
                text-align: left;
                padding: 8px;
            }
            
            tr:nth-child(even) {
                background-color: #F5F5F5;
            }
            
            .bghead{
                text-align:center;
                background-color:#D3D3D3;
            }
        </style>
        '.$body;
        // $mail->send();
        if($_SERVER['HTTP_HOST'] != "localhost"){
            $mail->send();
        }
}


function send_emailToAdminAndVender($subjectAdmin , $bodyAdmin ,$toAdmin = "" , $ccAdmin = "" , $subjectVender , $bodyVender ,$toVender = "" , $ccVender = "" )
{
    require("PHPMailer_5.2.0/class.phpmailer.php");
    require("PHPMailer_5.2.0/class.smtp.php");

    $mail = new PHPMailer();
    $mail2 = new PHPMailer();

    $mail->IsSMTP();
    $mail->CharSet = "utf-8";  // ในส่วนนี้ ถ้าระบบเราใช้ tis-620 หรือ windows-874 สามารถแก้ไขเปลี่ยนได้
    $mail->SMTPDebug = 1;                                      // set mailer to use SMTP
    $mail->Host = "mail.saleecolour.net";  // specify main and backup server

    $mail->Port = 587; // พอร์ท

    $mail->SMTPAuth = true;     // turn on SMTP authentication
    $mail->Username = getEmailUser()->email_user;  // SMTP username

    $mail->Password = getEmailUser()->email_password; // SMTP password

    $mail->From = getEmailUser()->email_user;
    $mail->FromName = "โปรแกรมวางบิลออนไลน์ e-Billing System";


    if($toAdmin != ""){
        foreach($toAdmin as $email){
            $mail->AddAddress($email);
        }
    }


    if($ccAdmin != ""){
        foreach($ccAdmin as $email){
            $mail->AddCC($email);
        }
    }


    // $mail->AddAddress("chainarong_k@saleecolour.com");
    $mail->AddBCC("chainarong_k@saleecolour.com");

    $mail->WordWrap = 50;                                 // set word wrap to 50 characters
    $mail->IsHTML(true);                                  // set email format to HTML
    $mail->Subject = $subjectAdmin;
    $mail->Body = '
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sarabun&display=swap");

        h3{
            font-family: Tahoma, sans-serif;
            font-size:14px;
        }
        
        table {
            font-family: Tahoma, sans-serif;
            font-size:14px;
            border-collapse: collapse;
            width: 800px;
        }
        
        .center{
            margin-left: auto;
            margin-right: auto;
        }
        
        td, th {
            border: 1px solid #ccc;
            text-align: left;
            padding: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #F5F5F5;
        }
        
        .bghead{
            text-align:center;
            background-color:#D3D3D3;
        }
    </style>
    '.$bodyAdmin;
    // $mail->send();
    if($_SERVER['HTTP_HOST'] != "localhost"){
        $mail->send();
    }


    $mail2->IsSMTP();
    $mail2->CharSet = "utf-8";  // ในส่วนนี้ ถ้าระบบเราใช้ tis-620 หรือ windows-874 สามารถแก้ไขเปลี่ยนได้
    $mail2->SMTPDebug = 1;                                      // set mailer to use SMTP
    $mail2->Host = "mail.saleecolour.net";  // specify main and backup server

    $mail2->Port = 587; // พอร์ท

    $mail2->SMTPAuth = true;     // turn on SMTP authentication
    $mail2->Username = getEmailUser()->email_user;  // SMTP username

    $mail2->Password = getEmailUser()->email_password; // SMTP password

    $mail2->From = getEmailUser()->email_user;
    $mail2->FromName = "โปรแกรมวางบิลออนไลน์ e-Billing System";


    if($toVender != ""){
        foreach($toVender as $email){
            $mail2->AddAddress($email);
        }
    }


    if($ccVender != ""){
        foreach($ccVender as $email){
            $mail2->AddBCC($email);
        }
    }


    // $mail->AddAddress("chainarong_k@saleecolour.com");
    $mail2->AddBCC("chainarong_k@saleecolour.com");

    $mail2->WordWrap = 50;                                 // set word wrap to 50 characters
    $mail2->IsHTML(true);                                  // set email format to HTML
    $mail2->Subject = $subjectVender;
    $mail2->Body = '
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Sarabun&display=swap");

        h3{
            font-family: Tahoma, sans-serif;
            font-size:14px;
        }
        
        table {
            font-family: Tahoma, sans-serif;
            font-size:14px;
            border-collapse: collapse;
            width: 800px;
        }
        
        .center{
            margin-left: auto;
            margin-right: auto;
        }
        
        td, th {
            border: 1px solid #ccc;
            text-align: left;
            padding: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #F5F5F5;
        }
        
        .bghead{
            text-align:center;
            background-color:#D3D3D3;
        }
    </style>
    '.$bodyVender;
    // $mail2->send();
    if($_SERVER['HTTP_HOST'] != "localhost"){
        $mail2->send();
    }
}

// Query Get Manager Email
function sendactivateLink($useremail)
{
    emailobj()->db2 = emailobj()->load->database('saleecolour', TRUE);
    if($deptcode == 1007){
        $ccSpeacial = "OR ecode = 'M0040' ";
    }else{
        $ccSpeacial = '';
    }
    $sql = emailobj()->db2->query("SELECT memberemail From member Where DeptCode = '$deptcode' and posi IN (65 , 75) and resigned = 0 and areaid is null $ccSpeacial");
    return $sql;
}

function getEmailAPSection()
{
    $sql = emailobj()->db->query("SELECT
    u_ecode,
    u_email,
    u_dept,
    u_username
    FROM user_permission WHERE u_ap_section = 'yes' AND u_userstatus = 'active'
    ");
    return $sql;
}

function getEmailFinanceSection()
{
    $sql = emailobj()->db->query("SELECT
    u_ecode,
    u_email,
    u_dept,
    u_username
    FROM user_permission WHERE u_finance_section = 'yes' AND u_userstatus = 'active'
    ");
    return $sql;
}

function getEmailAdminSection()
{
    $sql = emailobj()->db->query("SELECT
    u_ecode,
    u_email,
    u_dept,
    u_username
    FROM user_permission WHERE u_admin_section = 'yes' AND u_userstatus = 'active'
    ");
    return $sql;
}

function getEmailAccountSection()
{
    $sql = emailobj()->db->query("SELECT
    u_ecode,
    u_email,
    u_dept,
    u_username
    FROM user_permission WHERE u_account_section = 'yes' AND u_userstatus = 'active'
    ");
    return $sql;
}

function getEmailVender($taxid)
{
    if($taxid != ""){
        $sql = emailobj()->db->query("SELECT
        vm_email,
        vm_email1,
        vm_email2
        FROM vender_member WHERE vm_taxid = '$taxid'
        ");

        return $sql;
    }
}











?>