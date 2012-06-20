<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class ThematicAdvance extends \CourseEntity
{
    /**
     * @return \Entity\Repository\ThematicAdvanceRepository
     */
     public static function repository(){
        return \Entity\Repository\ThematicAdvanceRepository::instance();
    }

    /**
     * @return \Entity\ThematicAdvance
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $thematic_id
     */
    protected $thematic_id;

    /**
     * @var integer $attendance_id
     */
    protected $attendance_id;

    /**
     * @var text $content
     */
    protected $content;

    /**
     * @var datetime $start_date
     */
    protected $start_date;

    /**
     * @var integer $duration
     */
    protected $duration;

    /**
     * @var boolean $done_advance
     */
    protected $done_advance;


    /**
     * Set c_id
     *
     * @param integer $value
     * @return ThematicAdvance
     */
    public function set_c_id($value)
    {
        $this->c_id = $value;
        return $this;
    }

    /**
     * Get c_id
     *
     * @return integer 
     */
    public function get_c_id()
    {
        return $this->c_id;
    }

    /**
     * Set id
     *
     * @param integer $value
     * @return ThematicAdvance
     */
    public function set_id($value)
    {
        $this->id = $value;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function get_id()
    {
        return $this->id;
    }

    /**
     * Set thematic_id
     *
     * @param integer $value
     * @return ThematicAdvance
     */
    public function set_thematic_id($value)
    {
        $this->thematic_id = $value;
        return $this;
    }

    /**
     * Get thematic_id
     *
     * @return integer 
     */
    public function get_thematic_id()
    {
        return $this->thematic_id;
    }

    /**
     * Set attendance_id
     *
     * @param integer $value
     * @return ThematicAdvance
     */
    public function set_attendance_id($value)
    {
        $this->attendance_id = $value;
        return $this;
    }

    /**
     * Get attendance_id
     *
     * @return integer 
     */
    public function get_attendance_id()
    {
        return $this->attendance_id;
    }

    /**
     * Set content
     *
     * @param text $value
     * @return ThematicAdvance
     */
    public function set_content($value)
    {
        $this->content = $value;
        return $this;
    }

    /**
     * Get content
     *
     * @return text 
     */
    public function get_content()
    {
        return $this->content;
    }

    /**
     * Set start_date
     *
     * @param datetime $value
     * @return ThematicAdvance
     */
    public function set_start_date($value)
    {
        $this->start_date = $value;
        return $this;
    }

    /**
     * Get start_date
     *
     * @return datetime 
     */
    public function get_start_date()
    {
        return $this->start_date;
    }

    /**
     * Set duration
     *
     * @param integer $value
     * @return ThematicAdvance
     */
    public function set_duration($value)
    {
        $this->duration = $value;
        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function get_duration()
    {
        return $this->duration;
    }

    /**
     * Set done_advance
     *
     * @param boolean $value
     * @return ThematicAdvance
     */
    public function set_done_advance($value)
    {
        $this->done_advance = $value;
        return $this;
    }

    /**
     * Get done_advance
     *
     * @return boolean 
     */
    public function get_done_advance()
    {
        return $this->done_advance;
    }
}