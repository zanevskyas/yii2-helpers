<?php
/**
 * Created by PhpStorm.
 * User: mikhail
 * Date: 2/26/19
 * Time: 5:18 PM
 */

namespace Kakadu\Yii2Helpers\Queue;

use Kakadu\Yii2Helpers\App\Project;
use yii\queue\redis\Queue;
use yii\di\Instance;

/**
 * Class    RedisQueue
 * @package Kakadu\Yii2Helpers\Queue
 * @author  Yarmaliuk Mikhail
 * @version 1.0
 */
class RedisQueue extends Queue
{
    /**
     * @var string|Project
     */
    public $project = 'project';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->project = Instance::ensure($this->project, Project::class);
    }

    /**
     * Push job for projects
     *
     * @param object $job
     * @param array  $projectsIds
     *
     * @return void
     */
    public function pushProjectsJob($job, array $projectsIds = [])
    {
        if (empty($projectsIds)) {
            $projectsIds = Project::PROJECTS;
        }

        foreach ($projectsIds as $projectId) {
            $projectJob = $this->createProjectJob($job, $projectId);
            $this->push($projectJob);
        }
    }

    /**
     * @inheritdoc
     */
    public function push($job)
    {
        if (!$job instanceof ProjectJob) {
            $job = $this->createProjectJob($job);
        }

        if (!$job) {
            return null;
        }

        $jobName = get_class($job->job ?? $job);

        \Yii::info("Begin push job: $jobName.", \yii\queue\Queue::class);

        return parent::push($job);
    }

    /**
     *
     * Create project job
     *
     * @param object      $job
     * @param string|null $projectId
     *
     * @return ProjectJob|null
     */
    private function createProjectJob($job, string $projectId = null): ?ProjectJob
    {
        $projectId = $projectId ?? $this->project->getId();

        if ($projectId === Project::DEFAULT) {
            return null;
        }

        // Add wrap with project id
        $job = new ProjectJob([
            'projectId' => $projectId,
            'job'       => $job,
        ]);

        return $job;
    }

    /**
     * @inheritdoc
     */
    public function execute($id, $message, $ttr, $attempt, $workerPid)
    {
        $message = $this->changeProjectClassName($message);

        return parent::execute($id, $message, $ttr, $attempt, $workerPid);
    }

    /**
     * Change default job class name to project class name
     *
     * @param string $message
     *
     * @return string
     */
    protected function changeProjectClassName(string $message): string
    {
        // Change class name to project class name
        $messageObj = $this->serializer->unserialize($message);
        $projectObj = null;

        if ($messageObj instanceof IProjectJob) {
            $projectObj = $messageObj->getProjectInstance();

            if ($projectObj instanceof IProjectJob) {
                $message = $this->serializer->serialize($projectObj);
            }
        }

        return $message;
    }
}