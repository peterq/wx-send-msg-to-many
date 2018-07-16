const template =
  `
<el-card class="box-card">
  <div slot="header">
    <span>使用前请先登录本群发系统</span>
  </div>
  <div>
    <el-form label-width="80px">
      <el-form-item label="用户名">
        <el-input v-model="form.username"></el-input>
      </el-form-item>
      <el-form-item label="密码">
        <el-input v-model="form.password"></el-input>
      </el-form-item> 
      <el-form-item>
        <el-button type="primary" @click="onSubmit">登录</el-button>
      </el-form-item>
    </el-form>
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
