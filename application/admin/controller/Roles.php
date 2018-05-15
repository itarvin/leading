<?php
namespace app\admin\controller;
/**
 * 应用场景：角色类
 * @author  itarvin itarvin@163.com
 */
use app\model\Role;
use app\model\Rolepri;
use app\model\Privilege;
use think\facade\Request;
use app\Util\ReturnCode;
use app\Util\Tools;
class Roles extends Base
{
    /**
     * 应用场景：角色主页
     * @return array
     */
    public function index()
    {
        $model = new Role;

        $result = $model->search();

        if(request()->isPost()){

            $result = $model->search(Request::param());
        }
        $this->assign([
            'data' => $result['data'],
            'count' => $result['count'],
        ]);
        return $this->fetch('Roles/index');
    }


    /**
     * 应用场景：角色添加
     * @return array
     */
    public function add()
    {
        $priModel = new Privilege;

        $priData = $priModel->getTree();

        if(request()->isPost()){

            $model = new Role;

            $result = $model->store(Request::param());

            return buildReturn(['status' => $result['code'], 'info'=> $result['msg']]);
        }
        $this->assign([
            'priData' => $priData,
        ]);
        return $this->fetch('Roles/add');
    }



    /**
     * 应用场景：角色更新
     * @return array
     */
    public function edit()
    {
        $id = Request::param('rid', '', 'strip_tags');

        $model = new Role;
        $priModel = new Privilege;

        $priData = $priModel->getTree();

        $data = $model->find($id);

        // 取出当前角色已经拥有 的权限ID
        $rpModel = new Rolepri;

        $rpData = $rpModel->field('GROUP_CONCAT(pri_id) pri_id')
        ->where('role_id', $id)
        ->find();

        $exist = explode(",", $rpData['pri_id']);

        if(request()->isPost()){

            $result = $model->store(Request::param());

            return buildReturn(['status' => $result['code'], 'info'=> $result['msg']]);
        }
        $this->assign([
            'priData' => $priData,
            'data'    => $data,
            'rpData' => $exist,
        ]);
        return $this->fetch('Roles/edit');
    }


    /**
     * 应用场景：修改角色状态
     * @return array
     */
    public function changeStatus()
    {
        if(request()->isPost()){
            // 过滤注入
            $id = Request::param('rid', '', 'strip_tags');

            $model = new Role;

            $status = $model->find($id);

            if(!$status){

                return buildReturn(['status' => ReturnCode::ERROR,'info'=> Tools::errorCode(ReturnCode::ERROR)]);
            }
            $data = [];
            switch ($status['role_status']) {

                case '1':
                    $data['role_status'] = 0;
                    if($model->where('id',$id)->update($data)){

                        return buildReturn(['status' => ReturnCode::SUCCESS,'info'=> Tools::errorCode(ReturnCode::SUCCESS)]);
                    }else {

                        return buildReturn(['status' => ReturnCode::ERROR,'info'=> Tools::errorCode(ReturnCode::ERROR)]);
                    }
                    break;

                case '0':
                    $data['role_status'] = 1;
                    if($model->where('id',$id)->update($data)){

                        return buildReturn(['status' => ReturnCode::SUCCESS,'info'=> Tools::errorCode(ReturnCode::SUCCESS)]);
                    }else {

                        return buildReturn(['status' => ReturnCode::ERROR,'info'=> Tools::errorCode(ReturnCode::ERROR)]);
                    }
                    break;
            }
        }
    }


    /**
    * 删除角色
    */
    public function delete()
    {
        if(request()->isPost()){
            // 过滤注入
            $id = Request::param('rid', '', 'strip_tags');

            $model = new Role;

            $result = $model->del($id);

            return buildReturn(['status' => $result['code'], 'info'=> $result['msg']]);
        }
    }
}