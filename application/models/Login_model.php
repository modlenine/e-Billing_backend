<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Login_model extends CI_Model
{

    public function __construct()
    {
        parent::__construct();
        //Do your magic here
        date_default_timezone_set("Asia/bangkok");
        $this->db2 = $this->load->database('saleecolour', TRUE);
    }


    private function escape_string()
    {
        return mysqli_connect("localhost", "ant", "Ant1234", "saleecolour");
    }

    public function logout()
    {
        session_destroy();
        $this->session->unset_userdata('referrer_url');
        header("refresh:0; url=" . base_url());
        die();
    }



    public function getuser()
    {
        $sessionEcode = $_SESSION['ecode'];
        $sql = $this->db2->query("SELECT * FROM member WHERE ecode = '$sessionEcode' ");
        return $sql->row();
    }


    
}/* End of file ModelName.php */
