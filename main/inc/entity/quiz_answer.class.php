<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class QuizAnswer extends \CourseEntity
{
    /**
     * @return \Entity\Repository\QuizAnswerRepository
     */
     public static function repository(){
        return \Entity\Repository\QuizAnswerRepository::instance();
    }

    /**
     * @return \Entity\QuizAnswer
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $id_auto
     */
    protected $id_auto;

    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var integer $question_id
     */
    protected $question_id;

    /**
     * @var text $answer
     */
    protected $answer;

    /**
     * @var integer $correct
     */
    protected $correct;

    /**
     * @var text $comment
     */
    protected $comment;

    /**
     * @var float $ponderation
     */
    protected $ponderation;

    /**
     * @var integer $position
     */
    protected $position;

    /**
     * @var text $hotspot_coordinates
     */
    protected $hotspot_coordinates;

    /**
     * @var string $hotspot_type
     */
    protected $hotspot_type;

    /**
     * @var text $destination
     */
    protected $destination;

    /**
     * @var string $answer_code
     */
    protected $answer_code;


    /**
     * Set c_id
     *
     * @param integer $value
     * @return QuizAnswer
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
     * Set id_auto
     *
     * @param integer $value
     * @return QuizAnswer
     */
    public function set_id_auto($value)
    {
        $this->id_auto = $value;
        return $this;
    }

    /**
     * Get id_auto
     *
     * @return integer 
     */
    public function get_id_auto()
    {
        return $this->id_auto;
    }

    /**
     * Set id
     *
     * @param integer $value
     * @return QuizAnswer
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
     * Set question_id
     *
     * @param integer $value
     * @return QuizAnswer
     */
    public function set_question_id($value)
    {
        $this->question_id = $value;
        return $this;
    }

    /**
     * Get question_id
     *
     * @return integer 
     */
    public function get_question_id()
    {
        return $this->question_id;
    }

    /**
     * Set answer
     *
     * @param text $value
     * @return QuizAnswer
     */
    public function set_answer($value)
    {
        $this->answer = $value;
        return $this;
    }

    /**
     * Get answer
     *
     * @return text 
     */
    public function get_answer()
    {
        return $this->answer;
    }

    /**
     * Set correct
     *
     * @param integer $value
     * @return QuizAnswer
     */
    public function set_correct($value)
    {
        $this->correct = $value;
        return $this;
    }

    /**
     * Get correct
     *
     * @return integer 
     */
    public function get_correct()
    {
        return $this->correct;
    }

    /**
     * Set comment
     *
     * @param text $value
     * @return QuizAnswer
     */
    public function set_comment($value)
    {
        $this->comment = $value;
        return $this;
    }

    /**
     * Get comment
     *
     * @return text 
     */
    public function get_comment()
    {
        return $this->comment;
    }

    /**
     * Set ponderation
     *
     * @param float $value
     * @return QuizAnswer
     */
    public function set_ponderation($value)
    {
        $this->ponderation = $value;
        return $this;
    }

    /**
     * Get ponderation
     *
     * @return float 
     */
    public function get_ponderation()
    {
        return $this->ponderation;
    }

    /**
     * Set position
     *
     * @param integer $value
     * @return QuizAnswer
     */
    public function set_position($value)
    {
        $this->position = $value;
        return $this;
    }

    /**
     * Get position
     *
     * @return integer 
     */
    public function get_position()
    {
        return $this->position;
    }

    /**
     * Set hotspot_coordinates
     *
     * @param text $value
     * @return QuizAnswer
     */
    public function set_hotspot_coordinates($value)
    {
        $this->hotspot_coordinates = $value;
        return $this;
    }

    /**
     * Get hotspot_coordinates
     *
     * @return text 
     */
    public function get_hotspot_coordinates()
    {
        return $this->hotspot_coordinates;
    }

    /**
     * Set hotspot_type
     *
     * @param string $value
     * @return QuizAnswer
     */
    public function set_hotspot_type($value)
    {
        $this->hotspot_type = $value;
        return $this;
    }

    /**
     * Get hotspot_type
     *
     * @return string 
     */
    public function get_hotspot_type()
    {
        return $this->hotspot_type;
    }

    /**
     * Set destination
     *
     * @param text $value
     * @return QuizAnswer
     */
    public function set_destination($value)
    {
        $this->destination = $value;
        return $this;
    }

    /**
     * Get destination
     *
     * @return text 
     */
    public function get_destination()
    {
        return $this->destination;
    }

    /**
     * Set answer_code
     *
     * @param string $value
     * @return QuizAnswer
     */
    public function set_answer_code($value)
    {
        $this->answer_code = $value;
        return $this;
    }

    /**
     * Get answer_code
     *
     * @return string 
     */
    public function get_answer_code()
    {
        return $this->answer_code;
    }
}