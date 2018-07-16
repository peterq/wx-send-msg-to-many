@servers(['prod' => 'peterq.cn'])

{{--部署--}}
@task('deploy', ['on' => 'prod'])
cd ~/projects/wx-send-msg-to-many
git reset --hard HEAD
git pull
cd docker-env
docker-compose restart
@endtask

{{--初始化--}}
@task('init', ['on' => 'prod'])
cd ~/projects
git clone git@gitee.com:peterq/wx-send-msg-to-many.git
cd wx-send-msg-to-many/docker-env
docker-compose up -d
@endtask
