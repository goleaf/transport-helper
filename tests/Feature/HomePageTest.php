<?php

test('home page is an operational supply console', function () {
    $response = $this->get('/');

    $response
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertSeeText('Supply / Procurement Agent')
        ->assertSeeText('Deterministic replenishment')
        ->assertSeeText('Human approval required')
        ->assertSeeText('AI suggestions stay separate')
        ->assertSeeText('Open Supply Dashboard')
        ->assertSee(route('supply.dashboard'), false)
        ->assertSee(route('supply.imports.index'), false)
        ->assertSee(route('supply.proposals.index'), false)
        ->assertSee(route('supply.emails.index'), false)
        ->assertSee(route('supply.ai-extractions.index'), false)
        ->assertSee(route('supply.transport.quotes.index'), false)
        ->assertDontSeeText('Laravel News')
        ->assertDontSeeText('Documentation')
        ->assertDontSeeText('Laracasts');
});
