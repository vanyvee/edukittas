<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Grade;

class CleanExpiredAssignments extends Command
{
    protected $signature = 'assignments:purge-expired';
    protected $description = '🧹 Remove expired assignments and clean student answers/files in grade table.';

    public function handle()
    {
        $this->info('🚀 Starting expired assignment cleanup...');

        $now = now();
        $countGrades = 0;
        $deletedFiles = 0;
        $deletedAssignments = 0;

        // 1️⃣ Fetch expired assignments based on (created_at + duration)
        $expiredAssignments = DB::table('assignments')
            ->whereRaw('DATE_ADD(created_at, INTERVAL duration DAY) <= ?', [$now])
            ->get(['id', 'files']);

        if ($expiredAssignments->isEmpty()) {
            $this->info('✨ No expired assignments found.');
            return;
        }

        $expiredIds = $expiredAssignments->pluck('id')->toArray();
        $this->info('📋 Found ' . count($expiredIds) . ' expired assignment(s).');

        // 2️⃣ Clean up their attached files in assignment table
        foreach ($expiredAssignments as $assignment) {
            if (!empty($assignment->files)) {
                $files = json_decode($assignment->files, true);
                foreach ($files as $file) {
                    if (!empty($file['path']) && Storage::disk('public')->exists($file['path'])) {
                        Storage::disk('public')->delete($file['path']);
                        $deletedFiles++;
                    }
                }
            }
        }

        // 3️⃣ Clean each Grade.assignments JSON column
        Grade::chunk(100, function ($grades) use ($expiredIds, &$countGrades, &$deletedFiles) {
            foreach ($grades as $grade) {
                $assignments = $grade->assignments ?? [];
                $changed = false;

                foreach ($assignments as $assignId => &$details) {
                    if (in_array($assignId, $expiredIds)) {
                        // Delete related student files
                        if (!empty($details['files']) && is_array($details['files'])) {
                            foreach ($details['files'] as $file) {
                                if (!empty($file['path']) && Storage::disk('public')->exists($file['path'])) {
                                    Storage::disk('public')->delete($file['path']);
                                    $deletedFiles++;
                                }
                            }
                        }
                        
                        
        // 🆕 NEW: Delete remark file if it exists in storage
                          if (!empty($details['remark']) && is_string($details['remark'])) {
                            if (Storage::disk('public')->exists($details['remark'])) {
                            Storage::disk('public')->delete($details['remark']);
                            $deletedFiles++;
                             }
                            }
                        
                        // Clear answer and files
                        unset($details['answer'], $details['files'], $details['remark']);   
                        $changed = true;
                    }
                }

                if ($changed) {
                    $grade->assignments = $assignments;
                    $grade->save();
                    $countGrades++;
                }
            }
        });

        // 4️⃣ Delete expired assignments from the assignment table
        DB::table('assignments')->whereIn('id', $expiredIds)->delete();
        $deletedAssignments = count($expiredIds);

        $this->info("✅ Completed cleanup:
            • {$deletedAssignments} expired assignment(s) deleted
            • {$countGrades} grade record(s) updated
            • {$deletedFiles} file(s) deleted");
    }
}





this goes into app/console/kernel.php

use Illuminate\Console\Scheduling\Schedule;

protected function schedule(Schedule $schedule)
{
    // Run the cleanup monthly, early morning
    $schedule->command('assignments:purge-expired')
             ->monthlyOn(1, '02:00')   // 1st of every month at 2 AM
             ->withoutOverlapping()
             ->onOneServer();
}

