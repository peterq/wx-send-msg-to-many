import widget from 'es6!../../widgets/index'
import message from "es6!../message";

const template = `
<el-card>
  <div slot="header">
    新增任务
  </div>
  <p>
    待发送消息:
  </p>
  <div style="padding: 5px; border: 1px solid gainsboro; margin: 5px 0;">
    <message v-for="message in messages" :needDelButton="true" :message="message"></message>
    <div v-show="!messages.length">
      暂无, 请添加消息
    </div>
  </div>
  
  <div>
     <el-button type="primary" size="small" @click.native="addTextMessage">
       添加文本消息
     </el-button>
  </div>
  
  <p>
    消息接收者:
  </p>
  <div style="padding: 5px; border: 1px solid gainsboro; margin: 5px 0; 
  display: flex;flex-wrap: wrap; max-height: 200px; overflow-y: scroll;">
    <div v-for="(receiver, index) in receivers" style="text-align: center; border: 1px solid gainsboro; margin: 2px;">
      <p :style="{color: index % 2?'red' : 'green'}" style="margin-right: 5px">{{$state.wxData.contacts.friends[receiver].showLabel}}</p>
    </div>
    <div v-show="!receivers.length">
      暂无, 请添加好友
    </div>
  </div>
  <div>
    <el-dropdown>
     <el-button type="primary" size="small">
       添加好友<i class="el-icon-arrow-down el-icon--right"></i>
     </el-button>
      <el-dropdown-menu slot="dropdown">
        <el-dropdown-item @click.native="addFromFriends">从联系人添加</el-dropdown-item>
        <el-dropdown-item @click.native="addFromGroup">从群聊添加</el-dropdown-item>
        <el-dropdown-item @click.native="addFromCatgory">从分组添加</el-dropdown-item>
      </el-dropdown-menu>
    </el-dropdown>
  </div>
  <el-button size="small" @click.native="createTask">
       创建任务
  </el-button>
</el-card>
`

export default {
  data () {
    return {
      sessionId: '',
      messages: [

      ],
      receivers: [

      ]
    }
  },
  created () {
    this.sessionId = this.$rpc.getSessionId()
    this.$event.on('add-message', message => {
      this.messages.push(message)
    })
    this.$event.on('delete-message', message => {
      this.messages.splice(this.messages.indexOf(message), 1)
    })
  },
  methods: {
    async addTextMessage () {
      let msg = await widget.promote()
      this.messages.push({
        type: 'raw-text',
        content: msg
      })
    },
    async addFromFriends () {
      let receivers = await widget.selectFromFriends({
        friends: Object.values(this.$state.wxData.contacts.friends),
        selected: this.receivers,
        sessionId: this.$rpc.getSessionId()
      })
      console.log(receivers)
      this.receivers = receivers
    },
    async addFromGroup () {
      let group = await  widget.chooseOne({
        title: '选择一个群聊',
        items: Object.values(this.$state.wxData.contacts.groups)
      })
      let receivers = await widget.selectFromMembers({
        members: group.MemberList,
        selected: this.receivers,
        title: group.showLabel
      })
      console.log(receivers)
      this.receivers = receivers
    },
    async addFromCatgory()
    {
      this.$message.error('功能开发中')
    },
    async createTask () {
      if (!this.receivers.length)
        return this.$message.error('接收人列表为空')
      if (!this.messages.length)
        return this.$message.error('消息列表为空')
      await this.$rpc.call('createTask', {
        receivers: this.receivers,
        messages: this.messages.map(m => m.type == 'raw-text' ? m : {uid: m.uid})
      })
      this.receivers = []
      this.messages = []
    }
  },
  template,
  components: {
    message
  }
}
