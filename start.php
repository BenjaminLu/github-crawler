<?php
include "autoload.php";
use \Github\Tools\GithubCrawler;

$crawler = new GithubCrawler("out.txt");
$crawler->crawlPopularRepoWithStar(3000);
$crawler->crawEachRepoHtml();