<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Email_model extends CI_model{
    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        date_default_timezone_set("Asia/Bangkok");
    }

   
function createQrcode($linkQrcode, $id)
{
   // $obj = new emailfn();
   // $obj->gci()->load->library("Ciqrcode");
   require("phpqrcode/qrlib.php");
   // $this->load->library('phpqrcode/qrlib');

   $SERVERFILEPATH = $_SERVER['DOCUMENT_ROOT'] . '/intsys/ebilling/ebilling_backend/uploads/qrcode/';
   $urlQrcode = $linkQrcode;
   // $filename1 = 'qrcode' . rand(2, 200) . ".png";
   $filename1 = 'qrcode' . $id . ".png";
   $folder = $SERVERFILEPATH;

   $filename = $folder . $filename1;

   QRcode::png(
      $urlQrcode,
      $filename,
      // $outfile = false,
      $level = QR_ECLEVEL_H,
      $size = 4,
      $margin = 2
   );

   // echo "<img src='http://192.190.10.27/crf/upload/qrcode/".$filename1."'>";
   return $filename1;
}



function sendEmailtoUserForactivate($email , $taxid , $link)
{
   $subject = "ยืนยันการเข้าใช้งานโปรแกรม e-Billing System";
   if($_SERVER['HTTP_HOST'] != "localhost"){
      $activateurl = 'https://intranet.saleecolour.com/intsys/ebilling/result_activate/'.$taxid.'/'.$link;
   }else{
      $activateurl = 'http://localhost:8080/result_activate/'.$taxid.'/'.$link;
   }


   $body = '
      <h2>กรุณาทำการยืนยันการเข้าใช้งานโปรแกรม e-Billing System</h2>
      <table>
      <tr>
         <td>
         <span>เรียนผู้ค้า เลขที่ '.$taxid.' </span><br>
         <span>กรุณาคลิกที่ลิงค์ด้านล่างนี้ ภายใน 1 ชม. เพื่อเป็นการยืนยันตัวตนการเข้าใช้งานโปรแกรม e-Billing System ของบริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)</span><br>
         <span>'.$activateurl.'</span>
         </td>
      </tr>
      </table>
      ';

   $to = "";
   $cc = "";

   //  Email Zone
   $to = array($email);

   send_email($subject, $body, $to, $cc);
   //  Email Zone
}

function sendEmailtoUserForForgotpassword($email , $taxid , $tokencode)
{
   $subject = "ลิ้งสำหรับเปลี่ยนรหัสผ่าน e-Billing System";
   if($_SERVER['HTTP_HOST'] != "localhost"){
      $activateurl = 'https://intranet.saleecolour.com/intsys/ebilling/resetpassword/'.$taxid.'/'.$tokencode;
   }else{
      $activateurl = 'http://localhost:8080/resetpassword/'.$taxid.'/'.$tokencode;
   }


   $body = '
      <h2>กรุณากำหนดรหัสผ่านใหม่เพื่อเข้าใช้โปรแกรม e-Billing System</h2>
      <table>
      <tr>
         <td>
         <span>เรียนผู้ค้า เลขที่ '.$taxid.' </span><br>
         <span>กรุณาคลิกที่ลิงค์ด้านล่างนี้ ภายใน 1 ชั่วโมง. เพื่อกำหนดรหัสผ่านใหม่สำหรับการเข้าใช้งานโปรแกรม e-Billing System ของบริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)</span><br>
         <span>'.$activateurl.'</span>
         </td>
      </tr>
      </table>
      ';

   $to = "";
   $cc = "";

   //  Email Zone
   $to = array($email);

   send_email($subject, $body, $to, $cc);
   //  Email Zone
}


public function sendEmailStep1_toAPAndVender($formno)
{
   if($formno != ""){
      $dataForEmail = getDataEmailByFormno($formno)->row();
      $taxid = $dataForEmail->ma_taxid;
      $dataVender = getDataVenderByTaxid($taxid)->row();


      if($_SERVER['HTTP_HOST'] != "localhost"){
         $adminLink = "https://intranet.saleecolour.com/intsys/ebilling/admin/confirmbilled/$formno/$taxid";
      }else{
         $adminLink = "http://localhost:8080/admin/confirmbilled/$formno/$taxid";
      }


      $subjectAdmin = "เอกสารรายการวางบิลเลขที่ ".$formno." สถานะ Checking";

      $bodyAdmin = '
      <h2>e-Billing System</h2>
      <table>
      <tr>
         <td><strong>เลขที่เอกสาร</strong></td>
         <td>' . $formno . '</td>
         <td><strong>วันที่วางบิล</strong></td>
         <td>' . conDateFromDb($dataForEmail->ma_dateofbilling) . '</td>
      </tr>


      <tr>
         <td><strong>บริษัท / บุคคล</strong></td>
         <td>' . $dataVender->name . '</td>
         <td><strong>รหัสผู้ค้า</strong></td>
         <td>' . $dataForEmail->ma_venderaccount . '</td>
      </tr>


      <tr>
         <td><strong>เครดิตเทอม</strong></td>
         <td>' . $dataForEmail->ma_payment . '</td>
         <td><strong>บริษัทที่วางบิล</strong></td>
         <td>' . conAreaidToFullname($dataForEmail->ma_dataareaid) . '</td>
      </tr>

      <tr>
         <td><strong>ตรวจสอบรายการ</strong></td>
         <td colspan="3"><a href="' . $adminLink . '">' . $formno . '</a></td>
      </tr>

      <tr>
         <td><strong>Scan QrCode</strong></td>
         <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($adminLink, $formno) . '"></td>
      </tr>


      </table>
      ';
      $toAdmin = "";
      $ccAdmin = "";

      //  Email Zone
      $optionToAdmin = getEmailAPSection();
      $toAdmin = array();
      foreach ($optionToAdmin->result_array() as $result) {
         $toAdmin[] = $result['u_email'];
      }


      $optionccAdmin = getEmailAccountSection();
      $ccAdmin = array();
      foreach ($optionccAdmin->result_array() as $resultcc) {
         $ccAdmin[] = $resultcc['u_email'];
      }

      //////////////////////
      if($_SERVER['HTTP_HOST'] != "localhost"){
         $venderLink = "https://intranet.saleecolour.com/intsys/ebilling/ValidateBilled/$formno";
      }else{
         $venderLink = "http://localhost:8080/ValidateBilled/$formno";
      }

      $subjectVender = "เอกสารรายการวางบิลเลขที่ ".$formno." สถานะ Checking";

      $bodyVender = '
      <h2 class="">e-Billing System</h2>

      <table class="center">
            <tr>
               <td colspan="4" style="text-align:center;padding:30px;"><strong>บริษัทได้รับข้อมูลการวางบิลของท่านแล้ว รายการอยู่ระหว่างตรวจสอบข้อมูล</strong></td>
            </tr>

            <tr>
               <td><strong>สถานะ</strong></td>
               <td colspan="3">'.$dataForEmail->ma_status.'</td>
            </tr>

            <tr>
               <td><strong>วันที่วางบิล</strong></td>
               <td>'.conDateTimeFromDb($dataForEmail->ma_datetime).'</td>
            </tr>

            <tr>
               <td><strong>รายละเอียดเพิ่มเติม</strong></td>
               <td colspan="3"><a href="'.$venderLink.'">'.$formno.'</a></td>
            </tr>

      </table>
      ';
      $toVender = "";
      $ccVender = "";

      //  Email Zone
      $optionToVender = getEmailVender($taxid);
      $toVender = array();
      foreach ($optionToVender->result_array() as $result) {
         $toVender[] = $result['vm_email'];
      }


      $optionccVender = getEmailAccountSection();
      $ccVender = array();
      foreach ($optionccVender->result_array() as $resultcc) {
         $ccVender[] = $resultcc['u_email'];
      }

      send_emailToAdminAndVender($subjectAdmin , $bodyAdmin ,$toAdmin , $ccAdmin , $subjectVender , $bodyVender ,$toVender , $ccVender);

   }
}

public function sendEmailStep2_toFinanceAndVender($formno)
{
   if($formno != ""){
      $dataForEmail = getDataEmailByFormno($formno)->row();
      $taxid = $dataForEmail->ma_taxid;
      $dataVender = getDataVenderByTaxid($taxid)->row();


      if($_SERVER['HTTP_HOST'] != "localhost"){
         $adminLink = "https://intranet.saleecolour.com/intsys/ebilling/admin/confirmbilled/$formno/$taxid";
      }else{
         $adminLink = "http://localhost:8080/admin/confirmbilled/$formno/$taxid";
      }


      $subjectAdmin = "เอกสารรายการวางบิลเลขที่ ".$formno." สถานะ In Progress";

      $bodyAdmin = '
      <h2>e-Billing System</h2>
      <table>
      <tr>
         <td><strong>เลขที่เอกสาร</strong></td>
         <td>' . $formno . '</td>
         <td><strong>วันที่วางบิล</strong></td>
         <td>' . conDateFromDb($dataForEmail->ma_dateofbilling) . '</td>
      </tr>


      <tr>
         <td><strong>บริษัท / บุคคล</strong></td>
         <td>' . $dataVender->name . '</td>
         <td><strong>รหัสผู้ค้า</strong></td>
         <td>' . $dataForEmail->ma_venderaccount . '</td>
      </tr>

      <tr>
         <td><strong>เครดิตเทอม</strong></td>
         <td>' . $dataForEmail->ma_payment . '</td>
         <td><strong>บริษัทที่วางบิล</strong></td>
         <td>' . conAreaidToFullname($dataForEmail->ma_dataareaid) . '</td>
      </tr>

      <tr>
         <td colspan="4">
            <h4>ผลการตรวจสอบเอกสาร</h4>
         </td>
      </tr>

      <tr>
         <td><strong>ผู้อนุมัติ</strong></td>
         <td>' . $dataForEmail->ma_ap_name . '</td>
         <td><strong>รหัสพนักงาน</strong></td>
         <td>' . $dataForEmail->ma_ap_ecode . '</td>
      </tr>

      <tr>
         <td><strong>วันที่อนุมัติ</strong></td>
         <td colspan="3">'.conDateTimeFromDb($dataForEmail->ma_ap_datetime).'</td>
      </tr>

      <tr>
         <td><strong>ตรวจสอบรายการ</strong></td>
         <td colspan="3"><a href="' . $adminLink . '">' . $formno . '</a></td>
      </tr>

      <tr>
         <td><strong>Scan QrCode</strong></td>
         <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($adminLink, $formno) . '"></td>
      </tr>


      </table>
      ';
      $toAdmin = "";
      $ccAdmin = "";

      //  Email Zone
      $optionToAdmin = getEmailFinanceSection();
      $toAdmin = array();
      foreach ($optionToAdmin->result_array() as $result) {
         $toAdmin[] = $result['u_email'];
      }


      $optionccAdmin = getEmailAccountSection();
      $ccAdmin = array();
      foreach ($optionccAdmin->result_array() as $resultcc) {
         $ccAdmin[] = $resultcc['u_email'];
      }

      //////////////////////
      if($_SERVER['HTTP_HOST'] != "localhost"){
         $venderLink = "https://intranet.saleecolour.com/intsys/ebilling/ValidateBilled/$formno";
      }else{
         $venderLink = "http://localhost:8080/ValidateBilled/$formno";
      }

      $subjectVender = "เอกสารรายการวางบิลเลขที่ ".$formno." สถานะ In Progress";

      $bodyVender = '
      <h2 class="">e-Billing System</h2>

      <table class="center">
            <tr>
               <td colspan="4" style="text-align:center;padding:30px;"><strong>บริษัทได้อนุมัติรายการวางบิลของท่านแล้ว</strong></td>
            </tr>

            <tr>
               <td><strong>สถานะ</strong></td>
               <td colspan="3">'.$dataForEmail->ma_status.'</td>
            </tr>

            <tr>
               <td><strong>วันที่วางบิล</strong></td>
               <td>'.conDateTimeFromDb($dataForEmail->ma_datetime).'</td>
            </tr>

            <tr>
               <td><strong>วันที่อนุมัติรายการ</strong></td>
               <td colspan="3">'.conDateTimeFromDb($dataForEmail->ma_ap_datetime).'</td>
            </tr>

            <tr>
               <td><strong>รายละเอียดเพิ่มเติม</strong></td>
               <td colspan="3"><a href="'.$venderLink.'">'.$formno.'</a></td>
            </tr>

      </table>
      ';
      $toVender = "";
      $ccVender = "";

      //  Email Zone
      $optionToVender = getEmailVender($taxid);
      $toVender = array();
      foreach ($optionToVender->result_array() as $result) {
         $toVender[] = $result['vm_email'];
      }


      $optionccVender = getEmailAccountSection();
      $ccVender = array();
      foreach ($optionccVender->result_array() as $resultcc) {
         $ccVender[] = $resultcc['u_email'];
      }

      send_emailToAdminAndVender($subjectAdmin , $bodyAdmin ,$toAdmin , $ccAdmin , $subjectVender , $bodyVender ,$toVender , $ccVender);

   }
}


public function sendEmailStep3_toAccountAndVender($formno)
{
   if($formno != ""){
      $dataForEmail = getDataEmailByFormno($formno)->row();
      $taxid = $dataForEmail->ma_taxid;
      $dataVender = getDataVenderByTaxid($taxid)->row();


      if($_SERVER['HTTP_HOST'] != "localhost"){
         $adminLink = "https://intranet.saleecolour.com/intsys/ebilling/admin/confirmbilled/$formno/$taxid";
      }else{
         $adminLink = "http://localhost:8080/admin/confirmbilled/$formno/$taxid";
      }


      $subjectAdmin = "เอกสารรายการวางบิลเลขที่ ".$formno." สถานะ Posted";

      $bodyAdmin = '
      <h2>e-Billing System</h2>
      <table>
      <tr>
         <td><strong>เลขที่เอกสาร</strong></td>
         <td>' . $formno . '</td>
         <td><strong>วันที่วางบิล</strong></td>
         <td>' . conDateFromDb($dataForEmail->ma_dateofbilling) . '</td>
      </tr>


      <tr>
         <td><strong>บริษัท / บุคคล</strong></td>
         <td>' . $dataVender->name . '</td>
         <td><strong>รหัสผู้ค้า</strong></td>
         <td>' . $dataForEmail->ma_venderaccount . '</td>
      </tr>

      <tr>
         <td><strong>เครดิตเทอม</strong></td>
         <td>' . $dataForEmail->ma_payment . '</td>
         <td><strong>บริษัทที่วางบิล</strong></td>
         <td>' . conAreaidToFullname($dataForEmail->ma_dataareaid) . '</td>
      </tr>

      <tr>
         <td colspan="4">
            <h4>ผลการตรวจสอบเอกสาร</h4>
         </td>
      </tr>

      <tr>
         <td><strong>ผู้อนุมัติ</strong></td>
         <td>' . $dataForEmail->ma_ap_name . '</td>
         <td><strong>รหัสพนักงาน</strong></td>
         <td>' . $dataForEmail->ma_ap_ecode . '</td>
      </tr>

      <tr>
         <td><strong>วันที่อนุมัติ</strong></td>
         <td colspan="3">'.conDateTimeFromDb($dataForEmail->ma_ap_datetime).'</td>
      </tr>

      <tr>
         <td colspan="4">
            <h4>ผลการบันทึกข้อมูลทำจ่าย</h4>
         </td>
      </tr>

      <tr>
         <td><strong>หมายเหตุส่วนของ Vender</strong></td>
         <td>' . $dataForEmail->ma_memo_vender . '</td>
         <td><strong>หมายเหตุส่วนของ Admin</strong></td>
         <td>' . $dataForEmail->ma_memo_admin . '</td>
      </tr>

      <tr>
         <td><strong>ผู้ดำเนินการ</strong></td>
         <td>' . $dataForEmail->ma_fn_name . '</td>
         <td><strong>รหัสพนักงาน</strong></td>
         <td>' . $dataForEmail->ma_fn_ecode . '</td>
      </tr>

      <tr>
         <td><strong>วันที่</strong></td>
         <td>' . conDateTimeFromDb($dataForEmail->ma_fn_datetime) . '</td>
      </tr>

      <tr>
         <td><strong>ตรวจสอบรายการ</strong></td>
         <td colspan="3"><a href="' . $adminLink . '">' . $formno . '</a></td>
      </tr>

      <tr>
         <td><strong>Scan QrCode</strong></td>
         <td colspan="3"><img src="' . base_url('uploads/qrcode/') . $this->createQrcode($adminLink, $formno) . '"></td>
      </tr>


      </table>
      ';
      $toAdmin = "";
      $ccAdmin = "";

      //  Email Zone
      $optionToAdmin = getEmailFinanceSection();
      $toAdmin = array();
      foreach ($optionToAdmin->result_array() as $result) {
         $toAdmin[] = $result['u_email'];
      }


      $optionccAdmin = getEmailAccountSection();
      $ccAdmin = array();
      foreach ($optionccAdmin->result_array() as $resultcc) {
         $ccAdmin[] = $resultcc['u_email'];
      }

      //////////////////////
      if($_SERVER['HTTP_HOST'] != "localhost"){
         $venderLink = "https://intranet.saleecolour.com/intsys/ebilling/ValidateBilled/$formno";
      }else{
         $venderLink = "http://localhost:8080/ValidateBilled/$formno";
      }

      $subjectVender = "เอกสารรายการวางบิลเลขที่ ".$formno." สถานะ Posted";

      $bodyVender = '
      <h2 class="">e-Billing System</h2>

      <table class="center">
            <tr>
               <td colspan="4" style="text-align:center;padding:30px;"><strong>บริษัทได้กำหนดวันชำระเงินให้ท่านเรียบร้อยแล้ว</strong></td>
            </tr>

            <tr>
               <td><strong>สถานะ</strong></td>
               <td colspan="3">'.$dataForEmail->ma_status.'</td>
            </tr>

            <tr>
               <td><strong>วันที่วางบิล</strong></td>
               <td>'.conDateTimeFromDb($dataForEmail->ma_datetime).'</td>
            </tr>

            <tr>
               <td><strong>วันที่อนุมัติรายการ</strong></td>
               <td colspan="3">'.conDateTimeFromDb($dataForEmail->ma_ap_datetime).'</td>
            </tr>

            <tr>
               <td><strong>วันที่กำหนดวันชำระเงิน</strong></td>
               <td colspan="3">'.conDateTimeFromDb($dataForEmail->ma_fn_datetime).'</td>
            </tr>

            <tr>
               <td><strong>หมายเหตุ</strong></td>
               <td colspan="3">'.$dataForEmail->ma_memo_vender.'</td>
            </tr>

            <tr>
               <td><strong>วันที่ได้รับเงิน</strong></td>
               <td colspan="3">'.conDateFromDb($dataForEmail->ma_dateofpayreal).'</td>
            </tr>

            <tr>
               <td><strong>รายละเอียดเพิ่มเติม</strong></td>
               <td colspan="3"><a href="'.$venderLink.'">'.$formno.'</a></td>
            </tr>

      </table>
      ';
      $toVender = "";
      $ccVender = "";

      //  Email Zone
      $optionToVender = getEmailVender($taxid);
      $toVender = array();
      foreach ($optionToVender->result_array() as $result) {
         $toVender[] = $result['vm_email'];
      }


      $optionccVender = getEmailAccountSection();
      $ccVender = array();
      foreach ($optionccVender->result_array() as $resultcc) {
         $ccVender[] = $resultcc['u_email'];
      }

      send_emailToAdminAndVender($subjectAdmin , $bodyAdmin ,$toAdmin , $ccAdmin , $subjectVender , $bodyVender ,$toVender , $ccVender);

   }
}


function sendEmailtoVenderNotifyPay($taxid , $mainformnoPaying , $email)
{

   $taxname = contaxidToname($taxid);
   $subject = "แจ้งเตือน จากระบบ e-Billing System ของบริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)";

   $body = '
      <div>
         <p>เรียน '.$taxname->row()->name.'</p>
         <p style="margin-left:15px;">
            <b>รายการที่จะครบกำหนดชำระให้ท่านในเดือนนี้</b>
            <br>
            ';
            foreach($mainformnoPaying as $mainformnoPayings){

               if($_SERVER['HTTP_HOST'] != "localhost"){
                  $notifyLink = "https://intranet.saleecolour.com/intsys/ebilling/ValidateBilled/$mainformnoPayings";
               }else{
                  $notifyLink = "http://localhost:8080/ValidateBilled/$mainformnoPayings";
               }

               $body .='
               <a href="'.$notifyLink.'"><span>'.$mainformnoPayings.'</span></a><br>
               ';
            }
   $body .='
         </p>
         <p>
            <span>กรณีที่ถูกหักภาษี ณ ที่จ่าย ท่านสามารถดาวน์โหลดได้หลังวันรับชำระเงินเป็นต้นไป</span>
         </p>
         <p style="color:red;">**อีเมลฉบับนี้เป็นระบบอัตโนมัติ โปรดอย่าตอบกลับ**</p>
         <p>*หากมีข้อสงสัยหรือพบปัญหาโปรดติดต่อหน่วยงานการเงิน โทร.023232601-8 ต่อ 3041 <br>
         อีเมล : finance_sup@saleecolour.com
         </p>
      </div>
      ';

   $to = "";
   $cc = "";

   //  Email Zone
   $to = array($email);

   send_email($subject, $body, $to, $cc);
   //  Email Zone
   $arinsertEmail = array(
      "e_taxid" => $taxid,
      "e_mail" => $email,
      "e_formno" => json_encode($mainformnoPaying),
      "e_status" => "Send Success",
      "e_datetime" => date("Y-m-d H:i:s")
   );
   $this->db->insert("email_notify_log" , $arinsertEmail);
}





















    
}