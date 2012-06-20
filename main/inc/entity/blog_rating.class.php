<?php

namespace Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @license see /license.txt
 * @author autogenerated
 */
class BlogRating extends \CourseEntity
{
    /**
     * @return \Entity\Repository\BlogRatingRepository
     */
     public static function repository(){
        return \Entity\Repository\BlogRatingRepository::instance();
    }

    /**
     * @return \Entity\BlogRating
     */
     public static function create(){
        return new self();
    }

    /**
     * @var integer $c_id
     */
    protected $c_id;

    /**
     * @var integer $rating_id
     */
    protected $rating_id;

    /**
     * @var integer $blog_id
     */
    protected $blog_id;

    /**
     * @var string $rating_type
     */
    protected $rating_type;

    /**
     * @var integer $item_id
     */
    protected $item_id;

    /**
     * @var integer $user_id
     */
    protected $user_id;

    /**
     * @var integer $rating
     */
    protected $rating;


    /**
     * Set c_id
     *
     * @param integer $value
     * @return BlogRating
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
     * Set rating_id
     *
     * @param integer $value
     * @return BlogRating
     */
    public function set_rating_id($value)
    {
        $this->rating_id = $value;
        return $this;
    }

    /**
     * Get rating_id
     *
     * @return integer 
     */
    public function get_rating_id()
    {
        return $this->rating_id;
    }

    /**
     * Set blog_id
     *
     * @param integer $value
     * @return BlogRating
     */
    public function set_blog_id($value)
    {
        $this->blog_id = $value;
        return $this;
    }

    /**
     * Get blog_id
     *
     * @return integer 
     */
    public function get_blog_id()
    {
        return $this->blog_id;
    }

    /**
     * Set rating_type
     *
     * @param string $value
     * @return BlogRating
     */
    public function set_rating_type($value)
    {
        $this->rating_type = $value;
        return $this;
    }

    /**
     * Get rating_type
     *
     * @return string 
     */
    public function get_rating_type()
    {
        return $this->rating_type;
    }

    /**
     * Set item_id
     *
     * @param integer $value
     * @return BlogRating
     */
    public function set_item_id($value)
    {
        $this->item_id = $value;
        return $this;
    }

    /**
     * Get item_id
     *
     * @return integer 
     */
    public function get_item_id()
    {
        return $this->item_id;
    }

    /**
     * Set user_id
     *
     * @param integer $value
     * @return BlogRating
     */
    public function set_user_id($value)
    {
        $this->user_id = $value;
        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function get_user_id()
    {
        return $this->user_id;
    }

    /**
     * Set rating
     *
     * @param integer $value
     * @return BlogRating
     */
    public function set_rating($value)
    {
        $this->rating = $value;
        return $this;
    }

    /**
     * Get rating
     *
     * @return integer 
     */
    public function get_rating()
    {
        return $this->rating;
    }
}