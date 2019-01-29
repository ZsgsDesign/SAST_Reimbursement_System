<?php

class ReimbursementController extends BaseController
{
    public function actionInitiate()
    {
        //处理用户发起报销
        //TODO...
    }

    public function actionApproval()
    {
        //处理审批员审批报销,这里可以做成ajax的样子吗？
        //TODO...
    }

    public function actionView()
    {
        //查看某条报销的详情
        //应该有的变量：
        //$hasTransactionVoucher 有没有交易凭证 布尔型
        //$transactionVoucher_src 有的话交易凭证的图片源
        //...其他的请看View内的代码...写起来好麻烦(枯了
        //TODO...
    }

    public function actionEdit()
    {
        //处理编辑报销详情
        //TODO...
    }

    public function actionStatisticsTotality()
    {
        //处理总体统计详情有关的东西,比如总支出什么的
        //TODO...
    }

    public function actionDepartmentStatistics()
    {
        //查看某个部门的支出报销详情
        //TODO...
    }

    //We can do more.
}
