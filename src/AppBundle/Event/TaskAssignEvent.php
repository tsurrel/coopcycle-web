<?php

namespace AppBundle\Event;

use AppBundle\Entity\Task;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Component\EventDispatcher\Event;

class TaskAssignEvent extends Event
{
    const NAME = 'task.assign';

    protected $task;
    protected $user;

    public function __construct(Task $task, UserInterface $user)
    {
        $this->task = $task;
        $this->user = $user;
    }

    public function getTask()
    {
        return $this->task;
    }

    public function getUser()
    {
        return $this->user;
    }
}
