<?php

namespace tool_ilioscategoryassignment;

/**
 * Ilios sync source definition model.
 *
 * @package tool_ilioscategoryassignment
 * @category model
 */
class sync_source {

    /** @var  int $school_id */
    protected $school_id;

    /** @var  int[] $role_ids */
    protected $role_ids;

    /**
     * @param int $school_id
     * @param array $role_ids
     */
    public function __construct($school_id, array $role_ids = array()) {
        $this->school_id = $school_id;
        $this->role_ids = $role_ids;
    }

    /**
     * @return int
     */
    public function get_school_id() {
        return $this->school_id;
    }

    /**
     * @return int[]
     */
    public function get_role_ids() {
        return $this->role_ids;
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
