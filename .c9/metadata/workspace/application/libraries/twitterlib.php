{"changed":true,"filter":false,"title":"twitterlib.php","tooltip":"/application/libraries/twitterlib.php","value":"<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');\n/**\n * Codeigniter-Twitter-Search-Library\n *\n * Search for tweets using search/streaming api\n *\n * by Elliott Landsborough - github.com/ElliottLandsborough\n */\nclass Twitterlib {\n\n\tvar $terms;\n\n\tpublic function __construct()\n\t{\n\t\tini_set('precision', 20); // http://stackoverflow.com/a/8106127/908257\n\t\t$this->CI = & get_instance();\n\t\t$this->terms = $this->searchterms();\n\t}\n\n\t/**\n\t* Get all of the search terms from the db and return them in an array\n\t* output: array or false\n\t*/\n\tpublic function searchterms($result=false)\n\t{\n\t\t$this->CI->db->select('term');\n\t\t$query = $this->CI->db->get('search_terms');\n\t\tif ( $query->num_rows() > 0 )\n\t\t{\n\t\t\t$result = $query->result_array();\n\t\t\tforeach ($result as $row=>$data)\n\t\t\t{\n\t\t\t\t$result[$row]=$data['term'];\n\t\t\t}\n\t\t}\n\t\treturn $result;\n\t}\ncd\n\t/**\n\t* Stream the tweets via streaming api (can be run at the same time as search)\n\t*\n\t* Designed to be run via cron, this function does not automatically\n\t* restart after a tweet has been found. I would recommend doing it\n\t* once a minute but I've got away with anything up to once every\n\t* two seceonds without being rate limited before.\n\t*\n\t*/\n\tpublic function stream()\n\t{\n\t\t// get user/pass from config/twitter.php\n\t\t$this->CI->config->load('twitter');\n\t\t$user = $this->CI->config->item('user');\n\t\t$pass = $this->CI->config->item('pass');\n\t\t// check if user and pass are set\n\t\tif( !isset($user) || !isset($pass) || !$user || !$pass )\n\t\t{\n\t\t\techo 'ERROR: Username or password not found.'.PHP_EOL;\n\t\t}\n\t\telse\n\t\t{\n\t\t\t// start an infinite loop for reconnection attempts\n\t\t\twhile(1)\n\t\t\t{\n\t\t\t\t$fp = fsockopen(\"ssl://stream.twitter.com\", 443, $errno, $errstr, 30); // has to be ssl\n\t\t\t\tif(!$fp)\n\t\t\t\t{\n\t\t\t\t\techo $errstr.'('.$errno.')'.PHP_EOL;\n\t\t\t\t}\n\t\t\t\telse\n\t\t\t\t{\n\t\t\t\t\t// build request\n\t\t\t\t\t$trackstring=implode(',',$this->terms);\n\t\t\t\t\t$query_data = array('track' => $trackstring,'include_entities' => 'true');\n\t\t\t\t\t$request = \"GET /1/statuses/filter.json?\" . http_build_query($query_data) . \" HTTP/1.1\\r\\n\";\n\t\t\t\t\t$request .= \"Host: stream.twitter.com\\r\\n\";\n\t\t\t\t\t$request .= \"Authorization: Basic \" . base64_encode($user . ':' . $pass) . \"\\r\\n\\r\\n\";\n\t\t\t\t\t// write request\n\t\t\t\t\tfwrite($fp, $request);\n\t\t\t\t\t// set stream to non-blocking - research if this is really needed.\n\t\t\t\t\t// stream_set_blocking($fp, 0);\n\t\t\t\t\twhile(!feof($fp))\n\t\t\t\t\t{\n\n\t\t\t\t\t\t$read   = array($fp);\n\t\t\t\t\t\t$write  = null;\n\t\t\t\t\t\t$except = null;\n\n\t\t\t\t\t\t// Select, wait up to 10 minutes for a tweet.\n\t\t\t\t\t\t// If no tweet, reconnect by retsarting loop.\n\t\t\t\t\t\t$res = stream_select($read, $write, $except, 600, 0);\n\t\t\t\t\t\tif ( ($res == false) || ($res == 0) )\n\t\t\t\t\t\t{\n\t\t\t\t\t\t\tbreak;\n\t\t\t\t\t\t}\n\n\t\t\t\t\t\t$json = fgets($fp);\n\t\t\t\t\t\t$data = json_decode($json, true);\n\t\t\t\t\t\tif($data)\n\t\t\t\t\t\t{\n\t\t\t\t\t\t\t$this->process($data);\n\t\t\t\t\t\t}\n\t\t\t\t\t}\n\t\t\t\t\tfclose($fp);\n\t\t\t\t\t// sleep for ten seconds before reconnecting\n\t\t\t\t\tsleep(10);\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t}\n\n\t/**\n\t* Search the tweets via search feed (can be run at the same time as stream).\n\t*\n\t* Designed to be run though something like nohup, this function loops\n\t* continuously and only dies when ma nually closed. A better solution would\n\t* be to use something like node to parse the streaming api and save it into\n\t* mySQL but this was made for people who absolutely have to use PHP.\n\t*\n\t*/\n\tpublic function search($cachetime=null)\n\t{\n\t\t// if the number of minutes to cache has been set\n\t\tif($cachetime != null)\n\t\t{\n\t\t\t// load the memcache adapter\n\t\t\t$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));\n\t\t}\n\t\t// if we are not caching or if our cache has run out\n\t\tif( $cachetime == null || ! $content = $this->CI->cache->get('twitter-api-search') )\n\t\t{\n\t\t\t$query=implode('+OR+',$this->terms);\n\t\t\t$query_data = array('q' => $query, 'result_type' => 'recent', 'include_entities' => 'true','rpp' => 100,'result_type'=>'mixed');\n\t\t\t$url = 'http://search.twitter.com/search.json?'.http_build_query($query_data);\n\t\t\t$ch = curl_init();\n\t\t\tcurl_setopt ($ch, CURLOPT_URL, $url);\n\t\t\tcurl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);\n\t\t\t$content = curl_exec($ch);\n\t\t\tcurl_close($ch);\n\t\t\t$content = json_decode($content,true);\n\t\t\t// if we want to cache for an amount of time\n\t\t\tif($cachetime != null)\n\t\t\t{\n\t\t\t\t// cache the results\n\t\t\t\t$this->CI->cache->save('twitter-api-search', $content, $cachetime*60);\n\t\t\t}\n\t\t}\n\t\t// if we have new results\n\t\tif(isset($content))\n\t\t{\n\t\t\tif(empty($content))\n\t\t\t{\n\t\t\t\techo 'ERROR: No data was returned'.PHP_EOL;\n\t\t\t}\n\t\t\telse if(isset($content['error']))\n\t\t\t{\n\t\t\t\techo 'ERROR: '.$content['error'].PHP_EOL;\n\t\t\t}\n\t\t\telse if(isset($content['results']))\n\t\t\t{ // and if there were no errors\n\t\t\t\tif(count($content['results'])>0)\n\t\t\t\t{\n\t\t\t\t\tforeach ($content['results'] as $result)\n\t\t\t\t\t{\n\t\t\t\t\t\t// process each tweet one at a time\n\t\t\t\t\t\t$this->process($result);\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t}\n\n\t/**\n\t* Search the tweets via search feed on 1.1 api (can be run at the same time as stream).\n\t*\n\t* Designed to be run though something like nohup, this function loops\n\t* continuously and only dies when ma nually closed. A better solution would\n\t* be to use something like node to parse the streaming api and save it into\n\t* mySQL but this was made for people who absolutely have to use PHP.\n\t*\n\t*/\n\tpublic function searchone($cachetime=null)\n\t{\n\t\t// do oauth\n\t\t$this->CI->load->library('twitteroauth');\n\t\t// get user/pass from config/twitter.php\n\t\t$this->CI->config->load('twitter');\n\t\t$consumer_token = $this->CI->config->item('consumer_token');\n\t\t$consumer_secret = $this->CI->config->item('consumer_secret');\n\t\t$access_token = $this->CI->config->item('access_token');\n\t\t$access_secret = $this->CI->config->item('access_secret');\n\t\t$connection = $this->CI->twitteroauth->create($consumer_token, $consumer_secret, $access_token, $access_secret);\n\t\t$content = $connection->get('account/verify_credentials');\n\t\tif(isset($content->errors))\n\t\t{\n\t\t\tforeach ($content->errors as $error)\n\t\t\t{\n\t\t\t\techo $error->code.' '.$error->message.PHP_EOL;\n\t\t\t}\n\t\t\tdie;\n\t\t}\n\t\telse\n\t\t{\n\t\t\t// if the number of minutes to cache has been set\n\t\t\tif($cachetime != null)\n\t\t\t{\n\t\t\t\t// load the memcache adapter\n\t\t\t\t$this->CI->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));\n\t\t\t}\n\t\t\t// if we are not caching or if our cache has run out\n\t\t\tif( $cachetime == null || ! $content = $this->CI->cache->get('twitter-api-search') )\n\t\t\t{\n\t\t\t\t$query=implode('+OR+',$this->terms);\n\t\t\t\t$query_data = array('q' => $query, 'result_type' => 'recent', 'include_entities' => 'true','rpp' => 1,'result_type'=>'mixed');\n\t\t\t\t$url = 'https://api.twitter.com/1.1/search/tweets.json';\n\t\t\t\t$content=$connection->get($url,$query_data);\n\t\t\t\t// if we want to cache for an amount of time\n\t\t\t\tif($cachetime != null)\n\t\t\t\t{\n\t\t\t\t\t// cache the results\n\t\t\t\t\t$this->CI->cache->save('twitter-api-search', $content, $cachetime*60);\n\t\t\t\t}\n\t\t\t}\n\t\t\t// if we have new results\n\t\t\tif(isset($content))\n\t\t\t{\n\t\t\t\tif(isset($content->errors))\n\t\t\t\t{\n\t\t\t\t\tforeach ($content->errors as $error)\n\t\t\t\t\t{\n\t\t\t\t\t\techo $error->code.' '.$error->message.PHP_EOL;\n\t\t\t\t\t}\n\t\t\t\t\tdie;\n\t\t\t\t}\n\t\t\t\tif (isset($content->statuses))\n\t\t\t\t{\n\t\t\t\t\tforeach ($content->statuses as $tweet)\n\t\t\t\t\t{\n\t\t\t\t\t\t// process each tweet one at a time\n\t\t\t\t\t\t$this->process($tweet);\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t}\n\n\t/**\n\t* Process the tweet data and record it in mysql.\n\t* Input: $data (array - output from api)\n\t*/\n\tpublic function process($data=null)\n\t{\n\t\t// if the tweet has an id, if the tweet does not already exist in db, if there is at least one hashtag\n\t\tif( ( ( is_array($data) && isset($data['id_str']) ) || ( is_object($data) && isset($data->id_str) ) ) && !$this->exists($data) )\n\t\t{\n\t\t\tif($this->save($data))\n\t\t\t{\n\t\t\t\techo 'Saved a tweet!'.PHP_EOL;\n\t\t\t}\n\t\t}\n\t}\n\n\t/**\n\t* Find out if the tweet has already been inserted into the db\n\t* input: $data (array) - array from api - needs to contain $data['id_str']\n\t* output: true/false\n\t*/\n\tfunction exists($data=null,$result=false)\n\t{\n\t\tif( ( is_array($data) && isset($data['id_str']) ) || ( is_object($data) && isset($data->id_str) ) )\n\t\t{\n\t\t\t$this->CI->db->select('tweet_id');\n\t\t\tif(is_array($data))\n\t\t\t{\n\t\t\t\t$tweet_id=$data['id_str'];\n\t\t\t}\n\t\t\telse\n\t\t\t{\n\t\t\t\t$tweet_id=$data->id_str;\n\t\t\t}\n\t\t\t$this->CI->db->where('tweet_id',$tweet_id);\n\t\t\t$query=$this->CI->db->get('tweets',1,0);\n\t\t\tif($query->num_rows()>0)\n\t\t\t{\n\t\t\t\t$result=true;\n\t\t\t}\n\t\t}\n\t\treturn $result;\n\t}\n\n\t/**\n\t* Save the tweet in MySQL.\n\t* input: $data - array of a tweet returned from the twitter api\n\t* input: $data['id_str'], $data['user']['id_str'] OR data['id_str'], $data['from_user_id_str']\n\t* output: true/false\n\t*/\n\tfunction save($data=null,$result=false)\n\t{\n\t\t// if we have a tweet with an ID\n\t\tif ( (is_array($data) && isset($data['id_str']) ) || ( is_object($data) && isset($data->id_str) ) )\n\t\t{\n\t\t\tif( is_array($data) && isset($data['user']['id_str']) )\n\t\t\t{ // if we are dealing with streaming api\n\t\t\t\t$user_id=$data['user']['id_str'];\n\t\t\t\t$tweet_id=$data['id_str'];\n\t\t\t}\n\t\t\telse if ( is_array($data) && isset($data['from_user_id_str']) )\n\t\t\t{ // if we are dealing with search api\n\t\t\t\t$user_id=$data['from_user_id_str'];\n\t\t\t\t$tweet_id=$data['id_str'];\n\t\t\t}\n\t\t\telse if ( is_object($data) && isset($data->user->id_str) )\n\t\t\t{\n\t\t\t\t$user_id=$data->user->id_str;\n\t\t\t\t$tweet_id=$data->id_str;\n\n\t\t\t}\n\t\t\t// if we have detected a user id in the tweet array\n\t\t\tif( isset($user_id) )\n\t\t\t{\n\t\t\t\t// set input\n\t\t\t\t$input=array( 'tweet_id' =>  $tweet_id, 'user_id' => $user_id);\n\t\t\t\t// save tweet in db\n\t\t\t\t$result=$this->CI->db->insert('tweets',$input);\n\t\t\t}\n\t\t}\n\t\treturn $result;\n\t}\n\n}\n\n/* End of file twitterlib.php */","undoManager":{"mark":-2,"position":1,"stack":[[{"start":{"row":37,"column":0},"end":{"row":37,"column":1},"action":"insert","lines":["c"],"id":2}],[{"start":{"row":37,"column":1},"end":{"row":37,"column":2},"action":"insert","lines":["d"],"id":3}]]},"ace":{"folds":[],"scrolltop":0,"scrollleft":0,"selection":{"start":{"row":37,"column":2},"end":{"row":37,"column":2},"isBackwards":false},"options":{"guessTabSize":true,"useWrapMode":false,"wrapToView":true},"firstLineState":0},"timestamp":1488135892477}