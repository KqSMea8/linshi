<?php 
/** php下载类,支持断点续传 
*  Date:  2013-06-30 
*  Author: test 
*  Ver:  1.0 
* 
*  Func: 
*  download: 下载文件 
*  setSpeed: 设置下载速度 
*  getRange: 获取header中Range 
*/
  
class FileDownload{ // class start 
  
  private $_speed = 512;  // 下载速度 
  
  /** 下载 
  * @param String $file  要下载的文件路径 
  * @param String $name  文件名称,为空则与下载的文件名称一样 
  * @param boolean $reload 是否开启断点续传 
  */
  public function download($file, $name='',$file_size=0,$dateline=0, $reload=false){ 
      if($name==''){ 
        $name = basename($file); 
      } 
	  if(!$dateline){
		  $dataline=TIMESTAMP;
	  }
  
     if(!$fp = fopen($file, 'rb')){
		 topshowmessage('文件不存在');
	 }
	 @ob_end_clean();
	 if(getglobal('gzipcompress')) @ob_start('ob_gzhandler');
	  if(!$file_size)   $file_size = filesize($file); 
      $ranges = $this->getRange($file_size); 
  
      header('cache-control:public'); 
	  header('Date: '.gmdate('D, d M Y H:i:s', $dateline).' GMT');
	  header('Last-Modified: '.gmdate('D, d M Y H:i:s', $dateline).' GMT');
      header('content-type:application/octet-stream'); 
      header('content-disposition:attachment; filename='.$name); 
  
      if($reload && $ranges!=null){ // 使用续传 
        header('HTTP/1.1 206 Partial Content'); 
        header('Accept-Ranges:bytes'); 
          
        // 剩余长度 
        header(sprintf('content-length:%u',$ranges['end']-$ranges['start']+1)); 
          
        // range信息 
        header(sprintf('content-range:bytes %s-%s/%s', $ranges['start'], $ranges['end'], $file_size)); 
          
        // fp指针跳到断点位置 
        fseek($fp, sprintf('%u', $ranges['start'])); 
      }else{ 
        header('HTTP/1.1 200 OK'); 
        header('content-length:'.$file_size); 
      } 
  
      while(!feof($fp)){ 
        echo fread($fp, round($this->_speed*1024,0)); 
        @flush(); @ob_flush();
       // usleep(500); // 用于测试,减慢下载速度 
      } 
      ($fp!=null) && fclose($fp); 
  
  } 
  
  /** 设置下载速度 
  * @param int $speed 
  */
  public function setSpeed($speed){ 
    if(is_numeric($speed) && $speed>16 && $speed<4096){ 
      $this->_speed = $speed; 
    } 
  } 
  
  /** 获取header range信息 
  * @param int  $file_size 文件大小 
  * @return Array 
  */
  private function getRange($file_size){ 
  	if (isset($HTTP_SERVER_VARS['HTTP_RANGE'])) $range = substr($HTTP_SERVER_VARS['HTTP_RANGE'] , strlen('bytes='));  
    elseif($_SERVER['HTTP_RANGE']) $range = substr($_SERVER['HTTP_RANGE'] , strlen('bytes='));  
    if($range){  
		$range = explode('-', $range); 
		 if ($range[0] > 0){  
		   $range[0] = intval($range[0]);  
		}  
		if(count($range)<2){  
			$range[1] = $file_size-1;  
		} 
		if ($range[1] > 0) $range[1] = intval($range[1]);  
		else $range[1] = $file_size-1;   
		$range = array_combine(array('start','end'), $range);  
		if(empty($range['start']) || $range['start']<0){  
			$range['start'] = 0;  
		}  
		if(empty($range['end']) || ($range['end']>$file_size-1)){  
			$range['end'] = $file_size-1;  
		}  
		return $range;  
	}  
	return null;
  }
 
} // class end 
  
?> 