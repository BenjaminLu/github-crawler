<?php
namespace Github\Tools;
class GithubCrawler
{
    private $urlsDir = "urls";
    private $dataDir = "data";
    private $resDir = "resource";
    private $urlsFilePath;
    private $interestValueStorePath;
    public function __construct($urlsFilePath)
    {
        $this->urlsFilePath = $urlsFilePath;
    }
    
    public function crawlPopularRepoWithStar($starNumber, $doAnalyzing, $interestValueStorePath)
    {
        if (!file_exists($this->urlsDir)) {
            mkdir($this->urlsDir, 0755, true);
        }
        
        if (!file_exists($this->resDir)) {
            mkdir($this->resDir, 0755, true);
        }
        
        if (!file_exists($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
        
        if ($doAnalyzing) {
            $this->interestValueStorePath = $interestValueStorePath;
        }
        
        $this->fetchPopularRepositories($starNumber, $doAnalyzing);
    }
    
    public function crawlEachRepoHtml()
    {
        $filepath = $this->urlsDir . "/" . $this->urlsFilePath;
        $this->readFileAndFetchEachRepo($filepath, "repo_", "_src");
    }
    
    public function crawlEachRepoHtmlByFile($filepath)
    {
        $this->readFileAndFetchEachRepo($filepath, "repo_", "_src");
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
    
    private function fetchPopularRepositories($starNumber, $doAnalyzing)
    {
        echo "Fetching All Repositories which have over " . $starNumber . " stars\r\n";
        
        $waitSecond = 30;
        
        if ($doAnalyzing) {
            $waitSecond = 15;
        }
        
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
                $outFile = $this->urlsDir . "/" . $this->urlsFilePath;
                $fh      = fopen($outFile, 'a+');
                fwrite($fh, $aURL);
                fclose($fh);
                
                if ($doAnalyzing) {
                    $aURL = str_replace("\r\n", '', $aURL);
                    $this->analyzeRepoDetail($aURL);
                }
                
            }
            
            sleep($waitSecond);
        }
    }
    
    public function analyzeRepoDetail($aURL)
    {
        //immediately analyzing html
        $html        = $this->get_web_page($aURL);
        $htmlContent = $html['content'];
        
        $fh = fopen("data/" . $this->interestValueStorePath, 'a+');
        fwrite($fh, $aURL . ", ");
        fclose($fh);
        
        //#Stars
        $matches = $this->findInterestValueByRegex('/js-social-count" href="(.*)">\s*[0-9]*,?[0-9]+\s*<\/a>/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "number");
        
        //#Fork
        $matches = $this->findInterestValueByRegex('/class="social-count">\s*[0-9]*,?[0-9]+\s*<\/a>/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "number");
        
        //#Commit
        $matches = $this->findInterestValueByRegex('/num text-emphasized">\s*[0-9]*,?[0-9]+\s*<\/span>\s*commits/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "number");
        
        //#Branch
        $matches = $this->findInterestValueByRegex('/octicon-git-branch">\s*<\/span>\s*<span class="num text-emphasized">\s*[0-9]*,?[0-9]+\s*<\/span>/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "number");
        
        //#Release
        $matches = $this->findInterestValueByRegex('/num text-emphasized">\s*[0-9]*,?[0-9]+\s*<\/span>\s*releases/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "number");
        
        //#Contributors
        $matches = $this->findInterestValueByRegex('/num text-emphasized">\s*[0-9]*,?[0-9]+\s*<\/span>\s*contributors/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "number");
        
        //#Langs
        $matches = $this->findInterestValueByRegex('/<span class="lang">(.*)<\/span>/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "lang");
        
        //#Percent
        $matches = $this->findInterestValueByRegex('/<span class="percent">(.*)<\/span>/um', $htmlContent);
        $this->echoAndSaveInterestValue($matches, "", ", ", "lang");
        
        $this->repoDetailSaveEnd();
        
    }
    
    private function repoDetailSaveEnd()
    {
        $fh = fopen("data/" . $this->interestValueStorePath, 'a+');
        fwrite($fh, "\r\n");
        fclose($fh);
    }
    
    private function findInterestValueByRegex($regex, $source)
    {
        preg_match_all($regex, $source, $output);
        $matches = $output[0];
        return $matches;
    }
    
    private function echoAndSaveInterestValue($matches, $matchCasePrefix, $matchCaseSuffix, $type)
    {
        for ($j = 0; $j < count($matches); $j++) {
            $matches[$j] = trim($matches[$j]);
            
            if ($type == "number") {
                preg_match_all('/[0-9]*,?[0-9]+/um', $matches[$j], $output);
                $matches[$j] = $output[0][0];
            } else if ($type == "lang") {
                preg_match_all('/>(.*)</um', $matches[$j], $output);
                $matches[$j] = $output[0][0];
                $matches[$j] = substr($matches[$j], 1);
                $matches[$j] = strstr($matches[$j], "<", true);
            } else {
                return;
            }
            
            echo $matchCasePrefix . $matches[$j] . $matchCaseSuffix;
            $fh = fopen("data/" . $this->interestValueStorePath, 'a+');
            fwrite($fh, $matchCasePrefix . $matches[$j] . $matchCaseSuffix);
            fclose($fh);
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