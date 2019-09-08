<?php


namespace App;


class GeoPosition
{
    private $accuracy;
    private $latitude;
    private $longitude;

    public function __construct($accuracy, $latitude, $longitude)
    {
        $this->setAccuracy($accuracy);
        $this->setLatitude($latitude);
        $this->setLongitude($longitude);
    }

    /**
     * @return mixed
     */
    public function getAccuracy()
    {
        return $this->accuracy;
    }

    /**
     * @return mixed
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @return mixed
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param mixed $latitude
     */
    public function setLatitude($latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * @param mixed $longitude
     */
    public function setLongitude($longitude): void
    {
        $this->longitude = $longitude;
    }

    /**
     * @param mixed $accuracy
     */
    public function setAccuracy($accuracy): void
    {
        $this->accuracy = $accuracy;
    }

    // Здесь и далее можно разместить методы, касательно позиции пользователя.
}
