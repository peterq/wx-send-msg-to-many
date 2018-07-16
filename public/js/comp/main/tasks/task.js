import message from "es6!../message";

const template = `
<el-card style="margin: 10px 0">
  <p>全部人数: {{task.receivers.length}}, 成功:{{task.successCount}}, 失败: {{task.failCount}}  <span style="color:green">{{progressTip}}</span></p>
  <p>
    消息:
  </p>
  <div style="padding: 5px; border: 1px solid gainsboro; margin: 5px 0;">
    <message v-for="message in task.messages" :message="message"></message>
  </div>  
  <p>
    接收者:
  </p>
  <div style="padding: 5px; border: 1px solid gainsboro; margin: 5px 0;
  display: flex;flex-wrap: wrap; max-height: 200px; overflow-y: scroll;">
    <div v-for="(receiver, index) in task.receivers" style="text-align: center; border: 1px solid gainsboro; margin: 2px;">
      <p :style="{color: index % 2?'red' : 'green'}" style="margin-right: 5px">{{$state.wxData.contacts.friends[receiver].showLabel}}</p>
    </div>
  </div>
  <el-button size="small" @click.native="pause"> 暂停 </el-button>
  <el-button size="small" @click.native="run"> 发送 </el-button>
</el-card>
`

export default {
  props: ['task'],
  data () {
    return {
      sessionId: '',
      showSendingWhomTimer: 0,
      progressTip: ''
    }
  },
  created () {
    this.sessionId = this.$rpc.getSessionId()
    this.$event.on('task.sent.' + this.task.taskId, evt => {
      this.task.successCount = evt.successCount
      this.task.failCount = evt.failCount
    })
    this.$event.on('task.sending.' + this.task.taskId, evt => {
      if (this.showSendingWhomTimer) {
        clearTimeout(this.showSendingWhomTimer)
      }
      this.progressTip = '正在发送给:' + this.$state.wxData.contacts.friends[evt.username].showLabel
      this.task.successCount = evt.successCount
      this.task.failCount = evt.failCount
      this.showSendingWhomTimer = setTimeout(_ => {
        this.progressTip = ''
      }, 3e3)
    })
  },
  methods: {
    async run () {
      await this.$rpc.call('runTask', {taskId: this.task.taskId})
    },
    async pause () {
      await this.$rpc.call('pauseTask', {taskId: this.task.taskId})
    }
  },
  template,
  components: {
    message
  }
}
