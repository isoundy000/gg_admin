# gg_admin
杠杠麻将后台
#接口说明
* 示例 http://www.example.com/admin/user/users/param1/value1/.../paramN/valueN
* 使用GET PUT POST DELETE 对应数据库的查 改 增 删 操作
* 参数名称与数据库字段一致
#数据库表说明
表命名为：前缀_名称，在代码体现为模块名_控制器名，基本上一个表对应一个资源
##后台表
##### admin_user
|字段名|类型|说明|
|:---:|:---:|:---:|
|username|string|用户名|
|password|string|密码|
|name|string|名字|
|role_id|ObjectID|角色ID|
|status|int|状态|
|date|string|创建时间|
##### admin_role
|字段名|类型|说明|
|:---:|:---:|:---:|
|name|string|权限组名|
|status|int|状态|
|permission|array|权限列表|
|date|string|创建时间|
##### admin_menu
|字段名|类型|说明|
|:---:|:---:|:---:|
|sort|int|排序|
|name|string|菜单名称|
|action|string|菜单控制方法|
|icon|string|图标|
|pid|int|父级菜单，0为父级|
