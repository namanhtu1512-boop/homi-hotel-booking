<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns ok status from health check endpoint', function () {
    $response = $this->getJson('/api/health');

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'app',
                'env',
                'time',
                'database',
            ],
        ]);

    // Database phải kết nối được (không trả về chuỗi 'error: ...')
    $response->assertJsonPath('data.database', 'ok');
});