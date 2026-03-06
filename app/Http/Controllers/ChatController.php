<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Show list of conversations for the current user.
     */
    public function index()
    {
        $user = Auth::user();

        // Get all users the current user has exchanged messages with
        $conversations = collect();

        // For admins, we might want to show all drivers (or vice versa)
        // But for simplicity, we'll get all distinct users from messages
        $userIds = Message::where('sender_id', $user->id)
            ->orWhere('recipient_id', $user->id)
            ->get()
            ->flatMap(fn($msg) => [$msg->sender_id, $msg->recipient_id])
            ->unique()
            ->filter(fn($id) => $id !== $user->id)
            ->values();

        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $otherUser) {
            $lastMessage = Message::betweenUsers($user->id, $otherUser->id)
                ->latest()
                ->first();

            $unreadCount = Message::where('sender_id', $otherUser->id)
                ->where('recipient_id', $user->id)
                ->whereNull('read_at')
                ->count();

            $conversations->push([
                'user' => $otherUser,
                'last_message' => $lastMessage,
                'unread_count' => $unreadCount,
            ]);
        }

        // Optionally, if the user is admin, we could list all drivers even without messages
        if ($user->role === 'admin') {
            $allDrivers = User::where('role', 'driver')->get();
            // merge with existing, but keep those with messages first
        }

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show messages with a specific user.
     */
    public function show(User $otherUser)
    {
        $user = Auth::user();

        // Mark all messages from the other user as read
        Message::where('sender_id', $otherUser->id)
            ->where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::betweenUsers($user->id, $otherUser->id)
            ->with('sender', 'recipient')
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chat/Show', [
            'otherUser' => $otherUser,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a new message.
     */
public function store(Request $request)
{

    $request->validate([
        'recipient_id' => 'required|exists:users,id',
        'message' => 'required|string',
    ]);

    Message::create([
        'sender_id' => Auth::id(),
        'recipient_id' => $request->recipient_id,
        'message' => $request->message,
    ]);

    return redirect()->back();
}
}