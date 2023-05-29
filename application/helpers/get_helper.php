<?php
class getfn{
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


function getfn()
{
    $obj = new getfn();
    return $obj->gci();
}



// Template Set เป็นการกำหนดค่าให้กับ Template
// Template Set เป็นการกำหนดค่าให้กับ Template
// Template Set เป็นการกำหนดค่าให้กับ Template
// Template Set เป็นการกำหนดค่าให้กับ Template
function getHead()
{
    return getfn()->load->view("templates/head");
}

function getFooter()
{
    return getfn()->load->view("templates/footer");
}

function getContent($page , $data)
{
    return getfn()->parser->parse($page , $data);
}

function getModal()
{
    return getfn()->load->view("templates/modal");
}
// Template Set เป็นการกำหนดค่าให้กับ Template
// Template Set เป็นการกำหนดค่าให้กับ Template
// Template Set เป็นการกำหนดค่าให้กับ Template
// Template Set เป็นการกำหนดค่าให้กับ Template



function getFormNo()
{
    // check formno ซ้ำในระบบ
    $checkRowdata = getfn()->db->query("SELECT
    tr_formno FROM bill_trans ORDER BY tr_autoid DESC LIMIT 1 
    ");
    $result = $checkRowdata->num_rows();

    $cutYear = substr(date("Y"), 2, 2);
    $getMonth = substr(date("m"), 0, 2);
    $formno = "";
    if ($result == 0) {
        $formno = "BILL" . $cutYear.$getMonth. "000001";
    } else {

        $getFormno = $checkRowdata->row()->tr_formno;
        $cutGetYear = substr($getFormno, 4, 2); //KB2003001
        $cutNo = substr($getFormno, 8, 6); //อันนี้ตัดเอามาแค่ตัวเลขจาก CRF2003001 ตัดเหลือ 001
        $cutNo++;

        if ($cutNo < 10) {
            $cutNo = "00000" . $cutNo;
        } else if ($cutNo < 100) {
            $cutNo = "0000" . $cutNo;
        }else if($cutNo < 1000){
            $cutNo = "000" . $cutNo;
        }else if($cutNo < 10000){
            $cutNo = "00" . $cutNo;
        }else if($cutNo < 100000){
            $cutNo = "0" . $cutNo;
        }

        if ($cutGetYear != $cutYear) {
            $formno = "BILL" . $cutYear.$getMonth."000001";
        } else {
            $formno = "BILL" . $cutGetYear.$getMonth. $cutNo;
        }
    }

    return $formno;
}


function getRuningCode($groupcode)
{
    $date = date_create();
    $dateTimeStamp = date_timestamp_get($date);
    return $groupcode.$dateTimeStamp;
}


function getDb()
{
    if($_SERVER['HTTP_HOST'] == "localhost"){
        $dbHost = "192.168.20.22";
    }else{
        $dbHost = "localhost";
    }

    $sql = getfn()->db->query("SELECT
    db.db_autoid,
    db.db_username,
    db.db_password,
    db.db_databasename,
    db.db_host,
    db.db_active
    FROM
    db WHERE db_host = '$dbHost' ");

    return $sql->row();
}



// Query Zone
function getUser()
{
    getfn()->load->model("login_model");
    return getfn()->login_model->getUser();
}

function getdataByAutoid($autoid)
{
    if($autoid != ""){
        $sql = getfn()->db->query("SELECT
        autoid,
        taxid,
        invoiceaccount,
        purchid,
        invoiceid,
        invoicedate,
        invoiceamount,
        ulstatus
        FROM billupload
        WHERE autoid = '$autoid'
        ");
        return $sql;
    }
}

function getdataByTaxid($taxid)
{
    if($taxid != ""){
        $sql = getfn()->db->query("SELECT
        autoid,
        taxid,
        invoiceaccount,
        purchid,
        invoiceid,
        invoicedate,
        invoiceamount
        FROM billupload
        WHERE taxid = '$taxid'
        ");
        return $sql;
    }
}

function searchByInvoice($invoice , $taxid)
{
    if($invoice != ""){
        $sql = getfn()->db->query("SELECT
        tr_formno,
        tr_taxid
        FROM bill_trans WHERE tr_invoice = '$invoice' AND tr_taxid = '$taxid'
        ");
        return $sql;
    }
}

function searchByInvoice2($invoice)
{
    if($invoice != ""){
        $sql = getfn()->db->query("SELECT
        tr_formno,
        tr_taxid
        FROM bill_trans WHERE tr_invoice = '$invoice'
        ");
        return $sql;
    }
}

function getStatus($statusId)
{
    if($statusId != ""){
        $sql = getfn()->db->query("SELECT
        s_statusname,
        s_statusname2,
        s_autoid
        FROM status_master WHERE s_autoid = '$statusId'
        ");

        return $sql;
    }
}

function getTaxidByFormno($formno)
{
    if($formno != ""){
        $sql = getfn()->db->query("SELECT
        ma_taxid
        FROM bill_main WHERE ma_formno = '$formno'
        ");
        return $sql;
    }
}

function getdataFromBillMain($formno)
{
    if($formno != ""){
        $sql = getfn()->db->query("SELECT
        ma_memo_vender,
        ma_memo_admin,
        ma_taxid
        FROM bill_main WHERE ma_formno = '$formno'
        ");

        return $sql;
    }
}

function getdataFromBillFiles($formno)
{
    if($formno != ""){
        $sql = getfn()->db->query("SELECT
        bf_filename,
        bf_filepath
        FROM bill_files WHERE bf_formno = '$formno'
        ");
        return $sql;
    }
}

function getDataVenderByTaxid($taxid)
{
    if($taxid != ""){
        getfn()->db_mssql = getfn()->load->database('mssql' , TRUE);//sln,ca database
        getfn()->db_mssql2 = getfn()->load->database('mssql2' , TRUE);//tbb , st database

        $sql1 = getfn()->db_mssql->query("SELECT
        name,
        slc_fname,
        slc_lname,
        address,
        bpc_whtid
        FROM vendtable WHERE bpc_whtid = '$taxid'
        GROUP BY name , slc_fname , slc_lname , address , bpc_whtid
        ");


        $sql2 = getfn()->db_mssql2->query("SELECT
        name,
        slc_fname,
        slc_lname,
        address,
        bpc_whtid
        FROM vendtable WHERE bpc_whtid = '$taxid'
        GROUP BY name , slc_fname , slc_lname , address , bpc_whtid
        ");

        if($sql1->num_rows() != 0){
            return $sql1;
        }else{
            return $sql2;
        }

    }
}

function getDataEmailByFormno($formno)
{
    if($formno != ""){
        $sql = getfn()->db->query("SELECT
        bill_main.ma_autoid,
        bill_main.ma_formno,
        bill_main.ma_taxid,
        vender_member.vm_email,
        bill_main.ma_venderaccount,
        bill_main.ma_dataareaid,
        bill_main.ma_payment,
        bill_main.ma_numofday,
        bill_main.ma_dayfix,
        bill_main.ma_dateofbilling,
        bill_main.ma_dateofpay,
        bill_main.ma_dateofpayreal,
        bill_main.ma_dateofcalc,
        bill_main.ma_period,
        bill_main.ma_periodbilling,
        bill_main.ma_datetime,
        bill_main.ma_status,
        bill_main.ma_memo_vender,
        bill_main.ma_memo_admin,
        bill_main.ma_ap_name,
        bill_main.ma_ap_ecode,
        bill_main.ma_ap_datetime,
        bill_main.ma_fn_name,
        bill_main.ma_fn_ecode,
        bill_main.ma_fn_datetime
        FROM
        bill_main
        INNER JOIN vender_member ON vender_member.vm_taxid = bill_main.ma_taxid
        WHERE ma_formno = '$formno'
        ");

        return $sql;
    }
}













// END Helper
?>