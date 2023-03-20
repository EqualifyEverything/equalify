<?php
/*
 * A bare-bones implementation of an ordered hook system, similar to that of WordPress.
 * 
 * Ordering between hooks comes from PHP's arrays being ordered dicts.
 * Ordering within hooks comes from each action's priority number.
 * 
 * This implementation doesn't include filters, but they wouldn't work too differently;
 * filters are like actions, only their Closures must return a value,
 * and a "run_filters" function would work like a reducer, chaining the filters together.
 * 
 * Tested on PHP 8.1.
 * 
 * Copyright (c) 2022 Ezra Bertuccelli
 * MIT License
 * https://gist.github.com/ebertucc/bda36c8125186d9c290596ab7a50939e
 */

class Command {
    public function __construct(
        public string $hook,
        public Closure $command, 
        public int $priority = 100,
    ) {}
}

class HookSystem {
    public function __construct(
        private array $_hooks,
        private array $_actions = [],
        private array $_filters = [],
    ) {}

    public function add_action(Command $action): void {
        $this->_actions[] = $action;
    }

    public function add_filter(Command $filter): void {
        $this->_filters[] = $filter;
    }

    // run actions - commands that return nothing, but should have some side effects, e.g. db write or echo HTML
    public function do_actions(string $hook, mixed ...$params): void {
        // filter for the given hook name
        $match_hook = fn(Command $action) => $action->hook === $hook;
        // order by priority
        $priority_sort = fn($a, $b) => $a->priority <=> $b->priority;
        
        $actions_to_run = array_filter($this->_actions, $match_hook);
        usort($actions_to_run, $priority_sort);
        foreach ($actions_to_run as $action) {
            ($action->command)(...$params);
        }
    }

    // run filters - commands that transform an input and return an output
    public function apply_filters(string $hook, mixed $input, mixed ...$params): mixed {
        // filter for the given hook name
        $match_hook = fn(Command $action) => $action->hook === $hook;
        // order by priority
        $priority_sort = fn($a, $b) => $a->priority <=> $b->priority;

        $filters_to_run = array_filter($this->_filters, $match_hook);
        usort($filters_to_run, $priority_sort);
        $output = $input;
        foreach ($filters_to_run as $filter) {
            $output = ($filter->command)($output, ...$params);
        }
        return $output;
    }

}

// Initialize hooks.
$hook_system = new HookSystem([
    'before_content', // everything before the app content
    'no_alerts_fallback', // Fallback HTML for reports page when there are no alerts to display
]);

// Define hook behaviors
// ---------------------

$hook_system->add_action(
    new Command('before_content', function() {
        // noop
    })
);

$hook_system->add_filter(
    new Command('no_alerts_fallback', function($input) { 
        return $input;
    })
);