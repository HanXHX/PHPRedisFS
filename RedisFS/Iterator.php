<?php

namespace RedisFS;

class Iterator implements \Iterator
{
    private $keys;
    private $position = 0;
    private $db;

    /**
     *
     * @param array $keys
     */
    public function __construct(array $keys, \Redis $db)
    {
        $this->keys = $keys;
        $this->db   = $db;
    }

    /**
     * @return \RedisFS\File
     */
    public function current()
    {
        $file = new \RedisFS\File($this->db);
        $file->fromArray(
            $this->db->hGetAll(
                $this->keys[$this->position]
            )
        );

        return $file;
    }

    /**
     * @return int position
     */
    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * return bool
     */
    public function valid()
    {
        return isset($this->keys[$this->position]);
    }
}
