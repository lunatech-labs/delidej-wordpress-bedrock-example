<?php

namespace AcyMailing\Views\Stats;

use AcyMailing\Core\AcymView;

class StatsView extends AcymView
{
    public function __construct()
    {
        parent::__construct();

        $this->tabs = [
            'globalStats' => 'ACYM_OVERVIEW',
        ];
    }

    public function isMailSelected($mailId, $clickMap)
    {
        if (acym_level(ACYM_ESSENTIAL)) {
            $this->tabs['detailedStats'] = 'ACYM_DETAILED_STATS';
            if (!empty($mailId)) {
                if ($clickMap) $this->tabs['clickMap'] = 'ACYM_CLICK_MAP';
                $this->tabs['linksDetails'] = 'ACYM_LINKS_DETAILS';
                $this->tabs['userClickDetails'] = 'ACYM_USER_CLICK_DETAILS';
                $this->tabs['statsByList'] = 'ACYM_STATS_PER_LIST';
            }
        }

        $this->config->saveConfig(['mail_stats_checked_once' => 1]);
    }
}
