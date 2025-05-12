<?php
namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_registers_a_user_successfully()
    {
        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User created successfully',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_it_fails_validation_when_required_fields_are_missing()
    {
        $response = $this->postJson('/api/user/register', []);

        $response->assertStatus(400)
            ->assertJsonStructure(['name', 'email', 'password']);
    }

    public function test_it_requires_a_unique_email()
    {
        User::factory()->create([
            'email' => 'duplicate@example.com',
        ]);

        $payload = [
            'name' => 'Another User',
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertStatus(400)
            ->assertJsonFragment(['email' => ['The email has already been taken.']]);
    }

    public function test_it_requires_password_confirmation()
    {
        $payload = [
            'name' => 'User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertStatus(400)
            ->assertJsonFragment(['password' => ['The password field confirmation does not match.']]);
    }

    public function test_it_returns_server_error_when_unknown_error_occurs_during_register()
    {
        $mockController = Mockery::mock(AuthController::class);
        $mockController->shouldReceive('register')
            ->andThrow(new \Exception('Unexpected failure'));

        $this->app->instance(AuthController::class, $mockController);

        Route::post('/api/user/register', [AuthController::class, 'register']);

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertStatus(500)->assertJson(['message' => 'Server Error']);
    }

    #[runInSeparateProcess]
    #[preserveGlobalState(false)]
    public function test_it_returns_500_when_user_create_throws_exception()
    {
        $userMock = Mockery::mock('alias:' . User::class);
        $userMock->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('Unexpected user create error'));

        Route::post('/api/user/register', [AuthController::class, 'register']);

        $payload = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->postJson('/api/user/register', $payload);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Could not create user',
            ]);
    }

    public function test_it_logs_in_user_and_returns_tokens()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ])
            ->assertJson([
                'token_type' => 'bearer',
            ]);
    }

    public function test_it_fails_login_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'wrong@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials'
            ]);
    }

    #[runInSeparateProcess]
    #[preserveGlobalState(false)]
    public function test_it_returns_500_if_jwt_exception_occurs()
    {
        $mock = Mockery::mock('alias:' . JWTAuth::class);
        $mock->shouldReceive('attempt')->andThrow(new JWTException('Something went wrong'));

        $this->app->instance(JWTAuth::class, $mock);

        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/user/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'error' => 'Could not create token',
            ]);
    }

    public function test_it_returns_user_data_if_token_is_valid()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ]
            ]);
    }

    public function test_it_returns_404_if_user_is_not_found()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        $user->delete();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'User not found'
            ]);
    }

    public function test_it_returns_401_if_token_is_invalid()
    {
        $response = $this->withHeader('Authorization', "Bearer {invalid-token}")
            ->getJson('/api/user');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Token not valid'
            ]);
    }

    public function test_it_logs_out_successfully_with_valid_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);
    }

    public function test_it_returns_401_if_token_is_missing()
    {
        $response = $this->postJson('/api/user/logout');

        $response->assertStatus(401);
    }

    public function test_it_deletes_user_account_and_anonymizes_email()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Account successfully deleted. Sorry to see you go, good luck on your adventures.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        $user->refresh();
        $this->assertStringNotContainsString('test@example.com', $user->email);
        $this->assertMatchesRegularExpression('/^[a-f0-9]+@.*/', $user->email);
    }

    #[runInSeparateProcess]
    #[preserveGlobalState(false)]
    public function test_it_returns_401_if_token_is_invalid_on_deletion()
    {
        // Mock JWTAuth to throw JWTException
        $mock = Mockery::mock('alias:' . JWTAuth::class);
        $mock->shouldReceive('parseToken->authenticate')
            ->once()
            ->andThrow(new JWTException('Token not valid'));

        $this->app->instance(JWTAuth::class, $mock);

        $response = $this->withHeader('Authorization', 'Bearer faketoken')
            ->deleteJson('/api/user');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Token not valid',
            ]);
    }

    public function test_it_returns_new_tokens_with_valid_refresh_token()
    {
        $user = User::factory()->create();

        $refreshPayload = JWTFactory::customClaims([
            'sub' => $user->id,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(config('jwt.refresh_ttl'))->timestamp,
            'type' => 'refresh'
        ])->make();

        $refreshToken = JWTAuth::encode($refreshPayload)->get();

        $response = $this->postJson('/api/user/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in'
            ])
            ->assertJson([
                'token_type' => 'bearer',
            ]);
    }

    public function test_it_returns_unauthorized_if_token_type_is_not_refresh()
    {
        $user = User::factory()->create();
        $payload = JWTFactory::customClaims([
            'sub' => $user->id,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(60)->timestamp,
            'type' => 'access' // incorrect type
        ])->make();

        $token = JWTAuth::encode($payload)->get();

        $response = $this->postJson('/api/user/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid token type',
            ]);
    }

    public function test_it_returns_unauthorized_if_user_does_not_exist()
    {
        $nonExistentUserId = 999999;

        $payload = JWTFactory::customClaims([
            'sub' => $nonExistentUserId,
            'iat' => now()->timestamp,
            'exp' => now()->addMinutes(60)->timestamp,
            'type' => 'refresh'
        ])->make();

        $token = JWTAuth::encode($payload)->get();

        $response = $this->postJson('/api/user/refresh', [
            'refresh_token' => $token,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'User not found',
            ]);
    }

    #[runInSeparateProcess]
    #[preserveGlobalState(false)]
    public function test_it_returns_unauthorized_if_token_is_invalid()
    {
        $jwtMock = Mockery::mock('alias:' . JWTAuth::class);
        $jwtMock->shouldReceive('setToken')->once()->andThrow(new \Exception('invalid'));

        $this->app->instance(JWTAuth::class, $jwtMock);

        $response = $this->postJson('/api/user/refresh', [
            'refresh_token' => 'faketoken',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid refresh token',
            ]);
    }
}
