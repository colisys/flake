<?php

namespace App\Model;

use Flake\Persistent\Model;

/**
 * Example User Model
 * 
 * @property int $id
 * @property string $username
 * @property string $password
 * @property int $time
 */
class UserModel extends Model
{
    public static string $table = 'users';
    public static array $fields = ['id', 'username', 'password', 'time'];
    public static array $fillable = ['username', 'password', 'time'];
    public static array $visible = ['id', 'username', 'time'];
}
