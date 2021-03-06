# 推送限额说明



## 各厂商推送限额

| 通道 | 限额 |  是否可提额  |
| :------:  | :-----:  | :----:  |
| 华为 | 每秒最多3000个设备，每次推送最多100个设备；单个设备每天最多可推10万条，如果24小时内未满3000条，将被流控（具体未说明） | 月活大于500W可申请 |
| 小米 | 目前无限制，单设备每天可推数有限制（具体未说明） | - |
| 魅族 | 目前无限制 | - |
| OPPO | 初始10000，后续按前一天累计有效设备数控制。按设备计数，非调用次数 | 目前不支持 |
| VIVO | 最少10000，后续按累计有效设备数与10000最大值控制。| 暂未见说明 |
| 苹果 | 暂未见说明 | - |

> 有效设备数指排除已卸载设备和指定日期内不活跃的设备




## 原始说明
### 华为
##### 消息流控限制 
错误代码503是指流控限制。  

默认消息流控是针对单个应用3000QPS（每秒不能超过3000个token数），最高每次推送100个。如果超过这个值，推送将被系统流控返回503错误。

针对单个设备每天不能推送超过10万条/天，否则将进行推送权益限制，需要整改并申报整改方案重新申请push权益。

针对单个设备，如果倒推24小时的应用级推送量不能超过3000条，否则也会被流控。 

全网流量较高时，也会出现系统级流控。 

##### 申请开放流控限制 
如果觉得当前应用级流控限制太小，可以进行申请扩容，要求华为用户月活跃量需要达到500W（小于500W的不予申请）。

### 魅族
##### Flyme推送服务会有哪些限制？
目前对开发者没有设置任何推送限制，后期为了保证推送质量和效果可能会对推送次数推送内容进行限制，但是不会影响正常使用。

### OPPO
##### 目前OPPO PUSH的推送数量限制的规则是？ 
对接入的应用，每天仅可推送前一天累计用户数同等数量的消息数（不限制推送的用户及单个用户接收数，仅限制当天推送总量）。  

对新接入的APP，有最低保护阈值，即当天最低可推送量为10000。 

累计用户数是指从接入OPPO PUSH的app客户端，用户安装并激活的累计用户量，去除已卸载用户数。 

累计用户数在OPPO推送运营平台可查询https://push.oppo.com/，每天会刷新。 

##### 如果限制总量超过了，会返回什么错误吗？ 

33，The number of messages exceeds the daily limit。 

##### 批量单推算一条消息，还是多条消息？ 

消息数量是指发到用户手机上的消息数量。一个广播消息推送给1万人，就是一万条消息。

### VIVO
##### 应用每天可发送的消息数量
目前的策略是不限制单推和群推的比例，可发送的单推和群推总用户量不超过SDK订阅数，保底10000，SDK订阅数和可发送的用户总量可以在开发者后台查看，关于SDK订阅数的名词解释可以参考名词解释， 全推消息不计算在内，全推消息每日可发一条。

##### 用户每天可接收的消息数量限制
目前是每个客户端每天可接收单推消息不受限制，群推消息和全推消息都属于公共类资讯，每个用户每天可以接收5条公共类消息。

### 小米
##### 小米推送服务有哪些限制？
目前针对首批合作开发者，小米推送服务没有设置任何推送频率的使用限制，之后出于防止恶意应用攻击等考虑，可能会增加对推送消息的频率、对单一用户可以接收的数量等做一些限制，但不会影响开发者的正常使用。而且所提供的推送服务完全免费。

对于单条消息,可携带的数据量最大不能超过4KB。
