<?php
namespace Apie\Console\Helpers;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

final class DisplayResultHelper {
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function displayResult(mixed $result): string
    {
        $cloner = new VarCloner();
        $stream = tmpfile();
        $dumper = new CliDumper($stream);

        $dumper->dump($cloner->cloneVar($result));
        rewind($stream);
        return stream_get_contents($stream);
    }
}