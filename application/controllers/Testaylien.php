<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Testaylien extends CI_Controller {

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
	public function index()
	{
		$this->load->library('Aylien');
		$result = $this->aylien->Sentiment(array(
			'text' => "The only thing worse than the reviews for ‪#‎MothersDayMovie‬ is that awful wig ‪#‎JuliaRoberts‬ wears in the film. Here are some other options they considered in their hair and makeup tests! Top right wouldn't have been that bad!! https://www.instagram.com/p/BEzBflioaos/g!"));
  		
  		var_dump($result);
  		
  		echo "<br /><br />";
  		
  		$result = $this->aylien->Hashtags(array(
			'text' => "The only thing worse than the reviews for ‪#‎MothersDayMovie‬ is that awful wig ‪#‎JuliaRoberts‬ wears in the film. Here are some other options they considered in their hair and makeup tests! Top right wouldn't have been that bad!! https://www.instagram.com/p/BEzBflioaos/g!"));
  		
  		
  		var_dump($result);
  		
  		echo "<br /><br />";
  		
  		$result = $this->aylien->Classify(array(
			'text' => "The only thing worse than the reviews for ‪#‎MothersDayMovie‬ is that awful wig ‪#‎JuliaRoberts‬ wears in the film. Here are some other options they considered in their hair and makeup tests! Top right wouldn't have been that bad!! https://www.instagram.com/p/BEzBflioaos/g!"));
  		
  		
  		var_dump($result);
	}
}
