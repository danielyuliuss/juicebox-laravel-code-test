<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    public function index(): LengthAwarePaginator
    {
        return User::query()->paginate(15);
    }

    public function show(User $user): User
    {
        return $user;
    }
}
