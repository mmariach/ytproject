<?php
// src/AppBundle/Controller/YoutubeController.php
namespace AppBundle\Controller;

use Google_Client;
use Google_Service_YouTube;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\Response;

class YoutubeController extends Controller
{

public $apiKey = "AIzaSyAYi9lztCNu729UKlVn6PX395fTPqGM0pM"; //Insert your individual Google API-Key
public $maxResults = 8; //Can be changed...

    /**
     * @return Google_Client
     */
    public function getClient() {
        //Check for an existing API-Key
        if (empty($this->apiKey)) {
            $gapi ="https://developers.google.com/maps/documentation/javascript/get-api-key?hl=de";
            echo "<h3>You have to define your individual API-Key first!</h3>";
            echo "<a href=$gapi>Get Google API Key</a>";
            exit;
        }
        if (empty($this->maxResults)) {
            echo "<h3>You have to define the maximum number of results!</h3>";
            exit;
        }
        if ($this->maxResults > 100) {
            echo "<h3>Searching for more than 100 videos might cause performance problems!</h3>";
            exit;
        }

        $client = new Google_Client();
        $client->setApplicationName("Youtube_Channel_Search");
        $client->setDeveloperKey($this->apiKey);

        return $client;
    }

    /**
     * @Route("/mad/youtube", name="_youtube")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function youtubeAction(Request $request) {

        $channelId = "";
        $searchArg = "";
        $searchResultCount = -1;
        $titles = array();
        $descriptions = array();
        $videoIds = array();
        $thumbnails = array();
        $videoUrl = array();

        $task = new Job();

        $form = $this->createFormBuilder($task)
            ->add('task', TextType::class, array('label' => ''))
            ->add('save', SubmitType::class, array('label' => 'submit'))
            ->getForm();

        $form->handleRequest($request);

        //Form Action
        if ($form->isSubmitted() && $form->isValid()) {

            $searchArg = $form->getData()->getTask();

            $client = $this->getClient();
            $service = new Google_Service_YouTube($client);

            //Search for the channel depending on keyword q
            $searchResponse = $service->search->listSearch('snippet', array(
                'q' => $searchArg,
                'type' => 'channel',
                'maxResults' => 1,
            ));

    /*       //Videos-list (by id, ids, ...)
            $searchResponse = $service->videos->listVideos('snippet,contentDetails,statistics', array(
                'id' => 'Ks-_Mh1QhMc'
            ));

            //Channels-list (by channelId, user, ...)
            $searchResponse = $service->channels->listChannels( 'snippet,contentDetails,statistics', array(
                'id' => 'UCaBf1a-dpIsw8OxqH4ki2Kg',
                'maxResults' => 12
            ));
    */
            if(count($searchResponse)>0) {
                //Search for the channelId of the 1st SearchResult
                //which is by default sorted by Relevance
                foreach ($searchResponse as $searchResult) {
                    if ($searchResult['snippet']['channelId']) {
                        $channelId = $searchResult['snippet']['channelId'];
                        break;
                    }
                }

                //new Search parameters
                $searchResponse = $service->search->listSearch('snippet', array(
                    'type' => 'video',
                    'maxResults' => $this->maxResults,
                    'channelId' => $channelId,
                    'order' => 'date'
                ));
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
                            $descriptions[$i] = substr($descriptions[$i], 0, 80);
                            $descriptions[$i] .= '...';
                        }
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
            'search_arg' => $searchArg,
            'max_results' => $this->maxResults,
            'search_result_count' => $searchResultCount

        ));
    }


}


