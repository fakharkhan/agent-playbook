<?php

it('redirects legacy on-forge host to canonical with 301', function () {
    $response = $this->get('http://agent-playbook.on-forge.com/');

    $response->assertStatus(301);
    $response->assertRedirect('https://agent-playbook.fakhar-khan.com/');
});

it('redirects legacy fakharkhan.com host preserving path and query', function () {
    $response = $this->get('http://agent-playbook.fakharkhan.com/some/path?ref=legacy&utm=1');

    $response->assertStatus(301);
    $response->assertRedirect('https://agent-playbook.fakhar-khan.com/some/path?ref=legacy&utm=1');
});

it('does not redirect the canonical host', function () {
    $response = $this->get('http://agent-playbook.fakhar-khan.com/up');

    $response->assertSuccessful();
    expect($response->headers->get('Location'))->toBeNull();
});
