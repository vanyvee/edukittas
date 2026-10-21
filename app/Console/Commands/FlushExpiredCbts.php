// app/Console/Commands/FlushExpiredCbts.php
class FlushExpiredCbts extends Command
{
    protected $signature = 'cbt:flush-expired';
    protected $description = 'Delete expired CBTs and their temporary questions';

    public function handle(CbtService $cbts): int
    {
        $this->info("Removed {$cbts->flushExpired()} expired CBT(s).");

        return self::SUCCESS;
    }
}



Step 3: Make the scheduler actually run

Laravel’s scheduler does nothing until the server calls it every minute. On your server, add one cron line (crontab -e):

* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

On shared hosting, add the same thing through the cPanel Cron Jobs screen. If you use Forge, Ploi or similar, there’s a “scheduler” toggle that does it for you.

Test it before trusting it

Run each command by hand: php artisan cbt:auto-submit and php artisan cbt:flush-expired. You should see a count printed (0 is fine).
Locally, run php artisan schedule:work and leave it going. It mimics the cron and shows each task firing.
To really test auto-submit, start an attempt on a CBT with a 1-minute duration, don’t submit, wait past the deadline (plus the grace period in your service), and watch the attempt get graded.

Two things to watch for: if your app is multi-server, withoutOverlapping() needs a shared cache (Redis or database) to work across them, 
and on one server it works as is. Also, I only saw the method names autoSubmitOverdue() and flushExpired(), not their bodies, so the step 3 test is worth doing to confirm they behave as you intended.