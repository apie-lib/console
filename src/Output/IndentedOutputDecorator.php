<?php
namespace Apie\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndentedOutputDecorator implements OutputInterface
{
    private $indentation;

    public function __construct(
        private readonly OutputInterface $output,
        int $indent = 4
    ) {
        $this->indentation = str_repeat(' ', $indent);
    }

    public function write($messages, $newline = false, $options = 0)
    {
        $indentedMessages = $this->indentMessages($messages);
        $this->output->write($indentedMessages, $newline, $options);
    }

    public function writeln($messages, $options = 0)
    {
        $indentedMessages = $this->indentMessages($messages);
        $this->output->writeln($indentedMessages, $options);
    }

    private function indentMessages($messages)
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }

        $indentedMessages = [];
        foreach ($messages as $message) {
            $lines = explode("\n", $message);
            $indentedLines = array_map(function ($line) {
                return $this->indentation . $line;
            }, $lines);
            $indentedMessages = array_merge($indentedMessages, $indentedLines);
        }

        return $indentedMessages;
    }

    public function setVerbosity(int $level)
    {
        return $this->output->setVerbosity($level);
    }

    public function getVerbosity(): int
    {
        return $this->output->getVerbosity();
    }

    public function isQuiet(): bool
    {
        return $this->output->isQuiet();
    }

    public function isVerbose(): bool
    {
        return $this->output->isVerbose();
    }

    public function isVeryVerbose(): bool
    {
        return $this->output->isVeryVerbose();
    }

    public function isDebug(): bool
    {
        return $this->output->isDebug();
    }

    public function setDecorated(bool $decorated)
    {
        return $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        return $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }
}
