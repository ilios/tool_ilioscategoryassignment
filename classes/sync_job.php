<?php

namespace tool_ilioscategoryassignment;

/**
 * Sync job definition model.
 *
 * @package tool_ilioscategoryassignment
 * @category model
 */
class sync_job {
    /** @var int $id */
    protected $id;

    /** @var string $title */
    protected $title;

    /** @var int $role_id */
    protected $roleid;

    /** @var int $coursecategoryid */
    protected $coursecategoryid;

    /** @var bool $enabled */
    protected $enabled;

    /** @var int $school_id */
    protected $schoolid;

    /**
     * Constructor.
     *
     * @param $id
     * @param $title
     * @param $roleid
     * @param $course_category_id
     * @param $enabled
     * @param $schoolid
     */
    public function __construct($id, $title, $roleid, $coursecategoryid, $enabled, $schoolid) {
        $this->id = $id;
        $this->title = $title;
        $this->roleid = $roleid;
        $this->coursecategoryid = $coursecategoryid;
        $this->enabled = $enabled;
        $this->schoolid = $schoolid;
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
        return $this->roleid;
    }

    /**
     * @return int
     */
    public function get_school_id() {
        return $this->schoolid;
    }

    /**
     * @return int
     */
    public function get_course_category_id() {
        return $this->coursecategoryid;
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
