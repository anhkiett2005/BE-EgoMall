<?php

return [
    'model' => 'gemini-2.0-flash',
    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent',
    'api_key' => env('GEMINI_API_KEY'),
    'context_files' => [
        storage_path('app/chatbot/shop_info.txt'),
        storage_path('app/chatbot/brand.txt'),
        storage_path('app/chatbot/categories.txt'),
        storage_path('app/chatbot/policies.txt'),
        storage_path('app/chatbot/products.txt'),
        storage_path('app/chatbot/rules.txt')
    ],
];
