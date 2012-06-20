<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class TrackEUploads extends \Entity
{
    /**
     * @return \Entity\Repository\TrackEUploadsRepository
     */
     public static function repository(){
        return \Entity\Repository\TrackEUploadsRepository::instance();
    }

    /**
     * @return \Entity\TrackEUploads
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $upload_id
     */
    protected $upload_id;

    /**
     * @var integer $upload_user_id
     */
    protected $upload_user_id;

    /**
     * @var datetime $upload_date
     */
    protected $upload_date;

    /**
     * @var string $upload_cours_id
     */
    protected $upload_cours_id;

    /**
     * @var integer $upload_work_id
     */
    protected $upload_work_id;

    /**
     * @var integer $upload_session_id
     */
    protected $upload_session_id;


    /**
     * Get upload_id
     *
     * @return integer 
     */
    public function get_upload_id()
    {
        return $this->upload_id;
    }

    /**
     * Set upload_user_id
     *
     * @param integer $value
     * @return TrackEUploads
     */
    public function set_upload_user_id($value)
    {
        $this->upload_user_id = $value;
        return $this;
    }

    /**
     * Get upload_user_id
     *
     * @return integer 
     */
    public function get_upload_user_id()
    {
        return $this->upload_user_id;
    }

    /**
     * Set upload_date
     *
     * @param datetime $value
     * @return TrackEUploads
     */
    public function set_upload_date($value)
    {
        $this->upload_date = $value;
        return $this;
    }

    /**
     * Get upload_date
     *
     * @return datetime 
     */
    public function get_upload_date()
    {
        return $this->upload_date;
    }

    /**
     * Set upload_cours_id
     *
     * @param string $value
     * @return TrackEUploads
     */
    public function set_upload_cours_id($value)
    {
        $this->upload_cours_id = $value;
        return $this;
    }

    /**
     * Get upload_cours_id
     *
     * @return string 
     */
    public function get_upload_cours_id()
    {
        return $this->upload_cours_id;
    }

    /**
     * Set upload_work_id
     *
     * @param integer $value
     * @return TrackEUploads
     */
    public function set_upload_work_id($value)
    {
        $this->upload_work_id = $value;
        return $this;
    }

    /**
     * Get upload_work_id
     *
     * @return integer 
     */
    public function get_upload_work_id()
    {
        return $this->upload_work_id;
    }

    /**
     * Set upload_session_id
     *
     * @param integer $value
     * @return TrackEUploads
     */
    public function set_upload_session_id($value)
    {
        $this->upload_session_id = $value;
        return $this;
    }

    /**
     * Get upload_session_id
     *
     * @return integer 
     */
    public function get_upload_session_id()
    {
        return $this->upload_session_id;
    }
}