<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 
require_once APPPATH.'third_party/aylien_api/src/AYLIEN/TextAPI.php';
 
define('AYLIEN_CACHE_PATH', APPPATH . 'third_party/aylien_api/cache');
 
class Aylien extends \AYLIEN\TextAPI
{ 
    public $cache_location = AYLIEN_CACHE_PATH;
 
    public function __construct() {
        $a_id = 'bb400ffd';
        $a_key = 'acdac302bd952cb7463fac183640aec9';
        parent::__construct($a_id,$a_key,true);
    } 
}