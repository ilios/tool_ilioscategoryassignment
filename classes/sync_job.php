<?php

namespace tool_ilioscategoryassignment;

/**
 * Sync job definition model.
 *
 * @package tool_ilioscategoryassignment
 * @category model
 */
class sync_job {
    /** @var  int $id */
    protected $id;
    /** @var  string $title */
    protected $title;
    /** @var  int $role_id */
    protected $role_id;
    /** @var  int $course_category_id */
    protected $course_category_id;
    /** @var  bool $enabled */
    protected $enabled;
    /** @var  sync_source[] $sources */
    protected $sources;

    /**
     * sync_job constructor.
     *
     * @param $id
     * @param $title
     * @param $role_id
     * @param $course_category_id
     * @param $enabled
     * @param sync_source[] $sources
     */
    public function __construct($id, $title, $role_id, $course_category_id, $enabled, array $sources = array()) {
        $this->id = $id;
        $this->title = $title;
        $this->role_id = $role_id;
        $this->course_category_id = $course_category_id;
        $this->enabled = $enabled;
         $this->sources = $sources;
    }

    /**
     * @return int
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * @return int
     */
    public function get_role_id() {
        return $this->role_id;
    }

    /**
     * @return sync_source[]
     */
    public function get_sources() {
        return $this->sources;
    }

    /**
     * @return int
     */
    public function get_course_category_id() {
        return $this->course_category_id;
    }

    /**
     * @return bool
     */
    public function is_enabled() {
        return $this->enabled;
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get($prop) {
        return $this->$prop;
    }

    /**
     * @param string $prop
     * @return bool
     */
    public function __isset($prop) {
        return isset($this->$prop);
    }
}
