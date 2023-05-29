<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Exportadmin_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        date_default_timezone_set("Asia/Bangkok");
    }
    

    public function exportReportData($startDate , $endDate , $company , $status , $creditterm , $dateofpayreal , $period)
    {
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
            $conPeriodTotime = strtotime($period);
            $query_period = "AND dataPeriod1 = '$conPeriodTotime'";
        }


        $sql = $this->db->query("SELECT
        billupload.autoid AS autoid,
        billupload.taxid AS taxid,
        concat(`billupload`.`dataYear`,'-',`billupload`.`dataMonth`) AS periodupload,
        concat(year(`bill_trans`.`tr_dateofbilling`),'-',month(`bill_trans`.`tr_dateofbilling`)) AS periodofbilling,
        billupload.invoiceaccount AS invoiceaccount,
        billupload.purchid AS purchid,
        billupload.invoiceid AS invoiceid,
        -- billupload.salesbalance AS salesbalance,
        -- billupload.sumtax AS sumtax,
        billupload.invoiceamount AS invoiceamount,
        billupload.payment AS payment,
        billupload.invoicedate AS invoicedate,
        billupload.dataareaid AS dataareaid,
        billupload.dataMonth AS dataMonth,
        billupload.dataYear AS dataYear,
        billupload.ulstatus AS ulstatus,
        bill_trans.tr_billupload_autoid AS tr_billupload_autoid,
        -- bill_trans.tr_formno AS tr_formno,
        -- bill_trans.tr_taxid AS tr_taxid,
        -- bill_trans.tr_venderaccount AS tr_venderaccount,
        -- bill_trans.tr_dataareaid AS tr_dataareaid,
        -- bill_trans.tr_voucher AS tr_voucher,
        -- bill_trans.tr_po AS tr_po,
        -- bill_trans.tr_invoice AS tr_invoice,
        -- bill_trans.tr_invoicedate AS tr_invoicedate,
        -- bill_trans.tr_beforetax AS tr_beforetax,
        -- bill_trans.tr_sumtax AS tr_sumtax,
        -- bill_trans.tr_includetax AS tr_includetax,
        -- bill_trans.tr_payment AS tr_payment,
        -- bill_trans.tr_numofday AS tr_numofday,
        -- bill_trans.tr_dayfix AS tr_dayfix,
        bill_trans.tr_dateofbilling AS tr_dateofbilling,
        bill_trans.tr_dateofpay AS tr_dateofpay,
        bill_trans.tr_dateofpayreal AS tr_dateofpayreal,
        -- bill_trans.tr_period AS tr_period,
        -- bill_trans.tr_status AS tr_status,
        bill_trans.tr_datetime AS tr_datetime,
        bill_trans.tr_datetimemodify AS tr_datetimemodify,
        bill_trans.tr_autoid AS tr_autoid,
        billupload.datetime_upload AS datetime_upload
        FROM
        (billupload
        LEFT JOIN bill_trans ON (bill_trans.tr_billupload_autoid = billupload.autoid))
        WHERE $sql_searchBydate $query_company $query_status $query_creditterm $query_dateofpayreal $query_period
        ORDER BY dataYear DESC , dataMonth DESC
        ");


        require("PHPExcel/Classes/PHPExcel.php");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //กำหนดส่วนหัวเป็น Column แบบ Fix ไม่มีการเปลี่ยนแปลงใดๆ

        $objPHPExcel->getActiveSheet()->setCellValue('a1', 'Taxid.');
        $objPHPExcel->getActiveSheet()->setCellValue('b1', 'Period upload');
        $objPHPExcel->getActiveSheet()->setCellValue('c1', 'Vender account');
        $objPHPExcel->getActiveSheet()->setCellValue('d1', 'Company');
        $objPHPExcel->getActiveSheet()->setCellValue('e1', 'Invoice');
        $objPHPExcel->getActiveSheet()->setCellValue('f1', 'Invoice date');
        $objPHPExcel->getActiveSheet()->setCellValue('g1', 'Po');
        $objPHPExcel->getActiveSheet()->setCellValue('h1', 'Amount');
        $objPHPExcel->getActiveSheet()->setCellValue('i1', 'Credit term');
        $objPHPExcel->getActiveSheet()->setCellValue('j1', 'Period of billing');
        $objPHPExcel->getActiveSheet()->setCellValue('k1', 'Date pay');
        $objPHPExcel->getActiveSheet()->setCellValue('l1', 'Datetime');
        $objPHPExcel->getActiveSheet()->setCellValue('m1', 'Status');
        // $runCha = "g";
        // foreach(getRunScreen_exportData($m_code)->result() as $rs1){
        //     $objPHPExcel->getActiveSheet()->setCellValue($runCha.'4', $rs1->d_run_name);
        //     $objPHPExcel->getActiveSheet()->getColumnDimension($runCha)->setAutoSize(true);
        //     ++$runCha;
        // }

        // Loop Time
        $t1 = 2;
        foreach($sql->result() as $rs2){

            $fullCompany = '';
            switch($rs2->dataareaid){
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

            $conTax = contaxidToname($rs2->taxid);
            
            $objPHPExcel->getActiveSheet()->setCellValue('a'.$t1, $conTax->row()->name);
            $objPHPExcel->getActiveSheet()->setCellValue('b'.$t1, $rs2->periodupload);
            $objPHPExcel->getActiveSheet()->setCellValue('c'.$t1, $rs2->invoiceaccount);
            $objPHPExcel->getActiveSheet()->setCellValue('d'.$t1, $fullCompany);
            $objPHPExcel->getActiveSheet()->setCellValue('e'.$t1, $rs2->invoiceid);
            $objPHPExcel->getActiveSheet()->setCellValue('f'.$t1, conDateFromDb($rs2->invoicedate));
            $objPHPExcel->getActiveSheet()->setCellValue('g'.$t1, $rs2->purchid);
            $objPHPExcel->getActiveSheet()->setCellValue('h'.$t1, $rs2->invoiceamount);
            $objPHPExcel->getActiveSheet()->setCellValue('i'.$t1, $rs2->payment);
            $objPHPExcel->getActiveSheet()->setCellValue('j'.$t1, $rs2->periodofbilling);
            $objPHPExcel->getActiveSheet()->setCellValue('k'.$t1, conDateFromDb($rs2->tr_dateofpayreal));
            $objPHPExcel->getActiveSheet()->setCellValue('l'.$t1, conDateFromDb($rs2->tr_dateofbilling));
            $objPHPExcel->getActiveSheet()->setCellValue('m'.$t1, $rs2->ulstatus);

            $t1++;
        }
        // Loop Time
        $dateNow = date("Y-m-d H:i:s");
        $contoTime = strtotime($dateNow);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="รายงานการวางบิล-'.$contoTime.'.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        echo $objWriter->save('php://output');


    }
    

}
/* End of file ModelName.php */





?>