<?php

/**
* H5Pcms 后台菜单
*/
class MenuController extends Admin{
    
    protected $menu;
    protected $menu_data;
    protected $tree;

    function __construct(){
        parent::__construct();
        // $this->menu = $this->model('menu');
        $this->menu_data = $this->plugin_model($this->namespace,'menu');
        $this->tree = $this->instance('tree');
        $this->tree->config(array('id' => 'id', 'parent_id' => 'parentid', 'name' => 'name'));
    }

    // public function indexAction(){
    //  if ($this->post('submit')) {
    //      foreach ($_POST as $var => $value) {
    //          if(strpos($var,'del_') !== false){
    //              $id = (int)str_replace('del_','',$var);
    //              $this->menu->del($id);
    //          }
    //      }
    //      $this->adminMsg($this->getCacheCode('menu') . lang('success'), url('admin/menu/index'), 3, 1, 1);
    //  }

    //  $data = $this->menu->where('site='.$this->siteid)->select();
    //  $this->view->assign('list',$data);
    //  $this->view->display('admin/menu_list');
    // }

    // public function addAction(){
    //  if ($this->post('submit')){
    //      $data = $this->post('data');
    //      if (empty($data['name'])) $this->adminMsg(lang('a-fnx-63'));
    //      $data['site'] = $this->siteid;
    //      if ($this->menu->set(0,$data)){
    //          $this->adminMsg($this->getCacheCode('menu') . lang('success'),url('admin/menu/index'),3,1,1);
    //      }else {
    //          $this->adminMsg(lang('failure'));
    //      }
    //  }
    //  $this->view->display('admin/menu_add');
    // }

    // public function editAction(){
    //  $menuid = (int)$this->get('menuid');
    //  if (empty($menuid)) $this->adminMsg(lang('a-fnx-64'));
    //  if ($this->post('submit')) {
    //      $data = $this->post('data');
    //      if (empty($data['name'])) $this->adminMsg(lang('a-fnx-63'));
    //      $data['site'] = $this->siteid;
    //      $this->menu->set($menuid,$data);
    //      $this->adminMsg($this->getCacheCode('menu') . lang('success'),url('admin/menu/index'),3,1,1);
    //  }
    //  $data = $this->menu->find($menuid);
    //  if (empty($data)) $this->adminMsg(lang('a-fnx-64'));
    //  $this->view->assign('data',$data);
    //  $this->view->display('admin/menu_add');
    // }

    // public function delAction() {
    //  $menuid = (int)$this->get('menuid');
    //  if (empty($menuid)) $this->adminMsg(lang('a-fnx-64'));
    //  $this->menu->del($menuid);
    //  $this->adminMsg($this->getCacheCode('menu') . lang('success'),url('admin/menu/index'), 3, 1, 1);
    // }

    public function indexAction() {
        if ($this->post('submit_order') && $this->post('form') == 'order') {
            foreach ($_POST as $var => $value) {
                if (strpos($var,'order_') !== false) {
                    $id = (int)str_replace('order_', '', $var);
                    $this->menu_data->update(array('listorder'=>$value),'id='.$id);
                }
            }
            $this->redirect(url($this->namespace .'/admin_menu/cache'));
        }

        if ($this->post('submit_del') && $this->post('form') == 'del'){
            foreach ($_POST as $var => $value) {
                if (strpos($var,'del_') !== false) {
                    $id = (int)str_replace('del_', '', $var);
                    $this->menu_data->delete('id=' . $id);
                }
            }
            $this->redirect(url($this->namespace .'/admin_menu/cache'));
        }
        $menu_data = $this->menu_data->where('id > 0')->order('listorder ASC')->select();
        $this->view->assign(array(
            'navname' =>$data['name'],
            'list'  => $this->tree->get_tree_data($menu_data),
            ));
        $this->view->display('admin/menu_data_list');
    }

    public function adddataAction() {
        $id = (int)$this->get('id');

        if ($this->post('submit')) {
            $data = $this->post('data');
            $data['description'] = htmlspecialchars_decode($data['description']);
            if (empty($data['name']) || empty($data['url'])) $this->adminMsg(lang('a-pos-12'));
            if ($this->menu_data->set(0,$data)) {
                $this->redirect(url($this->namespace .'/admin_menu/cache'));
            }else {
                $this->adminMsg(lang('a-pos-13'));
            }
        }

        $menu_data = $this->menu_data->where('id > 0')->order('listorder ASC')->select();
        $menu_data_tree = $this->tree->get_tree($menu_data, 0, $id);
        $this->view->assign(array(
            'menu' => $menu,
            'menu_data_tree' => $menu_data_tree,
            ));
        $this->view->display('admin/menu_data_add');
    }

    public function editdataAction() {
        $id = (int)$this->get('id');

        if ($this->post('submit')) {
            $data = $this->post('data');
            $data['description'] = htmlspecialchars_decode($data['description']);
            if (empty($data['name']) || empty($data['url'])) $this->adminMsg(lang('a-pos-12'));
            if ($this->menu_data->set($id,$data)) {
                $this->redirect(url($this->namespace .'/admin_menu/cache'));
            }else {
                $this->adminMsg(lang('a-pos-13'));
            }
        }

        $data = $this->menu_data->find($id);
        if (empty($data)) $this->adminMsg(lang('a-fnx-66'));
        $menu_data = $this->menu_data->where('id > 0')->order('listorder ASC')->select();
        $menu_data_tree = $this->tree->get_tree($menu_data, 0, $data['parentid']);
        
        $this->view->assign(array(
            'data' => $data,
            'menu' => $menu,
            'menu_data_tree' => $menu_data_tree,
            ));
        $this->view->display('admin/menu_data_add');
    }

    public function deldataAction() {
        $id = (int)$this->get('id');
        if (empty($id)) $this->adminMsg(lang('a-fnx-66'));
        $this->menu_data->del($id);
        $this->redirect(url($this->namespace .'/admin_menu/cache'));
    }

    /**
     * $menu 缓存格式
     * array(
     *     menuid => array(
     *                  该导航栏目信息列表
     *              ),
     * );
     */
    public function cacheAction($show=0,$site_id=0) {
        $this->menu_data->repair();
        $site_id = $site_id ? $site_id : $this->siteid;

        $submenu = $this->menu_data->where('id > 0')->where('parentid=0')->where('ismenu=1')->order('listorder ASC, id ASC')->select();

        if($submenu){
            foreach ($submenu as $k => $v) {
                $subid = $v['id'];
                $data[$v['id']] = $this->menu_data->where('id > 0')->where('parentid='.$subid)->where('ismenu=1')->order('listorder ASC, id ASC')->select();
                $data[$v['id']]['name'] = $v['name'];
            }
        }
        // 写入缓存文件
        $this->cache->set($this->namespace . '_menu_' . $site_id, $data);
        $show or $this->adminMsg(lang('a-update'),url($this->namespace .'/admin_menu/index'), 3, 1, 1);
    }

}