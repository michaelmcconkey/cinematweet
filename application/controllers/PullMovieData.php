<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class PullMovieData extends CI_Controller {

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
		$this->load->view('welcome_message');
	}
	
	public function updateMovieList()
	{
		$this->load->model('UpdateMovie_model');
		
		$this->load->library('RSS');
 
		// Pull feed from Movie-List.com
		$feed = $this->rss;
		$feed->set_feed_url('http://feeds.feedburner.com/movie-list/jsZD');
		$success = $feed->init();
		$feed->handle_content_type();
 
 		// Parse out junk from feed
		$movie_rss_list = $feed->data["child"][""]["rss"][0]["child"][""]["channel"][0]["child"][""]["item"];
		unset($movie_rss_list[0]);
		
		$release_date = null;
		
		// Parse each movie item in feed
		foreach($movie_rss_list as $movie)
		{
			$director = null;
			$cast = null;
			$plot = null;
			$genre = null;
			$release_date = null;
			
			$title = $movie["child"][""]["title"][0]["data"];
			$movie_poster = $this->str_img_src($movie["child"][""]["description"][0]["data"]);
			
			// Parse through description
			$desc_block = strip_tags($movie["child"][""]["description"][0]["data"], "<br><strong><b>");
			foreach(explode("<br>",$desc_block) as $desc_item)
			{
				$item = explode("</b>", $desc_item);
				$item = str_replace("<b>","",$item);
				if($item[0] == "\nDirector:") $director = $item[1];
				else if($item[0] == "\nCast:") $cast = $item[1];
				else if($item[0] == "\nPlot:") $plot = $item[1];
				else if($item[0] == "\nGenre:") $genre = $item[1];
				else if($item[0] == "\nRelease Date:") $release_date = $item[1];
			}
			
			// Format date
			if($release_date != null &&  strpos($release_date, "TBA") == FALSE && strpos($release_date, ", ") == TRUE)
			{
				
				$release_date = explode(", ",trim($release_date));
				$y = $release_date[1];
				$d_m = explode(" ",$release_date[0]);
				$d = $d_m[1];
				$m = $d_m[0];
				if($m == "January") $m = "01";
				else if($m == "February") $m = "02";
				else if($m == "March") $m = "03";
				else if($m == "April") $m = "04";
				else if($m == "May") $m = "05";
				else if($m == "June") $m = "06";
				else if($m == "July") $m = "07";
				else if($m == "August") $m = "08";
				else if($m == "September") $m = "09";
				else if($m == "October") $m = "10";
				else if($m == "November") $m = "11";
				else if($m == "December") $m = "12";
				if($d < 10 && strlen($d) < 2) $d = "0" . $d;
				$release_date = $y . "-" . $m . "-" . $d . " 00:00:00";
			}
			else
				$release_date = "0000-00-00 00:00:00";
			
			echo "<br /><br />" . $release_date . "<br /> <br />";
			
			$movie = array(
				'title' => $title,
				'movie_poster' => $movie_poster,
				'plot' => trim($plot),
				'cast' => explode(", ",trim($cast)),
				'genre' => explode(", ",trim($genre)),
				'release_date' => $release_date
			);
			
			// Pass movie information to updateMovie_model to add to DB
			$this->UpdateMovie_model->add($movie);
			
			// Clear variables
			$director = null;
			$cast = null;
			$plot = null;
			$genre = null;
			$release_date = null;
		}
	}
	
	public function oneTimeAddHashTag()
	{
		$this->load->model('UpdateMovie_model');
		$this->UpdateMovie_model->oneTimeAddHashTag();
	}
	
	/**
    * Searches for the first occurence of an html <img> element in a string
    * and extracts the src if it finds it. Returns boolean false in case an
    * <img> element is not found.
    * @param    string  $str    An HTML string
    * @return   mixed           The contents of the src attribute in the
    *                           found <img> or boolean false if no <img>
    *                           is found
    */
    private function str_img_src($html) {
        if (stripos($html, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            preg_match($imgsrc_regex, $html, $matches);
            unset($imgsrc_regex);
            unset($html);
            if (is_array($matches) && !empty($matches)) {
                return $matches[2];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    
    private function format_date($release_date)
    {
    	if($release_date != null &&  strpos($release_date, "TBA") == FALSE && strpos($release_date, ", ") == TRUE)
			{
				echo "<br /><br />" . $release_date . "<br /> <br />";
				$release_date = explode(", ",trim($release_date));
				$y = $release_date[1];
				$d_m = explode(" ",$release_date[0]);
				$d = $d_m[1];
				$m = $d_m[0];
				if($m == "January") $m = "01";
				else if($m == "February") $m = "02";
				else if($m == "March") $m = "03";
				else if($m == "April") $m = "04";
				else if($m == "May") $m = "05";
				else if($m == "June") $m = "06";
				else if($m == "July") $m = "07";
				else if($m == "August") $m = "08";
				else if($m == "September") $m = "09";
				else if($m == "October") $m = "10";
				else if($m == "November") $m = "11";
				else if($m == "December") $m = "12";
				$release_date = $y . "-" . $m . "-" . $d . " 00:00:00";
			}
			else
				$release_date = "0000-00-00 00:00:00";
    
    	
    	return $release_date;
    }
}
