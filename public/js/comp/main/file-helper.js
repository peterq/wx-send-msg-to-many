import message from 'es6!./message'
const template = `
<el-card class="full-card">
  <div slot="header">
    文件小助手
  </div> 
  <div style="padding: 10px; overflow-y: scroll;">
      <message v-for="message in $state.filehelperMessages" :needAddButton="true" :message="message"></message>
  </div>
</el-card>
`

export default {
  template,
  components: {
    message
  }
}
