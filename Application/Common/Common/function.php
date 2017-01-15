<?php
use Think\Verify;
/**
 * @desc 公用PHP方法
 * @author JSS
 */

/**
 * @desc 170113 => 17:13:49
 * @param $time
 * @return string
 */
function timeFormat($time) {
    $time = str_split($time, 2);
    if ($time) {
        return implode(":", $time);
    }
    return null;
}

/**
 * @desc 170113171349 => 17-01-13 17:13:49
 * @param $date
 * @return string
 */
function datetimeFormat($date) {
    $date = str_split($date, 6);
    if ($date) {
        $d = str_split($date[0], 2);
        $d = implode('-', $d);
        $t = str_split($date[1], 2);
        $t = implode(':', $t);
        return $d . ' ' . $t;
    } else {
        return null;
    }
}

/**
 * @desc 将时间段分解成数组timestamp返回
 * Y/m/d H:i:s - Y/m/d H:i:s => array(timestamp1, timestamp2)
 * @param $date
 * @return array
 */
function rangeDate($date) {
    $date = explode('-', $date);
    foreach ($date as $key=>$value) {
        $date[$key] = strtotime($value);
    }
    return $date;
}

/*---------------------手机6位验证码------------------------*/

/**
 * @param $time 时间化为秒
 * @return int
 */
function convertToSeconds($time) {
    $data = explode(":", $time);
    $seconds = $data[0]*3600 + $data[1] * 60 + $data[2];
    return $seconds;
}

/**
 * @param $seconds 秒化为时间
 * @return string
 */
function convertToTime($seconds) {
    $hour = intval($seconds/3600);
    $seconds -= $hour*3600;
    $minute = intval($seconds / 60);
    $seconds -= $minute*60;
    return str_pad($hour, 2, "0", STR_PAD_LEFT) . ":" .
        str_pad($minute, 2, "0", STR_PAD_LEFT) . ":" .
        str_pad($seconds, 2, "0", STR_PAD_LEFT);
}


/*---------------------过滤不存在的数组元素-------------------*/
/**
 * @param $data
 */
function filter_array_element(&$data) {
	foreach ($data as $key => $value) {
		if ($value === null) {
			unset($data[$key]);
		}
	}
}


/*---------------------前端参数验证--------------------------*/

/**
 * @desc 合并请求错误
 * @param unknown $name
 * @param unknown $message
 * @param unknown $error
 */
function merge_request_error($name, $message, &$error) {
	if (is_array($error) && $name && $message) {
		array_push($error, array($name => $message));
	}
}

/**
 * @param $param
 * @param string $name 参数名称
 * @param string $message 错误消息
 * @param array $error 
 * @param $optional
 */
function merge_params_error(&$param, $name, $message, &$error, $optional=true) {
	if (is_array($error)) {
		if ($param===false) {
			array_push($error, array($name=>$message));
		}
		if (!isset($param)) {
			if ($optional) {
				array_push($error, array($name=>"不存在"));
			}
		}
	}
}

/**
 * @param $var
 * @return bool
 */
function check_empty_string($var) {
	if (!$var) {
		return false;
	} else {
		return $var;
	}
}

/**
 * @desc 检查参数是否为number
 * @param unknown $var
 * @return boolean
 */
function check_numeric($var) {
	if (is_numeric($var)) {
		return $var;
	} else {
		return false;
	}
}

/**
 * @desc 检查是否正整数
 * @param unknown $var
 * @return boolean
 */
function check_positive_integer($var) {
	if (!is_numeric($var)) {
		return false;
	}
	$var = intval($var);
	if ($var>0) {
		return $var;
	} else {
		return false;
	}
}

/**
 * @desc 检查是否正数
 * @param $var
 * @return bool|float
 */
function check_positive_number($var) {
	if (!is_numeric($var)) {
		return false;
	}
	$var = floatval($var);
	if ($var >= 0) {
		return $var;
	} else {
		return false;
	}
}

/**
 * @desc 检查是否负整数
 * @param unknown $var
 * @return boolean
 */
function check_negative_integer($var) {
	if (!is_numeric($var)) {
		return false;
	}
	$var = intval($var);
	if ($var<0) {
		return $var;
	} else {
		return false;
	}
}

/**
 * @desc 检查手机号码
 * @param mixed $var
 * @return boolean
 */
function check_cellphone_format($var) {
	if (!$var) {
		return false;
	}
	
	if (preg_match('/^[1][3578][0-9]{9}$/', $var)) {
		return $var;
	} else {
		return false;
	}
}

/**
 * @desc 检查是否自然数
 * @param unknown $var
 * @return boolean
 */
function check_nature_integer($var) {
	if (!is_numeric($var)) {
		return false;
	}
	$var = intval($var);
	if ($var>=0) {
		return $var;
	} else {
		return false;
	}
}


/*---------------------数据库字段验证-----------------------------*/

function checkUserExist($data) {
	$User = D("User");
	if ($User->where("username='{$data}'")->select()) {
		return false;
	} else {
		return true;
	}
}

function checkUsername($data) {
	if (preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/', $data)) {
		return true;
	} else {
		return false;
	}
}

function checkPassword($data) {
	if (preg_match('/^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,18}$/', $data)) {
		return true;
	} else {
		return false;
	}
}

function checkCellphoneExist($data) {
	$User = D("User");
	$user = $User->where("cellphone='{$data}'")->select();
	if ($user && $user[0]['id'] != $_SERVER['HTTP_USER_ID']) {
		return false;
	} else {
		return true;
	}
}

function checkCellphone($data) {
	if (preg_match('/^[1][3578][0-9]{9}$/', $data)) {
		return true;
	} else {
		return false;
	}
}

function checkSubUserExist($data) {
	$SubClient = D("SubClient");
	if ($SubClient->where("username='{$data}'")->select()) {
		return false;
	} else {
		return true;
	}
}

function checkGeneralizationLength($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length >= 2 && $length <= 40) {
		return true;
	} else {
		return false;
	}
}

function checkZouMaDengLength($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length >= 12 && $length <= 60) {
		return true;
	} else {
		return false;
	}
}

function checkTextLength6($data) {
    $data = iconv('utf-8', 'gbk', $data);
    $length = strlen($data);
    if ($length <= 6) {
        return true;
    } else {
        return false;
    }
}

function checkTextLength12($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length <= 12) {
		return true;
	} else {
		return false;
	}
}

function checkTextLength16($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length <= 16) {
		return true;
	} else {
		return false;
	}
}

function checkTextLength20($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length <= 20) {
		return true;
	} else {
		return false;
	}
}

function checkTextLength32($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length <= 32) {
		return true;
	} else {
		return false;
	}
}

function checkTextLength64($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length <= 64) {
		return true;
	} else {
		return false;
	}
}

function checkTextLength80($data) {
	$data = iconv('utf-8', 'gbk', $data);
	$length = strlen($data);
	if ($length <= 80) {
		return true;
	} else {
		return false;
	}
}

function findRecord($field_name, $file_value, $table) {
    return $table->findOne(array($field_name=>$file_value));
}

/*********************************HTTP*******************************/
/**
 * @desc http请求
 * @param $url
 * @param $post
 * @param $is_post
 * @return string
 */
function httpRequest($url, $post, $is_post=1) {
	$default = array(
			CURLOPT_POST => $is_post,
			CURLOPT_HEADER => 1,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 4,
			CURLOPT_POSTFIELDS => http_build_query($post)
	);

	$ch = curl_init();
	curl_setopt_array($ch, $default);
	if( ! $result = curl_exec($ch))
	{
		trigger_error(curl_error($ch));
	}
	curl_close($ch);
	return $result;
}

function curlGet($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch,CURLOPT_HEADER, 0);

    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

/**
 * @desc 随机字符串
 * @param $len
 * @param $type
 * @return integer
 */
function buildRandomCode($len=6, $type='NUMBER') {
	$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
	
	switch($type) {
		case 'BOTH':
			$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-@#~';
			break;
		case 'CHAR':
			$chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-@#~';
			break;
		case 'NUMBER':
			$chars='0123456789';
			break;
			
	}
	mt_srand((double)microtime()*1000000*getmypid());
	$code="";
	while(strlen($code)<$len) {
		$code.=substr($chars,(mt_rand()%strlen($chars)),1);
	}
	return $code;
}


/****************************Excel导出功能******************************/
/**
 * @desc excel export
 * @example
 * 		$option['filename'] = "推广计划" . date("Y-m-d") . ".xlsx";
 *		$option['author'] = $_SESSION['global_user']['username'];
 *		$option['title'] = '推广计划数据报表';
 *		$option['subject'] = '数据报表';
 *		$option['desc'] = '数据报表';
 *		$option['keyword'] = '推广计划';
 *		$option['category'] = '推广计划';
		
 *		$option['header'] = array('一行一列', '一行二列', '一行三列');
 *		$option['data'] = array(
 *				array('二行一列', '二行二列', '二行三列'),
 *				array('三行一列', '三行二列', '三行三列'),
 *		);
 * @author JSS
 * @param $options
 */
function excelExport($options = array()) {
	import("Org.Util.PHPExcel");
	$objExcel = new \PHPExcel();

	//设置EXCEL属性
	$objExcel->getProperties()->setCreated($options['author'])
	->setLastModifiedBy($options['author'])
	->setTitle($options['title'])
	->setSubject($options['subject'])
	->setDescription($options['desc'])
	->setKeywords($options['keyword'])
	->setCategory($options['category']);

	$start = 65;//字母开始位置
	//设置第一个SHEET，设置title
	foreach ($options['header'] as $key => $value) {
		$objExcel->setActiveSheetIndex(0)->setCellValue(chr($start+$key)."1", $value);
	}
	
	
	//设置数据
	foreach ($options['data'] as $key => $value) {
		$index = $key + 2;
		foreach ($value as $k => $v) {
			$objExcel->setActiveSheetIndex(0)->setCellValue(chr($start+$k)."$index", $v);
		}
	}

	header("Content-Type: application/vnd.ms-excel");
	header("Content-Disposition: attachment; filename=\"{$options['filename']}\"");
	header("Cache-Control: max-age=0");
	$objWriter = new PHPExcel_Writer_Excel5($objExcel);
	$objWriter->save("php://output");
	exit;
}

/***************************发送邮件***********************************/
/**
 * @desc 发送站外邮件
 * 需要配置以下信息
 * MAIL_ADDRESS 邮件地址
 * MAIL_SMTP 邮件SMTP地址
 * MAIL_LOGINNAME 邮件登录帐号
 * MAIL_PASSWORD 登录密码
 * MAIL_CHARSET 编码
 * MAIL_AUTH 邮箱认证 true or false
 * MAIL_HTML 内容格式 true HTML | false TXT
 * @author JSS
 * @param $options
 */
function PostMail($options) {
	import("Org.Util.Mail");
	SendMail($options['address'], $options['title'], $options['content'], $options['author']);
}

/************************FTP上传**********************************/
/**
 * @param $src
 * @param $filename
 * @param $src_size
 * @param $dest
 * @return int
 */
function FTPUpload($src, $filename, $src_size, $dest=null) {
    $dest = $dest ? $dest : C('FTP_HOST');
	$ch = curl_init();
	$fp = fopen($src, 'r');
	curl_setopt($ch, CURLOPT_URL, $dest . $filename);
	curl_setopt($ch, CURLOPT_UPLOAD, 1);
	curl_setopt($ch, CURLOPT_INFILE, $fp);
	curl_setopt($ch, CURLOPT_INFILESIZE, $src_size);
	curl_exec ($ch);
	$error_no = curl_errno($ch);
	curl_close ($ch);
	return $error_no;
}

/**
 * @param $file_list
 */
function FTPUploadMulti($file_list) {
    $resource = ftp_connect(C('FTP_SERVER'));
    ftp_login($resource, C('FTP_USER'), C('FTP_PASS'));
    ftp_pasv($resource, true);
    //删除FTP中的上一次版本
    ftp_rmdir($resource, C('FTP_BASE_DIRECTORY'));
    ftp_mkdir($resource, C('FTP_BASE_DIRECTORY'));
    foreach ($file_list as $value) {
        //将文件更新到外网FTP
        FTPMkDir($resource, C('FTP_BASE_DIRECTORY'), str_replace($value['name'], '', $value['path']));//创建远程目录
        ftp_put($resource, C('FTP_BASE_DIRECTORY').$value['path'], $value['absolute_path'], FTP_BINARY);
    }
}

function FTPUploadMultiBin($file_list) {
    $resource = ftp_connect(C('FTP_SERVER'));
    ftp_login($resource, C('FTP_USER'), C('FTP_PASS'));
    ftp_pasv($resource, true);
    //删除FTP中的上一次版本
    ftp_rmdir($resource, C('FTP_BIN_BASE_DIRECTORY'));
    ftp_mkdir($resource, C('FTP_BIN_BASE_DIRECTORY'));
    foreach ($file_list as $value) {
        //将文件更新到外网FTP
        FTPMkDir($resource, C('FTP_BIN_BASE_DIRECTORY'), str_replace($value['name'], '', $value['path']));//创建远程目录
        ftp_put($resource, C('FTP_BIN_BASE_DIRECTORY').$value['path'], $value['absolute_path'], FTP_BINARY);
    }
}

function FTPMkDir($resource, $base_path, $path)
{
    $parts = explode('/', $path);
    $relative_path = "";
    foreach ($parts as $part) {
        if ($part) {
            $relative_path .= "/$part";
            ftp_mkdir($resource, $base_path.$relative_path);
        }
    }
}

function ftp_directory_exists($resource, $dir)
{
    // Get the current working directory
    $origin = ftp_pwd($resource);

    // Attempt to change directory, suppress errors
    if (@ftp_chdir($resource, $dir))
    {
        // If the directory exists, set back to origin
        ftp_chdir($resource, $origin);
        return true;
    }

    // Directory does not exist
    return false;
}

/************************GUID***********************************/
function GUID()
{
    if (function_exists('com_create_guid') === true)
    {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', 
    		mt_rand(0, 65535), 
    		mt_rand(0, 65535), 
    		mt_rand(0, 65535), 
    		mt_rand(16384, 20479), 
    		mt_rand(32768, 49151), 
    		mt_rand(0, 65535), 
    		mt_rand(0, 65535), 
    		mt_rand(0, 65535));
}

/**
 * @desc 生成16位订单号
 */
function OrderNumber() {
	$order_number = date('Ymd').substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
	return $order_number;
}

/*********************验证码************************************/
/**
 * @param $code
 * @param string $id
 * @return bool
 */
function check_verify($code, $id="") {
	$config = array(
			'fontSize' => 18,
			'useNoise' => false,
			'useCurve' => false,
			'length' => 4,
			'imageW' => 150,
			'imageH' => 37,
			'reset' => false,//验证成功是否重置
	);
	$Verify = new Verify($config);
	return $Verify->check($code, $id);
}


/********************生成XML************************************/
/**
 * @param $arr
 * @param int $dom
 * @param int $item
 * @return string
 */
function buildXML($arr, $dom=0, $item=0) {
	if (!$dom){
		$dom = new DOMDocument("1.0", "UTF-8");
	}
	if(!$item){
		$item = $dom->createElement("root");
		$dom->appendChild($item);
	}
	foreach ($arr as $key=>$val){
		$item_x = $dom->createElement(is_string($key)?$key:"item");
		$item->appendChild($item_x);
		if (!is_array($val)){
			$text = $dom->createTextNode($val);
			$item_x->appendChild($text);

		}else {
			arrtoxml($val,$dom,$item_x);
		}
	}
	return $dom->saveXML();
}

/**
 * @desc 对数组元素进行排序
 * @param $array 数组
 * @param $on 排序基准 KEY NAME
 * @param int $order 排序方式 SORT_ASC|SORT_DESC
 * @return array
 */
function array_sort($array, $on, $order=SORT_ASC)
{
	$new_array = array();
	$sortable_array = array();

	if (count($array) > 0) {
		foreach ($array as $k => $v) {
			if (is_array($v)) {
				foreach ($v as $k2 => $v2) {
					if ($k2 == $on) {
						$sortable_array[$k] = $v2;
					}
				}
			} else {
				$sortable_array[$k] = $v;
			}
		}
		
		switch ($order) {
			case SORT_ASC:
				asort($sortable_array);
				break;
			case SORT_DESC:
				arsort($sortable_array);
				break;
		}

		foreach ($sortable_array as $k => $v) {
			$new_array[$k] = $array[$k];
		}
	}

	return $new_array;
}

/**@desc 遍历目录
 * @param $dir
 * @param $suffix
 * @return mixed
 */
function listDir($dir)
{
	$result = array();
	if(is_dir($dir))
	{

		if ($dh = opendir($dir))
		{
			while (($file = readdir($dh)) !== false)
			{
				if((is_dir($dir."/".$file)) && $file!="." && $file!="..")
				{
					$result = array_merge($result, listDir($dir."/".$file));
				}
				else
				{
					if($file!="." && $file!="..")
					{
					    $file_size = filesize("{$dir}/{$file}");
						$data = array(
							'name' => $file,
							'absolute_path' => "{$dir}/{$file}",
                            'path' => str_replace(C('FTP_UPDATE_ROOT')."/", "", "{$dir}/{$file}"),
                            'file_size' => $file_size,
						);
                        array_push($result, $data);
					}
				}
			}
			closedir($dh);
		}
	}
	return $result;
}

/**@desc 遍历目录
 * @param $dir
 * @param $suffix
 * @return mixed
 */
function listDirBin($dir)
{
    $result = array();
    if(is_dir($dir))
    {

        if ($dh = opendir($dir))
        {
            while (($file = readdir($dh)) !== false)
            {
                if((is_dir($dir."/".$file)) && $file!="." && $file!="..")
                {
                    $result = array_merge($result, listDir($dir."/".$file));
                }
                else
                {
                    if($file!="." && $file!="..")
                    {
                        $file_size = filesize("{$dir}/{$file}");
                        $data = array(
                            'name' => $file,
                            'absolute_path' => "{$dir}/{$file}",
                            'path' => str_replace(C('FTP_UPDATE_BIN_ROOT')."/", "", "{$dir}/{$file}"),
                            'file_size' => $file_size,
                        );
                        array_push($result, $data);
                    }
                }
            }
            closedir($dh);
        }
    }
    return $result;
}

//解析定向时间字段
function parseOriPeriod($data) {
	if (!$data) {
		return 0;
	}

	$data = explode(',', $data);
	$result = array();
	foreach ($data as $value) {
		$key = substr($value, 0, 1);
		$key -= 1;
		if (isset($result[$key])) {
			array_push($result[$key], $value);
		} else {
			$result[$key] = array($value);
		}
	}
	return $result;
}

/**
 * @desc 按指定大小生成缩略图，而且不变形，缩略图函数
 * @param $f path
 * @param $t name
 * @param $tw width
 * @param $th height
 * @return bool
 */
function image_resize($f, $t, $tw = 210, $th = 210){
	$temp = array(1=>'gif', 2=>'jpeg', 3=>'png');
	list($fw, $fh, $tmp) = getimagesize($f);
	if(!$temp[$tmp]){
		return false;
	}
	$tmp = $temp[$tmp];
	$in_func = "imagecreatefrom$tmp";
	$out_func = "image$tmp";

	$f_img = $in_func($f);
	// 把图片铺满要缩放的区域
	if($fw/$tw > $fh/$th){
		$zh = $th;
		$zw = $zh*($fw/$fh);
		$_zw = ($zw-$tw)/2;
	}else{
		$zw = $tw;
		$zh = $zw*($fh/$fw);
		$_zh = ($zh-$th)/2;
	}
	$z_img = imagecreatetruecolor($zw, $zh);
	// 先把图像放满区域
	imagecopyresampled($z_img, $f_img, 0,0, 0,0, $zw,$zh, $fw,$fh);

	// 再截取到指定的宽高度
	$t_img = imagecreatetruecolor($tw, $th);
	imagecopyresampled($t_img, $z_img, 0,0, 0+$_zw,0+$_zh, $tw,$th, $zw-$_zw*2,$zh-$_zh*2);
	if($out_func($t_img, $t)){
		return true;
	}else{
		return false;
	}
}

//根据时间格式获取秒数: xx:xx:xx
function getSecond($time) {
	$time = explode(":", $time);
	$seconds = 0;
	$seconds += $time[0] * 3600;
	$seconds += $time[1] * 60;
	$seconds += intval($time[2]);
	return $seconds;
}