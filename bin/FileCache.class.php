<?php
class FileCache{
	
    const FILE_LIFE_KEY = 'FILE_LIFE_KEY';
     
    const CLEAR_ALL_KEY = 'CLEAR_ALL';
     
    static $_instance = null;
	
    protected $_options = array(
        'cache_dir' => '/web/api/cache',
        'file_locking' => true,
        'file_name_prefix' => 'cache',
        'cache_file_umask' => 0777,
        'file_life'  => 100000
    );

    static function &getInstance($options = array())
    {
        if(self::$_instance === null)
        {	
            self::$_instance = new self($options);
        }  
        return self::$_instance;
    }
    static function &setOptions($options = array())
    {
            return self::getInstance($options);
    }
    private function __construct($options = array())
    {    
        if ($this->_options['cache_dir'] !== null) {
             
            $dir = rtrim($this->_options['cache_dir'],'/') . '/';
            $this->_options['cache_dir'] = $dir;
             
            if (!is_dir($this->_options['cache_dir'])) {
                mkdir($this->_options['cache_dir'],0777,TRUE);
            }
            if (!is_writable($this->_options['cache_dir'])) {
                exit('file_cache: 路径 "'. $this->_options['cache_dir'] .'" 不可写');
            }
          
        } else {
           exit('file_cache: "options" cache_dir 不能为空 ');
        }
    }
    static function setCacheDir($value)
    {
        $self = & self::getInstance();
		 if (!is_dir($value)) {
              mkdir($value,0777,TRUE);
         }
		 
        if (!is_dir($value)) {
            exit('file_cache: ' . $value.' 不是一个有效路径 ');
        }
        if (!is_writable($value)) {
            exit('file_cache: 路径 "'.$value.'" 不可写');
        }
     
        $value = rtrim($value,'/') . '/';
         
        $self->_options['cache_dir'] = $value;
    }
    static function save($data, $id = null, $cache_life = 3600)
    {
		//return false;//停掉cache
		
		if(!is_array($data)){
			$data=array();
		}
		
		$id = $_SERVER['HTTP_HOST'].$id;
		self::setCacheDir(WWW_ROOT."/cache/".substr(md5($id),0,2));
		$id = md5($id);
        $self = & self::getInstance();
        if (!$id) {
            if ($self->_id) {
                $id = $self->_id;
            } else {
                exit('file_cache:save() id 不能为空!');
            }
        }
        $time = time();
         
        if($cache_life) {
            $data[self::FILE_LIFE_KEY] = $time + $cache_life;
        }
        elseif
        ($cache_life != 0){
            $data[self::FILE_LIFE_KEY] = $time + $self->_options['file_life'];
        }
         
        $file = $self->_file($id);
        $data = "<?php\n".
                " // mktime: ". $time. "\n".
                " return ".
                var_export($data, true).
                "\n?>"
                ;
         
        $res = $self->_filePutContents($file, $data);
        return $res;
    }
    static function load($id)
    {
		$id = $_SERVER['HTTP_HOST'].$id;
		//return false;//停掉cache
		self::setCacheDir(WWW_ROOT."/cache/".substr(md5($id),0,2));
		$id = md5($id);
        $self = & self::getInstance();
        $time = time();
        if (!$self->test($id)) {
            return false;
        }
        $clearFile = $self->_file(self::CLEAR_ALL_KEY);
        $file = $self->_file($id);
        if(is_file($clearFile) && filemtime($clearFile) > filemtime($file))
        {
            return false;
        }
        
        $data = $self->_fileGetContents($file);
        $tmpData=null;
        if(isset($data[self::FILE_LIFE_KEY]))
            $tmpData=$data[self::FILE_LIFE_KEY];
        if(empty($tmpData) || $time < $tmpData) {
			
			if($tmpData) unset($data[self::FILE_LIFE_KEY]);

            return $data;          
        }
		
		
		
        return false;
    }   
    protected function _filePutContents($file, $string)
    {
		/*
        $self = & self::getInstance();
        $result = false;
        $f = @fopen($file, 'ab+');
        if ($f) {
            if ($self->_options['file_locking']) @flock($f, LOCK_EX);
            fseek($f, 0);
            ftruncate($f, 0);
            $tmp = @fwrite($f, $string);
            if (!($tmp === false)) {
                $result = true;
            }
            @fclose($f);
        }
        @chmod($file, $self->_options['cache_file_umask']);
        return $result;
       */

        $self = & self::getInstance();
        $result = false;
        $f = @fopen($file, 'wb+');
        if ($f) {
			if(flock($f , LOCK_SH | LOCK_NB))//使用LOCK_NB跳过阻塞,否则会形成请求队列
			{
				if ($self->_options['file_locking'])
				{
					if (flock($f, LOCK_EX))
					{
						//fseek($f, 0);
						//ftruncate($f, 0);
						$tmp = @fwrite($f, $string);
						@flock($f,LOCK_UN);
						if (!($tmp === false)) {
							$result = true;
						}
					}
				}
			}
            @fclose($f);
        }
        @chmod($file, $self->_options['cache_file_umask']);
        return $result;

    }
    protected function _file($id)
    {
        $self = & self::getInstance();
        $fileName = $self->_idToFileName($id);
        return $self->_options['cache_dir'] . $fileName;
    }   
    protected function _idToFileName($id)
    {
        $self = & self::getInstance();
        $self->_id = $id;
        $prefix = $self->_options['file_name_prefix'];
        $result = $prefix . '---' . $id;
        return $result;
    }  
    static function test($id)
    {
        $self = & self::getInstance();
        $file = $self->_file($id);
         
        if (!is_file($file)) {
            return false;
        }
         
        return true;
    }
    protected function _fileGetContents($file)
    {
        if (!is_file($file)) {
            return false;
        }
        return include $file;
    }    
    static function clear()
    {
        $self = & self::getInstance();
        $self->save('CLEAR_ALL',self::CLEAR_ALL_KEY);   
    }  
     
    static function del($id)
    {
		self::setCacheDir(WWW_ROOT."/cache/".substr(md5($id),0,2));
		
        $self = & self::getInstance();
        if(!$self->test($id)){
            // 该缓存不存在
            return false;
        }
        $file = $self->_file($id);
        return unlink($file);
    }  
}