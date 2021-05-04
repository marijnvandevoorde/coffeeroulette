<?php

namespace Marijnworks\Zoomroulette\Zoom;

class ZoomMeeting
{
    private string $startMeetingUrl;

    private string $joinMeetingUrl;

    public function __construct(string $startMeetingUrl, string $joinMeetingUrl)
    {
        $this->startMeetingUrl = $startMeetingUrl;
        $this->joinMeetingUrl = $joinMeetingUrl;
    }

    public function getStartMeetingUrl(): string
    {
        return $this->startMeetingUrl;
    }

    public function getJoinMeetingUrl(): string
    {
        return $this->joinMeetingUrl;
    }
}
