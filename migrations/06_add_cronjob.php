<?php
class AddCronjob extends Migration
{
    public function up()
    {
        require __DIR__ . '/../UniZensusCronjob.class.php';
        $task_id  = CronjobScheduler::registerTask(new UniZensusCronjob());
        $schedule = CronjobScheduler::schedulePeriodic($task_id, 30, 2);
        $schedule->active = true;
        $schedule->store();
    }

    public function down()
    {
        $task_id = CronjobTask::findByClass('UniZensusCronjob')->id;
        CronjobScheduler::unregisterTask($task_id);
    }
}
