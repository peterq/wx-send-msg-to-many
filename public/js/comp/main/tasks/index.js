import newTask from 'es6!./new-task'
import task from 'es6!./task'
const template = `
<el-card class="full-card">
  <div slot="header">
    任务列表
  </div>
  
  <new-task></new-task>
  
  <task v-for="task in $state.tasks" :task="task"></task>
  
</el-card>
`

export default {
  template,
  components: {
    newTask, task
  }
}
