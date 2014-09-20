<?php
include "autoload.php";
use \Github\Tools\GithubCrawler;

//get crawler and set urls output filename under "urls" directory
$crawler = new GithubCrawler("out.txt");

//crawl each repo on github which got over 3000 stars and do the interest value analyse immediately with store path data.txt which under "data" directory
$crawler->crawlPopularRepoWithStar(3000, true, "data.txt");

//other API usage

//analyse interest value by github repo url
//$crawler->analyzeRepoDetail("https://github.com/twbs/bootstrap");

//crawl and save all repo html source under resource directory by default urls file which contains all urls records. in this case, the file path is urls/out.txt (GithubCrawler constructor parameter)
//$crawler->crawlEachRepoHtml();

//crawl and save all repo html source under resource directory by given file which contains all urls records. in this case, the file path is myurls.txt
//$crawler->crawlEachRepoHtmlByFile("myurls.txt");