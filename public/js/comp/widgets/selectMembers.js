import Vue from 'vue'

const template =
  `
<el-card>
  <div slot="header">
    {{title}}, 选择群成员
  </div> 
  
  <el-transfer
      style="text-align: left; display: inline-block"
      filterable
      v-model="selected"
      :titles="['不发送', '发送']"
      :data="members">
      <span slot-scope="{ option }" :title="option.label" style="display: flex; align-items: center">{{ option.showLabel }}</span>
   </el-transfer>
  
  <el-row justify="end" type="flex" style="margin-top: 20px;">
    <el-button type="danger" @click="cancel">取消</el-button>
    <el-button type="primary" @click="confirm">确认</el-button>
  </el-row>
</el-card>
`


export default {
  template,
  props: ['promise', 'title' , 'members', 'selected'],
  data () {
    return {
    }
  },
  methods: {
    cancel () {
      this.promise.reject('cancel')
    },
    confirm () {
      this.promise.resolve(this.selected)
    },
  }
}