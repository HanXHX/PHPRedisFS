<?php

namespace LibraryTest;


class RedisFS extends \PHPUnit_Framework_TestCase //\UnitTestCase
{
    /**
     * Redis Database
     * @var \Redis
     */
    private $db;
    /**
     * Redis Filesystem manager
     * @var \RedisFS\Manager
     */
    private $manager;

    const REDIS_DB_INDEX = 9;
    const REDIS_KEY_PREFIX = 'PHPUNIT::RedisFS::';

    public function init()
    {
        $this->db = new \Redis();
        $this->db->connect('127.0.0.1');
        $this->db->select(self::REDIS_DB_INDEX);
        $this->db->flushDB();
        
        $this->manager = new \RedisFS\Manager($this->db, self::REDIS_KEY_PREFIX, self::REDIS_DB_INDEX);
    }


    public function testFileSaveGet()
    {
        $this->init();

        $time = time();

        $key = 'lorem';
        $content = 'lorem ipsum';
        $file = new \RedisFS\File($this->db);
        $file->setContent($content);
        $file->setFileName($key);
        $file->save();
       
        $this->assertGreaterThanOrEqual($time, $file->getCreatedAt());
        $this->assertEquals($content, $this->manager->findOneByName($key)->getContent());
    }

    public function testFailFindOne()
    {
        $this->init();
        $this->assertNull($this->manager->findOneByName('barbieaaaaaa'));
    }


    public function testDelete()
    {
        $this->init();
        $key = 'gunther';
        $content = 'touch my tralala';
        $this->manager->storeBytes($content, $key);
        $this->assertEquals(1, $this->manager->delete($key));
        $this->assertEquals(0, $this->manager->delete($key));
    }


    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can't read file: /etc/shadow
     */
    public function testStoreFileException()
    {
        $this->init();
        $this->manager->storeFile('/etc/shadow');

    }

    public function testFind()
    {
        $this->init();

        $gunther = [
            'file1' => '11111111111111',
            'file2' => '22222222222222'
        ];

        foreach($gunther as $key  => $value)
        {
            $this->manager->storeBytes($value, $key);
        }

        $i = 0;
        foreach($this->manager->findByName('file*') as $key => $value)
        {
            $this->assertEquals($gunther[$value->getName()], $value->getContent());
            $i++;
        }

        
    }

    public function testCount()
    {
        $this->init();
        $this->assertEquals(0, $this->manager->countAll());

        $this->manager->storeFile('/etc/passwd');
        $this->assertEquals(1, $this->manager->countAll());

        $this->manager->storeFile('/etc/group');
        $this->assertEquals(2, $this->manager->countAll());
    }

    public function testWriteDisk()
    {
        $this->init();

        $files = [
            '/etc/passwd',
            '/etc/group'
        ];

        $i = 0;
        foreach($files as $file)
        {
            $dest = '/tmp/' . $i . '.phpunit';
            $this->manager->storeFile($file);
            $this->assertTrue($this->manager->writeToDisk($file, $dest));
            $this->assertEquals(md5_file($file), md5_file($dest));
            $this->assertFalse($this->manager->writeToDisk($file, $dest));
            $this->assertTrue($this->manager->writeToDisk($file, $dest, true));

            unlink($dest);

            $dest = '/tmp';
            $expectedFile = $dest . '/' . basename($file);

            $this->assertTrue($this->manager->writeToDisk($file, $dest));
            $this->assertEquals(md5_file($file), md5_file($expectedFile), __LINE__);

            unlink($expectedFile);
        }
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage File unknow with this pattern: /tmp/unknowfile
     */
    public function testWriteDiskException1()
    {
        $this->init();
        $this->manager->writeToDisk('/tmp/unknowfile', '/tmp/dest_unknowfile');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can't write to this destination: /etc/prout
     */
    public function testWriteToDiskException2()
    {
        $this->init();
        $source_file = '/etc/passwd';
        $this->manager->storeFile($source_file);
        $this->manager->writeToDisk($source_file, '/etc/prout');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Can't save: missing data...
     */
    public function testRedisFileSaveException()
    {
        $this->init();
        $redisFile = new \RedisFS\File($this->db);
        $redisFile->save();
    }

    public function testGetMimeType()
    {
        $this->init();
        $redisFile = new \RedisFS\File($this->db);
        $redisFile->setContent(file_get_contents('/bin/true'));
        //$redisFile->
        $this->assertEquals('application/x-executable', $redisFile->getMimeType());
    }

}
