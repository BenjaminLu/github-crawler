<?php

if (!file_exists('urls')) {
    mkdir('urls', 0755, true);
}

if (!file_exists('resource')) {
    mkdir('resource', 0755, true);
}

fetchPopularRepositories(3000);

$filepath = "urls/out.txt";
readFileAndFetchEachRepo($filepath, "repo_","_src");

function readFileAndFetchEachRepo($filename = '', $filePrefix, $fileSuffix)
{
	$source_file = fopen( $filename, "r" ) or die("Couldn't open $filename");

   	$file = fopen($filename, "r");
   	$i = 1;
	while (!feof($file)) {
		$targetURL = fgets($file);
		$targetURL = str_replace("\r\n",'', $targetURL);

		if($targetURL == "\r\n" || $targetURL == "\n" || $targetURL == "" || $targetURL == null) {
			echo "Job finished";
			exit(1);
		}

		$pureRepoName = getRepoName($targetURL);

		printf("%-75s", "Fetching " . $targetURL . " ... ");
		$html = get_web_page($targetURL);
		$outFile = "resource/" . "repo_".  $pureRepoName  . ".txt";
		$fh = fopen($outFile, 'a+'); 
		fwrite($fh,$html['content']);
		fclose($fh);
		printf("%-5s", "done" . "\r\n");
		$i++;
	}

	fclose($file);
}

function fetchPopularRepositories($starNumber) 
{
	for ($i = 60; $i <= 100; $i++) { 
		$url = "https://github.com/search";
		$params = array(
		  "q"   	=>	"stars:>" + $starNumber,
		  "utf8"	=>	"%E2%9C%93",
		  "type"	=>	"Repositories",
		  "ref" 	=>	"searchresults",
		  "p"		=>  $i
		);

		$url .= '?' . http_build_query($params);

		echo "Fetching All Repositories which have over ".$starNumber." stars\r\n";
		$header = get_web_page($url);

		if(isset($header['errno'])) {
			echo "Error :" . $header['errno'] . "\r\n";
		}
		//echo $header['content'] . "\n" . "Page" . $i;
		preg_match_all("/<h3 class=\"repo-list-name\">\s*<a href=\"\/(.*)\">(.*)<\/a>/U", $header['content'], $output);
		
		$matches = $output[0];
		for ($j=0; $j < count($matches); $j++) { 

			if(!isset($matches[$j])) {
				exit(0);
			}

			$pureURL = getPureURL($matches[$j]);
			$aURL = "https://github.com". $pureURL . "\r\n";
			echo $aURL;
			$outFile = "urls/out.txt";
			$fh = fopen($outFile, 'a+'); 
			fwrite($fh,$aURL);
			fclose($fh);

		}

		sleep(30);
	}
}

function getRepoName($targetURL)
{
	$targetURL = substr($targetURL, 18);
	$targetURL = str_replace("//","_",$targetURL);
	$targetURL = str_replace("/","_",$targetURL);
	return $targetURL;
}

function getPureURL($htmlSnippet)
{
	$htmlSnippet = strstr($htmlSnippet, 'href="');
	$htmlSnippet = strstr($htmlSnippet, '">', true);
	$htmlSnippet = strstr($htmlSnippet, '/');
	return $htmlSnippet;
}

function get_web_page( $url )
{
    $options = array(
        CURLOPT_RETURNTRANSFER => true,    
        CURLOPT_HEADER         => false,   
        CURLOPT_FOLLOWLOCATION => true,    
        CURLOPT_ENCODING       => "",      
        CURLOPT_USERAGENT      => "spider",
        CURLOPT_AUTOREFERER    => true,    
        CURLOPT_CONNECTTIMEOUT => 120,     
        CURLOPT_TIMEOUT        => 120,     
        CURLOPT_MAXREDIRS      => 10,      
        CURLOPT_SSL_VERIFYPEER => false    
    );

    $ch      = curl_init( $url );
    curl_setopt_array( $ch, $options );
    $content = curl_exec( $ch );
    $err     = curl_errno( $ch );
    $errmsg  = curl_error( $ch );
    $header  = curl_getinfo( $ch );
    curl_close( $ch );

    $header['errno']   = $err;
    $header['errmsg']  = $errmsg;
    $header['content'] = $content;
    return $header;
}
