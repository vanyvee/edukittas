// app/Console/Commands/AutoSubmitOverdueCbts.php
class AutoSubmitOverdueCbts extends Command
{
    protected $signature = 'cbt:auto-submit';
    protected $description = 'Grade attempts whose time has run out and were never submitted';

    public function handle(CbtService $cbts): int
    {
        $this->info("Auto-submitted {$cbts->autoSubmitOverdue()} attempt(s).");

        return self::SUCCESS;
    }
}