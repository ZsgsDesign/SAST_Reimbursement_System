<?php

class ReimbursementController extends BaseController
{
    public function actionInitiate()
    {
        if (!$this->islogin) {
            $this->jump('/account');
        }
        if (arg('type') !== null) {
            return $this->invoice_type = arg('type');
        }

        $action = arg('action');
        if ($action == 'initiate') {
            $name = arg('name');
            $remarks = arg('remarks');
            $department = arg('department');
            $money = arg('money');
            $invoice_type = arg('invoice_type');

            if (empty($name) || empty($remarks) || empty($department) || empty($money)) {
                return $this->err_info = '填写的信息不足，请再次填写并提交';
            }

            //检查金额输入
            $pattern = '/\d{1,4}/';
            if (preg_match($pattern, $money)) {
                if ($money >= 1000) {
                    return $this->err_info = '交易额度较大，拒绝受理';
                } elseif ($money >= 500) {
                    if (empty($_FILES['transaction_voucher']['name']) || empty($_FILES['declaration']['name'])) {
                        return $this->err_info = '该交易额度必须提交交易凭证以及申报单,请仔细检查之后再一次提交';
                    }
                } elseif ($money >= 200) {
                    if (empty($_FILES['transaction_voucher']['name'])) {
                        return $this->err_info = '该交易额度必须提交交易凭证,请仔细检查之后再一次提交';
                    }
                } elseif ($money <= 0) {
                    return $this->err_info = '错误的金额！';
                }
            } else {
                return $this->err_info = '非法的金额！';
            }
            //检查发票有没有好好上传
            if ($invoice_type === '0') {
                if (empty($_FILES['invoice'])) {
                    return $this->err_info = '电子发票必须上传发票文件';
                }
            } elseif ($invoice_type !== '1') {
                if (empty($_FILES['invoice'])) {
                    return $this->err_info = '非法提交,请不要皮这个系统';
                }
            } elseif (isset($_FILES['invoice'])) {
                return $this->err_info = '有非法的文件提交,请不要皮这个系统';
            }

            //检查有没有对应的部门
            $db_department = new Model('department');
            $result = $db_department->find(['name=:name', ':name' => $department]);
            if (empty($result)) {
                return $this->err_info = '该部门不存在，请仔细检查，再次填写并提交';
            }

            //查找发起人
            $db_user = new Model('users');
            $uid = $db_user->find(['OPENID=:OPENID', ':OPENID' => $this->OPENID])['uid'];

            //唔。。申报时间
            $time = date('Y-m-d H:i:s');

            $RBM = [
                'name' => $name,
                'remarks' => $remarks,
                'department' => $result['did'],
                'status' => '0',
                'money' => $money,
                'time' => $time,
                'uid' => $uid,
            ];

            //处理上传的发票文件
            if (!empty($_FILES['invoice']['name'])) {
                $filename = $_FILES['invoice']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                if ($extension != 'pdf') {
                    return $this->err_info = '发票文件格式错误，请仔细检查，再次提交';
                }

                $hash = md5_file($_FILES['invoice']['tmp_name']);
                $RBM['invoice'] = $hash;
                move_uploaded_file($_FILES['invoice']['tmp_name'], APP_DIR.'/protected/file/invoice/'.$hash.'.pdf');
            }

            //处理上传的交易凭证
            if (!empty($_FILES['transaction_voucher']['name'])) {
                $filename = $_FILES['transaction_voucher']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['jpg', 'png'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许jpg，png上传，请重试';
                }

                $hash = md5_file($_FILES['transaction_voucher']['tmp_name']);
                $RBM['transaction_voucher'] = $hash;
                move_uploaded_file($_FILES['transaction_voucher']['tmp_name'], APP_DIR.'/protected/file/transaction_voucher/'.$hash.$extension);
            }

            //处理上传的申报单
            if (!empty($_FILES['declaration']['name'])) {
                $filename = $_FILES['declaration']['name'];
                $extension = explode('.', $filename);
                $extension = strtolower($extension[count($extension) - 1]);
                $allow_ext = ['docx'];
                if (!in_array($extension, $allow_ext)) {
                    return $this->err_info = '不支持的交易凭证格式，只允许docx上传，请重试';
                }

                $hash = md5_file($_FILES['declaration']['tmp_name']);
                $RBM['declaration'] = $hash;
                move_uploaded_file($_FILES['declaration']['tmp_name'], APP_DIR.'/protected/file/declaration/'.$hash.$extension);
            }
            //存入数据库
            $db_rbm = new Model('reimbursements');
            $rid = $db_rbm->create($RBM);

            $this->jump('/reimbursement/view/'.$rid);
        }

        //处理用户发起报销
        //TODO...
    }

    public function actionApproval()
    {
        //处理审批员审批报销,这里可以做成ajax的样子吗？
        //TODO...
    }

    public function actionViewDetail()
    {
        if (!$this->islogin) {
            $this->jump('/account');
        }

        $db_user = new Model('users');
        $user_info = $db_user -> find(['OPENID=:OPENID',':OPENID'=>$this->OPENID]);
        $uid = $user_info['uid'];

        $db_reimbursements = new Model('reimbursements');
        $reimDetails = $db_reimbursements -> findAll();

        $rid=arg('rid');
        if ($rid == null) {
            if (valid_auth($uid)) {
            }else{
                foreach ($reimDetails as $value) {
                    if($value['uid']!=$uid){
                        array_splice($value);
                    }
                }
            }
            $i=0;
            $j=0;
            foreach($reimDetails as $value){
                if($value['invoice']==null){
                    $hasInvoice[i]=0;
                    $i++;
                }else{
                    $hasInvoice[i]=1;
                    $i++;
                }
                if($value['transaction_voucher']==null){
                    $hasTransactionVoucher[j]=0;
                    $j++;
                }else{
                    $hasTransactionVoucher[j]=1;
                    $j++;
                }
            }
        }elseif(!empty($reimDetails['rid'])){
            foreach($reimDetails as $value){
                if($value['rid'] != $rid){
                    array_splice($value);
                }
            }
            if($reimDetails['invoice']==null){
                $hasInvoice=false;
            }else{
                $hasInvoice=true;
            }
            if($reimDetails['transaction_voucher']==null){
                $hasTransactionVoucher=false;
            }else{
                $hasTransactionVoucher=true;
            }
            if($reimDetails == null){
                return $this->err_info='当前并没有报销记录哦';
            }else{
                return $this->list=array($reimDetails,$hasInvoice,$hasTransactionVoucher);
            }
        }else{
            return $this->err_info = '无相关记录';
        }
        //查看某条报销的详情
        //TODO...
    }

    public function actionViewChangelog(){
        if(!$this->islogin){
            $this->jump('/account');
        }

        $db_user = new Model('users');
        $user_info = $db_user -> find(['OPENID=:OPENID',':OPENID'=>$this->OPENID]);
        $currentUID = $user_info['uid'];

        if(valid_auth($currentUID) == true){
            $rid=arg('rid');
            $db_change_log = new Model('change_log');
            $Change_Log = $db_change_log ->findAll(['rid=:rid',':rid'=>$rid]);
        }else{
            $rid=arg('rid');
            $db_reimbursements = new Model('reimbursements');
            $uid = $db_reimbursements ->find(['rid=:rid',':rid'=>$rid]);
            if($currentUID == $uid){
                $db_change_log = new Model('change_log');
                $Change_Log = $db_change_log ->findAll(['rid=:rid',':rid'=>$rid]);
            }else{
                return $this->err_info = '抱歉，不是你发起的报销，无权查看';
            }
        }
        return $this->liset = $Change_Log;
        //查看某条报销的操作记录
    }

    public function actionEdit()
    {
        //处理编辑报销详情
        //TODO...
    }

    public function actionStatisticsTotality()
    {
        //处理总体统计详情有关的东西,比如总支出什么的
        //status:
        //0 -> 待审核
        //1 -> 已通过
        //2 -> 被驳回
        //3 -> 被挂起
        //TODO...
    }

    public function actionDepartmentStatistics()
    {
        //查看某个部门的支出报销详情
        //TODO...
    }

    //We can do more.
}
