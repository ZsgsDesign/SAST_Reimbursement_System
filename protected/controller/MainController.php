<?php

class MainController extends BaseController
{
    public function actionIndex()
    {
        $this->project_name = 'SAST财务报销系统';
        if ($this->islogin) {
            $db_user = new Model('users');
            $db_reim = new Model('reimbursements');

            $page_ing = arg('page_ing', 1);
            $page_ed = arg('page_ed', 1);
            $user_info = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID]);
            $this->uid = $user_info['uid'];

            $this->reim_list = $db_reim->findAll(['uid=:uid AND status!=1', ':uid' => $this->uid], 'rid desc', '*', [$page_ing, 12, 7]);
            $this->count_ing = count($this->reim_list);
            if (!empty($db_reim->page)) {
                $this->pager_ing = $db_reim->page;
                $db_reim->page = array();
            }
            $this->reim_list_ed = $db_reim->findAll(['uid=:uid AND status=1', ':uid' => $this->uid], 'rid desc', '*', [$page_ed, 12, 7]);
            $this->count_ed = count($this->reim_list_ed);
            if (!empty($db_reim->page)) {
                $this->pager_ed = $db_reim->page;
                $db_reim->page = array();
            }
            $status_list = [
                '0' => '待审批',
                '1' => '已通过',
                '2' => '被驳回',
                '3' => '被挂起',
            ];
            if (count($this->reim_list) >= 1) {
                foreach ($this->reim_list as &$value) {
                    if ($value['status'] == 2) {
                        $this->tips_info = '你存在被驳回的报销项目，请尽快修改信息以恢复其待审核状态';
                    }
                    $value['status'] = $status_list[$value['status']];
                }
            }
        }
    }
}
