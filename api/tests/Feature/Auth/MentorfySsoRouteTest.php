<?php

it('allows the Mentorfy exchange to replace an existing OpnForm session', function () {
    $route = app('router')->getRoutes()->getByName('mentorfy.sso.exchange');

    expect($route)->not->toBeNull()
        ->and($route->gatherMiddleware())->toContain('throttle:20,1')
        ->not->toContain('guest:api');
});
