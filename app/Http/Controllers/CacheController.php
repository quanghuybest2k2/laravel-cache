<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class CacheController extends Controller
{
    // Create
    public function store(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ]);

            $user = User::create($request->all());
            $this->flushCache('users'); // Xóa cache
            return response()->json($user, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not create user'], 500);
        }
    }

    // Read
    public function index()
    {
        try {
            $cacheKey = 'users';
            $users = $this->getOrSetCache($cacheKey, function () {
                return User::all();
            });
            return response()->json($users, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not fetch users'], 500);
        }
    }

    // Update
    public function update(Request $request, $id)
    {
        try {
            $this->validate($request, [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|required|string|min:8',
            ]);

            $user = User::findOrFail($id);
            $user->update($request->all());
            $this->flushCache('users');
            return response()->json($user, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not update user'], 500);
        }
    }

    // Delete
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            $this->flushCache('users');
            return response()->json(null, 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not delete user'], 500);
        }
    }

    // Helper function to get or set cache
    private function getOrSetCache($key, $callback, $minutes = 60)
    {
        try {
            $cache = Redis::get($key);

            if (!$cache) {
                $data = $callback();
                Redis::set($key, json_encode($data), 'EX', $minutes * 60);
                return $data;
            }

            return json_decode($cache);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not handle cache'], 500);
        }
    }

    // Helper function to flush cache
    private function flushCache($tag)
    {
        try {
            Redis::del($tag);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not flush cache'], 500);
        }
    }
}
