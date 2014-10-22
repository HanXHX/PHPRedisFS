<?php



/**
 * Description of File
 *
 * @author hanx
 */
namespace RedisFS;

class File
{
	const CONTENT = 'content';
	const MIME_TYPE = 'mime-type';
	const FILENAME = 'name';
	const CREATED_AT = 'created_at';
	
	protected $content = null;
	protected $file_name = null ;
	protected $created_at = null;
	protected $mime_type = null;

    private $db;

	public function __construct(\Redis $db)
	{
        $this->db = $db;
	}
	
	/**
	 * 
	 * @return binary
	 */
	public function getContent()
	{
		return $this->content;
	}

	/**
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->file_name;
	}

	/**
	 * 
	 * @return int (unix timestamp)
	 */
	public function getCreatedAt()
	{
		return $this->created_at;
	}

	/**
	 * 
	 * @return string
	 */
	public function getMimeType()
	{
		return $this->mime_type;
	}

	/**
	 * 
	 * @param string $content
     * @return \RedisFS\File
	 */
	public function setContent($content)
	{
		$this->content = $content;
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $this->mime_type =  $finfo->buffer($this->content);
		return $this;
	}

	/**
	 * 
	 * @param string $name
     * @return \RedisFS\File
	 */
	public function setFileName($name)
	{
		$this->file_name = $name;
        return $this;
	}


    /**
     *
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        if($this->content === null || $this->mime_type === null || $this->file_name === null) {
            throw new \Exception('Can\'t save: missing data...');
        }

        if($this->created_at === null) {
            $this->created_at = time();
        }

        return $this->db->hMset($this->file_name, $this->toArray());
    }


	/**
	 * 
	 * @return array
	 */
	public function toArray()
	{
		return array(
			self::CONTENT => $this->content,
			self::CREATED_AT => $this->created_at,
			self::FILENAME => $this->file_name,
			self::MIME_TYPE => $this->mime_type
		);
	}

    public function fromArray(array $values)
    {
        $this->content = $values[self::CONTENT];
        $this->created_at = $values[self::CREATED_AT];
        $this->file_name = $values[self::FILENAME];
        $this->mime_type = $values[self::FILENAME];
    }
	
}
