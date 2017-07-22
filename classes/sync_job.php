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
    /** @var  int $created_at */
    protected $created_at;
    /** @var  int $updated_at */
    protected $updated_at;
    /** @var  int $modified_by */
    protected $modified_by;
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
     * @param $created_at
     * @param $updated_at
     * @param $modified_by
     * @param sync_source[] $sources
     */
    public function __construct($id, $title, $role_id, $course_category_id, $enabled, $created_at, $updated_at, $modified_by,
            array $sources = array()) {
        $this->id = $id;
        $this->title = $title;
        $this->role_id = $role_id;
        $this->course_category_id = $course_category_id;
        $this->enabled = $enabled;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
        $this->modified_by = $modified_by;
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
    public function get_created_at() {
        return $this->created_at;
    }

    /**
     * @return int
     */
    public function get_updated_at() {
        return $this->created_at;
    }

    /**
     * @return int
     */
    public function get_modified_by() {
        return $this->modified_by;
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
