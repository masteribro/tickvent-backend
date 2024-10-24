<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_the_register_endpoints() 
    {
        User::select(["id"])->delete();

        $data = [
            "first_name" => "Blessing",
            "last_name" => "Sanusi",
            "email" => "blessingsanusi97@gmail.com",
            "is_mobile" => true,
            "device_token" => "jsncskjcnsjdnsjcdnsjsdcndsnsdnsdg",
            "phone_number" => "08133667142",
            "passcode" => 111111,
            "passcode_confirmation" => 111111,
        ];

        $response = $this->postJson("/api/v1/register", $data);

        $response->assertStatus(201);

    }

    public function test_login_endpoints()
    {
        $data = [
            'email' => 'blessingsanusi97@gmail.com',
            'is_mobile' => true,
            'passcode' => 11111,
        ]
    }
}
