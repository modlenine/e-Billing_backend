<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Report_model extends CI_Model {
    
    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        // require("PHPExcel/Classes/PHPExcel.php");
        // date_default_timezone_set("Asia/Bangkok");
    }

    


    public function downloadReport($dateStart , $dateEnd)
    {
        if($dateStart != "" && $dateEnd != ""){

            $sql = $this->db->query("SELECT
                acci_main.m_autoid,
                acci_main.m_formno,
                acci_main.m_complaint_formno,
                acci_main.m_acci_datetime,
                acci_main.m_acci_location,
                acci_main.m_type1,
                acci_main.m_type2,
                acci_main.m_type3,
                acci_main.m_acci_name,
                acci_main.m_acci_ecode,
                acci_main.m_acci_dept,
                acci_main.m_acci_deptcode,
                acci_main.m_acci_cardno,
                acci_main.m_acci_carno,
                acci_main.m_acci_res,
                acci_main.m_acci_detail,
                acci_main.m_acci_filestatus,
                acci_main.m_user_inform,
                acci_main.m_ecode_inform,
                acci_main.m_dept_inform,
                acci_main.m_deptcode_inform,
                acci_main.m_datetime_inform,
                acci_main.m_status,
                acci_main.m_user_modify,
                acci_main.m_ecode_modify,
                acci_main.m_dept_modify,
                acci_main.m_deptcode_modify,
                acci_main.m_datetime_modify,
                acci_main.m_mgr_approve,
                acci_main.m_mgr_memo,
                acci_main.m_mgr_user,
                acci_main.m_mgr_dept,
                acci_main.m_mgr_datetime,
                acci_main.m_ohs_approve,
                acci_main.m_ohs_memo,
                acci_main.m_ohs_user,
                acci_main.m_ohs_dept,
                acci_main.m_ohs_datetime
            FROM
            acci_main
            WHERE m_datetime_inform BETWEEN '$dateStart 00:00:01' AND '$dateEnd 23:59:00'
            ORDER BY m_autoid DESC");

            require("PHPExcel/Classes/PHPExcel.php");


            $objPHPExcel = new PHPExcel();

            $objPHPExcel->setActiveSheetIndex(0);
            //กำหนดส่วนหัวเป็น Column แบบ Fix ไม่มีการเปลี่ยนแปลงใดๆ

            $objPHPExcel->getActiveSheet()->setCellValue('a1', 'เลขที่เอกสาร');
            $objPHPExcel->getActiveSheet()->setCellValue('b1', 'เลขที่เอกสาร Complaint');
            $objPHPExcel->getActiveSheet()->setCellValue('c1', 'วันที่เกิดเหตุ');
            $objPHPExcel->getActiveSheet()->setCellValue('d1', 'สถานที่เกิดเหตุ');
            $objPHPExcel->getActiveSheet()->setCellValue('e1', 'ประเภทการเกิด (เกิดจาก)');
            $objPHPExcel->getActiveSheet()->setCellValue('f1', 'หมวดหมู่');
            $objPHPExcel->getActiveSheet()->setCellValue('g1', 'ประเภทของอุบัติเหตุ');
            $objPHPExcel->getActiveSheet()->setCellValue('h1', 'ผู้ประสบเหตุ');
            $objPHPExcel->getActiveSheet()->setCellValue('i1', 'รหัสพนักงาน');
            $objPHPExcel->getActiveSheet()->setCellValue('j1', 'แผนก');
            $objPHPExcel->getActiveSheet()->setCellValue('k1', 'เลขบัตรประจำตัวประชาชน');
            $objPHPExcel->getActiveSheet()->setCellValue('l1', 'ทะเบียนรถ');
            $objPHPExcel->getActiveSheet()->setCellValue('m1', 'ตำแหน่งงาน / หน้าที่ที่รับผิดชอบ');
            $objPHPExcel->getActiveSheet()->setCellValue('n1', 'ผู้แจ้ง');
            $objPHPExcel->getActiveSheet()->setCellValue('o1', 'รหัสพนักงาน');
            $objPHPExcel->getActiveSheet()->setCellValue('p1', 'วันที่แจ้ง');
            $objPHPExcel->getActiveSheet()->setCellValue('q1', 'สถานะ');
            // $runCha = "g";
            // foreach(getRunScreen_exportData($m_code)->result() as $rs1){
            //     $objPHPExcel->getActiveSheet()->setCellValue($runCha.'4', $rs1->d_run_name);
            //     $objPHPExcel->getActiveSheet()->getColumnDimension($runCha)->setAutoSize(true);
            //     ++$runCha;
            // }

            // Loop Time
            $t1 = 2;
            foreach($sql->result() as $rs2){
                
                $objPHPExcel->getActiveSheet()->setCellValue('a'.$t1, $rs2->m_formno);
                $objPHPExcel->getActiveSheet()->setCellValue('b'.$t1, $rs2->m_complaint_formno);
                $objPHPExcel->getActiveSheet()->setCellValue('c'.$t1, $rs2->m_acci_datetime);
                $objPHPExcel->getActiveSheet()->setCellValue('d'.$t1, $rs2->m_acci_location);
                $objPHPExcel->getActiveSheet()->setCellValue('e'.$t1, $rs2->m_type1);
                $objPHPExcel->getActiveSheet()->setCellValue('f'.$t1, $rs2->m_type2);
                $objPHPExcel->getActiveSheet()->setCellValue('g'.$t1, $rs2->m_type3);
                $objPHPExcel->getActiveSheet()->setCellValue('h'.$t1, $rs2->m_acci_name);
                $objPHPExcel->getActiveSheet()->setCellValue('i'.$t1, $rs2->m_acci_ecode);
                $objPHPExcel->getActiveSheet()->setCellValue('j'.$t1, $rs2->m_acci_dept);
                $objPHPExcel->getActiveSheet()->setCellValue('k'.$t1, $rs2->m_acci_cardno);
                $objPHPExcel->getActiveSheet()->setCellValue('l'.$t1, $rs2->m_acci_carno);
                $objPHPExcel->getActiveSheet()->setCellValue('m'.$t1, $rs2->m_acci_res);
                $objPHPExcel->getActiveSheet()->setCellValue('n'.$t1, $rs2->m_user_inform);
                $objPHPExcel->getActiveSheet()->setCellValue('o'.$t1, $rs2->m_ecode_inform);
                $objPHPExcel->getActiveSheet()->setCellValue('p'.$t1, $rs2->m_datetime_inform);
                $objPHPExcel->getActiveSheet()->setCellValue('q'.$t1, $rs2->m_status);

                $t1++;
            }
            // Loop Time

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Accident Online Report.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            echo $objWriter->save('php://output');


        }


    }

    public function testcode()
    {
        echo "test test";
    }
    
    

}

/* End of file ModelName.php */


?>
