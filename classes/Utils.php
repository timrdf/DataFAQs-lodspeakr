<? 

class Utils{
  
  public static function send303($uri, $ext){
  	header("HTTP/1.0 303 See Other");
  	header("Location: ".$uri);
  	header("Content-type: ".$ext);
  	echo $uri."\n\n";
  	exit(0);
  }
  
  public static function send404($uri){
  	header("HTTP/1.0 404 Not Found");
  	echo "LODSPeaKr could not find ".$uri." or information about it.\n\n";
  	exit(0);
  }
  
  public static function send406($uri){
  	header("HTTP/1.0 406 Not Acceptable");
  	echo "I can't find a representation suitable for the content type you accept\n\n";
  	exit(0);
  }
  
  public static function send500($msg = null){
  	header("HTTP/1.0 500 Internal Server Error");
  	echo "An internal error ocurred. Please try later\n\n";
  	if($msg != null){
  	  echo $msg;
  	}
  	exit(0);
  }
  
  public static function uri2curie($uri){
  	global $conf;
  	$ns = $conf['ns'];
  	$curie = $uri;
  	foreach($ns as $k => $v){
  	  $curie = preg_replace("|^$v|", "$k:", $uri);
  	  if($curie != $uri){
  	  	break;
  	  }
  	}
  	return $curie;
  }
  
  public static function curie2uri($curie){
  	global $conf;
  	$ns = $conf['ns'];
  	$parts = explode(':', $curie);
  	//Avoid if we have a namespace prefix called 'http'
  	if(preg_match('|^//|', $parts[1])){
  	  return $curie;
  	}  	
  	if(sizeof($parts)>1 && isset($ns[$parts[0]])){
  	  return $ns[$parts[0]].$parts[1];
  	}else{
  	  return $curie;
  	}
  }
  
  public static function getPrefix($curie){
  	global $conf;
  	$ns = $conf['ns'];
  	$parts = explode(':', $curie);
  	//Avoid if we have a namespace prefix called 'http'
  	if(preg_match('|^//|', $parts[1])){
  	  return $curie;
  	}  	
  	return array('ns' => $ns[$parts[0]], 'prefix' => $parts[0]);;
  }
  
  public static function getTemplate($uri){
  	$filename = str_replace(":", "_", $uri);
  	if(file_exists ($filename)){
  	  include_once($filename);
  	}
  }
  
  private static function sparqlResult2Obj($data){
  	global $conf;
  	$obj = array();
  	if(!isset($data['results'])){
  	  foreach($data as $k => $v){
  	  	$obj[$k] = Utils::sparqlResult2Obj($v);
  	  }
  	}else{
  	  $aux = $data['results']['bindings'];
  	  if(sizeof($aux)>0){
  	  	foreach($aux as $w){
  	  	  $row = array();
  	  	  foreach($w as $k => $v){
  	  	  	
  	  	  	$row[$k]['value'] = $v['value'];
  	  	  	if($v['type'] == 'uri'){
  	  	  	  $row[$k]['curie'] = Utils::uri2curie($v['value']);
  	  	  	  $row[$k]['uri'] = 1;
  	  	  	}elseif($v['type'] == 'bnode'){
  	  	  	  $row[$k]['curie'] = 'blankNode';
  	  	  	}  	  	  	
  	  	  }
  	  	  /*if(sizeof($aux) == 1){
  	  	  $obj = $row;
  	  	  }*/
  	  	  if(sizeof($row) >0){
  	  	  	array_push($obj, $row);
  	  	  }
  	  	  
  	  	}
  	  }
  	}
  	return $obj;
  }
  
  
  
  public static function getExtension($accept_string){
  	global $conf;
  	$extension = "html";
  	foreach($conf['http_accept'] as $ext => $accept_arr){
  	  if(in_array($accept_string, $accept_arr)){
  	    $extension = $ext;
  	  }
  	}
  	return $extension;
  }
  
  public static function getBestContentType($accept_string){
  	global $conf;
  	$a = explode(",", $accept_string);
  	$b = array();
  	foreach($a as $v){
  	  foreach($conf['http_accept'] as $formatTypeArray){
  	  	if(strstr($v, ";")){
  	  	  $aux = explode(";q=", $v);
  	  	  if(in_array($aux[0], $formatTypeArray)){
  	  	  	$b[$aux[0]] = $aux[1];
  	  	  }
  	  	}else{
  	  	  if(in_array($v, $formatTypeArray)){
  	  	  	$b[$v] = 1;
  	  	  }
  	  	}
  	  }
  	}
  	$a = $b;
  	arsort($a);
  	$ct = 'text/html';
  	foreach($a as $k => $v){
  	  $ct = $k;
  	  break;
  	}
  	if($ct == NULL || $ct == "" || $ct == "*/*"){
  	  $ct = 'text/html';
  	}
  	return $ct;
  }
  
  private static function serializeRdf($data, $extension){
  	global	$conf;
  	require('lib/arc2/ARC2.php');
  	$parser = ARC2::getRDFParser();
  	$parser->parse($conf['basedir'], $data);
  	$triples = $parser->getTriples();
  	if($conf['mirror_external_uris']){
  	  global $uri;
  	  global $localUri;
  	  $t = array();
  	  $t['s']      = $localUri;
  	  $t['s_type'] = 'uri';
  	  $t['p']      = "http://www.w3.org/2002/07/owl#sameAs";
  	  $t['o']      = $uri;
  	  $t['o_type'] = 'uri';  	 
  	  array_push($triples, $t);
  	  $t['p']      = "http://www.w3.org/2000/10/swap/pim/contact#preferredURI";
  	  array_push($triples, $t);
  	}
  	$ser;
  	switch ($extension){
  	case 'ttl':
  	  $ser = ARC2::getTurtleSerializer();
  	  break;
  	case 'nt':
  	  $ser = ARC2::getNTriplesSerializer();
  	  break;
  	case 'json':
  	  $ser = ARC2::getRDFJSONSerializer();
  	  break;
  	case 'rdf':
  	  $ser = ARC2::getRDFXMLSerializer();
  	  break;
  	}
  	$doc = $ser->getSerializedTriples($triples);
  	return $doc;
  }
  
  public static function processDocument($viewFile, $base, $data){
  	global $conf;
  	$contentType = $base['this']['contentType'];
  	$extension = Utils::getExtension($contentType); 
  	
  	header('Content-Type: '.$contentType);
  	if(!is_object($data)){
  	  $data = Utils::serializeRdf($data, $extension);
  	}
  	Utils::showView($base, $data, $viewFile);  	
  }
  
  public static function getResultsType($query){
  	global $conf;
  	if(preg_match("/select/i", $query)){
  	  return $conf['output']['select'];
  	}elseif(preg_match("/describe/i", $query)){
  	  return $conf['output']['describe'];
  	}elseif(preg_match("/construct/i", $query)){
  	  return $conf['output']['describe'];
  	}else{
  	  Utils::send500(null);
  	} 
  }
  
  public static function queryDir($modelDir, &$r, &$f){
  	global $conf;
  	global $uri;
  	global $base;
  	global $endpoints;
  	global $results;
  	$base['model']['directory'] = $modelDir;
  	$originalDir = getcwd();
  	
  	trigger_error("Entering $modelDir from ".getcwd(), E_USER_NOTICE);
  	chdir($modelDir);
  	$handle = opendir('.');
  	
  	while (false !== ($modelFile = readdir($handle))) {
  	  if($modelFile != "." && $modelFile != ".." && strpos($modelFile, ".") !== 0){
  	  	if(is_dir($modelFile)){
  	  	  //Save it for later, after all the queries in the current directory has been resolved
  	  	  $subDirs[]=$modelFile;
  	  	}else{
  	  	  $e = null;
  	  	  if(!isset($endpoints[$modelDir])){
  	  	  	trigger_error("Creating endpoint for $modelDir", E_USER_NOTICE);
  	  	  	if(!isset($conf['endpoint'][$modelDir])){
  	  	  	  trigger_error("Couldn't find $modelDir as a list of available endpoints. Will continue using local", E_USER_WARNING);
  	  	  	  $e = $endpoints['local'];
  	  	  	}else{  
  	  	  	  $endpoints[$modelDir] = new Endpoint($conf['endpoint'][$modelDir], $conf['endpoint']['config']);
  	  	  	  $e = $endpoints[$modelDir];
  	  	  	}
  	  	  }else{
  	  	  	$e = $endpoints[$modelDir];
  	  	  }
  	  	  if($modelDir != $base['type']){
  	  	  	if(!isset($r[$modelDir]) ){
  	  	  	  $r[$modelDir] = array();
  	  	  	  $f[$modelDir] = array();
  	  	  	}
  	  	  	Utils::queryFile($modelFile, $e, $r[$modelDir], $f[$modelDir]);
  	  	  }else{
  	  	  	Utils::queryFile($modelFile, $e, $r, $f);
  	  	  }
 	  	}
  	  }
  	}
  	closedir($handle);
  	$originalDir = $base['model']['directory'];
  	foreach($subDirs as $v){
  	  if(!isset($r[$modelDir])){
  	  	$r[$modelDir] = array();
  	  }
  	  if($modelDir != $base['type']){
  	  	Utils::queryDir($v, $r[$modelDir]);
  	  }else{
  	  	Utils::queryDir($v, $r);
  	  }
  	}  	
  	chdir($conf['home']);
  	//return $data;
  }
  
  
  public static function queryFile($modelFile, $e, &$rPointer, &$fPointer){
  	global $conf;
  	global $base;
  	global $results;
  	global $first;
  	$uri = $base['this']['value'];
  	$data = array();
  	
  	
 	if(!is_dir($modelFile)){
  	  require_once($conf['home'].'lib/Haanga/lib/Haanga.php');
  	  Haanga::configure(array(
  	  	'cache_dir' => $conf['home'].'cache/',
  	  	'autoescape' => FALSE,
  	  	));
  	  
  	  //Haanga supports the dot (.) convention only for objects
  	  if(is_array($base)){
  	  	$baseObj = Convert::array_to_object($base);
  	    $base = $baseObj;
  	  }
  	  $r2 = Convert::array_copy($results);
  	  $r = Convert::array_to_object($r2);
  	  $f = Convert::array_to_object($first);
 	  $vars = compact('uri', 'base', 'r', 'f');
 	  $fnc = Haanga::compile(file_get_contents($modelFile));
  	  $query = $fnc($vars, TRUE);
  	  if(is_object($base)){
  	  	$baseObj = Convert::object_to_array($base);
  	    $base = $baseObj;
  	  }
  	  if($conf['debug']){
  	  	echo $query;
  	  }
  	  trigger_error("Running query on endpoint", E_USER_NOTICE);
  	  $aux = $e->query($query, Utils::getResultsType($query));  	  
  	  if($modelFile != $base['type']){
  	  	if(!isset($rPointer[$modelFile])){
  	  	  $rPointer[$modelFile] = array();
  	  	  $first[$modelFile] = array();
  	  	}
  	  	if(Utils::getResultsType($query) == $conf['output']['select']){
  	  	  $rPointer[$modelFile] = Utils::sparqlResult2Obj($aux);
  	  	  $fPointer[$modelFile] = $rPointer[$modelFile][0];
  	  	  /*if(sizeof($rPointer)>0){
  	  	  $rPointer[$modelFile]['first'] = $rPointer[$modelFile][0];
  	  	  }*/
  	  	}else{
  	  	  $rPointer[$modelFile] = $aux;
  	  	}
  	  }else{
  	  	if(Utils::getResultsType($query) == $conf['output']['select']){
  	  	  $rPointer = Utils::sparqlResult2Obj($aux);
  	  	  $fPointer[$modelFile] = $rPointer[0];
  	  	  /*if(sizeof($rPointer)>0){
  	  	  $rPointer['first'] = $rPointer[0];
  	  	  }*/
  	  	}else{
  	  	  $rPointer = $aux;
  	  	}  	 
  	  }
  	}else{
  	  trigger_error("$modelFile is a directory, will process it later", E_USER_NOTICE);  	  
  	  if($modelFile != $base['type']){
  	  	if(!isset($rPointer[$modelFile])){
  	  	  $rPointer[$modelFile] = array();
  	  	}
  	  	Utils::queryDir($modelFile, $rPointer[$modelFile], $fPointer[$modelFile]);
  	  }else{
  	  	Utils::queryDir($modelFile, $rPointer, $fPointer);
  	  }
  	}
  }
  
  public static function internalize($array){
  	global $conf;
  	$firstKeyAppearance = true;
  	foreach($array as $key => $value){
  	  if(!isset($value['value'])){
  	  	$array[$key] = Utils::internalize($value);
  	  	/*if($firstKeyAppearance){
  	  	$firstKeyAppearance = false;
  	  	$array['_first']=$array[$key];
  	  	}*/
  	  }else{
  	  	if(isset($value['uri']) && $value['uri'] == 1){
  	  	  $value['value'] = preg_replace("|^".$conf['ns']['local']."|", $conf['basedir'], $value['value']);
  	  	  $value['curie'] = Utils::uri2curie($value['value']);
  	  	  $array[$key] = $value;
  	  	}  	  	  	  	
  	  } 
  	}
  	return $array;
  }
  
  public static function getFirsts($array){
  	global $conf;
  	$firstKeyAppearance = true;
  	foreach($array as $key => $value){
  	  if(!isset($value['value'])){
  	  	$aux = Utils::getFirsts($value);
  	  	if(isset($aux['0'])){
  	  	  $array[$key] = $aux['0'];
  	  	}else{
  	  	  $array[$key] = $aux;
  	  	}
  	  } 
  	}
  	return $array;
  }
  
  
  public static function showView($baseData, $data, $view){
  	global $conf;
  	$base = $conf['view']['standard'];
  	$base = $baseData;
  	if(isset($baseData['params'])){
  	  $base['this']['params'] = $baseData['params'];
  	}
  	require_once('lib/Haanga/lib/Haanga.php');
  	Haanga::configure(array(
  	  'template_dir' => $base['view']['directory'],
  	  'cache_dir' => $conf['home'].'cache/',
  	  ));
  	$r = $data;
  	$first = $base['first'];
  	unset($base['first']);
  	$vars = compact('base', 'r', 'first');
 	if($conf['debug']){
 	  var_dump($vars); 	
 	}
	if(is_file($base['view']['directory'].$view)){
	  Haanga::Load($view, $vars);
	}else{
	  $fnc = Haanga::compile($view);
	  $fnc($vars, FALSE);
	}
  	
  }
  
  public static function getModelandView($t, $extension){  	
  	global $conf;
  	//Defining default views and models
  	$curieType="";
  	$modelFile = $conf['model']['default'].$conf['model']['extension'].".".$extension;
  	$viewFile = $conf['view']['default'].$conf['view']['extension'].".".$extension;
  	
  	//Get the first class available
  	/* TODO: Allow user to priotize 
  	* which class should be used
  	* Example: URI is foaf:Person and ex:Student
  	*          If both, prefer ex:Student
  	*/
  	$typesAndValues = array();
  	foreach($t as $v){
  	  $curie = Utils::uri2curie($v);
  	  $typesAndValues[$curie] = 0;
  	  if(isset($conf['types']['priorities'][$curie]) && $conf['types']['priorities'][$curie] >= 0){
  	  	$typesAndValues[$curie] = $conf['types']['priorities'][$curie];
  	  }
  	}
  	arsort($typesAndValues);
  	foreach($typesAndValues as $v => $w){
  	  $auxViewFile  = $conf['view']['directory'].$v.$conf['view']['extension'].".".$extension;
  	  $auxModelFile = $conf['model']['directory'].$v.$conf['model']['extension'].".".$extension;
  	  if(file_exists($auxModelFile) && file_exists($auxViewFile) && $v != null){
  	  	$viewFile = $v.$conf['view']['extension'].".".$extension;
  	  	$modelFile = $v.$conf['model']['extension'].".".$extension;
  	  	break;
  	  }
  	}
  	return array($modelFile, $viewFile);
  }
  
}

?>
