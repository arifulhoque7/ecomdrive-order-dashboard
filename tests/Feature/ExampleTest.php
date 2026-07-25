<?php

test('the root url sends visitors into the app', function () {
    $this->get(route('home'))->assertRedirect(route('dashboard'));
});
