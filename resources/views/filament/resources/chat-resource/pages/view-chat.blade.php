<x-filament-panels::page>
    <style>
        /* Override Filament's default container to make chat full width */
        .fi-page-content {
            max-width: none !important;
        }
        
        .chat-container {
            max-width: 100%;
            margin: 0;
            background: #f0f2f5;
            border-radius: 8px;
            height: 600px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: #075e54;
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .chat-header-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #128c7e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .chat-header-info h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .chat-header-info p {
            margin: 0;
            font-size: 12px;
            opacity: 0.8;
        }
        
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #e5ddd5;
            direction: ltr;
        }
        
        .message {
            display: flex;
            margin-bottom: 12px;
            animation: slideIn 0.2s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .message.received {
            justify-content: flex-start;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-bubble {
            max-width: 65%;
            padding: 8px 12px;
            border-radius: 7.5px;
            position: relative;
            box-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        
        .message.received .message-bubble {
            background: white;
            border-radius: 0 7.5px 7.5px 7.5px;
        }
        
        .message.sent .message-bubble {
            background: #dcf8c6;
            border-radius: 7.5px 0 7.5px 7.5px;
        }
        
        .message-sender {
            font-weight: 600;
            font-size: 13px;
            color: #075e54;
            margin-bottom: 2px;
        }
        
        .message-text {
            font-size: 14px;
            line-height: 1.4;
            color: #111;
            word-wrap: break-word;
            direction: rtl;
        }
        
        .message-time {
            font-size: 11px;
            color: #667781;
            margin-top: 4px;
            text-align: end;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }
        
        .message-image {
            max-width: 100%;
            border-radius: 5px;
            margin-bottom: 5px;
        }
        
        .chat-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #667781;
        }
        
        .chat-empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
    </style>

    <div class="chat-container">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-avatar">
                💬
            </div>
            <div class="chat-header-info">
                <h3>
                    {{ $record->order?->asker?->name ?? 'غير محدد' }} 
                    ↔ 
                    {{ $record->order?->answerer?->name ?? 'غير محدد' }}
                </h3>
                <p>طلب رقم: {{ $record->order_id }}</p>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="chat-messages">
            @forelse($record->messages()->orderBy('created_at', 'asc')->get() as $message)
                <div class="message {{ $message->sender_id === $record->order?->asker_id ? 'received' : 'sent' }}">
                    <div class="message-bubble">
                        <div class="message-sender">
                            {{ $message->sender?->name ?? 'مستخدم' }}
                        </div>
                        
                        @if($message->image)
                            <img src="{{ asset('storage/' . $message->image) }}" 
                                 alt="صورة" 
                                 class="message-image">
                        @endif
                        
                        @if($message->message)
                            <div class="message-text">
                                {{ $message->message }}
                            </div>
                        @endif
                        
                        <div class="message-time">
                            {{ $message->created_at->format('H:i') }}
                            @if($message->is_read)
                                <span style="color: #4fc3f7;">✓✓</span>
                            @else
                                <span style="color: #999;">✓</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="chat-empty">
                    <div class="chat-empty-icon">💭</div>
                    <p>لا توجد رسائل في هذه المحادثة</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
