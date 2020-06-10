<?php


namespace Teamleader\Zoomroulette\Zoom;


class ZoomMeeting
{

    private string $startMeetingUrl;
    private string $joinMeetingUrl;

    public function __construct(string $startMeetingUrl, string $joinMeetingUrl) {

        $this->startMeetingUrl = $startMeetingUrl;
        $this->joinMeetingUrl = $joinMeetingUrl;
    }

    /**
     * @return string
     */
    public function getStartMeetingUrl(): string
    {
        return $this->startMeetingUrl;
    }

    /**
     * @return string
     */
    public function getJoinMeetingUrl(): string
    {
        return $this->joinMeetingUrl;
    }


}