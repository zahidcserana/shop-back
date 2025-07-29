<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes;

    protected $guarded = [];

    const ROLE_ADMIN = 'ADMIN';
    const ROLE_SALESMAN = 'SALESMAN';
    const ROLE_OWNER = 'OWNER';
    const ROLE_TECHSUPPORT = 'TECHSUPPORT';

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function createUser(array $data)
    {
        $password = $data['password'] ?? '123456';

        $user = User::create([
            'name' => $data['name'],
            'pharmacy_branch_id' => $data['pharmacy_branch_id'],
            'pharmacy_id' => $data['pharmacy_id'],
            'user_type' => $data['user_type'] ?? '',
            'user_mobile' => $data['user_mobile'] ?? '',
            'email' => $data['email'] ?? '',
            'password' => Hash::make($password),
            'verification_pin' => rand(1000, 4000),
        ]);

        $user->userid = substr(sprintf('%03d%02d%04d',
            $user->pharmacy_id,
            $user->pharmacy_branch_id,
            $user->id
        ), 0, 9);

        $user->save();

        return $user;
    }
}
