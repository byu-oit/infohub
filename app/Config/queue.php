<?php
// seconds to sleep() when no executable job is found
$config['Queue']['sleeptime'] = 30;

//Should default set up cron job to spawn new Queue worker every
//10 minutes, so we'll give a small buffer here providing a small overlap
// seconds of running time after which the worker will terminate (0 = unlimited)
$config['Queue']['workermaxruntime'] = 610;
