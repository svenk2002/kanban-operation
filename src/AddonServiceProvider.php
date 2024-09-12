<?php

namespace Svenk\KanbanOperation;

use Illuminate\Support\ServiceProvider;

class AddonServiceProvider extends ServiceProvider
{
    use AutomaticServiceProvider;

    protected $vendorName = 'svenk';
    protected $packageName = 'kanban-operation';
    protected $commands = [];
}
