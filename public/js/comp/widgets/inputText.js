const template =
  `
<el-card style="width: 600px">
  <div slot="header">
    请输入文本消息
  </div> 
  
  <el-input
    type="textarea"
    :autosize="{ minRows: 2, maxRows: 8}"
    placeholder="请输入内容"
    v-model="text">
  </el-input>
  <el-row justify="end" type="flex" style="margin-top: 20px;">
    <el-button type="danger" @click="cancel">取消</el-button>
    <el-button type="primary" @click="confirm">确认</el-button>
  </el-row>
</el-card>
`

export default {
  template,
  props: ['promise'],
  data () {
    return {
      text: ''
    }
  },
  methods: {
    cancel () {
      this.promise.reject('cancel')
    },
    confirm () {
      if (!this.text)
        return this.$message.error('请输入文本内容')
      this.promise.resolve(this.text)
    },
  }
}