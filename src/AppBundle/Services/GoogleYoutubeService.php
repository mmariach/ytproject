<?php
// src/AppBundle/Services/GoogleYoutubeService.php
namespace AppBundle\Services;

use Google_Client;
use Google_Service_YouTube;

class GoogleYoutubeService extends Google_Service_YouTube
{
    /**
     * @var Google_Client client
     */
    public $client;

    /*
     * @var Google_Service_YouTube service
     */
    public $service;

    /**
     * GoogleYoutubeService constructor.
     * @param Google_Client $client
     */
    public function __construct(Google_Client $client)
    {
        $this->client = $client;
        $this->service = parent::__construct($client);
    }

    /**
     * @return Google_Service_YouTube
     */
    public function getService()
    {
        return $this;
    }

    /**
     * @param $searchArg
     * @return string
     */
    public function getChannelId($searchArg)
    {
        $channelId = "";
        $searchResponse = $this->search->listSearch('snippet', array(
            'q' => $searchArg,
            'type' => 'channel',
            'maxResults' => 1,
        ));
        if(empty($searchResponse)) {
            throw new \RuntimeException(sprintf('Could not find Channel %s', $searchArg));
        }
        if(count($searchResponse)==1) {
            $channelId = $searchResponse[0]['snippet']['channelId'];
        }
        return $channelId;
    }

    /**
     * @param $channelId
     * @param $maxResults
     * @return array $searchResponse
     */
    public function getChannelVideos($channelId, $maxResults)
    {
        $searchResponse = $this->search->listSearch('snippet', array(
            'type' => 'video',
            'maxResults' => $maxResults,
            'channelId' => $channelId,
            'order' => 'date'
        ));
        if(empty($searchResponse)) {
            throw new \RuntimeException(sprintf('Could not find Videos in Channel %s', $channelId));
        }
        return $searchResponse;
    }

    /**
     * @param $videoId
     * @return string
     */
    public function  getVideoDuration($videoId)
    {
        $duration = "";
        $searchResponse = $this->videos->listVideos('snippet,contentDetails,statistics', array(
            'id' => $videoId
        ));
        if(empty($searchResponse)) {
            throw new \RuntimeException(sprintf('Could not find Video %s', $videoId));
        }
        if(count($searchResponse)==1) {
            $duration = $searchResponse[0]['contentDetails']['duration'];
        }
        return $duration;
    }

}