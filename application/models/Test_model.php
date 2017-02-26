<?php
class Test_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
        
        $this->load->database();
        echo $this->db->platform();
    }
}
