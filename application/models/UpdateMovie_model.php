<?php
class UpdateMovie_model extends CI_Model {

    function __construct()
    {
        parent::__construct();
        
        $this->load->database();
    }
    
    public function add($movie)
    {
        // Check if movie already exists in DB
        $query = $this->db->query('SELECT title FROM movie WHERE title="' . $movie["title"] . '"');
        if ($query->result_id->num_rows > 0) 
        {
            echo "Match found - " . $movie["title"];
            return $this->update_movie($movie);
        }
        
        // Add movie to DB
        $data = array(
          'title' => $movie['title'],
          'plot' => $movie['plot'],
          'release_date' => $movie['release_date']
        );
        echo "Not found - adding " . $movie["title"];
        $result = $this->db->insert('movie',$data);
        
        $movie_id = $this->db->insert_id();
        
        echo $movie_id . "<br /><br />";
        
        // Add to log
        $this->add_to_log($movie_id,"new");
        
        if(!$result)
            return $this->db->error();
        
        
        // Add Hashtag
        $this->add_hashtag($movie['title'],$movie_id);
        
        // Add cast if exists
        if($movie['cast'] != null && $movie['cast'] != false)
            $this->add_cast($movie['cast'],$movie_id);
            
        // Add genre if exists
        if($movie['genre'] != null && $movie['genre'] != false)
            $this->add_genre($movie['genre'],$movie_id);
            
        // Add movie poster if exists
        if($movie['movie_poster'] != null && $movie['movie_poster'] != false)
            $this->add_movie_poster($movie['movie_poster'],$movie_id);
        
        return true;
    }
    
    private function add_hashtag($title,$id)
    {
        $replace = array(" ", ":", "'", "/", "\\", "#", '"', "-");
        $hashtag = "#" . str_replace($replace, "", $title);
        if(strlen($title) < 10)
            $hashtag = $hashtag . "Movie";
            
        $data = array(
            'movie_id' => $id,
            'hashtag' => $hashtag
        );
        $this->db->insert('movie_hashtag',$data);
        
    }
    
    private function add_cast($cast,$id)
    {
        $order = 1;
        foreach($cast as $member)
        {
            if($member != '')
            {
                $data = array(
                  'name' => $member,
                  'cast_order' => $order,
                  'movie_id' => $id
                );
                $result = $this->db->insert('cast',$data);
                if(!$result)
                    return $this->db->error();
            }
        }
        
        return true;
    }
    
    private function add_genre($genre,$id)
    {
        foreach($genre as $g)
        {
            if($g != '')
            {
                $data = array(
                  'genre' => $g,
                  'movie_id' => $id
                );
                $result = $this->db->insert('genre',$data);
                if(!$result)
                    return $this->db->error();
            $data++;
            }
        }
        
        return true;
    }
    
    private function add_movie_poster($url,$id)
    {
        // check if poster exists in db
        $query = $this->db->query("SELECT id FROM movie_poster WHERE url=" . $this->db->escape($url));
        if($query->result_id->num_rows > 0)
            return false;
        
        $data = array(
            'url' => $url,
            'movie_id' => $id
        );
        $result = $this->db->insert('movie_poster',$data);
        if($result)
            return $result;
        return $this->db->error();
    }
    
    private function update_movie($movie)
    {
        // pull movie info
        $query = $this->db->query("SELECT title,release_date,id,plot FROM movie WHERE title='" . $movie['title'] . "'");
        foreach($query->result() as $row)
        {
            echo "<br /><br />Found Match: " . $row->title . "<br /><br /> Current Relase Date: " . $row->release_date . "<br /><br />";
            // check if release date updated
            if($row->release_date != $movie['release_date'] && $row->release_date == '0000-00-00 00:00:00')
            {
                echo "Update Release Date to: " . $movie['release_date'];
                $this->db->set('release_date', $movie['release_date']);
                $this->db->where('title',$movie['title']);
                $this->db->update('movie');
                
                // Add to log
                $this->add_to_log($row->id,"update", "Update Release Date");
            }
            
            // check if plot updated
            if($row->plot != $movie['plot'] && $row->plot == '')
            {
                echo "Update Plot to: " . $movie['plot'];
                $this->db->set('plot', $movie['plot']);
                $this->db->where('title',$movie['title']);
                $this->db->update('movie');
                
                // Add to log
                $this->add_to_log($row->id,"update", "Update Plot");
            }
            
            //check if cast members were added
            foreach($movie['cast'] as $member)
            {
                $query = $this->db->query("SELECT id,name FROM cast WHERE name=\"" . $member . "\" AND movie_id=" . $row->id);
                if($query->result_id->num_rows == 0)
                {
                    $this->add_cast($movie['cast'],$row->id);
                }
            }
            
            // add poster if new
            $this->add_movie_poster($movie['movie_poster'],$row->id);
            
            // check if genre was added
            foreach($movie['genre'] as $g)
            {
                $query = $this->db->query("SELECT id,genre FROM genre WHERE genre='" . $g . "' AND movie_id=" . $row->id);
                if($query->result_id->num_rows == 0)
                {
                    $this->add_genre($movie['genre'],$row->id);
                }
            }
            
        }
        
        return true;
    }
    
    private function add_to_log($m_id,$type,$text="New Movie Added")
    {
        $data = array(
            'movie_id' => $m_id,
            'type' => $type,
            'text' => $text
        );
        return $this->db->insert('admin_log',$data);
    }
    
    public function update_admin_log($id,$viewed='1')
    {
        $this->db->set('viewed',$viewed);
        $this->db->where('id',$id);
        return $this->db->update('admin_log');
    }
    
    public function admin_update_movie($id,$t,$p,$r,$h,$g=false,$c=false)
    {
        if($t)
            $this->db->set('title',$t);
        if($p)
            $this->db->set('plot',$p);
        if($r)
            $this->db->set('release_date',$r);
        if($t || $p || $r)
        {
            $this->db->where('id',$id);
            $this->db->update('movie');
        }
        
        if($h)
        {
            $this->db->set('hashtag',$h);
            $this->db->where('movie_id',$id);
            $this->db->update('movie_hashtag');
        }
        
        if($c)
        {
            $query = $this->db->query("DELETE * FROM cast WHERE movie_id='" . $id . "'");
            if($query->result() != FALSE)
            {
                $this->add_cast($c,$id);
            }
            else
                return FALSE;
        }
        if($g)
        {
            $query = $this->db->query("DELETE * FROM genre WHERE movie_id='" . $id . "'");
            if($query->result() != FALSE)
            {
                $this->add_genre($g,$id);
            }
            else
                return FALSE;
        }
        
        return true;
    }
}
