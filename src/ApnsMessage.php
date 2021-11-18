<?php


namespace MingYuanYun\Push;


class ApnsMessage extends \Apns\Message
{
    /**
     * @var array
     */
    private $messageEntity;
    
    public function jsonSerialize()
    {
        return $this->messageEntity;
    }
    
    public function setMessageEntity(array $message)
    {
        $this->messageEntity = $message;
    }

}