const template =
  `
<el-card style="width: 600px">
  <div slot="header">
    {{title}}
  </div> 
  
  <div style="display: flex; flex-wrap: wrap;">
    <div v-for="(item, index) in items" :style="{color: index % 2?'red' : 'green'}" style="cursor: pointer;padding: 3px; margin: 3px;
        border: 1px solid gainsboro;" @click="confirm(item)">{{ item.showLabel }}</div>
  </div>
</el-card>
`

export default {
  template,
  props: ['promise', 'title', 'items'],
  data () {
    return {}
  },
  methods: {
    cancel () {
      this.promise.reject('cancel')
    },
    confirm (item) {
      this.promise.resolve(item)
    },
  }
}