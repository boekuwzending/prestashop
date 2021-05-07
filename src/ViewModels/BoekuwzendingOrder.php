<?php


namespace Boekuwzending\PrestaShop\ViewModels;


use DateTime;

class BoekuwzendingOrder
{

    /**
     * @var DateTime
     */
    private $created;

    /**\
     * @var string
     */
    private $boekuwzendingId;

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @return string
     */
    public function getLocalCreated(): string
    {
        // Use shop's zone
        $timezone = new \DateTimeZone(\Configuration::get('PS_TIMEZONE'));

        $created = $this->created;
        $created->setTimezone($timezone);

        return $created->format("Y-m-d H:i:s");
    }

    /**
     * @param DateTime $created
     */
    public function setCreated(DateTime $created): void
    {
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function getBoekuwzendingId(): string
    {
        return $this->boekuwzendingId;
    }

    /**
     * @param string $boekuwzendingId
     */
    public function setBoekuwzendingId(string $boekuwzendingId): void
    {
        $this->boekuwzendingId = $boekuwzendingId;
    }
}