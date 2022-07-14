<?php

use Goutte\Client;

require 'vendor/autoload.php';

//if (count($argv) > 1) {
    try {
        // set client
        $client = new Client();

// set base variables
        $startUrl = "https://search.ipaustralia.gov.au/trademarks/search/advanced";
//        $searchWord = $argv[1];
        $searchWord = "google";
        $allCount = 0;
        $searchUrls = array();

// get first advanced search page. https://search.ipaustralia.gov.au/trademarks/search/advanced

        $crawler = $client->request('GET', $startUrl);

        $statusCode = $client->getResponse()->getStatusCode();

        if ($statusCode === 200){
            // find search form
            $form = $crawler->filterXPath('//*[@id="basicSearchForm"]')->form();

            // submit form with keyword
            $crawler = $client->submit($form, ['wv[0]' => $searchWord]);

            // get first url
            $firstUrl = $crawler->getUri();

            // find all count for results
            $allCount = $crawler->filter('.qa-count')->each(function ($node) {
                return $node->text();
            });
            // convert all count to int if value is bigger than 2000, set 2000
            $allCount = intval(str_replace(",", "", $allCount[0]));
            if ($allCount > 2000){
                $allCount = 2000;
            }

            // get all views url and result merge.
            $scrapResult = $crawler->filter('tr')->each(function ($node, $key){
                $scrape = array();
                if ($key !== 0){
                    //  get number
                    $numbers =  $node->filter('.number')->each(function ($node) {
                        return $node->text();
                    });
                    $scrape["number"] = $numbers[0];
                    // get logo url
                    $images =  $node->filter('.image')->each(function ($node) {
                        return $node->attr("src");
                    });
                    if (count($images) === 0){
                        $scrape["logo_url"] = "null";
                    }
                    else{
                        $scrape["logo_url"] = $images[0];
                    }
                    // get name
                    $names =  $node->filter('.words')->each(function ($node) {
                        return $node->text();
                    });
                    $scrape["name"] = $names[0];

                    //     get class
                    $classes =  $node->filter('.classes ')->each(function ($node) {
                        return $node->text();
                    });
                    $scrape["classes"] = $classes[0];

                    // get status1, status2
                    $status =  $node->filter('.status')->each(function ($node) {
                        return $node->text();
                    });
                    $statusList = explode(":", $status[0]);
                    if(count($statusList) === 1){
                        $scrape["status1"] = str_replace("● ", "", $statusList[0]);
                        $scrape["status2"] = "";
                    }
                    else{
                        $scrape["status1"] = str_replace("● ", "", $statusList[0]);
                        $scrape["status2"] = $statusList[1];
                    }
                    //            get details page url
                    $detailUrls =  $node->filter('.qa-tm-number')->each(function ($node) {
                        return "https://search.ipaustralia.gov.au".$node->attr("href");
                    });
                    $scrape["details_page_url"] = $detailUrls[0];

                }
                return $scrape;
            });
            array_shift($scrapResult);
            $totalArray = array("totalCount" => $allCount);
            // search all pages
            for ($page = 1; $page < ($allCount / 100); $page ++ ){
                $scrapeUrl = $firstUrl."&p=".$page;
                $crawler = $client->request('GET', $scrapeUrl);
                //            check get response status code
                $statusCode = $client->getResponse()->getStatusCode();
                if ($statusCode === 200){
                    $result = $crawler->filter('tr')->each(function ($node, $key){
                        $scrape = array();
                        if ($key !== 0){
                            //  get number
                            $numbers =  $node->filter('.number')->each(function ($node) {
                                return $node->text();
                            });
                            $scrape["number"] = $numbers[0];
                            // get logo url
                            $images =  $node->filter('.image')->each(function ($node) {
                                return $node->attr("src");
                            });
                            if (count($images) === 0){
                                $scrape["logo_url"] = "null";
                            }
                            else{
                                $scrape["logo_url"] = $images[0];
                            }
                            // get name
                            $names =  $node->filter('.words')->each(function ($node) {
                                return $node->text();
                            });
                            $scrape["name"] = $names[0];

                            //     get class
                            $classes =  $node->filter('.classes ')->each(function ($node) {
                                return $node->text();
                            });
                            $scrape["classes"] = $classes[0];

                            // get status1, status2
                            $status =  $node->filter('.status')->each(function ($node) {
                                return $node->text();
                            });
                            $statusList = explode(":", $status[0]);
                            if(count($statusList) === 1){
                                $scrape["status1"] = str_replace("● ", "", $statusList[0]);
                                $scrape["status2"] = "";
                            }
                            else{
                                $scrape["status1"] = str_replace("● ", "", $statusList[0]);
                                $scrape["status2"] = $statusList[1];
                            }

                            //            get details page url
                            $detailUrls =  $node->filter('.qa-tm-number')->each(function ($node) {
                                return "https://search.ipaustralia.gov.au".$node->attr("href");
                            });
                            $scrape["details_page_url"] = $detailUrls[0];

                        }
                        return $scrape;
                    });
                    array_shift($result);
                    $scrapResult = array_merge($scrapResult, $result);
                }
                else{
                    print_r("please. check network connection.");
                }
            }
            $scrapResult = array_merge($scrapResult, $totalArray);
            print_r($scrapResult);
        }
        else{
            print_r("please. check network connection.");
        }
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }

//
//}
//else{
//    echo "you have to input first parameter";
//}
