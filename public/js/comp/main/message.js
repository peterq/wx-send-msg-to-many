const template = `

<div  style="border: 1px solid gray; border-radius: 5px;
   padding: 10px; margin: 10px;overflow: hidden;">  
   {{ message.uid}} <el-button type="success" size="small" v-if="needAddButton && message.sendAble" @click="window.$event.fire('add-message', message)" plain> 添加到待发送列表</el-button>
   <el-button type="danger" size="small" v-if="needDelButton" @click="window.$event.fire('delete-message', message)" plain> 删除</el-button>
   <img  v-if="message.type == 'image'" :src="imageSrc" style="max-width: 100%; max-height: 200px; display: block;" alt="">
    <p v-else-if="message.type == 'raw-text'">{{message.content}}</p>
    <p v-else>类型:{{message.type}}<br>{{message.content}}</p>
</div>

`

export default {
  props: {
    message: Object,
    needAddButton: {
      default: false
    },
    needDelButton: {
      default: false
    },
  },
  template,
  data () {
    return {
      sessionId: ''
    }
  },
  created () {
    this.sessionId = this.$rpc.getSessionId()
  },
  computed: {
    imageSrc () {
      if (this.message.type != 'image')
        return ''
      return  `/?sessionId=${this.sessionId}&path=${encodeURIComponent('https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxgetmsgimg?&MsgID=' + this.message.raw.MsgId)}`
    }
  },
  components: {
  }
}
