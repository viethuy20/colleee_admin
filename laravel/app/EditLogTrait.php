<?php
namespace App;

use Carbon\Carbon;

use DB;
use App\Admin;
use App\EditLog;

trait EditLogTrait
{
    abstract public function getEditLogType();

    private $edit_log_appends = [];

    public function getLastEditLogAttribute() : ?EditLog
    {
        if (array_key_exists('last_edit_log', $this->edit_log_appends)) {
            return $this->edit_log_appends['last_edit_log'];
        }
        if (!isset($this->id)) {
            return null;
        }
        $target_id = $this->id;
        $this->edit_log_appends['last_edit_log'] = EditLog::where('type', '=', $this->getEditLogType())
            ->where('target_id', '=', $target_id)
            ->orderBy('id', 'desc')
            ->first();
        return $this->edit_log_appends['last_edit_log'];
    }

    public function getAdminAttribute() : ?Admin
    {
        $edit_log = $this->getLastEditLogAttribute();
        if (!isset($edit_log->id)) {
            return null;
        }

        return Admin::where('id', '=', $edit_log->admin_id)
            ->first();
    }

    public function getUpdatedAtAttribute() : ?Carbon
    {
        $edit_log = $this->getLastEditLogAttribute();
        if (!isset($edit_log->id)) {
            return null;
        }
        return $edit_log->created_at;
    }

    public function saveEditLog(int $admin_id, string $message)
    {
        EditLog::createLog($this->getEditLogType(), $admin_id, $this->id, $message);
        if (array_key_exists('last_edit_log', $this->edit_log_appends)) {
            unset($this->edit_log_appends['last_edit_log']);
        }
    }
}
