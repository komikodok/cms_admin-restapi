<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Log;
use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $rooms = Room::all();

        return response()->json([
            'status' => 'ok',
            'message' => 'Successfully fetched rooms data.',
            'data' => $rooms
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make(data: $request->all(), rules: [
            'room_number' => 'required|string|unique:rooms,room_number',
            'status' => 'required|string|in:available,occupied',
            'price' => 'required|numeric',
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($validator->fails()) {
            Log::info("error: " . $validator->errors());
            return response()->json([
                'status' => 'errors',
                'message' => 'Validation errors.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $createdRoom = Room::create([
                'room_number' => $request->input('room_number'),
                'status' => $request->input('status'),
                'price' => $request->input('price'),
            ]);
            
            $images = $request->file('images');
            foreach($images as $image) {
                $imageName = $image->getClientOriginalName();
                $image->move(public_path('images/rooms'), $imageName);

                RoomImage::create([
                    'room_id' => $createdRoom->id,
                    'image' => 'images/rooms/' . $image
                ]);
            }
        } catch (\Exception $e) {
            Log::info("error: ". $e);
            return response()->json([
                'status' => 'errors',
                'message' => 'Failed to create room data.',
                'errors' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Room data created successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $room = Room::with('room_images')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'status' => 'ok',
            'message' => 'Successfully fetched a room data.',
            'data' => $room
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make(data: $request->all(), rules: [
            'room_number' => 'required|string|unique:rooms,room_number',
            'status' => 'required|string|in:available,occupied',
            'price' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'errors',
                'message' => 'Validation errors.',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $room = Room::where('id', $id)->firstOrFail();            
            $room->update($validator->validated());
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'errors',
                'message' => 'Failed to update room data.',
                'errors' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Room data updated successfully.',
        ], Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $room = Room::where('id', $id)->firstOrFail();
        $room->delete();

        return response()->json([
            'status' => 'ok',
            'message' => 'Payment data deleted successfully.',
            'data' => $room
        ], Response::HTTP_OK);
    }
}
