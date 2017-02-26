<?php
class Movie_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
        
        $this->load->database();
    }
    
    function upcomingmovies()
    {
        $today = date('Y-m-d H:i:s');
        $query = $this->db->query('SELECT *, movie.id AS movie_id, GROUP_CONCAT(DISTINCT genre.genre SEPARATOR \', \') AS genre, GROUP_CONCAT(DISTINCT cast.name SEPARATOR \', \') AS cast FROM movie JOIN movie_hashtag ON movie.id=movie_hashtag.movie_id JOIN genre ON movie.id=genre.movie_id JOIN cast ON movie.id=cast.movie_id WHERE movie.release_date>"' . $today . '" GROUP BY genre.movie_id,cast.movie_id ORDER BY movie.release_date');
        return $query->result();
    }
}
