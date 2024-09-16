<?php

namespace mod_payactiviti\task;

class remove_expired_transaction extends \core\task\scheduled_task
{
    public function get_name()
    {
        return "Remove Expired Ipaymu Transaction";
    }

    public function execute()
    {
        global $DB;
        $now = time();
        $expired_transactions = $DB->get_records_select('payactiviti_student', 'timeexpired < ?', [$now]);
        foreach ($expired_transactions as $expired_transaction) {
            $DB->delete_records('payactiviti_transactions', ['id' => $expired_transaction->id]);
        }
    }
}
