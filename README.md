# SAST_Reimbursement_System
Reimbursement System for SAST

## NOTICE

For better reference, all materials below would be written in Chinese.

# SAST财务报销系统

这是SAST财务报销系统的独立源代码，为ATSAST的OA模块源代码的一部分，本系统代码将会被整合于ATSAST的OA模块，但将作为单独部分开源并可独立使用。

## 基本功能

预期实现的基本功能包括但不限于：

1. 注册登录；
1. 权限系统
    1. 超级管理员：所有权限；
    1. 审批员：裁定某个报销是否合规并受理，更新报销进度；
    1. 用户：发起报销，查看报销进度。
1. 报销进度追踪
    1. 审批中：用户提交；
    1. 已驳回：被审批员驳回；
    1. 已受理：报销事项已经被受理并被提交学校财务处。
1. 发起报销
    1. 选择部门，填写报销物品名称、报销金额，凭证、备注等等；
    1. 发票选择：电子发票（上传文件）或纸质发票，提交申请后提示用户送往指定地点；
    1. 发票金额
        + 0~199 发票必填；
        + 200~499 发票、凭证（支付截图、交易截图）；
        + 500~999 发票、凭证（支付截图、交易截图）、申报单word文档（上传docx）；
        + 1000以上 提示被拒风险。
1. 审核报销 / 更新进度；
1. 编辑报销详情（只有审批中和已驳回可以编辑，编辑后重置为审批中）；
1. 总支出数据统计 / 各部门支出统计。

## 额外要求

+ 开发过程中，开发团队应该完成开发日志；
+ 编写项目文档（介绍各个功能）与接口文档（如果有）。

## 推荐

后端框架：`FlashPHP`

前端库：`Bootstrap Material Design`

数据库：`MySQL`

基本语言：`PHP`、`JS`、`CSS`

## 其他注意事项

因项目为ATSAST源码一部分，项目协议转为AGPL。
