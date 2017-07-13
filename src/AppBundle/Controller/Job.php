<?php
// src/AppBundle/Tools/Job.php
namespace AppBundle\Controller;

use Symfony\Component\Validator\Constraints as Assert;

class Job
{
    /**
     * @Assert\NotBlank()
     */
    public $task;

    public function getTask()
    {
        return $this->task;
    }

    public function setTask($task)
    {
        $this->task = $task;
    }



}
