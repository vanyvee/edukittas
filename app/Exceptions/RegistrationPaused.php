class RegistrationPaused extends \Exception
{
    public function __construct(public readonly array $payload) {}
}