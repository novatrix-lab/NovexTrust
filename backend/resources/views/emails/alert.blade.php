<!DOCTYPE html>
<html lang="{{ $message->locale }}" dir="{{ $message->locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ $message->subject }}</title>
</head>
<body>
    {!! nl2br(e($message->body)) !!}
</body>
</html>
