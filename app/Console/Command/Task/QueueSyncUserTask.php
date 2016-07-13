<?php
/**
 * @author MGriesbach@gmail.com
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link http://github.com/MSeven/cakephp_queue
 */
App::uses('QueueTask', 'Queue.Console/Command/Task');

/**
 * Sync one Collibra User based on BYU User info
 *
 */
class QueueSyncUserTask extends QueueTask {

	public $uses = ['Queue.QueuedTask', 'CollibraAPI'];
/**
 * @var QueuedTask
 */
	public $QueuedTask;

/**
 * Timeout for run, after which the Task is reassigned to a new worker.
 *
 * @var int
 */
	public $timeout = 0;

/**
 * Number of times a failed instance of this task should be restarted before giving up.
 *
 * @var int
 */
	public $retries = 1;

/**
 * Stores any failure messages triggered during run()
 *
 * @var string
 */
	public $failureMessage = '';

/**
 * Add functionality.
 * Will create one job in the queue, which later will be executed using run();
 *
 * @return void
 */
	public function add() {
		$this->out('CakePHP Queue Sync User task.');
		$this->hr();
		if (count($this->args) != 2) {
			$this->out('This will update one user\'s data from BYU to Collibra.');
			$this->out(' ');
			$this->out('Call like this:');
			$this->out('	cake queue add sync_user *net_id*');
			$this->out(' ');
		} else {
			if ($this->QueuedTask->createJob('SyncUser', ['netId' => $this->args[1]])) {
				$this->out('Job created');
			} else {
				$this->err('Could not create Job');
			}

		}
	}

/**
 * Run function.
 * This function is executed, when a worker is executing a task.
 * The return parameter will determine, if the task will be marked completed, or be requeued.
 *
 * @param array $data The array passed to QueuedTask->createJob()
 * @param int $id The id of the QueuedTask
 * @return bool Success
 */
	public function run($data, $id = null) {
		if (empty($data['netId'])) {
			return false;
		}
		return $this->CollibraAPI->updateUserFromByu($data['netId']);
	}

}
