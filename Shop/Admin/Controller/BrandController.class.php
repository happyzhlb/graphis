<?php
/**
 * 品牌管理控制器
 * @author jiwaini00000
 * @copyright 2014
 */
namespace Admin\Controller;
use Think\Controller;
class BrandController extends BackendController{
    var $_brand_mod = null;
    var $upload_config = array(); //图片上传配置
    var $thumb_w=225;
    var $thumb_h=80;
    function __construct(){
        parent::__construct();
        $this->BrandController(); 
    }
    function BrandController(){
        $this->_brand_mod = D('Brand');
        $this->upload_config = array( //图片上传设置
            'maxSize' => '400000',
            'exts' => 'gif,jpg,jpeg,png',
            'subrand_name' => '',
            'savePath' => 'brand/'
        );
    }
    
    /** 品牌列表 */
    function index(){
        if(isset($_GET['brand_name']) && $_GET['brand_name']){
            $where['brand_name'] = array('like','%'.I('brand_name','','trim').'%');
        }
        $count = $this->_brand_mod->where($where)->count();
        $page = $page = new \Think\Page($count,10);
        $brands = $this->_brand_mod->where($where)->order('brand_id DESC')->limit($page->firstRow.','.$page->listRows)
                  ->select();
        $this->assign('brands',$brands); 
        $this->assign('page',$page->show());
        $this->display('./brand.index');   
    }
    
    /** 添加品牌 */
    function add(){
        if(!IS_POST){
        	$this->assign('brandCate',getBrandCate());
            $this->display('./brand.form');
        }else{
            $data = array(
                'brand_name' => I('brand_name','','trim'),
                'blogo' => $_FIname['blogo']['name'],
            	'views' => I('views','','intval'),
            	'collect_num' => I('collect_num','','intval'),
            	'catchline' => I('catchline','','trim'),
            	'introduction' => I('introduction','','trim'),
            	'brandstore' => I('brandstore','','trim'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order','','intval'),
            	'cate_id' => I('cate_id',0,'intval')
            );
            if(!$this->_brand_mod->create($data)){
                $this->error($this->_brand_mod->getError());
                return;
            }
            if($data['if_show'] == '1'){
                if(empty($data['brand_name'])){
            		$this->error('品牌名称不能为空.');
            	}
            	if(empty($data['cate_id'])){
            		$this->error('品牌类别不能为空(或者不显示).');
            	}
            	if(empty($brand['blogo']) && empty($_FILES['blogo']['name'])){
            		$this->error('品牌logo图标不能为空(或者不显示).');
            	} 
                if(empty($data['catchline'])){
            		$this->error('品牌标语不能为空(或者不显示).');
            	}
            	if(empty($data['introduction']) && empty($_FILES['blogo']['name'])){
            		$this->error('品牌简介不能为空(或者不显示).');
            	}             	
            }
            
            $letter = I('letter');
            if(empty($letter)){
            	$data['letter']=\Org\Util\Pinyin::to_first($data['brand_name']);  
            }else{
            	$data['letter'] = $letter;
            }
            
            //图片上传
            if($_FILES['blogo']['size'] || $_FILES['blogo']['size']){ 
            	$upload = new \Think\Upload($this->upload_config); 
	            if(!$file = $upload->upload($_FILES)){
	                $this->error($upload->getError());
	                return;
	            }
	            if($_FILES['blogo']['size'] ){
		            $filename = $upload->__get('rootPath').$file['blogo']['savepath'].$file['blogo']['savename'];
		            if(is_file($filename)){ //文件上传成功，生成缩略图
		                $image = new \Think\Image();
		                $image->open($filename);
		                $image->thumb($this->thumb_w,$this->thumb_h,2)->save($filename); //
		            }else{
		                $this->error('品牌LOGO上传失败');
		                return;
		            }
		            $data['blogo'] = $filename;
	            } 
	            
	            if($_FILES['b_photo']['name']){ //上传品牌主图
	                $filename = $upload->__get('rootPath').$file['b_photo']['savepath'].$file['b_photo']['savename'];
	                if(file_exists_case($filename)){ //文件上传成功，生成缩略图
	                    $image = new \Think\Image();
	                    $image->open($filename);
	                    $image->thumb($this->thumb_w,$this->thumb_h,2)->save($filename); //
	                }else{
	                    $this->error('品牌主图上传失败');
	                    return;
	                }
	                $data['b_photo'] = $filename;
	            }    
            }      
            $res =  $this->_brand_mod->add($data); 
            if(!$res){
                $this->error('品牌添加失败');
                return;
            }
            S('brands',null); //清空缓存
            $this->success('品牌添加成功',U('/Admin/Brand'));
        }
    }

    
    /** 编辑品牌 */
    function edit(){
        $brand_id = I('id','','intval');
        $brand = $this->_brand_mod->find($brand_id);   
        if(empty($brand)){
            $this->error('品牌不存在');
            return;
        }
        if(!IS_POST){
        	$this->assign('brandCate',getBrandCate());
            $this->assign('brand',$brand);
            $this->display('./brand.edit');
        }else{
            $data = array(
                'brand_id' => $brand_id,
                'brand_name' => I('brand_name'),
            	'views' => I('views','','intval'),
            	'collect_num' => I('collect_num','','intval'),
            	'catchline' => I('catchline','','trim'),
            	'introduction' => I('introduction','','trim'),
            	'brandstore' => I('brandstore','','trim'),
                'if_show' => I('if_show'),
                'sort_order' => I('sort_order',255,'intval') ,
            	'cate_id' => I('cate_id',0,'intval')
            );
            if(!$this->_brand_mod->create($data)){
                $this->error($this->_brand_mod->getError());
                return;
            }
             
            if($data['if_show'] == '1'){
                if(empty($data['brand_name'])){
            		$this->error('品牌名称不能为空.');
            	}
            	if(empty($data['cate_id'])){
            		$this->error('品牌类别不能为空(或者不显示).');
            	}
            	if(empty($brand['blogo']) && empty($_FILES['blogo']['name'])){
            		$this->error('品牌logo图标不能为空(或者不显示).');
            	} 
                if(empty($data['catchline'])){
            		$this->error('品牌标语不能为空(或者不显示).');
            	}
            	if(empty($data['introduction']) && empty($_FILES['blogo']['name'])){
            		$this->error('品牌简介不能为空(或者不显示).');
            	}      	
            }
            $letter = I('letter');
            if(empty($letter)){
            	$data['letter']=\Org\Util\Pinyin::to_first($data['brand_name']);  
            }else{
            	$data['letter'] = $letter;
            }
            //图片上传
            if($_FILES['blogo']['size'] || $_FILES['blogo']['size']){ 
            	$upload = new \Think\Upload($this->upload_config); 
	            if(!$file = $upload->upload($_FILES)){
	                $this->error($upload->getError());
	                return;
	            }
	            if($_FILES['blogo']['size'] ){
		            $filename = $upload->__get('rootPath').$file['blogo']['savepath'].$file['blogo']['savename'];
		            if(is_file($filename)){ //文件上传成功，生成缩略图
		                $image = new \Think\Image();
		                $image->open($filename);
		                $image->thumb($this->thumb_w,$this->thumb_h,2)->save($filename); //
		            }else{
		                $this->error('品牌LOGO上传失败');
		                return;
		            }
		            $data['blogo'] = $filename;
	            } 
	            
	            if($_FILES['b_photo']['name']){ //上传品牌主图
	                $filename = $upload->__get('rootPath').$file['b_photo']['savepath'].$file['b_photo']['savename'];
	                if(file_exists_case($filename)){ //文件上传成功，生成缩略图
	                    $image = new \Think\Image();
	                    $image->open($filename);
	                    $image->thumb($this->thumb_w,$this->thumb_h,2)->save($filename); //
	                }else{
	                    $this->error('品牌主图上传失败');
	                    return;
	                }
	                $data['b_photo'] = $filename;
	            }    
            }          
            
            if(FALSE===$this->_brand_mod->save($data)){
                $this->error('编辑品牌保存失败');
                return;
            }
            if(isset($data['b_photo'])){
                //删除原来老的主图
                unlink($brand['b_photo']);
            }
            if(isset($data['blogo'])){
                //删除原来老的logo图片
                unlink($brand['blogo']);
            }
            S('brands',null);
            $this->success('品牌编辑成功',U('index'));
        }
    }
    
    
    /** 异步修改品牌是否显示 */
    function ajax_edit(){
        $brand_id = I('id','','intval');
        $brand = $this->_brand_mod->field('brand_id,if_show')->find($brand_id);
        if(!$brand){
            $this->error('品牌不存在','',IS_AJAX);
            return;
        }
        $data = array(
            'brand_id'=>$brand_id,
            'if_show' => $brand['if_show']? 0 : 1
        );
        if(!$this->_brand_mod->create($data)){
            $this->error($this->_brand_mod->getError(),'',IS_AJAX);
            return;
        }
        if(!$this->_brand_mod->save($data)){
            $this->error('状态修改失败','',IS_AJAX);
            return;
        }
        S('brands',null);
        $this->success('状态修改成功',U('/Admin/Brand'),IS_AJAX);
    }
    
    /** 删除品牌 */
    function drop(){
        $brand_id = I('id','','trim');
        if(!$brand_id){
            $this->error('ID为空，删除失败');
            return;
        }
        if(strpos($brand_id,','))
            $brand_id = explode(',',$brand_id);
        if(is_array($brand_id)){
            $where['brand_id'] = array('in',$brand_id);
        }else{
            $where['brand_id'] = intval($brand_id);
        }
        //处理图片
        $brands = $this->_brand_mod->field('brand_id,blogo')->where($where)->select();
        if(!empty($brands)){
            foreach($brands as $key => $value){
                unlink($value['blogo']);
            }
        } 
        if(!$this->_brand_mod->where($where)->delete()){
            $this->error('品牌删除失败');
            return;
        }
        S('brands',null);
        $this->success('品牌删除成功',U('/Admin/Brand'));
    }
      
    /** 关联品牌列表 */
    function sel(){  
    	C('SHOW_PAGE_TRACE',0); 
    	$ids=I('ids','','trim');
    	$this->assign('ids',','.$ids.',');
    	$keywords=$_REQUEST['keywords']= trim(I('keywords','','urldecode'));
    	$keywords=str_replace(' ', '%', $keywords);
    	
        if(is_numeric($keywords)){
        	$where['a.brand_id']=array('like',"%".$keywords.'%');
        }
        if(isset($_REQUEST['keywords']) && $_REQUEST['keywords']){
            $where['brand_name'] = array('exp','like "%'.$keywords.'%" or introduction like "%'.$keywords.'%"');
        	if(strstr($keywords,'%')){
        		$temp=explode('%', $keywords);
        		$where['brand_name'] = array("exp","like '%".$keywords."%' or (introduction like '%".$temp[0]."%' and brand_name like '%".$temp[1]."%') or (introduction like '%".$temp[1]."%' and brand_name like '%".$temp[0]."%')");
        	}
        }
 		
        $count = $this->_brand_mod 
                 ->where($where)->count();
        $page = new \Think\Page($count,5);
        $page->parameter=array('cate_id'=>$cate_id,'keywords'=>$keywords,'type'=>I('type'));
        $list = $this->_brand_mod 
                ->where($where)
                ->limit($page->firstRow.','.$page->listRows)
                ->order('sort_order ASC')->select();  
                
                //echo M()->getLastsql();
                
        $this->assign('brand',$list);
        $this->assign('page',$page->show());
        if(I('type')=='single'){
        	$this->display('./brand.sel.single');
        }elseif(I('type')=='singleId'){
        	$this->display('./brand.sel.singleId');
        }else{
        	$this->display('./brand.sel');
        }
    }
    
    /** 选框-异步获取品牌列表 */
    function ajax_get_brand(){
    	$brand_ids = I('brand_ids','','trim');
    	if(I('type')=='single') $brand_ids =I('brand_id');
    	$where['brand_id']=array('IN',$brand_ids);
    	$list=$this->_brand_mod->where($where)->Field('brand_id,brand_name,blogo,b_photo')->page(1,100)->select(); 
    	$list_str='';
    	if(I('type')=='single'){
	    	foreach ($list as $key => $val){
	    		$list_str.= '<li><img src="'.dealImg($val['blogo']).'" width="80" align="middle" title="'.$val['brand_id'].'" /> '.$val['brand_name'].' - '.' <a href="'.U('edit',array('id'=>$val['brand_id'])).'" target="_blank">查看</a></li> ';
	    	}
    	}else{
    		foreach ($list as $key => $val){
	    		$list_str.= '<li brand_id="'.$val['brand_id'].'">'.$val['brand_id'].' - '.$val['brand_name'].' - '.' <a href="'.U('edit',array('id'=>$val['brand_id'])).'" target="_blank">查看</a> | <a href="#" class="del_brand">删除</a></li> ';
	    	}
    	}
    	echo $list_str; 
    }
    
} 


?>