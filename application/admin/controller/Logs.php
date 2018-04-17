<?php
namespace app\admin\controller;
use app\admin\model\Log;
use app\admin\model\Logact;
use think\facade\Request;
use app\util\Tools;
class Logs extends Base{
    /**
     * 主页显示
     */
    public function index()
    {
        $log = new Log;
        $acts = Logact::field('id,act_name')->select();
        // 获取当前管理员的客户会员
        $where = [];
        $map = [];
        // 默认取出当天范围内的日志
        $where[] = ['a.newtime','between',[date('Y-m-d',time()),date('Y-m-d H:i:s',time())]];
        if(request()->isPost())
        {
            // 重新声明where条件
            $where = [];
            $start = Request::param('start','','trim');
            $end = Request::param('end','','trim');
            $aid = Request::param('aid','','trim');
            $keyword = Request::param('keyword','','trim');

            //check time
            if ($start && $end) {
                $where[] = ['a.newtime','between',[$start,$end]];
            }elseif($start){
                $where[] = ['a.newtime','GT',$start];
            }elseif ($end) {
                $where[] = ['a.newtime','LT',$end];
            }

            // check keyword
            if (!empty($aid) && !empty($keyword)) {
                switch ($aid) {
                    case '11':
                        $where[] = ['a.qq', 'LIKE', "%$keyword%"];
                        // 对应的(sql in)数组
                        $li = Tools::logactKey('cus_insert').','.Tools::logactKey('buy_insert').','.Tools::logactKey('cus_delete').','.Tools::logactKey('buy_delete').','.Tools::logactKey('cus_change').','.Tools::logactKey('cus_isdelete');
                        $where[] = ['a.act','in',$li];
                        break;
                    case 'all':
                        $where[] = ['a.qq','LIKE', "%$keyword%"];
                        $map[] = ['a.note','LIKE', "%,$keyword,%"];
                        break;
                    case Tools::logactKey('delete_import'):
                        $where[] = ['a.note', 'LIKE', "%,$keyword,%"];
                        break;
                    default:
                        $where[] = ['a.qq', 'LIKE', "%$keyword%"];
                        $where[] = ['a.act','EQ',$aid];
                        break;
                }
            }else if(!empty($aid)){
                $where[] = ['a.act','EQ',$aid];
            }
        }
        // 提取对应数据
        $list = $log->alias('a')
        ->field('a.*,b.users,c.act_name')
        ->join('admin b','a.uid = b.id')
        ->join(['logs_act'=>'c'],'a.act = c.id')
        ->where($where)->whereOr($map)->order('id desc')->paginate();
        // 是否提交了关键字
        if(!empty($keyword) && $aid == Tools::logactKey('delete_import'))
        {
            $lenght = mb_strlen($keyword,'utf8');
            // 对符合数据进行高亮显示，标识位置
            foreach ($list as $key => $value) {
                $have = strpos($value['note'],$keyword);
                if($have){
                    $list[$key]['position'] = $have;
                    $list[$key]['lenght'] = $lenght;
                    $list[$key]['k'] = '1';
                }
            }
        }else {
            foreach($list as $k => $v){
                // 解析json数据
                $json = json_decode($v['note']);
                if(json_last_error() == JSON_ERROR_NONE){
                    $note = '';
                    // 替换关键字
                    foreach ($json as $key => $value) {
                        $note .= Tools::keywordReplace($key).'：'.$value."，";
                    }
                    $list[$k]['note'] = $note;
                }
                $list[$k]['k'] = '0';
            }
        }
        $count = $list->total();
        $this->assign(array(
            'list' => $list,
            'count'=>$count,
            'uid'  => $this->uid,
            'acts' => $acts,
        ));
        return $this->fetch('Logs/index');
    }
}
