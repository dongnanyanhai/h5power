<?php

class ApiController extends Common {

	public function __construct() {
        parent::__construct();
	}
	
	/**
	  * JS调用数据
	  */
	public function jsAction() {
	    ob_start();
		$this->view->display($this->get('file'));
		$html = ob_get_contents();
		ob_clean();
		$html = addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html));
	    echo 'document.write("' . $html . '");';
	}
	
	/**
	  * 下载文件
	  */
	public function downAction() {
		$data = fn_authcode(base64_decode($this->get('file')), 'DECODE');
		$file = isset($data['finecms']) && $data['finecms'] ? $data['finecms'] : '';
		if (empty($file)) {
            $this->msg(lang('a-mod-213'));
        }
		if (strpos($file, ':/')) {
		    //远程
			header("Location: $file");
		} else {
		    //本地
            $file = str_replace('..', '', $file);
            $file = strpos($file, '/') === 0 ? APP_ROOT.$file : $file;
			if (!is_file($file)) {
                $this->msg(lang('a-mod-214') . '(#' . $file . ')');
            };
			header('Pragma: public');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Cache-Control: no-store, no-cache, must-revalidate');
			header('Cache-Control: pre-check=0, post-check=0, max-age=0');
			header('Content-Transfer-Encoding: binary');
			header('Content-Encoding: none');
			header('Content-type: ' . strtolower(trim(substr(strrchr($file, '.'), 1, 10))));
			header('Content-Disposition: attachment; filename="' . basename($file) . '"');
			header('Content-length: ' . sprintf("%u", filesize($file)));
			readfile($file);
			exit;
		}
	}
	
	/**
	  * 缩略图
	  */
	public function thumbAction() {
		$data	= fn_authcode(base64_decode($this->get('img')), 'DECODE');
		$file	= isset($data['finecms']) && $data['finecms'] && is_file($data['finecms']) ? $data['finecms'] : EXTENSION_PATH . '/null.jpg';
		$width	= (int)$this->get('width');
		$height	= (int)$this->get('height');
		if (!$width || !$height) list($width, $height) = getimagesize($file);
        $image	= $this->instance('image_lib');
		if ($this->site['SITE_WATERMARK'] == 1) {	//图片水印
			//生成临时水印图
			$temp = $file . '.thumb.' . substr(strrchr(trim($file), '.'), 1);
			copy($file, $temp);
            $image->set_watermark_alpha($this->site['SITE_WATERMARK_ALPHA'])
				  ->make_image_watermark($temp, $this->site['SITE_WATERMARK_POS'], $this->site['SITE_WATERMARK_IMAGE']);
			//缩略图
			$image->set_image_size($width, $height)->make_limit_image($temp, null);
			@unlink($temp);
        } elseif ($this->site['SITE_WATERMARK'] == 2) {	//文字水印
			//生成临时水印图
			$temp = $file . '.thumb.' . substr(strrchr(trim($file), '.'), 1);
			copy($file, $temp);
			$image->set_text_content($this->site['SITE_WATERMARK_TEXT'])
				  ->make_text_watermark($temp, $this->site['SITE_WATERMARK_POS'], $this->site['SITE_WATERMARK_SIZE']);
			//缩略图
			$image->set_image_size($width, $height)->make_limit_image($temp, null);
			@unlink($temp);
		} else {	//无水印时
			$image->set_image_size($width, $height)->make_limit_image($file, null);
        }
	}
	
	/**
	  * 文件信息查看
	  */
	public function fileinfoAction() {
		$file	= $this->post('file');	//文件
		if ($file && is_file($file)) {
			echo lang('a-att-6') . '：' . $file . '<br>' . lang('a-att-7') . '：' . date(TIME_FORMAT, @filemtime($file)) . '<br>' . lang('a-att-8') . '：' . formatFileSize(filesize($file)) . ' &nbsp;&nbsp;<a href="' . $file . '" target=_blank>' . lang('a-att-10') . '</a>';
		} else {
			echo '<a href="' . $file . '" target=_blank>' . $file . '</a>';
		}
	}
	
	/**
	  * 静态页面数据处理（JS调用）
	  */
	public function dataAction() {
		$file = $this->get('file') ? $this->get('file') : 'html';
		$data = base64_decode($this->get('data'));
		$data = fn_authcode($data, 'DECODE');
	    ob_start();
		if (count($data) == 1 && isset($data['finecms_html_to_data'])) {
			$this->view->assign('data', $data['finecms_html_to_data']);
		} else {
			$this->view->assign($data);
		}
		$this->view->display($file);
		$html = ob_get_contents();
		ob_clean();
		$html = addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html));
	    echo 'document.write("' . $html . '");';
	}
	
	/**
	  * 移动客户端模板Ajax数据调用
	  */
	public function mobiledataAction() {
		$tpl   = $this->post('tpl');	//模板
		$page  = $this->post('page');	//数据分页
		$catid = $this->post('catid');	//栏目id
		$this->view->assign(array(
			'page'  => $page + 1,
			'catid' => $catid
		));
		$this->view->display($tpl);
	}
	
	/**
	  * 移动客户端获取栏目数据
	  */
	public function categoryAction() {
		$this->view->assign('meta_title', '栏目-' . $this->site['SITE_NAME']);
		$this->view->display('category');
	}
	
	/**
	  * Jquery-autocomplete插件搜索提示
	  */
	public function searchAction() {
	    $kw  = str_replace(' ', '%', urldecode($this->get('q')));
		$mid = (int)$this->get('modelid');
		if ($kw) {
			$query = $this->content->where('title like ?', '%' . $kw . '%');
			$query->where('status=1');
			if ($mid) $query->where('modelid=' . $mid);
			$data  = $query->order('updatetime desc')->limit(10)->select();
			if ($data) {
			    foreach ($data as $t) {
				    echo $t['title'] . PHP_EOL;
				}
			}
		}
	}

	/**
	  * 会员登录信息JS调用
	  */
	public function userAction() {
	    ob_start();
		$this->view->display('user');
		$html = ob_get_contents();
		ob_clean();
		$html = addslashes(str_replace(array("\r", "\n", "\t", chr(13)), array('', '', '', ''), $html));
	    echo 'document.write("' . $html . '");';
	}
	
	
	/**
	  * 更新浏览数
	  */
	public function hitsAction() {
	    $id   = (int)$this->get('id');
		if (empty($id))	exit('document.write(\'0\');');
		$data = $this->content->find($id, 'hits');
		if (empty($data)) exit('document.write(\'0\');');
		$hits = $data['hits'];
		$this->content->update(array('hits' => 'hits+1'), 'id=' . $id);
		echo "document.write('" . ($hits + 1) . "');";
	}

	/**
	  * 获取浏览数
	  */
	public function gethitsAction() {
	    $id   = (int)$this->get('id');
		if (empty($id))	exit('document.write(\'0\');');
		$data = $this->content->find($id, 'hits');
		if (empty($data)) exit('document.write(\'0\');');
		$hits = $data['hits'];
		echo "document.write('" . ($hits) . "');";
	}
	
	/**
	  * 验证码
	  */
	public function captchaAction() {
	    $api    = $this->instance('captcha');
	    $width  = $this->get('width');
	    $height = $this->get('height');
	    if ($width)  $api->width  = $width;
	    if ($height) $api->height = $height;
	    $api->doimage((int)$this->site['SYS_CAPTCHA_MODE']);
        $this->session->set('captcha', $api->get_code());
	}
	
	/**
	 * 生成拼音
	 */
	public function pinyinAction() {
		echo word2pinyin($this->post('name'));
	}
	
	/**
	 * 获取关键字
	 */
	public function ajaxkwAction() {
	    $data = $this->post('data');
	    if (empty($data)) exit('');
	    echo getKw($data);
	}
	
	/**
	 * 联动菜单数据
	 */
	public function linkageAction() {
	    $keyid    = (int)$this->get('id');
	    $parentid = (int)$this->get('parent_id');
	    $linkage  = get_linkage_data();
	    $infos    = $linkage[$keyid]['data'];
	    $json     = array();
		foreach ($infos as $k=>$v) {
			if ($v['parentid'] == $parentid) {
				$json[] = array('region_id' => $v['id'], 'region_name' => $v['name']);
			}
		}
		echo json_encode($json);	
	}
	
	/*
	 * 百度地图调用
	 */
	public function baidumapAction() {
		$city = $this->get('city');
		$this->view->assign(array(
			'city'   => $city == '{SITE}' ? $this->site['SITE_NAME'] : $city,
			'name'   => $this->get('name'),
			'value'  => $this->get('value'),
		    'apikey' => $this->get('apikey'),
		));
		$this->view->display('../admin/baidumap');
	}
	
	/*
	 * 加入收藏夹
	 */
	public function addfavoriteAction() {
		$id   = (int)$this->post('id');
		if (empty($id)) exit(lang('api-0'));
		if (!$this->memberinfo) exit(lang('api-1'));
		$db   = $this->model('favorite');
		$row  = $db->getOne('site=' . $this->siteid . ' AND userid=' . $this->memberinfo['id'] . ' AND contentid=' . $id, null, array('id'));
		if ($row) exit(lang('api-2'));
		$data = $this->content->find($id, 'title,url');
		if (empty($data)) exit(lang('api-3'));
		$db->insert(array('site' => $this->siteid, 'title' => $data['title'], 'url' => $data['url'], 'contentid' => $id, 'userid' => $this->memberinfo['id'], 'adddate' => time()));
		exit(lang('api-4'));
	}

    /*
     * 保存微信图片
     */
    public function downWxImgAction() {
        $media_id   = $this->get('media_id');
        $dir = $this->get('dir');

        // 获取微信access_tooken

        $jssdkfile = EXTENSION_DIR."weixinjs/jssdk.php";
        if (file_exists($jssdkfile)){
            require_once $jssdkfile;
            $cfg = App::get_config();
            if ($appid == ""){
                $appid = $cfg['SITE_FNX_WXAPPID'];
            }
            if ($appsecret == ""){
                $appsecret = $cfg['SITE_FNX_WXAPPSECRET'];
            }

            $jssdk = new JSSDK($appid, $appsecret);
            $access_token = $jssdk->getAccessToken();
        }

        // 根据Media_id 获取微信服务器上的图片
        if($dir !==""){
            $save_dir = 'uploadfiles/'.$dir.'/';
        }else{
            $save_dir = 'uploadfiles/wx/';
        }
        
        $filename = time();
        $filename = $filename.rand(0,9).".jpg";

        // echo $access_token;
        // echo "\n";
        // echo $media_id;
        // echo "\n";
        // echo $filename;
        // echo "\n";
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$access_token."&media_id=".$media_id;
        // echo $url;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $img = curl_exec($curl);
        curl_close($curl);

        if (($fd = fopen($save_dir.$filename, "a")) !== false) { 
          fwrite($fd, $img);
          fclose($fd); 
        }

        echo $filename;
    }
	
	/*
	 * CMS信息
	 */
	public function indexAction() {
	    echo '版本：' . CMS_NAME . ' v' . CMS_VERSION . '<br>';
		echo '更新：' . CMS_UPDATE . '<br>';
		echo '校正：' . date('Y-m-d H:i:s', @filemtime(APP_ROOT . 'config/version.ini.php'));
	}
	
}