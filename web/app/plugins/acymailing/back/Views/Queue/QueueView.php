<?php

namespace AcyMailing\Views\Queue;

use AcyMailing\Core\AcymView;

class QueueView extends AcymView
{
    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'campaigns' => 'ACYM_MAILS',
        ];

        if (acym_level(ACYM_ESSENTIAL)) {
            $this->steps['scheduled'] = 'ACYM_SCHEDULED';
        }

        $this->steps['detailed'] = 'ACYM_QUEUE_DETAILED';
    }
}
