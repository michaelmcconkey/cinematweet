<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller {
 
    public function __construct()
    {
        parent::__construct();
        
        $this->load->helper('url');

        if($this->session->userdata('user_id') == null || $this->session->userdata('user_level') == null || $this->session->userdata('user_level') > 0)
            redirect('/');
    }
    
    public function index()
    {
        $this->load->helper('form');
        $query = $this->db->query("SELECT *,admin_log.id AS admin_log_id FROM admin_log JOIN movie ON admin_log.movie_id=movie.id JOIN movie_hashtag ON admin_log.movie_id=movie_hashtag.movie_id WHERE admin_log.viewed = '0'");
        
        $data = array(
            'content' => 'dashboard_home',
            'log' => $query->result()
        );
        $this->load->view('template/admin',$data);
    }
    
    public function update_movie()
    {
        if($_POST['form'] == 'upcomingmovies')
        {
            $this->load->library('form_validation');
            $this->load->model('UpdateMovie_model');
            if($_POST['old_title'] != $_POST['title']) $title = $_POST['title'];
            else $title = FALSE;
        
            if($_POST['old_plot'] != $_POST['plot']) $plot = $_POST['plot'];
            else $plot = FALSE;
        
            if($_POST['old_release_date'] != $_POST['release_date']) $release_date = $_POST['release_date'];
            else $release_date = FALSE;
        
            if($_POST['old_hashtag'] != $_POST['hashtag']) $hashtag = $_POST['hashtag'];
            else $hashtag = FALSE;
            
            $old_cast = explode(", ",$_POST['old_cast']);
            $cast = explode(", ",$_POST['cast']);
            if($old_cast == $cast) $cast = FALSE;
            
            $old_genre = explode(", ",$_POST['old_genre']);
            $genre = explode(", ",$_POST['genre']);
            if($old_genre == $genre) $genre = FALSE;
        
            $this->UpdateMovie_model->admin_update_movie($_POST['movie_id'],$title,$plot,$release_date,$hashtag,$genre,$cast);
            
            redirect('dashboard/upcomingmovies');
        }
        else 
        {
            $this->load->library('form_validation');
            $this->load->model('UpdateMovie_model');

            if($_POST['old_title'] == $_POST['title'] && $_POST['old_plot'] == $_POST['plot'] && $_POST['old_release_date'] == $_POST['release_date'] && $_POST['old_hashtag'] == $_POST['hashtag'])
            {
                // Update admin_log viewed
                if($_POST['admin_log_id'])
                    $this->UpdateMovie_model->update_admin_log($_POST['admin_log_id']);
                redirect('dashboard');
            }
        
            if($_POST['old_title'] != $_POST['title']) $title = $_POST['title'];
            else $title = FALSE;
        
            if($_POST['old_plot'] != $_POST['plot']) $plot = $_POST['plot'];
            else $plot = FALSE;
        
            if($_POST['old_release_date'] != $_POST['release_date']) $release_date = $_POST['release_date'];
            else $release_date = FALSE;
        
            if($_POST['old_hashtag'] != $_POST['hashtag']) $hashtag = $_POST['hashtag'];
            else $hashtag = FALSE;
        
            $this->UpdateMovie_model->admin_update_movie($_POST['movie_id'],$title,$plot,$release_date,$hashtag);
            $this->UpdateMovie_model->update_admin_log($_POST['admin_log_id']);
            redirect('dashboard');
        }
            
        }
 
    public function addmovie()
    {
        $this->load->helper('form');

        $data = array(
            'content' => 'add_movie'
        );
        $this->load->view('template/admin',$data);
    }
    
    public function addmovie_do()
    {
        $this->load->library('form_validation');
        $this->load->model('UpdateMovie_model');
        
        $this->form_validation->set_rules('title','Title','required|callback_already_exists');
        $this->form_validation->set_rules('plot','Plot','required');
        $this->form_validation->set_rules('release_date','Release Date','required');
        
        if($this->form_validation->run())
        {
            $cast = explode(',',$_POST['cast']);
            $i = 0;
            while($i < sizeof($cast))
            {
                $cast[$i] = trim($cast[$i]);
                $i++;
            }
            
            $genre = explode(',',$_POST['genre']);
            $i = 0;
            while($i < sizeof($genre))
            {
                $genre[$i] = trim($genre[$i]);
            
                $i++;
            }

            $movie = array(
                'title' => $_POST['title'],
                'plot' => $_POST['plot'],
                'release_date' => $_POST['release_date'],
                'cast' => $cast,
                'genre' => $genre,
                'movie_poster' => $_POST['movie_poster']
            );
            
            $this->UpdateMovie_model->add($movie);
            
            redirect('dashboard/addmovie');
        }
        else
        {
            $this->load->helper('form');

            $data = array(
                'content' => 'add_movie'
            );
            $this->load->view('template/admin',$data);
        }
    }
    
    public function upcomingmovies()
    {
        $this->load->helper('form');
        $this->load->model('Movie_model');

        $movies = $this->Movie_model->upcomingmovies();
    
        $data = array(
            'movies' => $movies,
            'content' => 'admin_upcoming_movies'
        );
        
        $this->load->view('template/admin',$data);
    }
    
    
    public function already_exists()
    {
        $query = $this->db->query('SELECT id,title FROM movie WHERE title="' . $_POST['title'] . '"');
        if($query->result_id->num_rows > 0) 
        {
            $this->form_validation->set_message('already_exists','This movie already exists in the database.');
			return false;
        }
        else
            return true;
    }
}