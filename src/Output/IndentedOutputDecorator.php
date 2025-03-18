<?php
namespace Apie\Console\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

class IndentedOutputDecorator implements OutputInterface
{
    private string $indentation;

    private bool $startWithIndenting = true;

    public function __construct(
        private readonly OutputInterface $output,
        int $indent = 4
    ) {
        $this->indentation = str_repeat(' ', $indent);
    }

    /**
     * @param string|iterable<int, string> $messages
     */
    public function write(string|iterable $messages, bool $newline = false, int $options = 0): void
    {
        $indentedMessages = $this->indentMessages($messages);
        $this->startWithIndenting = false;
        $this->output->write($indentedMessages, $newline, $options);
    }

    /**
     * @param string|iterable<int, string> $messages
     */
    public function writeln(string|iterable $messages, int $options = 0): void
    {
        $indentedMessages = $this->indentMessages($messages);
        $this->startWithIndenting = true;
        $this->output->writeln($indentedMessages, $options);
    }

    /**
     * @param string|iterable<int, string> $messages
     * @return iterable<int, string>
     */
    private function indentMessages(string|iterable $messages): iterable
    {
        if (is_array($messages)) {
            $messages = implode("\n", $messages);
        }
        if ($this->startWithIndenting) {
            $messages = $this->indentation . $messages;
        }
        $messages = str_replace("\n", "\n" . $this->indentation, $messages);

        return explode("\n", $messages);
    }

    public function setVerbosity(int $level): void
    {
        $this->output->setVerbosity($level);
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

    public function setDecorated(bool $decorated): void
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated(): bool
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter): void
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter(): OutputFormatterInterface
    {
        return $this->output->getFormatter();
    }
}
