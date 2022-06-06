<?php
/*
 * A bare-bones implementation of an ordered hook system, kinda like WordPress's.
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

class Action {
    public function __construct(
        public string $hook,
        public Closure $action, 
        public int $priority = 100,
    ) {}
}

class HookSystem {
    public function __construct(
        private array $_hooks,
        private array $_actions = []
    ) {}

    public function add_action(Action $action): void {
        $this->_actions[] = $action;
    }

    public function run_hook(string $hook): void {
        // filter by hook
        $filter = fn(Action $action) => $action->hook === $hook;
        // order by priority
        $sort = fn($a, $b) => $a->priority <=> $b->priority;
        $actions_to_run = array_filter($this->_actions, $filter);
        usort($actions_to_run, $sort);

        foreach ($actions_to_run as $action) {
            ($action->action)();
        }

    }

}

// Initialize hooks.
$hook_system = new HookSystem([
    'before_content' // everything before the app content
]);

// Set initial actions for hooks.
$hook_system->add_action(
    new Action('before_content', function() { 
        
        // We trigger scans every time content is loaded to
        // avoid the bloat and configuration requirements
        // of server crons. NOTE: This is why Linux is 
        // required.

    })
);