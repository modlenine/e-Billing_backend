<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบรับวางบิลเลขที่ <?=$formno?></title>
</head>

<body>
    <?php

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {

    private $getformno;
    private $getdateofbill;
    private $getdatereceivebill;
    private $getclientdata;
    private $getstatusbill;
    private $getstatusbilldate;
    private $getCompanyname;
    private $getdateprinter;

    public function setHeaderParams($formno , $dateofbill , $datereceivebill , $clientdata , $statusbill , $vendername , $dateprinter , $statusbilldate) {
        $this->getformno = $formno;
        $this->getdateofbill = $dateofbill;
        $this->getdatereceivebill = $datereceivebill;
        $this->getclientdata = $clientdata;
        $this->getstatusbill = $statusbill;
        $this->getCompanyname = $vendername;
        $this->getdateprinter = $dateprinter;
        $this->getstatusbilldate = $statusbilldate;
    }
    //Page header
    public function Header() {
        // Logo   
        // Set font
        $this->SetFont('thsarabun', 'B', 20);
        $this->SetX(10);
        $this->SetY(10);
        // Title
        $this->Cell(0, 15, 'ใบรับวางบิล', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln();

        $this->SetFont('thsarabun', 'B', 16);
        $this->SetX(10);
        $this->SetY(18);
        // Title
        $this->Cell(0, 15, $this->getclientdata, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln();
        $this->SetFont('thsarabun', 'B', 14);
        $this->SetY(10);
        // Title
        $this->Cell(190, 15, $this->getformno , 0, false, 'R', 0, '', 0, false, 'M', 'M');
        $this->Ln();

        $this->SetFont('thsarabun', 'B', 13);
        $this->SetY(32);
        // Title
        $this->Cell(78, 15, $this->getCompanyname , 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->Ln();

        $this->SetFont('thsarabun', 'B', 13);
        $this->SetY(38);
        // Title
        $this->Cell(46, 15, $this->getdateprinter , 0, false, 'L', 0, '', 0, false, 'M', 'M');
        $this->Ln();

    }

    // Page footer
    public function Footer() {

        $this->SetFont('thsarabun', 'B', 16);
        $this->SetY(-40);
        // Title
        $this->Cell(180, 10, $this->getstatusbill, 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->Ln();

        $this->SetFont('thsarabun', 'B', 13);
        $this->SetY(-34);
        // Title
        $this->Cell(180, 10, $this->getstatusbilldate, 0, false, 'C', 0, '', 0, false, 'T', 'M');
        $this->Ln();

        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

    // create new PDF document
    $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $htmlTextPrint = '';
    $htmlTextPrint2 = '';
    if($dataBillMain->row()->ma_status == "Checking"){
        $htmlTextPrint ='รายการอยู่ระหว่างตรวจสอบเอกสาร';
    }else if($dataBillMain->row()->ma_status == "In Progress"){
        $htmlTextPrint ='อนุมัติรายการแล้ว';
    }else if($dataBillMain->row()->ma_status == "Posted"){
        $htmlTextPrint ='รับเงินโอนเข้าบัญชี / รับเช็ค';
        $htmlTextPrint2 = 'วันที่รับเงิน : '.conDateFromDb($dataBillDetail->row()->tr_dateofpayreal);
    }

    $textclientdata = conDataareaid2($dataBillMain->row()->ma_dataareaid);
    $textstatusbill = $htmlTextPrint;
    $textstatusbilldate = $htmlTextPrint2;
    $textformno = "เอกสารเลขที่ : ".$formno;
    $textdateofbill = "วันที่วางบิล : ".conDateFromDb($dataBillMain->row()->ma_dateofbilling);
    $textdatereceivebill = "วันที่รับวางบิล : ".conDateFromDb($dataBillMain->row()->ma_dateofbilling);

    $textvendername = "ชื่อบริษัท/ร้านค้า : ".$dataVenderInformation->row()->name;
    $textdateprint = "วันที่พิมพ์ : ".date("d/m/Y H:i:s");

    $pdf->setHeaderParams($textformno , $textdateofbill , $textdatereceivebill , $textclientdata , $textstatusbill , $textvendername , $textdateprint , $textstatusbilldate);
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('IT Dept');
    $pdf->SetTitle('ใบวางบิล บริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)');
    $pdf->SetSubject('ใบวางบิล บริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)');
    $pdf->SetKeywords('ใบวางบิล บริษัท สาลี่ คัลเล่อร์ จำกัด (มหาชน)');

    // set default header data
// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
    // $pdf->SetHeaderData('Document Library');
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);


    // set header and footer fonts
    $pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);


    // set margins

    // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetMargins(10, 48, 10, true);

    // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    // $pdf->SetMargins(10, 20, 10, true);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 45);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__) . '/lang/eng.php')) {

        require_once(dirname(__FILE__) . '/lang/eng.php');

        $pdf->setLanguageArray($l);
    }

    // ---------------------------------------------------------

    // set font
    $pdf->SetFont('thsarabun', '', 12);
    // Print a table
    // add a page
    $pdf->AddPage();
    // create some HTML content

   


    $html ='
    <style>
        .textH1{
            font-size:22px;
            font-weight:600;
        }
        .textSub{
            font-size:18px;
        }
        
        .title1{
            font-size:16px;
            width:80px !important;
        }

        .title1 , .title2 , .title3 , .title4 , .title5 , .title6{
            text-align:center;
        }
        

        .detail{
            text-align:center;
        }
        
    </style>

    <div>
        <table cellpadding="2" border="1">
            <tr>
                <th class="title1"><b>ลำดับ</b></th>
                <th class="title2"><b>ใบส่งของ/ใบกำกับภาษี</b></th>
                <th class="title3"><b>วันที่</b></th>
                <th class="title4"><b>PO</b></th>
                <th class="title5"><b>Voucher</b></th>
                <th class="title6"><b>จำนวนเงิน</b></th>
            </tr>
                ';
            $i = 1;
            $sum = [];
            foreach($dataBillDetail->result() as $rs){
                $sum[] = $rs->tr_includetax;
                $html .='
                <tr>
                    <td class="detail">'.$i.'</td>
                    <td class="detail">'.$rs->tr_invoice.'</td>
                    <td class="detail">'.conDateFromDb($rs->tr_invoicedate).'</td>
                    <td class="detail">'.$rs->tr_po.'</td>
                    <td class="detail">'.$rs->tr_voucher.'</td>
                    <td class="detail">'.number_format($rs->tr_includetax , 2).'</td>
                </tr>
                ';
                $i++;
            }

            $conSum = array_sum($sum);
            $html .='
                <tr>
                    <td colspan="6" style="text-align:right;"><b>รวมทั้งหมด : </b>'.number_format($conSum , 2).' <b> บาท</b></td>
                </tr>
        </table>
    </div>
    ';


    
    // output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');

    // reset pointer to the last page
    $pdf->lastPage();

    // Print all HTML colors
    ob_end_clean();

    $filename = "ใบวางบิลเลขที่ $formno.pdf";

    //Close and output PDF document
    $pdf->Output($filename, 'I');

    //============================================================+
    // END OF FILE
    //============================================================+

    ?>
    
</body>
</html>