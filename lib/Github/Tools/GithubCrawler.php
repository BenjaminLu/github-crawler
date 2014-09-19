<?php
namespace Github\Tools;
class GithubCrawler
{
    private $urlsDir = "urls";
    private $resDir = "resource";
    private $outputFilePath;
    public function __construct($outputFilePath)
    {
        $this->outputFilePath = $outputFilePath;
    }
    
    public function crawlPopularRepoWithStar($starNumber)
    {
        if (!file_exists($this->urlsDir)) {
            mkdir($this->urlsDir, 0755, true);
        }
        
        if (!file_exists($this->resDir)) {
            mkdir($this->resDir, 0755, true);
        }
        
        $this->fetchPopularRepositories($starNumber);
    }
    
    public function crawEachRepoHtml()
    {
        $dirName  = $this->urlsDir;
        $outputFile  = $this->outputFilePath;
        $filename = $dirName."/".$outputFile;
        $this->readFileAndFetchEachRepo($filename, "repo_", "_src");
    }
    
    private function readFileAndFetchEachRepo($filename = '', $filePrefix, $fileSuffix)
    {
        
        $file = fopen($filename, "r");
        $i    = 1;
        while (!feof($file)) {
            $targetURL = fgets($file);
            $targetURL = str_replace("\r\n", '', $targetURL);
            
            if ($targetURL == "\r\n" || $targetURL == "\n" || $targetURL == "" || $targetURL == null) {
                echo "Job finished";
                exit(1);
            }
            
            $pureRepoName = $this->getRepoName($targetURL);
            
            printf("%-75s", "Fetching " . $targetURL . " ... ");
            $html    = $this->get_web_page($targetURL);
            $outFile = "resource/" . $filePrefix . $i . "_" . $pureRepoName . $fileSuffix . ".txt";
            $fh      = fopen($outFile, 'a+');
            fwrite($fh, $html['content']);
            fclose($fh);
            printf("%-5s", "done" . "\r\n");
            $i++;
        }
        
        fclose($file);
    }
    
    private function fetchPopularRepositories($starNumber)
    {
        echo "Fetching All Repositories which have over " . $starNumber . " stars\r\n";
        for ($i = 1; $i <= 61; $i++) {
            $url    = "https://github.com/search";
            $params = array(
                "q" => "stars:>" . $starNumber,
                "utf8" => "%E2%9C%93",
                "type" => "Repositories",
                "ref" => "searchresults",
                "p" => $i
            );
            
            $url .= '?' . http_build_query($params);
            
            $header = $this->get_web_page($url);
            preg_match_all("/<h3 class=\"repo-list-name\">\s*<a href=\"\/(.*)\">(.*)<\/a>/U", $header['content'], $output);
            
            $matches = $output[0];
            for ($j = 0; $j < count($matches); $j++) {
                $pureURL = $this->getPureURL($matches[$j]);
                $aURL    = "https://github.com" . $pureURL . "\r\n";
                echo $aURL;
                $outFile = $this->urlsDir . "/" . $this->outputFilePath;
                $fh      = fopen($outFile, 'a+');
                fwrite($fh, $aURL);
                fclose($fh);
            }
            
            sleep(30);
        }
    }
    
    private function getRepoName($targetURL)
    {
        $targetURL = substr($targetURL, 18);
        $targetURL = str_replace("//", "_", $targetURL);
        $targetURL = str_replace("/", "_", $targetURL);
        return $targetURL;
    }
    
    private function getPureURL($htmlSnippet)
    {
        $htmlSnippet = strstr($htmlSnippet, 'href="');
        $htmlSnippet = strstr($htmlSnippet, '">', true);
        $htmlSnippet = strstr($htmlSnippet, '/');
        return $htmlSnippet;
    }
    
    private function get_web_page($url)
    {
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING => "",
            CURLOPT_USERAGENT => "spider",
            CURLOPT_AUTOREFERER => true,
            CURLOPT_CONNECTTIMEOUT => 120,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYPEER => false
        );
        
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);
        curl_close($ch);
        
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return $header;
    }
    
}