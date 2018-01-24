<?php
// src/AppBundle/Controller/YoutubeController.php
namespace AppBundle\Controller;


use AppBundle\Entity\Task;
use AppBundle\Form\TaskType;
use AppBundle\Services\GoogleClient;
use AppBundle\Services\GoogleYoutubeService;


use DateInterval;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (ytproject)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
class YoutubeController extends Controller
{

public $apiKey = "AIzaSyBomlkhgsA7ghDyRa5oUiw7mOdc6Vra6-8"; //Insert your individual Google API-Key
public $maxResults = 8; //Maximum Videos to search

    /**
     * @return bool
     */
    public function checkConditions() {
        //Check for an existing API-Key
        if (empty($this->apiKey)) {
            $gapi ="https://developers.google.com/maps/documentation/javascript/get-api-key?hl=de";
            echo "<h3>You have to define your individual API-Key first!</h3>";
            echo "<a href=$gapi>Get Google API Key</a>";
            return false;
        }
        if (empty($this->maxResults)) {
            echo "<h3>You have to define the maximum number of results!</h3>";
            return false;
        }
        if (!file_exists(realpath(dirname(__DIR__ ) . '/../..') . '/vendor/autoload.php')) {
            echo "<h3> Please run 'composer require google/apiclient:~2.0' in "  . realpath(dirname(__DIR__ ) . '/../..');
            return false;
        }
        return true;
    }

    /**
     * @Route("/mad/youtube", name="_youtube")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function youtubeAction(Request $request) {

        $searchArg = "";
        $searchResultCount = -1;
        $titles = array();
        $descriptions = array();
        $videoIds = array();
        $thumbnails = array();
        $videoUrl = array();
        $durations = array();

        $task = new Task();
        $task->setTask('Channel');
        $task->setNum($this->maxResults);

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        //Form Action
        if ($form->isSubmitted() && $form->isValid() && $this->checkConditions()) {
            //submitted form values
            $searchArg = $form->getData()->getTask();
            $this->maxResults = $form->getData()->getNum();

            $arguments = array(
                'applName' => 'youtube_channel_search',
                'apiKey' => $this->apiKey
            );

            //get Google_Client and Google_Service_YouTube
            $client = new GoogleClient($arguments);
            $client = $client->getClient();
            $service = new GoogleYoutubeService($client);

            //get ChannelID of Channel
            $channelId = $service->getChannelId($searchArg);

            if($channelId != "") {
                //get (restricted amount of) videos from Channel
                $searchResponse = $service->getChannelVideos($channelId, $this->maxResults);

                if(count($searchResponse)>0) {
                    //Assign arrays values
                    foreach ($searchResponse as $searchResult) {
                        if ($searchResult['id']['videoId']) {
                            $videoIds[] = $searchResult['id']['videoId'];
                        }
                        if ($searchResult['snippet']['title']) {
                            $titles[] = $searchResult['snippet']['title'];
                        }
                        if ($searchResult['snippet']['description']) {
                            $descriptions[] = $searchResult['snippet']['description'];
                        } else { //no description for video
                            $descriptions[] = "";
                        }
                        if ($searchResult['snippet']['thumbnails']) {
                            foreach ($searchResult['snippet']['thumbnails'] as $value => $key) {
                                if ($value == 'medium')
                                    $thumbnails[] = $key['url'];
                            }
                        } else { //no thumbnail for video
                            $thumbnails[] = "";
                        }

                    }

                    //Concat video URL
                    foreach ($videoIds as $item) {
                        $videoUrl[] = "https://www.youtube.com/embed/" . $item . "?controls=1";
                    }

                    //Reduce string length, if necessary
                    for ($i = 0; $i < count($titles); $i++) {
                        if (strlen($titles[$i]) > 20) {
                            $titles[$i] = substr($titles[$i], 0, 20);
                            $titles[$i] .= '...';
                        }
                    }
                    for ($i = 0; $i < count($descriptions); $i++) {
                        if (strlen($descriptions[$i]) > 100) {
                            $descriptions[$i] = substr($descriptions[$i], 0, 75);
                            $descriptions[$i] .= '...';
                        }
                    }

                    //get Duration for each video
                    foreach ($videoIds as $ids) {
                        $duration = new DateInterval($service->getVideoDuration($ids));
                        $durations[] = $duration->format('%H:%I:%S');
                    }
                }
            }
            $searchResultCount = count($videoIds);
        }

        return $this->render('mad/youtube.html.twig', array(
            'form' => $form->createView(),
            'youtube_titles' => $titles,
            'youtube_descriptions' => $descriptions,
            'youtube_thumbnails' => $thumbnails,
            'youtube_video_urls' => $videoUrl,
            'youtube_video_durations' => $durations,
            'search_arg' => $searchArg,
            'max_results' => $this->maxResults,
            'search_result_count' => $searchResultCount

        ));
    }


}


