const template =
  `
<el-card class="box-card">
  <div slot="header">
    <span>使用微信客户端扫码登录微信账号</span>
  </div>
  <div>
    <img :src="$state.wxData.qrcode" alt="二维码">
  </div>
</el-card>
`

export default {
  template,
  data () {
    return {
      form: {
        username: '',
        password: ''
      }
    }
  },
  methods: {
    async onSubmit () {
      console.log(this.$rpc)
      try {
        let username = await this.$rpc.call('login', this.form)
        this.$message.success('欢迎你, ' + username)
        this.$state.username = username
        this.$state.layers.login.show = false
      } catch (e) {
        this.$message.error(e.message)
      }
    }
  }
}
