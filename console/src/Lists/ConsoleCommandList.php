<?php
namespace Apie\Console\Lists;

use Apie\Core\Lists\ItemList;
use Symfony\Component\Console\Command\Command;

class ConsoleCommandList extends ItemList
{
    public function offsetGet(mixed $offset): Command
    {
        return parent::offsetGet($offset);
    }
}
