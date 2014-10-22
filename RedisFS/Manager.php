<?php

namespace RedisFS;

class Manager
{
    const DEFAULT_DB_INDEX = 9;

    /**
     *
     * @var \Redis
     */
    protected $db = null;

    /**
     * 
     * @param \Redis $db
     * @param type $prefix Prefix keys in Redis
     * @param type $dbindex Database id in Redis
     */
    public function __construct(\Redis $db, $prefix = 'RedisFS::', $dbindex = self::DEFAULT_DB_INDEX)
    {
        $this->db = $db;
        $this->db->setOption(\Redis::OPT_PREFIX, $prefix);
        $this->db->select($dbindex);
    }

    /**
     * Retourne le nombre de fichier stockés
     * 
     * @return int
     */
    public function countAll()
    {
        $script = sprintf(
            "return #redis.pcall('keys', '%s*')", $this->db->getOption(\Redis::OPT_PREFIX)
        );

        return (int) $this->db->eval($script);
    }

    /**
     *
     * @param string $pattern
     * @return \RedisFS\Iterator
     */
    public function findByName($pattern)
    {
        // We remove the explicit prefix
        $keys = array_map(function($v) {
            return str_replace($this->db->getOption(\Redis::OPT_PREFIX), '', $v);
        }, $this->db->keys($pattern));

        return new \RedisFS\Iterator(
            $keys, $this->db
        );
    }

    /**
     * Return a Redis\File or null if missing
     * @param string $pattern
     * @return null|\RedisFS\File
     */
    public function findOneByName($pattern)
    {
        $data = $this->db->hGetAll($pattern);

        if (count($data) == 0) {
            return null;
        }

        $file = new \RedisFS\File($this->db);
        $file->fromArray($data);
        return $file;
    }

    public function storeBytes($bytes, $keyname)
    {
        $File = new \RedisFS\File($this->db);
        $File->setContent($bytes);
        $File->setFileName($keyname);
        return $File->save();
    }

    /**
     *
     * @param string $filename Chemin du fichier à enregistrer
     * @return bool
     * @throws \Exception
     */
    public function storeFile($filename, $keyname = null)
    {
        if ($keyname === null) {
            $keyname = $filename;
        }

        if (file_exists($filename) && is_readable($filename)) {
            return $this->storeBytes(file_get_contents($filename), $keyname);
        }
        throw new \Exception("Can't read file: ".$filename);
    }


    public function delete($filename)
    {
        return $this->db->del($filename);
    }

    /**
     * 
     * @param type $filename
     * @param type $destination
     * @throws \Exception
     * @return bool file is writed
     */
    public function writeToDisk($filename, $destination, $force = false)
    {
        if (null === $redisFile = $this->findOneByName($filename)) {
            throw new \Exception('File unknow with this pattern: '.$filename);
        }

        if(is_dir($destination)) {
            $destination = sprintf('%s%s%s', $destination, DIRECTORY_SEPARATOR, basename($filename));
        }

        if (!is_writable(dirname($destination))) {
            throw new \Exception('Can\'t write to this destination: '.$destination);
        }

        if(!$force && file_exists($destination)) {
            return false;
        }

        $fh = fopen($destination, 'w+');
        fwrite($fh, $redisFile->getContent());
        fclose($fh);
        return true;
    }
}
