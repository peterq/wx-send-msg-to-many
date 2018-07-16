import info from 'es6!./info'
import fileHelper from 'es6!./file-helper'
import tasks from 'es6!./tasks/index'

const template = `

<el-container v-if="$state.wxData.contacts.friends" style="padding: 10px" class="full">
  <el-aside width="300px">
     <info class="full"></info>
  </el-aside>
  <el-main>
    <tasks class="full"></tasks>
  </el-main>
  <el-aside width="500px">
    <file-helper class="full"></file-helper>
  </el-aside>
</el-container>

`

export default {
  template,
  components: {
    info,
    fileHelper,
    tasks
  }
}
