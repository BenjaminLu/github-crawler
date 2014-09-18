<?php 

for ($i=1; $i <= 100; $i++) { 
	$url = "https://github.com/search";
	$params = array(
	  "q"   	=>	"stars:>3000",
	  "utf8"	=>	"%E2%9C%93",
	  "type"	=>	"Repositories",
	  "ref" 	=>	"searchresults",
	  "p"		=>  $i
	);

	$url .= '?' . http_build_query($params);

	$header = get_web_page($url);
	echo $header['content'] . "\n" . "Page" . $i;
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
