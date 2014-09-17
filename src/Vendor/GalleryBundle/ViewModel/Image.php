<?php

namespace Vendor\GalleryBundle\ViewModel;


class Image {

    protected $id = 0;

    protected $path = '';

    protected $title = '';

    protected $fileName = '';


    private $created;


    private $updated;


    private $createdBy;

    private $updatedBy;

    protected $thumbnails;

    protected $votes;

    function __construct($id, $path, $votes)
    {
        $this->id = $id;
        $this->path = $path;
        $this->votes = $votes;

    }


    public function getId()
    {
        return $this->id;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getTitle()
    {
        return $this->title;
    }


    public function getCreated()
    {
        return $this->created;
    }


    public function getUpdated()
    {
        return $this->updated;
    }


    public function getFileName()
    {
        return $this->fileName;
    }



    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @return mixed
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @param mixed $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }


} 