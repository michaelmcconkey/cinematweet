<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Functions extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	 
	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
	}
	 
	public function index()
	{
		redirect('/');
	}

	
	public function login()
	{
		$this->load->library('form_validation');
		$this->load->model('user_model');

		if(!isset($_POST['username']) && !isset($_POST['password']))
		{
			// Check if logged in
			if($this->is_logged_in())
			{
				redirect('/');
			}
		
			$data = array(
				'content' => 'login_page'	
			);
		
			$this->load->view('template/user',$data);
		}
		
		else
		{

			$this->form_validation->set_rules('email', 'Email', 'required|valid_email');
			$this->form_validation->set_rules('password', 'Password', 'required|callback_login_do');
			
			if($this->form_validation->run() != FALSE)
			{
				// form passed
				$this->load->model('user_model');
				if($this->user_model->login($_POST['email'],$_POST['password']))
				{
					if($this->session->userdata('user_level') == 1)
						redirect('/');
					else 
						redirect('dashboard');
				}
				else
				{
					$_POST['email'] = null;
					$_POST['password'] = null;
					
					$data = array(
						'content' => 'login_page'	
					);
					$this->load->view('template/user',$data);
				}
			}
			
			else
			{
				$data = array(
					'content' => 'login_page'	
				);
				$this->load->view('template/user',$data);
			}
		}
	}
	
	public function login_do()
	{
		if(form_error('email') || form_error('password'))
			return true;
		if($this->user_model->login($_POST['email'],$_POST['password']))
			return true;
		else 
		{
			$this->form_validation->set_message('login_do','Email/Password combination not found. Please try again.');
			return false;
		}
	}
	
	public function logout()
    {
    	$this->session->unset_userdata('user_id');
    	$this->session->unset_userdata('user_level');
    	$this->session->unset_userdata('email');
        $this->session->sess_destroy();
        
        if($this->is_logged_in())
            show_404('logout');
        else
            redirect('/');
    }
	
	private function is_logged_in()
	{
		if($this->session->userdata('user_id'))
			return true;
		return false;
	}
}
