<?php
class User_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
    }
    
    public function login($u,$p)
    {
        // Add salt to submitted password
        $p = PASSWORD_SALT . $p;
        $p = hash('sha256',$p);
        
        // Validate username/password; return false if combination not found
        $query = $this->db->query("SELECT * FROM user WHERE email='" . $u . "' AND password='" . $p . "'");
        if($query->result_id->num_rows == 0)
        {
            return false;
        }
        
        // Set session variables
        foreach($query->result() as $row)
        {
            $data = array(
              'user_id' => $row->id,
              'username' => $row->username,
              'user_level' => $row->user_level,
              'email' => $row->email,
              'twitter' => $row->twitter,
              'facebook' => $row->facebook,
              'last_login' => $row->last_login
            );
            $this->session->set_userdata($data);
        }
        
        // Update last login
        $this->db->set('last_login',date('Y-m-d H:i:s'));
        $this->db->where('id',$this->session->userdata('user_id'));
        $this->db->update('user');
        
        return true;
    }
    
    
}
