<?php
/*
*	Unit test API profile
*/
	ini_set('max_execution_time', 0);
	require_once 'APIConnector.php';
	
	//Unit test framework
	//TODO do an include for this
	$mtime = microtime(); 
	$mtime = explode(" ",$mtime); 
	$mtime = $mtime[1] + $mtime[0]; 
	$starttime = $mtime; 	
	
	//Unit test Setup - Please input account detials
	$connector = new APIConnector("<uid>","<password>","Unit Test","<profile>","EBSCO DSE",True);
	$guestmode ="n";
	
	$queries = array(
		"Economic",
		"Global warming",
		"Managing pesticides safety risks in high value chains ",
		"Analysis of Individual Level Casino Gambling Behavior"
	);

	$params_config = array(
		'query' => '',
		'searchmode' => 'all',
		'resultsperpage'   => '20',
		'pagenumber' => '1',
		'sort' => 'relevance',
		'highlight'   => 'y',
		'includefacets' => 'y',
		'view'   => 'brief',
		'expander'=>'fulltext'
	);
	
	echo "<h1>Configuration for test suit => <custid></h1>";
	echo "<b><u>Connector</u></b> => wsapi connected via <custid> <br><br>";
	echo "<b><u>Test Cases</u></b><pre>"; print_r($queries);echo "</pre>";
	echo "<b><u>Query Parameters</u></b><pre> "; print_r($params_config);echo "</pre>";
	echo "Is guest mode? => " .$guestmode;
	echo "<br>=========================================";
	
	//Unit test execute
	echo "<h1>Begin executing test suit</h1>";	
	echo "Test suit ran on : ". date(DATE_RFC822)."<br/><br/>";
	
	echo "<br/><h2>Executing test case => UIDAuth()</h2>";
	$connector->requestAuthenticationToken();
	echo "<br/><b>AuthenticationToken generated: <u>".$connector->AuthenticationToken."</u></b><br/>";
	
	echo "<br/><h2>Executing test case => CreateSession()</h2>";
	$connector->requestSessionToken($guestmode);	
	echo "<br/><b>AuthenticationToken generated: <u>".$connector->SessionToken."</u></b><br/>";
	
	//Test Suite results
	foreach($queries as $query){
		echo "<br/><h2>Executing test case => Search for <u>".$query."</u></h2>";
		$params_config["query"] = $query;
		$params = http_build_query($params_config);		
		$xmlResponse = $connector->requestSearch($params, $connector->buildHeader());
		//echo "<pre>";
		//print_r($xmlResponse->SearchResult->Statistics->TotalHits);
		//print_r($xmlResponse->SearchResult->Statistics->TotalSearchTime);
		//echo "</pre>";
		//echo "<br>Total hits : ".$xmlResponse->SearchResult->Statistics->TotalHits." hits";
		//echo "<br>Total search time : ".$xmlResponse->SearchResult->Statistics->TotalSearchTime." ms";
	}
	echo "<br><br>=========================================";
	
	$mtime = microtime(); 
	$mtime = explode(" ",$mtime); 
	$mtime = $mtime[1] + $mtime[0]; 
	$endtime = $mtime; 
	$totaltime = ($endtime - $starttime); 
	echo "<h1>Test suite completed in ".$totaltime." seconds </h1>"; 
?>
	
	