<?php
/**
 * Created by PhpStorm.
 * User: JSS
 * Date: 2017/1/6
 * Time: 16:57
 */
namespace Admin\Controller;
use Common\Controller\BaseController;
use Think\Upload;

class MaterialController extends BaseController {
    public function materialPost() {
        $config = array(
            'rootPath'   =>    './Uploads/',
            'savePath'   =>    '',
            'exts'       =>    array('jpg', 'png'),
        );

        $upload= new Upload($config);
        $info = $upload->upload();
        if (!$info) {
            $this->_result['error'] = $upload->getError();
            $this->response($this->_result, 'json', 400);
        } else {
            $this->_result['data']['file_path'] = $upload->rootPath . $info['image']['savepath'] . $info['image']['savename'];
            $this->response($this->_result, 'json', 201, "上传成功!");
        }
    }
}